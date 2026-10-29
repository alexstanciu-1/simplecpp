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
            m.write_bytes(metadata if isinstance(metadata, bytes) else json.dumps(metadata).encode())
            p.write_bytes(publication if isinstance(publication, bytes) else json.dumps(publication).encode())
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


def vector_cases(proof, m, p):
    expected = proof.check('vectors', m, p)
    q = copy.deepcopy(p); q['vectors']['specializations'].reverse()
    for v in q['vectors']['specializations']:
        v['operations'].reverse(); v['casts_from'].reverse()
    assert expected == proof.check('vector-order', m, q)
    q = copy.deepcopy(p); del q['vectors']
    proof.check('unpublished-vectors', m, q)
    q = copy.deepcopy(p); q['operators'] = []
    for v in q['vectors']['specializations']: v.update(operations=[], native_index=False, native_value='none', casts_from=[])
    proof.check('core-vectors', m, q)
    full = copy.deepcopy(p)
    full['types'] = sorted(m['types'])
    elements = full['types'] + ['float64', 'bool_t', 'null_t', 'string']
    full['operators'] = [{'operation':'equal','operands':[t,t]} for t in full['types'] + ['float64']]
    full['string']['casts'] = full['types'] + ['float64', 'bool_t']
    full['vectors']['specializations'] = [dict(element=t, native_index=True,
        native_value='const char*' if t == 'string' else 'none' if t in ['bool_t','null_t'] else 'int',
        operations=['equal','not_equal'], casts_from=[]) for t in elements]
    preliminary = proof.check('all-vector-elements', m, full)
    conversions = {(c['source'],c['target']) for c in preliminary['conversions'] if not c['native_source']}
    for v in full['vectors']['specializations']:
        v['casts_from'] = ['vector_' + source for source in elements if (source,v['element']) in conversions]
    all_casts = proof.check('all-vector-casts', m, full)
    reverse = copy.deepcopy(full); reverse['vectors']['specializations'].reverse()
    for v in reverse['vectors']['specializations']: v['casts_from'].reverse()
    assert all_casts == proof.check('all-vector-casts-reordered', m, reverse)
    for path, value in [
        (['vector','storage'],'other'), (['vector','read'],'reference'), (['vector','mutation'],'reference'),
        (['vector','count_type'],'uint64'), (['vector','element_families'],['carrier']),
        (['vector','element_families'],['integer','integer']), (['vector','operations'],['add']),
        (['vector','operations'],['equal','equal']), (['vector','native_value'],['double']),
        (['vector','native_index'],'long'),
    ]:
        proof.mutations(m,p,[(path,value)],'metadata')
        q = copy.deepcopy(p); del q['vectors']
        proof.mutations(m,q,[(path,value)],'metadata')
    proof.mutations(m,p,[
        (['vectors','specializations'],[]),
        (['vectors','specializations'],p['vectors']['specializations'] * 2),
        (['vectors','header'],p['string']['header']),
        (['vectors','specializations',0,'element'],'missing'),
        (['vectors','specializations',0,'native_value'],'const char*'),
        (['vectors','specializations',0,'operations'],['equal','equal']),
        (['vectors','specializations',0,'casts_from'],['vector_missing']),
        (['vectors','specializations',0,'casts_from'],['vector_int64']),
        (['vectors','specializations',0,'casts_from'],['vector_string','vector_string']),
        (['vectors','specializations',0,'native_index'],'yes'),
    ],'publication')
    q = copy.deepcopy(p); q['operators'] = []
    proof.check('missing-integer-equality',m,q,False)
    q = copy.deepcopy(p); q['string']['operations'] = []
    proof.check('missing-string-equality',m,q,False)
    q = copy.deepcopy(p); q['string']['casts'] = []
    q['vectors']['specializations'][0]['casts_from'] = ['vector_string']
    proof.check('missing-element-cast',m,q,False)
    changed = copy.deepcopy(m); changed['construction']['native_integer_inputs'].remove('int')
    q = copy.deepcopy(p); q['operators'] = []
    for v in q['vectors']['specializations']: v.update(operations=[],native_value='none')
    proof.check('missing-native-index',changed,q,False)
    changed = copy.deepcopy(m); del changed['vector']
    proof.check('missing-vector-metadata',changed,p,False)


