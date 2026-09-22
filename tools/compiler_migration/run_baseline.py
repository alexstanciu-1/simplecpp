#!/usr/bin/env python3
"""Run the pinned prototype baseline without modifying the original checkout."""
import argparse
import datetime
import hashlib
import json
import os
import platform
from pathlib import Path
import shutil
import subprocess
import tempfile
import time

ROOT = Path(__file__).resolve().parents[2]
INVENTORY = ROOT / 'specs/planning/compiler_migration/source_inventory.json'
RUNTIME = Path('/home/alexv/__AI/simple_cpp_compiler/vendor/simple_cpp')
RUNTIME_REVISION = 'fc20d73d040c4e69758bcec0b1caf40c26755f72'


def command(args, cwd=None):
    return subprocess.check_output(args, cwd=cwd, text=True).strip()


def write(path, value):
    path.write_text(json.dumps(value, indent=2) + '\n')


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('results', type=Path, help='new durable evidence directory')
    args = parser.parse_args()
    results = args.results.resolve()
    if results.exists():
        raise SystemExit('Use a new evidence directory; previous evidence is never overwritten.')
    inventory = json.loads(INVENTORY.read_text())
    source = Path(inventory['source_root'])
    assert command(['git', 'rev-parse', 'HEAD'], source) == inventory['source_revision']
    assert command(['git', 'status', '--porcelain'], source) == ''
    assert command(['git', 'rev-parse', 'HEAD'], RUNTIME) == RUNTIME_REVISION
    assert command(['git', 'status', '--porcelain'], RUNTIME) == ''
    # Scratch is deliberately retained for diagnosis; only durable reports are copied back.
    workspace = Path(tempfile.mkdtemp(prefix='scpp-migration-baseline-'))
    snapshot = workspace / 'source'
    snapshot.mkdir()
    for item in inventory['files']:
        original = source / item['source']
        assert hashlib.sha256(original.read_bytes()).hexdigest() == item['sha256'], item['source']
        target = snapshot / item['source']
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(original, target)
    results.mkdir(parents=True)
    shutil.copy2(__file__, results / 'runner.py')
    (results / 'config').mkdir()
    changes = []
    for name, key, value in [
        # Some original proof runners join these paths directly to the config directory.
        ('prototype/src-runtime-preparation/config.json', 'include_directories', [os.path.relpath(RUNTIME / 'runtime/include', snapshot / 'prototype/src-runtime-preparation'), 'include']),
        ('tools/toolchain.json', 'scpp', str(RUNTIME / 'bin/scpp.php')),
    ]:
        path = snapshot / name
        config = json.loads(path.read_text())
        changes.append({'path': name, 'field': key, 'before': config[key], 'after': value})
        config[key] = value
        write(path, config)
        shutil.copy2(path, results / 'config' / (name.replace('/', '__')))
    temp = workspace / 'tmp'
    temp.mkdir()
    env = dict(os.environ, TMPDIR=str(temp), PYTHONDONTWRITEBYTECODE='1')
    versions = {}
    for name, flags in [('php', ['-v']), ('python3', ['--version']), ('clang', ['--version']), ('clang++-18', ['--version']), ('ld.lld-18', ['--version']), ('mold', ['--version'])]:
        versions[name] = command([name, *flags]).splitlines()[0]
    gates = [
        ('B01', ['python3', 'prototype/tests/run.py', '--jobs', '10', '--timeout', '180']),
        ('B02', ['php', 'prototype/src-runtime-preparation/tests/run.php']),
        ('B03', ['php', 'prototype/src-runtime-preparation/tests/families/run.php', str(workspace / 'families')]),
        ('B04', ['python3', 'prototype/src-runtime-preparation/tests/lifecycle/run.py', '--workspace', str(workspace / 'lifecycle')]),
        ('B05', ['python3', 'prototype/src-runtime-preparation/tests/source_operations/run.py', '--workspace', str(workspace / 'source-operations')]),
        ('B06', ['python3', 'prototype/src-runtime-preparation/tests/specializations/run.py', '--workspace', str(workspace / 'specializations')]),
        ('B07', ['python3', 'prototype/src-runtime-preparation/tests/sequence_aliasing/run.py']),
    ]
    report = {'started_utc': datetime.datetime.now(datetime.timezone.utc).isoformat(),
              'source_revision': inventory['source_revision'], 'runtime_revision': RUNTIME_REVISION,
              'source_root': str(source), 'runtime_root': str(RUNTIME), 'snapshot': str(snapshot),
              'workspace': str(workspace), 'inventory_sha256': hashlib.sha256(INVENTORY.read_bytes()).hexdigest(),
              'runner_sha256': hashlib.sha256(Path(__file__).read_bytes()).hexdigest(),
              'host': platform.platform(), 'clang_target': command(['clang', '-dumpmachine']),
              'configuration_changes': changes, 'versions': versions, 'environment_overrides': {'TMPDIR': str(temp), 'PYTHONDONTWRITEBYTECODE': '1'},
              'gates': [], 'limitations': ['Original runners may remove their own temporary fixture workspaces, including failed fixture workspaces; full process logs are retained.', 'No compiler/prototype algorithms or test assertions were changed.', 'Suites run sequentially; their internal concurrency is unchanged.']}
    write(results / 'summary.json', report)
    for gate, cmd in gates:
        record = {'id': gate, 'command': cmd, 'cwd': str(snapshot), 'status': 'running', 'stdout': gate + '.stdout.log', 'stderr': gate + '.stderr.log'}
        report['gates'].append(record)
        write(results / 'summary.json', report)
        print(gate + ' started', flush=True)
        started = time.monotonic()
        with (results / record['stdout']).open('w') as out, (results / record['stderr']).open('w') as err:
            proc = subprocess.run(cmd, cwd=snapshot, env=env, stdout=out, stderr=err)
        record.update(status='passed' if proc.returncode == 0 else 'failed', exit_code=proc.returncode, elapsed_seconds=round(time.monotonic() - started, 3))
        write(results / 'summary.json', report)
        print(f"{gate} {record['status']} ({record['elapsed_seconds']}s)", flush=True)
    changed = []
    for item in inventory['files']:
        if hashlib.sha256((source / item['source']).read_bytes()).hexdigest() != item['sha256']:
            changed.append(item['source'])
    report['integrity'] = {'changed_original_files': changed,
        'source_git_status': command(['git', 'status', '--porcelain'], source),
        'source_head': command(['git', 'rev-parse', 'HEAD'], source),
        'runtime_git_status': command(['git', 'status', '--porcelain'], RUNTIME),
        'runtime_head': command(['git', 'rev-parse', 'HEAD'], RUNTIME)}
    expected_changed = {c['path'] for c in changes}
    report['snapshot_unexpected_changes'] = [item['source'] for item in inventory['files']
        if item['source'] not in expected_changed and hashlib.sha256((snapshot / item['source']).read_bytes()).hexdigest() != item['sha256']]
    # Preserve compact proof reports in addition to full suite logs. Native binaries remain in scratch.
    artifacts = results / 'reports'
    for name in ['families', 'lifecycle', 'source-operations', 'specializations']:
        base = workspace / name
        if not base.exists():
            continue
        for path in base.rglob('*'):
            if path.is_file() and (path.name in {'report.json', 'results.json', 'accepted.json'} or path.suffix == '.log'):
                target = artifacts / name / path.relative_to(base)
                target.parent.mkdir(parents=True, exist_ok=True)
                shutil.copy2(path, target)
    report['completed_utc'] = datetime.datetime.now(datetime.timezone.utc).isoformat()
    report['passed'] = all(g['status'] == 'passed' for g in report['gates']) and not changed and not report['snapshot_unexpected_changes'] and report['integrity']['source_git_status'] == '' and report['integrity']['runtime_git_status'] == '' and report['integrity']['source_head'] == inventory['source_revision'] and report['integrity']['runtime_head'] == RUNTIME_REVISION
    write(results / 'summary.json', report)
    raise SystemExit(0 if report['passed'] else 1)


if __name__ == '__main__':
    main()
