#!/usr/bin/env python3
"""Capture the adopted runtime-preparation gates and source integrity evidence."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import time

from run_baseline import ROOT, RUNTIME, RUNTIME_REVISION, command, write


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('results', type=Path)
    args = parser.parse_args()
    results = args.results.resolve()
    results.mkdir(parents=True, exist_ok=True)
    if (results / 'preparation.json').exists():
        raise SystemExit('Use a fresh preparation evidence directory.')
    inventory = json.loads((ROOT / 'specs/planning/compiler_migration/adoption_inventory.json').read_text())
    source = Path(json.loads((ROOT / 'specs/planning/compiler_migration/source_inventory.json').read_text())['source_root'])
    before = {f['destination']: hashlib.sha256((ROOT / f['destination']).read_bytes()).hexdigest() for f in inventory['files']}
    assert command(['git', 'rev-parse', 'HEAD'], RUNTIME) == RUNTIME_REVISION
    assert command(['git', 'status', '--porcelain'], RUNTIME) == ''
    workspace = Path(tempfile.mkdtemp(prefix='scpp-adoption-preparation-'))
    temp = workspace / 'tmp'
    temp.mkdir()
    env = dict(os.environ, TMPDIR=str(temp), PYTHONDONTWRITEBYTECODE='1')
    base = 'compiler/src-runtime-preparation/tests/'
    gates = [('B02', ['php', base + 'run.php']),
             ('B03', ['php', base + 'families/run.php', str(workspace / 'families')]),
             ('B04', ['python3', base + 'lifecycle/run.py', '--workspace', str(workspace / 'lifecycle')]),
             ('B05', ['python3', base + 'source_operations/run.py', '--workspace', str(workspace / 'source-operations')]),
             ('B06', ['python3', base + 'specializations/run.py', '--workspace', str(workspace / 'specializations')]),
             ('B07', ['python3', base + 'sequence_aliasing/run.py'])]
    report = {'workspace': str(workspace), 'cwd': str(ROOT), 'runtime_revision': RUNTIME_REVISION,
              'source_revision': inventory['source_revision'], 'gates': [], 'adopted_hashes_before': before}
    shutil.copy2(__file__, results / 'preparation_runner.py')
    for gate, cmd in gates:
        record = {'id': gate, 'command': cmd, 'status': 'running'}
        report['gates'].append(record)
        write(results / 'preparation.json', report)
        print(gate + ' started', flush=True)
        started = time.monotonic()
        with (results / (gate + '.stdout.log')).open('w') as out, (results / (gate + '.stderr.log')).open('w') as err:
            proc = subprocess.run(cmd, cwd=ROOT, env=env, stdout=out, stderr=err)
        record.update(status='passed' if proc.returncode == 0 else 'failed', exit_code=proc.returncode, elapsed_seconds=round(time.monotonic() - started, 3))
        write(results / 'preparation.json', report)
        print(gate + ' ' + record['status'], flush=True)
    report['changed_adopted_files'] = [p for p, digest in before.items() if hashlib.sha256((ROOT / p).read_bytes()).hexdigest() != digest]
    report['changed_original_files'] = [f['source'] for f in inventory['files'] if hashlib.sha256((source / f['source']).read_bytes()).hexdigest() != f['sha256']]
    report['git'] = {name: {'head': command(['git', 'rev-parse', 'HEAD'], path), 'status': command(['git', 'status', '--porcelain'], path)} for name, path in [('source', source), ('runtime', RUNTIME)]}
    for name in ['families', 'lifecycle', 'source-operations', 'specializations']:
        for path in (workspace / name).rglob('*'):
            if path.is_file() and (path.name in {'report.json', 'accepted.json', 'results.json'} or path.suffix == '.log'):
                target = results / 'reports' / name / path.relative_to(workspace / name)
                target.parent.mkdir(parents=True, exist_ok=True)
                shutil.copy2(path, target)
    report['passed'] = all(g['status'] == 'passed' for g in report['gates']) and not report['changed_adopted_files'] and not report['changed_original_files'] and all(g['status'] == '' for g in report['git'].values()) and report['git']['source']['head'] == inventory['source_revision'] and report['git']['runtime']['head'] == RUNTIME_REVISION
    write(results / 'preparation.json', report)
    raise SystemExit(0 if report['passed'] else 1)


if __name__ == '__main__':
    main()
