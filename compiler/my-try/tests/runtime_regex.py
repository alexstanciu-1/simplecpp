"""Opt-in regex catalog, generated programs and explicit PCRE2 provider linkage."""
import argparse
from pathlib import Path
import shlex
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
for name in ('catalog', 'generics'):
    (output / name).mkdir()
expected = {
    'quote': b'a\\+b\\?\\.|a\\/b|plain',
    'match': '3|Abb-cDD|Abb|cDD|AB|AB|😀'.encode(),
    'named': b'AB|AB|AB|ab', 'rows': b'2|1|b|3|2|1|2|ab|1',
    'states': b'preserved|0||fallback|0', 'grep_replace': b'2|abb|3|ba ba',
    'split': b'3||b,c|2|,', 'order': b'a|/b/|1', 'shadow': b'',
}
with CommandRunner(output / 'logs', ROOT) as runner:
    run_tasks([
        Task('regex', lambda: runner.run('regex', ['php', APP / 'tests/runtime_regex.php', output])),
        Task('unselected', lambda: runner.run('unselected', ['php', APP / 'tests/runtime_regex.php', output, '--unselected'])),
        Task('catalog', lambda: runner.run('catalog', ['php', APP / 'tests/runtime_catalog.php', output / 'catalog'])),
        Task('generics', lambda: runner.run('generics', ['php', APP / 'tests/runtime_generics.php', output / 'generics'])),
        Task('capabilities', lambda: runner.run('capabilities', ['php', APP / 'tests/type_capabilities.php'])),
    ])
    cflags = shlex.split(runner.run('pcre2-cflags', ['pkg-config', '--cflags', 'libpcre2-8']).decode())
    libraries = shlex.split(runner.run('pcre2-libs', ['pkg-config', '--libs', 'libpcre2-8']).decode())
    compiler = CppFixtureCompiler(runner, ROOT, output, pch=False, runtime=True)
    compiler.flags.extend(cflags)
    provider = output / 'regex-runtime.o'
    run_tasks([Task('provider', lambda: runner.run('provider', [*compiler.flags, '-c',
               ROOT / 'runtime/include/modules/regex/regex.cpp', '-o', provider]))])
    compiler.link_flags.extend([provider, *libraries])

    def execute(name, wanted):
        executable = output / name
        compiler.compile(name + '-compile', output / (name + '.cpp'), executable)
        actual = runner.run(name + '-execute', [executable])
        if actual != wanted:
            raise AssertionError(f'{name}: {actual!r} != {wanted!r}')

    def runtime_regression():
        executable = output / 'runtime-regex'
        compiler.compile('runtime-regex-compile', ROOT / 'tests/runtime/native/test_regex.cpp', executable)
        runner.run('runtime-regex-execute', [executable])

    run_tasks([Task(name, lambda name=name, wanted=wanted: execute(name, wanted))
               for name, wanted in expected.items()] + [Task('runtime-regression', runtime_regression)])
