"""Prove capability-selected lifecycle with generated application optional storage."""
import argparse
import copy
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import time

root = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(root / 'runtime/tools'))
import concrete_types
from public_package import seal_package

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--tool', type=Path, required=True)
parser.add_argument('--core', type=Path)
parser.add_argument('--sanitize', action='store_true')
args = parser.parse_args()
args.tool = args.tool.resolve()
metadata = concrete_types.metadata(root / 'runtime')
# A metadata-owned static factory uses the same native transport as instance methods.
# Keep this fixture addition separate from the production API until sentinel recipes land.
metadata['families']['nullable']['classes'][0]['methods'].append({
    'name':'absent', 'static':True, 'returns':'self', 'parameters':[],
    'expression':'${native_type}()', 'transport':'native'})
# Use the production nullable recipe and its shared capability facts.
leaves = {name:{'cpp':'fixture::'+name, 'header':'fixture_records.hpp', 'owner':'application.records',
                'capabilities':caps}
          for name,caps in [('move_record',['move_storage']),('copy_record',['copy_storage','move_storage'])]}
names = {'nullable<move_record>':'optional_move_record',
         'nullable<copy_record>':'optional_copy_record',
         'vector<int64>':'integer_vector', 'nullable<vector<int64>>':'optional_integer_vector',
         'result<move_record>':'result_move_record', 'result<copy_record>':'result_copy_record',
         'result<nullable<move_record>>':'result_optional_move',
         'result<nullable<copy_record>>':'result_optional_copy'}
request = {'package':'application.optional', 'roots':[key for key in names if key.startswith(('nullable<','result<'))], 'leaves':leaves, 'names':names}
envelope = {'metadata':metadata,'request':request,'leaves':leaves,'imports':{},'layouts':{},'names':names}

def plan(data=envelope, error=None):
    result = subprocess.run([str(args.tool)], input=json.dumps(data), text=True, capture_output=True)
    if error:
        assert result.returncode and error in result.stderr, result.stderr
        return
    assert result.returncode == 0, result.stderr
    return json.loads(result.stdout)

def operation(action, request, error=None, meta=metadata, imports=None):
    data = {'action':action,'metadata':meta,'leaves':leaves,'names':names,'request':request}
    if imports is not None: data['imports'] = imports
    return plan(data,error)

presence = operation('resolve_operator',{'operation':'strict_identical','left':'nullable<move_record>','right':'null'})
assert presence['kind'] == 'carrier_literal_observation' and presence['payload_access'] == 'none'
operation('resolve_conversion',{'source':'nullable<move_record>','target':'boolean'},'payload access is unavailable')
operation('resolve_condition',{'source':'nullable<move_record>'},'payload access is unavailable')
# Capability propagation is computed through the family descriptors, not type-name switches.
# Hash physical noncopyable storage is a later migration; this is a planning admission check.
operation('resolve_conversion',{'source':'nullable<hash<move_record>>','target':'boolean'},'payload access is unavailable')
changed = copy.deepcopy(envelope)
companion = next(c for c in changed['metadata']['families']['vector']['classes'] if c['id'] != 'self')
companion['methods'].append({'name':'copy_probe','returns':'void','parameters':[], 'body':'return;',
                            'requires_capabilities':{companion['id']:['copy_storage']}})
assert all('copy_probe' not in unit['header'] for unit in plan(changed)['units'])
changed = copy.deepcopy(envelope)
changed['metadata']['families']['nullable']['requires_capabilities'] = {'T':['copy_storage']}
plan(changed,'Unsatisfied family capabilities')
operation('resolve_operator',{'operation':'strict_identical','left':'nullable<move_record>','right':'null'},
          'Unsatisfied family capabilities',meta=changed['metadata'])
