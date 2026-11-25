"""Executable CLI initialization and metadata calls across generated units."""
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
for name in ('catalog', 'generics', 'result-containers', 's2s'):
    (output / name).mkdir()
with CommandRunner(output / 'logs', ROOT) as runner:
    run_tasks([
        Task('cli', lambda: runner.run('cli', ['php', APP / 'tests/runtime_cli.php', output])),
        Task('catalog', lambda: runner.run('catalog', ['php', APP / 'tests/runtime_catalog.php', output / 'catalog'])),
        Task('generics', lambda: runner.run('generics', ['php', APP / 'tests/runtime_generics.php', output / 'generics'])),
        Task('results', lambda: runner.run('results', ['php', APP / 'tests/result_containers.php', output / 'result-containers'])),
        Task('s2s', lambda: runner.run('s2s', ['php', APP / 'tests/s2s.php', output / 's2s'])),
    ])
    compiler = CppFixtureCompiler(runner, ROOT, output, pch=False, runtime=True)

    def arguments():
        executable = output / 'arguments'
        compiler.compile('arguments-compile', output / 'arguments.cpp', executable)
        for label, values in [('none', []), ('values', ['', 'two words', 'é中😀', '--flag=x'])]:
            command = [executable, *values]
            actual = runner.run('arguments-' + label, command)
            raw = [os.fsencode(str(value)) for value in command]
            expected = ('|'.join([str(len(raw))] * 3) + '\n').encode()
            expected += b''.join(str(len(value)).encode() + b':' + value + b'\n' for value in raw)
            expected += (str(len(raw[0])).encode() + b':' + raw[0] + b'\n') * 2
            if actual != expected:
                raise AssertionError(f'{label}: {actual!r} != {expected!r}')

    def execute(name, source):
        executable = output / name
        compiler.compile(name + '-compile', source, executable)
        runner.run(name + '-execute', [executable])

    run_tasks([
        Task('arguments', arguments),
        Task('shadow', lambda: execute('shadow', output / 'shadow.cpp')),
        Task('instrumented-result', lambda: execute('instrumented-result', output / 'result-containers/failed_extract.cpp')),
        Task('instrumented-generic', lambda: execute('instrumented-generic', output / 'generics/count_bad_mixed_0.cpp')),
        Task('runtime-process', lambda: execute('runtime-process', ROOT / 'tests/runtime/native/test_php_process.cpp')),
    ])
