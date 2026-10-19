"""Execute explicitly contracted source probes; preserve every failed stage."""
import argparse
import hashlib
import json
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks, positive_jobs
from cpp_fixture import CppFixtureCompiler

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--manifest', type=Path, default=Path(__file__).with_suffix('.json'))
parser.add_argument('--results', required=True, type=Path)
parser.add_argument('--php', default='php')
parser.add_argument('--jobs', type=positive_jobs, default=8)
args = parser.parse_args()
output = args.results.resolve()
output.mkdir(parents=True, exist_ok=False)
manifest = json.loads(args.manifest.read_text())
(output / 'manifest.json').write_text(json.dumps(manifest, indent=2) + '\n')
cases = manifest['cases']
names = [case['id'] for case in cases]
if len(set(names)) != len(names) or any(Path(name).name != name or name in ('.', '..') for name in names):
    raise ValueError('Probe IDs must be unique path components')
with CommandRunner(output / 'logs', APP.parents[1], jobs=args.jobs) as runner:
    compiler = CppFixtureCompiler(runner, APP.parents[1], output, pch=False, runtime=True)
    def execute(case):
        name = case['id']
        work = output / name
        source = work / 'source'
        source.mkdir(parents=True)
        source_file = source / 'main.phs'
        source_file.write_text(case['source'] + '\n')
        result = dict(id=name, checks=case['checks'], authority=case['authority'],
                      source_sha256=hashlib.sha256(source_file.read_bytes()).hexdigest(),
                      stage='generation', passed=False)
        try:
            payload = runner.run(name + '-generate', [args.php, APP / 'main.php', '--s2s', source])
            (work / 'artifacts.json').write_bytes(payload)
            result['stage'] = 'cpp-build'
            executable = work / 'program'
            compiler.compile_artifacts(name + '-build', payload.decode(), executable)
            result['stage'] = 'execution'
            actual = runner.run(name + '-execute', [executable], expected=case['exit_code'])
            result['stdout_hex'] = actual.hex()
            if actual != case['stdout'].encode():
                raise AssertionError(f'Unexpected stdout: {actual!r}')
            result.update(stage='passed', passed=True)
        except Exception as error:
            result['error'] = str(error)
            raise
        finally:
            (work / 'outcome.json').write_text(json.dumps(result, indent=2) + '\n')
    try:
        run_tasks([Task(case['id'], lambda case=case: execute(case)) for case in cases], jobs=args.jobs)
    finally:
        outcomes = [json.loads(path.read_text()) for path in sorted(output.glob('*/outcome.json'))]
        (output / 'outcomes.json').write_text(json.dumps(outcomes, indent=2) + '\n')
