"""Full generated matrix on v0.2; original and explicit strict/boolean-observer lanes."""
import argparse
from collections import Counter
import hashlib
import json
from pathlib import Path
import re
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler
from legacy_compatibility import matches

BOOL_FAMILIES = {'operators_comparison_equality', 'operators_comparison_ordering',
                 'operators_strict_identity', 'operators_binary_logical', 'condition_truthiness'}

def adapt(text, family, native_observer=False):
    text, count = re.subn(r'(\$[A-Za-z_][A-Za-z_0-9]*)\s*/\*\*\s*([^*]+?)\s*\*/', r'\1 \2', text)
    text, properties = re.subn(r'\b(public|protected|private)\s+/\*\*\s*([^*]+?)\s*\*/\s*(\$[A-Za-z_][A-Za-z_0-9]*)', r'\1 \2 \3', text)
    count += properties
    observer = family in BOOL_FAMILIES
    if native_observer:
        text = re.sub(r'(?m)^(\s*)var_dump\((.*)\);$', r'\1echo (\2);', text)
        return text, count, True
    if observer:
        text = text.replace('var_dump(', 'matrix_observe_bool(')
        text += '\nfunction matrix_observe_bool(bool $value): void { echo "bool(", $value ? "true" : "false", ")\\n"; }\n'
    else:
        # Preserve evaluation while removing the unsupported output API. This lane
        # cannot claim result-value parity without an equivalent typed observer.
        text = re.sub(r'(?m)^(\s*)var_dump\((.*)\);$', r'\1(\2);', text)
    return text, count, observer


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', required=True, type=Path)
    parser.add_argument('--jobs', type=int, default=32)
    parser.add_argument('--limit', type=int)
    parser.add_argument('--php', default='php')
    parser.add_argument('--native-observer', action='store_true', help='Instrument echo observation sites with the existing native var_dump helper')
    parser.add_argument('--indices', type=Path, help='Replay selected original matrix indices')
    args = parser.parse_args()
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    manifest = json.loads((ROOT / 'tests/php-matrix/_manifest.json').read_text())
    entries = manifest['entries'][:args.limit] if args.limit else manifest['entries']
    if args.indices:
        entries = [dict(manifest['entries'][i], matrix_index=i) for i in json.loads(args.indices.read_text())]
    results = []
    (out / 'manifest.json').write_text(json.dumps(manifest, indent=2))
    hashes = {}
    for folder in ('compiler/my-try', 'runtime', 'tools/operator_matrix', 'tools/php_portability', 'specs/operator_matrix/data'):
        for p in sorted((ROOT / folder).rglob('*')):
            if p.is_file() and '__pycache__' not in str(p):
                hashes[str(p.relative_to(ROOT))] = hashlib.sha256(p.read_bytes()).hexdigest()
    (out / 'source_hashes.json').write_text(json.dumps(hashes, indent=2))
    with CommandRunner(out / 'runtime-logs', ROOT, jobs=args.jobs) as runner:
        runner.run('php-preflight', [args.php, '-r', 'require "compiler/my-try/boot.php"; echo "boot-ok\\n";'])
        compiler = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
        runtime_object = compiler.link_flags[0]
    # Shard journals to avoid quadratic rewriting of a 45k-command JSON journal.
    for offset in range(0, len(entries), 256):
        chunk = entries[offset:offset + 256]
        shard = out / f'shard-{offset:05}'
        with CommandRunner(shard / 'logs', ROOT, jobs=args.jobs) as runner:
            cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True, runtime_object=runtime_object)
            cpp.flags[1] = '-std=c++23'
            def check(index, entry):
                index = entry.get('matrix_index', index)
                original = (ROOT / entry['source_path']).read_text()
                metadata = json.loads((ROOT / entry['info_path']).read_text())
                family = entry['seed_id'].split('|')[1]
                modified, declarations, observer = adapt(original, family, args.native_observer)
                record = dict(index=index, **entry, family=family,
                              original_sha256=hashlib.sha256(original.encode()).hexdigest(),
                              strict_sha256=hashlib.sha256(modified.encode()).hexdigest(),
                              declaration_replacements=declarations, boolean_observer=observer and not args.native_observer,
                              native_observer=args.native_observer, run_expectation=metadata['expect']['run'])
                for lane, source_text in [('original', original), ('strict', modified)]:
                    work = out / 'cases' / f'{index:05}' / lane
                    work.mkdir(parents=True)
                    source_dir = work / 'source'
                    source_dir.mkdir()
                    (source_dir / 'main.phs').write_text(source_text)
                    prefix = f'{index:05}-{lane}'
                    state = {'stage': 'generation', 'status': 'not-run'}
                    try:
                        payload = runner.run(prefix + '-generate', [args.php, APP / 'main.php', '--s2s', source_dir], timeout=60)
                        (work / 'artifacts.json').write_bytes(payload)
                        if lane == 'strict' and args.native_observer:
                            bundle = json.loads(payload)
                            sites = 0
                            for artifact in bundle['artifacts']:
                                text = artifact['text']
                                count = text.count('::scpp::php::echo_one(')
                                if count:
                                    artifact['text'] = '#include "scpp/support/var_dump.hpp"\n' + text.replace('::scpp::php::echo_one(', '::scpp::php::var_dump(')
                                    sites += count
                            if sites == 0:
                                raise ValueError('No matrix observation site was emitted')
                            payload = json.dumps(bundle).encode()
                            (work / 'observed-artifacts.json').write_bytes(payload)
                            state['observation_sites'] = sites
                        state['stage'] = 'cpp-build'
                        exe = work / 'program'
                        cpp.compile_artifacts(prefix + '-build', payload, exe)
                        state['stage'] = 'execution'
                        expected = metadata['expect']['run']
                        run_command = ['env', 'SCPP_ERROR_FORMAT=json', exe] if metadata['outcome'] == 'negative_runtime' else [exe]
                        stdout = runner.run(prefix + '-run', run_command, cwd=work, expected=expected.get('exit_code', 0), timeout=10)
                        stderr = (shard / 'logs' / (prefix + '-run.stderr')).read_bytes()
                        state.update(stdout=stdout.decode(errors='replace'), stderr=stderr.decode(errors='replace'))
                        state['stage'] = 'oracle'
                        if metadata['outcome'] != 'positive':
                            raise AssertionError('Negative matrix case completed successfully')
                        if lane == 'strict' and not observer:
                            state.update(stage='executed', status='executed-without-value-oracle')
                            record[lane] = state
                            continue
                        if not all(matches(actual, expected.get(stream, ''), metadata.get('compare', {}), stream)
                                   for stream, actual in [('stdout', stdout), ('stderr', stderr)]):
                            raise AssertionError('Output differs from generated matrix oracle')
                        state.update(stage='passed', status='passed')
                    except Exception as error:
                        state.update(status='failed', error=str(error))
                        commands = [c for c in runner.commands if c['name'].startswith(prefix + '-')]
                        if commands:
                            last = commands[-1]
                            state['command'] = last['name']
                            state['exit_code'] = last['code']
                            if metadata['outcome'] != 'positive' and last['code'] not in (None, 0) and not last.get('error'):
                                state['status'] = 'rejection-needs-review'
                            state['diagnostic'] = (shard / 'logs' / (last['name'] + '.stderr')).read_text(errors='replace')[:4000]
                            if metadata['outcome'] == 'negative_runtime' and state['stage'] == 'execution' and isinstance(last['code'], int) and last['code'] > 0 and not last.get('error'):
                                diagnostic = (shard / 'logs' / (last['name'] + '.stderr')).read_text(errors='replace')
                                contract = metadata['expect']['run']
                                expected_json = contract.get('error_json') or {}
                                if not isinstance(expected_json, dict):
                                    raise ValueError('Nonempty error_json must be an object')
                                try:
                                    actual_json = json.loads(diagnostic) if expected_json else {}
                                except ValueError:
                                    actual_json = {}
                                if isinstance(actual_json, dict) and isinstance(actual_json.get('error'), dict):
                                    actual_json = actual_json['error']
                                json_ok = isinstance(actual_json, dict) and all(actual_json.get(k) == v for k, v in expected_json.items())
                                text_ok = all(piece in diagnostic for piece in contract.get('error_contains', []))
                                if json_ok and text_ok:
                                    state['status'] = 'expected-runtime-rejection'
                                state['expected_error_json'] = expected_json

                    record[lane] = state
                (out / 'cases' / f'{index:05}' / 'result.json').write_text(json.dumps(record, indent=2))
                return record
            outcomes = run_tasks([Task(str(offset+i), lambda i=i,e=e: check(offset+i,e)) for i,e in enumerate(chunk)], jobs=args.jobs, report=False)
            results.extend(o.value for o in outcomes)
        summary = {'selected': len(entries), 'completed': len(results), 'jobs': args.jobs,
                   'lanes': {lane: dict(Counter(r[lane]['status'] for r in results)) for lane in ('original','strict')}}
        (out / 'progress.json').write_text(json.dumps(summary, indent=2))
        print(json.dumps(summary), flush=True)
    (out / 'summary.json').write_text(json.dumps(dict(summary, cases=results), indent=2))

if __name__ == '__main__':
    main()
