#!/usr/bin/env python3
"""Scalar frozen artifact contract and optional generated-consumer proofs."""
import argparse
import copy
import json
import hashlib
import re
from pathlib import Path
import subprocess
import tempfile


def run(command):
    return subprocess.run(list(map(str, command)), capture_output=True, text=True, timeout=60)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    for flag in ['rust', 'facts', 'context', 'work']:
        parser.add_argument('--' + flag, type=Path, required=True)
    parser.add_argument("--emit", action="store_true", help="Compare complete scalar artifact bytes and compile consumers")
    parser.add_argument("--cxx", default="clang++")
    parser.add_argument("--flag", action="append", default=[])
    args = parser.parse_args()
    root = Path(__file__).resolve().parents[2]
    args.rust = args.rust.resolve()
    baseline = json.loads(Path(__file__).with_name("scalar_baseline.json").read_text())["cases"]
    args.facts, args.context = args.facts.resolve(), args.context.resolve()
    metadata = json.loads((root / 'runtime/metadata/runtime.json').read_text())
    # Explicit scalar boundary: optional-family validation belongs to the composed runtime planner.
    scalar_metadata = {k: v for k, v in metadata.items() if k not in ['string', 'vector', 'carriers', 'ownership']}
    publication = json.loads((root / 'runtime/metadata/publication.json').read_text())
    publication = {k: v for k, v in publication.items() if k not in ['string', 'vectors', 'carriers', 'ownership']}
    args.work.mkdir(parents=True, exist_ok=True)
    counts = {'accepted': 0, 'rejected': 0, 'max_operations': 0}
    with tempfile.TemporaryDirectory(dir=args.work, prefix='scalar-') as temporary:
        work = Path(temporary)

        def check(label, meta, pub, accepted=True):
            with tempfile.TemporaryDirectory(dir=work) as case:
                case = Path(case)
                m, p = case / 'metadata.json', case / 'publication.json'
                m.write_text(json.dumps(meta)); p.write_text(json.dumps(pub))
                rust = run([args.rust, 'prepare-scalars', m, p, args.facts, args.context])
                expected = 0 if accepted else 1
                for name, result in [('rust', rust)]:
                    assert result.returncode == expected, f'{label}: {name} exit {result.returncode}: {result.stderr}'
                    if not accepted:
                        assert result.stderr and not result.stdout, (label, name)
                if accepted:
                    actual = json.loads(rust.stdout)
                    key = hashlib.sha256(m.read_bytes() + b'\0' + p.read_bytes()).hexdigest()
                    if args.emit:
                        generated = case / 'rust'
                        command = [args.rust, 'emit-scalars', m, p, args.facts, args.context, generated]
                        emitted = run(command)
                        assert emitted.returncode == 0, emitted.stderr
                        def tree(directory):
                            return {str(f.relative_to(directory)): f.read_bytes() for f in directory.rglob('*') if f.is_file()}
                        candidate = tree(generated)
                        hashes = {name: hashlib.sha256(data).hexdigest() for name, data in candidate.items()}
                        assert hashes == baseline[key], label + ': frozen artifact contract differs'
                        assert json.loads(candidate['prepared.json']) == actual, label
                        stamps = {str(f): f.stat().st_mtime_ns for f in generated.rglob('*') if f.is_file()}
                        assert run(command + ['--check']).returncode == 0
                        assert stamps == {str(f): f.stat().st_mtime_ns for f in generated.rglob('*') if f.is_file()}
                        if label in ['default-scalars', 'complete-scalar-matrix']:
                            flags = [args.cxx, *args.flag, '-std=c++23', '-I' + str(generated / 'public'), '-I' + str(root / 'runtime/src/numeric')]
                            obj = case / 'runtime.o'
                            compiled = run(flags + ['-c', generated / 'src/runtime.cpp', '-o', obj])
                            assert compiled.returncode == 0, compiled.stderr
                            for header in (generated / 'public').rglob('*.hpp'):
                                sample = case / 'header.cpp'; sample.write_text('#include "' + str(header.relative_to(generated / 'public')) + '"\nint main() {}\n')
                                preprocessed = run(flags + ['-E', '-P', sample])
                                assert preprocessed.returncode == 0, preprocessed.stderr
                                assert not re.search(r'\b(template|typename)\b', preprocessed.stdout), str(header)
                                compiled = run(flags + ['-fsyntax-only', sample])
                                assert compiled.returncode == 0, compiled.stderr
                            if label == 'default-scalars':
                                for consumer in ['integer_consumer', 'value_consumer', 'floating_consumer']:
                                    exe = case / consumer
                                    compiled = run(flags + [root / ('tests/' + consumer + '.cpp'), obj, '-o', exe])
                                    assert compiled.returncode == 0, compiled.stderr
                                    assert run([exe]).returncode == 0, consumer
                                # Read-only freshness failure and obsolete-inventory protection.
                                target = generated / 'src/runtime.cpp'; target.write_text('stale')
                                failed = run(command + ['--check'])
                                assert failed.returncode == 1 and target.read_text() == 'stale'
                                assert run(command).returncode == 0
                                inventory = generated / 'inventory.json'
                                inventory.write_text('["retired.hpp"]')
                                before = tree(generated)
                                assert run(command).returncode == 1 and tree(generated) == before
                    counts['max_operations'] = max(counts['max_operations'], len(actual['operations']))
                    counts['accepted'] += 1
                    return actual
                if args.emit:
                    rejected_output = case / 'rejected'
                    result = run([args.rust, 'emit-scalars', m, p, args.facts, args.context, rejected_output])
                    assert result.returncode == 1 and not rejected_output.exists(), label
                counts['rejected'] += 1

        check('default-scalars', scalar_metadata, publication)
        # Accept full metadata as a scoped scalar input; optional sections are not validated.
        check('full-metadata-scalar-demand', metadata, publication)
        base = copy.deepcopy(publication)
        base.update(types=sorted(metadata['types']), operators=[])
        requests, rejected = {}, []

        def request(name, operands, accepted):
            row = {'operation': name, 'operands': operands}
            if accepted:
                requests[json.dumps(row, sort_keys=True)] = row
            else:
                rejected.append(row)

        def int_pair(left, right):
            l, r = metadata['types'][left], metadata['types'][right]
            return not ((left == 'uint64' and r['signed']) or (right == 'uint64' and l['signed']))

        # All integer families, forms, ordered pairs and native integer adapters.
        native_facts = json.loads(args.facts.read_text())['native_integers']
        for name, binding in metadata['operations'].items():
            binary = binding['form'] in ['binary', 'compound']
            for left in base['types']:
                if not binary:
                    request(name, [left], True)
                    continue
                for right in base['types']:
                    valid = binding['promotion'] != 'integer_domain_maximum' or int_pair(left, right)
                    request(name, [left, right], valid)
                if 'native_arguments' in binding:
                    for native in metadata['construction']['native_integer_inputs']:
                        fact = native_facts[native]
                        identity = next(k for k, t in metadata['types'].items() if t['signed'] == fact['signed'] and t['bits'] - int(t['signed']) == fact['digits'])
                        valid = int_pair(left, identity)
                        request(name, [left, {'native': native}], valid)
                        if binding['form'] == 'binary':
                            request(name, [{'native': native}, left], valid)

        # Every float operation, float/integer pair, both orders, and native adapters.
        for name, binding in metadata['floating']['operations'].items():
            binary = binding['form'] in ['binary', 'compound']
            if not binary:
                request(name, ['float64'], True)
                continue
            request(name, ['float64', 'float64'], True)
            for integer, info in metadata['types'].items():
                valid = info['bits'] - int(info['signed']) <= 53
                request(name, ['float64', integer], valid)
                request(name, [integer, 'float64'], valid)
            if 'native_arguments' in binding:
                for native, info in native_facts.items():
                    valid = info['digits'] <= 53
                    request(name, ['float64', {'native': native}], valid)
                    if binding['form'] == 'binary':
                        request(name, [{'native': native}, 'float64'], valid)

        full = copy.deepcopy(base); full['operators'] = list(requests.values())
        plan = check('complete-scalar-matrix', scalar_metadata, full)
        reordered = copy.deepcopy(full)
        for field in ['types', 'values', 'operators', 'short_aliases']:
            reordered[field].reverse()
        m = copy.deepcopy(scalar_metadata)
        m['scalar_casts'].reverse(); m['construction']['native_integer_inputs'].reverse()
        m['floating']['native_inputs'].reverse()
        for v in m['values'].values():
            v['operations'].reverse()
        assert plan == check('reordered-matrix', m, reordered)
        for index, row in enumerate(rejected):
            p = copy.deepcopy(base); p['operators'] = [row]
            check(f'rejected-matrix-{index}', scalar_metadata, p, False)

        for values in [[], ['bool_t'], ['bool_t', 'null_t'], ['float64'], ['bool_t', 'null_t', 'float64']]:
            p = copy.deepcopy(base); p['values'] = values
            check('selected-values-' + str(values), scalar_metadata, p)
            if values:
                p['types'] = []
                check('values-only-' + str(values), scalar_metadata, p)
        m = copy.deepcopy(scalar_metadata); m['source_types']['int'] = 'int32'
        check('configured-default', m, base)
        m = copy.deepcopy(scalar_metadata); m['values']['null_t']['operations'] = []
        p = copy.deepcopy(base); p.update(types=[], values=['null_t'])
        check('null-without-comparisons', m, p)
        p = copy.deepcopy(base); p['short_aliases'] = ['int_t', 'float_t']
        check('explicit-short-float-alias', scalar_metadata, p)
        p['operators'] = [{'operation': 'less', 'operands': ['int64', 'uint64'], 'both_orders': True}]
        check('both-orders-comparison', scalar_metadata, p)

        # Admission gates, disabled cast/native policies, and malformed registered bindings.
        mutations = [
            (['construction', 'explicit'], False),
            (['promotion_policies', 'integer_domain_maximum', 'different_signed'], 'int32'),
            (['operations', 'add', 'public_operator'], '-'),
            (['operations', 'divide', 'implementation'], 'integer_add'),
            (['operations', 'negate', 'form'], 'binary'),
            (['operations', 'less', 'native_arguments'], metadata['operations']['add']['native_arguments']),
            (['operations', 'add', 'native_arguments', 'policy'], 'literal_value'),
            (['values', 'bool_t', 'condition'], 'implicit'),
            (['values', 'null_t', 'condition'], 'implicit'),
            (['values', 'bool_t', 'operations'], ['not', 'not']),
            (['values', 'bool_t', 'operations'], ['add']),
            (['source_types', 'bool'], 'int64'),
            (['aliases', 'int_t'], 'null'),
            (['floating', 'mixed_integer'], 'round_everything'),
            (['floating', 'division_by_zero'], 'unchecked'),
            (['floating', 'native_inputs'], ['float']),
            (['floating', 'native_inputs'], ['double', 'double']),
            (['floating', 'operations', 'add', 'implementation'], 'integer_add'),
            (['floating', 'operations', 'less', 'native_arguments'], metadata['operations']['add']['native_arguments']),
            (['scalar_casts'], ['unsupported']),
            (['scalar_casts'], metadata['scalar_casts'] * 2),
        ]
        for path, value in mutations:
            m = copy.deepcopy(scalar_metadata); node = m
            for key in path[:-1]: node = node[key]
            node[path[-1]] = value
            check('invalid-metadata-' + '/'.join(path), m, base, False)
        for policy, op in [('integer_to_floating_rounding', 'add'), ('floating_to_integer_truncate_checked', 'add_assign')]:
            m = copy.deepcopy(scalar_metadata); m['scalar_casts'].remove(policy)
            p = copy.deepcopy(base); p['operators'] = [{'operation': op, 'operands': ['int32', 'float64']}]
            check('disabled-' + policy, m, p, False)
            # No operation demanding the cast: omitted policy is a valid smaller surface.
            check('optional-' + policy, m, base)
        invalid_requests = [
            [{'operation': 'add', 'operands': ['int32', {'native': 'int'}]}],
            [{'operation': 'add', 'operands': [{'native': 'int'}, {'native': 'int'}]}],
            [{'operation': 'add_assign', 'operands': [{'native': 'int'}, 'int64']}],
            [{'operation': 'negate', 'operands': ['int64', 'int64']}],
            [{'operation': 'negate', 'operands': ['int64'], 'both_orders': True}],
            [{'operation': 'remainder', 'operands': ['float64', 'float64']}],
            [{'operation': 'add', 'operands': ['bool_t', 'int64']}],
            [{'operation': 'add', 'operands': ['null_t', 'int64']}],
            [{'operation': 'less', 'operands': ['int64', {'native': 'int'}]}],
            [{'operation': 'add', 'operands': ['int64', {'native': 'double'}]}],
            [{'operation': 'add', 'operands': ['int64', 'int64']}] * 2,
        ]
        for index, rows in enumerate(invalid_requests):
            p = copy.deepcopy(base); p['operators'] = rows
            check('invalid-request-' + str(index), scalar_metadata, p, False)
        for field, value in [('values', ['null_t']), ('values', ['bool_t', 'bool_t']), ('types', ['int32', 'int32']), ('short_aliases', ['int_t', 'int_t']), ('namespace', 'other')]:
            p = copy.deepcopy(base); p[field] = value
            check('invalid-publication-' + field, scalar_metadata, p, False)
        p = copy.deepcopy(base); p['types'] = ['int8', 'int16']; p['operators'] = [{'operation': 'add', 'operands': ['int8', 'int16']}]
        check('missing-domain-maximum', scalar_metadata, p, False)
        p = copy.deepcopy(base); p['headers']['values'] = p['headers']['types']
        check('header-collision', scalar_metadata, p, False)
        # Non-scalar demand is a deliberate Rust-only boundary, not a parity claim.
        p = work / 'full-publication.json'; p.write_text((root / 'runtime/metadata/publication.json').read_text())
        m = work / 'full-metadata.json'; m.write_text(json.dumps(metadata))
        result = run([args.rust, 'prepare-scalars', m, p, args.facts, args.context])
        assert result.returncode == 1 and not result.stdout
        if args.emit:
            output = work / 'unsupported-emission'
            result = run([args.rust, 'emit-scalars', m, p, args.facts, args.context, output])
            assert result.returncode == 1 and not output.exists()
    (args.work / 'summary.json').write_text(json.dumps(counts, indent=2) + '\n')
    print(json.dumps(counts))


if __name__ == '__main__':
    main()
