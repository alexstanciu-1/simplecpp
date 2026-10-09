"""Runtime-owned string module bindings, byte behavior and ordered value arguments."""
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
(output / 'generics').mkdir()
expected = {'case': 'abc-é中😀|ABC-é中😀|hELLO|Hello|ÉX|éx'.encode(), 'empty': b'[]',
            'predicates': b'', 'order': b'changed', 'purity': b'AbC|abc|ABC', 'shadow': b'', 'boxed_failure': b''}
expected.update({
    'trim_default': '[abc]|[abc  ]|[  abc]|[]|[]|[ x ]'.encode(),
    'trim_masks': b'abc|abcxy|xyabc|abc|b|',
    'hex_bytes': b'[]|417a|00ff|c3a9e4b8adf09f9880',
    'replace': b'XXa|abc|abc|b|aa|615862',
    'trim_order': b'abc|changed',
})
with CommandRunner(output / 'logs', ROOT) as runner:
    run_tasks([
        Task('strings', lambda: runner.run('strings', ['php', APP / 'tests/runtime_strings.php', output])),
        Task('catalog', lambda: runner.run('catalog', ['php', APP / 'tests/runtime_catalog.php', output / 'catalog'])),
        Task('generics', lambda: runner.run('generics', ['php', APP / 'tests/runtime_generics.php', output / 'generics'])),
    ])
    compiler = CppFixtureCompiler(runner, ROOT, output, pch=False, runtime=True)
    def execute(name, wanted):
        executable = output / name
        compiler.compile(name + '-compile', output / (name + '.cpp'), executable)
        actual = runner.run(name + '-execute', [executable])
        if actual != wanted:
            raise AssertionError(f'{name}: {actual!r} != {wanted!r}')
    run_tasks([Task(name, lambda name=name, wanted=wanted: execute(name, wanted))
               for name, wanted in expected.items()])
