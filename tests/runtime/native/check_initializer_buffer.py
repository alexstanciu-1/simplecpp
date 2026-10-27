"""Owning initialization backing: no escaped views, ordered copies and failure cleanup."""
import argparse
from concurrent.futures import ThreadPoolExecutor
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
args = parser.parse_args()
names = {'initializer_buffer<'+t+'>': n+'_input' for t, n in
         [('string', 'string'), ('int64', 'integer'), ('mixed', 'mixed'), ('dynamic', 'dynamic')]}
request = {'package': 'application.initializer_inputs', 'roots': list(names), 'names': names}
published = json.loads((root/'runtime/public/specializations.json').read_text())
envelope = {'metadata': concrete_types.metadata(root/'runtime'), 'request': request,
            'names': {**{k: v['name'] for k, v in published['types'].items()}, **names}, 'imports': published['types'], 'layouts': {}, 'leaves': {}}
result = subprocess.run([str(args.tool)], input=json.dumps(envelope), text=True, capture_output=True)
assert result.returncode == 0, result.stderr
plan = json.loads(result.stdout)
assert {u['identity'] for u in plan['units']} == set(names)-set(published['types'])
for unit in plan['units']:
    assert ' view(' not in unit['header'] and 'template' not in unit['header']
    assert unit['probes'][0]['lifecycle'] == {'default': 'disabled', 'copy': 'disabled', 'move': 'disabled', 'destroy': 'native'}
envelope['leaves']['record'] = {'cpp': 'fixture::record', 'header': 'record.hpp',
                               'owner': 'application', 'capabilities': ['move_storage']}
envelope['request'] = {'package': 'application.invalid', 'roots': ['initializer_buffer<record>']}
result = subprocess.run([str(args.tool)], input=json.dumps(envelope), text=True, capture_output=True)
assert result.returncode and 'Unsatisfied family capabilities' in result.stderr
print('PASS: four owning-buffer recipes; no view/copy/move exposure; move-only input rejects')
if not args.core:
    sys.exit(0)

