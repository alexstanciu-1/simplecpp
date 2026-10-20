#!/usr/bin/env python3
"""One O0 prelowering acceptance workflow, composed from the existing proof owners."""
import argparse
import hashlib
import json
from pathlib import Path
import sys
import time

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks

# These remain independently runnable proofs. Run suites sequentially: each owns
# its existing 12-slot pool, and must not become a nested task pool here.
SUITES = (
    ('contracts', 'prelower_contracts.py', ()),
    ('public-options', 'prelower_null_output.py', ()),
    ('provider-boundary', 'prelower_native_boundary.py', ()),
    ('headers-and-counters', 'prelower_header_boundaries.py', ()),
    ('source-lifecycle', 'prelower_source_lifecycle.py', ()),
    ('layout-cache', 'prelower_layout.py', ('--o0-only',)),
    ('accounting', 'prelower_accounting_acceptance.py', ()),
)
METADATA = ('runtime_inventory.php', 'runtime_type_bindings.php', 'runtime_protocols.php')


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    out = parser.parse_args().results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    phases = [dict(name=name, status='pending') for name in ['metadata', *[s[0] for s in SUITES]]]
    report = dict(format='scpp-prelower-acceptance-v1', status='running', phases=phases,
                  optimization='O0', native_debug=False, strict_application_pch=False,
                  ordinary_pch_control=True,
                  scope='PHP-host/compiler-output correctness and closure; not a native compiler rebuild or timing benchmark')

    def save():
        temporary = out / 'result.json.tmp'
        temporary.write_text(json.dumps(report, indent=2) + '\n')
        temporary.replace(out / 'result.json')

    def phase(row, action):
        row['status'] = 'running'
        save()
        print('Running ' + row['name'], flush=True)
        started = time.monotonic()
        try:
            action()
            row['status'] = 'passed'
        except Exception as error:
            row['status'] = 'failed'
            row['error'] = f'{type(error).__name__}: {error}'
        row['seconds'] = round(time.monotonic() - started, 3)
        save()
        print(row['name'] + ': ' + row['status'].upper(), flush=True)

    save()
    try:
        with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
            report['revision'] = runner.run('revision', ['git', 'rev-parse', 'HEAD']).decode().strip()
            report['worktree_status'] = runner.run('worktree', ['git', 'status', '--porcelain']).decode()
            report['clang'] = runner.run('clang-version', ['/usr/bin/clang++', '--version']).decode()
            report['php'] = runner.run('php-version', ['php', '--version'], cli_reason='Toolchain identity').decode()
            sources = [Path(__file__), *[APP / 'tests' / s[1] for s in SUITES],
                       *[APP / 'tests' / name for name in METADATA]]
            report['entrypoint_sha256'] = {str(p.relative_to(ROOT)): hashlib.sha256(p.read_bytes()).hexdigest()
                                          for p in sources}
            phase(phases[0], lambda: run_tasks([
                Task(name, lambda name=name: runner.run(name, ['php', APP / 'tests' / name]))
                for name in METADATA]))
            for row, (name, script, options) in zip(phases[1:], SUITES):
                row['results'] = name
                phase(row, lambda name=name, script=script, options=options: runner.run(name,
                    [sys.executable, APP / 'tests' / script, '--results', out / name, *options], timeout=1800))
            if phases[-1]['status'] == 'passed':
                accounting = json.loads((out / 'accounting/result.json').read_text())
                report['accounting'] = {key: accounting[key] for key in (
                    'behavior_identical', 'checks_passed', 'checks_failed', 'application_units',
                    'template_declarations', 'instantiations')}
    except BaseException as error:
        report['error'] = f'{type(error).__name__}: {error}'
        raise
    finally:
        statuses = [row['status'] for row in phases]
        report['status'] = ('incomplete' if 'error' in report else
                            'passed' if all(s == 'passed' for s in statuses)
                            else 'failed' if 'failed' in statuses else 'incomplete')
        save()
    print('Prelowering acceptance: ' + report['status'].upper(), flush=True)
    return 0 if report['status'] == 'passed' else 1


if __name__ == '__main__':
    sys.exit(main())
