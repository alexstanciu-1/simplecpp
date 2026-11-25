"""Generate, compile, measure and reuse typed hash/nullable metadata compositions."""
import argparse
import json
from pathlib import Path
import subprocess
import sys
import tempfile
import time

root = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(root/'runtime/tools'))
import concrete_types
from public_package import seal_package

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--core', type=Path, required=True)
parser.add_argument('--sanitize', action='store_true')
parser.add_argument('--output', type=Path)
args = parser.parse_args()
metrics = {}
def execute(command, **kwargs):
    subprocess.run([str(x) for x in command], check=True, **kwargs)

with tempfile.TemporaryDirectory(prefix='scpp-typed-hash-') as temporary:
    work = Path(temporary)
    package = work/'package'
    (package/'public').mkdir(parents=True)
    (package/'public/record.hpp').write_text('''#pragma once
#include "scpp/integers.hpp"
struct record { scpp::int64 value; };
''')
    request = {'package':'test.hash', 'roots':['hash<string>', 'hash<string,int64>', 'hash<vector<string>>', 'hash<record>'],
               'names':{'hash<string>':'hash_strings','hash<string,int64>':'hash_integers',
                        'hash<vector<string>>':'hash_vectors','hash<record>':'hash_records',
                        'vector<string>':'test_vector_string','nullable<string>':'nullable_string'},
               'leaves':{'record':{'cpp':'record','header':'record.hpp','owner':'test.hash'}}}
    config = work/'request.json'
    config.write_text(json.dumps(request))
    flags = ['-fsanitize=address,undefined','-fno-omit-frame-pointer'] if args.sanitize else []
    start = time.perf_counter()
    manifest = concrete_types.generate(config, package, root/'runtime', [], 'clang++', flags)
    metrics['generation_and_layout_probes_seconds'] = time.perf_counter()-start
    assert len(manifest['sources']) == 8
    for path in (package/'public/scpp').glob('*.hpp'):
        assert 'template' not in path.read_text(), path
    assert ' append(' not in (package/'public/scpp/hash_strings.hpp').read_text()
    assert ' append(' in (package/'public/scpp/hash_integers.hpp').read_text()
    (work/'CMakeLists.txt').write_text('''cmake_minimum_required(VERSION 3.20)
project(hash_proof LANGUAGES CXX)
set(CMAKE_CXX_STANDARD 23)
if(SANITIZE)
  add_compile_options(-fsanitize=address,undefined -fno-omit-frame-pointer)
  add_link_options(-fsanitize=address,undefined)
endif()
add_library(storage STATIC ${GENERATED_SOURCES})
target_include_directories(storage PUBLIC "${PACKAGE}/public" "${RUNTIME}/public" PRIVATE "${RUNTIME}/include")
add_library(native_fixture OBJECT "${TEST_ROOT}/typed_hash_fixture.cpp")
target_include_directories(native_fixture PRIVATE "${PACKAGE}/public" "${RUNTIME}/public" "${RUNTIME}/include")
add_executable(proof "${TEST_ROOT}/test_typed_hash.cpp" $<TARGET_OBJECTS:native_fixture>)
target_link_libraries(proof PRIVATE storage "${CORE}")
target_link_options(proof PRIVATE -fuse-ld=mold)
''')
    execute(['cmake','-S',work,'-B',work/'build','-G','Ninja','-DCMAKE_CXX_COMPILER=clang++','-DCMAKE_BUILD_TYPE=Release',
             '-DCMAKE_EXPORT_COMPILE_COMMANDS=ON','-DRUNTIME='+str(root/'runtime'),'-DPACKAGE='+str(package),
             '-DTEST_ROOT='+str(root/'tests/runtime/native'),'-DCORE='+str(args.core.resolve()),
             '-DSANITIZE='+('ON' if args.sanitize else 'OFF'),
             '-DGENERATED_SOURCES='+';'.join(str(package/p) for p in manifest['sources'])])
    start = time.perf_counter()
    execute(['cmake','--build',work/'build','-j8'])
    metrics['fixture_build_seconds'] = time.perf_counter()-start
    execute([work/'build/proof'])
    commands = json.loads((work/'build/compile_commands.json').read_text())
    public = next(c for c in commands if c['file'].endswith('/test_typed_hash.cpp'))
    assert str(root/'runtime/include') not in public['command']
    # One matched caller compile per path, without repeating the heavy adapters.
    if not args.sanitize:
        for name,code,private in [
            ('public','#include "scpp/hash_integers.hpp"\nscpp::string read(const scpp::hash_integers &h) { return h.at(scpp::int64(1)); }\n',False),
            ('native','#include "scpp/detail/concrete_keys.hpp"\nscpp::string read(const scpp::hash_t<scpp::string,scpp::int64> &h) { return h.at(scpp::int64(1)); }\n',True)]:
            source = work/(name+'.cpp');source.write_text(code)
            command=['clang++','-std=c++23','-O2','-I'+str(package/'public'),'-I'+str(root/'runtime/public')]
            if private: command += ['-I'+str(root/'runtime/include')]
            start=time.perf_counter()
            execute([*command,'-c',source,'-o',work/(name+'.o')])
            metrics[name+'_caller_compile_seconds']=time.perf_counter()-start
            metrics[name+'_preprocessed_bytes']=len(subprocess.check_output([*command,'-E','-P',source]))
    # The app owns record and its specialization. A later app must reuse the same definitions.
    (package/'lib').mkdir()
    import shutil
    shutil.copy2(work/'build/libstorage.a',package/'lib/libstorage.a')
    seal_package(package,['storage'])
    start=time.perf_counter()
    imported=concrete_types.generate(config,work/'consumer',root/'runtime',[package],'clang++',flags)
    assert imported['sources']==[]
    assert imported['types']['hash<record,string>']['owner']=='test.hash'
    metrics['reuse_without_compilation_seconds']=time.perf_counter()-start
    print('PASS: public-only typed hashes, nullable, nested/app values and sealed application reuse')
print(json.dumps(metrics,indent=2))
if args.output: args.output.write_text(json.dumps(metrics,indent=2)+'\n')
