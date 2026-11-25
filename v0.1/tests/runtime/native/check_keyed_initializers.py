"""Concrete keyed factories share metadata publication and native adaptation."""
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
names = {'hash_keyed_initializer<string,string>':'string_factory',
         'hash<string,string>':'string_hash',
         'initializer_entry<string,string>':'string_entry',
         'initializer_buffer<initializer_entry<string,string>>':'string_entries',
         'hash_keyed_initializer<int64,int64>':'integer_factory',
         'hash<int64,int64>':'integer_hash',
         'initializer_entry<int64,int64>':'integer_entry',
         'initializer_buffer<initializer_entry<int64,int64>>':'integer_entries'}
request = {'package':'application.keyed_factories',
           'roots':['hash_keyed_initializer<string>','hash_keyed_initializer<int64,int64>',
                    'mixed_keyed_initializer','dynamic_keyed_initializer'], 'names':names}
published = json.loads((root/'runtime/public/specializations.json').read_text())
envelope = {'metadata':concrete_types.metadata(root/'runtime'), 'request':request,
            'names':{**{k:v['name'] for k,v in published['types'].items()}, **names},
            'imports':published['types'], 'layouts':{}, 'leaves':{}}
result = subprocess.run([str(args.tool)], input=json.dumps(envelope), text=True, capture_output=True)
assert result.returncode == 0, result.stderr
plan = json.loads(result.stdout)
assert plan['types']['mixed_keyed_initializer']['owner']=='runtime.core'
assert plan['types']['dynamic_keyed_initializer']['owner']=='runtime.core'
for unit in plan['units']:
    assert 'template' not in unit['header']
    if unit['identity'].startswith(('hash_keyed_initializer<',)):
        assert 'static ' in unit['header'] and 'from_key_value(' in unit['header']
envelope['request']={'package':'invalid','roots':['hash_keyed_initializer<int64,boolean>']}
result=subprocess.run([str(args.tool)],input=json.dumps(envelope),text=True,capture_output=True)
assert result.returncode and 'Unsatisfied family capabilities' in result.stderr
print('PASS: keyed recipes, string-key default, invalid key rejection and runtime ownership')
if not args.core:
    sys.exit(0)
cache = args.core.resolve().parent/'CMakeCache.txt'
assert cache.exists(), 'Core build configuration is required for the key-policy proof'
configuration = cache.read_text()
assert any('SCPP_LANGUAGE_TARGET_PHP:BOOL='+mode in configuration for mode in ['ON', 'OFF'])
php_keys = 'SCPP_LANGUAGE_TARGET_PHP:BOOL=ON' in configuration
policy_flags = ['-DSCPP_LANGUAGE_TARGET_PHP=1'] if php_keys else []
caller = (root/'tests/runtime/native/test_public_keyed_initializers.cpp').read_text()
with tempfile.TemporaryDirectory(prefix='scpp-keyed-factories-') as directory:
    work = Path(directory)
    sdk = work/'runtime'
    for name in ['public', 'include', 'metadata']:
        shutil.copytree(root/'runtime'/name, sdk/name)
    (sdk/'lib').mkdir(); (sdk/'tools').mkdir()
    shutil.copy2(args.core, sdk/'lib/libcore.a')
    shutil.copy2(args.tool, sdk/'tools/scpp_api_generator')
    os.environ['SCPP_API_GENERATOR'] = str(sdk/'tools/scpp_api_generator')
    if php_keys:
        # Publish the matching target through the ordinary generator, never relabel a manifest.
        configured = concrete_types.generate(root/'runtime/specs/public_specializations.json', sdk, sdk, [], 'clang++', policy_flags)
        for source in configured['sources']:
            assert (sdk/source).read_bytes() == (root/'runtime'/source).read_bytes(), 'Configured generation changed the implementation compiled into the supplied core'
    seal_package(sdk, ['core'])
    request_file = work/'request.json'; request_file.write_text(json.dumps(request))
    app = work/'app'
    manifest = concrete_types.generate(request_file, app, sdk, [sdk], 'clang++', policy_flags)
    for header in ['string_factory', 'integer_factory', 'mixed_keyed_initializer', 'dynamic_keyed_initializer']:
        subprocess.run(['clang++', '-std=c++23', '-I'+str(app/'public'), '-I'+str(sdk/'public'),
                        '-x', 'c++', '-fsyntax-only', '-'], input='#include "scpp/'+header+'.hpp"\n', text=True, check=True)
    flags = ['-fsanitize=address,undefined', '-fno-omit-frame-pointer', '-fno-sanitize-recover=all'] if args.sanitize else []
    common = ['clang++', '-std=c++23', '-O1' if args.sanitize else '-O2', *flags, *policy_flags,
              '-DSCPP_TEST_PHP_KEYS='+str(int(php_keys)),
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
    subprocess.run([*common, '-I'+str(sdk/'include'), '-c', str(root/'tests/runtime/native/keyed_initializer_reference.cpp'), '-o', str(work/'reference.o')], check=True)
    subprocess.run(['clang++', *flags, str(work/'reference.o'), str(work/'caller.o'), *map(str, objects), str(args.core.resolve()), '-pthread', '-o', str(work/'proof')], check=True)
    subprocess.run([str(work/'proof')], check=True)
    (app/'lib').mkdir(); subprocess.run(['ar', 'rcs', str(app/'lib/libfactories.a'), *map(str, objects)], check=True)
    seal_package(app, ['factories'])
    reused = concrete_types.generate(request_file, work/'reuse', sdk, [sdk, app], 'clang++', policy_flags)
    assert not reused['sources']
    print(f'PASS: sealed factory reuse (PHP keys={php_keys}); parallel storage compile={storage_time:.4f}s caller compile={caller_time:.4f}s')
