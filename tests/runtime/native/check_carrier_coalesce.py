"""Exact scalar/boxed coalescing with separate fallback and selected-value observations."""
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
parser.add_argument('--boxed',action='store_true')
args=parser.parse_args();args.tool=args.tool.resolve()
metadata=concrete_types.metadata(root/'runtime')
public_types={**metadata['types'],**json.loads((root/'runtime/public/specializations.json').read_text())['types']}
payloads=['mixed','dynamic'] if args.boxed else ['int64','float64','boolean','string']
families=['nullable','result','result_or_false']
names={f+'<'+t+'>':'select_'+str(i)+'_'+t for i,f in enumerate(families) for t in payloads}
request={'package':'application.carrier_coalesce','roots':list(names),'names':names}

def resolve(left,right,meta=metadata,error=None):
    p=subprocess.run([str(args.tool)],input=json.dumps({'metadata':meta,'names':names,'action':'resolve_coalesce','request':{'left':left,'right':right}}),text=True,capture_output=True)
    if error:
        assert p.returncode and error in p.stderr,p.stderr
        return
    assert p.returncode==0,p.stderr
    return json.loads(p.stdout)
for t in payloads:
    assert resolve(t,t)['result_type']==t
    for family in families:
        source=family+'<'+t+'>'
        for right in ([t] if args.boxed else [t,source]):assert resolve(source,right)['result_type']==t
        resolve(t,source,error='Unsupported coalesce join')
        if args.boxed:resolve(source,source,error='Unsupported coalesce payload join')
        for other in families:
            if family!=other:resolve(source,other+'<'+t+'>',error='Unsupported coalesce join')
    for side in ['left','right']:
        resolve('result_or_bool<'+t+'>' if side=='left' else t,'result_or_bool<'+t+'>' if side=='right' else t,error='Unsupported coalesce carrier')
resolve('nullable<int64>','float64',error='Unsupported coalesce join')
for t in ['int8','nullable<int64>','vector<string>']:
    resolve('result<'+t+'>','result<'+t+'>',error='Unsupported coalesce payload')
changed=copy.deepcopy(metadata);changed['families']['holder']=copy.deepcopy(changed['families']['nullable'])
changed['selections']['coalesce']['carriers']['holder']='payload'
assert resolve('holder<int64>','int64',changed)['left']['family']=='holder'
changed=copy.deepcopy(metadata);changed['selections']['coalesce']['carriers']['nullable']='null'
resolve('int64','int64',changed,error='Coalesce')
changed=copy.deepcopy(metadata);changed['functions']['coalesce_require_value']['operation']['returns']='boolean'
resolve('int64','int64',changed,error='must borrow boolean and return void')
changed=copy.deepcopy(metadata);changed['selections']['coalesce']['forms'].append('any')
resolve('int64','int64',changed,error='Unknown coalesce join form')
headers={'scpp/exception_info.hpp'};functions=[];checks=[];failures=[];boxed_checks=[];executors={}
def cpp(t):return 'scpp::'+names[t] if t in names else public_types[t]['cpp']
def value(t,n):
    if t in ['mixed','dynamic']:return 'scpp::'+t+'(scpp::int64('+str(n)+'))'
    return 'scpp::string("'+('' if n==0 else 'fallback')+'")' if t=='string' else 'scpp::'+t+'('+str(bool(n)).lower()+')' if t=='boolean' else 'scpp::'+t+'('+str(n)+')'
def equal(t,var,n):
    if t in ['mixed','dynamic']:return var+'.get_int().native_value()=='+str(n)
    return var+'.equals('+value(t,n)+')' if t=='string' else var+'.native_value()=='+('true' if n else 'false') if t=='boolean' else var+'.native_value()=='+str(n)
def absent(source):
    family=source.split('<')[0]
    return cpp(source)+'()' if family=='nullable' else cpp(source)+'(failure)' if family=='result' else cpp(source)+'::from_false()'
def extract(p,v):
    if p['kind']=='identity':return v
    headers.add(p['header']);return p['expression'].replace('${value}',v)
def usable(p,v):return p['usable_expression'].replace('${value}',v)
def consumer(source,right):
    p=resolve(source,right);t=p['result_type'];fn='select_'+str(len(functions));guard=p['require_selected'];headers.add(guard['header'])
    fragments={
      'evaluate_left_once':'++left_effects; const auto &left_value=left;',
      'observe_left_usability':'const bool available=static_cast<bool>('+usable(p['left'],'left_value')+');',
      'if_usable_return_owned_left':'if(available) return '+extract(p['left'],'left_value')+';',
      'evaluate_right_once':'++right_effects; if(fail_right) throw 42; const auto &right_value=right;',
      'require_right_usability':guard['function']+'(scpp::boolean('+p['right']['selected_usable_expression'].replace('${value}','right_value')+'));',
      'return_owned_right':'return '+extract(p['right'],'right_value')+';',
    }
    functions.append('static '+cpp(t)+' '+fn+'(const '+cpp(source)+' &left,const '+cpp(right)+' &right,int &left_effects,int &right_effects,bool fail_right=false) { '+' '.join(fragments[step] for step in p['steps'])+' }')
    return fn
