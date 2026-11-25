"""Preserve heterogeneous arguments and reuse symmetric identity adapters."""
import copy
import json
from pathlib import Path
import subprocess
import sys
root=Path(__file__).resolve().parents[3]
sys.path.insert(0,str(root/'runtime/tools'))
import concrete_types
m=concrete_types.load_api_metadata(root/'runtime')
pub=json.loads((root/'runtime/public/specializations.json').read_text())
def call(data,error=None):
    r=subprocess.run([sys.argv[1]],input=json.dumps(data),text=True,capture_output=True)
    if error:
        assert r.returncode and error in r.stderr,r.stderr
        return
    assert r.returncode==0,r.stderr
    return json.loads(r.stdout)
def resolve(op,left,right,metadata=m,error=None):
    return call({'action':'resolve_operator','metadata':metadata,'request':{'operation':op,'left':left,'right':right}},error)
apis=set()
plans=[]
for op in ['strict_identical','strict_not_identical']:
    for box in ['mixed','dynamic']:
        for plain in ['boolean','int64','float64','string','null']:
            for reverse in [False,True]:
                p=resolve(op,plain if reverse else box,box if reverse else plain)
                assert p['argument_order']==('right_left' if reverse else 'left_right')
                assert p['conversion']=='identity' and p['evaluation']=='ordered_eager_left_then_right'
                assert 'operand_type' not in p
                assert p['api']==op+'_boxed_'+box+'_'+plain
                apis.add(p['api']); plans.append(p)
    for a,b in [('mixed','dynamic'),('dynamic','mixed'),('mixed','int8'),('uint64','dynamic'),('int8','int8')]:
        resolve(op,a,b,error='Unsupported operator')
changed=copy.deepcopy(m)
changed['function_sets']['strict_identical_boxed']['variants']['mixed_int64']['plain']='string'
resolve('strict_identical','mixed','int64',changed,error='signature mismatch')
changed=copy.deepcopy(m)
changed['global_operators']['groups']['strict_scalar_identity']['rules'][-1]['reverse']='yes'
resolve('strict_identical','int64','mixed',changed,error='Invalid ordered pair')
base={'metadata':m,'request':{'roots':[],'apis':sorted(apis)},'imports':{t:pub['types'][t] for t in ['mixed','dynamic','float64']},'leaves':{},'layouts':{},'names':{}}
p=call(base); assert len(p['units'])==20 and all('template' not in u['header'] for u in p['units'])
base['api_imports']=pub['apis']; assert call(base)['units']==[]
print('PASS: 40 ordered pair plans, 20 symmetric implementations, no boxing or promotion, explicit rejections and reuse')

# Optional compiled consumer proves plan argument permutation happens only after
# left-to-right evaluation, including failure suppression. Frontend adoption is separate.
if len(sys.argv)>2:
    import tempfile
    headers=sorted({p['header'] for p in plans})
    source='#include "public_api_guard.hpp"\n'+''.join('#include "'+h+'"\n' for h in headers)
    source+='#include <cstdlib>\n#define CHECK(x) do { if(!(x)) std::abort(); } while(false)\nint main() {\n'
    samples={'int64':'scpp::int64(7)','float64':'scpp::float64(7.0)','boolean':'scpp::boolean(true)','string':'scpp::string("same")','null':'scpp::null_t{}'}
    for p in plans:
        plain=p['right'] if p['left'] in ['mixed','dynamic'] else p['left']
        expressions=[('scpp::'+t+'('+samples[plain]+')') if t in ['mixed','dynamic'] else samples[t] for t in [p['left'],p['right']]]
        for failure in [0,1,2]:
            source+='{ int order=0; bool failed=false; try {\n'
            for i,(name,expression) in enumerate(zip(['left','right'],expressions),1):
                source+='const auto '+name+'=([&]{ order=order*10+'+str(i)+'; '+('throw 7; ' if failure==i else '')+'return '+expression+'; })();\n'
            arguments='right,left' if p['argument_order']=='right_left' else 'left,right'
            source+='CHECK(static_cast<bool>('+p['function']+'('+arguments+'))=='+str(p['operation']=='strict_identical').lower()+');\n'
            source+='} catch(int) { failed=true; } CHECK(order=='+('1' if failure==1 else '12')+'); CHECK(failed=='+str(bool(failure)).lower()+'); }\n'
    source+='}\n'
    with tempfile.TemporaryDirectory(prefix='scpp-pair-plan-') as d:
        stage=Path(d); (stage/'main.cpp').write_text(source)
        flags=['-fsanitize=address,undefined','-fno-sanitize-recover=all'] if '--sanitize' in sys.argv else []
        subprocess.run(['clang++','-std=c++23','-O2',*flags,'-I'+str(root/'runtime/public'),'-I'+str(root/'tests/runtime/native'),str(stage/'main.cpp'),str(Path(sys.argv[2]).resolve()),'-fuse-ld=mold','-o',str(stage/'main')],check=True)
        subprocess.run([stage/'main'],check=True)
    print('PASS: 40 compiled plans in both directions, ordered effects and failure suppression')
