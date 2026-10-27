"""Concrete positional factories share metadata publication and native adaptation."""
import argparse
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
names = {'vector_list_initializer<string>':'vector_factory',
         'hash_list_initializer<int64,int64>':'hash_factory',
         'hash<int64,int64>':'integer_hash',
         'initializer_buffer<string>':'string_input',
         'initializer_buffer<int64>':'integer_input'}
request = {'package':'application.list_factories',
           'roots':['vector_list_initializer<string>','hash_list_initializer<int64>',
                    'mixed_list_initializer','dynamic_list_initializer'], 'names':names}
published = json.loads((root/'runtime/public/specializations.json').read_text())
envelope = {'metadata':concrete_types.metadata(root/'runtime'), 'request':request,
            'names':{**{k:v['name'] for k,v in published['types'].items()}, **names},
            'imports':published['types'], 'layouts':{}, 'leaves':{}}
result = subprocess.run([str(args.tool)], input=json.dumps(envelope), text=True, capture_output=True)
assert result.returncode == 0, result.stderr
plan = json.loads(result.stdout)
assert plan['types']['mixed_list_initializer']['owner']=='runtime.core'
assert plan['types']['dynamic_list_initializer']['owner']=='runtime.core'
for unit in plan['units']:
    assert 'template' not in unit['header']
    if unit['identity'].startswith(('vector_list_initializer<','hash_list_initializer<')):
        assert 'static ' in unit['header'] and 'from_list(' in unit['header']
envelope['request']={'package':'invalid','roots':['hash_list_initializer<int64,string>']}
result=subprocess.run([str(args.tool)],input=json.dumps(envelope),text=True,capture_output=True)
assert result.returncode and 'Unsatisfied family capabilities' in result.stderr
print('PASS: shared factory recipes, hash default, string-key list rejection and runtime ownership')
if not args.core:
    sys.exit(0)
caller = (root/'tests/runtime/native/test_public_list_initializers.cpp').read_text()
with tempfile.TemporaryDirectory(prefix='scpp-list-factories-') as directory:
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
    for header in ['vector_factory', 'hash_factory', 'mixed_list_initializer', 'dynamic_list_initializer']:
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
    (app/'lib').mkdir(); subprocess.run(['ar', 'rcs', str(app/'lib/libfactories.a'), *map(str, objects)], check=True)
    seal_package(app, ['factories'])
    reused = concrete_types.generate(request_file, work/'reuse', sdk, [sdk, app], 'clang++', [])
    assert not reused['sources']
    print(f'PASS: sealed factory reuse; parallel storage compile={storage_time:.4f}s caller compile={caller_time:.4f}s')
