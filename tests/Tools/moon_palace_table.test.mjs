import assert from 'node:assert/strict';
import test from 'node:test';
import { loadMoonPalaceTable, tablePalaceAt, verifyMoonPalaceTable } from '../../tools/moon_palace_table.mjs';

test('1600–2499 静态表通过5000随机点和全部边界邻近点核验', async () => {
    const report = await verifyMoonPalaceTable();
    assert.equal(report.random_samples_checked, 5000);
    assert.equal(report.palace_order_valid, true);
    assert.equal(report.shards_continuous, true);
    assert.ok(report.boundary_count > 140000);
});

test('范围采用开始包含、结束排除的半开区间', async () => {
    const table = await loadMoonPalaceTable();
    assert.doesNotThrow(() => tablePalaceAt(table, '1600-01-01T00:00:00.000Z'));
    assert.doesNotThrow(() => tablePalaceAt(table, '2499-12-31T23:59:59.999Z'));
    assert.throws(() => tablePalaceAt(table, '1599-12-31T23:59:59.999Z'), RangeError);
    assert.throws(() => tablePalaceAt(table, '2500-01-01T00:00:00.000Z'), RangeError);
});

test('2026 已独立验证的四次交宫保持在100ms范围内', async () => {
    const table = await loadMoonPalaceTable();
    const boundaries = table.shards.get('2000.json').boundaries;
    const expected = [
        [Date.parse('2026-09-13T06:33:09.128Z'), 5, 4],
        [Date.parse('2026-09-15T17:26:55.524Z'), 4, 3],
        [Date.parse('2026-09-18T00:33:03.394Z'), 3, 2],
        [Date.parse('2026-09-20T06:29:51.380Z'), 2, 1],
    ];
    for (const [target, before, after] of expected) {
        const boundary = boundaries.reduce((nearest, item) => Math.abs(item[0] - target) < Math.abs(nearest[0] - target) ? item : nearest);
        assert.ok(Math.abs(boundary[0] - target) <= 100, `${new Date(target).toISOString()} 相差 ${boundary[0] - target}ms`);
        assert.equal(tablePalaceAt(table, new Date(boundary[0] - 1)), before);
        assert.equal(tablePalaceAt(table, new Date(boundary[0])), after);
    }
});
