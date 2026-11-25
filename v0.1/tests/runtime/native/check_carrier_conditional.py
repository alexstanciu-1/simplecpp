"""Exact scalar/boxed carrier ternary and Elvis selection with state-preserving joins."""
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
parser.add_argument('--boxed',action='store_true',help='Exercise the mixed/dynamic domain separately from canonical scalars')
args=parser.parse_args();args.tool=args.tool.resolve()
metadata=concrete_types.metadata(root/'runtime')
public_types={**metadata['types'],**json.loads((root/'runtime/public/specializations.json').read_text())['types']}
payloads=['mixed','dynamic'] if args.boxed else ['int64','float64','boolean','string']
families=['nullable','result','result_or_false','result_or_bool']
names={f+'<'+t+'>':'conditional_'+str(i)+'_'+t for i,f in enumerate(families) for t in payloads}
published_types=json.loads((root/'runtime/public/specializations.json').read_text())['types']
names.update({identity:published_types[identity]['name'] for identity in names if identity in published_types})
request={'package':'application.carrier_conditional','roots':list(names),'names':names}

def resolve(left,right,form='ternary',meta=metadata,error=None,condition='boolean'):
    request={'form':form,'then':left,'else':right}
    if form=='ternary':request['condition']=condition
    p=subprocess.run([str(args.tool)],input=json.dumps({'metadata':meta,'names':names,'action':'resolve_conditional','request':request}),text=True,capture_output=True)
    if error:
        assert p.returncode and error in p.stderr,p.stderr
        return
    assert p.returncode==0,p.stderr
    return json.loads(p.stdout)
for t in payloads:
    assert resolve(t,t)['result_type']==t
    for family in families:
        source=family+'<'+t+'>'
        for left,right in [(source,t),(t,source),(source,source)]:
            for form in ['ternary','elvis']:
                p=resolve(left,right,form)
                assert p['result_type']==source and p['form']==form
                assert p['then_conversion']['kind']==('identity' if left==source else 'construct_payload')
                assert p['else_conversion']['kind']==('identity' if right==source else 'construct_payload')
        for other in families:
            if family!=other:resolve(source,other+'<'+t+'>',error='Unsupported conditional join')
resolve('nullable<int64>','float64',error='Unsupported conditional join')
resolve('mixed','dynamic',error='Unsupported conditional join')
resolve('result<mixed>','int64',error='Unsupported conditional join')
for t in ['int8','nullable<int64>','vector<string>']:
    resolve('result<'+t+'>','result<'+t+'>',error='Unsupported conditional payload')
resolve('int64','int64',condition='vector<string>',error='Unsupported condition operand')
changed=copy.deepcopy(metadata);changed['families']['holder']=copy.deepcopy(changed['families']['nullable'])
changed['selections']['conditional']['carriers'].append('holder')
assert resolve('holder<int64>','int64',meta=changed)['result_type']=='holder<int64>'
changed=copy.deepcopy(metadata);changed['types']['string']['capabilities'].remove('copy_storage')
resolve('int64','string',meta=changed,error='requires copying')
changed=copy.deepcopy(metadata);changed['selections']['conditional']['forms'].append('any')
resolve('int64','int64',meta=changed,error='Unknown conditional selection join form')
print('PASS: exact ternary/Elvis joins, condition admission, renamed family and copying requirements')
headers=set();functions=[];checks=[];failures=[];executors={};boxed_checks=[]
def cpp(t):return 'scpp::'+names[t] if t in names else public_types[t]['cpp']
def value(t,n):
    if t in ['mixed','dynamic']:return 'scpp::'+t+'(scpp::int64('+str(n)+'))'
    return 'scpp::string("'+('0' if n==0 else 'value')+'")' if t=='string' else 'scpp::'+t+'('+str(bool(n)).lower()+')' if t=='boolean' else 'scpp::'+t+'('+str(n)+')'
def equal(t,var,n):
    if t in ['mixed','dynamic']:return var+'.get_int().native_value()=='+str(n)
    return var+'.equals('+value(t,n)+')' if t=='string' else var+'.native_value()=='+('true' if n else 'false') if t=='boolean' else var+'.native_value()=='+str(n)
def cast(p,v):
    if p['kind']=='identity':return v
    headers.add(p['header']);return p['expression'].replace('${value}',v)
