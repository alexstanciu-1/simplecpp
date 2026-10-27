"""Prove one xor backend plus independent ordered boolean conversions."""
import argparse
import json
from pathlib import Path
import subprocess
import tempfile
import time
root=Path(__file__).resolve().parents[3]
p=argparse.ArgumentParser(description=__doc__)
p.add_argument('--tool',type=Path,required=True)
p.add_argument('--core',type=Path)
p.add_argument('--sanitize',action='store_true')
args=p.parse_args()
m=json.loads((root/'runtime/metadata/public_api.json').read_text())
pub=json.loads((root/'runtime/public/specializations.json').read_text())
def call(data,error=None):
    r=subprocess.run([str(args.tool)],input=json.dumps(data),text=True,capture_output=True)
    if error:
        assert r.returncode and error in r.stderr,r.stderr
        return
    assert r.returncode==0,r.stderr
    return json.loads(r.stdout)
def resolve(a,b,error=None):
    return call({'action':'resolve_operator','metadata':m,'request':{'operation':'logical_xor','left':a,'right':b}},error)
pairs=[('boolean','boolean'),('int64','int64'),('float64','float64'),('int64','float64'),('float64','int64')]
headers=set()
for a,b in pairs:
    plan=resolve(a,b)
    assert plan['api']=='logical_xor_boolean' and plan['operand_type']=='boolean' and plan['result_type']=='boolean'
    assert plan['kind']=='call' and plan['evaluation']=='ordered_eager_left_then_right'
    headers.add(plan['header'])
    for source in [a,b]:
        c=call({'action':'resolve_conversion','metadata':m,'request':{'source':source,'target':plan['operand_type']}})
        assert c['operand_type']==source
        headers.add(c['header'])
for a,b in [('boolean','int64'),('float64','boolean'),('string','string'),('mixed','mixed'),('null','int64'),('int32','int64'),('uint64','uint64')]:
    resolve(a,b,'Unsupported operator')
base={'metadata':m,'request':{'roots':[],'apis':['logical_xor_boolean']},'leaves':{},'imports':{},'layouts':{},'names':{}}
plan=call(base);assert len(plan['units'])==1
assert 'template' not in plan['units'][0]['header']
base['api_imports']=pub['apis'];assert call(base)['units']==[]
print('PASS: five admitted xor pairings, independent bool conversions, one backend and zero-unit reuse')
if not args.core: raise SystemExit(0)
samples={
    'boolean':[('scpp::boolean(false)',False),('scpp::boolean(true)',True)],
    'int64':[('scpp::int64(0)',False),('scpp::int64(-1)',True),('scpp::int64(1)',True)],
    'float64':[('scpp::float64(0.0)',False),('scpp::float64(-0.0)',False),('scpp::float64(1.0)',True),
               ('scpp::float64(-1.0)',True),('scpp::float64(std::numeric_limits<double>::infinity())',True),
               ('scpp::float64(std::numeric_limits<double>::quiet_NaN())',True)]}
common='''#include <cstdio>
#include <cstdlib>
#include <limits>
#include <new>
static unsigned allocations=0;
static bool counting=false;
void *operator new(std::size_t n) { if(counting) ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"xor consumer failed %d\\n",__LINE__); std::abort(); } } while(false)
int main() { counting=true;
'''
def render(native):
    text=('#include "scpp/cast.hpp"\n' if native else '#include "public_api_guard.hpp"\n'+''.join('#include "'+h+'"\n' for h in sorted(headers)))+common
    cast='scpp::cast<scpp::bool_t>' if native else 'scpp::cast_boolean'
    operation='scpp::bool_t(left_bool.native_value()!=right_bool.native_value())' if native else 'scpp::logical_xor(left_bool,right_bool)'
    for a,b in pairs:
        for left,lt in samples[a]:
            for right,rt in samples[b]:
                text+='{ int order=0;\n'
                text+='const auto left=([&]{ order=order*10+1; return '+left+'; })();\n'
                text+='const auto left_bool=([&]{ order=order*10+2; return '+cast+'(left); })();\n'
                text+='const auto right=([&]{ order=order*10+3; return '+right+'; })();\n'
                text+='const auto right_bool=([&]{ order=order*10+4; return '+cast+'(right); })();\n'
                text+='const auto result='+operation+'; CHECK(order==1234); CHECK(static_cast<bool>(result)=='+str(lt!=rt).lower()+'); }\n'
    text+='counting=false; CHECK(allocations==0);\n'
    for fail in range(1,5):
        text+='{ int order=0; bool failed=false; try {\n'
        stages=[('left','scpp::float64(1.0)'),('left_bool',cast+'(left)'),('right','scpp::int64(0)'),('right_bool',cast+'(right)')]
        for stage,(name,expression) in enumerate(stages,1):
            text+='const auto '+name+'=([&]{ order=order*10+'+str(stage)+'; '+('throw 7; ' if stage==fail else '')+'return '+expression+'; })();\n'
        text+='(void)left_bool; (void)right_bool; CHECK(false); } catch(int) { failed=true; } CHECK(failed && order=='+'1234'[:fail]+'); }\n'
    text+='std::puts("PASS: 85 xor cases, conversion order, eager RHS, failure suppression; successful allocations=0"); }\n'
    if native:
        for a,b in [('scpp::boolean','scpp::bool_t'),('scpp::int64','scpp::int_t<>'),('scpp::float64','scpp::float_t')]: text=text.replace(a,b)
    return text
rows=[]
with tempfile.TemporaryDirectory(prefix='scpp-xor-proof-') as directory:
    stage=Path(directory)
    for native in [False,True]:
        name='native' if native else 'public'; source=stage/(name+'.cpp');source.write_text(render(native))
        flags=['-fsanitize=address,undefined','-fno-sanitize-recover=all'] if args.sanitize else []
        start=time.perf_counter()
        subprocess.run(['clang++','-std=c++23','-O2',*flags,'-I'+str(root/'runtime'/('include' if native else 'public')),
                        '-I'+str(root/'tests/runtime/native'),str(source),str(args.core.resolve()),'-fuse-ld=mold','-o',str(stage/name)],check=True)
        rows.append({'case':name,'compile_and_link_seconds':time.perf_counter()-start})
        subprocess.run([stage/name],check=True)
print(json.dumps(rows,indent=2))
