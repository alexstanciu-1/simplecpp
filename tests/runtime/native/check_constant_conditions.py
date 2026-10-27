"""Explicit constant observations preserve noncopyable inline-record conditions."""
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
leaves={'record':{'cpp':'fixture::record','header':'record.hpp','owner':'application.records','capabilities':['move_storage']}}
names={'nullable<record>':'optional_record'}
observations=copy.deepcopy(metadata)
observations['conditions']['constants']={'record':True}

def resolve(source,meta=observations,error=None):
    result=subprocess.run([str(args.tool)],input=json.dumps({'action':'resolve_condition','metadata':meta,'leaves':leaves,'names':names,'request':{'source':source}}),text=True,capture_output=True)
    if error:
        assert result.returncode and error in result.stderr,result.stderr
        return
    assert result.returncode==0,result.stderr
    return json.loads(result.stdout)
resolve('nullable<record>',metadata,'payload access is unavailable')
plain=resolve('record');optional=resolve('nullable<record>')
assert plain['kind']=='constant_after_evaluation' and plain['value'] is True and plain['operand_access']=='none'
assert optional['kind']=='carrier_condition' and optional['payload_access']=='none'
assert optional['projection']['payload_available']=='false' and 'expression' not in optional['projection']
assert optional['payload_condition']==plain
# Nesting is not silently flattened: observing the inner presence still requires
# access, so this slice does not claim noncopyable nested observation support.
resolve('result<nullable<record>>',error='payload access is unavailable')
changed=copy.deepcopy(observations);changed['conditions']['constants']['record']=False
assert resolve('record',changed)['value'] is False
for key,value,error in [('record','true','explicit leaf and boolean'),('nullable<record>',True,'explicit leaf and boolean'),('int64',True,'Conflicting constant')]:
    changed=copy.deepcopy(observations);changed['conditions']['constants'][key]=value
    resolve('record',changed,error)
changed=copy.deepcopy(observations);changed['conditions']['constants']=[]
resolve('record',changed,'type-to-boolean map')
# Renaming the application leaf/family keeps the policy data-driven.
changed=copy.deepcopy(observations);changed['families']['holder']=copy.deepcopy(changed['families']['nullable'])
assert resolve('holder<record>',changed)['payload_access']=='none'
print('PASS: explicit constant leaf facts, no owned extraction, conflict checks and no nested flattening')
if not args.core:sys.exit(0)
fixture=r'''#pragma once
namespace fixture {
struct counts { int moves=0; int destroyed=0; };
class record final {
    counts *counts_;
public:
    record()=delete;
    explicit record(counts &c):counts_(&c) {}
    record(const record &)=delete;
    record &operator=(const record &)=delete;
    record(record &&other) noexcept:counts_(other.counts_) { other.counts_=nullptr; if(this->counts_) ++this->counts_->moves; }
    record &operator=(record &&other) noexcept { if(this!=&other) { if(this->counts_) ++this->counts_->destroyed; this->counts_=other.counts_;other.counts_=nullptr; if(this->counts_) ++this->counts_->moves; } return *this; }
    ~record() noexcept { if(this->counts_) ++this->counts_->destroyed; }
};
}
'''
def observation(plan,var):
    if plan['kind']=='constant_after_evaluation':return 'scpp::boolean('+str(plan['value']).lower()+')'
    assert plan['payload_access']=='none'
    body=''
    for state in plan['states']:
        branch='return '+observation(plan['payload_condition'],var)+';' if state['condition']=='payload' else 'return scpp::boolean('+state['condition']+');'
        body+=('if ('+state['test_expression'].replace('${value}',var)+') { '+branch+' }' if 'test_expression' in state else branch)
    return '([&]() -> scpp::boolean { '+body+' })()'
