"""Field-rooted lazy keyed probes/removal and retained owner execution."""
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
    run_tasks([Task('fields', lambda: runner.run('fields', ['php', APP / 'tests/field_container_probes.php', out])),
               Task('indexing', lambda: runner.run('indexing', ['php', APP / 'tests/indexing.php']))])
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
    def execute(source):
        exe = source.with_suffix('')
        cpp.compile(source.stem + '-build', source, exe)
        runner.run(source.stem + '-execute', [exe])
    run_tasks([Task(p.stem, lambda p=p: execute(p)) for p in out.glob('*.cpp')])
