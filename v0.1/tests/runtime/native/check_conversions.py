"""Check conversion normalization, admission and ordinary API ownership reuse."""
import copy
import json
from pathlib import Path
import subprocess
import sys
root = Path(__file__).resolve().parents[3]
sys.path.insert(0,str(root/'runtime/tools'))
import concrete_types
metadata = concrete_types.load_api_metadata(root/'runtime')
def call(data, error=None):
    result = subprocess.run([sys.argv[1]], input=json.dumps(data), text=True, capture_output=True)
    if error:
        assert result.returncode and error in result.stderr, result.stderr
        return
    assert result.returncode == 0, result.stderr
    return json.loads(result.stdout)
def resolve(source, target, meta=metadata, error=None):
    return call({'action':'resolve_conversion','metadata':meta,'request':{'source':source,'target':target}}, error)
for source, normalized in metadata['conversions']['sources'].items():
    for target in metadata['conversions']['bindings'][normalized]['targets']:
        p=resolve(source,target)
        assert p['operand_type']==normalized and p['target']==target
        assert p['api']==f'cast_{target}_from_{normalized}'
for source,target in [('null','int8'),('string','mixed'),('null','int64'),('int64','mixed')]:
    resolve(source,target,error='Unsupported checked conversion')
changed=copy.deepcopy(metadata)
changed['conversions']['bindings']['string']['targets'].remove('int8')
resolve('string','int8',changed,error='Unsupported checked conversion')
changed=copy.deepcopy(metadata)
changed['conversions']['sources']['int8']='int16'
resolve('int8','int64',changed,error='Missing')
changed=copy.deepcopy(metadata)
changed['conversions']['bindings']['int8']=copy.deepcopy(changed['conversions']['bindings']['int64'])
resolve('int8','int64',changed,error='Conversion binding must be normalized')
apis=[f'cast_{t}_from_{s}' for s,b in metadata['conversions']['bindings'].items() for t in b['targets']]
envelope={'metadata':metadata,'request':{'roots':[],'apis':apis+apis},'leaves':{},'imports':{},'layouts':{},'names':{}}
# float64 needs layout probing in a standalone request. Reuse the published descriptor.
published=json.loads((root/'runtime/public/specializations.json').read_text())
envelope['imports']={n:published['types'][n] for n in ['float64','mixed','dynamic']}
plan=call(envelope)
assert len(plan['units'])==len(apis)
assert all('template' not in unit['header'] for unit in plan['units'])
assert all('scpp::cast<' in unit['source'] for unit in plan['units'])
envelope['api_imports']=plan['apis']
assert call(envelope)['units']==[]
envelope['api_imports'][apis[0]]['namespace']='conflict'
call(envelope,error='Conflicting API ownership')
print('PASS: normalized numeric/string conversions; metadata admission; normalized adapters; published reuse')
