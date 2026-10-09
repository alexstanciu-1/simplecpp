"""Startup catalog validation, retained input and generated strlen byte semantics."""
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
with CommandRunner(output / 'logs', ROOT) as runner:
    run_tasks([Task('catalog', lambda: runner.run('catalog', ['php', APP / 'tests/runtime_catalog.php', output])),
               Task('callables', lambda: runner.run('callables', ['php', APP / 'tests/callables.php']))])
    compiler = CppFixtureCompiler(runner, ROOT, output, pch=False, runtime=True)
    def execute(name):
        executable = output / name
        compiler.compile(name + '-compile', output / (name + '.cpp'), executable)
        runner.run(name + '-execute', [executable])
    run_tasks([Task(name, lambda name=name: execute(name)) for name in ['length', 'shadow']])
