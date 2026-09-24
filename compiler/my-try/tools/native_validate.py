#!/usr/bin/env python3
"""Build a candidate native compiler and compare its fixture behavior with PHP.

The selected checkout is explicitly a candidate, not an update to the verified pin.
The executable reads the module directory from RESULTS/request.txt (serial test entry).
"""
import argparse
import hashlib
import json
from pathlib import Path
import re
import shutil
import subprocess
import time

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
STAGES = ['01_prepare_inputs', '02_tokenize', '03_parse', '04_analyze', '05_llvm', '06_native', 'compile']


def php_literal(text):
    return "'" + str(text).replace('\\', '\\\\').replace("'", "\\'") + "'"


def modules(trace):
    if not trace.startswith(b'OK\n'):
        raise ValueError('Expected successful module trace')
    position = 3
    parts = []
    while position < len(trace):
        end = trace.index(b':', position)
        length = int(trace[position:end])
        position = end + 1
        parts.append(trace[position:position + length])
        position += length
    if len(parts) % 2:
        raise ValueError('Incomplete module frame')
    return [(parts[i].decode(), parts[i + 1]) for i in range(0, len(parts), 2)]


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--target-checkout', required=True, type=Path)
    parser.add_argument('--candidate-revision', required=True)
    parser.add_argument('--results', required=True, type=Path)
    args = parser.parse_args()
    target, results = args.target_checkout.resolve(), args.results.resolve()
    results.mkdir(parents=True, exist_ok=False)
    logs = results / 'logs'
    logs.mkdir()
    commands = []

    def run(name, command, cwd=ROOT, expected=0, timeout=240):
        start = time.monotonic()
        process = subprocess.run([str(x) for x in command], cwd=cwd, capture_output=True, timeout=timeout)
        (logs / (name + '.stdout')).write_bytes(process.stdout)
        (logs / (name + '.stderr')).write_bytes(process.stderr)
        commands.append(dict(name=name, command=[str(x) for x in command], cwd=str(cwd),
                             seconds=round(time.monotonic() - start, 3), code=process.returncode))
        (results / 'commands.json').write_text(json.dumps(commands, indent=2) + '\n')
        if process.returncode != expected:
            raise RuntimeError(f'{name}: expected exit {expected}, got {process.returncode}; see {logs}')
        return process.stdout

    source = results / 'source'
    source.mkdir()
    for stage in STAGES:
        shutil.copytree(APP / stage, source / stage)
    request = results / 'request.txt'
    recovery = results / 'recovery'
    recovery.mkdir()
    (recovery / 'main.phs').write_text('return 0;')
    driver = (APP / 'tests/native_driver.php.in').read_text()
    driver = driver.replace('__REQUEST_FILE__', php_literal(request)).replace('__RECOVERY_DIR__', php_literal(recovery))
    (source / 'main.php').write_text(driver)
    hashes = {str(p.relative_to(source)): hashlib.sha256(p.read_bytes()).hexdigest() for p in source.rglob('*.php')}
    (results / 'source_hashes.json').write_text(json.dumps(hashes, indent=2) + '\n')
    target_hashes = {}
    for folder in ['bin', 'generators/php', 'runtime']:
        for p in (target / folder).rglob('*'):
            if p.is_file() and p.suffix in {'.php', '.hpp', '.cpp', '.json'}:
                target_hashes[str(p.relative_to(target))] = hashlib.sha256(p.read_bytes()).hexdigest()
    (results / 'candidate.json').write_text(json.dumps(dict(revision=args.candidate_revision,
        checkout=str(target), files=target_hashes), indent=2) + '\n')
    project = results / 'phpp'
    run('convert', ['php', ROOT / 'tools/php_portability/convert.php', source, project])
    run('framework', ['php', ROOT / 'tools/php_portability/install_native_runtime.php', project, '--filesystem'])
    cli = target / 'bin/scpp.php'
    run('init', ['php', cli, 'init', '--php-profile=strict'], project)
    config_path = project / 'prism.json'
    config = json.loads(config_path.read_text())
    config['build']['cxx'] = 'clang++-18'
    config['runtime']['modules'] = ['compiler', 'filesystem']
    config_path.write_text(json.dumps(config, indent=2) + '\n')
    print('Building native compiler with normal STAN...', flush=True)
    run('native-build', ['php', cli, 'build', '--build-runtime'], project, timeout=600)
    executable = project / '.prism/build/main'
    # Ordinary host tests independently assert the algorithms and expected sample exits.
    for suite in ['storage', 'tokenizer', 'ast', 'model', 'llvm_text']:
        run('php-' + suite, ['php', APP / 'tests' / (suite + '.php')])
    fixtures = results / 'fixtures'
    fixtures.mkdir()
    for suite in ['llvm', 'calls']:
        folder = fixtures / suite
        folder.mkdir()
        run('php-' + suite, ['php', APP / 'tests' / (suite + '.php'), folder])
    expected_exits = {}
    for item in json.loads((fixtures / 'llvm/executions.json').read_text()):
        expected_exits['llvm/' + Path(item['path']).stem] = item['exit_code']
    call_log = (logs / 'php-calls.stdout').read_text()
    for name, code in re.findall(r'^(\w+): dependencies verified, native exit (\d+)$', call_log, re.M):
        expected_exits['calls/' + name] = int(code)
    expected_exits['sample/01_base'] = 9
    cases = [(suite + '/' + path.name, path) for suite in ['llvm', 'calls']
             for path in sorted((fixtures / suite).iterdir()) if path.is_dir()]
    cases.append(('sample/01_base', APP / 'samples/01_base'))
    php_code = 'require ' + php_literal(APP / 'boot.php') + '; require ' + php_literal(source / 'main.php') + ';'
    outcomes = []
    for name, path in cases:
        request.write_text(str(path))
        key = name.replace('/', '-')
        php_trace = run(key + '-php', ['php', '-r', php_code])
        native_trace = run(key + '-native', [executable])
        if native_trace != php_trace:
            raise RuntimeError(f'{name}: PHP/native trace mismatch; see {logs}')
        valid = name in expected_exits
        if valid:
            folder = results / 'programs' / key
            folder.mkdir(parents=True)
            files = []
            for filename, llvm in modules(native_trace):
                if Path(filename).name != filename or not filename.endswith('.ll'):
                    raise RuntimeError('Unexpected output filename: ' + filename)
                output = folder / filename
                output.write_bytes(llvm)
                files.append(output)
            run(key + '-link', ['clang-18', *files, '-o', folder / 'program'])
            run(key + '-execute', [folder / 'program'], expected=expected_exits[name])
        elif not native_trace.startswith(b'ERROR\n'):
            raise RuntimeError(name + ': invalid input was not rejected')
        outcomes.append(dict(name=name, valid=valid, passed=True))
        if len(outcomes) % 20 == 0:
            print(f'{len(outcomes)}/{len(cases)} native comparisons passed', flush=True)
    run('incremental-build', ['php', cli, 'build'], project)
    stan = json.loads((project / '.prism/cache/stan_status.json').read_text())
    analysis = {key: stan[key] for key in ['compile_error_count', 'stan_error_count', 'stan_warning_count', 'stan_notice_count']}
    summary = dict(analysis=analysis, passed=True, executable=str(executable), request_file=str(request),
                   cases=len(outcomes), valid=sum(x['valid'] for x in outcomes),
                   rejected=sum(not x['valid'] for x in outcomes),
                   repeated_compile_and_recovery=True, outcomes=outcomes)
    (results / 'summary.json').write_text(json.dumps(summary, indent=2) + '\n')
    print(json.dumps({k: v for k, v in summary.items() if k != 'outcomes'}), flush=True)


if __name__ == '__main__':
    main()
