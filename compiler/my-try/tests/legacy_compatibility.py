"""Run unchanged, pinned v0.1 fixtures against both compiler/runtime generations.

No operator matrices, implicit source migrations, STAN, or native compiler rebuilds.
Explicit source_variant entries run retained migration companions on both versions.
The inventory includes deferred test families; --all discovers metadata and probes.
"""
import argparse
from collections import Counter
import hashlib
import json
from pathlib import Path
import re
import shutil
import subprocess
import sys
import tarfile

sys.dont_write_bytecode = True
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, TaskFailures, run_tasks, positive_jobs
from cpp_fixture import CppFixtureCompiler
from cpp_artifacts import materialize_artifacts

MANIFEST = Path(__file__).with_name('legacy_compatibility.json')


def fixture_bytes(original, case):
    content = original.read_bytes()
    if 'nowdoc' in case:
        label = re.escape(case['nowdoc'])
        matches = re.findall(rb"<<<'" + label.encode() + rb"'\r?\n(.*?)\r?\n" +
                             label.encode() + rb"[);,]", content, re.S)
        if len(matches) != 1:
            raise ValueError('Expected exactly one named nowdoc in ' + str(original))
        content = matches[0] + b'\n'
    return content


def git(*args):
    return subprocess.check_output(['git', *args], cwd=ROOT)


def normalize(data, comparison, stream):
    if comparison.get('normalize_' + stream + '_newlines', False):
        data = data.replace(b'\r\n', b'\n').replace(b'\r', b'\n')
    if comparison.get('trim_final_' + stream + '_newline', False):
        data = data.removesuffix(b'\n')
    return data


def classify(result, comparison):
    if not result['v01']['passed']:
        return 'baseline-unproven'
    if not result['v02']['passed']:
        return 'compatibility-gap'
    for stream in ('stdout', 'stderr'):
        before = bytes.fromhex(result['v01'][stream + '_hex'])
        after = bytes.fromhex(result['v02'][stream + '_hex'])
        if normalize(before, comparison, stream) != normalize(after, comparison, stream):
            return 'behavior-difference'
    return 'compatible-proved'


def operator_matrix(path):
    return any(part in path for part in ('/php-matrix/', '/runtime-matrix/', '/operators/matrix/'))


def discover(revision, initial):
    """Metadata owns executable oracles; unannotated sources are generation probes."""
    paths = git('ls-tree', '-r', '--name-only', revision).decode().splitlines()
    available = set(paths)
    cases = {row['source']: dict(row) for row in initial}
    for path in paths:
        if not path.startswith(('tests/php/', 'tests/runtime/')) or not path.endswith('.test-info.json') or operator_matrix(path):
            continue
        metadata = json.loads(git('show', revision + ':' + path))
        runtime = path.startswith('tests/runtime/')
        source = path.removesuffix('.test-info.json') + ('.cpp' if runtime else '.phs')
        if source not in available:
            source = source.removesuffix('.phs') + '.php'
        case = cases.setdefault(source, {'source': source})
        case.update(metadata=path, profile='runtime' if runtime else (
                    'strict' if any(part.startswith('strict_') for part in path.split('/')) else 'legacy'),
                    feature=metadata.get('feature', path.split('/')[2]), lane='runtime' if runtime else 'source')
        build = metadata.get('build', {})
        if not metadata.get('enabled', True):
            case['blocked'] = 'Disabled by pinned baseline metadata'
        elif source not in available:
            case['blocked'] = 'Metadata has no matching standalone source'
        elif build.get('runtime_modules'):
            case['blocked'] = 'Optional provider/project resource adapter required: ' + ','.join(build['runtime_modules'])
        elif build.get('env') or build.get('sanitizers'):
            case['blocked'] = 'Environment/sanitizer-specific original harness required'
        if '/source/' in source or 'text_builder' in source or 'source_buffer' in source:
            case['modules'] = ['core', 'source', 'strings']
    for path in paths:
        if (path.startswith('tests/runtime/native/test_') or path.startswith('tests/runtime/source/')) and path.endswith('.cpp') and path not in cases:
            case = {'source': path, 'profile': 'runtime', 'feature': 'native-runtime', 'lane': 'runtime'}
            if any(name in path for name in ('test_curl', 'test_mysqli')):
                case['blocked'] = 'Optional provider/environment native harness required'
            cases[path] = case
    # No oracle is invented for examples, rejected fixtures, goldens or project inputs.
    for path in paths:
        if path in cases or operator_matrix(path) or not path.endswith(('.phs', '.php')):
            continue
        if not path.startswith(('tests/php/', 'tests/generators/php/', 'generators/php/samples/', 'docs/examples/')):
            continue
        if '/fixtures/' in path or '/project_samples/' in path or '/http_server/' in path:
            continue
        cases[path] = {'source': path, 'profile': 'strict' if '/strict' in path else 'legacy',
                       'feature': 'unannotated-source', 'lane': 'generation-probe'}
    return sorted(cases.values(), key=lambda row: row['source'])