def truth(p,v):
    if p['kind']=='call':headers.add(p['header']);return p['function']+'('+v+')'
    assert p['kind']=='carrier_condition';headers.add(p['projection']['header']);body=''
    for state in p['states']:
        branch='return scpp::boolean('+state['condition']+');' if state['condition']!='payload' else ('return '+p['payload_expression'].replace('${value}',v)+';' if p['payload_access']=='scoped_operation' else 'const auto payload='+p['projection']['expression'].replace('${value}',v)+'; return '+truth(p['payload_condition'],'payload')+';')
        body+=('if ('+state['test_expression'].replace('${value}',v)+') { '+branch+' } ' if 'test_expression' in state else branch)
    return '([&]() -> scpp::boolean { '+body+' })()'
def states(source):
    family=source.split('<')[0];ct=cpp(source)
    out=[(ct+'()','!result.has_value()')] if family=='nullable' else [(ct+'(failure)','result.has_error() && result.error().get_line().native_value()==7')] if family=='result' else [(ct+'::from_false()','result.is_false()')]
    if family=='result_or_bool':out.append((ct+'::from_true()','result.is_true()'))
    return out
for source in names:
    t=source.split('<')[1][:-1];ct=cpp(source)
    for left,right in [(source,t),(t,source),(source,source)]:
        ternary=resolve(left,right);elvis=resolve(left,right,'elvis');fn='ternary_'+str(len(functions));ev='elvis_'+str(len(functions))
        executors[(source,left,right)]=(fn,ev)
        functions.append('static '+ct+' '+fn+'(scpp::boolean condition,const '+cpp(left)+' &left,const '+cpp(right)+' &right,int &observations,int &a,int &b,bool fail_left=false,bool fail_right=false) { ++observations; if ('+truth(ternary['condition'],'condition')+') { ++a; if(fail_left) throw 42; return '+cast(ternary['then_conversion'],'left')+'; } ++b; if(fail_right) throw 42; return '+cast(ternary['else_conversion'],'right')+'; }')
        functions.append('static '+ct+' '+ev+'(const '+cpp(left)+' &left,const '+cpp(right)+' &right,int &a,int &b,bool fail_right=false) { ++a; const auto saved=left; if ('+truth(elvis['condition'],'saved')+') return '+cast(elvis['then_conversion'],'saved')+'; ++b; if(fail_right) throw 42; return '+cast(elvis['else_conversion'],'right')+'; }')
        lv=ct+'('+value(t,0)+')' if left==source else value(t,0)
        rv=ct+'('+value(t,7)+')' if right==source else value(t,7)
        for selected in [True,False]:
            expected=0 if selected else 7
            checks.append(f'{{ auto left={lv}; auto right={rv}; int c=0,a=0,b=0; auto result={fn}(scpp::boolean({str(selected).lower()}),left,right,c,a,b,{str(not selected).lower()},{str(selected).lower()}); CHECK(c==1 && a=={int(selected)} && b=={int(not selected)} && result.has_value() && ({equal(t,"result.value()",expected)})); left={value(t,7)}; right={value(t,0)}; CHECK(({equal(t,"result.value()",expected)})); }}')
        checks.append(f'{{ auto left={lv}; auto right={rv}; int a=0,b=0; auto result={ev}(left,right,a,b); CHECK(a==1 && b==1 && result.has_value() && ({equal(t,"result.value()",7)})); }}')
        truthy=ct+'('+value(t,7)+')' if left==source else value(t,7)
        checks.append(f'{{ auto left={truthy}; auto right={rv}; int a=0,b=0; auto result={ev}(left,right,a,b,true); CHECK(a==1 && b==0 && result.has_value() && ({equal(t,"result.value()",7)})); }}')
        failures.append(f'{{ auto left={lv}; auto right={rv}; int a=0,b=0; bool caught=false; try {{ (void){ev}(left,right,a,b,true); }} catch(int) {{ caught=true; }} CHECK(caught && a==1 && b==1); }}')
        if left==right:
            for initial,state in states(source):
                # Full ternary may select and preserve an unavailable state without extracting.
                failures.append(f'{{ auto left={initial}; auto right={rv}; int c=0,a=0,b=0; auto result={fn}(scpp::boolean(true),left,right,c,a,b); CHECK(c==1 && a==1 && b==0 && ({state})); }}')
                if 'from_true' in initial:
                    checks.append(f'{{ auto left={initial}; auto right={rv}; int a=0,b=0; auto result={ev}(left,right,a,b,true); CHECK(a==1 && b==0 && result.is_true()); }}')
                else:
                    failures.append(f'{{ auto left={initial}; auto right={rv}; int a=0,b=0; auto result={ev}(left,right,a,b); CHECK(a==1 && b==1 && result.has_value() && ({equal(t,"result.value()",7)})); }}')
