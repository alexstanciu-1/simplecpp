"""Prepared checked/shared, weak and unique ownership transports through demanded APIs."""
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
from concurrent.futures import ThreadPoolExecutor
root=Path(__file__).resolve().parents[3]
sys.path.insert(0,str(root/'runtime/tools'))
import concrete_types
from public_package import seal_package
parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--tool',type=Path,required=True)
parser.add_argument('--core',type=Path)
parser.add_argument('--sanitize',action='store_true')
parser.add_argument('--jobs',type=int,default=8)
args=parser.parse_args();args.tool=args.tool.resolve()
assert args.jobs>0
leaf_names=['base','derived','sibling','left','right','plain_base','plain_derived']
leaves={name:{'cpp':'fixture::'+name,'header':'cast_records.hpp','owner':'application.ownership_casts',
             'capabilities':[] if name in ['left','right'] else ['default_storage']} for name in leaf_names}
operations={'TO_BASE':'shared_upcast<base,derived>','TO_LEFT':'shared_upcast<left,derived>',
    'OTHER_BASE':'shared_upcast<base,sibling>','CAST':'shared_object_cast<derived,base>',
    'IS':'shared_object_is<derived,base>','CROSS':'shared_object_cast<right,left>',
    'CROSS_IS':'shared_object_is<right,left>','WEAK_UP':'weak_upcast<base,derived>',
    'UNIQUE_UP':'unique_upcast<base,derived>'}
names={f'{family}<{name}>':family+'_'+name for family in ['shared','weak','unique'] for name in leaf_names}
data={'package':'application.ownership_casts','roots':[],'leaves':leaves,'names':names,'apis':list(operations.values())}
bindings={}
for i,api in enumerate(data['apis']):
    for parameter,name in zip(['To','From'],api[api.index('<')+1:-1].split(',')):
        bindings[f'api_{i}_{parameter}']={'source':'source.'+name,'physical':name,'arguments':{}}
requirements=[{'subject':slot,'contract':'application_formed'} for slot in bindings]
requirements+=[{'subject':'operation','contract':contract} for contract in ['ownership_ancestry','source_polymorphism','target_virtual_destruction','explicit_transfer']]
prepared={'version':'prepared-api-v1','producer':'fixture.semantic-owner','revision':'fixture-v1','action':'generate',
          'request':copy.deepcopy(data),'admission':'fixture/ownership-casts','bindings':bindings,
          'requirements':requirements,'facts':[{**r,'evidence':'fixture/'+r['contract']} for r in requirements]}
envelope={'metadata':concrete_types.metadata(root/'runtime'),'request':data,'leaves':leaves,'names':names,'imports':{},'layouts':{},
          'action':'generate','mode':'source','prepared':prepared}
def call(value):
    result=subprocess.run([str(args.tool)],input=json.dumps(value),text=True,capture_output=True)
    assert result.returncode==0,result.stderr
    return json.loads(result.stdout)
result=call(envelope)
assert len(result['apis'])==9 and len(result['types'])==9
for imported in [{},result['apis']]:
    for contract in ['ownership_ancestry','source_polymorphism','target_virtual_destruction','explicit_transfer']:
        bad=copy.deepcopy(envelope);bad['api_imports']=imported
        bad['prepared']['facts']=[f for f in bad['prepared']['facts'] if f['contract']!=contract]
        failure=subprocess.run([str(args.tool)],input=json.dumps(bad),text=True,capture_output=True)
        assert failure.returncode and 'operation/'+contract in failure.stderr
