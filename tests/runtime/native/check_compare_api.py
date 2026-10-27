"""Spaceship normalization and exact floating admission remain metadata-owned."""
import copy
import json
from pathlib import Path
import subprocess
import sys
root=Path(__file__).resolve().parents[3]
sys.path.insert(0,str(root/'runtime/tools'))
import concrete_types
m=concrete_types.load_api_metadata(root/'runtime')
pub=json.loads((root/'runtime/public/specializations.json').read_text())
signed=['int8','int16','int32','int64']; unsigned=['uint8','uint16','uint32','uint64']
types=signed+unsigned+['float64','mixed','dynamic','boolean','string','null']
def call(data,error=None):
    r=subprocess.run([sys.argv[1]],input=json.dumps(data),text=True,capture_output=True)
    if error:
        assert r.returncode and error in r.stderr,r.stderr
        return
    assert r.returncode==0,r.stderr
    return json.loads(r.stdout)
def resolve(a,b,meta=m,error=None):
    return call({'action':'resolve_operator','metadata':meta,'request':{'operation':'compare','left':a,'right':b}},error)
apis=set()
for a in types:
    for b in types:
        selected=None
        for family,maximum in [(signed,'int64'),(unsigned,'uint64')]:
            if a in family and b in family: selected=a if a==b else maximum
        if (a,b) in [('float64','float64'),('int64','float64'),('float64','int64')]: selected='float64'
        p=resolve(a,b,error=None if selected else 'Unsupported operator')
        if selected:
            assert p['operand_type']==selected and p['result_type']=='int64'
            assert p['api']=='compare_'+selected and p['evaluation']=='ordered_eager_left_then_right'
            apis.add(p['api'])
changed=copy.deepcopy(m)
changed['global_operators']['groups']['numeric_spaceship']['rules'][3]['left_types']=['uint64']
resolve('int64','float64',changed,error='outside its domain')
changed=copy.deepcopy(m)
changed['global_operators']['groups']['numeric_spaceship']['rules'][3]['left_types']=['int64','int64']
resolve('int64','float64',changed,error='Duplicate operator type restriction')
changed=copy.deepcopy(m)
changed['global_operators']['groups']['numeric_spaceship']['rules'][3]['left_types']=['int32']
assert resolve('int32','float64',changed)['operand_type']=='float64'
resolve('int64','float64',changed,error='Unsupported operator')
base={'metadata':m,'request':{'roots':[],'apis':sorted(apis)},'leaves':{},'imports':{'float64':pub['types']['float64']},'layouts':{},'names':{}}
p=call(base); assert len(p['units'])==9 and all('template' not in u['header'] for u in p['units'])
base['api_imports']=pub['apis']; assert call(base)['units']==[]
print('PASS: 196 spaceship admission cases, metadata-controlled restrictions, nine adapters and zero-unit reuse')
