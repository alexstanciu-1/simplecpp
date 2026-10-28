"""Prepared application ownership: noncopyable target, lifetime, allocation and reuse."""
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
root = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(root/'runtime/tools'))
import concrete_types
from public_package import seal_package
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--tool', type=Path, required=True)
parser.add_argument('--core', type=Path)
parser.add_argument('--sanitize', action='store_true')
args = parser.parse_args(); args.tool = args.tool.resolve()
leaves = {'record': {'cpp':'fixture::record', 'header':'ownership_record.hpp',
          'owner':'application.ownership', 'capabilities':['default_storage']}}
names = {f'{family}<record>':f'{family}_record' for family in ['shared','unique','weak']}
data = {'package':'application.ownership', 'roots':list(names), 'leaves':leaves, 'names':names}
requirements = [{'subject':f'root_{i}', 'contract':'application_formed'} for i in range(3)]
requirements += [{'subject':'dependency:target', 'contract':'ownership_target'}]
prepared = {'version':'prepared-api-v1', 'producer':'fixture.semantic-owner', 'revision':'fixture-v1',
    'action':'generate', 'request':copy.deepcopy(data), 'admission':'fixture/ownership',
    'bindings':{f'root_{i}':{'source':'source.'+family, 'physical':physical, 'arguments':{'T':'source.record'}}
                for i,(physical,family) in enumerate(names.items())},
    'dependencies':{'target':{'source':'source.record','physical':'record','arguments':{}}},
    'requirements':requirements, 'facts':[{**r,'evidence':'fixture/'+r['subject']} for r in requirements]}
envelope = {'metadata':concrete_types.metadata(root/'runtime'), 'action':'generate', 'mode':'source',
            'prepared':prepared, 'request':data, 'leaves':leaves, 'names':names, 'imports':{}, 'layouts':{}}
def plan(value):
    result = subprocess.run([str(args.tool)],input=json.dumps(value),text=True,capture_output=True)
    assert result.returncode == 0, result.stderr
    return json.loads(result.stdout)
result = plan(envelope)
units = {u['identity']:u for u in result['units']}
assert len(units) == 3
assert units['unique<record>']['probes'][0]['lifecycle']['copy'] == 'disabled'
assert units['shared<record>']['probes'][0]['lifecycle']['copy'] == 'native'
assert '#include "scpp/shared_record.hpp"' in units['weak<record>']['header']
for family in ['unique','weak']:
    assert ' retain_present(' not in units[family+'<record>']['header']
    assert ' identical(' not in units[family+'<record>']['header']
# A weak-only demand recursively publishes the shared signature dependency.
weak_only = copy.deepcopy(envelope); weak_only.pop('prepared'); weak_only['mode']='native'
weak_only['request']['roots']=['weak<record>']
assert set(plan(weak_only)['types']) == {'shared<record>','weak<record>'}
no_default = copy.deepcopy(envelope); no_default['leaves']['record']['capabilities']=[]
no_default.pop('prepared'); no_default['mode']='native'
for unit in plan(no_default)['units']:
    assert ' make(' not in unit['header']
for imports in [{},result['types']]:
    bad = copy.deepcopy(envelope); bad['imports']=imports; bad['prepared']['facts']=[]
    failure = subprocess.run([str(args.tool)],input=json.dumps(bad),text=True,capture_output=True)
    assert failure.returncode and 'Missing prepared semantic fact' in failure.stderr
    bad=copy.deepcopy(envelope); bad['imports']=imports
    bad['prepared']['facts']=[f for f in bad['prepared']['facts'] if f['subject']!='dependency:target']
    failure=subprocess.run([str(args.tool)],input=json.dumps(bad),text=True,capture_output=True)
    assert failure.returncode and 'dependency:target/ownership_target' in failure.stderr
for invalid in [True, ['unknown'], ['move','move']]:
    bad=copy.deepcopy(envelope)
    bad['metadata']['families']['unique']['classes'][0]['lifecycle']['noexcept']=invalid
    failure=subprocess.run([str(args.tool)],input=json.dumps(bad),text=True,capture_output=True)
    assert failure.returncode and 'noexcept' in failure.stderr