for source in names:
    t=source.split('<')[1][:-1]
    for right in ([t] if args.boxed else [t,source]):
        fn=consumer(source,right);executors[(source,right)]=fn
        rv=cpp(right)+'('+value(t,7)+')' if right==source else value(t,7)
        checks.append(f'{{ {cpp(source)} left({value(t,0)}); auto right={rv}; int a=0,b=0; auto result={fn}(left,right,a,b,true); CHECK(a==1 && b==0 && ({equal(t,"result",0)})); left={value(t,7)}; CHECK(({equal(t,"result",0)})); }}')
        checks.append(f'{{ auto left={absent(source)}; auto right={rv}; int a=0,b=0; auto result={fn}(left,right,a,b); CHECK(a==1 && b==1 && ({equal(t,"result",7)})); right={value(t,0)}; CHECK(({equal(t,"result",7)})); }}')
        failures.append(f'{{ auto left={absent(source)}; auto right={rv}; int a=0,b=0; bool caught=false; try {{ (void){fn}(left,right,a,b,true); }} catch(int) {{ caught=true; }} CHECK(caught && a==1 && b==1 && !left.has_value()); }}')
        if right==source:
            checks.append(f'{{ {cpp(source)} left({value(t,0)}); auto right={absent(source)}; int a=0,b=0; CHECK(({equal(t,fn+"(left,right,a,b)",0)}) && a==1 && b==0); }}')
            failures.append(f'{{ auto left={absent(source)}; auto right={absent(source)}; int a=0,b=0; bool caught=false; try {{ (void){fn}(left,right,a,b); }} catch (...) {{ caught=true; auto error=scpp::capture_runtime_exception(); CHECK(error.code().equals("coalesce_selected_branch_has_no_usable_value_domain") && error.component().equals("php::coalesce_eval") && error.operation().equals("??")); }} CHECK(caught && a==1 && b==1 && !left.has_value() && !right.has_value()); }}')
if args.boxed:
    for t in payloads:
        p=resolve(t,t)
        assert p['left']['usable_expression']=='!${value}.is_null()'
        assert p['right']['selected_usable_expression']=='true'
        fn=consumer(t,t)
        checks.append(f'{{ scpp::{t} left; auto right={value(t,7)}; int a=0,b=0; auto selected={fn}(left,right,a,b); CHECK(a==1 && b==1 && selected.get_int()==scpp::int64(7)); }}')
        checks.append(f'{{ scpp::{t} left,right; int a=0,b=0; auto selected={fn}(left,right,a,b); CHECK(a==1 && b==1 && selected.is_null()); }}')
        checks.append(f'{{ auto left={value(t,0)}; scpp::{t} right; int a=0,b=0; auto selected={fn}(left,right,a,b,true); CHECK(a==1 && b==0 && selected.get_int()==scpp::int64(0)); }}')
        for family in families:
            source=family+'<'+t+'>';fn=executors[(source,t)]
            checks.append(f'{{ {cpp(source)} left{{scpp::{t}()}}; auto right={value(t,7)}; int a=0,b=0; auto selected={fn}(left,right,a,b,true); CHECK(a==1 && b==0 && selected.is_null()); }}')
            checks.append(f'{{ auto left={absent(source)}; scpp::{t} right; int a=0,b=0; auto selected={fn}(left,right,a,b); CHECK(a==1 && b==1 && selected.is_null()); }}')
            factory='make_mixed_table' if t=='mixed' else 'make_dynamic_container'
            initial=f'auto table=scpp::{factory}(); table.set(scpp::int64(0),{value(t,1)}); {cpp(source)} left(table); auto right={value(t,7)}; int a=0,b=0;'
            expected=1 if t=='mixed' else 9
            boxed_checks.append('{ '+initial+f'auto selected={fn}(left,right,a,b,true); CHECK(a==1 && b==0 && selected.is_hash()); selected.set(scpp::int64(0),{value(t,9)}); CHECK(table.get(scpp::int64(0)).get_int()==scpp::int64({expected})); }}')
    changed=copy.deepcopy(metadata);changed['selections']['coalesce']['payload_rules']['mixed']['plain_usable_expression']='${missing}'
    resolve('mixed','mixed',changed,error='Unknown coalesce observation marker')
    changed=copy.deepcopy(metadata);changed['selections']['coalesce']['payload_rules']['mixed']['forms']=['any']
    resolve('mixed','mixed',changed,error='Unknown coalesce payload join form')