with tempfile.TemporaryDirectory(prefix='scpp-constant-condition-') as directory:
    work=Path(directory);sdk=work/'sdk';app=work/'app'
    for folder in ['metadata','include','public']:shutil.copytree(root/'runtime'/folder,sdk/folder)
    (sdk/'lib').mkdir();(sdk/'tools').mkdir()
    shutil.copy2(args.core,sdk/'lib/libcore.a');shutil.copy2(args.tool,sdk/'tools/scpp_api_generator')
    seal_package(sdk,['core']);os.environ['SCPP_API_GENERATOR']=str(sdk/'tools/scpp_api_generator')
    (app/'public').mkdir(parents=True);(app/'public/record.hpp').write_text(fixture)
    request_file=work/'request.json';request_file.write_text(json.dumps({'package':'application.record_conditions','roots':list(names),'leaves':leaves,'names':names}))
    manifest=concrete_types.generate(request_file,app,sdk,[sdk],'clang++',[])
    assert len(manifest['sources'])==1
    code='#include "scpp/optional_record.hpp"\n#include "public_api_guard.hpp"\n'
    code+='static scpp::boolean observe(const fixture::record &value) { (void)value; return '+observation(plain,'value')+'; }\n'
    code+='static scpp::boolean observe(const scpp::optional_record &value) { return '+observation(optional,'value')+'; }\n'
    code+=r'''
#include <cstdlib>
#include <cstdio>
#include <new>
#include <type_traits>
static int allocations=0;
void *operator new(__SIZE_TYPE__ n) { ++allocations; if(auto p=std::malloc(n)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,__SIZE_TYPE__) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) std::abort(); } while(false)
static_assert(!std::is_copy_constructible_v<fixture::record>);
static_assert(!std::is_copy_constructible_v<scpp::optional_record>);
int main() {
    const int before=allocations; fixture::counts counts;
    {
        fixture::record value(counts);
        int evaluations=0;
        CHECK(observe(([&]() -> const fixture::record & { ++evaluations;return value; })()));
        CHECK(evaluations==1 && counts.moves==0 && counts.destroyed==0);
        scpp::optional_record empty;
        CHECK(!observe(empty));
        scpp::optional_record present(static_cast<fixture::record&&>(value));
        const int moves=counts.moves;
        for(int i=0;i<100;++i) CHECK(observe(present));
        CHECK(counts.moves==moves && counts.destroyed==0 && present.has_value());
        evaluations=0;
        CHECK(observe(([&]() -> const scpp::optional_record & { ++evaluations;return present; })()));
        CHECK(evaluations==1);
        present.reset();CHECK(!observe(present) && counts.destroyed==1);
        bool caught=false;
        try { (void)observe(([&]() -> const fixture::record & { ++evaluations;throw 42; })()); }
        catch(int) { caught=true; }
        CHECK(caught && evaluations==2 && counts.moves==moves);
    }
    CHECK(counts.destroyed==1 && allocations==before);
    std::puts("PASS: noncopyable constant/nullable conditions; no observation moves, allocations or premature destruction");
}
'''
    caller=work/'caller.cpp';caller.write_text(code)
    flags=['-std=c++23','-O1']
    if args.sanitize:flags+=['-fsanitize=address,undefined','-fno-omit-frame-pointer']
    common=['clang++',*flags,'-I'+str(app/'public'),'-I'+str(sdk/'public')]
    start=time.perf_counter();obj=work/'storage.o'
    subprocess.run([*common,'-I'+str(app/'include'),'-I'+str(sdk/'include'),'-c',str(app/manifest['sources'][0]),'-o',str(obj)],check=True)
    storage=time.perf_counter()-start;start=time.perf_counter()
    subprocess.run([*common,'-I'+str(root/'tests/runtime/native'),'-c',str(caller),'-o',str(work/'caller.o')],check=True)
    caller_time=time.perf_counter()-start
    subprocess.run(['clang++',*flags,str(work/'caller.o'),str(obj),str(args.core.resolve()),'-pthread','-o',str(work/'proof')],check=True)
    subprocess.run([str(work/'proof')],check=True)
    (app/'lib').mkdir();subprocess.run(['ar','rcs',str(app/'lib/librecord.a'),str(obj)],check=True)
    seal_package(app,['record']);reused=concrete_types.generate(request_file,work/'reuse',sdk,[sdk,app],'clang++',[])
    assert not reused['sources']
    print(f'PASS: sealed storage reuse; storage compile={storage:.4f}s caller compile={caller_time:.4f}s')
