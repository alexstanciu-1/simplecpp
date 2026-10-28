"""Prepared source roots/dependencies through probes, publication and imported reuse."""
import argparse
import copy
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import time
root=Path(__file__).resolve().parents[3]
sys.path.insert(0,str(root/'runtime/tools'))
import concrete_types
from public_package import seal_package
parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--tool',type=Path,required=True)
parser.add_argument('--core',type=Path)
parser.add_argument('--sanitize',action='store_true')
args=parser.parse_args();args.tool=args.tool.resolve()
metadata=concrete_types.metadata(root/'runtime')
data={'package':'application.prepared_storage','roots':['vector<vector<int64>>'],
      'names':{'vector<int64>':'prepared_row','vector<vector<int64>>':'prepared_matrix'}}
requirements=[{'subject':subject,'contract':'application_formed'}
              for subject in ['root_0','dependency:row','dependency:integer']]
prepared={'version':'prepared-api-v1','producer':'fixture.semantic-owner','revision':'fixture-v1',
    'action':'generate','request':copy.deepcopy(data),'admission':'fixture/approved-storage',
    'bindings':{'root_0':{'source':'source.matrix','physical':'vector<vector<int64>>','arguments':{'T':'source.row'}}},
    'dependencies':{'row':{'source':'source.row','physical':'vector<int64>','arguments':{'T':'source.integer'}},
                    'integer':{'source':'source.integer','physical':'int64','arguments':{}}},
    'requirements':requirements,'facts':[{**r,'evidence':'fixture/'+r['subject']} for r in requirements]}
base={'metadata':metadata,'action':'generate','request':data,'names':data['names'],'imports':{},'leaves':{},'layouts':{}}
source={**base,'mode':'source','prepared':prepared}
def call(envelope,error=None):
    result=subprocess.run([str(args.tool)],input=json.dumps(envelope),text=True,capture_output=True)
    if error:
        assert result.returncode and error in result.stderr,result.stderr
        return
    assert result.returncode==0,result.stderr
    return json.loads(result.stdout)
native=call(base);actual=call(source)
assert actual.pop('preparation')==prepared and actual==native
assert len(actual['units'])==2
for imported in [{},actual['types']]:
    request=copy.deepcopy(source);request['imports']=imported
    call(request)
    bad=copy.deepcopy(request);del bad['prepared']['dependencies']['row']
    call(bad,'Missing prepared source dependency')
    bad=copy.deepcopy(request);bad['prepared']['facts']=[f for f in bad['prepared']['facts'] if f['subject']!='dependency:integer']
    call(bad,'Missing prepared semantic fact: dependency:integer')
    bad=copy.deepcopy(request);bad['prepared']['bindings']['root_0']['physical']='vector<int64>'
    call(bad,'physical binding mismatch')
    bad=copy.deepcopy(request);bad['prepared']['dependencies']['integer']['source']='source.row'
    call(bad,'Conflicting prepared source binding')
