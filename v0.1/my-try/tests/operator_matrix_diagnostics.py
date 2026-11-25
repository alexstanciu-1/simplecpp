"""Recheck existing negative matrix executables with structured diagnostics enabled."""
import argparse
import hashlib
import json
from pathlib import Path
import sys
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--input', type=Path, required=True)
parser.add_argument('--results', type=Path, required=True)
parser.add_argument('--jobs', type=int, default=32)
args = parser.parse_args()
rows = json.loads(args.input.read_text())
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)
results = []
for offset in range(0, len(rows), 256):
    chunk = rows[offset:offset+256]
    with CommandRunner(out / f'shard-{offset:05}' / 'logs', ROOT, jobs=args.jobs) as runner:
        def check(row):
            index = row['index']
            work = Path(row['program_directory'])
            program = work / 'program'
            state = dict(row['strict'])
            state['stage'] = 'execution'
            state['binary_sha256'] = hashlib.sha256(program.read_bytes()).hexdigest()
            name = f'{index:05}-diagnostic'
            try:
                runner.run(name, ['env', 'SCPP_ERROR_FORMAT=json', program], cwd=work, timeout=10)
            except RuntimeError:
                pass
            command = next(c for c in runner.commands if c['name'] == name)
            diagnostic = (runner.logs / (name + '.stderr')).read_text(errors='replace')
            metadata = json.loads((ROOT / row['info_path']).read_text())
            contract = metadata['expect']['run']
            expected = contract.get('error_json') or {}
            if not isinstance(expected, dict):
                raise ValueError('Nonempty error_json must be an object')
            try:
                actual = json.loads(diagnostic) if expected else {}
            except ValueError:
                actual = {}
            if isinstance(actual, dict) and isinstance(actual.get('error'), dict):
                actual = actual['error']
            code = command['code']
            normal_failure = isinstance(code, int) and code > 0 and not command.get('error')
            matched = normal_failure and isinstance(actual, dict) and all(actual.get(k) == v for k,v in expected.items()) and all(s in diagnostic for s in contract.get('error_contains', []))
            state.update(status='expected-runtime-rejection' if matched else 'rejection-needs-review',
                         diagnostic=diagnostic[:4000], exit_code=code,
                         expected_error_json=expected, command=name)
            result = dict(row, strict=state, run_expectation=contract)
            (out / f'{index:05}.json').write_text(json.dumps(result, indent=2))
            return result
        outcomes = run_tasks([Task(str(r['index']), lambda r=r: check(r)) for r in chunk], jobs=args.jobs, report=False)
        results.extend(o.value for o in outcomes)
    print(f'Diagnostics {len(results)}/{len(rows)}', flush=True)
(out / 'summary.json').write_text(json.dumps({'selected':len(rows),'completed':len(results),'jobs':args.jobs,'cases':results},indent=2)+'\n')