def carrier_cases(proof, m, p):
    expected = proof.check('default-carriers',m,p)
    assert len(expected['carriers']) == 16
    q = copy.deepcopy(p); q['carriers']['specializations'].reverse()
    assert expected == proof.check('carrier-order',m,q)
    changed = copy.deepcopy(m); changed['carriers']['extraction'] = 'explicit_checked_one_layer_value_copy'
    explicit = proof.check('explicit-carriers',changed,p)
    assert all(c['extraction'] == 'explicit_checked_one_layer_value_copy' for c in explicit['carriers'])
    assert explicit['operations'] == expected['operations']
    q = copy.deepcopy(p); del q['carriers']
    without = proof.check('unpublished-carriers',m,q)
    assert without['operations'] == expected['operations'] and without['conversions'] == expected['conversions']
    assert 'error' not in without
    full = copy.deepcopy(p); full['types'] = sorted(m['types'])
    elements = full['types'] + ['float64','bool_t','null_t','string']
    full['operators'] = []
    full['vectors']['specializations'] = [dict(element=t,native_index=False,native_value='none',operations=[],casts_from=[]) for t in elements]
    payloads = [t for t in elements if t != 'null_t'] + ['vector_' + t for t in elements]
    full['carriers']['specializations'] = [dict(family=f,payload=t) for f in m['carriers']['families'] for t in payloads]
    all_carriers = proof.check('all-carrier-payloads',m,full)
    assert len(all_carriers['carriers']) == 92 and not all_carriers['operations']
    proof.check('all-explicit-carrier-payloads',changed,full)
    for family in m['carriers']['families']:
        q = copy.deepcopy(full); q['carriers']['specializations'] = [dict(family=family,payload='int64')]
        result = proof.check('only-' + family,m,q)
        assert ('error' in result) == (family == 'result')
    for path, value in [
        (['carriers','extraction'],'unchecked'), (['carriers','construction'],'implicit'),
        (['carriers','families','result'],'error'), (['carriers','families','nullable'],'value'),
        (['carriers','families','result_or_bool'],'true'), (['carriers','families','other'],'value'),
        (['carriers','error','state'],'message_not_empty'), (['carriers','error','line'],'int32'),
        (['carriers','error','type'],'string'), (['carriers','error','message'],'native'),
    ]:
        proof.mutations(m,p,[(path,value)],'metadata')
        q = copy.deepcopy(p); del q['carriers']
        proof.mutations(m,q,[(path,value)],'metadata')
    proof.mutations(m,p,[
        (['carriers','specializations'],[]),
        (['carriers','specializations'],p['carriers']['specializations'] * 2),
        (['carriers','specializations',0,'payload'],'null_t'),
        (['carriers','specializations',0,'payload'],'nullable_int64'),
        (['carriers','specializations',0,'payload'],'error_t'),
        (['carriers','specializations',0,'payload'],'missing'),
        (['carriers','specializations',0,'family'],'unknown'),
        (['carriers','header'],p['vectors']['header']),
        (['carriers','header'],p['string']['header']),
    ],'publication')
    q = copy.deepcopy(p); q['carriers']['specializations'] = [dict(family='nullable',payload='int64')]
    q['values'].remove('null_t')
    proof.check('nullable-needs-null',m,q,False)
    q = copy.deepcopy(p); q.pop('string'); q.pop('vectors')
    q['carriers']['specializations'] = [dict(family='result',payload='int64')]
    proof.check('result-needs-string',m,q,False)
    q = copy.deepcopy(p); q.pop('vectors'); q['types'] = ['int32']; q['operators'] = []
    q['string']['casts'] = []; q['carriers']['specializations'] = [dict(family='result',payload='int32')]
    changed = copy.deepcopy(m); changed['string']['count_type'] = 'int32'
    proof.check('result-needs-int64',changed,q,False)
    q = copy.deepcopy(p); q['operators'] = [dict(operation='add',operands=['nullable_int64','int64'])]
    proof.check('no-lifted-carrier-operator',m,q,False)
    changed = copy.deepcopy(m); del changed['carriers']
    proof.check('missing-carrier-metadata',changed,p,False)


