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
names.update({'result_or_false<float64>':'false_float', 'result_or_bool<float64>':'bool_float'})
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
assert {u['identity'] for u in plan['units']} == set(names) | {'float64'} | set(plan['apis'])
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

# Render consumer statements from resolved plans, keeping evaluation in the consumer.
headers=set(); consumers=[]; execution_checks=[]; failure_checks=[]
def select_states(states, branch):
    body=''
    for state in states:
        code=branch(state)
        if 'test_expression' in state:
            body+='if ('+state['test_expression'].replace('${value}','source')+') { '+code+' }\n'
        else: body+=code
    return body

def consumer(name, result, source, body):
    headers.add('scpp/'+names[source]+'.hpp')
    consumers.append('static '+result+' '+name+'(const scpp::'+names[source]+' &source) { '+body+' }')

for family,prefix in [('result_or_false','false'),('result_or_bool','bool')]:
    source=family+'<int64>'
    condition=resolve('resolve_condition',{'source':source})
    condition_call=condition['payload_condition'];headers.add(condition_call['header'])
    projection=condition['projection']['expression'].replace('${value}','source')
    def truth_branch(state):
        if state['condition']=='payload':return 'return '+condition['payload_expression'].replace('${value}','source')+';' if condition['payload_access']=='scoped_operation' else 'const auto payload='+projection+'; return '+condition_call['function']+'(payload);'
        return 'return scpp::boolean('+state['condition']+');'
    consumer(prefix+'_condition','scpp::boolean',source,select_states(condition['states'],truth_branch))
    negate=resolve('resolve_operator',{'operation':'logical_not','operand':source})
    assert negate['kind']=='carrier_unary'
    expression=negate['calculation']['result_expression'].replace('${result}',negate['calculation']['function']+'('+negate['projection']['expression'].replace('${value}','source')+')')
    consumer(prefix+'_not','scpp::boolean',source,'return '+expression+';')
    for state in (['false','true'] if family=='result_or_bool' else ['false']):
        failure_checks.append(f'{{ bool caught=false; try {{ (void){prefix}_not(scpp::{names[source]}::from_{state}()); }} catch (...) {{ caught=true; }} CHECK(caught); }}')
    for target in ['boolean','int8','string']:
        cast=resolve('resolve_conversion',{'source':source,'target':target})
        call=cast['payload_conversion'];headers.add(call['header'])
        def cast_branch(state):
            conversion=state['conversion']
            if conversion['kind']=='expression':
                headers.add(conversion['header']);return 'return '+conversion['expression']+';'
            return 'const auto payload='+projection+'; return '+call['function']+'(payload);'
        consumer(prefix+'_cast_'+target,metadata['types'][target]['cpp'],source,select_states(cast['states'],cast_branch))
    rebuilt=resolve('resolve_conversion',{'source':source,'target':family+'<float64>'})
    call=rebuilt['payload_conversion'];headers.update([call['header'],rebuilt['target_header']])
    def rebuild_branch(state):
        expression=state['expression'].replace('${source}','source')
        if state['kind']=='payload':
            return 'const auto payload='+projection+'; const auto converted='+call['function']+'(payload); return '+expression.replace('${value}','converted')+';'
        return 'return '+expression+';'
    consumer(prefix+'_rebuild',rebuilt['target_cpp'],source,select_states(rebuilt['states'],rebuild_branch))
    cpp='scpp::'+names[source]
    execution_checks += [
        f'CHECK(!{prefix}_condition({cpp}(int64(0))) && {prefix}_condition({cpp}(int64(7))));',
        f'CHECK(!{prefix}_cast_boolean({cpp}(int64(0))) && {prefix}_cast_boolean({cpp}(int64(7))));',
        f'CHECK({prefix}_cast_int8({cpp}(int64(127))).native_value()==127);',
        f'CHECK({prefix}_cast_string({cpp}(int64(12))).equals("12"));',
        f'CHECK({prefix}_cast_string({cpp}::from_false()).byte_size()==0);',
        f'CHECK({prefix}_rebuild({cpp}::from_false()).is_false());',
        f'CHECK({prefix}_rebuild({cpp}(int64(12))).value().native_value()==12.0);']
    if prefix=='bool':
        execution_checks += [f'CHECK({prefix}_condition({cpp}::from_true()));',
                             f'CHECK({prefix}_cast_string({cpp}::from_true()).byte_size()==0);',
                             f'CHECK({prefix}_rebuild({cpp}::from_true()).is_true());']
    for sentinel in (['false','true'] if prefix=='bool' else ['false']):
        for target in ['boolean','int8']:
            failure_checks.append(f'{{ const auto source={cpp}::from_{sentinel}(); bool rejected=false; try {{ (void){prefix}_cast_{target}(source); }} catch (...) {{ rejected=true; }} CHECK(rejected && source.is_{sentinel}()); }}')
    failure_checks.append(f'{{ const {cpp} source(int64(128)); int8 destination(9); bool rejected=false; try {{ destination={prefix}_cast_int8(source); }} catch (...) {{ rejected=true; }} CHECK(rejected && destination.native_value()==9 && source.value().native_value()==128); }}')

