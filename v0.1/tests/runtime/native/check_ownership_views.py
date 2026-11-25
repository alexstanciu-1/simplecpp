"""Demanded function families: shared ancestry, prepared arguments and publication."""
import argparse
import copy
import json
import os
import re
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
args=parser.parse_args(); args.tool=args.tool.resolve()
leaves={name:{'cpp':'fixture::'+name,'header':'views.hpp','owner':'application.views','capabilities':['default_storage']}
        for name in ['base','derived','virtual_derived','unrelated']}
apis=['shared_upcast<base,derived>','shared_identical<base,derived>','shared_identical<derived,base>',
      'shared_identical<derived>','shared_upcast<base,virtual_derived>','shared_identical<virtual_derived,base>']
names={f'shared<{name}>':'shared_'+name for name in leaves}
data={'package':'application.views','roots':[], 'leaves':leaves,'names':names,'apis':apis}
metadata=concrete_types.metadata(root/'runtime')
envelope={'metadata':metadata,'request':data,'leaves':leaves,'names':names,'imports':{},'layouts':{}}
def call(request,error=None):
    out=subprocess.run([str(args.tool)],input=json.dumps(request),text=True,capture_output=True)
    if error:
        assert out.returncode and error in out.stderr,out.stderr
        return
    assert out.returncode==0,out.stderr
    return json.loads(out.stdout)
result=call(envelope)
assert len(result['apis'])==6 and len(result['types'])==3 and len(result['units'])==9
bad=copy.deepcopy(envelope);bad['api_imports']=copy.deepcopy(result['apis'])
first=next(iter(bad['api_imports']));bad['api_imports'][first]['operation']['name']='changed_symbol'
call(bad,'Conflicting API ownership')
same=copy.deepcopy(envelope);same['request']['apis']=['shared_identical<derived>','shared_identical<derived,derived>']
assert len(call(same)['apis'])==1
# No demanded function becomes an instantiable public storage type.
bad=copy.deepcopy(envelope);bad['request']['roots']=['shared_identical<derived>'];call(bad,'Unregistered type')
bad=copy.deepcopy(envelope);bad['request']['apis']=['shared_upcast<base>'];call(bad,'argument')
bad=copy.deepcopy(envelope);bad['metadata']['function_families']['shared_identical']['defaults']=['Right'];call(bad,'earlier parameter')
# Fixture facts assert source ancestry; the consumer validates evidence, not ancestry itself.
bindings={}
for i,api in enumerate(apis):
    inner=api[api.index('<')+1:-1].split(',')
    parameters=['To','From'] if api.startswith('shared_upcast') else ['Left','Right']
    if len(inner)==1:inner*=2
    for parameter,name in zip(parameters,inner):
        bindings[f'api_{i}_{parameter}']={'source':'source.'+name,'physical':name,'arguments':{}}
requirements=[{'subject':slot,'contract':'application_formed'} for slot in bindings]
requirements+=[{'subject':'operation','contract':'ownership_ancestry'}]
prepared={'version':'prepared-api-v1','producer':'fixture.semantic-owner','revision':'fixture-v1','action':'generate',
          'request':copy.deepcopy(data),'admission':'fixture/ownership-views','bindings':bindings,
          'requirements':requirements,'facts':[{**r,'evidence':'fixture/'+r['subject']} for r in requirements]}
source={**envelope,'action':'generate','mode':'source','prepared':prepared}
actual=call(source); assert actual.pop('preparation')==prepared and actual==result
for imports in [{},result['apis']]:
    bad=copy.deepcopy(source);bad['api_imports']=imports
    bad['prepared']['facts']=[f for f in bad['prepared']['facts'] if f['contract']!='ownership_ancestry']
    call(bad,'operation/ownership_ancestry')
    bad=copy.deepcopy(source);del bad['prepared']['bindings']['api_0_To'];call(bad,'api_0_To')
