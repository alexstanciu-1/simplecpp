"""Audit canonical scalar cast obligations against metadata and published API records.

This is a coverage/freshness check, not proof of runtime value behavior or frontend
admission. Wrapper/ownership/container conversions have separately tracked owners.
"""
import json
import sys
from pathlib import Path

root = Path(__file__).resolve().parents[3]
sys.path.insert(0,str(root/'runtime/tools'))
import concrete_types
config = json.loads((root/'runtime/specs/config.json').read_text())
metadata = concrete_types.load_api_metadata(root/'runtime')
publication = json.loads((root/'runtime/public/specializations.json').read_text())
canonical = {'bool_t':'boolean', 'int_t':'int64', 'float_t':'float64',
             'string_t':'string', 'mixed_t':'mixed', 'null_t':'null'}
registry = metadata['conversions']
checked = set()
boxing = set()
for row in config['casts']:
    source, target = row['from'], row['to']
    if source not in canonical or target not in canonical:
        continue
    source, target = canonical[source], canonical[target]
    if row['form']=='constructor' and target=='mixed':
        methods=metadata['families'][target]['classes'][0]['methods']
        assert any(m.get('kind')=='constructor' and len(m.get('parameters',[]))==1
                   and m['parameters'][0]['type']==source for m in methods), (source,target)
        assert target in publication['types'], target
        boxing.add((source,target))
        continue
    # Numeric constructor policies are represented by checked named casts at the
    # public boundary, not permission for unchecked source-level constructors.
    assert row['form'] in ('named_cast','constructor'), row
    normalized=registry['sources'][source]
    assert target in registry['bindings'][normalized]['targets'], (source,target)
    api=f'cast_{target}_from_{normalized}'
    record=publication['apis'][api]
    assert record['operation']['returns']==target
    assert record['operation']['parameters']==[{'name':'value','type':normalized,'passing':'const_ref'}]
    assert (root/'runtime/public'/record['header']).is_file(), api
    assert (root/'runtime'/record['source']).is_file(), api
    checked.add((source,target))
# Top-level dynamic_types.md adds symmetric scalar boxing and explicit family
# conversion; these already belong to generated family declarations/functions.
for target in ('mixed','dynamic'):
    methods=metadata['families'][target]['classes'][0]['methods']
    constructors={m['parameters'][0]['type'] for m in methods
                  if m.get('kind')=='constructor' and len(m.get('parameters',[]))==1}
    assert {'null','boolean','int64','float64','string'} <= constructors
    record=publication['types'][target]
    assert (root/'runtime/public'/record['header']).is_file()
functions={f['name']:f for f in metadata['families']['dynamic']['functions']}
for name,source,target in [('to_dynamic','mixed','self'),('to_mixed','self','mixed')]:
    function=functions[name]
    assert function['returns']==target
    assert function['parameters']==[{'name':'value','type':source,'passing':'const_ref'}]
    assert function['transport']=='native' and 'scpp::cast<' in function['expression']
    assert name+'(' in (root/'runtime/public/scpp/dynamic.hpp').read_text()
print(f'PASS: {len(checked)} canonical scalar cast pairs, {len(boxing)} configured boxing pairs, dynamic boxing and two explicit family conversions')
