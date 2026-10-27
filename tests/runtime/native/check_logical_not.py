"""Plan logical negation from existing observations without generating adapters."""
import argparse
import copy
import json
import sys
from pathlib import Path
import subprocess
import tempfile
import time
root=Path(__file__).resolve().parents[3]
sys.path.insert(0,str(root/'runtime/tools'))
import concrete_types
p=argparse.ArgumentParser(description=__doc__)
p.add_argument('--tool',type=Path,required=True)
p.add_argument('--core',type=Path)
p.add_argument('--sanitize',action='store_true')
args=p.parse_args()
m=concrete_types.load_api_metadata(root/'runtime')
pub=json.loads((root/'runtime/public/specializations.json').read_text())
def call(data,error=None):
    r=subprocess.run([str(args.tool)],input=json.dumps(data),text=True,capture_output=True)
    if error:
        assert r.returncode and error in r.stderr,r.stderr
        return
    assert r.returncode==0,r.stderr
    return json.loads(r.stdout)
def resolve(t,meta=m,error=None):
    return call({'action':'resolve_operator','metadata':meta,'request':{'operation':'logical_not','operand':t}},error)
plans={t:resolve(t) for t in ['int64','float64','boolean','mixed','dynamic','error_value']}
for t,plan in plans.items():
    assert plan['api']=='condition_truthy_'+t and plan['result_expression']=='!${result}'
    assert plan['evaluation']=='evaluate_operand_once' and plan['conversion']=='identity'
    assert plan['result_type']=='boolean'
for t in ['string','int8','uint64','null','vector<string>']:
    resolve(t,error='Unsupported unary')
for expression in ['true','${result} && ${result}']:
    changed=copy.deepcopy(m); changed['global_operators']['operations']['logical_not']['result_expression']=expression
    resolve('boolean',changed,error='exactly one result marker')
changed=copy.deepcopy(m)
changed['global_operators']['operations']['add']['result_expression']='!${result}'
resolve('boolean',changed,error='requires a unary operation')
# Transformation is metadata, not hard-coded negation in the generator.
changed=copy.deepcopy(m)
changed['global_operators']['operations']['logical_not']['result_expression']='${result}'
assert resolve('boolean',changed)['result_expression']=='${result}'
base={'metadata':m,'request':{'roots':[],'apis':[p['api'] for p in plans.values()]},'imports':{t:pub['types'][t] for t in ['mixed','dynamic','float64']},'leaves':{},'layouts':{},'names':{},'api_imports':pub['apis']}
assert call(base)['units']==[]
print('PASS: six unary plans reuse condition APIs, explicit admission and one-result transformation; zero new implementations')
if not args.core: raise SystemExit(0)
samples={
'int64':[('int64(0)',True),('int64(-1)',False)],
'float64':[('float64(0.0)',True),('float64(-0.0)',True),('float64(1.5)',False),('float64(std::numeric_limits<double>::infinity())',False),('float64(std::numeric_limits<double>::quiet_NaN())',False)],
'boolean':[('boolean(false)',True),('boolean(true)',False)],
'error_value':[('error_value{}',True),('error_value::make(string("error"))',False)],
'mixed':[('mixed{}',True),('mixed(boolean(false))',True),('mixed(int64(0))',True),('mixed(float64(1.5))',False),('mixed(string("0"))',True),('mixed(string("false"))',False),('make_mixed_table()',None)],
'dynamic':[('dynamic{}',True),('dynamic(boolean(false))',True),('dynamic(int64(0))',True),('dynamic(float64(1.5))',False),('dynamic(string("0"))',True),('dynamic(string("false"))',False),('make_dynamic_container()',False)]}
source='#include "public_api_guard.hpp"\n'+''.join('#include "'+p['header']+'"\n' for p in plans.values())
source+='''#include "scpp/exception_info.hpp"
#include <cstdio>
#include <cstdlib>
#include <limits>
#include <new>
using namespace scpp;
static unsigned allocations=0;
static bool counting=false;
void *operator new(std::size_t n) { if(counting) ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"not failure %d\\n",__LINE__); std::abort(); } } while(false)
int main() {
'''
for t,values in samples.items():
    plan=plans[t]
    expression=plan['result_expression'].replace('${result}','observed')
    for value,expected in values:
        source+='{ const auto input='+value+'; int evaluations=0; bool failed=false; allocations=0; counting=true; try {\n'
        source+='const auto &operand=([&]() -> const auto & { ++evaluations; return input; })();\n'
        source+='const auto observed='+plan['function']+'(operand); const auto result='+expression+';\n'
        source+='counting=false; CHECK(allocations==0); CHECK(static_cast<bool>(result)=='+str(bool(expected)).lower()+');\n'
        source+='} catch (...) { counting=false; failed=true; CHECK(capture_runtime_exception().code().equals("condition_truthy_reject_mixed_kind")); }\n'
        source+='CHECK(evaluations==1); CHECK(failed=='+str(expected is None).lower()+'); }\n'
source+='''{ int stages=0; bool failed=false; try {
 const auto value=([&]() -> boolean { ++stages; throw 7; })();
 ++stages; (void)value;
 } catch(int) { failed=true; } CHECK(failed && stages==1); }
 std::puts("PASS: planned logical negation, false-like boxed strings, kind rejection, operand once; successful allocations=0");
}
'''
with tempfile.TemporaryDirectory(prefix='scpp-logical-not-') as d:
    stage=Path(d); (stage/'main.cpp').write_text(source)
    flags=['-fsanitize=address,undefined','-fno-sanitize-recover=all'] if args.sanitize else []
    start=time.perf_counter()
    subprocess.run(['clang++','-std=c++23','-O2',*flags,'-I'+str(root/'runtime/public'),'-I'+str(root/'tests/runtime/native'),str(stage/'main.cpp'),str(args.core.resolve()),'-fuse-ld=mold','-o',str(stage/'main')],check=True)
    print(json.dumps({'public_proof_compile_and_link_seconds':time.perf_counter()-start}))
    subprocess.run([stage/'main'],check=True)
