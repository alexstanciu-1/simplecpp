"""Scoped carrier conditions borrow payloads without allocations or escaping references."""
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
leaves={}
names={f+'<string>':f+'_text' for f in ['nullable','result','result_or_false','result_or_bool']}
names['nullable<error_value>']='optional_error'
for f in ['result','result_or_false','result_or_bool']: names[f+'<nullable<string>>']=f+'_optional_text'
request={'package':'application.observations','roots':list(names),'leaves':leaves,'names':names}
envelope={'metadata':metadata,'request':request,'leaves':leaves,'names':names,'imports':{},'layouts':{}}
def plan(data=envelope,error=None):
    result=subprocess.run([str(args.tool)],input=json.dumps(data),text=True,capture_output=True)
    if error:
        assert result.returncode and error in result.stderr,result.stderr
        return
    assert result.returncode==0,result.stderr
    return json.loads(result.stdout)
def condition(source,meta=metadata,error=None):
    return plan({'action':'resolve_condition','metadata':meta,'leaves':leaves,'names':names,'request':{'source':source}},error)
result=plan();units={u['identity']:u for u in result['units']}
conditions={source:condition(source) for source in names}
for source,p in conditions.items():
    assert p['payload_access']=='scoped_operation'
    assert p['payload_expression']=='${value}.observe_payload_condition()'
    assert 'observe_payload_condition() const;' in units[source]['header']
    assert 'const auto &payload =' in units[source]['source']
    assert 'static_assert(std::is_same_v<decltype((' in units[source]['source']
assert {'condition_truthy_string','condition_truthy_error_value'} <= set(result['apis'])
# Nested states delegate through the same scoped operation.
assert condition('result<nullable<string>>')['payload_access']=='scoped_operation'
condition('nullable<vector<string>>',error='Unsupported condition operand')
legacy=copy.deepcopy(metadata);del legacy['conditions']['scoped_delegation']
assert condition('result<nullable<string>>',legacy)['payload_access']=='owned_value'
bad=copy.deepcopy(metadata);bad['conditions']['scoped_delegation']='flatten'
condition('nullable<string>',bad,'Unknown scoped condition delegation policy')
changed=copy.deepcopy(metadata)
changed['families']['nullable']['carrier']['condition_observation']='missing'
condition('nullable<string>',changed,'requires one method')
changed=copy.deepcopy(metadata)
next(o for o in changed['families']['nullable']['classes'][0]['methods'] if o.get('name')=='observe_payload_condition')['returns']='string'
condition('nullable<string>',changed,'scoped const boolean method')
# No family-name special casing.
changed=copy.deepcopy(metadata);changed['families']['holder']=changed['families']['nullable']
assert condition('holder<string>',changed)['payload_access']=='scoped_operation'
print('PASS: scoped condition plans, dependency publication, exact borrow assertions and invalid metadata')
if not args.core:sys.exit(0)
def observation(p,var):
    body=''
    for state in p['states']:
        branch='return '+p['payload_expression'].replace('${value}',var)+';' if state['condition']=='payload' else 'return scpp::boolean('+state['condition']+');'
        body+=('if ('+state['test_expression'].replace('${value}',var)+') { '+branch+' }' if 'test_expression' in state else branch)
    return body
