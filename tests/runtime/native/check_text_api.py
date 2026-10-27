"""Assert bounded text admission, native transport and ordinary publication reuse."""
import json
from pathlib import Path
import subprocess
import sys
root=Path(__file__).resolve().parents[3]
metadata=json.loads((root/'runtime/metadata/public_api.json').read_text())
published=json.loads((root/'runtime/public/specializations.json').read_text())
variants=metadata['function_sets']['concat_text']['variants']
assert set(variants)=={'int8','int16','int32','int64','uint8','uint16','uint32','float64','boolean','string','null'}
base={'metadata':metadata,'request':{'roots':[],'apis':['concat_text_'+v for v in variants]},
      'leaves':{},'imports':{'float64':published['types']['float64']},'layouts':{},'names':{}}
def call(data,error=None):
    p=subprocess.run([sys.argv[1]],input=json.dumps(data),text=True,capture_output=True)
    if error:
        assert p.returncode and error in p.stderr,p.stderr
        return
    assert p.returncode==0,p.stderr
    return json.loads(p.stdout)
plan=call(base)
assert len(plan['units'])==11
assert all('template' not in u['header'] for u in plan['units'])
assert all('scpp::php::to_string(' in u['source'] and 'string_access::make(' in u['source'] for u in plan['units'])
base['api_imports']=plan['apis']
assert call(base)['units']==[]
for excluded in ('uint64','mixed','dynamic','error_value'):
    base['request']['apis']=['concat_text_'+excluded]
    call(base,'Missing metadata field')
print('PASS: bounded scalar text domain, existing owned transport and published reuse')
