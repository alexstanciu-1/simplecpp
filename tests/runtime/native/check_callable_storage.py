"""Signature-driven callable classes, owned captures and ordinary publication."""
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
metadata=concrete_types.metadata(root/'runtime')
def plan(envelope,error=None):
    out=subprocess.run([str(args.tool)],input=json.dumps(envelope),text=True,capture_output=True)
    if error:
        assert out.returncode and error in out.stderr,out.stderr
        return
    assert out.returncode==0,out.stderr
    return json.loads(out.stdout)
signatures=[{'returns':'int64','parameters':[{'type':'int64'}]},
            {'returns':'int64','parameters':[{'type':'int64','passing':'ref'}],'return_origin':0},
            {'returns':'void','parameters':[{'type':'int64','passing':'ref'},{'type':'int64','passing':'const_ref'}]}]
normalized=[plan({'action':'resolve_signature','metadata':metadata,'request':signature,'leaves':{},'names':{}}) for signature in signatures]
keys=[item['key'] for item in normalized]
leaves={key:{'signature':signature} for key,signature in zip(keys,signatures)}
leaves['owned_closure']={'cpp':'fixture::environment','header':'environment.hpp','owner':'application.callables','capabilities':['copy_storage']}
callback_types=['callable<'+key+'>' for key in keys]
names=dict(zip(callback_types,['callback_int','callback_ref','callback_void']))
# Variadic source identity remains distinct from its physical vector input.
for signature, name in [
    ({'returns':'int64','parameters':[{'type':'vector<int64>','variadic':True}]},'callback_pack'),
    ({'returns':'int64','parameters':[{'type':'vector<int64>'}]},'callback_vector'),
    ({'returns':callback_types[0],'parameters':[{'type':callback_types[0]}]},'callback_nested'),
    ({'returns':'boolean','parameters':[{'type':'int64'}]},'callback_predicate')]:
    normalized=plan({'action':'resolve_signature','metadata':metadata,'request':signature,'leaves':leaves,'names':names})
    key=normalized['key'];keys.append(key);signatures.append(signature)
    leaves[key]={'signature':signature};callback_types.append('callable<'+key+'>');names[callback_types[-1]]=name
assert keys[3]!=keys[4]
compositions={'vector<boolean>':'vector_boolean','hash<int64,string>':'hash_int64','vector<int64>':'vector_int64','vector<'+callback_types[0]+'>':'vector_callbacks',
              'hash<'+callback_types[0]+',string>':'hash_callbacks',
              **{family+'<'+callback_types[0]+'>':family+'_callback' for family in ['result','result_or_false','result_or_bool']}}
names.update(compositions)
functions={
 'make_owned_callback':{'returns':callback_types[0],'parameters':[{'name':'offset','type':'int64'}],'transport':'native',
  'includes':['environment.hpp'],
  'body':'fixture::environment captured(offset); return scpp::callable_t<scpp::int64(scpp::int64)>([captured](scpp::int64 value) { if (value == scpp::int64(-1)) throw scpp::runtime_error("callback failure"); return value + captured.offset; }, true);'},
 'make_reference_callback':{'returns':callback_types[1],'parameters':[],'transport':'native',
  'expression':'scpp::callable_t<scpp::int64 &(scpp::int64 &)>([](scpp::int64 &value) -> scpp::int64 & { value += scpp::int64(1); return value; }, true)'},
 'make_void_callback':{'returns':callback_types[2],'parameters':[],'transport':'native',
  'expression':'scpp::callable_t<void(scpp::int64 &, const scpp::int64 &)>([](scpp::int64 &value, const scpp::int64 &delta) { value += delta; }, true)'},
 'callback_permissions':{'returns':'uint64','parameters':[{'name':'callback','type':callback_types[0],'passing':'const_ref'}],
  'transport':'native','expression':'static_cast<std::uint64_t>(${native:callback}.transfer_permissions())','includes':['cstdint']}}
functions['make_pack_callback']={'returns':callback_types[3],'parameters':[], 'transport':'native','uses':['vector<int64>'],
 'expression':'scpp::callable_t<scpp::int64(scpp::vector_int64)>([](scpp::vector_int64 values) { scpp::int64 sum(0); for (scpp::uint64 i(0); i < values.size(); ++i) sum += values.at(i); return sum; }, true)'}
