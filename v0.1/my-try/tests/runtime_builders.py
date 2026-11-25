"""Builder module metadata, mutation/copy boundaries and generated C++ behavior."""
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
    suites = ['runtime_builders', 'runtime_strings', 'runtime_types', 'type_catalog', 'callables', 'references']
    run_tasks([Task(name, lambda name=name: check(name)) for name in suites])
    compiler = CppFixtureCompiler(runner, ROOT, output, pch=False, runtime=True)
    expected = {'parts': b'6|10|hello-42-1|0|0|again',
                'text': b'10|hello-42-1|hello-42-1|hello-42-1!|0|again|0'}
    for family in ['string_parts_builder', 'text_builder']:
        expected[family + '_copy'] = b'A|AB|AXR|AXRC|AXR|0'
        expected[family + '_bytes'] = b'31|' + (b'\x00\xff' + 'é中😀'.encode() + b'-9223372036854775808').hex().encode()
        expected[family + '_order'] = b'after!|1'
        expected[family + '_negative'] = b''
    def execute(name, wanted):
        executable = output / name
        compiler.compile(name + '-compile', output / 'runtime_builders' / (name + '.cpp'), executable)
        actual = runner.run(name + '-execute', [executable])
        if actual != wanted:
            raise AssertionError(f'{name}: {actual!r} != {wanted!r}')
    def native(name):
        executable = output / ('native-' + name)
        compiler.compile('native-' + name + '-compile', ROOT / 'tests/runtime/native' / ('test_' + name + '.cpp'), executable)
        runner.run('native-' + name + '-execute', [executable])
    run_tasks([Task(name, lambda name=name, wanted=wanted: execute(name, wanted)) for name, wanted in expected.items()]
              + [Task('native-' + name, lambda name=name: native(name)) for name in ['string_parts_builder', 'text_builder']])
