"""Replay exported boxed operator probes independently of unrelated suite gates."""
import argparse
import json
from pathlib import Path
import re
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--exports', required=True, type=Path)
parser.add_argument('--results', required=True, type=Path)
args = parser.parse_args()
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)
fixtures = [r for r in json.loads((args.exports / 'executions.json').read_text())
            if re.fullmatch(r'boxed_(mixed|dynamic)_(add|subtract|multiply|divide|remainder|unary_and_snapshots|failures|comparisons|text|containers)', Path(r['path']).stem)]
if len(fixtures) != 20:
    raise ValueError('Expected all 20 exported boxed operator probes')
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    compiler = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
    def execute(row):
        source = Path(row['path'])
        executable = out / source.stem
        compiler.compile(source.stem + '-build', source, executable)
        runner.run(source.stem + '-execute', [executable], expected=row['exit_code'])
    def snapshot(family):
        source_dir = out / family
        source_dir.mkdir()
        source = source_dir / 'main.phs'
        source.write_text('$v ' + family + ' = 1; $out ' + family + ' = $v++ + $v; '
                          '$sum int = $out; $after int = $v; '
                          'if ($sum !== 3 || $after !== 2) { return 1; } return 0;\n')
        artifacts = runner.run(family + '-snapshot-generate', ['php', APP / 'main.php', '--s2s', source_dir])
        (source_dir / 'artifacts.json').write_bytes(artifacts)
        executable = out / (family + '-snapshot')
        compiler.compile_artifacts(family + '-snapshot-build', artifacts, executable)
        runner.run(family + '-snapshot-execute', [executable])
    run_tasks([Task(Path(r['path']).stem, lambda r=r: execute(r)) for r in fixtures]
              + [Task(f + '-snapshot', lambda f=f: snapshot(f)) for f in ('mixed', 'dynamic')], jobs=12)
(out / 'summary.json').write_text(json.dumps(dict(passed=True, boxed_programs=20,
    snapshot_programs=2, native_compiler_rebuilt=False), indent=2) + '\n')
