#!/usr/bin/env python3
"""Isolated compile experiment; original sources and runtime remain read-only."""
import sys
sys.dont_write_bytecode = True
import json
from pathlib import Path
import shutil
import time

HERE = Path(__file__).resolve().parent
ROOT = HERE.parents[3]
sys.path.insert(0, str(ROOT / 'compiler/my-try/tools'))
from test_runner import CommandRunner, Task, run_tasks

ORIGINAL = Path('/tmp/my-try-probes-20261003-1/programs')
PROGRAMS = HERE / 'programs'
PROGRAMS.mkdir(exist_ok=True)
fixtures = [x for x in json.loads((ORIGINAL / 'executions.json').read_text())
            if Path(x['path']).stem.startswith('nullable_')]
for item in fixtures:
    source = Path(item['path'])
    shutil.copy2(source, PROGRAMS / source.name)
    item['path'] = str(PROGRAMS / source.name)
(HERE / 'fixtures.json').write_text(json.dumps(fixtures, indent=2))
FLAGS = ['clang++', '-std=c++20', '-Werror=return-type', '-I', str(ROOT / 'runtime/include')]
source = PROGRAMS / 'nullable_elvis_present.cpp'
headers = '\n'.join(line for line in source.read_text().splitlines() if line.startswith('#include')) + '\n'
(HERE / 'runtime.hpp').write_text(headers)
summary = {}
with CommandRunner(HERE / 'logs', ROOT, jobs=12, php_executor='cli') as runner:
    def phase(name, items, extra=(), jobs=12):
        folder = HERE / name
        folder.mkdir(exist_ok=True)
        def execute(item):
            src = Path(item['path'])
            binary = folder / (src.stem + '.program')
            runner.run(name + '-' + src.stem + '-compile', [*FLAGS, *extra, src, '-o', binary])
            runner.run(name + '-' + src.stem + '-execute', [binary], expected=item['exit_code'])
        start = time.monotonic()
        outcomes = run_tasks([Task(Path(i['path']).stem, lambda i=i: execute(i)) for i in items], jobs=jobs)
        summary[name] = dict(wall_seconds=time.monotonic()-start, tasks=[dict(name=o.name, seconds=o.seconds) for o in outcomes])
        (HERE / 'summary.json').write_text(json.dumps(summary, indent=2))
    exact = [i for i in fixtures if Path(i['path']).stem == source.stem]
    phase('isolated', exact, jobs=1)
    runner.run('profile', [*FLAGS, '-ftime-trace', '-c', source, '-o', HERE / 'profile.o'])
    runner.run('preprocess', [*FLAGS, '-E', source, '-o', HERE / 'preprocessed.ii'])
    runner.run('pch-build', [*FLAGS, '-x', 'c++-header', HERE / 'runtime.hpp', '-o', HERE / 'runtime.pch'])
    pch = ['-include-pch', str(HERE / 'runtime.pch')]
    phase('pch-isolated', exact, pch, jobs=1)
    phase('baseline-12', fixtures)
    phase('pch-12', fixtures, pch)
    phase('baseline-repeat-12', [*exact] + [i for i in fixtures if i not in exact][:11])
    phase('pch-repeat-12', [*exact] + [i for i in fixtures if i not in exact][:11], pch)
print(json.dumps({k: v['wall_seconds'] for k,v in summary.items()}, indent=2))