with tempfile.TemporaryDirectory(prefix='scpp-scoped-condition-') as directory:
    work=Path(directory);sdk=work/'sdk';app=work/'app'
    for folder in ['metadata','include','public']:shutil.copytree(root/'runtime'/folder,sdk/folder)
    (sdk/'lib').mkdir();(sdk/'tools').mkdir()
    shutil.copy2(args.core,sdk/'lib/libcore.a');shutil.copy2(args.tool,sdk/'tools/scpp_api_generator')
    seal_package(sdk,['core']);os.environ['SCPP_API_GENERATOR']=str(sdk/'tools/scpp_api_generator')
    (app/'public').mkdir(parents=True)
    request_file=work/'request.json';request_file.write_text(json.dumps(request))
    manifest=concrete_types.generate(request_file,app,sdk,[sdk],'clang++',[])
    assert len(manifest['sources'])==8,manifest['sources']
    code=''.join('#include "scpp/'+name+'.hpp"\n' for name in names.values())+'#include "public_api_guard.hpp"\n'
    for source,name in names.items():
        code+='static scpp::boolean observe(const scpp::'+name+' &value) { '+observation(conditions[source],'value')+' }\n'
    code+=r'''
#include <cstdlib>
#include <cstdio>
#include <new>
#include <initializer_list>
static int allocations=0;
void *operator new(__SIZE_TYPE__ n) { ++allocations; if(auto p=std::malloc(n)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,__SIZE_TYPE__) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"CHECK failed line %d: %s\n",__LINE__,#x); std::abort(); } } while(false)
int main() {
    const scpp::string text("a long string that exceeds inline storage and must never be copied merely to observe its condition");
    const auto error=scpp::error_value::make(text);
    scpp::nullable_text optional(text), absent;
    scpp::result_text result(text), failed(error);
    scpp::result_or_false_text rf(text), fs=scpp::result_or_false_text::from_false();
    scpp::result_or_bool_text rb(text), bs=scpp::result_or_bool_text::from_false(), ts=scpp::result_or_bool_text::from_true();
    scpp::optional_error retained(error), no_error(scpp::error_value{});
    scpp::result_optional_text nested(optional), nested_absent(absent), nested_failed(error);
    scpp::result_or_false_optional_text nested_false(optional), nested_false_absent(absent), nested_fs=scpp::result_or_false_optional_text::from_false();
    scpp::result_or_bool_optional_text nested_bool(optional), nested_bool_absent(absent), nested_ts=scpp::result_or_bool_optional_text::from_true();
    const int before=allocations;
    for(int i=0;i<100;++i) {
        CHECK(observe(optional) && observe(result) && observe(rf) && observe(rb));
        CHECK(!observe(absent) && !observe(failed) && !observe(fs) && !observe(bs) && observe(ts));
        CHECK(observe(retained) && !observe(no_error));
        CHECK(observe(nested) && !observe(nested_absent) && !observe(nested_failed));
        CHECK(observe(nested_false) && !observe(nested_false_absent) && !observe(nested_fs));
        CHECK(observe(nested_bool) && !observe(nested_bool_absent) && observe(nested_ts));
    }
    CHECK(allocations==before);
    int evaluations=0;
    CHECK(observe(([&]() -> const scpp::nullable_text & { ++evaluations;return optional; })()));
    CHECK(evaluations==1 && allocations==before);
    bool threw=false;
    try { (void)observe(([&]() -> const scpp::nullable_text & { ++evaluations;throw 42; })()); }
    catch(int) { threw=true; }
    CHECK(threw && evaluations==2);
    // The payload-only method is checked even if a caller ignores the state plan.
    threw=false;try { (void)absent.observe_payload_condition(); } catch(...) { threw=true; } CHECK(threw);
    for(const char *value : {"", "0"}) {
        optional=scpp::string(value); result=scpp::string(value);
        rf=scpp::string(value); rb=scpp::string(value);
        CHECK(!observe(optional) && !observe(result) && !observe(rf) && !observe(rb));
    }
    optional.reset(); CHECK(!observe(optional));
    std::puts("PASS: scoped conditions preserve all states, no observation allocation, evaluation once and checked access");
}
'''
    caller=work/'caller.cpp';caller.write_text(code)
    flags=['-std=c++23','-O1']
    if args.sanitize:flags+=['-fsanitize=address,undefined','-fno-omit-frame-pointer']
    common=['clang++',*flags,'-I'+str(app/'public'),'-I'+str(sdk/'public')]
    start=time.perf_counter();objects=[]
    for index,source in enumerate(manifest['sources']):
        obj=work/('storage'+str(index)+'.o');objects.append(obj)
        subprocess.run([*common,'-I'+str(app/'include'),'-I'+str(sdk/'include'),'-c',str(app/source),'-o',str(obj)],check=True)
    storage=time.perf_counter()-start;start=time.perf_counter()
    subprocess.run([*common,'-I'+str(root/'tests/runtime/native'),'-c',str(caller),'-o',str(work/'caller.o')],check=True)
    caller_time=time.perf_counter()-start
    subprocess.run(['clang++',*flags,str(work/'caller.o'),*[str(o) for o in objects],str(args.core.resolve()),'-pthread','-o',str(work/'proof')],check=True)
    subprocess.run([str(work/'proof')],check=True)
    (app/'lib').mkdir();subprocess.run(['ar','rcs',str(app/'lib/librecord.a'),*[str(o) for o in objects]],check=True)
    seal_package(app,['record']);reused=concrete_types.generate(request_file,work/'reuse',sdk,[sdk,app],'clang++',[])
    assert not reused['sources']
    # An older producer without scoped delegation cannot satisfy this demand.
    publication_path=app/'public/specializations.json'
    publication=json.loads(publication_path.read_text())
    legacy_metadata=concrete_types.metadata(sdk);del legacy_metadata['conditions']['scoped_delegation']
    publication['api_identity']=concrete_types.digest(json.dumps(legacy_metadata,sort_keys=True).encode())
    publication_path.write_text(json.dumps(publication));seal_package(app,['record'])
    try:concrete_types.generate(request_file,work/'old_consumer',sdk,[sdk,app],'clang++',[])
    except ValueError as error:assert 'Incompatible dependency API metadata' in str(error),error
    else:raise AssertionError('Old observation publication was accepted')
    print(f'PASS: sealed storage reuse; storage compile={storage:.4f}s caller compile={caller_time:.4f}s')
