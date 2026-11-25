"""Validate the current portability-ready compiler source set and selected native proofs.

Host orchestration only: this does not resume migration or define conversion rules.
"""
import argparse
import hashlib
import importlib.util
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
    'snapshots': TESTS / 'collection_snapshots.py',
    'containers': TESTS / 'container_annotations.py',
    'iteration': TESTS / 'map_iteration.py',
    'returns': TESTS / 'container_returns.py',
    'utf8': TESTS / 'utf8.py',
    'traits': TESTS / 'traits.py',
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
    report = {'status': 'running', 'scope': 'portability-ready source set',
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
        if not isinstance(names, list) or not names or len(names) != len(set(names)):
            raise RuntimeError('Expected a nonempty unique source file list')
        source = results / 'source'
        source.mkdir()
        hashes = {}
        for name in names:
            p = Path(name)
            if p.is_absolute() or '..' in p.parts or p.suffix != '.php':
                raise RuntimeError('Invalid portability source path: ' + name)
            original = ROOT / 'compiler' / p
            if not original.resolve().is_relative_to(ROOT / 'compiler'):
                raise RuntimeError('Source escapes compiler root: ' + name)
            destination = source / p
            destination.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(original, destination)
            hashes[name] = hashlib.sha256(destination.read_bytes()).hexdigest()
        report['source_sha256'] = hashes
        shutil.copy2(TESTS / 'compiler_context/main.php', source / 'main.php')
        shutil.copy2(ROOT / 'compiler/portability.json', results / 'source-manifest.json')
        run('check', ['php', TOOLS / 'check.php', source])
        php = run('php-behavior', ['php', '-r',
                  'foreach (array_slice($argv, 1) as $file) { require $file; }',
                  TOOLS / 'runtime/bootstrap.php', *[source / name for name in names], source / 'main.php'])
        # Reuse the existing independent expected-output owner, not a second copy.
        spec = importlib.util.spec_from_file_location('portability_context', TESTS / 'compiler_context/run.py')
        context = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(context)
        (results / 'expected.stdout').write_text(context.EXPECTED)
        if php != context.EXPECTED:
            raise RuntimeError('PHP behavior differs from the existing expected result')
        run('source-set-oracle', ['php', TESTS / 'source_set_oracle.php'])
        run('binary-syntax-oracle', ['php', TESTS / 'binary_syntax_oracle.php'])
        run('syntax-comparer-oracle', ['php', TESTS / 'syntax_comparer_oracle.php'])
        run('byte-literals-oracle', ['php', TESTS / 'byte_literals_oracle.php'])
        run('collections-php', ['php', TESTS / 'collections_php.php'])
        for label, script in [('foundation', 'run.py'), ('check-regressions', 'check.py'),
                              ('prologues', 'compiler_context/prologues.py'),
                              ('native-framework-install', 'native_runtime.py')]:
            run(label, [sys.executable, TESTS / script])
        for name in report['native_requested']:
            run('native-' + name, [sys.executable, NATIVE[name], '--target-checkout', checkout,
                                  '--results', results / ('proof-' + name)])
        if args.native and run('target-status-after', ['git', '-C', checkout, 'status', '--porcelain']):
            raise RuntimeError('Native target changed during validation')
        report.update(status='passed', native_proved=report['native_requested'])
        save()
        print('Validation passed; native proofs: ' + (', '.join(report['native_requested']) or 'not requested'))
        return 0
    except (Exception, KeyboardInterrupt) as error:
        report.update(status='failed', error=str(error) or 'interrupted')
        save()
        print(report['error'], file=sys.stderr)
        return 1


if __name__ == '__main__':
    sys.exit(main())
