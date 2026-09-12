#!/usr/bin/env node
import { findNextPalaceBoundary } from './moon_palace_core.mjs';

try {
    if (process.argv.length !== 3) throw new Error('用法：node tools/moon_palace_boundary.mjs "2026-09-13T01:10:00+08:00"');
    console.log(JSON.stringify(findNextPalaceBoundary(process.argv[2]), null, 2));
} catch (error) {
    console.error(error.message);
    process.exitCode = 1;
}
