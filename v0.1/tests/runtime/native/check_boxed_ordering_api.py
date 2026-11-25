"""Global ordering exposes same-family boxes without widening other operand domains."""
import json
from pathlib import Path
import subprocess
import sys
root = Path(__file__).resolve().parents[3]
sys.path.insert(0,str(root/'runtime/tools'))
import concrete_types
metadata = concrete_types.load_api_metadata(root/'runtime')
pub = json.loads((root/'runtime/public/specializations.json').read_text())
def call(data, error=None):
    result = subprocess.run([sys.argv[1]], input=json.dumps(data), text=True, capture_output=True)
    if error:
        assert result.returncode and error in result.stderr, result.stderr
        return
    assert result.returncode == 0, result.stderr
    return json.loads(result.stdout)
apis = []
for operation in ['less', 'less_equal', 'greater', 'greater_equal']:
    for left in ['mixed', 'dynamic']:
        for right in ['mixed', 'dynamic', 'int64', 'float64', 'boolean', 'string', 'null']:
            data = {'action':'resolve_operator', 'metadata':metadata,
                    'request':{'operation':operation, 'left':left, 'right':right}}
            valid = left == right
            plan = call(data, None if valid else 'Unsupported operator')
            if plan:
                assert plan['operand_type'] == left and plan['result_type'] == 'boolean'
                assert plan['conversion'] == ('identity' if left == right else 'ordered_operand_conversion')
                assert plan['evaluation'] == 'ordered_eager_left_then_right'
                assert plan['api'] == operation+'_'+left
                apis.append(plan['api'])
base = {'metadata':metadata, 'request':{'roots':[], 'apis':apis},
        'imports':{t:pub['types'][t] for t in ['mixed','dynamic']}, 'leaves':{}, 'layouts':{}, 'names':{}}
units = call(base)['units']
assert len(units) == 8 and all('template' not in unit['header'] for unit in units)
base['api_imports'] = pub['apis']; assert call(base)['units'] == []
print('PASS: homogeneous boxed ordering admission, eight ordinary recipes, shared operation identities and publication reuse')

for operation in ['less', 'less_equal', 'greater', 'greater_equal']:
    for carrier in ['nullable<mixed>', 'result<dynamic>', 'result_or_false<mixed>', 'result_or_bool<dynamic>']:
        call({'action':'resolve_operator', 'metadata':metadata,
              'request':{'operation':operation, 'left':carrier, 'right':carrier}}, 'Unsupported carrier payload')
    for right in ['mixed', 'dynamic']:
        call({'action':'resolve_operator', 'metadata':metadata,
              'request':{'operation':operation, 'left':'int64', 'right':right}}, 'Unsupported operator')
print('PASS: carrier payload and reversed boxed/plain admission remain bounded')
