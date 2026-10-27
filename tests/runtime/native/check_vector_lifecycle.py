"""Capability-selected vector storage and companions preserve move-only lifetimes."""
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
names={'vector<record>':'record_vector','vector<int64>':'integer_vector'}
request={'package':'application.record_vectors','roots':list(names),'leaves':leaves,'names':names}
envelope={'metadata':metadata,'request':request,'leaves':leaves,'names':names,'imports':{},'layouts':{}}
def plan(data=envelope,error=None):
    result=subprocess.run([str(args.tool)],input=json.dumps(data),text=True,capture_output=True)
    if error:
        assert result.returncode and error in result.stderr,result.stderr
        return
    assert result.returncode==0,result.stderr
    return json.loads(result.stdout)
result=plan();units={u['identity']:u for u in result['units']}
move=units['vector<record>'];ordinary=units['vector<int64>']
assert len(move['probes'])==1 and len(ordinary['probes'])==2
assert move['probes'][0]['lifecycle']=={'default':'native','copy':'disabled','move':'native','destroy':'native'}
assert result['types']['vector<record>']['capabilities']==['default_storage','move_storage']
for method in ['iterate','at','index','set','resize']:
    assert ' '+method+'(' not in move['header'],move['header']
    assert ' '+method+'(' in ordinary['header'],ordinary['header']
assert 'cursor' not in move['header']
assert 'append(fixture::record &&' in move['header']
assert 'append(const fixture::record &' not in move['header']
changed=copy.deepcopy(envelope)
changed['metadata']['families']['vector']['classes'][0]['requires_capabilities']={'T':['copy_storage']}
plan(changed,'Primary class cannot be conditional')
changed=copy.deepcopy(envelope)
companion=changed['metadata']['families']['vector']['classes'][1]
companion['requires_capabilities']={'missing':['copy_storage']}
plan(changed,'Unknown capability parameter')
# A signature cannot reference a companion omitted by its own capability guard.
changed=copy.deepcopy(envelope)
method=next(m for m in changed['metadata']['families']['vector']['classes'][0]['methods'] if m['name']=='iterate')
del method['requires_capabilities']
assert subprocess.run([str(args.tool)],input=json.dumps(changed),text=True,capture_output=True).returncode
print('PASS: conditional companion/lifecycle/method selection and invalid metadata rejection')
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
with tempfile.TemporaryDirectory(prefix='scpp-vector-lifecycle-') as directory:
    work=Path(directory);sdk=work/'sdk';app=work/'app'
    for folder in ['metadata','include','public']:shutil.copytree(root/'runtime'/folder,sdk/folder)
    (sdk/'lib').mkdir();(sdk/'tools').mkdir()
    shutil.copy2(args.core,sdk/'lib/libcore.a');shutil.copy2(args.tool,sdk/'tools/scpp_api_generator')
    seal_package(sdk,['core']);os.environ['SCPP_API_GENERATOR']=str(sdk/'tools/scpp_api_generator')
    (app/'public').mkdir(parents=True);(app/'public/record.hpp').write_text(fixture)
    request_file=work/'request.json';request_file.write_text(json.dumps(request))
    manifest=concrete_types.generate(request_file,app,sdk,[sdk],'clang++',[])
    assert len(manifest['sources'])==2
    code=r'''#include "scpp/record_vector.hpp"
#include "scpp/integer_vector.hpp"
#include "public_api_guard.hpp"
#include <cstdlib>
#include <cstdio>
#include <new>
#include <type_traits>
static int allocations=0;
void *operator new(__SIZE_TYPE__ n) { ++allocations; if(auto p=std::malloc(n)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,__SIZE_TYPE__) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"CHECK failed line %d: %s\n",__LINE__,#x); std::abort(); } } while(false)
static_assert(!std::is_copy_constructible_v<scpp::record_vector>);
static_assert(!std::is_copy_assignable_v<scpp::record_vector>);
static_assert(std::is_move_constructible_v<scpp::record_vector>);
int main() {
    fixture::counts counts;
    {
        scpp::record_vector values;
        values.reserve(scpp::uint64(3));
        const int before=allocations;
        values.append(fixture::record(counts));
        values.push_back(fixture::record(counts));
        values.append(fixture::record(counts));
        CHECK(values.size().native_value()==3 && counts.destroyed==0);
        CHECK(values.remove(scpp::int64(1)));
        CHECK(values.size().native_value()==2 && counts.destroyed==1);
        const int moves=counts.moves;
        scpp::record_vector moved(static_cast<scpp::record_vector&&>(values));
        scpp::record_vector assigned;
        assigned=static_cast<scpp::record_vector&&>(moved);
        CHECK(assigned.size().native_value()==2 && counts.moves==moves);
        assigned.clear();
        CHECK(counts.destroyed==3 && allocations==before);
    }
    CHECK(counts.destroyed==3);
    scpp::integer_vector integers;
    integers.append(scpp::int64(7));
    auto cursor=integers.iterate();
    auto copied=integers;
    integers.clear();
    CHECK(copied.at(scpp::uint64(0)).native_value()==7);
    CHECK(cursor.value().native_value()==7);
    std::puts("PASS: move-only vector ownership, zero post-reserve allocations, copy/cursor snapshots");
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
    print(f'PASS: sealed storage reuse; storage compile={storage:.4f}s caller compile={caller_time:.4f}s')
