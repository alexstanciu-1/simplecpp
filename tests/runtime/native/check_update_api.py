"""Writable unary plans and owned prefix/postfix results use ordinary recipes."""
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
for op in ['pre_increment','post_increment','pre_decrement','post_decrement']:
    for t in ['int64','float64','mixed','dynamic','int8','uint64','boolean','string','null']:
        valid=t in ['int64','float64','mixed','dynamic']
        p=resolve(op,t,error=None if valid else 'Unsupported unary')
        if valid:
            assert p['result_type']==t and p['operand_type']==t
            assert p['operand_passing']=='ref' and p['evaluation']=='resolve_writable_operand_once'
            assert p['conversion']=='identity'; apis.append(p['api'])
changed=copy.deepcopy(m)
# Isolate the ordinary scalar signature check from the independently tested carrier policy.
del changed['global_operators']['operations']['pre_increment']['carrier_unary']
changed['global_operators']['operations']['pre_increment']['operand_passing']='const_ref'
resolve('pre_increment','int64',changed,error='signature mismatch')
changed=copy.deepcopy(m)
# Isolate the ordinary scalar signature check from the independently tested carrier policy.
del changed['global_operators']['operations']['pre_increment']['carrier_unary']
changed['global_operators']['operations']['pre_increment']['operand_passing']='value'
resolve('pre_increment','int64',changed,error='Unsupported unary operand passing')
changed=copy.deepcopy(m)
changed['global_operators']['operations']['add']['operand_passing']='ref'
resolve('positive','int64',changed,error='requires a unary operation')
assert resolve('positive','int64')['operand_passing']=='const_ref'
base={'metadata':m,'request':{'roots':[],'apis':apis},'imports':{t:pub['types'][t] for t in ['mixed','dynamic','float64']},'leaves':{},'layouts':{},'names':{}}
p=call(base); assert len(p['units'])==16 and all('template' not in u['header'] for u in p['units'])
base['api_imports']=pub['apis']; assert call(base)['units']==[]
print('PASS: writable unary admission, exact reference signatures, sixteen update implementations and reuse')
