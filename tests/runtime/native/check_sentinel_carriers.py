"""Sentinel carrier state/take contracts, shared plans and application publication."""
import argparse
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
metadata = concrete_types.metadata(root/'runtime')
names = {'result_or_false<int64>':'false_integer', 'result_or_bool<int64>':'bool_integer',
         'result_or_false<boolean>':'false_boolean', 'result_or_bool<boolean>':'bool_boolean',
         'nullable<int64>':'optional_integer', 'result_or_bool<nullable<int64>>':'bool_optional'}
names.update({'result_or_false<move_record>':'false_record', 'result_or_bool<move_record>':'bool_record'})
leaves = {'move_record':{'cpp':'fixture::move_record','header':'sentinel_record.hpp',
                         'owner':'application.records','capabilities':['move_storage']}}
request = {'package':'application.sentinels','roots':list(names),'names':names,'leaves':leaves}

def invoke(data):
    p = subprocess.run([str(args.tool)], input=json.dumps(data), text=True,capture_output=True)
    assert p.returncode == 0, p.stderr
    return json.loads(p.stdout)

def resolve(action, request):
    return invoke({'metadata':metadata,'names':names,'leaves':leaves,'action':action,'request':request})

plan = invoke({'metadata':metadata,'request':request,'names':names,'leaves':leaves,'imports':{},'layouts':{}})
assert len(plan['units']) == 8
for family in ['result_or_false','result_or_bool']:
    source = family+'<int64>'
    unit = next(u for u in plan['units'] if u['identity']==source)
    assert unit['probes'][0]['lifecycle']['default'] == 'native'
    assert 'static scpp::'+names[source]+' from_false();' in unit['header']
    condition = resolve('resolve_condition',{'source':source})
    assert [s['condition'] for s in condition['states']] == (['payload','false','true'] if family=='result_or_bool' else ['payload','false'])
    for target in ['boolean','int8','string']:
        conversion=resolve('resolve_conversion',{'source':source,'target':target})
        for state in conversion['states'][1:]:
            assert state['conversion']['kind'] == ('expression' if target=='string' else 'checked_payload')
            if target=='string': assert state['conversion']['expression']=='scpp::string()'
    reconstruction = resolve('resolve_conversion',{'source':source,'target':family+'<float64>'})
    assert 'from_false' in json.dumps(reconstruction)
    if family=='result_or_bool': assert 'from_true' in json.dumps(reconstruction)
    for other in ['nullable<int64>','result<int64>','result_or_false<int64>','result_or_bool<int64>','int64']:
        for op in ['add','numeric_equal','strict_identical','less']:
            operator=resolve('resolve_operator',{'operation':op,'left':source,'right':other})
            if op in ['numeric_equal','strict_identical']:
                assert operator['different_states']=='false'
                if other.startswith('result_or_'): assert operator['same_states']['false']=='true'
                if source.startswith('result_or_bool') and other.startswith('result_or_bool'):
                    assert operator['same_states']['true']=='true'
for family in ['result_or_false','result_or_bool']:
    unit=next(u for u in plan['units'] if u['identity']==family+'<move_record>')
    assert unit['probes'][0]['lifecycle'] == {'default':'native','copy':'disabled','move':'native','destroy':'native'}
    assert ' require_value(' not in unit['header'] and ' take(' not in unit['header']
