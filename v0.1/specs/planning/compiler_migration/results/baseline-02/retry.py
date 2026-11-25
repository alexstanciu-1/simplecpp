import datetime
import hashlib
import importlib.util
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import time

root = Path('/home/alexv/__AI/simple_cpp/simple_cpp_01')
results = root / 'specs/planning/compiler_migration/results/baseline-02'
summary = json.loads((results / 'summary.json').read_text())
assert 'completed_utc' in summary, 'Wait for the complete baseline before retrying'
os.environ['TMPDIR'] = str(Path(summary['workspace']) / 'tmp')
tempfile.tempdir = None
snapshot = Path(summary['snapshot'])
spec = importlib.util.spec_from_file_location('prototype_tests', snapshot / 'prototype/tests/run.py')
runner = importlib.util.module_from_spec(spec)
spec.loader.exec_module(runner)
fixture = 'integration/provider_family_runtime_types.php'
record = {'started_utc': datetime.datetime.now(datetime.timezone.utc).isoformat(), 'fixture': fixture,
          'runner': str(snapshot / 'prototype/tests/run.py'), 'entry': 'run_fixture(fixture, 180)',
          'concurrency': 1, 'timeout_seconds': 180, 'reason': 'B01 fixture timed out at 10 jobs; first baseline passed this fixture.',
          'stdout': 'B01-retry.stdout.log', 'stderr': 'B01-retry.stderr.log'}
shutil.copy2(__file__, results / 'retry.py')
started = time.monotonic()
try:
    proc = runner.run_fixture(fixture, 180)
    (results / record['stdout']).write_text(proc.stdout)
    (results / record['stderr']).write_text(proc.stderr)
    record.update(status='passed' if proc.returncode == 0 else 'failed', exit_code=proc.returncode)
except subprocess.TimeoutExpired as exc:
    record.update(status='timed_out')
    for name, value in [('stdout', exc.stdout), ('stderr', exc.stderr)]:
        (results / record[name]).write_text(value.decode() if isinstance(value, bytes) else value or '')
record['elapsed_seconds'] = round(time.monotonic() - started, 3)
inv = json.loads((root / 'specs/planning/compiler_migration/source_inventory.json').read_text())
record['changed_original_files'] = [f['source'] for f in inv['files'] if hashlib.sha256((Path(inv['source_root']) / f['source']).read_bytes()).hexdigest() != f['sha256']]
configs = {c['path'] for c in summary['configuration_changes']}
record['snapshot_unexpected_changes'] = [f['source'] for f in inv['files'] if f['source'] not in configs and hashlib.sha256((snapshot / f['source']).read_bytes()).hexdigest() != f['sha256']]
record['git'] = {kind: {key: subprocess.check_output(['git', *cmd], cwd=summary[kind + '_root'], text=True).strip() for key, cmd in [('head', ['rev-parse', 'HEAD']), ('status', ['status', '--porcelain'])]} for kind in ['source', 'runtime']}
(results / 'B01-retry.json').write_text(json.dumps(record, indent=2) + '\n')
print(json.dumps(record, indent=2), flush=True)
