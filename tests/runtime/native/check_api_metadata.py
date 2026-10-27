"""Validate metadata-driven shape and errors without compiling generated C++."""
import argparse
import copy
import importlib.util
import json
from pathlib import Path
import subprocess
import sys

root = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(root/'runtime/tools'))
import concrete_types
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--tool',type=Path,required=True)
args = parser.parse_args()
base={'metadata':concrete_types.metadata(root/'runtime'),
      'request':json.loads((root/'runtime/specs/public_specializations.json').read_text()),
      'leaves':{},'imports':{},'layouts':{},'names':{'vector<string>':'vector_string','result<vector<string>>':'result_vector_string'}}

def invoke(data, failure=None):
    result=subprocess.run([str(args.tool)],input=json.dumps(data),text=True,capture_output=True)
    if failure:
        assert result.returncode and failure in result.stderr, result.stderr
        return
    assert result.returncode == 0, result.stderr
    return json.loads(result.stdout)

base['request']['roots'] = ['result<vector<string>>']
plan=invoke(base)
assert len(plan['units']) == 3
assert 'vector<string>' in plan['types']
# No type-name dispatch: a newly named family with the same metadata works unchanged.
renamed=copy.deepcopy(base)
renamed['metadata']['families']={'sequence':renamed['metadata']['families']['vector']}
renamed['request']={'package':'application.middleware','roots':['sequence<string>']}
renamed['names']={}
result=invoke(renamed)
unit=result['units'][0]
assert 'sequence_0string_' in unit['header']
assert 'vector_t<scpp::string>' in unit['probes'][0]['native']
assert 'std::is_copy_constructible_v<scpp::string>' in unit['checks']
assert 'cursor_' in unit['header'] and 'value_copy()' in unit['source']
# Snippet changes affect emitted implementation without a generator change.
changed=copy.deepcopy(renamed)
changed['metadata']['families']['sequence']['classes'][0]['methods'][0]['expression']='${self}.capacity()'
assert invoke(changed)['units'][0]['source'] != unit['source']
# Unknown fields/placeholders must reject rather than silently dropping a capability.
for mutate,message in [
    (lambda x:x['metadata']['types']['string'].update(borow='typo'),'unknown field'),
    (lambda x:x['metadata']['families']['vector']['classes'].append(copy.deepcopy(x['metadata']['families']['vector']['classes'][0])),'Duplicate class identity'),
    (lambda x:x['metadata']['families']['vector'].update(unrecognized=True),'unknown field'),
    (lambda x:x['metadata']['families']['vector']['classes'][0]['methods'][0].update(expression='${missing}'),'Unknown metadata substitution'),
    (lambda x:x['metadata']['families']['vector']['classes'][0]['lifecycle'].update(move='guess'),'Unknown lifecycle policy'),
    (lambda x:x['metadata']['families']['vector']['classes'][0].update(methods='wrong'),'methods must be an array'),
    (lambda x:x['leaves'].update(string={'cpp':'scpp::string','header':'wrong.hpp','owner':'runtime.core'}),'Conflicting leaf identity'),
    (lambda x:x['request'].update(roots=['unregistered<string>']),'Unregistered type'),
    (lambda x:x['metadata']['families']['vector']['classes'][0]['methods'][0].update(transport='unknown'),'Unknown result transport'),
]:
    invalid=copy.deepcopy(base); mutate(invalid); invoke(invalid,message)
# A missing signature dependency is a graph error before any target compilation.
invalid=copy.deepcopy(base)
invalid['metadata']['families']['vector']['classes'][0]['methods'][0]['returns']='missing'
invoke(invalid,'Unregistered type')
print('PASS: unknown family recipe, companion lifecycle, snippet emission and metadata rejection; no generated compilation')

