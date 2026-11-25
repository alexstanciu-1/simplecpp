"""Core process-memory catalog: semantic checks and generated-C++ execution."""
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
with CommandRunner(output / 'logs', ROOT) as runner:
    run_tasks([
        Task('memory', lambda: runner.run('memory', ['php', APP / 'tests/runtime_memory.php', output])),
        Task('catalog', lambda: runner.run('catalog', ['php', APP / 'tests/runtime_catalog.php', output / 'catalog'])),
        Task('generics', lambda: runner.run('generics', ['php', APP / 'tests/runtime_generics.php', output / 'generics'])),
    ])
    compiler = CppFixtureCompiler(runner, ROOT, output, pch=False, runtime=True)

    def execute(name, wanted):
        executable = output / name
        compiler.compile(name + '-compile', output / (name + '.cpp'), executable)
        actual = runner.run(name + '-execute', [executable])
        if name == 'usage':
            values = list(map(int, actual.split(b'|')))
            if len(values) != 6 or any(value < 0 for value in values):
                raise AssertionError(f'Invalid process memory observations: {actual!r}')
            # Linux /proc-backed observations should report a live process, not
            # zero. Other supported/unsupported platforms may use the zero fallback.
            if sys.platform == 'linux' and Path('/proc/self/status').is_file():
                if any(value == 0 for value in values):
                    raise AssertionError(f'Missing Linux memory observations: {actual!r}')
            return
        if actual != wanted:
            raise AssertionError(f'{name}: {actual!r} != {wanted!r}')

    run_tasks([Task(name, lambda name=name, wanted=wanted: execute(name, wanted))
               for name, wanted in {'usage': None, 'evaluation': b'2', 'shadow': b''}.items()])
