"""Check generic shared-function recipes and ordinary publication reuse."""
import copy
import json
from pathlib import Path
import subprocess
import sys
root=Path(__file__).resolve().parents[3]
metadata=json.loads((root/'runtime/metadata/public_api.json').read_text())
published=json.loads((root/'runtime/public/specializations.json').read_text())
variants=metadata['function_sets']['condition_truthy']['variants']
base={'metadata':metadata,'request':{'roots':[],'apis':['condition_truthy_'+v for v in variants]},
      'leaves':{},'imports':{k:published['types'][k] for k in ['float64','mixed','dynamic']},'layouts':{},'names':{}}
def call(data,error=None):
    p=subprocess.run([sys.argv[1]],input=json.dumps(data),text=True,capture_output=True)
    if error:
        assert p.returncode and error in p.stderr,p.stderr
        return
    assert p.returncode==0,p.stderr
    return json.loads(p.stdout)
plan=call(base)
assert len(plan['units'])==len(variants)==14
assert all('template' not in u['header'] for u in plan['units'])
assert all('condition_truthy(' in u['source'] for u in plan['units'])
reuse=copy.deepcopy(base);reuse['api_imports']=plan['apis']
assert call(reuse)['units']==[]
reuse['api_imports']['condition_truthy_string']['namespace']='bad'
call(reuse,'Conflicting API ownership')
# Renaming the set and function proves expansion is independent of operation names.
changed=copy.deepcopy(base)
recipe=changed['metadata']['function_sets'].pop('condition_truthy')
recipe['function']['operation']['name']='observe'
changed['metadata']['function_sets']['observation']=recipe
changed['request']['apis']=['observation_string']
assert 'observe(' in call(changed)['units'][0]['header']
for change,error in [
    (lambda r:r.update(unknown=True),'unknown field'),
    (lambda r:r['variants']['string'].update(operand='${variant:operand}'),'Recursive variant'),
    (lambda r:r['function']['operation'].update(expression='${variant:missing}'),'Missing metadata field'),
    (lambda r:r['function']['operation'].update(expression='${variant:operand'),'Unclosed variant'),
]:
    invalid=copy.deepcopy(base);change(invalid['metadata']['function_sets']['condition_truthy']);call(invalid,error)
invalid=copy.deepcopy(base)
invalid['metadata']['functions']['condition_truthy_string']=plan['apis']['condition_truthy_string']
call(invalid,'Duplicate global operator ownership')
invalid=copy.deepcopy(base)
invalid['metadata']['function_sets']['duplicate']=copy.deepcopy(invalid['metadata']['function_sets']['condition_truthy'])
invalid['request']['apis']=['condition_truthy_string','duplicate_string']
call(invalid,'Conflicting generated output path')
print('PASS: generic function-set expansion, substitutions, template-free headers, ownership reuse and rejection')
