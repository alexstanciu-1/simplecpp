"""Capability-selected typed hashes preserve move-only storage and explicit erase requirements."""
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
leaves={name:{'cpp':'fixture::'+name,'header':'record.hpp','owner':'application.records','capabilities':caps}
        for name,caps in [('record',['move_storage']),('default_record',['move_storage','default_storage'])]}
names={'hash<record,int64>':'record_hash','hash<default_record,int64>':'default_record_hash',
       'hash<record>':'string_record_hash','hash<int64,int64>':'integer_hash'}
request={'package':'application.record_hashes','roots':list(names),'leaves':leaves,'names':names}
envelope={'metadata':metadata,'request':request,'leaves':leaves,'names':names,'imports':{},'layouts':{}}
def plan(data=envelope,error=None):
    result=subprocess.run([str(args.tool)],input=json.dumps(data),text=True,capture_output=True)
    if error:
        assert result.returncode and error in result.stderr,result.stderr
        return
    assert result.returncode==0,result.stderr
    return json.loads(result.stdout)
result=plan();units={u['identity']:u for u in result['units']}
move=units['hash<record,int64>'];ordinary=units['hash<int64,int64>']
assert len(move['probes'])==1 and len(ordinary['probes'])==2
assert move['probes'][0]['lifecycle']=={'default':'native','copy':'disabled','move':'native','destroy':'native'}
assert result['types']['hash<record,int64>']['capabilities']==['default_storage','move_storage']
for method in ['iterate','at','find']:
    assert ' '+method+'(' not in move['header'],move['header']
    assert ' '+method+'(' in ordinary['header'],ordinary['header']
assert 'cursor' not in move['header']
assert 'append(fixture::record &&' in move['header']
assert 'append(const fixture::record &' not in move['header']
assert ' remove(' not in move['header']
assert ' remove(' in units['hash<default_record,int64>']['header']
assert ' append(' not in units['hash<record,string>']['header']
assert 'nullable<record>' not in result['types']
print('PASS: hash conditional lifecycle/cursor/access, default erase and integer append guards')
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
# Identical ownership probe with an empty default state for the erase contract.
fixture+=fixture[fixture.index('namespace fixture'):].replace('struct counts { int moves=0; int destroyed=0; };','').replace('record','default_record').replace('default_record()=delete;', 'default_record() noexcept:counts_(nullptr) {}')
with tempfile.TemporaryDirectory(prefix='scpp-hash-lifecycle-') as directory:
    work=Path(directory);sdk=work/'sdk';app=work/'app'
    for folder in ['metadata','include','public']:shutil.copytree(root/'runtime'/folder,sdk/folder)
    (sdk/'lib').mkdir();(sdk/'tools').mkdir()
    shutil.copy2(args.core,sdk/'lib/libcore.a');shutil.copy2(args.tool,sdk/'tools/scpp_api_generator')
    seal_package(sdk,['core']);os.environ['SCPP_API_GENERATOR']=str(sdk/'tools/scpp_api_generator')
    (app/'public').mkdir(parents=True);(app/'public/record.hpp').write_text(fixture)
    request_file=work/'request.json';request_file.write_text(json.dumps(request))
    manifest=concrete_types.generate(request_file,app,sdk,[sdk],'clang++',[])
    assert len(manifest['sources'])==5, manifest['sources']  # Four hashes plus the owned nullable<int64> find result.
    code=r'''#include "scpp/record_hash.hpp"
#include "scpp/default_record_hash.hpp"
#include "scpp/string_record_hash.hpp"
#include "scpp/integer_hash.hpp"
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
static_assert(!std::is_copy_constructible_v<scpp::record_hash>);
static_assert(!std::is_copy_assignable_v<scpp::record_hash>);
static_assert(std::is_move_constructible_v<scpp::record_hash>);
int main() {
    // Empty cursors and exhausted preserved cursors accept repeated next().
    {
        scpp::integer_hash values;
        { auto empty=values.iterate(); empty.next(); empty.next(); CHECK(!empty.valid()); }
        values.set(scpp::int64(2),scpp::int64(7));
        { auto exhausted=values.iterate(); (void)exhausted.value(); exhausted.next();
          CHECK(!exhausted.valid()); exhausted.next(); CHECK(!exhausted.valid()); }
        auto cursor=values.iterate();
        CHECK(cursor.valid()); (void)cursor.value();
        values.clear();
        cursor.next(); CHECK(!cursor.valid());
        const int before=allocations;
        for(int i=0;i<100;++i) cursor.next();
        CHECK(!cursor.valid() && allocations==before);
        bool key_failed=false, value_failed=false;
        try { (void)cursor.key(); } catch(...) { key_failed=true; }
        try { (void)cursor.value(); } catch(...) { value_failed=true; }
        CHECK(key_failed && value_failed);
    }
    fixture::counts counts;
    {
        scpp::record_hash values;
        CHECK(values.append(fixture::record(counts)).native_value()==0);
        CHECK(values.append(fixture::record(counts)).native_value()==1);
        CHECK(values.size().native_value()==2 && counts.destroyed==0);
        const int before=allocations;
        values.set(scpp::int64(0),fixture::record(counts));
        CHECK(counts.destroyed==1 && values.has(scpp::int64(0)));
        const int moves=counts.moves;
        scpp::record_hash moved(static_cast<scpp::record_hash&&>(values));
        scpp::record_hash assigned;
        assigned=static_cast<scpp::record_hash&&>(moved);
        CHECK(assigned.size().native_value()==2 && counts.moves==moves);
        assigned.clear();
        CHECK(counts.destroyed==3 && allocations==before);
    }
    CHECK(counts.destroyed==3);
    {
        scpp::default_record_hash values;
        values.set(scpp::int64(4),fixture::default_record(counts));
        values.set(scpp::int64(8),fixture::default_record(counts));
        const int before=allocations;
        CHECK(values.remove(scpp::int64(4)) && !values.remove(scpp::int64(4)));
        CHECK(counts.destroyed==4 && !values.has(scpp::int64(4)));
        values.clear();CHECK(counts.destroyed==5 && allocations==before);
    }
    {
        scpp::string_record_hash values;
        const scpp::string key("key");
        values.set(key,fixture::record(counts));
        CHECK(values.has(key));
        values.clear();CHECK(counts.destroyed==6);
    }
    scpp::integer_hash integers;
    integers.set(scpp::int64(2),scpp::int64(7));
    auto cursor=integers.iterate();
    auto copied=integers;
    integers.clear();
    CHECK(copied.at(scpp::int64(2)).native_value()==7);
    CHECK(copied.find(scpp::int64(2)).has_value());
    CHECK(cursor.key().native_value()==2 && cursor.value().native_value()==7);
    cursor.next(); CHECK(!cursor.valid()); cursor.next(); CHECK(!cursor.valid());
    std::puts("PASS: move-only hash lifetime/transfer/erase, no mutation allocations, copy/cursor snapshots");
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
