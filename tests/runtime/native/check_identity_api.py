"""Validate same-type strict identity publication without arithmetic normalization."""
import json
from pathlib import Path
import subprocess
import sys
root=Path(__file__).resolve().parents[3]
m=json.loads((root/'runtime/metadata/public_api.json').read_text())
pub=json.loads((root/'runtime/public/specializations.json').read_text())
expected={'int64','float64','boolean','string','null','mixed','dynamic'}
apis=[]
for name in ['strict_identical','strict_not_identical']:
    recipe=m['function_sets'][name]
    assert set(recipe['variants'])==expected
    assert all(p['type']=='${variant}' for p in recipe['function']['operation']['parameters'])
    apis += [name+'_'+v for v in expected]
base={'metadata':m,'request':{'roots':[],'apis':apis},'leaves':{},
      'imports':{n:pub['types'][n] for n in ['float64','mixed','dynamic']},'layouts':{},'names':{}}
def call(data,error=None):
    p=subprocess.run([sys.argv[1]],input=json.dumps(data),text=True,capture_output=True)
    if error:
        assert p.returncode and error in p.stderr,p.stderr
        return
    assert p.returncode==0,p.stderr
    return json.loads(p.stdout)
plan=call(base)
assert len(plan['units'])==14
assert all('template' not in u['header'] for u in plan['units'])
assert all('scpp::identical(' in u['source'] or 'scpp::not_identical(' in u['source'] for u in plan['units'])
base['api_imports']=plan['apis'];assert call(base)['units']==[]
base['request']['apis']=['strict_identical_uint64'];call(base,'Missing metadata field')
print('PASS: same-type identity signatures, native authority, unchanged representations and import reuse')