def ownership_cases(proof, m, p, default):
    import itertools
    expected = proof.check('ownership-default',m,p)
    q = copy.deepcopy(p); q['ownership']['records'].reverse()
    for r in q['ownership']['records']:
        r['constructors'].reverse()
        for f in ['families','const_families']:
            if f in r: r[f].reverse()
    assert expected == proof.check('ownership-order',m,q)
    q = copy.deepcopy(default); q['ownership'] = copy.deepcopy(p['ownership'])
    proof.check('all-families-composed',m,q)
    def subsets(items):
        return [list(s) for n in range(len(items)+1) for s in itertools.combinations(items,n)]
    for mutable in subsets(['shared','weak','unique','value']):
        for const in subsets(['shared','weak','unique']):
            q = copy.deepcopy(p); q['ownership']['records'] = [q['ownership']['records'][0]]
            q['ownership']['records'][0].update(families=mutable,const_families=const)
            valid = bool(mutable or const) and all('weak' not in f or 'shared' in f for f in [mutable,const])
            proof.check('owner-subsets-' + str((mutable,const)),m,q,valid)
    q = copy.deepcopy(p); q['ownership']['records'] = [q['ownership']['records'][0]]
    r = q['ownership']['records'][0]; r['families'] = ['value']; r.pop('const_families')
    r['constructors'] = [[]]
    proof.check('default-value',m,q)
    r['constructors'] = [['bool_t','int64'],['int64','bool_t']]
    proof.check('ordered-constructor-arguments',m,q)
    r['header'] = 'scpp/./records.hpp'
    proof.check('legacy-record-dot-component',m,q)
    q['ownership']['header'] = 'scpp/./owners.hpp'
    proof.check('legacy-owner-dot-component',m,q)
    q = copy.deepcopy(p); q['ownership']['records'] = [q['ownership']['records'][0]]
    del q['ownership']['records'][0]['families']
    proof.check('const-only-no-mutable-field',m,q)
    for path,value in [
        (['ownership','binding'],'external'), (['ownership','families'],['shared','weak']),
        (['ownership','families'],['shared','weak','unique','unique']),
        (['ownership','default'],'value'), (['ownership','construction'],'implicit'),
        (['ownership','access'],'unchecked'), (['ownership','weak_access'],'implicit'),
        (['ownership','payload'],'generic'), (['ownership','const_pointee'],'implicit'),
        (['ownership','value','special_members'],'always_noexcept'),
        (['ownership','value','binding'],'heap_record'), (['ownership','extra'],True),
    ]:
        proof.mutations(m,p,[(path,value)],'metadata')
        proof.mutations(m,default,[(path,value)],'metadata')
    proof.mutations(m,p,[
        (['ownership','records'],[]),
        (['ownership','header'],p['headers']['types']),
        (['ownership','records',0,'header'],p['ownership']['header']),
        (['ownership','records',0,'header'],'../outside.hpp'),
        (['ownership','records',0,'name'],'int64'),
        (['ownership','records',0,'families'],['weak']),
        (['ownership','records',0,'families'],['shared','shared']),
        (['ownership','records',0,'const_families'],['value']),
        (['ownership','records',0,'const_families'],['weak']),
        (['ownership','records',0,'constructors'],[]),
        (['ownership','records',0,'constructors'],[[],[]]),
        (['ownership','records',0,'constructors'],[['string']]),
        (['ownership','records',0,'constructors'],[['shared_record_probe']]),
        (['ownership','records'],p['ownership']['records'] * 2),
    ],'publication')
    for name in ['class','std','detail','record__x','_record','Record','r-x','error_t','null','co_await','']:
        proof.mutations(m,p,[(['ownership','records',0,'name'],name)],'publication')
    for collision in ['string','vector_int64','nullable_int64','int_t']:
        q = copy.deepcopy(default); q['ownership'] = copy.deepcopy(p['ownership'])
        q['ownership']['records'][0]['name'] = collision
        proof.check('owner-name-collision-' + collision,m,q,False)
    q = copy.deepcopy(p); q['ownership']['records'][1]['name'] = 'shared_record_probe'
    proof.check('record-specialization-collision',m,q,False)
    for missing in ['value','const_pointee']:
        changed = copy.deepcopy(m); del changed['ownership'][missing]
        proof.check('missing-owner-policy-' + missing,changed,p,False)
        q = copy.deepcopy(p); q['ownership']['records'] = [q['ownership']['records'][0]]
        q['ownership']['records'][0]['families'] = ['shared']; q['ownership']['records'][0].pop('const_families')
        proof.check('unused-owner-policy-' + missing,changed,q)
    changed = copy.deepcopy(m); del changed['ownership']
    proof.check('missing-owner-metadata',changed,p,False)


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
    args.work.mkdir(parents=True, exist_ok=True)
    with tempfile.TemporaryDirectory(dir=args.work) as work:
        proof = Parity(args, work)
        string_p = copy.deepcopy(p); string_p.pop('vectors', None); string_p.pop('carriers', None)
        vector_p = copy.deepcopy(p); vector_p.pop('carriers', None)
        string_cases(proof, m, string_p)
        vector_cases(proof, m, vector_p)
        carrier_cases(proof, m, p)
        ownership = json.loads((root / 'tests/ownership/publication.json').read_text())
        ownership_cases(proof,m,ownership,p)
        from corpus import cases
        for case in cases(root):
            proof.check('G1-' + case.name, case.metadata, case.publication, not case.rejection)
        report = {'accepted': proof.accepted, 'rejected': proof.rejected}
    (args.work / 'summary.json').write_text(json.dumps(report, indent=2) + '\n')
    print(json.dumps(report))


if __name__ == '__main__':
    main()
