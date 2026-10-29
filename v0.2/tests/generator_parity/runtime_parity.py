#!/usr/bin/env python3
"""Complete prepared-document parity for runtime families ported after scalars."""
import argparse
import copy
import json
from pathlib import Path
import subprocess
import tempfile


class Parity:
    def __init__(self, args, work):
        self.args, self.work = args, work
        self.accepted = self.rejected = 0

    def check(self, name, metadata, publication, accepted=True):
        with tempfile.TemporaryDirectory(dir=self.work) as directory:
            root = Path(directory)
            m, p = root / 'metadata.json', root / 'publication.json'
            m.write_text(json.dumps(metadata)); p.write_text(json.dumps(publication))
            commands = [
                [self.args.cpp, m, p, root / 'cpp'],
                [self.args.rust, 'prepare-runtime', m, p, self.args.facts, self.args.context],
            ]
            results = [subprocess.run(list(map(str, command)), capture_output=True, text=True, timeout=60) for command in commands]
            for engine, result in zip(['cpp', 'rust'], results):
                assert result.returncode == (0 if accepted else 1), f'{name}: {engine}: {result.stderr}'
                if not accepted:
                    assert result.stderr and not result.stdout, (name, engine)
            if accepted:
                expected = json.loads((root / 'cpp/prepared.json').read_text())
                actual = json.loads(results[1].stdout)
                encode = lambda value: json.dumps(value, sort_keys=True, separators=(',', ':'))
                assert encode(expected) == encode(actual), f'{name}: complete prepared document differs'
                self.accepted += 1
                return actual
            self.rejected += 1

    def mutations(self, metadata, publication, mutations, label):
        for path, value in mutations:
            m, p = copy.deepcopy(metadata), copy.deepcopy(publication)
            node = m if label == 'metadata' else p
            for part in path[:-1]:
                node = node[part]
            node[path[-1]] = value
            self.check(label + ':' + str(path), m, p, False)


def string_cases(proof, m, p):
    expected = proof.check('strings', m, p)
    q = copy.deepcopy(p)
    q['string']['operations'].reverse(); q['string']['casts'].reverse()
    changed = copy.deepcopy(m)
    changed['string']['operations'].reverse(); changed['string']['text_casts'].reverse()
    assert expected == proof.check('string-order', changed, q)
    q = copy.deepcopy(p); q['string'].update(operations=[], casts=[])
    proof.check('minimal-string', m, q)
    q = copy.deepcopy(p); q['types'] = sorted(m['types']); q['operators'] = []
    q['string']['casts'] = sorted(m['types']) + ['bool_t', 'float64']
    proof.check('all-text-casts', m, q)
    for op in m['string']['operations']:
        q = copy.deepcopy(p); q['string'].update(operations=[op], casts=[])
        proof.check('single-string-' + op, m, q)
    q = copy.deepcopy(p); del q['string']
    proof.check('unpublished-string', m, q)
    for path, value in [
        (['string', 'storage'], 'other'), (['string', 'count_type'], 'uint64'),
        (['string', 'construction'], 'implicit'), (['string', 'null_pointer'], 'empty'),
        (['string', 'operations'], ['unknown']), (['string', 'operations'], ['add', 'add']),
        (['string', 'text_casts'], ['php_truth']), (['string', 'text_casts'], ['decimal_integer'] * 2),
    ]:
        proof.mutations(m, p, [(path, value)], 'metadata')
        # Registered metadata is validated even when this family is not published.
        proof.mutations(m, q, [(path, value)], 'metadata')
    proof.mutations(m, p, [
        (['string', 'operations'], ['unknown']), (['string', 'operations'], ['add', 'add']),
        (['string', 'casts'], ['null_t']), (['string', 'casts'], ['int64', 'int64']),
        (['string', 'header'], '../escape.hpp'), (['string', 'header'], p['headers']['types']),
        (['string', 'extra'], True),
    ], 'publication')
    for field, value in [('operations', []), ('text_casts', [])]:
        changed = copy.deepcopy(m); changed['string'][field] = value
        proof.check('disabled-string-' + field, changed, p, False)
    for missing in ['bool_t', 'int64', 'long long']:
        changed, q = copy.deepcopy(m), copy.deepcopy(p)
        if missing == 'bool_t': q['values'] = ['null_t', 'float64']
        elif missing == 'int64': q['types'] = ['int32']
        else: changed['construction']['native_integer_inputs'].remove(missing)
        q['operators'] = []; q['string'].update(operations=[], casts=[])
        if missing == 'bool_t': q['values'] = ['float64']
        proof.check('missing-string-dependency-' + missing, changed, q, False)
    changed = copy.deepcopy(m); del changed['string']
    proof.check('missing-string-metadata', changed, p, False)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    for field in ['rust', 'cpp', 'facts', 'context', 'work']:
        parser.add_argument('--' + field, type=Path, required=True)
    args = parser.parse_args()
    for field in ['rust', 'cpp', 'facts', 'context']:
        setattr(args, field, getattr(args, field).resolve())
    root = Path(__file__).resolve().parents[2]
    m = json.loads((root / 'runtime/metadata/runtime.json').read_text())
    p = json.loads((root / 'runtime/metadata/publication.json').read_text())
    for field in ['vector', 'carriers', 'ownership']: m.pop(field, None)
    for field in ['vectors', 'carriers', 'ownership']: p.pop(field, None)
    args.work.mkdir(parents=True, exist_ok=True)
    with tempfile.TemporaryDirectory(dir=args.work) as work:
        proof = Parity(args, work)
        string_cases(proof, m, p)
        report = {'accepted': proof.accepted, 'rejected': proof.rejected}
    (args.work / 'summary.json').write_text(json.dumps(report, indent=2) + '\n')
    print(json.dumps(report))


if __name__ == '__main__':
    main()