result = plan()
units = {u['identity']:u for u in result['units']}
move = units['nullable<move_record>']
copy_unit = units['nullable<copy_record>']
assert move['probes'][0]['lifecycle'] == {'default':'native','copy':'disabled','move':'native','destroy':'native'}
assert copy_unit['probes'][0]['lifecycle']['copy'] == 'native'
result_move = units['result<move_record>']
assert result_move['probes'][0]['lifecycle'] == {'default':'disabled','copy':'disabled','move':'native','destroy':'native'}
assert ' require_value(' not in result_move['header'] and ' take(' not in result_move['header']
assert ' require_error(' in result_move['header'] and ' has_error(' in result_move['header']
assert result['types']['result<move_record>']['capabilities'] == ['move_storage']
assert result['types']['result<copy_record>']['capabilities'] == ['copy_storage','move_storage']
assert result['types']['result<nullable<move_record>>']['capabilities'] == ['default_storage','move_storage']
assert units['result<nullable<move_record>>']['probes'][0]['lifecycle']['default'] == 'native'
operation('resolve_conversion',{'source':'result<move_record>','target':'boolean'},'payload access is unavailable')
operation('resolve_condition',{'source':'result<move_record>'},'payload access is unavailable')

assert result['types']['nullable<move_record>']['capabilities'] == ['default_storage','move_storage']
assert result['types']['nullable<copy_record>']['capabilities'] == ['copy_storage','default_storage','move_storage']
for identity in ['vector<int64>','nullable<vector<int64>>']:
    assert result['types'][identity]['capabilities'] == ['copy_storage','default_storage','move_storage']
assert ' require_value(' not in move['header'] and ' take(' not in move['header']
assert ' require_value(' in copy_unit['header'] and ' take(' in copy_unit['header']
assert 'template' not in move['header'] and 'requires' not in move['header']
assert any('is_move_constructible_v<fixture::move_record>' in c for c in move['checks'])
assert not any('is_copy_constructible_v<fixture::move_record>' in c for c in move['checks'])
# Default capability selection is independent of copying and of payload default construction.
changed = copy.deepcopy(envelope)
changed['metadata']['families']['nullable']['classes'][0]['lifecycle']['default'] = {'requires_capabilities':{'T':['copy_storage']}}
assert plan(changed)['units'][0]['probes'][0]['lifecycle']['default'] == 'disabled'
for replacement, error in [({},'requires capabilities'),
                           ({'requires_capabilities':{'missing':['copy_storage']}},'Unknown capability parameter'),
                           ({'requires_capabilities':{'T':['missing']}},'Unknown capability'),
                           ({'requires_capabilities':{'T':'copy_storage'}},'capabilities must be an array'),
                           ({'requires_capabilities':{},'fallback':'native'},'unknown field')]:
    changed = copy.deepcopy(envelope)
    changed['metadata']['families']['nullable']['classes'][0]['lifecycle']['copy'] = replacement
    plan(changed,error)
changed = copy.deepcopy(envelope)
changed['metadata']['families']['nullable']['classes'][0]['lifecycle']['destroy'] = {'requires_capabilities':{'T':['copy_storage']}}
plan(changed,'Inline storage requires native destruction')
print('PASS: shared capability selection for lifecycle/methods, resolved probe facts and malformed policy rejection')
if not args.core: sys.exit(0)

