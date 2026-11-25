"""Bounded carrier text plans, ordered public execution and publication reuse."""
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
families=['nullable','result','result_or_false','result_or_bool']
names={f+'<boolean>':'text_boolean_'+str(i) for i,f in enumerate(families)}
names.update({'nullable<float64>':'optional_float','result<string>':'result_text'})
for i,f in enumerate(families): names.setdefault(f+'<string>','text_string_'+str(i))
published_types=json.loads((root/'runtime/public/specializations.json').read_text())['types']
names.update({identity:published_types[identity]['name'] for identity in names if identity in published_types})
request={'package':'application.carrier_text','roots':list(names),'names':names}

def resolve(left,right,meta=metadata,error=None,compound=False):
    p=subprocess.run([str(args.tool)],input=json.dumps({'metadata':meta,'names':names,
        'action':'resolve_compound' if compound else 'resolve_operator','request':{'operation':'concat_assign' if compound else 'concat','target' if compound else 'left':left,'right':right}}),text=True,capture_output=True)
    if error:
        assert p.returncode and error in p.stderr,p.stderr
        return
    assert p.returncode==0,p.stderr
    return json.loads(p.stdout)
for family in families:
    for payload in ['int64','float64','boolean','string']:
        source=family+'<'+payload+'>'
        for other in ['string','null','uint32','result_or_bool<boolean>']:
            for left,right in [(source,other),(other,source)]:
                p=resolve(left,right)
                assert p['kind']=='converted_carrier_binary'
                assert p['steps']==['evaluate_left','convert_left','evaluate_right','convert_right','calculate']
                side='left' if left==source else 'right';c=p[side+'_conversion']
                assert c['kind']=='carrier_conversion'
                if payload!='string':assert c['payload_conversion']['api']=='concat_text_'+payload
                else:assert c['payload_conversion']['kind']=='identity'
    for bad in ['int8','uint64','mixed','dynamic','nullable<int64>','vector<string>']:
        resolve(family+'<'+bad+'>','string',error='Unsupported carrier conversion payload')
for bad in ['mixed','dynamic','uint64']:
    resolve('nullable<int64>',bad,error='Unsupported carrier conversion peer')
    resolve(bad,'nullable<int64>',error='Unsupported carrier conversion peer')
changed=copy.deepcopy(metadata);changed['families']['holder']=copy.deepcopy(changed['families']['nullable'])
assert resolve('holder<boolean>','string',changed)['kind']=='converted_carrier_binary'
changed=copy.deepcopy(metadata);changed['global_operators']['operations']['concat']['carrier_operands']['state_cast']='int64'
resolve('nullable<boolean>','string',changed,'must match selected operand type')
changed=copy.deepcopy(metadata);del changed['global_operators']['operations']['concat']['operand_conversion_function_set']
resolve('nullable<boolean>','string',changed,'requires a binary conversion owner')
changed=copy.deepcopy(metadata);del changed['families']['nullable']['carrier']['states']
resolve('nullable<boolean>','string',changed,'requires state descriptions')
print('PASS: bounded text domain, metadata-selected state policy, payload formatter ownership and rejected widening')
headers=set();functions=[]

def convert(plan,var):
    if plan['kind']=='identity':return var
    if plan['kind']=='call':
        headers.add(plan['header']);return plan['function']+'('+var+')'
    assert plan['kind']=='carrier_conversion'
    headers.add(plan['projection']['header'])
    payload=plan['payload_conversion'];projection=plan['projection']['expression'].replace('${value}',var)
    body=''
    for state in plan['states']:
        conversion=state['conversion']
        if conversion['kind']=='expression':headers.add(conversion['header']);branch='return '+conversion['expression']+';'
        else:
            branch='const auto payload='+projection+'; return '+convert(payload,'payload')+';'
        body+=('if ('+state['test_expression'].replace('${value}',var)+') { '+branch+' }' if 'test_expression' in state else branch)
    return '([&]() -> scpp::string { '+body+' })()'

