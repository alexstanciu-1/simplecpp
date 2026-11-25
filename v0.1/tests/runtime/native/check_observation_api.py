"""Check primitive observation/reset ownership and bounded admission."""
import json
from pathlib import Path
import subprocess
import sys
root=Path(__file__).resolve().parents[3]
sys.path.insert(0,str(root/'runtime/tools'))
import concrete_types
metadata=concrete_types.load_api_metadata(root/'runtime')
published=json.loads((root/'runtime/public/specializations.json').read_text())
expected={'isset':{'float64','null','mixed','dynamic'},'empty':{'mixed','dynamic'},
          'count':{'mixed','dynamic'}, 'mixed_predicate':{'bool','int','float'},
          'clean':{'int8','int16','int32','int64','uint8','uint16','uint32','uint64','boolean','float64','string'}}
for name,types in expected.items():
    assert set(metadata['function_sets'][name]['variants'])==types
apis=[name+'_'+t for name,types in expected.items() for t in sorted(types)]
base={'metadata':metadata,'request':{'roots':[],'apis':apis},'leaves':{},
      'imports':{t:published['types'][t] for t in ['float64','mixed','dynamic']},'layouts':{},'names':{}}
def call(data,error=None):
    result=subprocess.run([sys.argv[1]],input=json.dumps(data),text=True,capture_output=True)
    if error:
        assert result.returncode and error in result.stderr,result.stderr
        return
    assert result.returncode==0,result.stderr
    return json.loads(result.stdout)
plan=call(base)
assert len(plan['units'])==22
assert all('template' not in u['header'] for u in plan['units'])
base['api_imports']=published['apis']
assert call(base)['units']==[]
# Existing integer/string presence, string emptiness and boxed reset belong to
# their type owners; new operation sets must not generate duplicate signatures.
for api in ['isset_int64','isset_string','empty_string','empty_int64','empty_null','clean_mixed','clean_dynamic','clean_null','count_string','count_null','count_int64','mixed_predicate_string','mixed_predicate_dynamic']:
    base['request']['apis']=[api]
    call(base,'Missing metadata field')
print('PASS: 22 bounded observations/resets, preserved type ownership and zero-unit reuse')
