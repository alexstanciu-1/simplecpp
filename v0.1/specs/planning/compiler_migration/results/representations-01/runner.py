#!/usr/bin/env python3
"""Probe the pinned PHP++ target without changing compiler representations."""
import argparse
import hashlib
import json
from pathlib import Path
import shutil
import subprocess
import tempfile
import time

ROOT = Path(__file__).resolve().parents[2]
FIXTURES = ROOT / 'tests/portability/native_representation'
EXPECTED = {
    'scalar_control': '7:9\n',
    'vector_record_field': '7:9\n',
    'hash_record_field': '7:9\n',
    'string_field': 'before:after\n',
    'vector_string_field': 'before:after\n',
    'hash_string_field': 'before:after\n',
    'hash_keyed_field': 'before:after\n',
    'class_field': '9:9\n9:11\n',
}


def git(path, *args):
    return subprocess.check_output(['git', *args], cwd=path, text=True).strip()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('results', type=Path)
    options = parser.parse_args()
    results = options.results.resolve()
    if results.exists():
        raise SystemExit('Use a fresh evidence directory.')
    config = json.loads((ROOT / 'compiler/tools/toolchain.json').read_text())
    cli = (ROOT / 'compiler' / config['scpp']).resolve()
    toolchain = cli.parent.parent
    revision = git(toolchain, 'rev-parse', 'HEAD')
    assert revision == config['verified_commit']
    assert git(toolchain, 'status', '--porcelain') == ''
    results.mkdir(parents=True)
    shutil.copy2(__file__, results / 'runner.py')
    workspace = Path(tempfile.mkdtemp(prefix='scpp-representation-probes-'))
    report = {'toolchain': str(toolchain), 'revision': revision, 'workspace': str(workspace),
              'cxx': 'clang++-18', 'profile': 'strict', 'probes': []}

    def save():
        (results / 'summary.json').write_text(json.dumps(report, indent=2) + '\n')

    def invoke(name, stage, args, cwd):
        started = time.monotonic()
        proc = subprocess.run(['php', str(cli), *args], cwd=cwd, text=True, capture_output=True)
        prefix = name + '.' + stage
        (results / (prefix + '.stdout.log')).write_text(proc.stdout)
        (results / (prefix + '.stderr.log')).write_text(proc.stderr)
        return proc, {'command': ['php', str(cli), *args], 'cwd': str(cwd), 'exit_code': proc.returncode,
                      'elapsed_seconds': round(time.monotonic() - started, 3), 'stdout': prefix + '.stdout.log', 'stderr': prefix + '.stderr.log'}

    for name, expected in EXPECTED.items():
        print(name + ' started', flush=True)
        project = workspace / name
        project.mkdir()
        fixture = FIXTURES / (name + '.phs')
        row = {'name': name, 'fixture': str(fixture.relative_to(ROOT)), 'sha256': hashlib.sha256(fixture.read_bytes()).hexdigest(), 'expected_stdout': expected, 'status': 'running', 'commands': []}
        report['probes'].append(row)
        save()
        proc, record = invoke(name, 'init', ['init', '--php-profile=strict'], project)
        row['commands'].append(record)
        if proc.returncode != 0:
            row['status'] = 'setup_failed'
            save()
            continue
        cfg = json.loads((project / 'prism.json').read_text())
        cfg['runtime']['modules'] = []
        cfg['build']['cxx'] = 'clang++-18'
        (project / 'prism.json').write_text(json.dumps(cfg, indent=2) + '\n')
        shutil.copy2(project / 'prism.json', results / (name + '.config.json'))
        shutil.copy2(fixture, project / 'main.phs')
        proc, record = invoke(name, 'run', ['run'], project)
        row['commands'].append(record)
        row['status'] = 'passed' if proc.returncode == 0 and proc.stdout.endswith(expected) else 'failed'
        if proc.returncode != 0:
            # Legacy STAN is not the compiler migration authority. Distinguish its
            # rejection from generator/native support, preserving both outcomes.
            proc, record = invoke(name, 'no-stan', ['run', '--no-stan'], project)
            row['commands'].append(record)
            if proc.returncode == 0 and proc.stdout.endswith(expected):
                row['status'] = 'passed_without_stan'
            for diag in ['last_error.json', 'last_run.json']:
                path = project / '.prism' / diag
                if path.exists():
                    shutil.copy2(path, results / (name + '.' + diag))
        save()
        print(name + ' ' + row['status'], flush=True)
    report['toolchain_status_after'] = git(toolchain, 'status', '--porcelain')
    report['toolchain_revision_after'] = git(toolchain, 'rev-parse', 'HEAD')
    report['passed'] = all(p['status'] in {'passed', 'passed_without_stan'} for p in report['probes']) and report['toolchain_status_after'] == '' and report['toolchain_revision_after'] == revision
    save()
    raise SystemExit(0 if report['passed'] else 1)


if __name__ == '__main__':
    main()