def emit(left,right):
    plan=resolve(left,right);name='join_'+str(len(functions));headers.add(plan['calculation']['header'])
    for t in [left,right]:
        headers.add('scpp/'+names[t]+'.hpp' if t in names else metadata['types'][t]['header'])
    cpp=lambda t:'scpp::'+names[t] if t in names else metadata['types'][t]['cpp']
    a=convert(plan['left_conversion'],'left');b=convert(plan['right_conversion'],'right')
    body='const auto left_text='+a+'; ++effects; const auto right_text='+b+'; return '+plan['calculation']['function']+'(left_text,right_text);'
    functions.append('static scpp::string '+name+'(const '+cpp(left)+' &left, const '+cpp(right)+' &right, int &effects) { '+body+' }')
    return name
checks=[]
for i,f in enumerate(families):
    t=f+'<boolean>';cpp='scpp::'+names[t];fn=emit(t,'string');reverse=emit('string',t)
    checks += [f'CHECK({fn}({cpp}(boolean(true)),string("x"),effects).equals("1x"));',
               f'CHECK({fn}({cpp}(boolean(false)),string("x"),effects).equals("x"));',
               f'CHECK({reverse}(string("x"),{cpp}(boolean(true)),effects).equals("x1"));']
    if f=='nullable':checks.append(f'CHECK({fn}({cpp}(),string("x"),effects).equals("x"));')
    elif f!='result':
        checks.append(f'CHECK({fn}({cpp}::from_false(),string("x"),effects).equals("x"));')
        if f=='result_or_bool':checks.append(f'CHECK({fn}({cpp}::from_true(),string("x"),effects).equals("x"));')
result_left=emit('result<boolean>','string');result_right=emit('string','result<boolean>')
float_join=emit('nullable<float64>','string');bytes_join=emit('result<string>','string')
cross_join=emit('result_or_bool<boolean>','nullable<boolean>')
headers.add('scpp/text/concat_text_float64.hpp')

compound_checks=[];compound_failures=[]
for family in families:
    target=family+'<string>'
    for right in ['string','null','int8','nullable<boolean>','result_or_bool<float64>']:
        p=resolve(target,right,compound=True)
        assert p['kind']=='ordered_compound' and p['result_type']==target
        assert p['writeback_conversion']['kind']=='construct_payload'
        assert p['steps']==['capture_destination_and_retain_owners','snapshot_converted_left','evaluate_right','convert_right','calculate','convert_writeback','commit_destination','return_value_snapshot']
    for invalid in ['uint64','mixed','dynamic','nullable<int8>','nullable<nullable<string>>']:
        resolve(target,invalid,compound=True,error='Unsupported carrier conversion')
    resolve(family+'<boolean>','string',compound=True,error='Unsupported compound destination payload')
plain=resolve('string','nullable<boolean>',compound=True)
assert plain['writeback_conversion']['kind']=='identity'
changed=copy.deepcopy(metadata);del changed['global_operators']['operations']['concat']['compound_left_snapshot']
resolve('nullable<string>','string',changed,'requires converted operand snapshots',True)
changed=copy.deepcopy(metadata)
del changed['families']['nullable']['carrier']['states'][0]['reconstruct']
resolve('nullable<string>','string',changed,'requires one payload construction',True)

def emit_compound(target,right):
    p=resolve(target,right,compound=True);name='update_'+str(len(functions))
    cpp=lambda t:'scpp::'+names[t] if t in names else metadata['types'][t]['cpp']
    for t in [target,right]: headers.add('scpp/'+names[t]+'.hpp' if t in names else metadata['types'][t]['header'])
    headers.add(p['calculation']['header'])
    a=convert(p['left_conversion'],'(*captured)');b=convert(p['right_conversion'],'right')
    writeback=p['writeback_conversion']
    if writeback['kind']=='construct_payload':
        headers.add(writeback['header']);expression=writeback['expression'].replace('${value}','calculated')
    else:assert writeback['kind']=='identity';expression='calculated'
    body='auto *captured=&destination; const auto left_text='+a+'; ++effects; if (mutate) *captured=scpp::string("rhs"); if (fail) throw 17; const auto right_text='+b+'; const auto calculated='+p['calculation']['function']+'(left_text,right_text); const auto prepared='+expression+'; *captured=prepared; return *captured;'
    functions.append('static '+cpp(target)+' '+name+'('+cpp(target)+' &destination, const '+cpp(right)+' &right, int &effects, bool mutate=false, bool fail=false) { '+body+' }')
    return name