caller = r'''#include "public_api_guard.hpp"
#include "scpp/string_input.hpp"
#include "scpp/integer_input.hpp"
#include "scpp/mixed_input.hpp"
#include "scpp/dynamic_input.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>
#include <type_traits>
using namespace scpp;
static bool counting=false, fail_next=false;
static unsigned allocations=0;
void *operator new(std::size_t n) {
    if(fail_next) { fail_next=false; throw std::bad_alloc(); }
    if(counting) ++allocations;
    if(auto p=std::malloc(n?n:1)) return p;
    throw std::bad_alloc();
}
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"initializer buffer failure %d\n",__LINE__); std::abort(); } } while(false)
static_assert(!std::is_default_constructible_v<string_input>);
static_assert(!std::is_copy_constructible_v<string_input>);
static_assert(!std::is_move_constructible_v<string_input>);
int main() {
    counting=true;
    { integer_input empty(uint64(0)); CHECK(empty.count()==int64(0));
      integer_input small(uint64(2)); small.append(int64(3)); small.append(int64(7));
      CHECK(small.count()==int64(2)); CHECK(small.at(int64(0))==int64(3)); CHECK(small.at(int64(1))==int64(7)); }
    counting=false; CHECK(allocations==0);
    { integer_input large(uint64(128));
      for(int i=0;i<128;++i) large.append(int64(i));
      CHECK(large.count()==int64(128) && large.at(int64(127))==int64(127)); }
    string escaped;
    const string long_text("An owned initialization value long enough to require independent backing allocation");
    {
        string_input input(uint64(2)); input.append(long_text);
        bool incomplete=false;
        try { (void)input.count(); } catch(...) { incomplete=true; }
        CHECK(incomplete);
        fail_next=true; bool failed=false;
        try { input.append(long_text); } catch(const std::bad_alloc &) { failed=true; }
        CHECK(failed && !fail_next);
        input.append(string("second"));
        CHECK(input.count()==int64(2)); escaped=input.at(int64(0));
        bool negative=false, past=false, full=false;
        try { (void)input.at(int64(-1)); } catch(...) { negative=true; }
        try { (void)input.at(int64(2)); } catch(...) { past=true; }
        try { input.append(string("extra")); } catch(...) { full=true; }
        CHECK(negative && past && full && input.at(int64(1)).equals("second"));
    }
    CHECK(escaped==long_text);
    // Partially filled backing must release the elements already constructed.
    try { string_input partial(uint64(3)); partial.append(long_text); throw 7; } catch(int) {}
    auto table=make_mixed_table(); table.set(int64(0),mixed(int64(1)));
    auto object=make_dynamic_container(); object.set(int64(0),dynamic(int64(1)));
    mixed retained; dynamic alias;
    {
        mixed_input m(uint64(1)); dynamic_input d(uint64(1)); m.append(table); d.append(object);
        table.set(int64(0),mixed(int64(2))); object.set(int64(0),dynamic(int64(2)));
        retained=m.at(int64(0)); alias=d.at(int64(0));
        CHECK(retained.get(int64(0)).get_int()==int64(1));
        CHECK(alias.get(int64(0)).get_int()==int64(2));
    }
    table.reset(); object.reset();
    CHECK(retained.get(int64(0)).get_int()==int64(1));
    CHECK(alias.get(int64(0)).get_int()==int64(2));
    std::puts("PASS: ordered owning inputs, inline/heap backing, failed-copy retry and escaped value lifetime");
}
'''
with tempfile.TemporaryDirectory(prefix='scpp-initializer-buffer-') as directory:
    work = Path(directory)
    sdk = work/'runtime'
    for name in ['public', 'include', 'metadata']:
        shutil.copytree(root/'runtime'/name, sdk/name)
    (sdk/'lib').mkdir(); (sdk/'tools').mkdir()
    shutil.copy2(args.core, sdk/'lib/libcore.a')
    shutil.copy2(args.tool, sdk/'tools/scpp_api_generator')
    seal_package(sdk, ['core'])
    os.environ['SCPP_API_GENERATOR'] = str(sdk/'tools/scpp_api_generator')
    request_file = work/'request.json'; request_file.write_text(json.dumps(request))
    app = work/'app'
    manifest = concrete_types.generate(request_file, app, sdk, [sdk], 'clang++', [])
    flags = ['-fsanitize=address,undefined', '-fno-omit-frame-pointer', '-fno-sanitize-recover=all'] if args.sanitize else []
    common = ['clang++', '-std=c++23', '-O1' if args.sanitize else '-O2', *flags,
              '-I'+str(app/'public'), '-I'+str(sdk/'public')]
    def compile_unit(pair):
        index, source = pair
        output = work/('storage'+str(index)+'.o')
        subprocess.run([*common, '-I'+str(app/'include'), '-I'+str(sdk/'include'), '-c', str(app/source), '-o', str(output)], check=True)
        return output
    start = time.perf_counter()
    with ThreadPoolExecutor(max_workers=8) as pool:
        objects = list(pool.map(compile_unit, enumerate(manifest['sources'])))
    storage_time = time.perf_counter()-start
    (work/'caller.cpp').write_text(caller)
    start = time.perf_counter()
    subprocess.run([*common, '-I'+str(root/'tests/runtime/native'), '-c', str(work/'caller.cpp'), '-o', str(work/'caller.o')], check=True)
    caller_time = time.perf_counter()-start
    subprocess.run(['clang++', *flags, str(work/'caller.o'), *map(str, objects), str(args.core.resolve()), '-pthread', '-o', str(work/'proof')], check=True)
    subprocess.run([str(work/'proof')], check=True)
    (app/'lib').mkdir(); subprocess.run(['ar', 'rcs', str(app/'lib/libinputs.a'), *map(str, objects)], check=True)
    seal_package(app, ['inputs'])
    reused = concrete_types.generate(request_file, work/'reuse', sdk, [sdk, app], 'clang++', [])
    assert not reused['sources']
    print(f'PASS: sealed input reuse; parallel storage compile={storage_time:.4f}s caller compile={caller_time:.4f}s')