functions['make_nested_callback']={'returns':callback_types[5],'parameters':[], 'transport':'native','uses':[callback_types[0]],
 'expression':'scpp::callable_t<scpp::callback_int(scpp::callback_int)>([](scpp::callback_int value) { return value; }, true)'}
functions['make_predicate']={'returns':callback_types[6],'parameters':[], 'transport':'native',
 'expression':'scpp::callable_t<scpp::boolean(scpp::int64)>([](scpp::int64 value) { return scpp::boolean(value.native_value() > 3); }, true)'}
collections={}
for carrier,label in [('vector<int64>','VECTOR'),('hash<int64,string>','HASH')]:
    collections['MAP_'+label]=('typed_collection_map',[carrier,carrier,callback_types[0]])
    collections['FILTER_'+label]=('typed_collection_filter',[carrier,'int64',callback_types[6]])
collections['MAP_BOOLEAN']=('typed_collection_map',['vector<int64>','vector<boolean>',callback_types[6]])
collection_apis={name:family+'<'+','.join(types)+'>' for name,(family,types) in collections.items()}
factory='callable_from<'+keys[0]+',owned_closure>'
data={'package':'application.callables','roots':[*callback_types,*compositions],'leaves':leaves,'names':names,
      'application_functions':functions,'apis':[*functions,factory,*collection_apis.values()]}
def binding(type):
    arguments={'Signature':'source.'+keys[callback_types.index(type)]} if type in callback_types else {}
    if type in compositions:
        family=type.split('<')[0]
        arguments={'V':'source.'+('int64' if type=='hash<int64,string>' else callback_types[0]),'K':'source.string'} if family=='hash' else {'T':'source.'+('int64' if type=='vector<int64>' else 'boolean' if type=='vector<boolean>' else callback_types[0])}
    return {'source':'source.'+type,'physical':type,'arguments':arguments}
bindings={f'root_{i}':binding(type) for i,type in enumerate(data['roots'])}
for key,signature in zip(keys,signatures):
    bindings['signature_'+key+'_return']=binding(signature['returns'])
    for i,parameter in enumerate(signature['parameters']):bindings['signature_'+key+'_parameter_'+str(i)]=binding(parameter['type'])
for name,operation in functions.items():
    bindings['function_'+name+'_return']=binding(operation['returns'])
    for i,parameter in enumerate(operation['parameters']):bindings['function_'+name+'_parameter_'+str(i)]=binding(parameter['type'])
    for i,type in enumerate(operation.get('uses',[])):bindings['function_'+name+'_uses_'+str(i)]=binding(type)
bindings[f'api_{len(functions)}_Signature']=binding(keys[0])
bindings[f'api_{len(functions)}_Closure']=binding('owned_closure')
for i,(family,types) in enumerate(collections.values(),start=len(functions)+1):
    for parameter,type in zip(metadata['function_families'][family]['parameters'],types):bindings[f'api_{i}_{parameter}']=binding(type)
dependencies={f'signature_{i}':binding(key) for i,key in enumerate(keys)}
dependencies['string']=binding('string')
requirements=[{'subject':slot,'contract':'application_formed'} for slot in bindings]
requirements += [{'subject':'dependency:'+slot,'contract':'signature_admitted'} for slot in dependencies]
requirements += [{'subject':'operation','contract':contract} for contract in ['owned_capture_and_signature_admitted','collection_exact_signature_and_no_mutation']]
prepared={'version':'prepared-api-v1','producer':'fixture.semantic-owner','revision':'fixture-v1','action':'generate',
 'request':copy.deepcopy(data),'admission':'fixture/callables','bindings':bindings,'dependencies':dependencies,
 'requirements':requirements,'facts':[{**r,'evidence':'fixture/'+r['subject']} for r in requirements]}
envelope={'metadata':metadata,'request':data,'leaves':leaves,'names':names,'imports':{},'layouts':{},
          'action':'generate','mode':'source','prepared':prepared}
result=plan(envelope)
assert set(callback_types).issubset(result['types'])
assert set(compositions).issubset(result['types'])
for unit in result['units']:
    assert 'std::function' not in unit['header'] and 'template' not in unit['header']
