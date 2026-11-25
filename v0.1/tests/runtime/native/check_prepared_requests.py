"""Prepared logical admission is bound to the request and independent of C++ traits."""
import argparse
import copy
import json
from pathlib import Path
import subprocess
import sys
import time
from prepared_fixture import logical, request as prepare_request
root=Path(__file__).resolve().parents[3]
sys.path.insert(0,str(root/'runtime/tools'))
import concrete_types
parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--tool',type=Path,required=True)
args=parser.parse_args()
metadata=concrete_types.metadata(root/'runtime')
def call(data,error=None):
    result=subprocess.run([str(args.tool)],input=json.dumps(data),text=True,capture_output=True)
    if error:
        assert result.returncode and error in result.stderr,result.stderr
        return
    assert result.returncode==0,result.stderr
    return json.loads(result.stdout)
base={'action':'resolve_logical','metadata':metadata,'request':{'operation':'logical_and','left':'nullable<int64>','right':'int64'}}
prepared=logical(base)
prepared['prepared']['bindings']['left']['arguments']={'T':'fixture.source.int64'}
native=call(base);source=call(prepared)
assert source.pop('preparation')==prepared['prepared'] and source==native
shape=call({'metadata':metadata,'request':{'package':'fixture.prepared','roots':['nullable<int64>']},
            'names':{},'layouts':{},'imports':{},'leaves':{}})
imported=copy.deepcopy(prepared);imported['imports']=shape['types']
assert call(imported)['preparation']==prepared['prepared']
# Imports and real storage traits cannot manufacture source evidence.
for initial in [prepared,imported]:
    bad=copy.deepcopy(initial);bad['prepared']['facts']=[]
    call(bad,'Missing prepared semantic fact')
    bad=copy.deepcopy(initial);bad['prepared']['requirements'].append({'subject':'left','contract':'copyable_value'})
    bad['prepared']['facts'].append({'subject':'left','contract':'copy_storage','evidence':'native trait'})
    call(bad,'Missing prepared semantic fact: left/copyable_value')
    bad=copy.deepcopy(initial);bad['prepared']['requirements'].append({'subject':'left','contract':'result_payload'})
    call(bad,'Missing prepared semantic fact: left/result_payload')
bad=copy.deepcopy(prepared);bad['request']['operation']='logical_or'
call(bad,'does not match requested operation')
bad=copy.deepcopy(prepared);bad['prepared']['bindings']['left']['physical']='nullable<float64>'
call(bad,'physical binding mismatch')
bad=copy.deepcopy(prepared);bad['prepared']['facts'].append(bad['prepared']['facts'][0])
call(bad,'Duplicate prepared fact')
bad=copy.deepcopy(prepared);bad['prepared']['facts'][0]['evidence']=''
call(bad,'nonempty evidence')
bad=copy.deepcopy(prepared);bad['prepared']['facts'][0]['subject']='missing'
call(bad,'Unknown prepared fact subject')
bad=copy.deepcopy(base);bad['mode']='source'
call(bad,'requires prepared facts')
bad=copy.deepcopy(prepared);del bad['mode']
call(bad,'require source mode')
bad=copy.deepcopy(prepared);bad['prepared']['revision']=''
call(bad,'nonempty revision')
bad=logical({**base,'action':'names'})
call(bad,'source action is not implemented')
# A source attestation cannot bypass the physical operation's own admission.
bad=logical({**base,'request':{'operation':'logical_and','left':'nullable<int64>','right':'mixed'}})
call(bad,'Unsupported logical operand pair')
# Aliases are resolved by the existing naming owner; evidence binds canonical physical identity.
alias=copy.deepcopy(prepared);alias['names']={'nullable<int64>':'prepared_optional'}
alias['request']['left']='prepared_optional';alias['prepared']['request']=copy.deepcopy(alias['request'])
assert call(alias)['left']['projection']['target']=='int64'
# Every operation request owner uses the same evidence validator.
cases=[
    ('resolve_condition', {'source':'result<nullable<int64>>'}, ['source']),
    ('resolve_conversion', {'source':'nullable<int64>','target':'int8'}, ['source','target']),
    ('resolve_operator', {'operation':'logical_not','operand':'nullable<int64>'}, ['operand']),
    ('resolve_operator', {'operation':'add','left':'nullable<int64>','right':'result<int64>'}, ['left','right']),
    ('resolve_operator', {'operation':'strict_identical','left':'nullable<int64>','right':'null'}, ['left','right']),
    ('resolve_compound', {'operation':'add_assign','target':'nullable<int64>','right':'int64'}, ['target','right']),
    ('resolve_coalesce', {'left':'nullable<int64>','right':'int64'}, ['left','right']),
    ('resolve_conditional', {'form':'ternary','condition':'boolean','then':'nullable<int64>','else':'int64'}, ['condition','then','else']),
    ('resolve_conditional', {'form':'elvis','then':'nullable<int64>','else':'int64'}, ['then','else']),
]
for action,request,slots in cases:
    plain={'metadata':metadata,'action':action,'request':request}
    data=prepare_request(plain,slots)
    result=call(data);assert result.pop('preparation')==data['prepared'] and result==call(plain)
    for slot in slots:
        bad=copy.deepcopy(data)
        bad['prepared']['facts']=[fact for fact in bad['prepared']['facts'] if fact['subject']!=slot]
        call(bad,'Missing prepared semantic fact: '+slot)
    bad=copy.deepcopy(data);bad['request']['unapproved_field']=True
    call(bad,'does not match requested operation')
    bad=copy.deepcopy(data);bad['prepared']['bindings']['unexpected']=copy.deepcopy(data['prepared']['bindings'][slots[0]])
    call(bad,'prepared bindings')
# Formation evidence never substitutes for the separately demanded boundary fact.
data=prepare_request({'metadata':metadata,'action':'resolve_conversion',
    'request':{'source':'result<int64>','target':'int64'}},['source','target'])
data['prepared']['facts']=[fact for fact in data['prepared']['facts']
    if not (fact['subject']=='source' and fact['contract']=='boundary_admitted')]
call(data,'Missing prepared semantic fact: source/boundary_admitted')
print('PASS: all operation request shapes, per-slot facts, whole-request binding and separate boundary admission')
print('PASS: prepared provenance/bindings, required semantic facts, import non-bypass, aliases and native-mode separation')
# Timing includes the process and JSON work; not a compiler or runtime benchmark.
for label,data in [('native',base),('prepared',prepared)]:
    start=time.perf_counter()
    for _ in range(5):call(data)
    print(f'{label} logical planning mean including process: {(time.perf_counter()-start)/5:.4f}s')
