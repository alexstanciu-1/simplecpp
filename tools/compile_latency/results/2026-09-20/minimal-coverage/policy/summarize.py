#!/usr/bin/env python3
"""Summarize raw native timings without mixing setup or failure attempts into edits."""
import argparse
import json
import math
import statistics
from pathlib import Path


def stats(rows):
    times = sorted(r['native_wall_seconds'] for r in rows)
    return {'samples': len(times), 'median_seconds': statistics.median(times),
            'p95_seconds': times[math.ceil(.95 * len(times)) - 1], 'max_seconds': times[-1],
            'passed_native_budget': sum(r['exit_code'] == 0 and r['native_wall_seconds'] <= 8.5 for r in rows),
            'failed_builds': sum(r['exit_code'] != 0 for r in rows)}


parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('results', type=Path)
args = parser.parse_args()
rows = [json.loads(line) for line in (args.results / 'measurements.jsonl').read_text().splitlines()]
summary = {}
for config in sorted({r['config'] for r in rows}):
    selected = [r for r in rows if r['config'] == config]
    summary[config] = stats(selected)
print(json.dumps(summary, indent=2))