for bad_change,error in [('storage','Signature parameter'),('mode','Unknown family parameter kind'),
                         ('operation','Operation requires a signature'),('override','signature operation')]:
    bad=copy.deepcopy(envelope);bad.pop('prepared');bad['mode']='native'
    if bad_change=='storage':bad['request']['roots']=['callable<int64>']
    elif bad_change=='mode':bad['metadata']['families']['callable']['parameter_kinds']['Signature']='arbitrary'
    elif bad_change=='operation':bad['metadata']['families']['callable']['classes'][0]['methods'][0]['signature_from']='Missing'
    else:bad['metadata']['families']['callable']['classes'][0]['methods'][0]['parameters']=[]
    plan(bad,error)
bad=copy.deepcopy(envelope);bad['prepared']['facts']=[f for f in bad['prepared']['facts'] if f['subject']!='operation']
plan(bad,'owned_capture_and_signature_admitted')
bad['api_imports']=result['apis']
plan(bad,'owned_capture_and_signature_admitted')
print('PASS: signature roles, generic invocation expansion, prepared capture facts and template-free declarations')
if not args.core:sys.exit(0)
fixture='''#pragma once
#include "scpp/integers.hpp"
namespace fixture {
struct environment {
    static inline int live=0, copies=0;
    scpp::int64 offset;
    explicit environment(scpp::int64 value):offset(value) { ++live; }
#ifdef SCPP_NONCOPYABLE_CLOSURE
    environment(const environment &)=delete;
#else
    environment(const environment &other):offset(other.offset) { ++live; ++copies; }
#endif
    ~environment() { --live; }
    scpp::int64 operator()(scpp::int64 value) const { return value + this->offset; }
};
}
'''
with tempfile.TemporaryDirectory(prefix='scpp-callables-') as directory:
    work=Path(directory);sdk=work/'sdk';app=work/'app'
    for folder in ['metadata','include','public']:shutil.copytree(root/'runtime'/folder,sdk/folder)
    (sdk/'lib').mkdir();(sdk/'tools').mkdir()
    shutil.copy2(args.core,sdk/'lib/libcore.a');shutil.copy2(args.tool,sdk/'tools/scpp_api_generator')
    seal_package(sdk,['core']);os.environ['SCPP_API_GENERATOR']=str(sdk/'tools/scpp_api_generator')
    (app/'public').mkdir(parents=True);(app/'public/environment.hpp').write_text(fixture)
    request=work/'request.json';request.write_text(json.dumps({'mode':'source','prepared':prepared,'request':data}))
    manifest=concrete_types.generate(request,app,sdk,[sdk],'clang++',[])
    caller='\n'.join('#include "'+manifest['apis'][name]['header']+'"' for name in data['apis'])+'\n'
    caller+='\n'.join('#include \"'+manifest['types'][type]['header']+'\"' for type in compositions)+'\n'
    caller+='\n'.join('#define '+name+' scpp::'+manifest['apis'][name]['operation']['name'] for name in functions)+'\n'
    caller+='\n'.join('#define '+name+' scpp::'+manifest['apis'][api]['operation']['name'] for name,api in collection_apis.items())+'\n'
    caller+='#define FROM_CLOSURE scpp::'+manifest['apis'][factory]['operation']['name']+'\n'
    caller+=(root/'tests/runtime/native/public_callable_storage.cpp').read_text();(work/'caller.cpp').write_text(caller)
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
    factory_source=app/manifest['apis'][factory]['source']
    rejected=subprocess.run([*private,'-DSCPP_NONCOPYABLE_CLOSURE','-fsyntax-only',str(factory_source)],capture_output=True,text=True)
    assert rejected.returncode and 'is_copy_constructible' in rejected.stderr,rejected.stderr
    print('PASS: noncopyable closure rejected at the compiled conversion boundary')
    (app/'lib').mkdir();subprocess.run(['ar','rcs',str(app/'lib/libcallables.a'),*objects],check=True);seal_package(app,['callables'])
    reused=concrete_types.generate(request,work/'reuse',sdk,[sdk,app],'clang++',[]);assert not reused['sources']
    print(f'PASS: public-only callable lifetime and sealed reuse; {len(objects)} units at 8 jobs={storage:.4f}s public caller={consumer:.4f}s')
