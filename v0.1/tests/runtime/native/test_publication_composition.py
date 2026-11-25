"""Relocated runtime publication + application-specific specialization/reuse proof."""
import argparse
import hashlib
import json
from pathlib import Path
import re
import shutil
import subprocess
import tempfile

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--package', type=Path, required=True)
parser.add_argument('--compiler', default='clang++')
args = parser.parse_args()

def execute(command, **kwargs):
    return subprocess.run([str(x) for x in command], check=True, **kwargs)

with tempfile.TemporaryDirectory(prefix='scpp-publication-proof-') as directory:
    root = Path(directory)
    package = root/'relocated runtime'
    shutil.copytree(args.package, package)
    app = root/'app'
    (app/'public/sample').mkdir(parents=True)
    (app/'public/sample/record.hpp').write_text('''#pragma once
#include "scpp/string.hpp"
namespace sample { struct record { scpp::string label; scpp::uint64 id; }; }
''')
    request = {'version':1,'package':'application.middleware',
        'roots':['result<vector<string>>','vector<sample::record>'],
        'leaves':{'sample::record':{'cpp':'sample::record','header':'sample/record.hpp','owner':'application.model'}}}
    config = root/'request.json'
    config.write_text(json.dumps(request))
    tool = package/'tools/concrete_types.py'
    command = ['python3',tool,'--request',config,'--output',app,'--dependency',package,'--compiler',args.compiler]
    execute(command)
    manifest = json.loads((app/'public/specializations.json').read_text())
    assert len(manifest['sources']) == 1, 'runtime definitions were regenerated'
    assert manifest['types']['vector<string>']['owner'] == 'runtime.core'
    assert manifest['types']['result<vector<string>>']['owner'] == 'runtime.core'
    assert not (app/'public/scpp/vector_string.hpp').exists()
    own = manifest['types']['vector<sample::record>']
    main = root/'main.cpp'
    main.write_text('''#include "modules/filesystem/scan.hpp"
#include "scpp/api_generation.hpp"
#include "'''+own['header']+'''"
int main(int argc, char **argv) {
    if (argc != 2) return 1;
    const scpp::string request(R"({"action":"names","registry":{"version":1,"types":["string"],"families":{"sequence":{"parameters":["type"]}},"mappings":[]},"types":["sequence<string>"]})");
    if (scpp::api_generation::generate(request).byte_size() == scpp::uint64(0)) return 6;
    scpp::vector_string listing;
    scpp::error_value error;
    if (!scpp::take(listing, error, scpp::fs::scan(scpp::string(argv[1])))) return 2;
    sample::record record{scpp::string("record"), scpp::uint64(17)};
    '''+own['cpp']+''' records;
    records.append(record);
    record.label.clear();
    if (!records.at(scpp::uint64(0)).label.equals("record")) return 3;
    auto copied = records;
    records.clear();
    if (copied.at(scpp::uint64(0)).id != scpp::uint64(17)) return 4;
    auto cursor = copied.iterate();
    return cursor.value().label.equals("record") ? 0 : 5;
}
''')
    public_flags = ['-I'+str(app/'public'), '-I'+str(package/'public')]
    for header in [own['header'], 'modules/filesystem/scan.hpp']:
        result = subprocess.check_output([args.compiler,'-std=c++23',*public_flags,'-E','-P','-x','c++','-'],
            input=f'#include "{header}"\n',text=True)
        assert not re.search(r'\b(template|typename)\b',result),header
        dependencies = subprocess.check_output([args.compiler,'-std=c++23',*public_flags,'-M','-x','c++','-'],
            input=f'#include "{header}"\n',text=True)
        assert '/include/scpp/' not in dependencies, dependencies
    (root/'CMakeLists.txt').write_text('''cmake_minimum_required(VERSION 3.20)
project(application_proof LANGUAGES CXX)
include("${RUNTIME_PACKAGE}/package.cmake")
add_library(app_middleware STATIC "${APP_IMPLEMENTATION}")
target_include_directories(app_middleware PUBLIC "${APP_ROOT}/public" PRIVATE "${APP_ROOT}/include" "${RUNTIME_PACKAGE}/include")
target_link_libraries(app_middleware PUBLIC compiler_runtime_api)
add_executable(app main.cpp)
target_link_libraries(app PRIVATE app_middleware compiler_filesystem_api compiler_api_generation)
''')
    execute(['cmake','-S',root,'-B',root/'build','-G','Ninja',
             '-DCMAKE_CXX_COMPILER='+args.compiler,'-DCMAKE_BUILD_TYPE=Release',
             '-DCMAKE_EXPORT_COMPILE_COMMANDS=ON','-DRUNTIME_PACKAGE='+str(package),
             '-DAPP_ROOT='+str(app),'-DAPP_IMPLEMENTATION='+str(app/manifest['sources'][0])])
    execute(['cmake','--build',root/'build','-j8'])
    commands = json.loads((root/'build/compile_commands.json').read_text())
    consumer = next(c['command'] for c in commands if c['file'] == str(main))
    assert str(package/'include') not in consumer, consumer
    execute([root/'build/app',root])
    # Missing or conflicting exports must reject, never regenerate a competing class.
    def rejected():
        result = subprocess.run([str(x) for x in command],text=True,capture_output=True)
        assert result.returncode != 0, 'incompatible publication accepted'
        return result.stderr
    original = (package/'public/specializations.json').read_text()
    bad = json.loads(original); bad['abi'] = 'wrong'
    (package/'public/specializations.json').write_text(json.dumps(bad))
    assert 'ABI' in rejected()
    (package/'public/specializations.json').write_text(original)
    request['names'] = {'vector<string>':'different_vector'}
    config.write_text(json.dumps(request))
    assert 'Conflicting concrete name' in rejected()
    del request['names']; config.write_text(json.dumps(request))
    header = package/'public/scpp/string.hpp'
    text = header.read_text(); header.write_text(text+'\n// changed\n')
    assert 'public package' in rejected()
    header.write_text(text)
    library = package/'lib/libscpp_runtime_core.a'
    with library.open('ab') as stream: stream.write(b'changed')
    assert 'library' in rejected()
    print('Relocated public-only application linked and ran; one app specialization generated; runtime vector/result reused; ABI/name/header/library conflicts rejected')
