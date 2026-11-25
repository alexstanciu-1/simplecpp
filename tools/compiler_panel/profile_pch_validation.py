#!/usr/bin/env python3
"""Paired validation on/off measurements using the panel's combined Ninja/PCH path."""
import json
from pathlib import Path
import shutil
import statistics
import tempfile
import time

from native_build import write_build
from pch_cache import runtime_pch
from profile_native import HERE, invoke, stats
from server import process

work = Path(tempfile.mkdtemp(prefix='scpp-pch-validation-'))
(work/'sources').mkdir()
(work/'sources/main.phs').write_text('$ret int = 8; echo "Result: "; echo $ret; echo "\\n"; return $ret;')
request = work/'request.json'
request.write_text(json.dumps({'entry':'main.phs','files':[{'name':'main.phs','flavor':'php'}]}))
_, raw = invoke(['php', HERE/'report.php', request])
report = json.loads(raw)
assert report['ok'], report
(work/'compiler-report.json').write_bytes(raw)
rows = []
number = 0

def build(level, validate, record=True):
    global number
    number += 1
    application = work/f'run-{number}'
    application.mkdir()
    shutil.copytree(work/'generated', application/'generated')
    units = [application/'generated'/name for name in report['units']]
    start = time.perf_counter()
    with runtime_pch(work/'cache', application, level) as (directory, cache):
        command = write_build(directory, units, level, True, validate_pch=validate)
        result = process(command, directory, 60)
        cache['success'] = result['exit_code'] == 0
        assert cache['success'], result
        shutil.copy2(directory/'program', application/'program')
        (application/'build.ninja').write_text((directory/'build.ninja').read_text())
    total = (time.perf_counter() - start)*1000
    _, output = invoke([application/'program'], expected=8)
    assert output == b'Result: 8\n'
    (application/'build.log').write_text(result['stdout']+result['stderr'])
    if record:
        assert cache['status'] == 'reused', cache
        assert ('-fno-validate-pch' in result['stdout']) == (not validate)
        rows.append({'optimization':level, 'validation':validate, 'total_ms':total, 'ninja_ms':result['ms'], 'cache':cache['status']})

for level in ['O0','O1','O2']:
    build(level, True, False)
for sample in range(7):
    levels = ['O0','O1','O2']
    levels = levels[sample%3:]+levels[:sample%3]
    for level in levels:
        for validate in ([True,False] if sample%2==0 else [False,True]):
            build(level,validate)
summary = []
for level in ['O0','O1','O2']:
    for validate in [True,False]:
        selected = [r for r in rows if r['optimization']==level and r['validation']==validate]
        summary.append({'optimization':level,'validation':validate,
                        'total':stats([r['total_ms'] for r in selected]),'ninja':stats([r['ninja_ms'] for r in selected])})
notes = 'Seven samples per setting; rotated optimization order and alternating validation order; PCH prewarmed, application objects always fresh; single Ninja -j4 invocation, mold, -g0. Total includes cache locking/graph setup and executable copy; Ninja time includes dependency checking, compilation and link. Output and exit 8 verified.'
(work/'results.json').write_text(json.dumps({'notes':notes,'raw':rows,'summary':summary},indent=2))
lines = ['# Combined Ninja / PCH validation experiment','',notes,'', '| Level | Clang validation | Native total median [min–max] ms | Ninja median ms |','|---|---|---:|---:|']
for row in summary:
    t=row['total'];lines.append(f"| {row['optimization']} | {row['validation']} | {t['median']} [{t['min']}–{t['max']}] | {row['ninja']['median']} |")
(work/'report.md').write_text('\n'.join(lines)+'\n')
print(work)
print('\n'.join(lines))
