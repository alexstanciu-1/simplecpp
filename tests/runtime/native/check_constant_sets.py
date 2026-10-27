"""Constant sets use the shared API publication path and public literal types."""
import argparse
import copy
import json
from pathlib import Path
import subprocess
import sys
import tempfile
import time

root = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(root/'runtime/tools'))
import concrete_types
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--tool', type=Path, required=True)
parser.add_argument('--compile', action='store_true')
args = parser.parse_args()
metadata = concrete_types.metadata(root/'runtime')
base = {'metadata':metadata, 'request':{'roots':[], 'apis':['debug_flags']},
        'imports':{}, 'leaves':{}, 'names':{}, 'layouts':{}}
def plan(data=base, error=None):
    result = subprocess.run([str(args.tool)], input=json.dumps(data), text=True, capture_output=True)
    if error:
        assert result.returncode and error in result.stderr, result.stderr
        return
    assert result.returncode == 0, result.stderr
    return json.loads(result.stdout)
result = plan()
assert len(result['units']) == 1
constants = metadata['constant_sets']['debug_flags']['constants']
catalog = json.loads((root/'runtime/metadata/modules/core.json').read_text())
assert {c['name']:int(c['expression']) for c in constants} == {c['name']:c['value'] for c in catalog['constants']}
assert len(constants) == 19
assert 'template' not in result['units'][0]['header']
assert 'inline constexpr scpp::int64 DBG_DEFAULT{262343};' in result['units'][0]['header']
reuse = copy.deepcopy(base); reuse['api_imports'] = result['apis']
assert plan(reuse)['units'] == []
reuse['api_imports']['debug_flags']['constants'][0]['expression'] = '999'
plan(reuse, 'Conflicting API ownership')
for value, message in [([], 'nonempty constants'), ({}, 'nonempty constants')]:
    bad = copy.deepcopy(base); bad['metadata']['constant_sets']['debug_flags']['constants'] = value
    plan(bad, message)
bad = copy.deepcopy(base); bad['metadata']['constant_sets']['debug_flags']['constants'].append(constants[0])
plan(bad, 'Duplicate constant name')
bad = copy.deepcopy(base); bad['metadata']['constant_sets']['debug_flags']['constants'][0]['expression'] = ''
plan(bad, 'Constant requires expression')
bad = copy.deepcopy(base); bad['metadata']['constant_sets']['debug_flags']['constants'][0]['name'] = 'bad-name'
assert subprocess.run([str(args.tool)], input=json.dumps(bad), text=True, capture_output=True).returncode
bad = copy.deepcopy(base); bad['metadata']['functions']['debug_flags'] = metadata['functions']['cli_argc']
plan(bad, 'Duplicate generated API ownership')
# Different names, namespace and literal type require no debug-specific code.
custom = copy.deepcopy(base)
custom['metadata']['constant_sets']['fixture'] = {
    'namespace':'fixture', 'header':'fixture/constants.hpp', 'source':'include/fixture/constants.cpp',
    'constants':[{'name':'enabled','type':'boolean','expression':'true'},
                 {'name':'limit','type':'int32','expression':'23'}]}
custom['request']['apis'] = ['debug_flags','fixture']
custom_result = plan(custom)
assert len(custom_result['units']) == 2
print('PASS: 19 catalog flags, generic literal constants, metadata rejection and zero-unit API reuse')
if args.compile:
    with tempfile.TemporaryDirectory(prefix='scpp-constant-sets-') as directory:
        stage = Path(directory)
        for unit in custom_result['units']:
            path = stage/unit['header_path']; path.parent.mkdir(parents=True, exist_ok=True); path.write_text(unit['header'])
            path = stage/unit['source_path']; path.parent.mkdir(parents=True, exist_ok=True); path.write_text(unit['source'])
        code = '#include "public_api_guard.hpp"\n#include "scpp/debug/flags.hpp"\n#include "fixture/constants.hpp"\n'
        for constant in constants:
            code += f'static_assert(scpp::debug::{constant["name"]}.native_value() == {constant["expression"]});\n'
        code += 'static_assert(fixture::enabled.native_value());\nstatic_assert(fixture::limit.native_value()==23);\nint main() { return 0; }\n'
        caller = stage/'caller.cpp'; caller.write_text(code)
        start = time.perf_counter()
        subprocess.run(['clang++','-std=c++23','-O2','-I'+str(stage/'public'),
                        '-I'+str(root/'runtime/public'),'-I'+str(root/'tests/runtime/native'),
                        str(caller),*[str(stage/u['source_path']) for u in custom_result['units']],
                        '-o',str(stage/'proof')], check=True)
        elapsed = time.perf_counter()-start
        subprocess.run([str(stage/'proof')], check=True)
        print(f'PASS: public-only constexpr compile/link/run without runtime archive, total={elapsed:.4f}s')