# Defaults are expanded before naming and scheduling, including chained dependencies.
defaults = copy.deepcopy(base)
sequence = copy.deepcopy(defaults['metadata']['families']['vector'])
sequence['defaults'] = ['string']
nested = json.loads(json.dumps(sequence).replace('"T"', '"V"').replace('${public:T}', '${public:V}').replace('${native:T}', '${native:V}'))
nested['parameters'] = ['T', 'U', 'V']
nested['defaults'] = ['string', 'sequence<T>', 'U']
defaults['metadata']['families'].update(sequence=sequence, nested=nested)
defaults['request'] = {'package':'test', 'roots':['nested', 'nested<>', 'nested<string>', 'nested<string,sequence<string>>', 'nested<string,sequence<string>,sequence<string>>']}
defaults['names'] = {'nested':'nested_strings', 'sequence<string>':'sequence_string'}
result = invoke(defaults)
canonical = 'nested<string,sequence<string>,sequence<string>>'
assert [u['identity'] for u in result['units']] == ['sequence<string>', canonical]
assert result['types'][canonical]['name'] == 'nested_strings'
assert result['types'][canonical]['native'] == 'scpp::vector_t<scpp::sequence_string>'
# Explicit earlier arguments override defaults and are used by later defaults.
explicit = copy.deepcopy(defaults)
explicit['request']['roots'] = ['nested<vector<string>>']
result = invoke(explicit)
assert 'nested<vector<string>,sequence<vector<string>>,sequence<vector<string>>>' in result['types']
# The same canonical identity imports as a single already-published specialization.
reused = copy.deepcopy(defaults)
reused['imports'] = invoke(defaults)['types']
for key, record in reused['imports'].items(): reused['names'][key] = record['name']
assert invoke(reused)['units'] == []
for change, message in [
    (lambda f:f.update(defaults='string'), 'Invalid trailing defaults'),
    (lambda f:f.update(defaults=['string']*4), 'Invalid trailing defaults'),
    (lambda f:f.update(defaults=['T', 'sequence<T>', 'U']), 'earlier parameter'),
    (lambda f:f.update(defaults=['V', 'sequence<T>', 'U']), 'earlier parameter'),
    (lambda f:f.update(defaults=['string', 'T<string>', 'U']), 'template family'),
    (lambda f:f.update(parameters=['T','T','V']), 'Duplicate parameter'),
    (lambda f:f.update(defaults=['missing', 'sequence<T>', 'U']), 'Unregistered type'),
    (lambda f:f.update(defaults=['nested', 'sequence<T>', 'U']), 'expansion limit'),
]:
    invalid = copy.deepcopy(defaults)
    change(invalid['metadata']['families']['nested'])
    invoke(invalid, message)
invalid = copy.deepcopy(defaults)
invalid['request']['roots'] = ['string<>']
invoke(invalid, 'Unregistered type')
print('PASS: constant/dependent/chained defaults, explicit overrides, canonical reuse and invalid defaults')
invalid = copy.deepcopy(defaults)
invalid['names'][canonical] = 'conflicting_name'
invoke(invalid, 'Conflicting mapping')
invalid = copy.deepcopy(defaults)
invalid['names'] = {}
invalid['request']['roots'] = [canonical]
invalid['metadata']['families']['nested']['defaults'][0] = 'T'
invoke(invalid, 'earlier parameter')

# Conditional members are selected from declared capabilities, not family names.
hashes=copy.deepcopy(base)
hashes['request']={'package':'test','roots':['hash<string>', 'hash<string,int64>', 'hash<vector<string>>']}
result=invoke(hashes)
units={u['identity']:u for u in result['units']}
assert ' append(' not in units['hash<string,string>']['header']
assert ' append(' in units['hash<string,int64>']['header']
assert 'nullable<vector<string>>' in units
assert any('integer_key<scpp::int64>' in c for c in units['hash<string,int64>']['checks'])
# Rename both family and capability: no generator update is required.
renamed=copy.deepcopy(hashes)
renamed['metadata']['families']['table']=renamed['metadata']['families'].pop('hash')
renamed['metadata']=json.loads(json.dumps(renamed['metadata']).replace('"integer_key"','"append_key"'))
renamed['request']['roots']=['table<string,int64>']
assert ' append(' in invoke(renamed)['units'][-1]['header']
# Nonselected operations do not materialize dependencies or expand snippets.
skipped=copy.deepcopy(hashes)
skipped['request']['roots']=['hash<string>']
for op in skipped['metadata']['families']['hash']['classes'][0]['methods']:
    if op.get('name')=='append':op['returns']='absent';op['expression']='${absent}'