def matches(actual, expected, comparison, stream):
    actual = normalize(actual, comparison, stream)
    mode = comparison.get(stream, 'exact')
    if mode == 'exact':
        return actual == normalize(expected.encode(), comparison, stream)
    if mode == 'substring_all':
        pieces = expected if isinstance(expected, list) else [expected]
        return all(normalize(piece.encode(), comparison, stream) in actual for piece in pieces)
    raise ValueError('Unsupported oracle comparison: ' + mode)


def observed_rejection(command):
    return command.get('error') is None and isinstance(command.get('code'), int) and command['code'] > 0


def native_provider_requirements(cases):
    providers = {
        'tests/runtime/native/test_process.cpp': ('modules/process/process.cpp', []),
        'tests/runtime/native/test_regex.cpp': ('modules/regex/regex.cpp', ['-lpcre2-8']),
    }
    sources, libraries = [], []
    for case in cases:
        if case['source'] in providers and not case.get('blocked'):
            source, links = providers[case['source']]
            sources.append(source)
            libraries.extend(links)
    return sources, libraries


def inventory(revision):
    paths = git('ls-tree', '-r', '--name-only', revision).decode().splitlines()
    rows = []
    for path in paths:
        if not path.startswith(('tests/', 'docs/examples/', 'generators/php/samples/')):
            continue
        if not path.endswith(('.phs', '.test-info.json', '.py', '.php', '.cpp', '.jss')):
            continue
        if operator_matrix(path):
            group = 'operator-second-pass'
        elif path.startswith('tests/.runtime/') or (path.startswith('tests/generators/') and path.endswith('.cpp')):
            group = 'generated-artifact-not-source-test'
        elif path.startswith('tests/php/') and path.endswith('.phs'):
            group = 'source-fixture-needs-selection'
        elif path.endswith('.test-info.json'):
            group = 'expectation-metadata'
        elif path.endswith('.jss'):
            group = 'other-frontend'
        elif path.startswith('tests/runtime/'):
            group = 'runtime-only-needs-separate-adapter'
        elif path.startswith('tests/tools/') and not '/fixtures/' in path:
            group = 'harness-coupled-needs-adapter'
        else:
            group = 'source-or-project-needs-oracle-and-adapter'
        rows.append({'path': path, 'disposition': group})
    return rows


def snapshot(revision, output):
    archive = output / 'baseline.tar'
    with archive.open('wb') as target:
        subprocess.run(['git', 'archive', revision, 'bin', 'generators', 'runtime',
                        'specs', 'tests', 'docs/examples'],
                       cwd=ROOT, stdout=target, check=True)
    destination = output / 'baseline'
    destination.mkdir()
    with tarfile.open(archive) as source:
        source.extractall(destination, filter='data')
    archive.unlink()
    return destination


