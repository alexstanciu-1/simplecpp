#!/usr/bin/env python3
"""Audit unchanged accounting through production preparation and C++ emitters."""
import argparse
from collections import Counter
import hashlib
import json
from pathlib import Path
import sys

HERE = Path(__file__).resolve().parent
ROOT = HERE.parents[3]
sys.path.insert(0, str(ROOT / 'compiler/my-try/tools'))
from test_runner import CommandRunner, Task, run_tasks

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', type=Path, required=True)
args = parser.parse_args()
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=True)
source = ROOT / 'examples/accounting_workload'
summary = {'source': str(source.relative_to(ROOT)), 'sources': {
    str(p.relative_to(source)): hashlib.sha256(p.read_bytes()).hexdigest()
    for p in sorted(source.rglob('*.phs'))}, 'modes': {}}
with CommandRunner(out / 'audit-logs', ROOT, jobs=12) as runner:
    def compile_mode(mode):
        result = json.loads(runner.run(mode, ['php', HERE / 'probe.php', source, mode, out / mode]))
        summary['modes'][mode] = result
        if result['ok']:
            build = json.loads(runner.run(mode + '-build', ['php', ROOT / 'compiler/my-try/tools/build_cpp.php',
                out / mode, out / (mode + '-cache'), '--no-pch', '-O0'],
                timeout=600, cli_reason='Native builder process'))
            result['build'] = build
            result['output'] = runner.run(mode + '-run', [build['executable']]).decode()
            assert 'CHECKS passed=998 failed=0' in result['output']
            assert 'ACCOUNTING_WORKLOAD_PASS' in result['output']
    def fragments():
        rows = json.loads(runner.run('fragments', ['php', HERE / 'fragments.php', source]))
        (out / 'fragments.json').write_text(json.dumps(rows, indent=2) + '\n')
        summary['fragments'] = {
            'total': len(rows), 'emitted': sum(r['ok'] for r in rows),
            'errors': dict(Counter(r['error'] for r in rows if not r['ok'])),
            'template_bearing_emitted': sum(bool(r.get('template_lines')) for r in rows),
            'failures': [{'owner': r['owner'], 'kind': r['kind'], 'error': r['error'],
                'first_frame': r['trace'].splitlines()[0]} for r in rows if not r['ok']]}
    run_tasks([Task(m, lambda m=m: compile_mode(m)) for m in ['direct', 'concrete']]
              + [Task('fragment-audit', fragments)])
(out / 'summary.json').write_text(json.dumps(summary, indent=2) + '\n')
print(json.dumps(summary['fragments']['errors'], indent=2))
