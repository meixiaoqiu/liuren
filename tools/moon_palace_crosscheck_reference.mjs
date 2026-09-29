#!/usr/bin/env node
/** 仅向独立 Python 验证器暴露 Astronomy Engine 一侧的原始向量和既有研究输出。 */
import { GeoMoon } from 'astronomy-engine';
import { calculateMoonPalace, findNextPalaceBoundary } from './moon_palace_core.mjs';

let input = '';
for await (const chunk of process.stdin) input += chunk;
const request = JSON.parse(input);
const samples = request.times.map((time) => {
    const vector = GeoMoon(new Date(time));
    return { time, vector_au: [vector.x, vector.y, vector.z], moon_palace: calculateMoonPalace(time) };
});
const boundaries = (request.boundary_starts ?? []).map((time) => findNextPalaceBoundary(time, { tolerance_ms: 10 }));
process.stdout.write(JSON.stringify({ samples, boundaries }));