invoke(skipped)
for change,message in [
    (lambda x:x['metadata']['types']['string'].update(capabilities=['typo']), 'Unknown capability'),
    (lambda x:x['metadata']['types']['string'].update(capabilities='typed_hash_key'), 'capabilities must be an array'),
    (lambda x:x['metadata']['families']['hash'].update(requires_capabilities={'missing':['typed_hash_key']}), 'Unknown capability parameter'),
    (lambda x:x['metadata']['families']['hash'].update(requires_capabilities={'K':['typo']}), 'Unknown capability'),
    (lambda x:x['metadata']['families']['hash'].update(requires_capabilities={'K':'typed_hash_key'}), 'capabilities must be an array'),
    (lambda x:x['request'].update(roots=['hash<string,boolean>']), 'Unsatisfied family capabilities'),
    (lambda x:x['metadata']['capabilities']['integer_key'].update(checks=[]), 'Capability requires nonempty checks'),
]:
    invalid=copy.deepcopy(hashes);change(invalid);invoke(invalid,message)
reused=copy.deepcopy(hashes)
reused['imports']=result['types']
for key,record in reused['imports'].items():reused['names'][key]=record['name']
assert invoke(reused)['units']==[]
print('PASS: typed hash/nullable closure, generic capability selection, rejected keys and import reuse')

# Mixed and float are zero-parameter recipes; their names are not generator cases.
boxed=copy.deepcopy(base)
boxed['request']={'package':'test','roots':['mixed']}
result=invoke(boxed)
assert [u['identity'] for u in result['units']]==['float64','mixed']
assert result['types']['mixed']['native']=='scpp::mixed_t'
assert 'null_index_read' in result['units'][-1]['source']
assert 'bound_range' in result['units'][-1]['probes'][1]['native']
assert 'template' not in result['units'][-1]['header']
renamed=copy.deepcopy(boxed)
renamed['metadata']['families']['boxed']=renamed['metadata']['families'].pop('mixed')
renamed['request']['roots']=['boxed']
assert invoke(renamed)['types']['boxed']['native']=='scpp::mixed_t'
print('PASS: zero-parameter scalar/boxed recipes, owned extraction and binding-aware cursor')

reused=copy.deepcopy(boxed)
reused['imports']=result['types']
for key,record in reused['imports'].items(): reused['names'][key]=record['name']
assert invoke(reused)['units']==[]
print('PASS: canonical zero-parameter recipe imports do not create shadowing aliases')

# Dynamic adds a recipe and existing scalar conversion bindings, not generator branches.
dynamic=copy.deepcopy(base)
dynamic['request']={'package':'test','roots':['dynamic']}
result=invoke(dynamic)
assert [u['identity'] for u in result['units']]==['float64','mixed','dynamic']
unit=result['units'][-1]
assert result['types']['dynamic']['native']=='scpp::dynamic_t<>'
assert 'to_dynamic(const scpp::mixed &' in unit['header']
assert 'to_mixed(const scpp::dynamic &' in unit['header']
assert 'scpp::mixed key()' in unit['header']
assert 'scpp::dynamic value()' in unit['header']
assert 'scpp::cast<' in unit['source'] and 'template' not in unit['header']
renamed=copy.deepcopy(dynamic)
renamed['metadata']['families']['shared_box']=renamed['metadata']['families'].pop('dynamic')
renamed['request']['roots']=['shared_box']
assert invoke(renamed)['types']['shared_box']['native']=='scpp::dynamic_t<>'
dynamic['imports']=result['types']
for key,record in dynamic['imports'].items():dynamic['names'][key]=record['name']
assert invoke(dynamic)['units']==[]
print('PASS: dynamic recipe, explicit conversion signatures, family-preserving iteration and import reuse')