print('PASS: ownership recipes, weak signature dependency, capability selection and prepared admission')
if not args.core: sys.exit(0)
fixture = r'''#pragma once
namespace fixture {
class record final {
public:
    record();
    ~record() noexcept;
    record(const record &) = delete;
    record &operator=(const record &) = delete;
    record(record &&) = delete;
    record &operator=(record &&) = delete;
};
int live() noexcept;
int destroyed() noexcept;
void fail_next() noexcept;
void native_shared();
void native_unique();
}
'''
implementation = r'''#include "ownership_record.hpp"
#include "scpp/memory.hpp"
namespace fixture {
static int live_count=0, destroyed_count=0;
static bool failing=false;
record::record() { if(failing) { failing=false; throw 17; } ++live_count; }
record::~record() noexcept { --live_count; ++destroyed_count; }
int live() noexcept { return live_count; }
int destroyed() noexcept { return destroyed_count; }
void fail_next() noexcept { failing=true; }
void observe_native(const void *) noexcept;
void native_shared() { auto handle=scpp::shared<record>(); observe_native(handle.get()); }
void native_unique() { auto handle=scpp::unique<record>(); observe_native(handle.get()); }
}
'''
caller = r'''#include "scpp/shared_record.hpp"
#include "scpp/unique_record.hpp"
#include "scpp/weak_record.hpp"
#include "public_api_guard.hpp"
#include <cstdlib>
#include <cstdio>
#include <new>
#include <type_traits>
#include <utility>
using namespace scpp;
namespace fixture { void observe_native(const void *value) noexcept { asm volatile("" : : "r"(value) : "memory"); } }
static unsigned allocations=0;
void *operator new(std::size_t n) { ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"ownership line %d: %s\n",__LINE__,#x); std::abort(); } } while(false)
static_assert(!std::is_copy_constructible_v<fixture::record> && !std::is_move_constructible_v<fixture::record>);
static_assert(std::is_copy_constructible_v<shared_record> && std::is_copy_assignable_v<shared_record>);
static_assert(!std::is_copy_constructible_v<unique_record> && !std::is_copy_assignable_v<unique_record>);
static_assert(std::is_nothrow_move_constructible_v<unique_record> && std::is_nothrow_move_assignable_v<unique_record>);
static_assert(std::is_nothrow_copy_constructible_v<shared_record> && std::is_nothrow_copy_assignable_v<weak_record>);
static_assert(!std::is_convertible_v<weak_record,bool>);
static void checked_receiver(shared_record &source, bool fail_argument) {
    auto receiver=source.retain_present();
    // Prepared evaluation selects/checks the receiver before argument effects.
    source.reset();
    CHECK(receiver.has_value() && fixture::live()==1);
    if(fail_argument) throw 29;
    CHECK(receiver.identical(receiver));
}
int main() {
    {
        shared_record empty_left, empty_right;
        CHECK(empty_left.identical(empty_right));
        bool argument_reached=false, rejected=false;
        try { auto receiver=empty_left.retain_present(); argument_reached=true; }
        catch(...) { rejected=true; }
        CHECK(rejected && !argument_reached && fixture::live()==0);
        for(int pass=0;pass<2;++pass) {
            const bool fail=pass!=0;
            auto receiver_source=shared_record::make();
            const auto before=allocations;
            const auto destroyed=fixture::destroyed();
            bool caught=false;
            try { checked_receiver(receiver_source,fail); }
            catch(int value) { caught=value==29; }
            CHECK(caught==fail && !receiver_source.has_value());
            CHECK(fixture::live()==0 && fixture::destroyed()==destroyed+1);
            // C++ exception machinery is not counted as a successful-path allocation claim.
            if(!fail) CHECK(allocations==before);
        }
        auto original=shared_record::make(); auto different=shared_record::make();
        const auto before=allocations;
        auto left_snapshot=original;
        original=different;
        auto right_snapshot=original;
        CHECK(!left_snapshot.identical(right_snapshot));
        CHECK(original.identical(different));
        CHECK(!original.identical(empty_left) && !empty_left.identical(original));
        CHECK(allocations==before);
    }
    CHECK(fixture::live()==0);
    auto before=allocations;
    shared_record empty; unique_record unique_empty; weak_record weak_empty;
    CHECK(!empty.has_value() && !unique_empty.has_value() && !weak_empty.lock().has_value());
    CHECK(allocations==before);
    before=allocations; fixture::native_shared(); const auto shared_cost=allocations-before;
    before=allocations; fixture::native_unique(); const auto unique_cost=allocations-before;
    int destroyed=fixture::destroyed();
    weak_record observer;
    {
        before=allocations; auto owner=shared_record::make(); CHECK(allocations-before==shared_cost);
        CHECK(fixture::live()==1);
        before=allocations;
        observer=weak_record(owner); auto copy=owner; shared_record assigned; assigned=copy;
        owner.reset(); copy.reset(); auto retained=observer.lock(); assigned.reset();
        CHECK(retained.has_value() && fixture::live()==1 && fixture::destroyed()==destroyed);
        CHECK(allocations==before);
    }
    CHECK(fixture::live()==0 && fixture::destroyed()==destroyed+1);
    before=allocations;
    for(int i=0;i<100;++i) CHECK(!observer.lock().has_value());
    observer.reset(); CHECK(allocations==before);
    destroyed=fixture::destroyed();
    {
        before=allocations; auto source=unique_record::make(); CHECK(allocations-before==unique_cost);
        auto target=unique_record::make(); CHECK(fixture::live()==2);
        before=allocations; auto moved=std::move(source); CHECK(!source.has_value());
        target=std::move(moved); CHECK(!moved.has_value() && target.has_value());
        CHECK(fixture::live()==1 && fixture::destroyed()==destroyed+1 && allocations==before);
        target.reset(); target.reset(); CHECK(fixture::live()==0 && fixture::destroyed()==destroyed+2);
    }
    destroyed=fixture::destroyed();
    for(int i=0;i<2;++i) {
        fixture::fail_next(); bool caught=false;
        try { if(i==0) (void)shared_record::make(); else (void)unique_record::make(); }
        catch(int value) { caught=value==17; }
        CHECK(caught && fixture::live()==0 && fixture::destroyed()==destroyed);
    }
    std::printf("PASS: checked retention/identity/lifetime/transfer/throw cleanup; shared allocations=%u unique=%u; empty/copy/lock/transfer=0\n",shared_cost,unique_cost);
}
'''
with tempfile.TemporaryDirectory(prefix='scpp-ownership-') as directory:
    work=Path(directory); sdk=work/'sdk'; app=work/'app'
    for folder in ['metadata','include','public']: shutil.copytree(root/'runtime'/folder,sdk/folder)
    (sdk/'lib').mkdir(); (sdk/'tools').mkdir()
    shutil.copy2(args.core,sdk/'lib/libcore.a'); shutil.copy2(args.tool,sdk/'tools/scpp_api_generator')
    seal_package(sdk,['core']); os.environ['SCPP_API_GENERATOR']=str(sdk/'tools/scpp_api_generator')
    (app/'public').mkdir(parents=True); (app/'public/ownership_record.hpp').write_text(fixture)
    request=work/'request.json'; wrapper={'mode':'source','prepared':prepared,'request':data}
    request.write_text(json.dumps(wrapper))
    manifest=concrete_types.generate(request,app,sdk,[sdk],'clang++',[])
    assert len(manifest['sources'])==3
    (work/'record.cpp').write_text(implementation); (work/'caller.cpp').write_text(caller)
    flags=['-std=c++23','-O1']
    if args.sanitize: flags+=['-fsanitize=address,undefined','-fno-omit-frame-pointer']
    public=['clang++',*flags,'-I'+str(app/'public'),'-I'+str(sdk/'public')]
    private=[*public,'-I'+str(app/'include'),'-I'+str(sdk/'include')]
    objects=[]; start=time.perf_counter()
    for index,source in enumerate([*(app/p for p in manifest['sources']),work/'record.cpp']):
        obj=work/f'{index}.o'; objects.append(str(obj))
        subprocess.run([*private,'-c',str(source),'-o',str(obj)],check=True)
    storage_time=time.perf_counter()-start; start=time.perf_counter()
    subprocess.run([*public,'-I'+str(root/'tests/runtime/native'),'-c',str(work/'caller.cpp'),'-o',str(work/'caller.o')],check=True)
    caller_time=time.perf_counter()-start
    for name in names.values():
        subprocess.run([*public,'-fsyntax-only','-x','c++','-'],input=f'#include "scpp/{name}.hpp"\n#include "public_api_guard.hpp"\n',text=True,check=True,
                       cwd=root/'tests/runtime/native')
    subprocess.run(['clang++',*flags,str(work/'caller.o'),*objects,str(args.core.resolve()),'-pthread','-o',str(work/'proof')],check=True)
    subprocess.run([str(work/'proof')],check=True)
    false_promise=copy.deepcopy(envelope); false_promise.pop('prepared'); false_promise['mode']='native'
    false_promise['request']['roots']=['shared<record>']
    recipe=false_promise['metadata']['families']['shared']
    recipe['native']='${public:T}'
    recipe['classes'][0]['methods']=[]
    recipe['classes'][0]['lifecycle']={'default':'native','copy':'disabled','move':'disabled','destroy':'native','noexcept':['default']}
    false_promise['layouts']={'shared<record>:self':[1,1]}
    invalid=plan(false_promise)['units'][0]
    invalid_public=work/'invalid-public/scpp'; invalid_public.mkdir(parents=True)
    (invalid_public/'shared_record.hpp').write_text(invalid['header'])
    invalid_source=work/'invalid.cpp'; invalid_source.write_text(invalid['source'])
    failure=subprocess.run(['clang++','-I'+str(invalid_public.parent),*private[1:],'-fsyntax-only',str(invalid_source)],text=True,capture_output=True)
    assert failure.returncode and 'is_nothrow_default_constructible' in failure.stderr, failure.stderr

    (app/'lib').mkdir(); subprocess.run(['ar','rcs',str(app/'lib/libownership.a'),*objects],check=True)
    seal_package(app,['ownership'])
    reused=concrete_types.generate(request,work/'reuse',sdk,[sdk,app],'clang++',[])
    assert not reused['sources']
    bad=copy.deepcopy(wrapper); bad['prepared']['facts']=[]; request.write_text(json.dumps(bad))
    rejected=False
    try: concrete_types.generate(request,work/'rejected',sdk,[sdk,app],'clang++',[])
    except subprocess.CalledProcessError as error: rejected=error.returncode==1
    assert rejected and not (work/'rejected').exists()
    print(f'PASS: standalone public headers, sealed reuse, missing-fact rejection; storage+fixture={storage_time:.4f}s caller={caller_time:.4f}s')
