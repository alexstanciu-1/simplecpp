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
    'pad': b'[x   ]|xababa|abax|xaba|axab|abc|abc|aba|c3a9e4',
    'format': b'1,234|1,234.00|1_234,00|1,235|1,234.50|1_234,50|-1,234.50|1,200',
    'constant_shadow': b'', 'constant_use': b'', 'pad_empty': b'', 'pad_mode': b'',
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
        Task('source_constants', lambda: runner.run('source_constants', ['php', APP / 'tests/source_constants.php'])),
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
    def catalog_extremes():
        executable = output / 'catalog-extremes'
        compiler.compile('catalog-extremes-compile', output / 'catalog/length.cpp', executable)
        runner.run('catalog-extremes-execute', [executable])
    run_tasks([Task('catalog-extremes', catalog_extremes)])