def write_summary(output, summary):
    (output / 'summary.json').write_text(json.dumps(summary, indent=2) + '\n')
    lines = ['# v0.1 compatibility execution', '', 'Doc Status: planning', '',
             'Baseline: `' + summary['baseline'] + '`; v0.2: `' + summary['current'] + '`.', '',
             'Unchanged source; PHP-host compilers and generated native programs. '
             'Operator matrices excluded. This does not rebuild the native compiler.', '',
             '| Status | Count |', '| --- | ---: |']
    for state, count in sorted(Counter(row['status'] for row in summary['cases']).items()):
        lines.append(f'| {state} | {count} |')
    lines += ['', '| Source | Profile | Result | v0.1 stage | v0.2 stage |',
              '| --- | --- | --- | --- | --- |']
    for row in summary['cases']:
        lines.append(f"| `{row['source']}` | {row['profile']} | {row['status']} | "
                     f"{row['v01']['stage']} | {row['v02']['stage']} |")
    (output / 'report.md').write_text('\n'.join(lines) + '\n')


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', required=True, type=Path)
    parser.add_argument('--manifest', type=Path, default=MANIFEST)
    parser.add_argument('--all', action='store_true', help='Discover all metadata and standalone generation probes')
    parser.add_argument('--filter', default='', help='Substring of source path')
    parser.add_argument('--inventory-only', action='store_true')
    parser.add_argument('--jobs', type=positive_jobs, default=12)
    parser.add_argument('--cxx', default='clang++')
    args = parser.parse_args()
    manifest = json.loads(args.manifest.read_text())
    revision = git('rev-parse', manifest['baseline'] + '^{commit}').decode().strip()
    output = args.results.resolve()
    output.mkdir(parents=True, exist_ok=False)
    if args.all:
        manifest['cases'] = discover(revision, manifest['cases'])
        manifest['scope'] = 'All non-matrix metadata plus standalone generation probes; explicit blocked cases'
    selected = [row for row in manifest['cases'] if args.filter in row['source']]
    if not selected:
        parser.error('No selected cases')
    paths = [row['source'] for row in selected]
    if len(set(paths)) != len(paths):
        raise ValueError('Duplicate selected source')
    if any(operator_matrix(path) for path in paths):
        raise ValueError('Operator fixtures are reserved for the second pass')
    entries = inventory(revision)
    by_source = {case['source']: case for case in selected}
    metadata_paths = {case.get('metadata') for case in selected}
    for row in entries:
        if row['path'] in by_source:
            case = by_source[row['path']]
            row['disposition'] = 'adapter-blocked' if case.get('blocked') else case.get('lane', 'source')
            if case.get('blocked'):
                row['reason'] = case['blocked']
        elif row['path'] in metadata_paths:
            row['disposition'] = 'selected-expectation-metadata'
    (output / 'inventory.json').write_text(json.dumps(entries, indent=2) + '\n')
    (output / 'manifest.json').write_text(json.dumps(manifest, indent=2) + '\n')
    summary = {'baseline': revision, 'current': git('rev-parse', 'HEAD').decode().strip(),
               'dirty_status': git('status', '--short').decode(), 'cases': [],
               'scope': manifest['scope'], 'cxx': args.cxx,
               'inventory_counts': dict(Counter(row['disposition'] for row in entries))}
    # Hash actual source/runtime/harness bytes; HEAD alone cannot identify this overlay.
    tracked = git('ls-files', 'compiler/my-try', 'runtime').decode().splitlines()
    hashes = {path: hashlib.sha256((ROOT / path).read_bytes()).hexdigest()
              for path in tracked if (ROOT / path).is_file()}
    for path in (Path(__file__), APP / 'tests/compatibility_export.php',
                 APP / 'tests/compatibility_modules.php', args.manifest):
        hashes[str(path.relative_to(ROOT)) if path.is_relative_to(ROOT) else str(path)] = hashlib.sha256(path.read_bytes()).hexdigest()
    (output / 'source_hashes.json').write_text(json.dumps(hashes, indent=2) + '\n')
    if args.inventory_only:
        write_summary(output, summary)
        print(json.dumps(summary['inventory_counts'], indent=2))
        return 0
    baseline = snapshot(revision, output)
    with CommandRunner(output / 'logs', ROOT, jobs=args.jobs) as runner:
        compilers = {}
        native_objects = {}
        provider_sources, provider_libraries = native_provider_requirements(selected)
        # Runtime failures are infrastructure failures, never language rejections.
        try:
            for version, repository in [('v01', baseline), ('v02', ROOT)]:
                directory = output / version
                directory.mkdir()
                composition = directory / 'runtime.cpp'
                includes = ['core/runtime.cpp', 'modules/json/json.cpp', 'modules/datetime/datetime.cpp']
                includes.extend(provider_sources)
                if version == 'v01':
                    includes += ['modules/mysql/mysql_module.cpp', 'lang/php/php_filesystem.cpp',
                                 'lang/php/php_json.cpp', 'lang/php/php_mysqli.cpp', 'lang/php/php_regex.cpp']
                composition.write_text(''.join('#include "' + name + '"\n' for name in includes))
                obj = directory / 'runtime.o'
                runner.run(version + '-runtime', [args.cxx, '-std=c++23', '-pthread',
                           '-DSCPP_LANGUAGE_TARGET_PHP=1', '-I', repository / 'runtime/include',
                           '-c', composition, '-o', obj], timeout=240)
                compiler = CppFixtureCompiler(runner, repository, directory, pch=False,
                                             runtime=True, runtime_object=obj, cxx=args.cxx)
                compiler.flags[1] = '-std=c++23'
                compiler.link_flags.extend(provider_libraries)
                compilers[version] = compiler
                if any(row.get('lane') == 'runtime' and not row.get('blocked') for row in selected):
                    native_obj = directory / 'native-runtime.o'
                    runner.run(version + '-native-runtime', [args.cxx, '-std=c++23', '-pthread',
                               '-I', repository / 'runtime/include', '-c', composition, '-o', native_obj], timeout=240)
                    native_objects[version] = native_obj
        except Exception as error:
            summary['infrastructure_error'] = str(error)
            write_summary(output, summary)
            raise

        def check(index, case):
            identity = f'case-{index:03}'
            directory = output / identity
            directory.mkdir()
            result = dict(case)
            if case.get('blocked'):
                result.update(status='adapter-blocked', v01={'stage': 'not-run', 'passed': False},
                              v02={'stage': 'not-run', 'passed': False})
                (directory / 'result.json').write_text(json.dumps(result, indent=2) + '\n')
                return result
            original = baseline / case['source']
            content = fixture_bytes(original, case)
            if case.get('source_variant'):
                result['original_sha256'] = hashlib.sha256(content).hexdigest()
                content = (ROOT / case['source_variant']).read_bytes()
            metadata = json.loads((baseline / case['metadata']).read_text()) if case.get('metadata') else {}
            comparison = metadata.get('compare', {})
            expectations = metadata.get('expect', {})
            expected = expectations.get('run', case.get('expected'))
            build = metadata.get('build', {})
            run_args = build.get('run_args', [])
            lane = case.get('lane', 'source')
            rejection_stage = ('generation' if expectations.get('generate', {}).get('success') is False
                               else 'cpp-build' if expectations.get('compile', {}).get('success') is False else None)
            compile_only = build.get('compile_unit_only', False) or (expected is not None and expected.get('success') is False and rejection_stage is None)
            result.update(sha256=hashlib.sha256(content).hexdigest(), lane=lane,
                          expectation='generation-only' if lane == 'generation-probe' else 'rejection' if rejection_stage else 'compile-only' if compile_only else 'run')
            for version in ('v01', 'v02'):
                work = directory / version
                work.mkdir()
                source_dir = work / 'source'
                source_dir.mkdir()
                source = source_dir / ('main.phs' if 'nowdoc' in case else original.name)
                source.write_bytes(content)
                # Preserve local resource bytes without admitting sibling source programs.
                if 'nowdoc' not in case and (original.parent / 'fixtures').is_dir():
                    shutil.copytree(original.parent / 'fixtures', source_dir / 'fixtures')
                    # Some original tests use repository-relative paths from their run cwd.
                    resource_dir = work / Path(case['source']).parent / 'fixtures'
                    resource_dir.parent.mkdir(parents=True, exist_ok=True)
                    shutil.copytree(original.parent / 'fixtures', resource_dir)
                state = {'stage': 'cpp-build' if lane == 'runtime' else 'generation', 'passed': False}
                prefix = identity + '-' + version
                try:
                    if lane != 'runtime':
                        if version == 'v01':
                            text = runner.run(prefix + '-generate', ['php', APP / 'tests/compatibility_export.php',
                                              baseline, source, case['profile']])
                            generated = work / 'program.cpp'
                            generated.write_bytes(text)
                        else:
                            if case.get('modules'):
                                text = runner.run(prefix + '-generate', ['php', APP / 'tests/compatibility_modules.php',
                                                  source_dir, json.dumps(case['modules'])])
                            else:
                                text = runner.run(prefix + '-generate', ['php', APP / 'main.php', '--s2s', source_dir, '--no-prelower-templates'],
                                                  cli_reason='Exercise the production CLI argument boundary')
                            (work / 'artifacts.json').write_bytes(text)
                        if lane == 'generation-probe':
                            state.update(stage='generated', passed=True)
                            result[version] = state
                            continue
                        if rejection_stage == 'generation':
                            raise AssertionError('Expected generation rejection, but generation succeeded')
                    state['stage'] = 'cpp-build'
                    executable = work / 'program'
                    if lane == 'runtime':
                        repository = baseline if version == 'v01' else ROOT
                        # Baseline test helpers; version-specific runtime headers take precedence.
                        runner.run(prefix + '-build', [args.cxx, '-std=' + build.get('language_standard', 'c++23'),
                                   *build.get('flags', ['-O3']), '-pthread', '-I', repository / 'runtime/include',
                                   '-I', baseline, original, native_objects[version], *provider_libraries, '-o', executable], timeout=240)
                    elif compile_only or rejection_stage == 'cpp-build':
                        units = [generated] if version == 'v01' else materialize_artifacts(text, work / 'units')
                        for unit_index, unit in enumerate(units):
                            runner.run(prefix + '-build-' + str(unit_index), [*compilers[version].flags,
                                       '-I', work / 'units', '-c', unit, '-o', work / ('unit-' + str(unit_index) + '.o')])
                    elif version == 'v01':
                        compilers[version].compile(prefix + '-build', generated, executable)
                    else:
                        compilers[version].compile_artifacts(prefix + '-build', text, executable)
                    if rejection_stage:
                        raise AssertionError('Expected compilation rejection, but compilation succeeded')
                    if compile_only:
                        state.update(stage='compiled', passed=True)
                        result[version] = state
                        continue
                    state['stage'] = 'execution'
                    stdout = runner.run(prefix + '-run', [executable, *run_args], cwd=work,
                                        expected=expected.get('exit_code', 0) if expected else 0,
                                        timeout=build.get('run_timeout_seconds', 15))
                    stderr = (output / 'logs' / (prefix + '-run.stderr')).read_bytes()
                    state.update(stdout=stdout.decode('utf-8', errors='backslashreplace'),
                                 stderr=stderr.decode('utf-8', errors='backslashreplace'),
                                 stdout_hex=stdout.hex(), stderr_hex=stderr.hex())
                    state['stage'] = 'expectation'
                    if expected:
                        for stream, actual in [('stdout', stdout), ('stderr', stderr)]:
                            if not matches(actual, expected.get(stream, ''), comparison, stream):
                                raise AssertionError(stream + ' differs from pinned test metadata')
                    state.update(stage='passed', passed=True)
                except Exception as error:
                    state['error'] = str(error)
                    commands = [command for command in runner.commands if command['name'].startswith(prefix + '-')]
                    if commands:
                        command = commands[-1]
                        name = command['name']
                        state['failure_command'] = name
                        diagnostic = (output / 'logs' / (name + '.stderr')).read_text(errors='replace')
                        state['diagnostic'] = diagnostic[:4000]
                        if rejection_stage and observed_rejection(command):
                            pieces = expectations['generate' if rejection_stage == 'generation' else 'compile'].get('error_contains', [])
                            sensitive = comparison.get('case_sensitive_errors', True)
                            expected_diagnostic = all(piece in diagnostic if sensitive else piece.lower() in diagnostic.lower() for piece in pieces)
                            state['rejected'] = True
                            # Only the baseline must retain its own diagnostics/stage. v0.2 rejects need semantic review.
                            state['passed'] = version == 'v02' or (state['stage'] == rejection_stage and expected_diagnostic)
                result[version] = state
            if lane == 'generation-probe':
                result['status'] = 'probe-generated-both' if all(result[v]['passed'] for v in ('v01','v02')) else 'probe-difference' if result['v01']['passed'] else 'probe-baseline-unproven'
            elif not result['v01']['passed']:
                result['status'] = 'baseline-unproven'
            elif rejection_stage:
                result['status'] = 'rejection-needs-review' if result['v02'].get('rejected') else 'rejection-not-preserved'
            elif not result['v02']['passed']:
                result['status'] = 'compatibility-gap'
            elif compile_only:
                result['status'] = 'compile-compatible'
            else:
                result['status'] = classify(result, comparison)
            (directory / 'result.json').write_text(json.dumps(result, indent=2) + '\n')
            print(identity + ': ' + result['status'] + ' ' + case['source'], flush=True)
            return result

        try:
            outcomes = run_tasks([Task(f'case-{i:03}', lambda i=i, case=case: check(i, case))
                                  for i, case in enumerate(selected)], jobs=args.jobs)
        except TaskFailures as failure:
            summary['infrastructure_errors'] = [row.error for row in failure.outcomes if row.error]
            summary['cases'] = [row.value for row in failure.outcomes if row.value is not None]
            write_summary(output, summary)
            raise
        summary['cases'] = [row.value for row in outcomes]
        write_summary(output, summary)
    print(json.dumps(Counter(row['status'] for row in summary['cases']), indent=2))
    return 0 if all(row['status'] == 'compatible-proved' for row in summary['cases']) else 1


if __name__ == '__main__':
    sys.exit(main())
