"""Isolated diagnostic compiles using the recorded native recipes; no baseline writes."""
import hashlib
import json
import os
from pathlib import Path
import statistics
import subprocess
import sys
import time

B = Path('/home/alex-ai/accounting-shared-handles-20261016/bench')
O = Path(__file__).resolve().parent / 'results'
O.mkdir(exist_ok=False)
sys.path.insert(0, str(B.parent / 'repo/compiler/my-try/tools'))
from test_runner import Task, run_tasks

calls = json.loads((B / 'edit-results/trial-1/full/clang-calls.json').read_text())
env = dict(PATH='/usr/bin:/bin', LC_ALL='C', TZ='UTC', SOURCE_DATE_EPOCH='0')

def clean(args):
    result = []
    i = 0
    while i < len(args):
        if args[i] == '-MD':
            i += 1
        elif args[i] in ('-MF', '-MQ', '-o'):
            i += 2
        else:
            result.append(args[i])
            i += 1
    return result

def run(name, args):
    command = ['/usr/bin/clang++-18', *args]
    started = time.monotonic()
    proc = subprocess.run(['/usr/bin/time', '-f', '%e %U %S %M', '-o', str(O / (name + '.time')), *command], env=env, capture_output=True, text=True)
    elapsed = time.monotonic() - started
    (O / (name + '.stderr')).write_text(proc.stderr)
    assert proc.returncode == 0, proc.stderr
    timing = (O / (name + '.time')).read_text().split()
    return dict(name=name, command=command, elapsed=elapsed, user=float(timing[1]), system=float(timing[2]), peak_rss_kib=int(timing[3]))

pch = next(c for c in calls if c['stage'] == 'pch')
rows = [run('pch', [*clean(pch['args']), '-o', str(O / 'runtime.pch')])]
for trial in range(1, 4):
    for unit in ('workflow_checks', 'closing_checks'):
        source = 'n' + ('src/' + unit + '.phs').encode().hex() + '.cpp'
        call = next(c for c in calls if c['stage'] == 'object' and source in ' '.join(c['args']))
        args = clean(call['args'])
        args[args.index('-include-pch') + 1] = str(O / 'runtime.pch')
        name = f'{unit}-{trial}'
        args += ['-ftime-trace', '-o', str(O / (name + '.o'))]
        task = run_tasks([Task(name, lambda name=name, args=args: run(name, args))], jobs=1)
        row = task[0].value
        trace = json.loads((O / (name + '.json')).read_text())['traceEvents']
        row['stages'] = {e['name']: e['dur'] / 1e6 for e in trace if e['name'] in ('Total ExecuteCompiler', 'Total Frontend', 'Total Backend', 'Total InstantiateFunction', 'Total InstantiateClass')}
        row['top_templates'] = sorted([dict(name=e['name'], detail=e.get('args', {}).get('detail', ''), seconds=e['dur']/1e6) for e in trace if e['name'] in ('InstantiateFunction', 'InstantiateClass')], key=lambda e: -e['seconds'])[:30]
        rows.append(row)
        print(name, row['stages'], row['peak_rss_kib'], flush=True)

inventory = {}
for root in (B / 'published-1', B.parent / 'repo/runtime'):
    for path in sorted(root.rglob('*')):
        if path.is_file():
            inventory[str(path)] = hashlib.sha256(path.read_bytes()).hexdigest()
(O / 'input-hashes.json').write_text(json.dumps(inventory, indent=2) + '\n')
(O / 'summary.json').write_text(json.dumps(rows, indent=2) + '\n')
print('COMPLETE', flush=True)
