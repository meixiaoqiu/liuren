#!/usr/bin/env python3
"""用 JPL Horizons DE441 + ERFA 独立核验 Astronomy Engine moonPalace 研究实现。"""

from __future__ import annotations

import argparse
import csv
import json
import math
import random
import shutil
import statistics
import subprocess
import sys
import urllib.parse
import urllib.request
from datetime import datetime, timedelta, timezone
from pathlib import Path

import erfa
import numpy as np

ROOT = Path(__file__).resolve().parents[1]
FIXTURE = ROOT / "tests" / "Fixtures" / "moon_palace_horizons_2026.json"
API_URL = "https://ssd.jpl.nasa.gov/api/horizons.api"
AU_KM = 149_597_870.7
XU_RA_DEG = 322.8897169583333
XU_DEC_DEG = -(5 + 34 / 60 + 16.22938 / 3600)
SIX_ANCIENT_DEG = 6 * 360 / 365.2578
PALACES = ["子", "丑", "寅", "卯", "辰", "巳", "午", "未", "申", "酉", "戌", "亥"]
FIXED_TIMES = [
    "2026-01-01T00:00:00Z", "2026-03-20T12:00:00Z", "2026-06-21T00:00:00Z",
    "2026-09-12T17:10:00Z", "2026-12-21T12:00:00Z",
]


def sample_times() -> list[str]:
    """固定种子生成15个全年随机时刻，再加5个已有 fixture 时刻。"""
    rng = random.Random(4301)
    start = datetime(2026, 1, 1, tzinfo=timezone.utc)
    seconds = sorted(rng.sample(range(366 * 24 * 3600), 15))
    random_times = [(start + timedelta(seconds=value)).isoformat().replace("+00:00", "Z") for value in seconds]
    return sorted(FIXED_TIMES + random_times)


def base_query() -> dict[str, str]:
    return {
        "format": "json", "COMMAND": "'301'", "CENTER": "'500@399'", "MAKE_EPHEM": "'YES'",
        "EPHEM_TYPE": "'VECTORS'", "TIME_TYPE": "'UT'", "OUT_UNITS": "'KM-S'",
        "REF_SYSTEM": "'ICRF'", "REF_PLANE": "'FRAME'", "VEC_CORR": "'NONE'",
        "VEC_TABLE": "'2'", "CSV_FORMAT": "'YES'", "OBJ_DATA": "'YES'", "CAL_TYPE": "'GREGORIAN'",
    }


def horizons_query(params: dict[str, str]) -> dict:
    url = API_URL + "?" + urllib.parse.urlencode(params)
    with urllib.request.urlopen(url, timeout=120) as response:
        payload = json.load(response)
    if "error" in payload or "$$SOE" not in payload.get("result", ""):
        raise RuntimeError(payload.get("error", "Horizons 响应不含向量表"))
    return {"url": url, "params": params, "signature": payload.get("signature"), "result": payload["result"]}


def fetch_fixture() -> None:
    times = sample_times()
    discrete = base_query()
    discrete.update({
        "TLIST": " ".join(f"'{datetime.fromisoformat(t.replace('Z', '+00:00')).strftime('%Y-%b-%d %H:%M:%S')}'" for t in times),
        "TLIST_TYPE": "'CAL'",
    })
    boundary = base_query()
    boundary.update({"START_TIME": "'2026-09-13 00:00'", "STOP_TIME": "'2026-09-24 00:00'", "STEP_SIZE": "'2 h'"})
    payload = {
        "description": "Raw NASA/JPL Horizons DE441 geocentric Moon vectors for independent moonPalace cross-check.",
        "random_seed": 4301,
        "sample_times_utc": times,
        "sample_query": horizons_query(discrete),
        "boundary_query": horizons_query(boundary),
    }
    FIXTURE.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")


def parse_vectors(raw: str) -> list[dict]:
    table = raw.split("$$SOE", 1)[1].split("$$EOE", 1)[0]
    rows = []
    for row in csv.reader(line for line in table.splitlines() if line.strip()):
        rows.append({
            "jd_utc": float(row[0]), "calendar": row[1].strip(),
            "position_km": np.array([float(row[2]), float(row[3]), float(row[4])]),
            "velocity_km_s": np.array([float(row[5]), float(row[6]), float(row[7])]),
        })
    return rows


def iso_from_jd(jd: float) -> str:
    value = datetime.fromtimestamp((jd - 2440587.5) * 86400, tz=timezone.utc)
    return value.isoformat(timespec="milliseconds").replace("+00:00", "Z")


def tt_jd(instant: datetime) -> tuple[float, float]:
    second = instant.second + instant.microsecond / 1_000_000
    utc1, utc2 = erfa.dtf2d("UTC", instant.year, instant.month, instant.day, instant.hour, instant.minute, second)
    tai1, tai2 = erfa.utctai(utc1, utc2)
    return erfa.taitt(tai1, tai2)