for family in families:
    target=family+'<string>';cpp='scpp::'+names[target]
    fn=emit_compound(target,'string');alias=emit_compound(target,target)
    rhs_failure=emit_compound(target,'result<boolean>')
    if family=='nullable': property_update=fn
    compound_checks += [f'{{ {cpp} destination(string("old")); int effects=0; auto result={fn}(destination,string("!"),effects); CHECK(effects==1 && destination.value().equals("old!") && result.value().equals("old!")); destination=string("later"); CHECK(result.value().equals("old!")); }}',
                        f'{{ {cpp} destination(string("old")); int effects=0; auto result={alias}(destination,destination,effects,true); CHECK(effects==1 && result.value().equals("oldrhs") && destination.value().equals("oldrhs")); }}']
    if family!='result':
        initial=cpp+'()' if family=='nullable' else cpp+'::from_false()'
        compound_checks.append(f'{{ auto destination={initial}; int effects=0; auto result={fn}(destination,string("new"),effects); CHECK(effects==1 && result.value().equals("new") && destination.has_value()); }}')
        if family=='result_or_bool':compound_checks.append(f'{{ auto destination={cpp}::from_true(); int effects=0; (void){fn}(destination,string("new"),effects); CHECK(effects==1 && destination.has_value() && destination.value().equals("new")); }}')
    compound_failures += [f'{{ {cpp} destination(string("old")); int effects=0; bool caught=false; try {{ (void){rhs_failure}(destination,failed,effects); }} catch (...) {{ caught=true; }} CHECK(caught && effects==1 && destination.value().equals("old")); }}',
                         f'{{ {cpp} destination(string("old")); int effects=0; bool caught=false; try {{ (void){fn}(destination,string("unused"),effects,true,true); }} catch (...) {{ caught=true; }} CHECK(caught && effects==1 && destination.value().equals("rhs")); }}']
    if family=='result':compound_failures.append(f'{{ {cpp} destination(error_value::make(string("failure"))); int effects=0; bool caught=false; try {{ (void){fn}(destination,string("later"),effects); }} catch (...) {{ caught=true; }} CHECK(caught && effects==0 && destination.has_error()); }}')
plain_update=emit_compound('string','result_or_bool<boolean>')
compound_checks.append(f'{{ string destination("a"); int effects=0; CHECK({plain_update}(destination,text_boolean_3(boolean(true)),effects).equals("a1") && destination.equals("a1") && effects==1); }}')
compound_checks.append(f'{{ struct holder {{ text_string_0 value{{string("member")}}; int captures=0; text_string_0 &select() {{ ++this->captures; return this->value; }} }} owner; int effects=0; auto result={property_update}(owner.select(),string("!"),effects); CHECK(owner.captures==1 && effects==1 && owner.value.value().equals("member!") && result.value().equals("member!")); }}')
print('PASS: compound carrier destination policy, payload construction, named bindings and rejected write-back widening')

