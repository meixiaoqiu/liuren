/**
 * 第43课“二烦课”的 moonPalace 天文研究核心。
 * 独立研究代码，不是生产接口。月球取地心位置；月球与 HIP 106278 均由
 * J2000 赤道坐标旋转到同一 EQD 坐标系后比较。
 */
import {
    EquatorFromVector, GeoMoon, MakeTime, RotateVector, Rotation_EQJ_EQD, Vector,
} from 'astronomy-engine';

export const PALACES = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
export const XU_STAR = Object.freeze({
    name: 'β Aquarii / Sadalsuud / 虚宿一',
    hip: 106278,
    j2000_ra_hours: 21 + 31 / 60 + 33.53207 / 3600,
    j2000_ra_deg: 322.8897169583333,
    j2000_dec_deg: -(5 + 34 / 60 + 16.22938 / 3600),
    source: 'CDS SIMBAD (HIP 106278), ICRS coordinates at J2000',
    source_url: 'https://simbad.cds.unistra.fr/simbad/sim-id?Ident=HIP+106278',
    proper_motion_applied: false,
});
export const ANCIENT_CIRCLE_DEG = 365.2578;
export const XU_SIX_ANCIENT_DEG_MODERN = 6 * 360 / ANCIENT_CIRCLE_DEG;

export function normalizeDegrees(value) {
    return ((value % 360) + 360) % 360;
}

export function signedDegrees(value) {
    const normalized = normalizeDegrees(value + 180) - 180;
    return Object.is(normalized, -0) ? 0 : normalized;
}

/** 宫区间为 [中心-15°, 中心+15°)，赤经增加时项目地支索引反向递增。 */
export function palaceFromRa(moonRaDeg, ziCenterRaDeg) {
    const deltaRaDeg = signedDegrees(moonRaDeg - ziCenterRaDeg);
    const increasingRaSector = Math.floor((deltaRaDeg + 15) / 30);
    const palaceIndex = ((-increasingRaSector % 12) + 12) % 12;
    const palaceCenterDelta = signedDegrees(deltaRaDeg - increasingRaSector * 30);
    const nearestBoundaryDistanceDeg = Math.max(0, 15 - Math.abs(palaceCenterDelta));

    return {
        delta_ra_deg: normalizeDegrees(moonRaDeg - ziCenterRaDeg),
        signed_delta_ra_deg: deltaRaDeg,
        palace_index: palaceIndex,
        palace: PALACES[palaceIndex],
        nearest_boundary_distance_deg: nearestBoundaryDistanceDeg,
        nearest_boundary_distance_arcmin: nearestBoundaryDistanceDeg * 60,
    };
}

function fixedEqjVector(raDeg, decDeg, time) {
    const ra = raDeg * Math.PI / 180;
    const dec = decDeg * Math.PI / 180;
    const cosDec = Math.cos(dec);
    return new Vector(cosDec * Math.cos(ra), cosDec * Math.sin(ra), Math.sin(dec), MakeTime(time));
}

export function parseZonedTime(input) {
    if (typeof input !== 'string' || ! /(Z|[+-]\d{2}:\d{2})$/i.test(input)) {
        throw new Error('时间必须是带时区的 ISO 8601 字符串，例如 2026-09-13T01:10:00+08:00');
    }
    const date = new Date(input);
    if (Number.isNaN(date.getTime())) {
        throw new Error(`无法解析时间：${input}`);
    }
    return date;
}

export function calculateMoonPalace(inputTime) {
    const date = inputTime instanceof Date ? new Date(inputTime) : parseZonedTime(inputTime);
    const rotation = Rotation_EQJ_EQD(date);
    const moonEqd = EquatorFromVector(RotateVector(rotation, GeoMoon(date)));
    const xuEqj = fixedEqjVector(XU_STAR.j2000_ra_deg, XU_STAR.j2000_dec_deg, date);
    const xuEqd = EquatorFromVector(RotateVector(rotation, xuEqj));
    const moonRaDeg = normalizeDegrees(moonEqd.ra * 15);
    const xuRaDeg = normalizeDegrees(xuEqd.ra * 15);
    const ziCenterRaDeg = normalizeDegrees(xuRaDeg + XU_SIX_ANCIENT_DEG_MODERN);
    const palace = palaceFromRa(moonRaDeg, ziCenterRaDeg);

    return {
        input_time: inputTime instanceof Date ? inputTime.toISOString() : inputTime,
        utc_time: date.toISOString(),
        coordinate_frame: 'EQD',
        moon_ra_deg: moonRaDeg,
        xu_ra_deg: xuRaDeg,
        zi_center_ra_deg: ziCenterRaDeg,
        delta_ra_deg: palace.delta_ra_deg,
        palace_index: palace.palace_index,
        palace: palace.palace,
        nearest_boundary_distance_deg: palace.nearest_boundary_distance_deg,
        nearest_boundary_distance_arcmin: palace.nearest_boundary_distance_arcmin,
        provider: 'astronomy-engine',
        proper_motion_applied: false,
    };
}

/** 搜索给定时刻之后的下一次交宫：先逐步夹逼，再二分到指定时间容差。 */
export function findNextPalaceBoundary(inputTime, options = {}) {
    const start = inputTime instanceof Date ? new Date(inputTime) : parseZonedTime(inputTime);
    const stepMs = options.step_ms ?? 60 * 60 * 1000;
    const toleranceMs = options.tolerance_ms ?? 100;
    const maxSearchMs = options.max_search_ms ?? 4 * 24 * 60 * 60 * 1000;
    const initial = calculateMoonPalace(start);
    let low = start.getTime();
    let high = low + stepMs;

    while (high - start.getTime() <= maxSearchMs) {
        if (calculateMoonPalace(new Date(high)).palace_index !== initial.palace_index) {
            while (high - low > toleranceMs) {
                const middle = Math.floor((low + high) / 2);
                if (calculateMoonPalace(new Date(middle)).palace_index === initial.palace_index) low = middle;
                else high = middle;
            }
            const after = calculateMoonPalace(new Date(high));
            return {
                search_start_utc: start.toISOString(), boundary_utc: new Date(high).toISOString(),
                from_palace_index: initial.palace_index, from_palace: initial.palace,
                to_palace_index: after.palace_index, to_palace: after.palace,
                time_tolerance_ms: toleranceMs,
            };
        }
        low = high;
        high += stepMs;
    }
    throw new Error('在搜索窗口内未找到下一次月球交宫');
}