print('PASS: demanded/defaulted function identities, prepared argument facts and no storage-type leakage')
if not args.core:sys.exit(0)
fixture=r'''#pragma once
namespace fixture {
struct prefix { int padding=3; };
struct base { int value=7; base()=default; base(const base &)=delete; base &operator=(const base &)=delete; };
struct derived final : prefix, base {};
struct virtual_parent : virtual base {};
struct virtual_derived final : virtual_parent {};
struct unrelated final {};
}
'''
with tempfile.TemporaryDirectory(prefix='scpp-views-') as directory:
    work=Path(directory);sdk=work/'sdk';app=work/'app'
    for folder in ['metadata','include','public']:shutil.copytree(root/'runtime'/folder,sdk/folder)
    (sdk/'lib').mkdir();(sdk/'tools').mkdir()
    shutil.copy2(args.core,sdk/'lib/libcore.a');shutil.copy2(args.tool,sdk/'tools/scpp_api_generator')
    seal_package(sdk,['core']);os.environ['SCPP_API_GENERATOR']=str(sdk/'tools/scpp_api_generator')
    (app/'public').mkdir(parents=True);(app/'public/views.hpp').write_text(fixture)
    request=work/'request.json';wrapper={'mode':'source','prepared':prepared,'request':data};request.write_text(json.dumps(wrapper))
    manifest=concrete_types.generate(request,app,sdk,[sdk],'clang++',[])
    def function(identity):return manifest['apis'][identity]['operation']['name']
    caller='\n'.join('#include "'+api['header']+'"' for api in manifest['apis'].values())+'\n'
    caller+=r'''#include "public_api_guard.hpp"
#include <cstdlib>
#include <cstdio>
#include <new>
static unsigned allocations=0;
void *operator new(std::size_t n) { ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"views line %d\n",__LINE__);std::abort(); } } while(false)
using namespace scpp;
int main() {
    auto child=shared_derived::make(); auto other=shared_derived::make();
    auto virtual_child=shared_virtual_derived::make();
    const auto before=allocations;
    auto parent=UPCAST(child); auto virtual_base=VUPCAST(virtual_child);
    CHECK(FORWARD(parent,child) && REVERSE(child,parent));
    CHECK(!FORWARD(parent,other) && !REVERSE(other,parent));
    CHECK(SAME(child,child) && !SAME(child,other));
    CHECK(VIDENTICAL(virtual_child,virtual_base));
    shared_base absent_parent; shared_derived absent_child;
    CHECK(FORWARD(absent_parent,absent_child) && !FORWARD(absent_parent,child));
    CHECK(!UPCAST(absent_child).has_value());
    auto left_snapshot=child;child=other;auto right_snapshot=child;
    CHECK(!SAME(left_snapshot,right_snapshot));
    child.reset();left_snapshot.reset();CHECK(parent.has_value());
    CHECK(allocations==before);
    std::puts("PASS: shared multiple/virtual ancestry, absence, snapshots and retention; view allocations=0");
}
'''
    for token,identity in {'UPCAST':'shared_upcast<base,derived>','VUPCAST':'shared_upcast<base,virtual_derived>',
        'FORWARD':'shared_identical<base,derived>','REVERSE':'shared_identical<derived,base>',
        'SAME':'shared_identical<derived,derived>','VIDENTICAL':'shared_identical<virtual_derived,base>'}.items():
        # Replace longest identifiers as whole words, so UPCAST cannot corrupt VUPCAST.
        caller=re.sub(r'\b'+token+r'\b',function(identity),caller)
    (work/'caller.cpp').write_text(caller)
    flags=['-std=c++23','-O1']
    if args.sanitize:flags+=['-fsanitize=address,undefined','-fno-omit-frame-pointer']
    public=['clang++',*flags,'-I'+str(app/'public'),'-I'+str(sdk/'public')]
    private=[*public,'-I'+str(app/'include'),'-I'+str(sdk/'include')]
    start=time.perf_counter();objects=[]
    for i,path in enumerate(manifest['sources']):
        obj=work/f'{i}.o';objects.append(str(obj));subprocess.run([*private,'-c',str(app/path),'-o',str(obj)],check=True)
    storage=time.perf_counter()-start;start=time.perf_counter()
    subprocess.run([*public,'-I'+str(root/'tests/runtime/native'),'-c',str(work/'caller.cpp'),'-o',str(work/'caller.o')],check=True)
    consumer=time.perf_counter()-start
    subprocess.run(['clang++',*flags,str(work/'caller.o'),*objects,str(args.core.resolve()),'-pthread','-o',str(work/'proof')],check=True)
    subprocess.run([str(work/'proof')],check=True)
    (app/'lib').mkdir();subprocess.run(['ar','rcs',str(app/'lib/libviews.a'),*objects],check=True);seal_package(app,['views'])
    reused=concrete_types.generate(request,work/'reuse',sdk,[sdk,app],'clang++',[]);assert not reused['sources']
    bad=copy.deepcopy(wrapper);bad['prepared']['facts']=[f for f in bad['prepared']['facts'] if f['contract']!='ownership_ancestry'];request.write_text(json.dumps(bad))
    rejected=False
    try:concrete_types.generate(request,work/'rejected',sdk,[sdk,app],'clang++',[])
    except subprocess.CalledProcessError as error:rejected=error.returncode==1
    assert rejected and not (work/'rejected').exists()
    # Native probes permit storage for both targets; operation compilation must
    # still reject unrelated identity and reverse/downcast construction.
    for index,invalid_api in enumerate(['shared_identical<base,unrelated>','shared_upcast<derived,base>']):
        invalid_request=work/f'invalid{index}.json'; invalid_output=work/f'invalid{index}'
        invalid_request.write_text(json.dumps({**data,'apis':[invalid_api]}))
        invalid_manifest=concrete_types.generate(invalid_request,invalid_output,sdk,[sdk,app],'clang++',[])
        api=invalid_manifest['apis'][invalid_api]
        failure=subprocess.run(['clang++',*flags,'-I'+str(invalid_output/'public'),'-I'+str(invalid_output/'include'),
            *private[len(['clang++',*flags]):],'-fsyntax-only',str(invalid_output/api['source'])],text=True,capture_output=True)
        assert failure.returncode, invalid_api
        assert ('is_convertible' if index==0 else 'shared_p') in failure.stderr, failure.stderr
    print(f'PASS: sealed API reuse/missing ancestry rejection; storage+APIs={storage:.4f}s public caller={consumer:.4f}s')
