"""Execute metadata-planned boxed logical operations through public headers only."""
import argparse
import json
from pathlib import Path
import subprocess
import sys
import tempfile
import time
root=Path(__file__).resolve().parents[3]
sys.path.insert(0,str(root/'runtime/tools'))
import concrete_types
parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--tool',type=Path,required=True)
parser.add_argument('--core',type=Path)
parser.add_argument('--sanitize',action='store_true')
args=parser.parse_args()
metadata=concrete_types.metadata(root/'runtime')
headers=set();functions=[];seen=set()
for box in ['mixed','dynamic']:
    for peer in [box,'boolean','int64','float64']:
        for left,right in [(box,peer),(peer,box)]:
            for operation in ['logical_and','logical_or']:
                if (left,right,operation) in seen:continue
                seen.add((left,right,operation))
                result=subprocess.run([str(args.tool)],input=json.dumps({'action':'resolve_logical','metadata':metadata,
                    'request':{'operation':operation,'left':left,'right':right}}),text=True,capture_output=True)
                assert result.returncode==0,result.stderr
                plan=json.loads(result.stdout)
                assert plan['left']['projection']['kind']==plan['right']['projection']['kind']=='identity'
                headers.update([plan[side]['condition']['header'] for side in ['left','right']])
                short='true' if plan['short_circuit'] else 'false'
                functions.append(f'static scpp::boolean {operation}(const scpp::{left} &left,const scpp::{right} &right,int &effects) {{ '
                    f'++effects; const auto truth={plan["left"]["condition"]["function"]}(left); '
                    f'if(truth.native_value()=={short}) return scpp::boolean({short}); '
                    f'++effects; return {plan["right"]["condition"]["function"]}(right); }}')
code='\n'.join('#include "'+header+'"' for header in sorted(headers))+r'''
#include "public_api_guard.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>
#include <initializer_list>
using namespace scpp;
static unsigned allocations=0;
void *operator new(std::size_t n) { ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"boxed logical failure %d\n",__LINE__); std::abort(); } } while(false)
'''+ '\n'.join(functions)+r'''
template<class Box> static void family() {
    const Box values[]={Box{},Box(boolean(false)),Box(int64(0)),Box(float64(-0.0)),Box(string("")),Box(string("0")),
        Box(boolean(true)),Box(int64(2)),Box(float64(-1.5)),Box(string("a sufficiently long true string that would expose a copying allocation"))};
    const auto before=allocations;
    for(int i=0;i<10;++i) for(int j=0;j<10;++j) {
        const bool a=i>=6,b=j>=6; int effects=0;
        CHECK(logical_and(values[i],values[j],effects).native_value()==(a&&b)); CHECK(effects==(a?2:1));
        effects=0; CHECK(logical_or(values[i],values[j],effects).native_value()==(a||b)); CHECK(effects==(a?1:2));
    }
    for(const auto &box:values) {
        const bool truth=condition_truthy(box).native_value();
        for(int n=0;n<2;++n) {
            int effects=0; CHECK(logical_and(box,boolean(n),effects).native_value()==(truth&&bool(n)));
            effects=0; CHECK(logical_or(boolean(n),box,effects).native_value()==(bool(n)||truth));
            effects=0; CHECK(logical_and(box,int64(n),effects).native_value()==(truth&&bool(n)));
            effects=0; CHECK(logical_or(int64(n),box,effects).native_value()==(bool(n)||truth));
            effects=0; CHECK(logical_and(box,float64(n),effects).native_value()==(truth&&bool(n)));
            effects=0; CHECK(logical_or(float64(n),box,effects).native_value()==(bool(n)||truth));
            effects=0; CHECK(logical_or(box,boolean(n),effects).native_value()==(truth||bool(n)));
            effects=0; CHECK(logical_and(boolean(n),box,effects).native_value()==(bool(n)&&truth));
            effects=0; CHECK(logical_or(box,int64(n),effects).native_value()==(truth||bool(n)));
            effects=0; CHECK(logical_and(int64(n),box,effects).native_value()==(bool(n)&&truth));
            effects=0; CHECK(logical_or(box,float64(n),effects).native_value()==(truth||bool(n)));
            effects=0; CHECK(logical_and(float64(n),box,effects).native_value()==(bool(n)&&truth));
        }
    }
    CHECK(allocations==before);
}
int main() {
    family<mixed>();family<dynamic>();
    const auto bad=make_mixed_table();const mixed no(boolean(false)),yes(boolean(true));
    int effects=0;CHECK(!logical_and(no,bad,effects).native_value()&&effects==1);
    effects=0;CHECK(logical_or(yes,bad,effects).native_value()&&effects==1);
    unsigned caught=0;
    effects=0;try{(void)logical_and(yes,bad,effects);}catch(...){++caught;}CHECK(effects==2);
    effects=0;try{(void)logical_or(no,bad,effects);}catch(...){++caught;}CHECK(effects==2);
    effects=0;try{(void)logical_and(bad,yes,effects);}catch(...){++caught;}CHECK(effects==1);
    CHECK(caught==3);
    const auto object=make_dynamic_container();const dynamic empty;
    const auto before=allocations;
    effects=0;CHECK(logical_or(object,empty,effects).native_value()&&effects==1);
    effects=0;CHECK(logical_and(object,object,effects).native_value()&&effects==2);
    CHECK(allocations==before);
    std::puts("PASS: boxed/scalar logical pairs, short circuit, mixed table rejection, dynamic object truth; success allocations=0");
}
'''
if not args.core:
    print(f'PASS: {len(functions)} boxed logical public consumer plans')
    sys.exit(0)
with tempfile.TemporaryDirectory(prefix='scpp-boxed-logical-') as directory:
    work=Path(directory);caller=work/'caller.cpp';caller.write_text(code)
    flags=['-std=c++23','-O1']
    if args.sanitize:flags+=['-fsanitize=address,undefined','-fno-omit-frame-pointer']
    start=time.perf_counter()
    subprocess.run(['clang++',*flags,'-I'+str(root/'runtime/public'),'-I'+str(root/'tests/runtime/native'),'-c',str(caller),'-o',str(work/'caller.o')],check=True)
    elapsed=time.perf_counter()-start
    subprocess.run(['clang++',*flags,str(work/'caller.o'),str(args.core.resolve()),'-pthread','-o',str(work/'proof')],check=True)
    subprocess.run([str(work/'proof')],check=True)
    print(f'PASS: {len(functions)} public-only logical consumers; caller compile={elapsed:.4f}s')
