"""Datetime module catalog, generated programs and explicit provider linkage."""
import argparse
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
for name in ('catalog', 'generics'):
    (output / name).mkdir()
expected = {
    'utc': b'1970-01-01T00:00:00Z|0|2024-02-29T12:34:56Z',
    'local': b'2024-02-29 00:00:00|2024-02-29 12:34:56|2024-02-29 12:34:56|2024-02-29 12:34:56|1970 70 01 1 01 1 00 0 00 00 0|!',
    'errors': b'', 'clocks': None, 'order': b'1970|!|1', 'shadow': b'',
}
with CommandRunner(output / 'logs', ROOT) as runner:
    run_tasks([
        Task('datetime', lambda: runner.run('datetime', ['php', APP / 'tests/runtime_datetime.php', output])),
        Task('catalog', lambda: runner.run('catalog', ['php', APP / 'tests/runtime_catalog.php', output / 'catalog'])),
        Task('generics', lambda: runner.run('generics', ['php', APP / 'tests/runtime_generics.php', output / 'generics'])),
    ])
    compiler = CppFixtureCompiler(runner, ROOT, output, pch=False, runtime=True)
    provider = output / 'datetime-runtime.o'
    run_tasks([Task('provider', lambda: runner.run('provider', [*compiler.flags, '-c',
               ROOT / 'runtime/include/modules/datetime/datetime.cpp', '-o', provider]))])
    compiler.link_flags.append(provider)

    def execute(name, wanted):
        executable = output / name
        compiler.compile(name + '-compile', output / (name + '.cpp'), executable)
        actual = runner.run(name + '-execute', ['env', 'TZ=UTC0', executable])
        if name == 'clocks':
            before_us, after_us, before_ns, after_ns = map(int, actual.split(b'|'))
            if not (0 <= before_us <= after_us < 2**64 and 0 <= before_ns <= after_ns < 2**64):
                raise AssertionError(f'Invalid monotonic counters: {actual!r}')
            return
        if actual != wanted:
            raise AssertionError(f'{name}: {actual!r} != {wanted!r}')

    def runtime_regression():
        executable = output / 'runtime-datetime'
        compiler.compile('runtime-datetime-compile', ROOT / 'tests/runtime/native/test_datetime.cpp', executable)
        runner.run('runtime-datetime-execute', ['env', 'TZ=UTC0', executable])

    run_tasks([Task(name, lambda name=name, wanted=wanted: execute(name, wanted))
               for name, wanted in expected.items()] + [Task('runtime-regression', runtime_regression)])
