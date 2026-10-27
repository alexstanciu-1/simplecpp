"""Prove compact scalar identity plans and optionally compile a small eager consumer."""
import argparse
import copy
import json
from pathlib import Path
import subprocess
import tempfile
import time

root=Path(__file__).resolve().parents[3]
parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--tool',type=Path,required=True)
parser.add_argument('--core',type=Path)
parser.add_argument('--sanitize',action='store_true')
args=parser.parse_args()
metadata=json.loads((root/'runtime/metadata/public_api.json').read_text())
published=json.loads((root/'runtime/public/specializations.json').read_text())
plain=['int64','float64','boolean','string','null']
values={'int64':'scpp::int64(1)','float64':'scpp::float64(1.0)','boolean':'scpp::boolean(true)',
        'string':'scpp::string("1")','null':'scpp::null_t{}'}
def call(data,error=None):
    p=subprocess.run([str(args.tool)],input=json.dumps(data),text=True,capture_output=True)
    if error:
        assert p.returncode and error in p.stderr,p.stderr
        return
    assert p.returncode==0,p.stderr
    return json.loads(p.stdout)
def resolve(op,left,right,meta=metadata,error=None):
    return call({'action':'resolve_operator','metadata':meta,'request':{'operation':op,'left':left,'right':right}},error)
plans=[]
for op in ['strict_identical','strict_not_identical']:
    for left in plain:
        for right in plain:
            p=resolve(op,left,right); plans.append(p)
            assert p['conversion']=='identity' and p['evaluation']=='ordered_eager_left_then_right'
            if left!=right:
                assert p['kind']=='constant_after_evaluation'
                assert p['value']==('false' if op=='strict_identical' else 'true')
                assert 'api' not in p and 'operand_type' not in p
            else:
                assert p['kind']=='call' and p['operand_type']==left
                assert p['api']==op+'_'+left
                assert p['header']==published['apis'][p['api']]['header']
    for box in ['mixed','dynamic']:
        assert resolve(op,box,box)['kind']=='call'
    for a,b in [('int8','int64'),('uint64','float64'),('mixed','dynamic')]:
        resolve(op,a,b,error='Unsupported operator')
changed=copy.deepcopy(metadata)
changed['global_operators']['operations']['strict_identical']['distinct_result']='true'
assert resolve('strict_identical','int64','string',changed)['value']=='true'
changed=copy.deepcopy(metadata)
changed['global_operators']['groups']['strict_scalar_identity']['rules'].append({'left':'signed','right':'string','selection':'same_or_constant'})
resolve('strict_identical','int64','string',changed,error='Ambiguous')
changed=copy.deepcopy(metadata)
changed['function_sets']['strict_identical']['function']['operation']['returns']='string'
resolve('strict_identical','int64','int64',changed,error='signature mismatch')
changed=copy.deepcopy(metadata)
changed['global_operators']['operations']['strict_identical']['distinct_result']='0'
resolve('strict_identical','int64','string',changed,error='Constant selection requires a boolean literal')
changed=copy.deepcopy(metadata)
changed['types']['flag']=copy.deepcopy(changed['types']['boolean'])
changed['global_operators']['operations']['strict_identical']['returns']='flag'
changed['function_sets']['strict_identical']['function']['operation']['returns']='flag'
assert resolve('strict_identical','int64','string',changed)['result_type']=='flag'
assert resolve('strict_identical','int64','int64',changed)['result_type']=='flag'
changed=copy.deepcopy(metadata)
changed['global_operators']['groups']['strict_scalar_identity']['rules'][0]['domain']='signed'
resolve('strict_identical','int64','string',changed,error='Preserving selection cannot specify a promotion domain')
changed=copy.deepcopy(metadata)
changed['function_sets']['strict_identical']['function']['header']='scpp/custom/${variant}.hpp'
assert resolve('strict_identical','int64','int64',changed)['header']=='scpp/custom/int64.hpp'
# Linking to existing function sets does not emit another implementation.
apis=sorted({p['api'] for p in plans if p['kind']=='call'})
envelope={'metadata':metadata,'request':{'roots':[],'apis':apis},'leaves':{},
          'imports':{n:published['types'][n] for n in ['float64','mixed','dynamic']},'layouts':{},'names':{},
          'api_imports':{a:published['apis'][a] for a in apis}}
