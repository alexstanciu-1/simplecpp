"""Environment/shell catalog proofs with isolated child environment and fixed commands."""
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
        Task('environment', lambda: runner.run('environment', ['php', APP / 'tests/runtime_environment.php', output])),
        Task('catalog', lambda: runner.run('catalog', ['php', APP / 'tests/runtime_catalog.php', output / 'catalog'])),
    ])
    compiler = CppFixtureCompiler(runner, ROOT, output, pch=False, runtime=True)

    def execute(name, wanted):
        executable = output / name
        compiler.compile(name + '-compile', output / (name + '.cpp'), executable)
        actual = runner.run(name + '-execute', ['env', '-u', 'SCPP_ENV_PROBE_MISSING',
                            'SCPP_ENV_PROBE_EMPTY=', 'SCPP_ENV_PROBE_VALUE=hello world', executable])
        if actual != wanted:
            raise AssertionError(f'{name}: {actual!r} != {wanted!r}')

    def shell():
        executable = output / 'shell'
        compiler.compile('shell-compile', output / 'shell.cpp', executable)
        for label, command, wanted in [
            ('text', "printf 'hello world'", b'hello world'),
            ('empty', "printf ''", b''),
            ('binary', "printf 'a\\000b\\n'", b'a\0b\n'),
            ('exit', 'exit 7', b''),
            ('chunks', "printf '%05000d' 0", b'0' * 5000),
        ]:
            actual = runner.run('shell-' + label, [executable, command])
            expected = str(len(wanted)).encode() + b':' + wanted
            if actual != expected:
                raise AssertionError(f'{label}: {actual!r} != {expected!r}')

    def pipe_failure():
        executable = output / 'pipe-failure'
        compiler.compile('pipe-failure-compile', ROOT / 'tests/runtime/native/test_php_process_pipe_failure.cpp', executable)
        runner.run('pipe-failure-execute', [executable])

    run_tasks([Task(name, lambda name=name, wanted=wanted: execute(name, wanted))
               for name, wanted in {'environment': b'preserved|0|hello world|fallback|0',
                                    'evaluation': b'1', 'shadow': b''}.items()]
              + [Task('shell', shell), Task('pipe-failure', pipe_failure)])
