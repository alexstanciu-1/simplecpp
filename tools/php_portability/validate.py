"""Validate portability tooling and selected native capabilities during the compiler rewrite.

An empty compiler ready set is explicit; framework success is not compiler coverage.
"""
import argparse
import json
from pathlib import Path
import shutil
import subprocess
import sys
import time

ROOT = Path(__file__).resolve().parents[2]
TOOLS = ROOT / 'tools/php_portability'
TESTS = ROOT / 'tests/portability'
NATIVE = {
    'os': TESTS / 'os_native.py',
    'compiler': TESTS / 'compiler_context/run.py',
    'methods': TESTS / 'method_signatures.py',
    'static': TESTS / 'static_properties.py',
    'nullable-parameters': TESTS / 'nullable_parameters.py',
    'required-fields': TESTS / 'required_fields.py',
    'snapshots': TESTS / 'collection_snapshots.py',
    'containers': TESTS / 'container_annotations.py',
    'iteration': TESTS / 'map_iteration.py',
    'returns': TESTS / 'container_returns.py',
    'utf8': TESTS / 'utf8.py',
    'traits': TESTS / 'traits.py',
    'records': TESTS / 'value_records.py',
}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    parser.add_argument('--native', choices=NATIVE, action='append', default=[],
                        help='Run a selected native proof after fast checks; repeat to select more.')
    parser.add_argument('--target-checkout', type=Path)
    args = parser.parse_args()
    if bool(args.native) != bool(args.target_checkout):
        parser.error('--native and --target-checkout must be supplied together')
    results = args.results.resolve()
    # Results contain staged copies; never put them inside authored inputs or proof discovery.
    for owner in [ROOT / 'compiler', ROOT / 'tools', ROOT / 'tests', ROOT / '.agents']:
        if results == owner or owner in results.parents:
            parser.error('Results must be outside compiler/tools/tests/.agents source trees')
    if results.exists():
        parser.error('Use a fresh results directory')
    results.mkdir(parents=True)
    report = {'status': 'running', 'scope': 'portability tooling and active rewrite readiness',
              'native_requested': list(dict.fromkeys(args.native)), 'steps': []}

    def save():
        (results / 'summary.json').write_text(json.dumps(report, indent=2) + '\n')

    def run(label, command):
        step = {'name': label, 'command': list(map(str, command)), 'status': 'running'}
        report['steps'].append(step)
        save()
        print(label, flush=True)
        start = time.monotonic()
        with (results / (label + '.stdout.log')).open('w') as stdout, \
             (results / (label + '.stderr.log')).open('w') as stderr:
            process = subprocess.run(step['command'], cwd=ROOT, stdout=stdout, stderr=stderr)
        step.update(status='passed' if process.returncode == 0 else 'failed',
                    exit_code=process.returncode, seconds=round(time.monotonic() - start, 3))
        save()
        if process.returncode:
            raise RuntimeError(f'{label} failed; see {results / (label + ".stderr.log")}')
        return (results / (label + '.stdout.log')).read_text()

    try:
        save()
        shutil.copy2(__file__, results / 'runner.py')
        target = json.loads((ROOT / 'compiler/tools/portability_target.json').read_text())
        report['configured_target'] = target
        if args.native:
            checkout = args.target_checkout.resolve()
            revision = run('target-revision', ['git', '-C', checkout, 'rev-parse', 'HEAD']).strip()
            dirty = run('target-status', ['git', '-C', checkout, 'status', '--porcelain'])
            if revision != target['verified_commit'] or dirty:
                raise RuntimeError('Native target must be the clean configured revision')
            report['verified_target_revision'] = revision
        selection = json.loads((ROOT / 'compiler/portability.json').read_text())
        if selection.get('schema_version') != 1 or selection.get('source_root') != '.':
            raise RuntimeError('Unsupported compiler portability source manifest')
        names = selection['files']
        if not isinstance(names, list) or len(names) != len(set(names)):
            raise RuntimeError('Expected a unique source file list')
        report['compiler_ready_files'] = len(names)
        report['compiler_status'] = 'not_started' if not names else 'proof_pending'
        shutil.copy2(ROOT / 'compiler/portability.json', results / 'source-manifest.json')
        save()
        if names:
            run('compiler-php', [sys.executable, ROOT / 'compiler/tests/run.py', '--results', results / 'compiler-php'])
            report['compiler_status'] = 'php_proved'
        elif 'compiler' in report['native_requested']:
            raise RuntimeError('Compiler rewrite has no ready component; historical 39-file coverage is archived')
        run('global-functions', ['php', ROOT / 'tools/php_portability/generate_global_functions.php', '--check'])
        run('process-launch-ack', [sys.executable, TESTS / 'process_launch_ack.py', '--results', results / 'process-launch-ack'])
        run('collections-php', ['php', TESTS / 'collections_php.php'])
        run('required-fields-php', [sys.executable, TESTS / 'required_fields.py', '--results', results / 'required-fields-php'])
        run('nullable-parameters-php', [sys.executable, TESTS / 'nullable_parameters.py', '--results', results / 'nullable-parameters-php'])
        run('static-properties-php', [sys.executable, TESTS / 'static_properties.py', '--results', results / 'static-properties-php'])
        run('value-records-php', [sys.executable, TESTS / 'value_records.py', '--results', results / 'value-records-php'])
        for label, script in [('foundation', 'run.py'), ('check-regressions', 'check.py'),
                              ('prologues', 'prologues.py'),
                              ('native-framework-install', 'native_runtime.py')]:
            run(label, [sys.executable, TESTS / script])
        for name in report['native_requested']:
            run('native-' + name, [sys.executable, NATIVE[name], '--target-checkout', checkout,
                                  '--results', results / ('proof-' + name)])
        if args.native and run('target-status-after', ['git', '-C', checkout, 'status', '--porcelain']):
            raise RuntimeError('Native target changed during validation')
        if 'compiler' in report['native_requested']:
            report['compiler_status'] = 'php_native_proved'
        report.update(status='passed', native_proved=report['native_requested'])
        save()
        print('Validation passed; compiler ready files: ' + str(len(names)) + '; compiler status: ' + report['compiler_status'] + '; native proofs: ' + (', '.join(report['native_requested']) or 'not requested'))
        return 0
    except (Exception, KeyboardInterrupt) as error:
        report.update(status='failed', error=str(error) or 'interrupted')
        save()
        print(report['error'], file=sys.stderr)
        return 1


if __name__ == '__main__':
    sys.exit(main())
