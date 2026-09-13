/** 静态月宿交宫表的 JS 读取与验证工具；仅用于生成和回归，不参与 PHP 生产运行。 */
import { readFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { calculateMoonPalace } from './moon_palace_core.mjs';

export const TABLE_DIRECTORY = path.join(path.dirname(fileURLToPath(import.meta.url)), '..', 'resources', 'astronomy', 'moon-palace');

function calculateIsolated(timestamp) {
    calculateMoonPalace(new Date(timestamp - 1000));
    return calculateMoonPalace(new Date(timestamp));
}

export async function loadMoonPalaceTable(directory = TABLE_DIRECTORY) {
    const manifest = JSON.parse(await readFile(path.join(directory, 'manifest.json'), 'utf8'));
    const shards = new Map();
    for (const filename of manifest.shards) {
        shards.set(filename, JSON.parse(await readFile(path.join(directory, filename), 'utf8')));
    }
    return { manifest, shards };
}

export function tablePalaceAt(table, time) {
    const timestamp = time instanceof Date ? time.getTime() : Date.parse(time);
    const start = Date.parse(table.manifest.start_utc);
    const end = Date.parse(table.manifest.end_utc_exclusive);
    if (!Number.isSafeInteger(timestamp) || timestamp < start || timestamp >= end) {
        throw new RangeError(`时间超出月宿表范围：[${table.manifest.start_utc}, ${table.manifest.end_utc_exclusive})`);
    }
    const year = new Date(timestamp).getUTCFullYear();
    const filename = `${Math.floor(year / 100) * 100}.json`;
    const shard = table.shards.get(filename);
    if (!shard) throw new Error(`缺少月宿表分片：${filename}`);

    let palace = shard.start_palace_index;
    let low = 0;
    let high = shard.boundaries.length - 1;
    while (low <= high) {
        const middle = Math.floor((low + high) / 2);
        if (shard.boundaries[middle][0] <= timestamp) {
            palace = shard.boundaries[middle][1];
            low = middle + 1;
        } else {
            high = middle - 1;
        }
    }
    return palace;
}

function mulberry32(seed) {
    return () => {
        seed |= 0;
        seed = seed + 0x6D2B79F5 | 0;
        let value = Math.imul(seed ^ seed >>> 15, 1 | seed);
        value = value + Math.imul(value ^ value >>> 7, 61 | value) ^ value;
        return ((value ^ value >>> 14) >>> 0) / 4294967296;
    };
}

export async function verifyMoonPalaceTable({ randomCount = 5000, checkAllBoundaries = true, directory = TABLE_DIRECTORY } = {}) {
    const table = await loadMoonPalaceTable(directory);
    const { manifest, shards } = table;
    const expectedOrder = [0, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1];
    let previousTimestamp = null;
    let previousPalace = null;
    let boundaryCount = 0;
    const intervals = [];

    for (const filename of manifest.shards) {
        const shard = shards.get(filename);
        if (previousPalace !== null && shard.start_palace_index !== previousPalace) {
            throw new Error(`${filename} 的世纪起始状态与前一分片末状态不连续`);
        }
        let state = shard.start_palace_index;
        for (const [timestamp, palace] of shard.boundaries) {
            if (!Number.isSafeInteger(timestamp) || (previousTimestamp !== null && timestamp <= previousTimestamp)) {
                throw new Error(`交宫时间不严格递增：${timestamp}`);
            }
            if (palace !== (state + 11) % 12) throw new Error(`交宫次序错误：${state} → ${palace}`);
            if (previousTimestamp !== null) intervals.push(timestamp - previousTimestamp);
            previousTimestamp = timestamp;
            previousPalace = palace;
            state = palace;
            boundaryCount++;

            if (checkAllBoundaries) {
                for (const offset of [-1000, -1, 0, 1, 1000]) {
                    const sample = timestamp + offset;
                    if (sample < Date.parse(manifest.start_utc) || sample >= Date.parse(manifest.end_utc_exclusive)) continue;
                    const direct = calculateIsolated(sample).palace_index;
                    const stored = tablePalaceAt(table, new Date(sample));
                    if (direct !== stored) throw new Error(`边界 ${timestamp} 偏移 ${offset}ms 不一致：JS=${direct}, table=${stored}`);
                }
            }
        }
        if (state !== previousPalace) previousPalace = state;
    }

    const rng = mulberry32(4302);
    const start = Date.parse(manifest.start_utc);
    const duration = Date.parse(manifest.end_utc_exclusive) - start;
    for (let index = 0; index < randomCount; index++) {
        const timestamp = start + Math.floor(rng() * duration);
        const direct = calculateIsolated(timestamp).palace_index;
        const stored = tablePalaceAt(table, new Date(timestamp));
        if (direct !== stored) throw new Error(`随机样本 ${new Date(timestamp).toISOString()} 不一致：JS=${direct}, table=${stored}`);
    }

    const sorted = [...intervals].sort((a, b) => a - b);
    return {
        schema_version: manifest.schema_version,
        boundary_count: boundaryCount,
        random_seed: 4302,
        random_samples_checked: randomCount,
        boundary_samples_checked: checkAllBoundaries ? boundaryCount * 5 : 0,
        palace_order_valid: expectedOrder.every((value, index) => manifest.palace_index_order_increasing_ra[index] === value),
        shards_continuous: true,
        shortest_interval_ms: sorted[0],
        longest_interval_ms: sorted.at(-1),
        mean_interval_ms: intervals.reduce((sum, value) => sum + value, 0) / intervals.length,
    };
}
