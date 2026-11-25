#!/usr/bin/env python3
"""Filesystem catalog, private-directory generated proofs and snapshot regression."""
import argparse
import json
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
expected = {'roundtrip': b'p||', 'paths': b'A|a|b|0|' + os.fsencode(output / 'data-paths/scan/A') + b'|a/b|c|',
            'failures': b'preserved', 'snapshot': b'payload||', 'shadow': b''}
for name in expected:
    (output / ('data-' + name)).mkdir()
for name in ('catalog', 'generics', 'result-containers', 'callable-results'):
    (output / name).mkdir()
paths = output / 'data-paths'
(paths / 'scan').mkdir()
(paths / 'empty').mkdir()
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
        Task('type-catalog', lambda: runner.run('type-catalog', ['php', APP / 'tests/type_catalog.php'])),
        Task('type-capabilities', lambda: runner.run('type-capabilities', ['php', APP / 'tests/type_capabilities.php'])),
        Task('filesystem', lambda: runner.run('filesystem', ['php', APP / 'tests/runtime_filesystem.php', output])),
        Task('catalog', lambda: runner.run('catalog', ['php', APP / 'tests/runtime_catalog.php', output / 'catalog'])),
        Task('generics', lambda: runner.run('generics', ['php', APP / 'tests/runtime_generics.php', output / 'generics'])),
        Task('results', lambda: runner.run('results', ['php', APP / 'tests/results.php'])),
        Task('result-containers', lambda: runner.run('result-containers', ['php', APP / 'tests/result_containers.php', output / 'result-containers'])),
        Task('callable-results', lambda: runner.run('callable-results', ['php', APP / 'tests/callable_results.php', output / 'callable-results'])),
    ])
    compiler = CppFixtureCompiler(runner, ROOT, output, pch=False, runtime=True)
    # core/runtime.cpp already bundles filesystem.cpp and snapshot.cpp.
    # Linking their standalone objects as well would duplicate definitions.

    def execute(name, wanted):
        executable = output / name
        label = name.replace('/', '-')
        compiler.compile(label + '-compile', output / (name + '.cpp'), executable)
        actual = runner.run(label + '-execute', [executable])
        if actual != wanted:
            raise AssertionError(f'{name}: {actual!r} != {wanted!r}')

    def snapshot_regression():
        executable = output / 'runtime-snapshot'
        compiler.compile('runtime-snapshot-compile', ROOT / 'tests/runtime/native/test_snapshot.cpp', executable)
        runner.run('runtime-snapshot-execute', [executable])

    expected.update({'result-containers/' + name: value.encode()
                     for name, value in json.loads((output / 'result-containers/executions.json').read_text()).items()})
    run_tasks([Task(name, lambda name=name, wanted=wanted: execute(name, wanted))
               for name, wanted in expected.items()] + [Task('snapshot-regression', snapshot_regression)])
