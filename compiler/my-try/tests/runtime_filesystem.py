#!/usr/bin/env python3
"""Filesystem catalog, private-directory generated proofs and snapshot regression."""
import argparse
import os
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', type=Path, required=True)
args = parser.parse_args()
output = args.results.resolve()
output.mkdir(parents=True, exist_ok=False)
expected = {'roundtrip': b'p||', 'paths': os.fsencode(output / 'data-paths/scan/A') + b'|a/b|c|',
            'failures': b'', 'snapshot': b'payload||', 'shadow': b''}
for name in expected:
    (output / ('data-' + name)).mkdir()
for name in ('catalog', 'generics'):
    (output / name).mkdir()
paths = output / 'data-paths'
(paths / 'scan').mkdir()
for name in ('b', 'a', 'A'):
    (paths / 'scan' / name).write_bytes(b'x')
(paths / 'link').symlink_to(paths / 'scan', target_is_directory=True)
(paths / 'broken').symlink_to(paths / 'absent')
snapshot = output / 'data-snapshot'
for name, payload in (('data', b'payload'), ('empty', b'')):
    (snapshot / name).write_bytes(payload)
    os.utime(snapshot / name, (1700000000, 1700000000))
(snapshot / 'link').symlink_to(snapshot / 'data')

with CommandRunner(output / 'logs', ROOT) as runner:
    run_tasks([
        Task('filesystem', lambda: runner.run('filesystem', ['php', APP / 'tests/runtime_filesystem.php', output])),
        Task('catalog', lambda: runner.run('catalog', ['php', APP / 'tests/runtime_catalog.php', output / 'catalog'])),
        Task('generics', lambda: runner.run('generics', ['php', APP / 'tests/runtime_generics.php', output / 'generics'])),
    ])
    compiler = CppFixtureCompiler(runner, ROOT, output, pch=False, runtime=True)
    # core/runtime.cpp already bundles filesystem.cpp and snapshot.cpp.
    # Linking their standalone objects as well would duplicate definitions.

    def execute(name, wanted):
        executable = output / name
        compiler.compile(name + '-compile', output / (name + '.cpp'), executable)
        actual = runner.run(name + '-execute', [executable])
        if actual != wanted:
            raise AssertionError(f'{name}: {actual!r} != {wanted!r}')

    def snapshot_regression():
        executable = output / 'runtime-snapshot'
        compiler.compile('runtime-snapshot-compile', ROOT / 'tests/runtime/native/test_snapshot.cpp', executable)
        runner.run('runtime-snapshot-execute', [executable])

    run_tasks([Task(name, lambda name=name, wanted=wanted: execute(name, wanted))
               for name, wanted in expected.items()] + [Task('snapshot-regression', snapshot_regression)])
