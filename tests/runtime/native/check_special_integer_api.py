"""Canonical/boxed binary integer admission, native ownership and reuse."""
import json
from pathlib import Path
import subprocess
import sys
root=Path(__file__).resolve().parents[3]
sys.path.insert(0,str(root/'runtime/tools'))
import concrete_types
m=concrete_types.load_api_metadata(root/'runtime')
pub=json.loads((root/'runtime/public/specializations.json').read_text())
def call(request,error=None):
    r=subprocess.run([sys.argv[1]],input=json.dumps(request),text=True,capture_output=True)
    if error:
        assert r.returncode and error in r.stderr,r.stderr
        return
    assert r.returncode==0,r.stderr
    return json.loads(r.stdout)
apis=[]
for op in ['bit_and','bit_or','bit_xor','shift_left','shift_right','power']:
    admitted=['int64'] if op=='power' else ['int64','mixed','dynamic']
    for left in ['int64','mixed','dynamic','int8','uint64','float64','boolean','string','null']:
        for right in ['int64','mixed','dynamic','int8','uint64','float64','boolean','string','null']:
            valid=left==right and left in admitted
            p=call({'action':'resolve_operator','metadata':m,'request':{'operation':op,'left':left,'right':right}},None if valid else 'Unsupported operator')
            if valid:
                assert p['result_type']==left and p['operand_type']==left
                assert p['conversion']=='identity' and p['evaluation']=='ordered_eager_left_then_right'
                assert p['api']==op+'_'+left
                apis.append(p['api'])
base={'metadata':m,'request':{'roots':[],'apis':apis},'imports':{t:pub['types'][t] for t in ['mixed','dynamic']},'leaves':{},'layouts':{},'names':{}}
p=call(base); assert len(p['units'])==16 and all('template' not in u['header'] for u in p['units'])
base['api_imports']=pub['apis']; assert call(base)['units']==[]
print('PASS: 486 binary admission cases, 16 template-free adapters, native return transport and zero-unit reuse')
