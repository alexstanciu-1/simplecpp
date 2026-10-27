"""Unary operations preserve their admitted operand rather than making a fake pair."""
import copy
import json
from pathlib import Path
import subprocess
import sys
root=Path(__file__).resolve().parents[3]
m=json.loads((root/'runtime/metadata/public_api.json').read_text())
pub=json.loads((root/'runtime/public/specializations.json').read_text())
def call(data,error=None):
    r=subprocess.run([sys.argv[1]],input=json.dumps(data),text=True,capture_output=True)
    if error:
        assert r.returncode and error in r.stderr,r.stderr
        return
    assert r.returncode==0,r.stderr
    return json.loads(r.stdout)
def resolve(op,t,meta=m,error=None):
    return call({'action':'resolve_operator','metadata':meta,'request':{'operation':op,'operand':t}},error)
apis=[]
for op in ['positive','negative','complement']:
    for t in ['int64','float64','mixed','dynamic','int8','uint64','boolean','string','null']:
        valid=t in ['int64','mixed','dynamic'] or (t=='float64' and op!='complement')
        p=resolve(op,t,error=None if valid else 'Unsupported unary')
        if valid:
            assert p['operand_type']==t and p['result_type']==t
            assert p['evaluation']=='evaluate_operand_once' and p['conversion']=='identity'
            assert 'left' not in p and 'right' not in p
            apis.append(p['api'])
changed=copy.deepcopy(m)
changed['function_sets']['positive']['function']['operation']['parameters'][0]['passing']='ref'
resolve('positive','int64',changed,error='signature mismatch')
changed=copy.deepcopy(m)
changed['global_operators']['operations']['positive']['form']='ternary'
resolve('positive','int64',changed,error='Unknown operator form')
changed=copy.deepcopy(m)
changed['global_operators']['operations']['positive']['group']='numeric'
resolve('positive','int64',changed,error='unknown field group')
base={'metadata':m,'request':{'roots':[],'apis':apis},'imports':{t:pub['types'][t] for t in ['mixed','dynamic','float64']},'leaves':{},'layouts':{},'names':{}}
p=call(base); assert len(p['units'])==11 and all('template' not in u['header'] for u in p['units'])
assert all('#include \"operators/arithmetic/boxed_numeric.hpp\"' in u['source'] for u in p['units'])
base['api_imports']=pub['apis']; assert call(base)['units']==[]
print('PASS: unary admission, exact borrowed signatures, one-operand plans, eleven implementations and reuse')
