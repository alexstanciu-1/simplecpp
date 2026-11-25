"""Runtime-first enum wrapper state/copy/extraction proofs."""
import argparse
from pathlib import Path
import sys
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler
p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', required=True, type=Path)
out = p.parse_args().results.resolve()
out.mkdir(parents=True, exist_ok=False)
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
    cpp.flags.extend(['-std=c++23', '-fsanitize=address,undefined', '-fno-sanitize-recover=undefined'])
    def check():
        exe = out / 'enum-wrapper'
        cpp.compile('build', APP / 'tests/enum_wrapper_runtime.cpp', exe)
        assert runner.run('execute', [exe]) == b'enum wrapper runtime passed\n'
    run_tasks([Task('enum-wrapper-runtime', check)])
