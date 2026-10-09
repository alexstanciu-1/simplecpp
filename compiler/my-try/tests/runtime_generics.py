"""Runtime type-pattern inference and count bindings, without rebuilding the compiler."""
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
for name in ['catalog', 'observation', 'json']:
    (output / name).mkdir()
with CommandRunner(output / 'logs', ROOT) as runner:
    run_tasks([
        Task('generics', lambda: runner.run('generics', ['php', APP / 'tests/runtime_generics.php', output])),
        Task('catalog', lambda: runner.run('catalog', ['php', APP / 'tests/runtime_catalog.php', output / 'catalog'])),
        Task('observation', lambda: runner.run('observation', ['php', APP / 'tests/runtime_observation.php', output / 'observation'])),
        Task('json', lambda: runner.run('json', ['php', APP / 'tests/json.php', output / 'json'])),
        Task('callables', lambda: runner.run('callables', ['php', APP / 'tests/callables.php'])),
    ])
    compiler = CppFixtureCompiler(runner, ROOT, output, pch=False, runtime=True)
    compiler.flags.extend(['-I', APP / 'tests', '-fsanitize=address', '-fno-omit-frame-pointer'])
    def execute(source):
        executable = source.with_suffix('')
        compiler.compile(source.stem + '-compile', source, executable)
        runner.run(source.stem + '-execute', ['env', 'ASAN_OPTIONS=detect_leaks=0', executable])
    run_tasks([Task(source.stem, lambda source=source: execute(source)) for source in sorted(output.glob('*.cpp'))])