for t in payloads:
    p=resolve(t,t,'elvis');fn='plain_'+t
    functions.append('static '+cpp(t)+' '+fn+'(const '+cpp(t)+' &left,const '+cpp(t)+' &right,int &effects) { const auto saved=left; if ('+truth(p['condition'],'saved')+') return '+cast(p['then_conversion'],'saved')+'; ++effects; return '+cast(p['else_conversion'],'right')+'; }')
    for n in [0,7]:
        checks.append(f'{{ int effects=0; auto result={fn}({value(t,n)},{value(t,7)},effects); CHECK(effects=={1 if n==0 else 0} && ({equal(t,"result",7)})); }}')
if args.boxed:
    for source in names:
        t=source.split('<')[1][:-1];ct=cpp(source);fn,ev=executors[(source,source,source)]
        boxed_checks.append(f'{{ {ct} left{{scpp::{t}()}}; {ct} right({value(t,7)}); int c=0,a=0,b=0; auto selected={fn}(scpp::boolean(true),left,right,c,a,b); CHECK(selected.has_value() && selected.value().is_null() && c==1 && a==1 && b==0); a=b=0; auto fallback={ev}(left,right,a,b); CHECK(fallback.has_value() && fallback.value().get_int()==scpp::int64(7) && a==1 && b==1); }}')
        factory='make_mixed_table' if t=='mixed' else 'make_dynamic_container'
        table=f'auto table=scpp::{factory}(); table.set(scpp::int64(0),{value(t,1)}); {ct} left(table), right({value(t,7)}); int c=0,a=0,b=0;'
        if t=='mixed':
            boxed_checks.append('{ '+table+f'auto selected={fn}(scpp::boolean(true),left,right,c,a,b); auto copy=selected.value(); copy.set(scpp::int64(0),{value(t,9)}); CHECK(table.get(scpp::int64(0)).get_int()==scpp::int64(1)); a=b=0; bool caught=false; try {{ (void){ev}(left,right,a,b); }} catch(...) {{ caught=true; }} CHECK(caught && a==1 && b==0); }}')
        else:
            boxed_checks.append('{ '+table+f'auto selected={ev}(left,right,a,b,true); CHECK(a==1 && b==0); auto alias=selected.value(); alias.set(scpp::int64(0),{value(t,9)}); CHECK(table.get(scpp::int64(0)).get_int()==scpp::int64(9)); }}')
            boxed_checks.append(f'{{ {ct} left(scpp::make_dynamic_container()), right({value(t,7)}); int a=0,b=0; auto selected={ev}(left,right,a,b,true); CHECK(a==1 && b==0 && selected.value().is_hash()); }}')
print(f'PASS: {len(checks)} value/lazy cases, {len(failures)} failure/state-preservation cases and {len(boxed_checks)} boxed-kind cases')
if not args.core:sys.exit(0)
with tempfile.TemporaryDirectory(prefix='scpp-carrier-conditional-') as directory:
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
    code+='\n'.join(functions)+'\nint main() { const int before=allocations;\n'+'\n'.join(checks)
    code+='\nCHECK(allocations==before); const auto failure=scpp::error_value::make(scpp::string("failure"),scpp::int64(7),scpp::string("input"));\n'+'\n'.join(failures)
    code+='\n'+'\n'.join(boxed_checks)
    code+='\nstd::puts("PASS: ternary and Elvis preserve carrier states and evaluate only selected branches; scalar-payload success allocations=0"); }'
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
    (app/'lib').mkdir();subprocess.run(['ar','rcs',str(app/'lib/libconditional.a'),*objects],check=True)
    seal_package(app,['conditional']);reused=concrete_types.generate(request_file,work/'reuse',sdk,[sdk,app],'clang++',[])
    assert not reused['sources']
    print(f'PASS: sealed reuse; storage compile={storage:.4f}s caller compile={caller_time:.4f}s')