for op,expected in [('add',10),('subtract',6),('multiply',16),('divide',4),('remainder',0),('less','false'),('compare',1)]:
    binary=resolve('resolve_operator',{'operation':op,'left':'result_or_bool<int64>','right':'result_or_false<int64>'})
    assert binary['steps']==['evaluate_left','project_left','convert_left','evaluate_right','project_right','convert_right','calculate']
    calc=binary['calculation'];headers.add(calc['header'])
    assert calc['left_conversion']['kind']==calc['right_conversion']['kind']=='identity'
    left=binary['left_projection']['expression'].replace('${value}','left')
    right=binary['right_projection']['expression'].replace('${value}','saved_right')
    body='const auto left_payload='+left+'; ++effects; if (mutate) left.reset(); const auto saved_right=right; const auto right_payload='+right+'; return '+calc['function']+'(left_payload,right_payload);'
    result=metadata['types'][binary['result_type']]['cpp']
    consumers.append('static '+result+' operate_'+op+'(scpp::bool_integer &left, const scpp::false_integer &right, int &effects, bool mutate=false) { '+body+' }')
    execution_checks.append(f'{{ bool_integer left(int64(8)); int effects=0; CHECK(operate_{op}(left,false_integer(int64(2)),effects,true).native_value()=={expected}); CHECK(effects==1 && left.is_false()); }}')
    for state in ['false','true']:
        failure_checks.append(f'{{ auto left=bool_integer::from_{state}(); int effects=0; bool rejected=false; try {{ (void)operate_{op}(left,false_integer(int64(2)),effects); }} catch (...) {{ rejected=true; }} CHECK(rejected && effects==0 && left.is_{state}()); }}')
    failure_checks.append(f'{{ bool_integer left(int64(8)); int effects=0; bool rejected=false; try {{ (void)operate_{op}(left,false_integer::from_false(),effects); }} catch (...) {{ rejected=true; }} CHECK(rejected && effects==1 && left.value().native_value()==8); }}')
for op in ['divide','remainder']:
    failure_checks.append(f'{{ bool_integer left(int64(8)); int effects=0; bool rejected=false; try {{ (void)operate_{op}(left,false_integer(int64(0)),effects); }} catch (...) {{ rejected=true; }} CHECK(rejected && effects==1 && left.value().native_value()==8); }}')
print('PASS: public execution statements derived for conditions, negation, checked casts, reconstruction and ordered numeric operations')

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
    assert len(manifest['sources']) == 10
    caller=work/'caller.cpp'
    caller.write_text('\n'.join('#include "'+h+'"' for h in sorted(headers))+'\n'+'\n'.join(consumers)+r'''
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
    EXECUTION_CHECKS
    CHECK(allocations==before);
    FAILURE_CHECKS
    bool threw=false;
    try { (void)no.require_value("false sentinel"); } catch (...) { threw=true; }
    CHECK(threw && no.is_false()); threw=false;
    try { (void)retained.value(); } catch (...) { threw=true; }
    CHECK(threw && retained.is_true());
    std::puts("PASS: sentinel/payload distinctions, take outputs, nested states, owned extraction; success allocations=0");
}
'''.replace('EXECUTION_CHECKS','\n'.join(execution_checks)).replace('FAILURE_CHECKS','\n'.join(failure_checks)))
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