def eqd_vector(vector: np.ndarray, instant: datetime) -> np.ndarray:
    return erfa.pnm06a(*tt_jd(instant)) @ vector


def ra_deg(vector: np.ndarray) -> float:
    return math.degrees(math.atan2(vector[1], vector[0])) % 360


def angular_error_arcsec(a: np.ndarray, b: np.ndarray) -> float:
    ua, ub = a / np.linalg.norm(a), b / np.linalg.norm(b)
    return math.degrees(math.atan2(np.linalg.norm(np.cross(ua, ub)), float(np.dot(ua, ub)))) * 3600


def circular_difference_arcsec(a: float, b: float) -> float:
    return abs((a - b + 180) % 360 - 180) * 3600


def palace_from_ra(moon_ra: float, xu_ra: float) -> dict:
    zi = (xu_ra + SIX_ANCIENT_DEG) % 360
    delta = (moon_ra - zi) % 360
    signed = (delta + 180) % 360 - 180
    sector = math.floor((signed + 15) / 30)
    index = (-sector) % 12
    center_delta = (signed - sector * 30 + 180) % 360 - 180
    distance = max(0.0, 15 - abs(center_delta))
    return {"moon_ra_deg": moon_ra, "xu_ra_deg": xu_ra, "zi_center_ra_deg": zi, "delta_ra_deg": delta,
            "palace_index": index, "palace": PALACES[index], "nearest_boundary_distance_deg": distance,
            "nearest_boundary_distance_arcmin": distance * 60}


def independent_result(vector: np.ndarray, instant: datetime) -> dict:
    moon = eqd_vector(vector, instant)
    ra = math.radians(XU_RA_DEG)
    dec = math.radians(XU_DEC_DEG)
    star = np.array([math.cos(dec) * math.cos(ra), math.cos(dec) * math.sin(ra), math.sin(dec)])
    return palace_from_ra(ra_deg(moon), ra_deg(eqd_vector(star, instant)))


def node_reference(times: list[str], boundary_starts: list[str] | None = None) -> dict:
    node = shutil.which("node")
    if not node:
        raise RuntimeError("找不到 Node.js")
    request = json.dumps({"times": times, "boundary_starts": boundary_starts or []})
    process = subprocess.run(
        [node, str(ROOT / "tools" / "moon_palace_crosscheck_reference.mjs")], input=request,
        text=True, encoding="utf-8", capture_output=True, check=True, cwd=ROOT,
    )
    return json.loads(process.stdout)


def hermite_state(rows: list[dict], jd: float) -> np.ndarray:
    for left, right in zip(rows, rows[1:]):
        if left["jd_utc"] <= jd <= right["jd_utc"]:
            dt = (right["jd_utc"] - left["jd_utc"]) * 86400
            s = (jd - left["jd_utc"]) / (right["jd_utc"] - left["jd_utc"])
            h00, h10 = 2*s**3 - 3*s**2 + 1, s**3 - 2*s**2 + s
            h01, h11 = -2*s**3 + 3*s**2, s**3 - s**2
            return h00*left["position_km"] + h10*dt*left["velocity_km_s"] + h01*right["position_km"] + h11*dt*right["velocity_km_s"]
    raise ValueError("时刻不在 Horizons 边界网格内")


def result_at_jd(rows: list[dict], jd: float) -> dict:
    instant = datetime.fromtimestamp((jd - 2440587.5) * 86400, tz=timezone.utc)
    return independent_result(hermite_state(rows, jd), instant)


def independent_boundaries(rows: list[dict], count: int = 4) -> list[dict]:
    found = []
    for left, right in zip(rows, rows[1:]):
        before = result_at_jd(rows, left["jd_utc"])
        after = result_at_jd(rows, right["jd_utc"])
        if before["palace_index"] == after["palace_index"]:
            continue
        low, high = left["jd_utc"], right["jd_utc"]
        for _ in range(50):
            middle = (low + high) / 2
            if result_at_jd(rows, middle)["palace_index"] == before["palace_index"]:
                low = middle
            else:
                high = middle
            if (high - low) * 86400 < 0.01:
                break
        found.append({"jd_utc": high, "utc": iso_from_jd(high), "from_palace": before["palace"], "to_palace": after["palace"]})
        if len(found) == count:
            return found
    raise RuntimeError(f"边界网格只找到 {len(found)} 次交宫")


def percentile(values: list[float], percentile_value: float) -> float:
    return float(np.percentile(np.array(values), percentile_value))


