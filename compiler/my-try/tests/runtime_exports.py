"""Focused runtime export/import and source-namespace regression proof."""
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
output = parser.parse_args().results.resolve()
output.mkdir(parents=True, exist_ok=False)
with CommandRunner(output / 'logs', ROOT, jobs=12) as runner:
    def check(name):
        target = output / name
        target.mkdir()
        runner.run(name, ['php', APP / 'tests' / (name + '.php'), target])
    run_tasks([Task(name, lambda name=name: check(name))
               for name in ['runtime_exports', 'runtime_types', 'namespaces']])
    compiler = CppFixtureCompiler(runner, ROOT, output, pch=False, runtime=True)
    compiler.flags.extend(['-I', APP / 'tests'])
    def execute(source):
        executable = source.with_suffix('')
        compiler.compile(source.stem + '-build', source, executable)
        runner.run(source.stem + '-run', [executable])
    run_tasks([Task(source.stem, lambda source=source: execute(source)) for source in [
        output / 'runtime_exports/runtime_exports.cpp', output / 'runtime_types/type_imports.cpp']])
