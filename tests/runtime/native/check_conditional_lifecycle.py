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
# Physical capability facts, not new source-language constraint syntax.
for name, traits in {'copy_storage':['copy_constructible','copy_assignable'],
                     'move_storage':['move_constructible','move_assignable']}.items():
    metadata['capabilities'][name] = {
        'includes':['type_traits'], 'checks':['std::is_'+trait+'_v<${cpp}>' for trait in traits]}
recipe = copy.deepcopy(metadata['families']['nullable'])
# Operator planning remains a separate migration: this proof exercises physical storage.
del recipe['carrier']
recipe['checks'] = ['std::is_nothrow_destructible_v<${public:T}>']
life = recipe['classes'][0]['lifecycle']
for mode in ['copy','move']:
    life[mode] = {'requires_capabilities':{'T':[mode+'_storage']}}
for method in recipe['classes'][0]['methods']:
    parameters = method.get('parameters', [])
    if any(p['type'] == 'T' for p in parameters) or method.get('returns') == 'T':
        capability = 'move_storage' if any(p.get('passing') == 'move_ref' for p in parameters) else 'copy_storage'
        method['requires_capabilities'] = {'T':[capability]}
for function in recipe['functions']:
    if function['name'] == 'take': function['requires_capabilities'] = {'T':['copy_storage']}
metadata['families']['optional_storage'] = recipe
leaves = {name:{'cpp':'fixture::'+name, 'header':'fixture_records.hpp', 'owner':'application.records',
                'capabilities':caps}
          for name,caps in [('move_record',['move_storage']),('copy_record',['copy_storage','move_storage'])]}
names = {'optional_storage<move_record>':'optional_move_record',
         'optional_storage<copy_record>':'optional_copy_record'}
request = {'package':'application.optional', 'roots':list(names), 'leaves':leaves, 'names':names}
envelope = {'metadata':metadata,'request':request,'leaves':leaves,'imports':{},'layouts':{},'names':names}

def plan(data=envelope, error=None):
    result = subprocess.run([str(args.tool)], input=json.dumps(data), text=True, capture_output=True)
    if error:
        assert result.returncode and error in result.stderr, result.stderr
        return
    assert result.returncode == 0, result.stderr
    return json.loads(result.stdout)

result = plan()
units = {u['identity']:u for u in result['units']}
move = units['optional_storage<move_record>']
copy_unit = units['optional_storage<copy_record>']
assert move['probes'][0]['lifecycle'] == {'default':'native','copy':'disabled','move':'native','destroy':'native'}
assert copy_unit['probes'][0]['lifecycle']['copy'] == 'native'
assert ' require_value(' not in move['header'] and ' take(' not in move['header']
assert ' require_value(' in copy_unit['header'] and ' take(' in copy_unit['header']
assert 'template' not in move['header'] and 'requires' not in move['header']
assert any('is_move_constructible_v<fixture::move_record>' in c for c in move['checks'])
assert not any('is_copy_constructible_v<fixture::move_record>' in c for c in move['checks'])
# Default capability selection is independent of copying and of payload default construction.
changed = copy.deepcopy(envelope)
changed['metadata']['families']['optional_storage']['classes'][0]['lifecycle']['default'] = {'requires_capabilities':{'T':['copy_storage']}}
assert plan(changed)['units'][0]['probes'][0]['lifecycle']['default'] == 'disabled'
for replacement, error in [({},'requires capabilities'),
                           ({'requires_capabilities':{'missing':['copy_storage']}},'Unknown capability parameter'),
                           ({'requires_capabilities':{'T':['missing']}},'Unknown capability'),
                           ({'requires_capabilities':{'T':'copy_storage'}},'capabilities must be an array'),
                           ({'requires_capabilities':{},'fallback':'native'},'unknown field')]:
    changed = copy.deepcopy(envelope)
    changed['metadata']['families']['optional_storage']['classes'][0]['lifecycle']['copy'] = replacement
    plan(changed,error)
changed = copy.deepcopy(envelope)
changed['metadata']['families']['optional_storage']['classes'][0]['lifecycle']['destroy'] = {'requires_capabilities':{'T':['copy_storage']}}
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
    # Only add test recipes/capabilities; published runtime layouts/implementations are unchanged.
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
    assert len(manifest['sources']) == 2
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
    caller.write_text(r'''
#include "scpp/optional_move_record.hpp"
#include "scpp/optional_copy_record.hpp"
#include "public_api_guard.hpp"
#include <cstdlib>
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
int main() {
    const int before = allocations;
    int destructions = 0;
    {
        scpp::optional_move_record absent;
        CHECK(!absent.has_value()); absent.reset(); CHECK(destructions == 0);
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
        absent = static_cast<fixture::move_record&&>(last);
        CHECK(absent.has_value());
        scpp::optional_copy_record copyable(fixture::copy_record(42));
        auto copied = copyable; copyable.reset();
        CHECK(copied.require_value("copy").value == 42);
    }
    CHECK(destructions == 3 && allocations == before);
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
    rejected.write_text('#include "scpp/optional_move_record.hpp"\nvoid copy(scpp::optional_move_record &source) { auto invalid = source; }\n')
    failure=subprocess.run(['clang++',*flags,*public_flags,'-fsyntax-only',str(rejected)],capture_output=True,text=True)
    assert failure.returncode and 'deleted' in failure.stderr, failure.stderr
    (app/'lib').mkdir()
    subprocess.run(['ar','rcs',str(app/'lib/liboptional.a'),*[str(o) for o in objects]],check=True)
    seal_package(app,['optional'])
    reused=concrete_types.generate(request_file,work/'consumer',sdk,[sdk,app],'clang++',[])
    assert reused['sources']==[]
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
    print(f'PASS: public move-only optional lifetime/reset/copy rejection; allocations=0; sealed reuse; storage compile={storage_time:.4f}s caller compile={caller_time:.4f}s')