print(f'PASS: exact scalar selection admission, renamed recipe; {len(checks)} success and {len(failures)} failure and {len(boxed_checks)} container cases')
if not args.core:sys.exit(0)
with tempfile.TemporaryDirectory(prefix='scpp-carrier-coalesce-') as directory:
    work=Path(directory);sdk=work/'sdk';app=work/'app'
    for folder in ['metadata','include','public']:shutil.copytree(root/'runtime'/folder,sdk/folder)
    (sdk/'lib').mkdir();(sdk/'tools').mkdir()
    shutil.copy2(args.core,sdk/'lib/libcore.a');shutil.copy2(args.tool,sdk/'tools/scpp_api_generator')
    seal_package(sdk,['core']);os.environ['SCPP_API_GENERATOR']=str(sdk/'tools/scpp_api_generator')
    request_file=work/'request.json';request_file.write_text(json.dumps(request))
    manifest=concrete_types.generate(request_file,app,sdk,[sdk],'clang++',[])
    assert len(manifest['sources'])==len(names)
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
    if args.boxed:code+='\nextern bool native_dynamic_coalesce_proof();\n'
    code+='\n'.join(functions)+'\nint main() { const auto failure=scpp::error_value::make(scpp::string("failure")); const int before=allocations;\n'+'\n'.join(checks)
    code+='\nCHECK(allocations==before);\n'+'\n'.join(failures)
    code+='\n'+'\n'.join(boxed_checks)
    if args.boxed:code+='\nCHECK(native_dynamic_coalesce_proof());'
    code+='\nstd::puts("PASS: coalescing preserves false-like payloads, lazy fallback and error identity; success allocations=0"); }'
    caller=work/'caller.cpp';caller.write_text(code)
    flags=['-std=c++23','-O1']
    if args.sanitize:flags+=['-fsanitize=address,undefined','-fno-omit-frame-pointer']
    common=['clang++',*flags,'-I'+str(app/'public'),'-I'+str(sdk/'public')]
    objects=[];start=time.perf_counter()
    for i,source in enumerate(manifest['sources']):
        obj=work/f'{i}.o';objects.append(str(obj))
        subprocess.run([*common,'-I'+str(app/'include'),'-I'+str(sdk/'include'),'-c',str(app/source),'-o',str(obj)],check=True)
    storage=time.perf_counter()-start
    if args.boxed:
        native=work/'native.cpp'
        native.write_text(r'''#include "operators/coalesce/coalesce.hpp"
#include "scpp/dynamic_t.hpp"
bool native_dynamic_coalesce_proof() {
    scpp::dynamic_t<> empty, value(scpp::int_t<>(7)); int effects=0;
    auto selected=scpp::coalesce_eval([&]() -> const scpp::dynamic_t<> & { return empty; }, [&]() -> const scpp::dynamic_t<> & { ++effects; return value; });
    if(effects!=1 || selected.get_int().native_value()!=7) return false;
    auto nil=scpp::coalesce_eval([&]() -> const scpp::dynamic_t<> & { return empty; }, [&]() -> const scpp::dynamic_t<> & { ++effects; return empty; });
    return effects==2 && nil.is_null().native_value();
}
''')
        before=time.perf_counter();native_object=work/'native.o'
        subprocess.run([*common,'-I'+str(sdk/'include'),'-c',str(native),'-o',str(native_object)],check=True)
        print(f'Native parity fixture compile={time.perf_counter()-before:.4f}s')
        objects.append(str(native_object))
    start=time.perf_counter()
    subprocess.run([*common,'-I'+str(root/'tests/runtime/native'),'-c',str(caller),'-o',str(work/'caller.o')],check=True)
    caller_time=time.perf_counter()-start
    subprocess.run(['clang++',*flags,str(work/'caller.o'),*objects,str(args.core.resolve()),'-pthread','-o',str(work/'proof')],check=True)
    subprocess.run([str(work/'proof')],check=True)
    (app/'lib').mkdir();subprocess.run(['ar','rcs',str(app/'lib/libcoalesce.a'),*objects],check=True)
    seal_package(app,['coalesce']);reused=concrete_types.generate(request_file,work/'reuse',sdk,[sdk,app],'clang++',[])
    assert not reused['sources']
    print(f'PASS: sealed reuse; storage compile={storage:.4f}s caller compile={caller_time:.4f}s')
