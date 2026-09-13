#!/usr/bin/env node
/** 基于冻结的 Astronomy Engine 核心离线生成 1600–2499 月宿十二宫交宫事件。 */
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { calculateMoonPalace, XU_STAR, ANCIENT_CIRCLE_DEG } from './moon_palace_core.mjs';
import { TABLE_DIRECTORY, verifyMoonPalaceTable } from './moon_palace_table.mjs';

const START_MS = Date.UTC(1600, 0, 1);
const END_MS = Date.UTC(2500, 0, 1);
const STEP_MS = 6 * 60 * 60 * 1000;
const PRECISION_MS = 1;
const SHARD_YEARS = 100;

function mulberry32(seed) {
    return () => {
        seed |= 0;
        seed = seed + 0x6D2B79F5 | 0;
        let value = Math.imul(seed ^ seed >>> 15, 1 | seed);
        value = value + Math.imul(value ^ value >>> 7, 61 | value) ^ value;
        return ((value ^ value >>> 14) >>> 0) / 4294967296;
    };
}

function assertSafeStep(previous, current) {
    const raStep = ((current.moon_ra_deg - previous.moon_ra_deg + 540) % 360) - 180;
    if (!(raStep > 0 && raStep < 30)) {
        throw new Error(`6小时月球赤经增量异常：${previous.utc_time} → ${current.utc_time}, ${raStep}°`);
    }
    const expectedNext = (previous.palace_index + 11) % 12;
    if (current.palace_index !== previous.palace_index && current.palace_index !== expectedNext) {
        throw new Error(`相邻采样跳宫或反向：${previous.palace} → ${current.palace}`);
    }
    return raStep;
}

/** 避免 Astronomy Engine 的亚100ms章动缓存使精细边界结果依赖前一次调用时刻。 */
function calculateIsolated(timestamp) {
    calculateMoonPalace(new Date(timestamp - 1000));
    return calculateMoonPalace(new Date(timestamp));
}

function refineBoundary(lowMs, highMs, beforePalace) {
    while (highMs - lowMs > PRECISION_MS) {
        const middle = Math.floor((lowMs + highMs) / 2);
        if (calculateIsolated(middle).palace_index === beforePalace) lowMs = middle;
        else highMs = middle;
    }
    return highMs;
}

async function generate() {
    const started = Date.now();
    const shards = new Map();
    for (let year = 1600; year < 2500; year += SHARD_YEARS) {
        const startMs = Date.UTC(year, 0, 1);
        shards.set(year, {
            start_ms: startMs,
            end_ms_exclusive: Date.UTC(year + SHARD_YEARS, 0, 1),
            start_palace_index: calculateMoonPalace(new Date(startMs)).palace_index,
            boundaries: [],
        });
    }

    let previousMs = START_MS;
    let previous = calculateMoonPalace(new Date(previousMs));
    let maximumCoarseRaStep = 0;
    let boundaryCount = 0;
    for (let currentMs = START_MS + STEP_MS; currentMs <= END_MS; currentMs += STEP_MS) {
        const cappedMs = Math.min(currentMs, END_MS);
        const current = calculateMoonPalace(new Date(cappedMs));
        maximumCoarseRaStep = Math.max(maximumCoarseRaStep, assertSafeStep(previous, current));
        if (current.palace_index !== previous.palace_index) {
            const boundaryMs = refineBoundary(previousMs, cappedMs, previous.palace_index);
            if (boundaryMs >= END_MS) break;
            const after = calculateIsolated(boundaryMs);
            if (after.palace_index !== (previous.palace_index + 11) % 12) {
                throw new Error(`二分后的交宫次序异常：${previous.palace} → ${after.palace}`);
            }
            const year = new Date(boundaryMs).getUTCFullYear();
            const century = Math.floor(year / 100) * 100;
            shards.get(century).boundaries.push([boundaryMs, after.palace_index]);
            boundaryCount++;
        }
        previousMs = cappedMs;
        previous = current;
        if (cappedMs === END_MS) break;
    }

    await mkdir(TABLE_DIRECTORY, { recursive: true });
    const filenames = [];
    for (const [year, shard] of shards) {
        const filename = `${year}.json`;
        filenames.push(filename);
        await writeFile(path.join(TABLE_DIRECTORY, filename), JSON.stringify(shard) + '\n', 'utf8');
    }
    const manifest = {
        schema_version: 1,
        start_utc: '1600-01-01T00:00:00Z',
        end_utc_exclusive: '2500-01-01T00:00:00Z',
        generator: 'astronomy-engine',
        astronomy_engine_version: '2.1.19',
        coordinate_frame: 'EQD',
        proper_motion_applied: false,
        ancient_circle_degrees: ANCIENT_CIRCLE_DEG,
        boundary_precision_ms: PRECISION_MS,
        coarse_scan_step_hours: 6,
        maximum_observed_coarse_moon_ra_step_deg: maximumCoarseRaStep,
        palace_order_increasing_ra: ['子', '亥', '戌', '酉', '申', '未', '午', '巳', '辰', '卯', '寅', '丑'],
        palace_index_order_increasing_ra: [0, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1],
        xu_star: XU_STAR,
        generator_file: 'tools/generate_moon_palace_boundaries.mjs',
        generated_on: '2026-09-13',
        research_document: 'docs/课经/43-二烦课-moonPalace天文基础研究.md',
        boundary_count: boundaryCount,
        shards: filenames,
    };
    await writeFile(path.join(TABLE_DIRECTORY, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n', 'utf8');
    const sampleTimestamps = new Set([START_MS, END_MS - 1, Date.parse('2026-09-13T06:33:09.000Z')]);
    for (let year = 1600; year < 2500; year += 100) {
        sampleTimestamps.add(Date.UTC(year, 0, 1));
        sampleTimestamps.add(Date.UTC(year + 99, 11, 31, 23, 59, 59, 999));
    }
    const rng = mulberry32(4303);
    while (sampleTimestamps.size < 520) sampleTimestamps.add(START_MS + Math.floor(rng() * (END_MS - START_MS)));
    const samples = [...sampleTimestamps].sort((a, b) => a - b).map((timestamp) => [
        timestamp,
        calculateIsolated(timestamp).palace_index,
    ]);
    await writeFile(
        path.join(TABLE_DIRECTORY, '..', '..', '..', 'tests', 'Fixtures', 'moon_palace_table_samples.json'),
        JSON.stringify({ schema_version: 1, generator: 'astronomy-engine 2.1.19', random_seed: 4303, samples }, null, 2) + '\n',
        'utf8',
    );
    return { boundary_count: boundaryCount, generation_duration_ms: Date.now() - started };
}

const verifyOnly = process.argv.includes('--verify');
if (verifyOnly) {
    console.log(JSON.stringify(await verifyMoonPalaceTable(), null, 2));
} else {
    console.log(JSON.stringify(await generate(), null, 2));
}
