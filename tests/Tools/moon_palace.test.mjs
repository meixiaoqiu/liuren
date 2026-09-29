import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import { calculateMoonPalace, findNextPalaceBoundary, palaceFromRa, parseZonedTime } from '../../tools/moon_palace_core.mjs';

const fixture = JSON.parse(await readFile(new URL('../Fixtures/moon_palace_2026.json', import.meta.url)));

function closeTo(actual, expected, tolerance, label) {
    assert.ok(Math.abs(actual - expected) <= tolerance, `${label}: ${actual} 与 ${expected} 相差超过 ${tolerance}`);
}

test('子宫中心及 ±15° 边界采用明确半开区间', () => {
    assert.equal(palaceFromRa(100, 100).palace, '子');
    assert.equal(palaceFromRa(85, 100).palace, '子');
    assert.equal(palaceFromRa(115 - 1e-9, 100).palace, '子');
    assert.equal(palaceFromRa(115, 100).palace, '亥');
    assert.equal(palaceFromRa(85 - 1e-9, 100).palace, '丑');
    assert.equal(palaceFromRa(115, 100).nearest_boundary_distance_deg, 0);
});

test('赤经递增宫序为子、亥、戌，项目索引反向变化', () => {
    const expected = [
        [0, '子'], [11, '亥'], [10, '戌'], [9, '酉'], [8, '申'], [7, '未'],
        [6, '午'], [5, '巳'], [4, '辰'], [3, '卯'], [2, '寅'], [1, '丑'],
    ];
    for (const [step, [index, palace]] of expected.entries()) {
        const actual = palaceFromRa(10 + step * 30, 10);
        assert.equal(actual.palace_index, index);
        assert.equal(actual.palace, palace);
    }
});

test('0°/360° 回绕保持子宫判断', () => {
    const actual = palaceFromRa(1, 359);
    assert.equal(actual.palace, '子');
    assert.equal(actual.delta_ra_deg, 2);
    assert.equal(actual.nearest_boundary_distance_deg, 13);
});

test('拒绝没有明确时区的输入', () => {
    assert.throws(() => parseZonedTime('2026-09-13T01:10:00'), /必须是带时区/);
});

test('2026 研究 fixture 的完整数值输出保持稳定', () => {
    for (const expected of fixture.cases) {
        const actual = calculateMoonPalace(expected.input_time);
        assert.equal(actual.utc_time, expected.utc_time);
        assert.equal(actual.coordinate_frame, 'EQD');
        assert.equal(actual.provider, 'astronomy-engine');
        assert.equal(actual.proper_motion_applied, false);
        assert.equal(actual.palace_index, expected.palace_index);
        assert.equal(actual.palace, expected.palace);
        for (const field of ['moon_ra_deg', 'xu_ra_deg', 'zi_center_ra_deg', 'delta_ra_deg', 'nearest_boundary_distance_deg']) {
            closeTo(actual[field], expected[field], fixture.angular_tolerance_deg, field);
        }
        closeTo(actual.nearest_boundary_distance_arcmin, expected.nearest_boundary_distance_arcmin, fixture.angular_tolerance_deg * 60, 'nearest_boundary_distance_arcmin');
    }
});

test('边界搜索能夹逼宫位改变且结果位于声明容差内', () => {
    const boundary = findNextPalaceBoundary('2026-09-13T00:00:00Z', { tolerance_ms: 100 });
    assert.equal(boundary.from_palace, '巳');
    assert.equal(boundary.to_palace, '辰');
    assert.ok(Date.parse(boundary.boundary_utc) > Date.parse(boundary.search_start_utc));
    assert.equal(boundary.time_tolerance_ms, 100);
    const before = calculateMoonPalace(new Date(Date.parse(boundary.boundary_utc) - 100));
    const after = calculateMoonPalace(new Date(boundary.boundary_utc));
    assert.equal(before.palace, boundary.from_palace);
    assert.equal(after.palace, boundary.to_palace);
    assert.ok(after.nearest_boundary_distance_deg < 0.00002);
});