fixture_header = r'''#pragma once
namespace fixture {
class move_record final {
    int *destructions_;
public:
    move_record() = delete;
    explicit move_record(int &destructions) : destructions_(&destructions) {}
    move_record(const move_record &) = delete;
    move_record &operator=(const move_record &) = delete;
    move_record(move_record &&other) noexcept : destructions_(other.destructions_) { other.destructions_ = nullptr; }
    move_record &operator=(move_record &&other) noexcept {
        if (this != &other) {
            if (this->destructions_) ++*this->destructions_;
            this->destructions_ = other.destructions_; other.destructions_ = nullptr;
        }
        return *this;
    }
    ~move_record() noexcept { if (this->destructions_) ++*this->destructions_; }
};
struct copy_record final {
    int value;
    copy_record() = delete;
    explicit copy_record(int value) : value(value) {}
};
}
'''
with tempfile.TemporaryDirectory(prefix='scpp-lifecycle-') as directory:
    work = Path(directory); sdk = work/'sdk'; app = work/'app'
    for folder in ['metadata','include','public']:
        shutil.copytree(root/'runtime'/folder, sdk/folder)
    # The SDK uses the production metadata and publication; records are application leaves.
    fixture_metadata = copy.deepcopy(metadata); del fixture_metadata['bindings']
    (sdk/'metadata/public_api.json').write_text(json.dumps(fixture_metadata))
    manifest = json.loads((sdk/'public/specializations.json').read_text())
    manifest['api_identity'] = hashlib.sha256(json.dumps(metadata,sort_keys=True).encode()).hexdigest()
    (sdk/'public/specializations.json').write_text(json.dumps(manifest))
    (sdk/'lib').mkdir(); (sdk/'tools').mkdir()
    shutil.copy2(args.core, sdk/'lib/libcore.a'); shutil.copy2(args.tool,sdk/'tools/scpp_api_generator')
    seal_package(sdk,['core'])
    os.environ['SCPP_API_GENERATOR'] = str(sdk/'tools/scpp_api_generator')
    (app/'public').mkdir(parents=True)
    (app/'public/fixture_records.hpp').write_text(fixture_header)
    request_file = work/'request.json'; request_file.write_text(json.dumps(request))
    manifest = concrete_types.generate(request_file,app,sdk,[sdk],'clang++',[])
    assert len(manifest['sources']) == 8
    flags = ['-std=c++23','-O1']
    if args.sanitize: flags += ['-fsanitize=address,undefined','-fno-omit-frame-pointer','-g']
    objects=[]; storage_time=0
    for index,source in enumerate(manifest['sources']):
        obj=work/('storage'+str(index)+'.o'); objects.append(obj)
        before=time.monotonic()
        subprocess.run(['clang++',*flags,'-I'+str(app/'public'),'-I'+str(app/'include'),
                        '-I'+str(sdk/'public'),'-I'+str(sdk/'include'),'-c',str(app/source),'-o',str(obj)],check=True)
        storage_time+=time.monotonic()-before
    caller=work/'caller.cpp'
    presence_body = ''
    for state in presence['states']:
        branch = 'return '+state['value']+';'
        if 'test_expression' in state:
            presence_body += 'if ('+state['test_expression'].replace('${value}','value')+') { '+branch+' }'
        else: presence_body += branch
    caller.write_text('#include "scpp/optional_move_record.hpp"\nstatic bool absent(const scpp::optional_move_record &value) { '+presence_body+' }\n'+r'''
#include "scpp/optional_move_record.hpp"
#include "scpp/optional_copy_record.hpp"
#include "scpp/optional_integer_vector.hpp"
#include "scpp/result_move_record.hpp"
#include "scpp/result_copy_record.hpp"
#include "scpp/result_optional_move.hpp"
#include "scpp/result_optional_copy.hpp"
#include "public_api_guard.hpp"
#include <cstdlib>
#include <cstdio>
#include <new>
#include <type_traits>
static int allocations = 0;
void *operator new(__SIZE_TYPE__ size) { ++allocations; if (auto p = std::malloc(size)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p, __SIZE_TYPE__) noexcept { std::free(p); }
#define CHECK(x) do { if (!(x)) std::abort(); } while(false)
static_assert(!std::is_default_constructible_v<fixture::move_record>);
static_assert(std::is_default_constructible_v<scpp::optional_move_record>);
static_assert(!std::is_copy_constructible_v<scpp::optional_move_record>);
static_assert(!std::is_copy_assignable_v<scpp::optional_move_record>);
static_assert(std::is_move_constructible_v<scpp::optional_move_record>);
static_assert(std::is_move_assignable_v<scpp::optional_move_record>);
static_assert(std::is_copy_constructible_v<scpp::optional_copy_record>);
static_assert(!std::is_default_constructible_v<scpp::result_move_record>);
static_assert(!std::is_copy_constructible_v<scpp::result_move_record>);
static_assert(!std::is_copy_assignable_v<scpp::result_move_record>);
static_assert(std::is_move_constructible_v<scpp::result_move_record>);
static_assert(std::is_move_assignable_v<scpp::result_move_record>);
static_assert(!std::is_default_constructible_v<scpp::result_copy_record>);
static_assert(std::is_copy_constructible_v<scpp::result_copy_record>);
static_assert(std::is_default_constructible_v<scpp::result_optional_move>);
int main() {
    const auto failure = scpp::error_value::make(scpp::string("failed"));
    const int before = allocations;
    int destructions = 0;
    {
        auto empty_value = scpp::optional_move_record::absent();
        CHECK(absent(empty_value)); empty_value.reset(); CHECK(destructions == 0);
        fixture::move_record first(destructions);
        scpp::optional_move_record source(static_cast<fixture::move_record&&>(first));
        scpp::optional_move_record moved(static_cast<scpp::optional_move_record&&>(source));
        source.reset(); CHECK(destructions == 0 && moved.has_value());
        fixture::move_record second(destructions);
        scpp::optional_move_record assigned(static_cast<fixture::move_record&&>(second));
        assigned = static_cast<scpp::optional_move_record&&>(moved);
        CHECK(destructions == 1); moved.reset(); CHECK(destructions == 1);
        assigned = static_cast<scpp::optional_move_record&&>(assigned);
        assigned.reset(); CHECK(destructions == 2);
        fixture::move_record last(destructions);
        empty_value = static_cast<fixture::move_record&&>(last);
        CHECK(!absent(empty_value));
        scpp::optional_copy_record copyable(fixture::copy_record(42));
        auto copied = copyable; copyable.reset();
        CHECK(copied.require_value("copy").value == 42);
    }
    int result_destructions = 0, nested_destructions = 0;
    {
        fixture::move_record first(result_destructions);
        scpp::result_move_record success(static_cast<fixture::move_record&&>(first));
        scpp::result_move_record moved(static_cast<scpp::result_move_record&&>(success));
        scpp::result_move_record destination(failure);
        CHECK(destination.has_error());
        CHECK(destination.require_error("failure").get_message().equals("failed"));
        destination = static_cast<scpp::result_move_record&&>(moved);
        CHECK(destination.has_value() && result_destructions == 0);
        destination = failure;
        CHECK(destination.has_error() && result_destructions == 1);
        fixture::move_record second(result_destructions);
        destination = static_cast<fixture::move_record&&>(second);
        destination = static_cast<scpp::result_move_record&&>(destination);
        CHECK(destination.has_value() && result_destructions == 1);

        scpp::result_optional_move default_success;
        CHECK(default_success.has_value()); // Payload is an absent nullable, not a default record.
        fixture::move_record nested(nested_destructions);
        scpp::optional_move_record payload(static_cast<fixture::move_record&&>(nested));
        scpp::result_optional_move nested_result(static_cast<scpp::optional_move_record&&>(payload));
        payload.reset(); CHECK(nested_destructions == 0 && nested_result.has_value());
        nested_result = failure;
        CHECK(nested_destructions == 1 && nested_result.has_error());

        scpp::result_optional_copy copy_default;
        CHECK(!copy_default.require_value("successful absence").has_value());
        scpp::result_copy_record copy_result(fixture::copy_record(24));
        auto copied = copy_result;
        fixture::copy_record output(3); scpp::error_value error = failure;
        CHECK(scpp::take(output,error,copied)); CHECK(output.value == 24 && !error.has_error());
        copied = failure;
        CHECK(!scpp::take(output,error,copied)); CHECK(output.value == 24 && error.has_error());
    }
    CHECK(result_destructions == 2 && nested_destructions == 1);
    CHECK(destructions == 3 && allocations == before);
    // Rejected error assignment must not destroy a noncopyable success payload.
    int retained_destructions = 0;
    {
        fixture::move_record retained(retained_destructions);
        scpp::result_move_record value(static_cast<fixture::move_record&&>(retained));
        bool caught = false;
        try { value = scpp::error_value{}; } catch (...) { caught = true; }
        CHECK(caught && value.has_value() && retained_destructions == 0);
        value = failure; CHECK(value.has_error() && retained_destructions == 1);
    }
    CHECK(retained_destructions == 1);
    // A newly generated inner type must publish facts used by its nullable parent.
    const auto nested_before = allocations;
    scpp::integer_vector numbers;
    numbers.append(scpp::int64(7));
    scpp::optional_integer_vector nested(numbers);
    auto copied_nested = nested; nested.reset(); numbers.clear();
    CHECK(copied_nested.require_value("nested copy").at(scpp::uint64(0)).native_value() == 7);
    std::printf("Nested vector copy allocations=%d (separate from record operations)\n", allocations - nested_before);
}
''')
    public_flags=['-I'+str(app/'public'),'-I'+str(sdk/'public'),'-I'+str(root/'tests/runtime/native')]
    obj=work/'caller.o'; before=time.monotonic()
    subprocess.run(['clang++',*flags,*public_flags,'-c',str(caller),'-o',str(obj)],check=True)
    caller_time=time.monotonic()-before
    executable=work/'proof'
    subprocess.run(['clang++',*flags,str(obj),*[str(o) for o in objects],str(sdk/'lib/libcore.a'),'-pthread','-o',str(executable)],check=True)
    subprocess.run([str(executable)],check=True)
    rejected=work/'rejected.cpp'
    rejected.write_text('#include "scpp/optional_move_record.hpp"\n#include "scpp/result_move_record.hpp"\n#include "scpp/result_copy_record.hpp"\nvoid copy(scpp::optional_move_record &source) { auto invalid = source; }\nvoid copy_result(scpp::result_move_record &source) { auto invalid = source; }\nvoid default_result() { scpp::result_copy_record invalid; }\n')
    failure=subprocess.run(['clang++',*flags,*public_flags,'-fsyntax-only',str(rejected)],capture_output=True,text=True)
    assert failure.returncode and 'deleted' in failure.stderr, failure.stderr
    assert 'result_move_record' in failure.stderr and 'result_copy_record' in failure.stderr, failure.stderr
    (app/'lib').mkdir()
    subprocess.run(['ar','rcs',str(app/'lib/liboptional.a'),*[str(o) for o in objects]],check=True)
    seal_package(app,['optional'])
    reused=concrete_types.generate(request_file,work/'consumer',sdk,[sdk,app],'clang++',[])
    assert reused['sources']==[]
    forged = copy.deepcopy(manifest['types'])
    forged['nullable<move_record>']['capabilities'].append('copy_storage')
    operation('resolve_operator',{'operation':'strict_identical','left':'nullable<move_record>','right':'null'},
              'Conflicting imported capabilities',imports=forged)
    bad_import = copy.deepcopy(envelope); bad_import['imports'] = forged
    plan(bad_import,'Conflicting imported capabilities')
    # Declared capabilities must be substantiated by the target, before publication.
    lying = copy.deepcopy(request)
    lying['leaves']['move_record']['capabilities'].append('copy_storage')
    bad_request = work/'lying.json'; bad_request.write_text(json.dumps(lying))
    original_manifest = (app/'public/specializations.json').read_bytes()
    original_header = (app/'public/scpp/optional_move_record.hpp').read_bytes()
    failure = subprocess.run([sys.executable,str(root/'runtime/tools/concrete_types.py'),
        '--request',str(bad_request),'--output',str(app),'--runtime',str(sdk),'--dependency',str(sdk)],
        capture_output=True,text=True)
    assert failure.returncode and 'static assertion failed' in failure.stderr, failure.stderr
    assert (app/'public/specializations.json').read_bytes() == original_manifest
    assert (app/'public/scpp/optional_move_record.hpp').read_bytes() == original_header
    print('Optional storage bytes:', {key:record['layout'][0] for key,record in manifest['types'].items()})
    print(f'PASS: public nullable/Result lifecycle, state transitions and rejected copy/default; record-operation allocations=0; sealed reuse; storage compile={storage_time:.4f}s caller compile={caller_time:.4f}s')
