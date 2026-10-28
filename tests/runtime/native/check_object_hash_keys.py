"""Prepared shared identity keys: exact native policy, retention, upcast and reuse."""
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
args=parser.parse_args();args.tool=args.tool.resolve()
leaves={name:{'cpp':'fixture::'+name,'header':'key_records.hpp','owner':'application.object_keys',
             'capabilities':['default_storage']} for name in ['base','derived']}
names={'shared<base>':'key_base','shared<derived>':'key_derived','hash<int64,shared<base>>':'object_hash'}
names.update({'vector<int64>':'key_row','hash<vector<int64>,shared<base>>':'key_rows',
              'nullable<shared<derived>>':'optional_key'})
paths={name:{'root':'hash<vector<int64>,shared<base>>','depth':depth,'operation':operation}
       for name,depth,operation in [('read_key_row',2,'existing_read'),('store_key_row',2,'existing_store'),
                                    ('ready_key_row',1,'prefix_ready'),('erase_key_row',1,'existing_erase')]}
data={'package':'application.object_keys','roots':['hash<int64,shared<base>>','nullable<shared<derived>>'],'leaves':leaves,'names':names,
      'paths':paths,'apis':['shared_upcast<base,derived>',*paths]}
def binding(physical,arguments=None):
    return {'source':'source.'+physical,'physical':physical,'arguments':{k:'source.'+v for k,v in (arguments or {}).items()}}
bindings={'root_0':binding(data['roots'][0],{'V':'int64','K':'shared<base>'}),
          'api_0_To':binding('base'),'api_0_From':binding('derived')}
bindings['root_1']=binding('nullable<shared<derived>>',{'T':'shared<derived>'})
for name,path in paths.items():
    prefix='path_'+name+'_'
    bindings[prefix+'root']=binding(path['root'],{'V':'vector<int64>','K':'shared<base>'})
    bindings[prefix+'key_0']=binding('shared<base>',{'T':'base'})
    bindings[prefix+'element_0']=binding('vector<int64>',{'T':'int64'})
    bindings[prefix+'value']=binding('int64') if path['depth']==2 else binding('vector<int64>',{'T':'int64'})
    if path['depth']==2:
        bindings[prefix+'key_1']=binding('int64');bindings[prefix+'element_1']=binding('int64')
dependencies={'derived_key':binding('shared<derived>',{'T':'derived'}),'key':binding('shared<base>',{'T':'base'}),'value':binding('int64')}
requirements=[{'subject':slot,'contract':'application_formed'} for slot in bindings]
requirements += [{'subject':'dependency:'+slot,'contract':'application_formed'} for slot in dependencies]
requirements += [{'subject':'operation','contract':'identity_key_ancestry_admitted'}]
prepared={'version':'prepared-api-v1','producer':'fixture.semantic-owner','revision':'fixture-v1','action':'generate',
          'request':copy.deepcopy(data),'admission':'fixture/object-keys','bindings':bindings,'dependencies':dependencies,
          'requirements':requirements,'facts':[{**r,'evidence':'fixture/'+r['subject']} for r in requirements]}
envelope={'metadata':concrete_types.metadata(root/'runtime'),'request':data,'leaves':leaves,'names':names,
          'imports':{},'layouts':{},'action':'generate','mode':'source','prepared':prepared}
def plan(value,error=None):
    result=subprocess.run([str(args.tool)],input=json.dumps(value),text=True,capture_output=True)
    if error:
        assert result.returncode and error in result.stderr,result.stderr
        return
    assert result.returncode==0,result.stderr
    return json.loads(result.stdout)
result=plan(envelope)
assert 'using scpp_runtime_hash_key = void;' in next(u['header'] for u in result['units'] if u['identity']=='shared<base>')
for family in ['weak','unique']:
    bad=copy.deepcopy(envelope);bad.pop('prepared');bad['mode']='native'
    bad['request']['roots']=['hash<int64,'+family+'<base>>']
    plan(bad,'Unsatisfied family capabilities')
for aliases,error in [([], 'Class aliases must be an object'),({'bad::alias':'void'},'unqualified member'),
                      ({'marker':'unregistered_type'},'Unregistered type')]:
    bad=copy.deepcopy(envelope);bad['metadata']['families']['shared']['classes'][0]['aliases']=aliases
    plan(bad,error)
