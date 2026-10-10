"""Compound snapshots, retained destinations and failure preservation."""
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
    def check(name):
        command = ['php', APP / 'tests' / (name + '.php')]
        if name == 'compound_effects':
            command.append(out)
        runner.run(name, command)
    run_tasks([Task(n, lambda n=n: check(n)) for n in ['compound_effects', 'operators', 'logical', 'mixed']])
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
    def execute(source):
        exe = source.with_suffix('')
        cpp.compile(source.stem + '-build', source, exe)
        runner.run(source.stem + '-execute', [exe])
    run_tasks([Task(p.stem, lambda p=p: execute(p)) for p in out.glob('*.cpp')])
