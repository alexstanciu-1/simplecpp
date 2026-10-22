"""V-D01 runtime-first mutation probes; --baseline records pre-repair behavior."""
import argparse
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', required=True, type=Path)
parser.add_argument('--baseline', action='store_true')
args = parser.parse_args()
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
    cpp.flags.extend(['-std=c++23', '-fsanitize=address,undefined',
                      '-fno-sanitize-recover=undefined'])

    def check():
        exe = out / 'iteration-mutation'
        cpp.compile('build', APP / 'tests/iteration_mutation_runtime.cpp', exe)
        observed = runner.run('execute', [exe, '--baseline'] if args.baseline else [exe])
        (out / 'observed.txt').write_bytes(observed)
        expected = b'live: 1 20 3\nappend: 1 20 3\nreplacement: 1 2 3\n'
        (out / 'expected.txt').write_bytes(expected)
        if not args.baseline:
            assert observed == expected, (observed, expected)

    run_tasks([Task('iteration-mutation-runtime', check)])
