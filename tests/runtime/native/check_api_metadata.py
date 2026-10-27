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