if not args.core:sys.exit(0)
with tempfile.TemporaryDirectory(prefix='scpp-carrier-text-') as directory:
    work=Path(directory);sdk=work/'sdk';app=work/'app'
    for folder in ['metadata','include','public']:shutil.copytree(root/'runtime'/folder,sdk/folder)
    (sdk/'lib').mkdir();(sdk/'tools').mkdir()
    shutil.copy2(args.core,sdk/'lib/libcore.a');shutil.copy2(args.tool,sdk/'tools/scpp_api_generator')
    seal_package(sdk,['core']);os.environ['SCPP_API_GENERATOR']=str(sdk/'tools/scpp_api_generator')
    request_file=work/'request.json';request_file.write_text(json.dumps(request))
    manifest=concrete_types.generate(request_file,app,sdk,[sdk],'clang++',[])
    assert len(manifest['sources'])==sum(identity not in published_types for identity in names)
    code='\n'.join('#include "'+h+'"' for h in sorted(headers))+r'''
#include "public_api_guard.hpp"
#include <cstdlib>
#include <cstdio>
#include <new>
static int allocations=0;
void *operator new(__SIZE_TYPE__ n) { ++allocations; if(auto p=std::malloc(n)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,__SIZE_TYPE__) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"failed line %d\n",__LINE__);std::abort(); } } while(false)
'''
    code+='\n'.join(functions)+'\nint main() { using namespace scpp; int effects=0; const int before=allocations;\n'+'\n'.join(checks+compound_checks)
    code+=f'''
CHECK({cross_join}(text_boolean_3::from_true(),text_boolean_0(boolean(true)),effects).equals("1"));
const char raw[]={{'a',0,'b',char(0xc3),char(0xa9)}};
const string bytes(raw,uint64(5));
const auto joined={bytes_join}(result_text(bytes),string("!"),effects);
CHECK(joined.byte_size()==6 && joined.byte_at(1)==0 && joined.byte_at(5)=='!');
CHECK(allocations==before);
const float64 number(1.23456789012345);const int float_before=allocations;
const auto floating_text={float_join}(optional_float(number),string(),effects);
std::printf("Float carrier text allocations=%d\\n",allocations-float_before);
CHECK(floating_text.equals(concat_text(number)));
const text_boolean_1 failed(error_value::make(string("failure"),int64(7),string("input")));
effects=0;bool rejected=false;
try {{ (void){result_left}(failed,string("later"),effects); }} catch (...) {{ rejected=true; }}
CHECK(rejected && effects==0 && failed.has_error());
effects=0;rejected=false;string destination("old");
try {{ destination={result_right}(string("earlier"),failed,effects); }} catch (...) {{ rejected=true; }}
CHECK(rejected && effects==1 && destination.equals("old") && failed.has_error());
COMPOUND_FAILURES
std::puts("PASS: carrier text states, formatting, binary bytes and ordered failures; short success allocations=0");
}}
'''
    code=code.replace('COMPOUND_FAILURES','\n'.join(compound_failures))
    caller=work/'caller.cpp';caller.write_text(code)
    flags=['-std=c++23','-O1']
    if args.sanitize:flags+=['-fsanitize=address,undefined','-fno-omit-frame-pointer']
    common=['clang++',*flags,'-I'+str(app/'public'),'-I'+str(sdk/'public')]
    objects=[];start=time.perf_counter()
    for i,source in enumerate(manifest['sources']):
        obj=work/f'{i}.o';objects.append(str(obj))
        subprocess.run([*common,'-I'+str(app/'include'),'-I'+str(sdk/'include'),'-c',str(app/source),'-o',str(obj)],check=True)
    storage=time.perf_counter()-start;start=time.perf_counter()
    subprocess.run([*common,'-I'+str(root/'tests/runtime/native'),'-c',str(caller),'-o',str(work/'caller.o')],check=True)
    caller_time=time.perf_counter()-start
    subprocess.run(['clang++',*flags,str(work/'caller.o'),*objects,str(args.core.resolve()),'-pthread','-o',str(work/'proof')],check=True)
    subprocess.run([str(work/'proof')],check=True)
    (app/'lib').mkdir();subprocess.run(['ar','rcs',str(app/'lib/libtext.a'),*objects],check=True)
    seal_package(app,['text']);reused=concrete_types.generate(request_file,work/'reuse',sdk,[sdk,app],'clang++',[])
    assert not reused['sources']
    print(f'PASS: sealed reuse; storage compile={storage:.4f}s caller compile={caller_time:.4f}s')
