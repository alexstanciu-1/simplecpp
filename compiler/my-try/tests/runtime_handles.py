"""Focused runtime shared-storage proof, not a complete ownership matrix."""
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
with CommandRunner(output / 'logs', ROOT) as runner:
    def check(name):
        directory = output / name
        directory.mkdir()
        runner.run(name, ['php', APP / 'tests' / (name + '.php'), directory])
    run_tasks([Task(name, lambda name=name: check(name)) for name in
               ['runtime_handles', 'runtime_types', 'methods', 'static_members']])
    compiler = CppFixtureCompiler(runner, ROOT, output, pch=False, runtime=True)
    compiler.flags.extend(['-I', APP / 'tests'])
    def execute(name, expected):
        executable = output / name
        compiler.compile(name + '-compile', output / 'runtime_handles' / (name + '.cpp'), executable)
        actual = runner.run(name + '-execute', [executable])
        if actual != expected:
            raise AssertionError(f'{name}: {actual!r} != {expected!r}')
    run_tasks([Task(name, lambda name=name, expected=expected: execute(name, expected))
               for name, expected in {'identity': b'9|9|9', 'result': b'13|13', 'container': b'19',
                                      'members': b'23|99|yes|42|31|51', 'null_receiver': b'',
                                      'constants': b'37|41|53|37|1',
                                      'properties': b'23|23|23|41|43|9'}.items()])
