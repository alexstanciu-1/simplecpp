"""Focused V-D01 layout and no-mutation vector traversal measurements."""
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
out = parser.parse_args().results.resolve()
out.mkdir(parents=True, exist_ok=False)
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
    cpp.flags.extend(['-std=c++23', '-O2'])

    def check():
        executable = out / 'iteration-cost'
        cpp.compile('build', APP / 'tests/iteration_mutation_cost.cpp', executable)
        (out / 'observed.txt').write_bytes(runner.run('measure', [executable]))

    run_tasks([Task('iteration-cost', check)])