print('PASS: prepared ancestry/polymorphism/destruction/transfer facts required, including imported APIs')
if not args.core:sys.exit(0)
fixture=r'''#pragma once
namespace fixture {
struct base {
    static inline int live=0,destroyed=0;
    base() { ++live; }
    virtual ~base() { --live; ++destroyed; }
    base(const base &)=delete;
    base &operator=(const base &)=delete;
};
struct left { virtual ~left()=default;virtual int tag() const=0; };
struct right { virtual ~right()=default;virtual int tag() const=0; };
struct derived final : base,left,right {
    static inline int destroyed=0;
    ~derived() override { ++destroyed; }
    int tag() const override { return 7; }
};
struct sibling final : base {};
struct plain_base {};
struct plain_derived final : plain_base {};
}
'''
with tempfile.TemporaryDirectory(prefix='scpp-ownership-casts-') as directory:
    work=Path(directory);sdk=work/'sdk';app=work/'app'
    for folder in ['metadata','include','public']:shutil.copytree(root/'runtime'/folder,sdk/folder)
    (sdk/'lib').mkdir();(sdk/'tools').mkdir()
    shutil.copy2(args.core,sdk/'lib/libcore.a');shutil.copy2(args.tool,sdk/'tools/scpp_api_generator')
    seal_package(sdk,['core']);os.environ['SCPP_API_GENERATOR']=str(sdk/'tools/scpp_api_generator')
    (app/'public').mkdir(parents=True);(app/'public/cast_records.hpp').write_text(fixture)
    request=work/'request.json';wrapper={'mode':'source','prepared':prepared,'request':data};request.write_text(json.dumps(wrapper))
    manifest=concrete_types.generate(request,app,sdk,[sdk],'clang++',[])
    caller='\n'.join('#include "'+api['header']+'"' for api in (manifest['apis'][identity] for identity in operations.values()))+'\n'
    caller+='\n'.join('#define '+name+' scpp::'+manifest['apis'][identity]['operation']['name'] for name,identity in operations.items())+'\n'
    caller+=(root/'tests/runtime/native/public_ownership_casts.cpp').read_text();(work/'caller.cpp').write_text(caller)
    flags=['-std=c++23','-O1']
    if args.sanitize:flags+=['-fsanitize=address,undefined','-fno-omit-frame-pointer']
    public=['clang++',*flags,'-I'+str(app/'public'),'-I'+str(sdk/'public')]
    private=[*public,'-I'+str(app/'include'),'-I'+str(sdk/'include')]
    def compile_source(item):
        i,path=item;obj=work/f'{i}.o'
        subprocess.run([*private,'-c',str(app/path),'-o',str(obj)],check=True)
        return str(obj)
    start=time.perf_counter()
    with ThreadPoolExecutor(max_workers=args.jobs) as pool:objects=list(pool.map(compile_source,enumerate(manifest['sources'])))
    storage=time.perf_counter()-start;start=time.perf_counter()
    subprocess.run([*public,'-I'+str(root/'tests/runtime/native'),'-c',str(work/'caller.cpp'),'-o',str(work/'caller.o')],check=True)
    consumer=time.perf_counter()-start
    subprocess.run(['clang++',*flags,str(work/'caller.o'),*objects,str(args.core.resolve()),'-pthread','-o',str(work/'proof')],check=True)
    subprocess.run([str(work/'proof')],check=True)
    (app/'lib').mkdir();subprocess.run(['ar','rcs',str(app/'lib/libcasts.a'),*objects],check=True);seal_package(app,['casts'])
    reused=concrete_types.generate(request,work/'reuse',sdk,[sdk,app],'clang++',[]);assert not reused['sources']
    for i,invalid_api in enumerate(['unique_upcast<plain_base,plain_derived>','shared_object_cast<plain_derived,plain_base>',
                                  'weak_upcast<derived,base>']):
        invalid_file=work/f'invalid{i}.json';invalid_output=work/f'invalid{i}'
        invalid_file.write_text(json.dumps({**data,'apis':[invalid_api]}))
        invalid_manifest=concrete_types.generate(invalid_file,invalid_output,sdk,[sdk,app],'clang++',[])
        api=invalid_manifest['apis'][invalid_api]
        failure=subprocess.run(['clang++',*flags,'-I'+str(invalid_output/'public'),'-I'+str(invalid_output/'include'),
            *private[len(['clang++',*flags]):],'-fsyntax-only',str(invalid_output/api['source'])],text=True,capture_output=True)
        assert failure.returncode,invalid_api
        assert ['unique_p','polymorphic','weak_p'][i] in failure.stderr,failure.stderr
    # A named unique source cannot enter the public transfer operation without an explicit move.
    unique_api=manifest['apis'][operations['UNIQUE_UP']]
    invalid='#include "'+unique_api['header']+'"\nvoid test(scpp::unique_derived &source) { scpp::'+unique_api['operation']['name']+'(source); }\n'
    failure=subprocess.run([*public,'-fsyntax-only','-x','c++','-'],input=invalid,text=True,capture_output=True)
    assert failure.returncode and 'rvalue' in failure.stderr,failure.stderr
    print(f'PASS: sealed reuse, invalid native transports and implicit unique transfer rejected; {len(objects)} units at {args.jobs} jobs={storage:.4f}s public caller={consumer:.4f}s')