for imports in [{},result['types']]:
    bad=copy.deepcopy(envelope);bad['imports']=imports
    bad['prepared']['facts']=[f for f in bad['prepared']['facts'] if f['subject']!='operation']
    plan(bad,'identity_key_ancestry_admitted')
print('PASS: identity key capability, alias validation and prepared ancestry admission')
if not args.core:sys.exit(0)
fixture='''#pragma once
namespace fixture {
struct base {
    static inline int live=0;
    base() { ++live; }
    virtual ~base() { --live; }
    base(const base &)=delete;
    base &operator=(const base &)=delete;
};
struct padding { virtual ~padding()=default; int payload=42; };
struct derived final : padding, base {};
}
'''
with tempfile.TemporaryDirectory(prefix='scpp-object-keys-') as directory:
    work=Path(directory);sdk=work/'sdk';app=work/'app'
    for folder in ['metadata','include','public']:shutil.copytree(root/'runtime'/folder,sdk/folder)
    (sdk/'lib').mkdir();(sdk/'tools').mkdir()
    shutil.copy2(args.core,sdk/'lib/libcore.a');shutil.copy2(args.tool,sdk/'tools/scpp_api_generator')
    seal_package(sdk,['core']);os.environ['SCPP_API_GENERATOR']=str(sdk/'tools/scpp_api_generator')
    (app/'public').mkdir(parents=True);(app/'public/key_records.hpp').write_text(fixture)
    request=work/'request.json';request.write_text(json.dumps({'mode':'source','prepared':prepared,'request':data}))
    manifest=concrete_types.generate(request,app,sdk,[sdk],'clang++',[])
    api=manifest['apis'][data['apis'][0]]
    caller='#include "'+manifest['types'][data['roots'][0]]['header']+'"\n#include "'+api['header']+'"\n'
    caller+='\n'.join('#include "'+manifest['types'][identity]['header']+'"' for identity in data['roots'])+'\n'
    caller+='\n'.join('#include "'+manifest['apis'][name]['header']+'"' for name in paths)+'\n'
    caller+='\n'.join('#define '+name+' scpp::'+manifest['apis'][name]['operation']['name'] for name in paths)+'\n'
    caller+='#define TO_BASE scpp::'+api['operation']['name']+'\n'
    caller+=(root/'tests/runtime/native/public_object_hash_keys.cpp').read_text();(work/'caller.cpp').write_text(caller)
    flags=['-std=c++23','-O1']
    if args.sanitize:flags+=['-fsanitize=address,undefined','-fno-omit-frame-pointer']
    public=['clang++',*flags,'-I'+str(app/'public'),'-I'+str(sdk/'public')]
    private=[*public,'-I'+str(app/'include'),'-I'+str(sdk/'include')]
    def compile_source(item):
        i,path=item;obj=work/f'{i}.o';subprocess.run([*private,'-c',str(app/path),'-o',str(obj)],check=True);return str(obj)
    start=time.perf_counter()
    with ThreadPoolExecutor(max_workers=8) as pool:objects=list(pool.map(compile_source,enumerate(manifest['sources'])))
    storage=time.perf_counter()-start;start=time.perf_counter()
    subprocess.run([*public,'-I'+str(root/'tests/runtime/native'),'-c',str(work/'caller.cpp'),'-o',str(work/'caller.o')],check=True)
    consumer=time.perf_counter()-start
    subprocess.run(['clang++',*flags,str(work/'caller.o'),*objects,str(args.core.resolve()),'-pthread','-o',str(work/'proof')],check=True)
    subprocess.run([str(work/'proof')],check=True)
    (app/'lib').mkdir();subprocess.run(['ar','rcs',str(app/'lib/libkeys.a'),*objects],check=True);seal_package(app,['keys'])
    reused=concrete_types.generate(request,work/'reuse',sdk,[sdk,app],'clang++',[]);assert not reused['sources']
    print(f'PASS: public-only identity keys and sealed reuse; {len(objects)} units at 8 jobs={storage:.4f}s public caller={consumer:.4f}s')
