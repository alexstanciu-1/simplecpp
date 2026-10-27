"""Same global numeric operations preserve homogeneous boxed families."""
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
for operation in ['add', 'subtract', 'multiply', 'divide', 'remainder']:
    for left in ['mixed', 'dynamic']:
        for right in ['mixed', 'dynamic', 'int64', 'float64', 'boolean', 'string', 'null']:
            data = {'action':'resolve_operator', 'metadata':metadata,
                    'request':{'operation':operation, 'left':left, 'right':right}}
            valid = left == right or right == 'int64' or right == 'float64' and operation != 'remainder'
            plan = call(data, None if valid else 'Unsupported operator')
            if plan:
                assert plan['operand_type'] == plan['result_type'] == left
                assert plan['conversion'] == ('identity' if left == right else 'ordered_operand_conversion')
                assert plan['evaluation'] == 'ordered_eager_left_then_right'
                assert plan['api'] == operation+'_'+left
                apis.append(plan['api'])
base = {'metadata':metadata, 'request':{'roots':[], 'apis':apis},
        'imports':{t:pub['types'][t] for t in ['mixed','dynamic']}, 'leaves':{}, 'layouts':{}, 'names':{}}
units = call(base)['units']
assert len(units) == 10 and all('template' not in unit['header'] for unit in units)
base['api_imports'] = pub['apis']; assert call(base)['units'] == []
print('PASS: homogeneous boxed arithmetic admission, ten ordinary recipes, shared operation identities and publication reuse')
