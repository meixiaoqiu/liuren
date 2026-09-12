"""JPL Horizons + ERFA 独立交叉验证的离线验收测试。"""

import sys
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / "tools"))

import moon_palace_crosscheck as crosscheck  # noqa: E402


class MoonPalaceCrosscheckTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.result = crosscheck.validate()

    def test_horizons_query_is_geocentric_icrf_geometric_de441(self):
        payload = __import__("json").loads(crosscheck.FIXTURE.read_text(encoding="utf-8"))
        params = payload["sample_query"]["params"]
        self.assertEqual(params["COMMAND"], "'301'")
        self.assertEqual(params["CENTER"], "'500@399'")
        self.assertEqual(params["EPHEM_TYPE"], "'VECTORS'")
        self.assertEqual(params["REF_SYSTEM"], "'ICRF'")
        self.assertEqual(params["REF_PLANE"], "'FRAME'")
        self.assertEqual(params["VEC_CORR"], "'NONE'")
        self.assertIn("{source: DE441}", payload["sample_query"]["result"])
        self.assertIn("Center-site name: BODY CENTER", payload["sample_query"]["result"])
        self.assertIn("Output type     : GEOMETRIC cartesian states", payload["sample_query"]["result"])

    def test_twenty_samples_have_small_direction_error_and_same_palace(self):
        self.assertEqual(self.result["sample_count"], 20)
        self.assertLess(self.result["moon_direction_error_arcsec"]["max"], 10)
        self.assertTrue(self.result["all_palaces_match"])
        self.assertEqual(sum(item["is_original_fixture"] for item in self.result["comparisons"]), 5)

    def test_four_boundaries_meet_sixty_second_target(self):
        self.assertGreaterEqual(len(self.result["boundary_comparisons"]), 4)
        self.assertLess(self.result["max_boundary_time_difference_seconds"], 60)

    def test_boundary_risk_samples_match(self):
        self.assertTrue(self.result["boundary_risk_all_match"])
        thresholds = {item["threshold"] for item in self.result["boundary_risk_checks"]}
        self.assertEqual(thresholds, {"<10′", "<1′", "<0.1′"})


if __name__ == "__main__":
    unittest.main()