print('PASS: both sentinel recipes, nested demands, shared arithmetic/comparison/condition/conversion plans')
if not args.core: sys.exit(0)
with tempfile.TemporaryDirectory(prefix='scpp-sentinels-') as directory:
    work=Path(directory); sdk=work/'sdk'; app=work/'app'
    for folder in ['metadata','include','public']:
        shutil.copytree(root/'runtime'/folder,sdk/folder)
    (sdk/'lib').mkdir(); (sdk/'tools').mkdir()
    shutil.copy2(args.core,sdk/'lib/libcore.a'); shutil.copy2(args.tool,sdk/'tools/scpp_api_generator')
    seal_package(sdk,['core'])
    os.environ['SCPP_API_GENERATOR']=str(sdk/'tools/scpp_api_generator')
    request_file=work/'request.json'; request_file.write_text(json.dumps(request))
    (app/'public').mkdir(parents=True)
    (app/'public/sentinel_record.hpp').write_text(r'''#pragma once
namespace fixture {
struct move_record final {
    int *counter;
    move_record()=delete;
    explicit move_record(int &counter):counter(&counter) {}
    move_record(const move_record&)=delete;
    move_record &operator=(const move_record&)=delete;
    move_record(move_record &&other) noexcept :counter(other.counter) { other.counter=nullptr; }
    move_record &operator=(move_record &&other) noexcept {
        if (this!=&other) { if (this->counter) ++*this->counter; this->counter=other.counter; other.counter=nullptr; } return *this;
    }
    ~move_record() noexcept { if (this->counter) ++*this->counter; }
};
}
''')
    manifest=concrete_types.generate(request_file,app,sdk,[sdk],'clang++',[])
    assert len(manifest['sources']) == 8
    caller=work/'caller.cpp'
    caller.write_text(r'''
#include "scpp/false_integer.hpp"
#include "scpp/bool_integer.hpp"
#include "scpp/false_boolean.hpp"
#include "scpp/bool_boolean.hpp"
#include "scpp/bool_optional.hpp"
#include "scpp/false_record.hpp"
#include "scpp/bool_record.hpp"
#include "public_api_guard.hpp"
#include <type_traits>
#include <cstdlib>
#include <cstdio>
#include <new>
static int allocations=0;
void *operator new(__SIZE_TYPE__ n) { ++allocations; if (auto p=std::malloc(n)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p, __SIZE_TYPE__) noexcept { std::free(p); }
#define CHECK(x) do { if (!(x)) std::abort(); } while(false)
int main() {
    using namespace scpp;
    static_assert(!std::is_copy_constructible_v<false_record>);
    static_assert(!std::is_copy_constructible_v<bool_record>);
    static_assert(std::is_default_constructible_v<bool_record>);
    const int before=allocations;
    int destroyed=0;
    {
        auto no_record=false_record::from_false();
        auto yes_record=bool_record::from_true();
        fixture::move_record first(destroyed), second(destroyed);
        no_record=static_cast<fixture::move_record&&>(first);
        yes_record=static_cast<fixture::move_record&&>(second);
        CHECK(no_record.has_value() && yes_record.has_value());
        auto moved=static_cast<bool_record&&>(yes_record);
        moved=bool_record::from_true(); CHECK(destroyed==1);
        no_record.reset(); CHECK(destroyed==2);
    }
    CHECK(destroyed==2);
    false_integer absent;
    CHECK(absent.is_false() && !absent.has_value());
    int64 output(73);
    CHECK(!take(output,absent) && output.native_value()==73);
    false_integer payload(int64(0));
    CHECK(payload.has_value() && !payload.is_false());
    CHECK(take(output,payload) && output.native_value()==0);
    auto copy=payload; payload.reset();
    CHECK(payload.is_false() && copy.require_value("copy").native_value()==0);
    copy=false_integer::from_false(); CHECK(copy.is_false());
    copy=int64(19); CHECK(copy.has_value());
    bool_integer yes=bool_integer::from_true();
    bool_integer no=bool_integer::from_false();
    boolean alternative(false); output=int64(73);
    CHECK(take(output,alternative,yes) && alternative && output.native_value()==73);
    CHECK(!take(output,alternative,no) && !alternative && output.native_value()==73);
    bool_integer zero(int64(0)); alternative=boolean(true);
    CHECK(take(output,alternative,zero) && alternative && output.native_value()==0);
    zero.reset(); CHECK(zero.is_false());
    auto retained=yes; yes=int64(21);
    CHECK(retained.is_true() && yes.has_value());
    boolean value(true);
    false_boolean false_payload(boolean(false));
    CHECK(false_payload.has_value() && !false_payload.is_false());
    CHECK(take(value,false_payload) && !value);
    value=boolean(true); auto false_state=false_boolean::from_false();
    CHECK(!take(value,false_state) && value);
    bool_boolean boolean_payload(boolean(false));
    CHECK(boolean_payload.has_value() && !boolean_payload.is_false() && !boolean_payload.is_true());
    alternative=boolean(true);
    CHECK(take(value,alternative,boolean_payload) && !value && alternative);
    auto true_state=bool_boolean::from_true(); value=boolean(false); alternative=boolean(false);
    CHECK(take(value,alternative,true_state) && alternative && !value);
    auto no_boolean=bool_boolean::from_false(); value=boolean(true);
    CHECK(!take(value,alternative,no_boolean) && !alternative && value);
    boolean_payload=boolean(true);
    CHECK(boolean_payload.has_value() && !boolean_payload.is_true());
    bool_optional nested{optional_integer()};
    CHECK(nested.has_value() && !nested.value().has_value());
    optional_integer inner(int64(9)); alternative=boolean(true);
    CHECK(take(inner,alternative,nested) && !inner.has_value() && alternative);
    inner=int64(31); nested=bool_optional::from_true();
    CHECK(take(inner,alternative,nested) && inner.value().native_value()==31);
    CHECK(allocations==before);
    bool threw=false;
    try { (void)no.require_value("false sentinel"); } catch (...) { threw=true; }
    CHECK(threw && no.is_false()); threw=false;
    try { (void)retained.value(); } catch (...) { threw=true; }
    CHECK(threw && retained.is_true());
    std::puts("PASS: sentinel/payload distinctions, take outputs, nested states, owned extraction; success allocations=0");
}
''')
    flags=['-std=c++23','-O1']
    if args.sanitize: flags+=['-fsanitize=address,undefined','-fno-omit-frame-pointer']
    common=['clang++',*flags,'-I'+str(app/'public'),'-I'+str(sdk/'public')]
    objects=[]; start=time.perf_counter()
    for i,source in enumerate(manifest['sources']):
        obj=work/f'{i}.o';objects.append(str(obj))
        subprocess.run([*common,'-I'+str(app/'include'),'-I'+str(sdk/'include'),'-c',str(app/source),'-o',str(obj)],check=True)
    storage=time.perf_counter()-start;start=time.perf_counter()
    subprocess.run([*common,'-I'+str(root/'tests/runtime/native'),'-c',str(caller),'-o',str(work/'caller.o')],check=True)
    caller_time=time.perf_counter()-start
    subprocess.run(['clang++',*flags,str(work/'caller.o'),*objects,str(args.core.resolve()),'-pthread','-o',str(work/'proof')],check=True)
    subprocess.run([str(work/'proof')],check=True)
    (app/'lib').mkdir();subprocess.run(['ar','rcs',str(app/'lib/libsentinels.a'),*objects],check=True)
    seal_package(app,['sentinels'])
    reused=concrete_types.generate(request_file,work/'reuse',sdk,[sdk,app],'clang++',[])
    assert not reused['sources']
    print(f'PASS: sealed reuse; storage compile={storage:.4f}s caller compile={caller_time:.4f}s')
