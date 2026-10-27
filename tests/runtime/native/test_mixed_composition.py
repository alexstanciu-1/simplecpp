"""Compile an application vector<mixed> against a sealed runtime publication."""
import argparse
import json
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile

root=Path(__file__).resolve().parents[3]
sys.path.insert(0,str(root/'runtime/tools'))
import concrete_types
from public_package import seal_package
parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--core',type=Path,required=True)
args=parser.parse_args()
def run(command): subprocess.run([str(x) for x in command],check=True)
with tempfile.TemporaryDirectory(prefix='scpp-mixed-composition-') as temporary:
    work=Path(temporary)
    sdk=work/'runtime'
    for directory in ['public','include','metadata']:
        shutil.copytree(root/'runtime'/directory,sdk/directory)
    (sdk/'lib').mkdir()
    shutil.copy2(args.core,sdk/'lib/libcore.a')
    (sdk/'tools').mkdir()
    shutil.copy2(concrete_types.generator_tool(root/'runtime'),sdk/'tools/scpp_api_generator')
    seal_package(sdk,['core'])
    request=work/'request.json'
    request.write_text(json.dumps({'package':'app.mixed','roots':['vector<mixed>'],'names':{'vector<mixed>':'mixed_values'}}))
    package=work/'app'
    manifest=concrete_types.generate(request,package,sdk,[sdk],'clang++',[])
    assert len(manifest['sources'])==1
    assert manifest['types']['mixed']['owner']=='runtime.core'
    assert manifest['types']['float64']['owner']=='runtime.core'
    (work/'main.cpp').write_text('''#include "public_api_guard.hpp"
#include "scpp/mixed_values.hpp"
#include <cstdlib>
int main() {
    scpp::mixed_values values;
    scpp::mixed row;
    row.append(scpp::mixed(scpp::int64(42)));
    values.append(row);
    row.reset();
    auto copy=values.at(scpp::uint64(0));
    values.clear();
    if (copy.get(scpp::int64(0)).get_int()!=scpp::int64(42)) std::abort();
}
''')
    run(['clang++','-std=c++23','-O2','-I'+str(sdk/'include'),'-I'+str(sdk/'public'),'-I'+str(package/'public'),
         '-c',package/manifest['sources'][0],'-o',work/'storage.o'])
    run(['clang++','-std=c++23','-O2','-I'+str(sdk/'public'),'-I'+str(package/'public'),'-I'+str(root/'tests/runtime/native'),
         work/'main.cpp',work/'storage.o',sdk/'lib/libcore.a','-fuse-ld=mold','-o',work/'proof'])
    run([work/'proof'])
    (package/'lib').mkdir()
    run(['ar','rcs',package/'lib/libapp.a',work/'storage.o'])
    seal_package(package,['app'])
    reused=concrete_types.generate(request,work/'consumer',sdk,[sdk,package],'clang++',[])
    assert reused['sources']==[]
    print('PASS: relocated runtime mixed/float reuse, compiled application vector<mixed>, sealed application reuse')
