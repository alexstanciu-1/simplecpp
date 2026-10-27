"""Exercise admission, normalization and ownership without target compilations."""
import copy
import json
from pathlib import Path
import subprocess
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[3] / 'runtime/tools'))
import concrete_types
root = Path(__file__).resolve().parents[3]
tool = sys.argv[1]
metadata = concrete_types.metadata(root/'runtime')
def call(data, error=None):
    result = subprocess.run([tool], input=json.dumps(data), text=True, capture_output=True)
    if error:
        assert result.returncode and error in result.stderr, result.stderr
        return
    assert result.returncode == 0, result.stderr
    return json.loads(result.stdout)
def resolve(left, right, operation='add', error=None, meta=metadata):
    return call({'action':'resolve_operator','metadata':meta,'request':{'operation':operation,'left':left,'right':right}}, error)
for left,right,selected in [('int8','int8','int8'),('int8','int16','int64'),('uint16','uint32','uint64'),('int32','float64','float64'),('uint64','float64','float64')]:
    for a,b in [(left,right),(right,left)]:
        plan=resolve(a,b)
        assert plan['operand_type']==selected and plan['result_type']==selected
for a,b in [('int8','uint8'),('uint64','int64'),('boolean','int32'),('null','int32'),('string','int64'),('mixed','int64')]:
    resolve(a,b,error='Unsupported operator')
assert resolve('mixed','mixed','equal')['result_type']=='boolean'
resolve('mixed','dynamic','equal',error='Unsupported operator')
changed=copy.deepcopy(metadata)
changed['global_operators']['domains']['signed']['maximum']='int32'
assert resolve('int8','int16',meta=changed)['operand_type']=='int32'
changed['global_operators']['groups']['numeric']['rules'].append(copy.deepcopy(changed['global_operators']['groups']['numeric']['rules'][0]))
resolve('int8','int16',meta=changed,error='Ambiguous')
base={'metadata':metadata,'request':{'roots':[],'apis':['add_int64','add_int64','add_uint64']},'leaves':{},'imports':{},'layouts':{},'names':{}}
plan=call(base)
assert len(plan['units'])==2
assert all('template<' not in x['header'] for x in plan['units'])
assert 'numeric_add' in plan['units'][0]['source']
# The existing publication path owns normalized signatures, independent of callers.
base['api_imports']=plan['apis']
assert call(base)['units']==[]
base['api_imports']['add_int64']['namespace']='wrong'
call(base,error='Conflicting API ownership')
print('PASS: global admission, max-family promotion, metadata-driven selection, normalized emission and ownership reuse')

changed=copy.deepcopy(metadata)
changed['global_operators']['operations']['another_add']=copy.deepcopy(changed['global_operators']['operations']['add'])
invalid=copy.deepcopy(base); invalid['api_imports']={}; invalid['metadata']=changed
call(invalid,error='Duplicate normalized operator signature')
assert resolve('mixed','mixed','equal')['conversion']=='identity'
print('PASS: duplicate normalized signatures reject; boxed operands retain identity')

# A1 shares the same normalization across ordinary arithmetic and comparisons.
for op in ['subtract','multiply','divide','remainder','numeric_equal','numeric_not_equal','less','less_equal','greater','greater_equal']:
    plan=resolve('int16','int32',op)
    assert plan['operand_type']=='int64'
    assert plan['result_type']==('boolean' if op in ['numeric_equal','numeric_not_equal','less','less_equal','greater','greater_equal'] else 'int64')
    resolve('int16','uint32',op,error='Unsupported operator')
    resolve('boolean','int32',op,error='Unsupported operator')
    if op=='remainder': resolve('float64','float64',op,error='Unsupported operator')
    else: assert resolve('uint64','float64',op)['operand_type']=='float64'
print('PASS: A1 arithmetic/comparison normalization and integer-only remainder')
