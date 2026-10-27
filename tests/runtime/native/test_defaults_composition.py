"""Compile defaulted metadata families and nested vector lifetimes using existing runtime archives."""
import argparse
import copy
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--package', type=Path, required=True)
parser.add_argument('--sanitized-core', type=Path)
args = parser.parse_args()
root = Path(__file__).resolve().parents[3]
package = args.package.resolve()
sys.path.insert(0, str(root/'runtime/tools'))
import concrete_types
from public_package import seal_package

def execute(command, **kwargs):
    subprocess.run([str(x) for x in command], check=True, **kwargs)

with tempfile.TemporaryDirectory(prefix='scpp-defaults-composition-') as temporary:
    work = Path(temporary)
    # Test-only metadata extension. Existing runtime metadata and publications stay intact.
    sdk = work/'sdk'
    sdk.mkdir()
    shutil.copytree(package/'metadata', sdk/'metadata')
    for folder in ('include','public'):
        (sdk/folder).symlink_to(package/folder, target_is_directory=True)
    (sdk/'tools').mkdir()
    (sdk/'tools/scpp_api_generator').symlink_to(concrete_types.generator_tool(package))
    metadata = json.loads((sdk/'metadata/public_api.json').read_text())
    sequence = copy.deepcopy(metadata['families']['vector'])
    sequence['defaults'] = ['string']
    nested = json.loads(json.dumps(sequence).replace('"T"', '"V"').replace('${public:T}', '${public:V}').replace('${native:T}', '${native:V}'))
    nested['parameters'] = ['T','U','V']
    nested['defaults'] = ['string','sequence<T>','U']
    metadata['families'].update(sequence=sequence, nested=nested)
    (sdk/'metadata/public_api.json').write_text(json.dumps(metadata))
    request = {'package':'test.defaults','roots':['nested','nested<>','nested<string>','nested<string,sequence<string>,sequence<string>>'],
               'names':{'nested':'nested_strings','sequence<string>':'sequence_string'}}
    config = work/'defaults.json'
    config.write_text(json.dumps(request))
    defaults = work/'defaults'
    default_flags = ['-fsanitize=address,undefined', '-fno-omit-frame-pointer'] if args.sanitized_core else []
    manifest = concrete_types.generate(config, defaults, sdk, [], 'clang++', default_flags)
    assert len(manifest['sources']) == 2, 'Equivalent default forms generated duplicates'
    key = 'nested<string,sequence<string>,sequence<string>>'
    assert manifest['types'][key]['native'] == 'scpp::vector_t<scpp::sequence_string>'
    assert manifest['types'][key]['name'] == 'nested_strings'

    outer = work/'outer'
    config = work/'outer.json'
    config.write_text(json.dumps({'package':'test.nesting','roots':['vector<vector<string>>']}))
    nested_manifest = concrete_types.generate(config, outer, package, [package], 'clang++', [])
    assert len(nested_manifest['sources']) == 1
    assert nested_manifest['types']['vector<string>']['owner'] == 'runtime.core'
    assert nested_manifest['types']['vector<vector<string>>']['native'] == 'scpp::vector_t<scpp::vector_string>'
    (work/'test_types.hpp').write_text('''#pragma once
#include "scpp/vector_0vector_1string_.hpp"
#include "scpp/nested_strings.hpp"
using nested_vector = scpp::vector_0vector_1string_;
using default_nested = scpp::nested_strings;
using default_sequence = scpp::sequence_string;
''')
    sources = [str(defaults/p) for p in manifest['sources']] + [str(outer/p) for p in nested_manifest['sources']]
    (work/'CMakeLists.txt').write_text('''cmake_minimum_required(VERSION 3.20)
project(defaults_proof LANGUAGES CXX)
include("${PACKAGE}/package.cmake")
if(SANITIZED_CORE)
  set_target_properties(compiler_runtime_core PROPERTIES IMPORTED_LOCATION "${SANITIZED_CORE}")
  add_compile_options(-fsanitize=address,undefined -fno-omit-frame-pointer)
  add_link_options(-fsanitize=address,undefined)
endif()
add_library(storage STATIC ${GENERATED_SOURCES})
target_include_directories(storage PUBLIC "${DEFAULTS}/public" "${OUTER}/public" PRIVATE "${PACKAGE}/include")
target_link_libraries(storage PUBLIC compiler_runtime_api)
add_executable(proof "${TEST_ROOT}/test_nested_vector.cpp")
target_include_directories(proof PRIVATE "${CMAKE_CURRENT_SOURCE_DIR}" "${TEST_ROOT}")
target_link_libraries(proof PRIVATE storage)
''')
    execute(['cmake','-S',work,'-B',work/'build','-G','Ninja','-DCMAKE_CXX_COMPILER=clang++','-DCMAKE_BUILD_TYPE=Release',
             '-DCMAKE_EXPORT_COMPILE_COMMANDS=ON','-DPACKAGE='+str(package),'-DDEFAULTS='+str(defaults),'-DOUTER='+str(outer),
             '-DTEST_ROOT='+str(root/'tests/runtime/native'),'-DGENERATED_SOURCES='+';'.join(sources),
             '-DSANITIZED_CORE='+str(args.sanitized_core.resolve() if args.sanitized_core else '')])
    execute(['cmake','--build',work/'build','-j8'])
    commands = json.loads((work/'build/compile_commands.json').read_text())
    caller = next(c['command'] for c in commands if c['file'].endswith('test_nested_vector.cpp'))
    assert str(package/'include') not in caller
    execute([work/'build/proof'])
    # Seal the fixture's own compiled storage and request its defaulted API again.
    (defaults/'lib').mkdir()
    shutil.copy2(work/'build/libstorage.a', defaults/'lib/libstorage.a')
    seal_package(defaults, ['storage'])
    reuse = work/'reuse.json'
    reuse.write_text(json.dumps(request))
    imported = concrete_types.generate(reuse, work/'consumer', sdk, [defaults], 'clang++', default_flags)
    assert imported['sources'] == [], 'Defaulted spelling did not reuse the explicit published identity'
    assert imported['types'][key]['name'] == 'nested_strings'
    print('PASS: defaulted/explicit requests reuse the same sealed application specialization without compilation')