assert call(envelope)['units']==[]
print('PASS: 50 scalar plans, constants after eager evaluation, existing ownership, explicit admission and metadata rejection')
if not args.core:
    raise SystemExit(0)

headers=sorted({p['header'] for p in plans if p['kind']=='call'})
common='''#include <cstdio>
#include <cstdlib>
#include <new>
static unsigned allocations=0;
static bool counting=false;
void *operator new(std::size_t n) { if(counting) ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"plan consumer failed %d\\n",__LINE__); std::abort(); } } while(false)
int main() {
counting=true;
'''
def render(native):
    code=('#include "operators/identity/strict_identity.hpp"\n' if native else '#include "public_api_guard.hpp"\n'+''.join('#include "'+h+'"\n' for h in headers))+common
    for p in plans:
        if native:
            expression='scpp::'+p['operation'].removeprefix('strict_')+'(left,right)'
        elif p['kind']=='call': expression=p['function']+'(left,right)'
        else: expression='scpp::boolean('+p['value']+')'
        expected=(p['left']==p['right']) == (p['operation']=='strict_identical')
        code+='{ int order=0;\n'
        code+='const auto left=([&]{ order=order*10+1; return '+values[p['left']]+'; })();\n'
        code+='const auto right=([&]{ order=order*10+2; return '+values[p['right']]+'; })();\n'
        code+='const auto result='+expression+'; CHECK(order==12); CHECK(static_cast<bool>(result)=='+str(expected).lower()+'); }\n'
    code+='counting=false; CHECK(allocations==0);\n'
    # Constants do not authorize skipping side effects or evaluating RHS after LHS failure.
    for throwing in ['left','right']:
        code+='{ int order=0; bool failed=false; try {\n'
        code+='const auto left=([&]{ order=order*10+1; '+('throw 7; ' if throwing=='left' else '')+'return scpp::int64(1); })();\n'
        code+='const auto right=([&]{ order=order*10+2; '+('throw 7; ' if throwing=='right' else '')+'return scpp::string("1"); })();\n'
        code+='(void)left; (void)right; CHECK(false); } catch(int) { failed=true; }\n'
        code+='CHECK(failed && order=='+('1' if throwing=='left' else '12')+'); }\n'
    code+='std::puts("PASS: eager identity consumer, all scalar results, operand effects/failures, zero successful allocations"); }\n'
    if native:
        for public,private in [('scpp::int64','scpp::int_t<>'),('scpp::float64','scpp::float_t'),('scpp::boolean','scpp::bool_t'),('scpp::string(','scpp::string_t(')]: code=code.replace(public,private)
    return code
rows=[]
with tempfile.TemporaryDirectory(prefix='scpp-identity-plan-') as directory:
    stage=Path(directory)
    for native in [False,True]:
        name='native' if native else 'public'
        path=stage/(name+'.cpp');path.write_text(render(native))
        flags=['-fsanitize=address,undefined','-fno-sanitize-recover=all'] if args.sanitize else []
        start=time.perf_counter()
        subprocess.run(['clang++','-std=c++23','-O2',*flags,'-I'+str(root/'runtime'/('include' if native else 'public')),
                        '-I'+str(root/'tests/runtime/native'),str(path),str(args.core.resolve()),'-fuse-ld=mold','-o',str(stage/name)],check=True)
        rows.append({'case':name,'compile_and_link_seconds':time.perf_counter()-start})
        subprocess.run([stage/name],check=True)
print(json.dumps(rows,indent=2))
