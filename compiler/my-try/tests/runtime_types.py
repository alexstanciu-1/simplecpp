"""Metadata-only runtime types, registry rules and generated C++ execution."""
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
    suites = ['runtime_types', 'type_catalog', 'type_capabilities', 'initialization',
              'runtime_catalog', 'runtime_generics', 'runtime_observation', 'result_containers',
              'runtime_strings', 'runtime_datetime', 'runtime_filesystem', 'runtime_regex']
    def check(name):
        directory = output / name
        directory.mkdir()
        runner.run(name, ['php', APP / 'tests' / (name + '.php'), directory])
    run_tasks([Task(name, lambda name=name: check(name)) for name in suites])
    compiler = CppFixtureCompiler(runner, ROOT, output, pch=False, runtime=True)
    compiler.flags.extend(['-I', APP / 'tests'])
    def execute(source):
        executable = source.with_suffix('')
        compiler.compile(source.stem + '-compile', source, executable)
        runner.run(source.stem + '-execute', [executable])
    run_tasks([Task(source.stem, lambda source=source: execute(source))
               for source in sorted((output / 'runtime_types').glob('*.cpp'))])
