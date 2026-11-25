"""Owning source metadata and generated-program lifetime/bounds proofs."""
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
    def check(name):
        directory = output / name
        directory.mkdir()
        runner.run(name, ['php', APP / 'tests' / (name + '.php'), directory])
    run_tasks([Task(name, lambda name=name: check(name)) for name in
               ['runtime_source', 'runtime_types', 'runtime_builders', 'callables', 'references']]
              + [Task('unselected-source', lambda: runner.run('unselected-source',
                 ['php', APP / 'tests/runtime_source.php', output, '--unselected']))])
    compiler = CppFixtureCompiler(runner, ROOT, output, pch=False, runtime=True)
    expected = {'shadow': b'42', 'buffer': b'0|3|97|abc|0|abc|bc|0', 'index': b'4|10|4|3|10|1|1|1',
                'bytes': b'2|255|00ff'}
    expected.update({name: b'' for name in ['negative', 'wide', 'past_end', 'overflow', 'zero_line']})
    def execute(name, wanted):
        executable = output / name
        compiler.compile(name + '-compile', output / 'runtime_source' / (name + '.cpp'), executable)
        actual = runner.run(name + '-execute', [executable])
        if actual != wanted:
            raise AssertionError(f'{name}: {actual!r} != {wanted!r}')
    run_tasks([Task(name, lambda name=name, wanted=wanted: execute(name, wanted))
               for name, wanted in expected.items()])