def validate() -> dict:
    payload = json.loads(FIXTURE.read_text(encoding="utf-8"))
    sample_rows = parse_vectors(payload["sample_query"]["result"])
    times = payload["sample_times_utc"]
    if len(sample_rows) != len(times):
        raise RuntimeError("Horizons 样本数量与时间列表不一致")
    ae = node_reference(times)["samples"]
    comparisons, angular_errors, distance_errors = [], [], []
    for time, jpl, reference in zip(times, sample_rows, ae):
        instant = datetime.fromisoformat(time.replace("Z", "+00:00"))
        ae_vector = np.array(reference["vector_au"])
        independent = independent_result(jpl["position_km"], instant)
        current = reference["moon_palace"]
        angular = angular_error_arcsec(ae_vector, jpl["position_km"])
        angular_errors.append(angular)
        distance_error = np.linalg.norm(ae_vector) * AU_KM - np.linalg.norm(jpl["position_km"])
        distance_errors.append(float(distance_error))
        comparisons.append({
            "time": time, "is_original_fixture": time in FIXED_TIMES, "palace_match": current["palace"] == independent["palace"],
            "astronomy_engine_palace": current["palace"], "independent_palace": independent["palace"],
            "moon_ra_difference_arcsec": circular_difference_arcsec(current["moon_ra_deg"], independent["moon_ra_deg"]),
            "xu_ra_difference_arcsec": circular_difference_arcsec(current["xu_ra_deg"], independent["xu_ra_deg"]),
            "zi_center_difference_arcsec": circular_difference_arcsec(current["zi_center_ra_deg"], independent["zi_center_ra_deg"]),
            "delta_ra_difference_arcsec": circular_difference_arcsec(current["delta_ra_deg"], independent["delta_ra_deg"]),
            "boundary_distance_difference_arcsec": abs(current["nearest_boundary_distance_deg"] - independent["nearest_boundary_distance_deg"]) * 3600,
            "moon_direction_error_arcsec": angular, "moon_distance_error_km": distance_error,
        })

    grid = parse_vectors(payload["boundary_query"]["result"])
    independent_edges = independent_boundaries(grid, 4)
    starts = [iso_from_jd(edge["jd_utc"] - 0.5) for edge in independent_edges]
    ae_edges = node_reference([], starts)["boundaries"]
    boundary_comparisons = []
    for independent, current in zip(independent_edges, ae_edges):
        difference = (datetime.fromisoformat(current["boundary_utc"].replace("Z", "+00:00")) - datetime.fromisoformat(independent["utc"].replace("Z", "+00:00"))).total_seconds()
        boundary_comparisons.append({**independent, "astronomy_engine_utc": current["boundary_utc"], "difference_seconds": difference})

    risk_checks = []
    for edge in independent_edges:
        for threshold, offset_seconds in [("<10′", 600), ("<1′", 60), ("<0.1′", 10)]:
            for side in (-1, 1):
                jd = edge["jd_utc"] + side * offset_seconds / 86400
                time = iso_from_jd(jd)
                independent = result_at_jd(grid, jd)
                current = node_reference([time])["samples"][0]["moon_palace"]
                risk_checks.append({"boundary": f'{edge["from_palace"]}→{edge["to_palace"]}', "threshold": threshold,
                                    "side": "before" if side < 0 else "after", "time": time,
                                    "independent_distance_arcmin": independent["nearest_boundary_distance_arcmin"],
                                    "astronomy_engine_palace": current["palace"], "independent_palace": independent["palace"],
                                    "palace_match": current["palace"] == independent["palace"]})

    return {
        "software": {"astronomy_engine": "2.1.19", "horizons_ephemeris": "DE441", "pyerfa": erfa.__version__, "numpy": np.__version__},
        "sample_count": len(times),
        "moon_direction_error_arcsec": {"max": max(angular_errors), "mean": statistics.fmean(angular_errors),
                                             "median": statistics.median(angular_errors), "p95": percentile(angular_errors, 95)},
        "moon_distance_error_km": {"max_absolute": max(map(abs, distance_errors)), "mean_signed": statistics.fmean(distance_errors)},
        "max_ra_differences_arcsec": {
            key: max(item[key] for item in comparisons) for key in
            ["moon_ra_difference_arcsec", "xu_ra_difference_arcsec", "zi_center_difference_arcsec"]
        },
        "all_palaces_match": all(item["palace_match"] for item in comparisons),
        "comparisons": comparisons,
        "boundary_comparisons": boundary_comparisons,
        "max_boundary_time_difference_seconds": max(abs(item["difference_seconds"]) for item in boundary_comparisons),
        "boundary_risk_all_match": all(item["palace_match"] for item in risk_checks),
        "boundary_risk_checks": risk_checks,
    }


if __name__ == "__main__":
    if hasattr(sys.stdout, "reconfigure"):
        sys.stdout.reconfigure(encoding="utf-8")
    parser = argparse.ArgumentParser()
    parser.add_argument("--fetch", action="store_true", help="从 JPL Horizons 刷新原始研究夹具")
    args = parser.parse_args()
    if args.fetch:
        fetch_fixture()
    print(json.dumps(validate(), ensure_ascii=False, indent=2))
