"""Non-retaining runtime observation, including noncopyable values and temporary owners."""
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
(output / 'catalog').mkdir()
with CommandRunner(output / 'logs', ROOT) as runner:
    run_tasks([
        Task('observation', lambda: runner.run('observation', ['php', APP / 'tests/runtime_observation.php', output])),
        Task('catalog', lambda: runner.run('catalog', ['php', APP / 'tests/runtime_catalog.php', output / 'catalog'])),
        Task('callables', lambda: runner.run('callables', ['php', APP / 'tests/callables.php'])),
        Task('references', lambda: runner.run('references', ['php', APP / 'tests/references.php', output / 'references'])),
    ])
    compiler = CppFixtureCompiler(runner, ROOT, output, pch=False, runtime=True)
    # A temporary owner's member must not outlive its owner before the observing call.
    compiler.flags.extend(['-fsanitize=address', '-fno-omit-frame-pointer'])
    def execute(name):
        executable = output / name
        compiler.compile(name + '-compile', output / (name + '.cpp'), executable)
        # LeakSanitizer cannot inspect processes under the sandbox's ptrace boundary.
        runner.run(name + '-execute', ['env', 'ASAN_OPTIONS=detect_leaks=0', executable])
    run_tasks([Task(name, lambda name=name: execute(name))
               for name in ['locals', 'temporaries', 'fields', 'noncopyable', 'families']])