# Reusing the same source identity for duplicate roots is valid if bindings agree.
repeat=copy.deepcopy(source);repeat['request']['roots']*=2;repeat['prepared']['request']=copy.deepcopy(repeat['request'])
repeat['prepared']['bindings']['root_1']=copy.deepcopy(repeat['prepared']['bindings']['root_0'])
assert len(call(repeat)['units'])==2
print('PASS: source root/dependency bindings, native plan identity and import non-bypass')
if not args.core:sys.exit(0)
with tempfile.TemporaryDirectory(prefix='scpp-prepared-generation-') as directory:
    work=Path(directory);sdk=work/'sdk';app=work/'app'
    for folder in ['metadata','include','public']:shutil.copytree(root/'runtime'/folder,sdk/folder)
    (sdk/'lib').mkdir();(sdk/'tools').mkdir()
    shutil.copy2(args.core,sdk/'lib/libcore.a');shutil.copy2(args.tool,sdk/'tools/scpp_api_generator')
    seal_package(sdk,['core']);os.environ['SCPP_API_GENERATOR']=str(sdk/'tools/scpp_api_generator')
    request_file=work/'source.json';wrapper={'mode':'source','prepared':prepared,'request':data}
    request_file.write_text(json.dumps(wrapper))
    manifest=concrete_types.generate(request_file,app,sdk,[sdk],'clang++',[])
    assert len(manifest['sources'])==2 and 'preparation' not in manifest and 'prepared' not in manifest
    code=r'''
#include "public_api_guard.hpp"
#include "scpp/prepared_matrix.hpp"
#include <cstdlib>
#include <cstdio>
#include <new>
using namespace scpp;
static unsigned allocations=0;
void *operator new(std::size_t n) { ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"prepared storage line %d\n",__LINE__);std::abort(); } } while(false)
static prepared_row retained() {
    prepared_row row;row.append(int64(17));prepared_matrix matrix;matrix.append(row);
    return matrix.at(uint64(0));
}
int main() {
    const auto before=allocations;
    prepared_row empty_row;prepared_matrix empty_matrix;
    CHECK(empty_row.size()==uint64(0) && empty_matrix.size()==uint64(0));CHECK(allocations==before);
    auto row=retained();CHECK(row.at(uint64(0))==int64(17));
    prepared_matrix matrix;matrix.append(row);row.set(uint64(0),int64(99));
    auto extracted=matrix.at(uint64(0));CHECK(extracted.at(uint64(0))==int64(17));
    extracted.set(uint64(0),int64(25));CHECK(matrix.at(uint64(0)).at(uint64(0))==int64(17));
    const auto reads=allocations;
    for(unsigned i=0;i<100;++i) CHECK(row.at(uint64(0))==int64(99));
    CHECK(allocations==reads);
    std::puts("PASS: prepared nested storage, independent owned extraction/lifetime; empty/read allocations=0");
}
'''
    caller=work/'caller.cpp';caller.write_text(code)
    flags=['-std=c++23','-O1']
    if args.sanitize:flags+=['-fsanitize=address,undefined','-fno-omit-frame-pointer']
    common=['clang++',*flags,'-I'+str(app/'public'),'-I'+str(sdk/'public')]
    objects=[];start=time.perf_counter()
    for i,source_path in enumerate(manifest['sources']):
        obj=work/f'{i}.o';objects.append(str(obj))
        subprocess.run([*common,'-I'+str(app/'include'),'-I'+str(sdk/'include'),'-c',str(app/source_path),'-o',str(obj)],check=True)
    storage=time.perf_counter()-start;start=time.perf_counter()
    subprocess.run([*common,'-I'+str(root/'tests/runtime/native'),'-c',str(caller),'-o',str(work/'caller.o')],check=True)
    caller_time=time.perf_counter()-start
    subprocess.run(['clang++',*flags,str(work/'caller.o'),*objects,str(args.core.resolve()),'-pthread','-o',str(work/'proof')],check=True)
    subprocess.run([str(work/'proof')],check=True)
    (app/'lib').mkdir();subprocess.run(['ar','rcs',str(app/'lib/libstorage.a'),*objects],check=True)
    seal_package(app,['storage']);reused=concrete_types.generate(request_file,work/'reuse',sdk,[sdk,app],'clang++',[])
    assert not reused['sources'] and 'prepared' not in reused
    bad=copy.deepcopy(wrapper);bad['prepared']['facts']=[];request_file.write_text(json.dumps(bad))
    rejected=False
    try:concrete_types.generate(request_file,work/'rejected',sdk,[sdk,app],'clang++',[])
    except subprocess.CalledProcessError as error:rejected=error.returncode==1
    assert rejected and not (work/'rejected').exists()
    print(f'PASS: sealed source-mode reuse and missing-fact rejection before publication; storage compile={storage:.4f}s caller compile={caller_time:.4f}s')
