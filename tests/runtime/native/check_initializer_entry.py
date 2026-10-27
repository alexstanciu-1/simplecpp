"""Independent assignment lifecycle and immutable initializer entry composition."""
import argparse
import copy
from concurrent.futures import ThreadPoolExecutor
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import time

root = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(root/'runtime/tools'))
import concrete_types
from public_package import seal_package

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--tool', type=Path, required=True)
parser.add_argument('--core', type=Path)
parser.add_argument('--sanitize', action='store_true')
args = parser.parse_args()
names = {'initializer_entry<string>':'string_entry',
         'initializer_buffer<initializer_entry<string>>':'string_entry_input',
         'initializer_entry<int64,int64>':'integer_entry',
         'initializer_buffer<initializer_entry<int64,int64>>':'integer_entry_input'}
request={'package':'application.initializer_entries','roots':list(names),'names':names}
published=json.loads((root/'runtime/public/specializations.json').read_text())
envelope={'metadata':concrete_types.metadata(root/'runtime'),'request':request,
          'names':{**{k:v['name'] for k,v in published['types'].items()},**names},
          'imports':published['types'],'layouts':{},'leaves':{}}
def plan(data=envelope,error=None):
    result=subprocess.run([str(args.tool)],input=json.dumps(data),text=True,capture_output=True)
    if error:
        assert result.returncode and error in result.stderr,result.stderr
        return
    assert result.returncode==0,result.stderr
    return json.loads(result.stdout)
result=plan()
assert len(result['units'])==4
for unit in result['units']:
    assert 'template' not in unit['header']
    if unit['identity'].startswith('initializer_entry<'):
        life=unit['probes'][0]['lifecycle']
        assert life['copy']=='native' and life['move']=='native'
        assert life['copy_assignment']=='disabled' and life['move_assignment']=='disabled'
        assert result['types'][unit['identity']]['capabilities']==['copy_construct_storage']
# All names in the declared implication graph must be valid and acyclic.
for edges,error in [(['unknown'],'Unknown implied capability'),
                    (['copy_storage'],'Capability implication cycle'),
                    (['copy_construct_storage','copy_construct_storage'],'Duplicate implied capability'),
                    ('copy_construct_storage','Capability implies must be an array')]:
    invalid=copy.deepcopy(envelope)
    invalid['metadata']['capabilities']['copy_storage']['implies']=edges
    plan(invalid,error)
cycle=copy.deepcopy(envelope)
cycle['metadata']['capabilities']['copy_construct_storage']['implies']=['copy_storage']
plan(cycle,'Capability implication cycle')
# Transitive and diamond implication paths are general metadata, not type exceptions.
diamond=copy.deepcopy(envelope)
for name in ['left_copy','right_copy']:
    diamond['metadata']['capabilities'][name]={'checks':['true'],'implies':['copy_construct_storage']}
diamond['metadata']['capabilities']['copy_storage']['implies']=['left_copy','right_copy']
plan(diamond)
invalid=copy.deepcopy(envelope)
invalid['metadata']['families']['initializer_entry']['classes'][0]['lifecycle']['copy_assignment']='guess'
plan(invalid,'Unknown lifecycle policy')
# Overrides can independently enable assignment even when construction is disabled.
shape=copy.deepcopy(envelope)
shape['metadata']['families']['initializer_entry']['classes'][0]['lifecycle'].update(copy='disabled',copy_assignment='native')
shape['metadata']['families']['initializer_entry']['capabilities']=[]
shape['request']={'package':'shape.only','roots':['initializer_entry<string>']}
shape_plan=plan(shape)
header=shape_plan['units'][0]['header']
assert 'string_entry(const string_entry &other) = delete;' in header
assert 'string_entry &operator=(const string_entry &other);' in header
conditional=copy.deepcopy(shape)
conditional['metadata']['families']['initializer_entry']['classes'][0]['lifecycle']['copy_assignment']={'requires_capabilities':{'V':['integer_key']}}
assert plan(conditional)['units'][0]['probes'][0]['lifecycle']['copy_assignment']=='disabled'
conditional['metadata']['families']['initializer_entry']['classes'][0]['lifecycle']['copy_assignment']={'requires_capabilities':{'V':['copy_construct_storage']}}
assert plan(conditional)['units'][0]['probes'][0]['lifecycle']['copy_assignment']=='native'
print('PASS: capability implications, assignment overrides and immutable input composition')
if not args.core:
    sys.exit(0)
caller=(root/'tests/runtime/native/test_public_initializer_entry.cpp').read_text()
with tempfile.TemporaryDirectory(prefix='scpp-initializer-entry-') as directory:
    work = Path(directory)
    sdk = work/'runtime'
    for name in ['public', 'include', 'metadata']:
        shutil.copytree(root/'runtime'/name, sdk/name)
    (sdk/'lib').mkdir(); (sdk/'tools').mkdir()
    shutil.copy2(args.core, sdk/'lib/libcore.a')
    shutil.copy2(args.tool, sdk/'tools/scpp_api_generator')
    seal_package(sdk, ['core'])
    os.environ['SCPP_API_GENERATOR'] = str(sdk/'tools/scpp_api_generator')
    request_file = work/'request.json'; request_file.write_text(json.dumps(request))
    app = work/'app'
    manifest = concrete_types.generate(request_file, app, sdk, [sdk], 'clang++', [])
    for header in ['string_entry', 'integer_entry', 'string_entry_input', 'integer_entry_input']:
        subprocess.run(['clang++', '-std=c++23', '-I'+str(app/'public'), '-I'+str(sdk/'public'),
                        '-x', 'c++', '-fsyntax-only', '-'], input='#include "scpp/'+header+'.hpp"\n', text=True, check=True)
    flags = ['-fsanitize=address,undefined', '-fno-omit-frame-pointer', '-fno-sanitize-recover=all'] if args.sanitize else []
    common = ['clang++', '-std=c++23', '-O1' if args.sanitize else '-O2', *flags,
              '-I'+str(app/'public'), '-I'+str(sdk/'public')]
    def compile_unit(pair):
        index, source = pair
        output = work/('storage'+str(index)+'.o')
        subprocess.run([*common, '-I'+str(app/'include'), '-I'+str(sdk/'include'), '-c', str(app/source), '-o', str(output)], check=True)
        return output
    start = time.perf_counter()
    with ThreadPoolExecutor(max_workers=8) as pool:
        objects = list(pool.map(compile_unit, enumerate(manifest['sources'])))
    storage_time = time.perf_counter()-start
    (work/'caller.cpp').write_text(caller)
    start = time.perf_counter()
    subprocess.run([*common, '-I'+str(root/'tests/runtime/native'), '-c', str(work/'caller.cpp'), '-o', str(work/'caller.o')], check=True)
    caller_time = time.perf_counter()-start
    subprocess.run(['clang++', *flags, str(work/'caller.o'), *map(str, objects), str(args.core.resolve()), '-pthread', '-o', str(work/'proof')], check=True)
    subprocess.run([str(work/'proof')], check=True)
    (app/'lib').mkdir(); subprocess.run(['ar', 'rcs', str(app/'lib/libentries.a'), *map(str, objects)], check=True)
    seal_package(app, ['entries'])
    reused = concrete_types.generate(request_file, work/'reuse', sdk, [sdk, app], 'clang++', [])
    assert not reused['sources']
    print(f'PASS: sealed entry reuse; parallel storage compile={storage_time:.4f}s caller compile={caller_time:.4f}s')
