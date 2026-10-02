#!/usr/bin/env python3
"""Build a candidate native compiler and compare its fixture behavior with PHP.

The selected checkout is explicitly a candidate, not an update to the verified pin.
Each invocation reads request.txt from its own working directory.
"""
import argparse
import hashlib
import json
from pathlib import Path
import re
import shutil
import time

from test_runner import CommandRunner, DEFAULT_JOBS, Task, positive_jobs, run_tasks

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
STAGES = ['01_prepare_inputs', '02_tokenize', '03_parse', '04_analyze', '05_backend', '06_native', 'compiler']


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
    parser.add_argument('--no-stan', action='store_true', help='Explicitly isolate native validation from STAN; record analysis as skipped')
    parser.add_argument('--types-only', action='store_true',
                        help='Run frontend/C++ type validation while explicitly skipping parked LLVM proofs')
    parser.add_argument('--jobs', type=positive_jobs, default=DEFAULT_JOBS,
                        help='Maximum concurrent fixture tasks (default: 12)')
    parser.add_argument('--resume', action='store_true', help='Reuse this proof workspace and native objects; retain each attempt log directory')
    args = parser.parse_args()
    target, results = args.target_checkout.resolve(), args.results.resolve()
    results.mkdir(parents=True, exist_ok=args.resume)
    (results / 'summary.json').unlink(missing_ok=True)
    logs = results / 'logs'
    attempt = 1
    while logs.exists():
        attempt += 1
        logs = results / f'logs-{attempt}'
    logs.mkdir()
    started = time.monotonic()

    runner = CommandRunner(logs, ROOT, journals=[results / 'commands.json'])
    run = runner.run

    source = results / 'source'
    source.mkdir(exist_ok=args.resume)
    for stage in STAGES:
        shutil.copytree(APP / stage, source / stage, dirs_exist_ok=args.resume)
    request = results / 'request.txt'
    recovery = results / 'recovery'
    recovery.mkdir(exist_ok=args.resume)
    (recovery / 'main.phs').write_text('return 0;')
    pipeline = results / 'pipeline'
    pipeline.mkdir(exist_ok=args.resume)
    (pipeline / 'a.phs').write_text('function ready(): int { return 1; }')
    (pipeline / 'b.phs').write_text('$')
    driver = (APP / 'tests/native_driver.php.in').read_text()
    shutil.copyfile(APP / 'tests/s2s_proof.php', source / 's2s_proof.php')
    driver = (driver.replace('__REQUEST_FILE__', php_literal('request.txt'))
              .replace('__RECOVERY_DIR__', php_literal(recovery))
              .replace('__PIPELINE_DIR__', php_literal(pipeline))
              .replace('__RUN_LLVM_PROOFS__', 'false' if args.types_only else 'true'))
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
    config_path = project / 'prism.json'
    if not config_path.exists():
        run('init', ['php', cli, 'init', '--php-profile=strict'], project)
    config = json.loads(config_path.read_text())
    config['build']['cxx'] = 'clang++-18'
    config['runtime']['modules'] = ['compiler', 'filesystem', 'tasks']
    config_path.write_text(json.dumps(config, indent=2) + '\n')
    analysis_options = ['--no-stan'] if args.no_stan else []
    print('Building native compiler ' + ('without STAN...' if args.no_stan else 'with normal STAN...'), flush=True)
    run('native-build', ['php', cli, 'build', '--build-runtime', *analysis_options], project, timeout=600)
    executable = project / '.prism/build/main'
    # Ordinary host tests independently assert the algorithms and expected sample exits.
    host_suites = ['storage', 'tokenizer', 'ast']
    if args.types_only:
        host_suites += ['type_catalog', 'constructed_type']
    else:
        host_suites += ['model', 'llvm_text']
    run_tasks([Task('php-' + suite, lambda suite=suite:
                    run('php-' + suite, ['php', APP / 'tests' / (suite + '.php')]))
               for suite in host_suites], args.jobs)
    fixtures = results / 'fixtures'
    fixtures.mkdir(exist_ok=args.resume)
    if not args.types_only:
        def emit_suite(suite):
            folder = fixtures / suite
            folder.mkdir(exist_ok=args.resume)
            run('php-' + suite, ['php', APP / 'tests' / (suite + '.php'), folder])
        run_tasks([Task(suite, lambda suite=suite: emit_suite(suite))
                   for suite in ['llvm', 'calls']], args.jobs)
    expected_exits = {}
    cases = []
    if not args.types_only:
        for item in json.loads((fixtures / 'llvm/executions.json').read_text()):
            expected_exits['llvm/' + Path(item['path']).stem] = item['exit_code']
        call_log = (logs / 'php-calls.stdout').read_text()
        for name, code in re.findall(r'^(\w+): dependencies verified, native exit (\d+)$', call_log, re.M):
            expected_exits['calls/' + name] = int(code)
        expected_exits['sample/01_base'] = 9
        cases = [(suite + '/' + path.name, path) for suite in ['llvm', 'calls']
                 for path in sorted((fixtures / suite).iterdir()) if path.is_dir()]
        cases.append(('sample/01_base', APP / 'tests/samples/01_base'))
    php_code = 'require ' + php_literal(APP / 'boot.php') + '; require ' + php_literal(APP / 'tests/s2s_proof.php') + '; require ' + php_literal(source / 'main.php') + ';'
    # Compile generated C++ from both host implementations, independently of LLVM parity.
    request.write_text('s2s-proof')
    expected_cpp = run('s2s-host', ['php', '-r', php_code], cwd=results)
    native_cpp = run('s2s-native', [executable], cwd=results)
    if native_cpp != expected_cpp:
        raise RuntimeError('Native C++ emission differs from PHP')
    (results / 's2s.cpp').write_bytes(native_cpp)
    run('s2s-clang', ['clang++-18', '-std=c++20', '-I', ROOT / 'runtime/include', results / 's2s.cpp', '-o', results / 's2s-program'])
    run('s2s-execute', [results / 's2s-program'], expected=10)
    request.write_text('types-proof')
    expected_type_cpp = run('types-host', ['php', '-r', php_code], cwd=results)
    native_type_cpp = run('types-native', [executable], cwd=results)
    if native_type_cpp != expected_type_cpp:
        raise RuntimeError('Native constructed-type C++ emission differs from PHP')
    (results / 'types.cpp').write_bytes(native_type_cpp)
    run('types-clang', ['clang++-18', '-std=c++20', '-I', ROOT / 'runtime/include',
                        results / 'types.cpp', '-o', results / 'types-program'])
    run('types-execute', [results / 'types-program'])
    # Reuse every valid authored S2S case instead of maintaining native-only fixture subsets.
    s2s_fixtures = results / 's2s-fixtures'
    s2s_fixtures.mkdir(exist_ok=args.resume)
    run('php-s2s-suite', ['php', APP / 'tests/s2s.php', s2s_fixtures])
    programs = json.loads((s2s_fixtures / 'programs.json').read_text())
    valid_cases = [(name, text, code) for name, (text, code) in programs['valid'].items()]
    declaration_case_count = sum(name.startswith(('function_', 'struct_', 'integer_', 'field_'))
                                 for name, _, _ in valid_cases)
    def validate_source(name, text, code):
        folder = results / 's2s-programs' / name
        folder.mkdir(parents=True, exist_ok=args.resume)
        (folder / 'main.phs').write_text(text)
        (folder / 'request.txt').write_text('s2s:' + str(folder))
        host_output = run(name + '-s2s-host', ['php', '-r', php_code], cwd=folder)
        native_output = run(name + '-s2s-native', [executable], cwd=folder)
        if native_output != host_output or native_output.startswith(b'ERROR\n'):
            raise RuntimeError(name + ': native S2S output differs from PHP or was rejected')
        generated = native_output.decode()
        if name.startswith('field_'):
            alias = name[len('field_'):]
            native_type = 'uint8' if alias == 'byte' else alias
            generated += ('\nstatic_assert(std::is_same_v<decltype(record_Item{}.field_value), '
                          'scpp::int_t<std::' + native_type + '_t>>);\n')
        if name.startswith('float_form_'):
            prefix = '$a = '
            if not text.startswith(prefix) or not text.endswith(';'):
                raise RuntimeError(name + ': float spelling fixture has an unexpected source shape')
            spelling = text[len(prefix):-1]
            if 'static_cast<scpp::float_t>(' + spelling + ')' not in generated:
                raise RuntimeError(name + ': native compiler changed float precision/spelling')
            probe = ('\tstatic_assert(std::is_same_v<decltype(local_a), scpp::float_t>);\n'
                     '\tif (local_a.native_value() != ' + spelling + ') { return 91; }\n')
            generated_with_probe = generated.replace('\treturn 0;', probe + '\treturn 0;')
            if generated_with_probe == generated:
                raise RuntimeError(name + ': float spelling probe could not find the entry return')
            generated = generated_with_probe
        (folder / 'main.cpp').write_text(generated)
        run(name + '-s2s-clang', ['clang++-18', '-std=c++20', '-I', ROOT / 'runtime/include',
                                 folder / 'main.cpp', '-o', folder / 'program'])
        run(name + '-s2s-execute', [folder / 'program'], expected=code)
        return generated.encode(), code

    source_outcomes = run_tasks([Task(name, lambda name=name, text=text, code=code:
                                      validate_source(name, text, code))
                                for name, text, code in valid_cases], args.jobs)
    executed_cpp = {row.value for row in source_outcomes}
    float_spelling_assertions = sum(name.startswith('float_form_') for name, _, _ in valid_cases)
    # These instrumented programs assert exact values, string bytes and error/store
    # behavior. Their base compiler output has passed PHP/native parity above.
    def validate_probe(item):
        probe_source = Path(item['path'])
        name = probe_source.stem
        executable_probe = results / ('probe-' + name)
        run(name + '-probe-clang', ['clang++-18', '-std=c++20', '-I', ROOT / 'runtime/include',
                                  probe_source, '-o', executable_probe])
        run(name + '-probe-execute', [executable_probe], expected=item['exit_code'])

    probe_cases = [item for item in json.loads((s2s_fixtures / 'executions.json').read_text())
                   if (Path(item['path']).read_bytes(), item['exit_code']) not in executed_cpp]
    run_tasks([Task('probe-' + Path(item['path']).stem, lambda item=item: validate_probe(item))
               for item in probe_cases], args.jobs)
    probe_executions = len(probe_cases)
    print(f'{probe_executions} supplementary instrumented programs passed', flush=True)
    rejection_cases = programs['rejected'] + [
        'struct Loop { Loop $next; }', 'struct A { B $b; } struct B { A $a; }',
        '$s = "abc"; $s[];', '$s = "abc"; $s[0];',
        '$s = "abc"; $s[] = "d";', '$s = "abc"; $s[0] = "d";']
    def validate_rejection(index, text):
        name = 's2s-rejected-' + str(index)
        folder = results / 'declaration-programs' / name
        folder.mkdir(parents=True, exist_ok=args.resume)
        (folder / 'main.phs').write_text(text)
        (folder / 'request.txt').write_text('s2s:' + str(folder))
        host_output = run(name + '-host', ['php', '-r', php_code], cwd=folder)
        native_output = run(name + '-native', [executable], cwd=folder)
        if native_output != host_output or not native_output.startswith(b'ERROR\n'):
            raise RuntimeError(name + ': native rejection/recovery differs from PHP')
    run_tasks([Task('rejected-' + str(index), lambda index=index, text=text:
                    validate_rejection(index, text))
               for index, text in enumerate(rejection_cases)], args.jobs)
    print(f'{len(valid_cases)} valid S2S executions, including {float_spelling_assertions} float spelling assertions, '
          f'and {len(rejection_cases)} S2S rejections passed', flush=True)
    def validate_llvm(name, path):
        key = name.replace('/', '-')
        invocation = results / 'requests' / key
        invocation.mkdir(parents=True, exist_ok=args.resume)
        (invocation / 'request.txt').write_text(str(path))
        php_trace = run(key + '-php', ['php', '-r', php_code], cwd=invocation)
        native_trace = run(key + '-native', [executable], cwd=invocation)
        if native_trace != php_trace:
            raise RuntimeError(f'{name}: PHP/native trace mismatch; see {logs}')
        valid = name in expected_exits
        if valid:
            folder = results / 'programs' / key
            folder.mkdir(parents=True, exist_ok=args.resume)
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
        return dict(name=name, valid=valid, passed=True)

    outcomes = [row.value for row in run_tasks(
        [Task(name, lambda name=name, path=path: validate_llvm(name, path)) for name, path in cases], args.jobs)]
    run('incremental-build', ['php', cli, 'build', *analysis_options], project)
    analysis = {'skipped': True}
    if not args.no_stan:
        stan = json.loads((project / '.prism/cache/stan_status.json').read_text())
        analysis = {key: stan[key] for key in ['compile_error_count', 'stan_error_count', 'stan_warning_count', 'stan_notice_count']}
    summary = dict(jobs=args.jobs, wall_seconds=round(time.monotonic() - started, 3), analysis=analysis, passed=True, executable=str(executable), request_file=str(request),
                   validation_scope='types-and-s2s' if args.types_only else 'full',
                   parked_llvm_validation='skipped' if args.types_only else 'included',
                   portable_type_proof=True,
                   s2s_valid_executions=len(valid_cases), s2s_float_spelling_assertions=float_spelling_assertions,
                   s2s_supplementary_probe_executions=probe_executions,
                   s2s_declaration_executions=declaration_case_count,
                   s2s_rejections=len(rejection_cases), cases=len(outcomes), valid=sum(x['valid'] for x in outcomes),
                   rejected=sum(not x['valid'] for x in outcomes),
                   repeated_compile_and_recovery=True, outcomes=outcomes)
    (results / 'summary.json').write_text(json.dumps(summary, indent=2) + '\n')
    print(json.dumps({k: v for k, v in summary.items() if k != 'outcomes'}), flush=True)


if __name__ == '__main__':
    main()
