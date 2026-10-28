"""Prepared application function publication and retained property receivers."""
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
leaves={'record':{'cpp':'fixture::record','header':'member_record.hpp','owner':'application.members',
                  'capabilities':['default_storage']}}
names={'vector<int64>':'member_values','shared<record>':'member_owner','vector<shared<record>>':'member_owners'}
receiver={'name':'owner','type':'shared<record>','passing':'const_ref'}
functions={
 'read_member':{'returns':'int64','parameters':[receiver], 'expression':'${native:owner}.deref().value'},
 'write_member':{'returns':'int64','parameters':[receiver,{'name':'value','type':'int64'}],
                 'body':'auto &object = ${native:owner}.deref(); object.value = value; return object.value;'}}
paths={name:{'root':'vector<int64>','depth':1,'operation':operation}
       for name,operation in [('member_value_read','existing_read'),('member_value_store','existing_store')]}
functions.update({
 'replace_values':{'returns':'void','parameters':[receiver,{'name':'value','type':'vector<int64>','passing':'const_ref'}],
                   'expression':'${native:owner}.deref().values = value'},
 'read_field_value':{'returns':'int64','parameters':[receiver,{'name':'key','type':'int64'}],
                     'expression':'scpp::path_member_value_read(${native:owner}.deref().values, key)',
                     'includes':['scpp/access/member_value_read.hpp']},
 'write_field_value':{'returns':'int64','parameters':[receiver,{'name':'key','type':'int64'},{'name':'value','type':'int64'}],
                      'body':'scpp::path_member_value_store(${native:owner}.deref().values, key, value); return value;',
                      'includes':['scpp/access/member_value_store.hpp']}})
data={'package':'application.members','roots':['vector<int64>','vector<shared<record>>'],'leaves':leaves,'names':names,
      'application_functions':functions,'paths':paths,'apis':[*paths,*functions]}
def binding(physical,arguments=None):
    return {'source':'source.'+physical,'physical':physical,'arguments':{k:'source.'+v for k,v in (arguments or {}).items()}}
bindings={'root_0':binding('vector<int64>',{'T':'int64'}),'root_1':binding('vector<shared<record>>',{'T':'shared<record>'})}
for name in paths:
    bindings['path_'+name+'_root']=binding('vector<int64>',{'T':'int64'})
    for suffix in ['value','key_0','element_0']:bindings['path_'+name+'_'+suffix]=binding('int64')
for name,operation in functions.items():
    prefix='function_'+name+'_'
    bindings[prefix+'return']=binding(operation['returns'])
    for i,parameter in enumerate(operation['parameters']):
        type=parameter['type']
        bindings[prefix+'parameter_'+str(i)]=binding(type,{'T':'record'} if type=='shared<record>' else {'T':'int64'} if type=='vector<int64>' else {})
dependencies={'target':binding('record')}
requirements=[{'subject':slot,'contract':'application_formed'} for slot in bindings]
requirements += [{'subject':'dependency:target','contract':'application_formed'},
                 {'subject':'operation','contract':'member_access_and_retention_admitted'}]
prepared={'version':'prepared-api-v1','producer':'fixture.semantic-owner','revision':'fixture-v1','action':'generate',
          'request':copy.deepcopy(data),'admission':'fixture/member-access','bindings':bindings,'dependencies':dependencies,
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
assert set(result['apis'])==set(data['apis'])
for imported in [{},result['apis']]:
    bad=copy.deepcopy(envelope);bad['api_imports']=imported
    bad['prepared']['facts']=[f for f in bad['prepared']['facts'] if f['subject']!='operation']
    plan(bad,'member_access_and_retention_admitted')
bad=copy.deepcopy(envelope);del bad['prepared']['bindings']['function_read_member_parameter_0']
plan(bad,'function_read_member_parameter_0')
bad=copy.deepcopy(envelope);bad.pop('prepared');bad['mode']='native'
bad['request']['application_functions']['read_member']['name']='injected'
plan(bad,'application function')
bad=copy.deepcopy(envelope);bad['api_imports']=result['apis']
bad['api_imports']['read_member']['operation']['expression']='different'
plan(bad,'Conflicting API ownership')
# Undemanded application operations do not emit code or materialize signature types.
undemanded=copy.deepcopy(envelope);undemanded.pop('prepared');undemanded['mode']='native'
undemanded['request']['roots']=[];undemanded['request']['apis']=[]
assert not plan(undemanded)['units']
print('PASS: application signature facts, admission, demand-only emission and ownership conflicts')
if not args.core:sys.exit(0)
fixture='''#pragma once
#include "scpp/integers.hpp"
#include "scpp/member_values.hpp"
namespace fixture {
struct record {
    static inline int live=0;
    scpp::int64 value{0};
    scpp::member_values values;
    record() { ++live; }
    ~record() { --live; }
    record(const record &)=delete;
    record &operator=(const record &)=delete;
};
}
'''
with tempfile.TemporaryDirectory(prefix='scpp-application-functions-') as directory:
    work=Path(directory);sdk=work/'sdk';app=work/'app'
    for folder in ['metadata','include','public']:shutil.copytree(root/'runtime'/folder,sdk/folder)
    (sdk/'lib').mkdir();(sdk/'tools').mkdir()
    shutil.copy2(args.core,sdk/'lib/libcore.a');shutil.copy2(args.tool,sdk/'tools/scpp_api_generator')
    seal_package(sdk,['core']);os.environ['SCPP_API_GENERATOR']=str(sdk/'tools/scpp_api_generator')
    (app/'public').mkdir(parents=True);(app/'public/member_record.hpp').write_text(fixture)
    request=work/'request.json';request.write_text(json.dumps({'mode':'source','prepared':prepared,'request':data}))
    manifest=concrete_types.generate(request,app,sdk,[sdk],'clang++',[])
    caller='\n'.join('#include "'+manifest['types'][identity]['header']+'"' for identity in data['roots'])+'\n'
    caller+='\n'.join('#include "'+manifest['apis'][name]['header']+'"' for name in functions)+'\n'
    caller+='\n'.join('#define '+name+' scpp::'+manifest['apis'][name]['operation']['name'] for name in functions)+'\n'
    caller+=(root/'tests/runtime/native/public_application_functions.cpp').read_text();(work/'caller.cpp').write_text(caller)
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
    print(f'PASS: public-only application functions and sealed reuse; {len(objects)} units at 8 jobs={storage:.4f}s public caller={consumer:.4f}s')
