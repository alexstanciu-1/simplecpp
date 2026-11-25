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
    sanitizer_flags = ['-fsanitize=address,undefined', '-fno-sanitize-recover=undefined',
                       '-fno-omit-frame-pointer']
    runtime_object = out / 'sanitized-runtime.o'
    runner.run('runtime-build-sanitized', ['clang++', '-std=c++23', *sanitizer_flags,
        '-DSCPP_LANGUAGE_TARGET_PHP=1', '-I', ROOT / 'runtime/include', '-pthread', '-c',
        ROOT / 'runtime/include/core/runtime.cpp', '-o', runtime_object], timeout=240)
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True, runtime_object=runtime_object)
    cpp.flags.extend(['-std=c++23', *sanitizer_flags])

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
