"""String ordering through ordinary metadata and checked carrier projection."""
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
public_types={**metadata['types'],**json.loads((root/'runtime/public/specializations.json').read_text())['types']}
families=['nullable','result','result_or_false','result_or_bool']
names={f+'<'+t+'>':'order_'+str(i)+'_'+t for i,f in enumerate(families) for t in ['string']}
published_types=json.loads((root/'runtime/public/specializations.json').read_text())['types']
names.update({identity:published_types[identity]['name'] for identity in names if identity in published_types})
request={'package':'application.carrier_order','roots':list(names),'names':names}

ops=['less','less_equal','greater','greater_equal']
def resolve(left,right,op,meta=metadata,error=None):
    p=subprocess.run([str(args.tool)],input=json.dumps({'metadata':meta,'names':names,'action':'resolve_operator','request':{'operation':op,'left':left,'right':right}}),text=True,capture_output=True)
    if error:
        assert p.returncode and error in p.stderr,p.stderr
        return
    assert p.returncode==0,p.stderr
    return json.loads(p.stdout)
for op in ops:
    for left in ['string',*names]:
        for right in ['string',*names]:
            p=resolve(left,right,op)
            assert p['result_type']=='boolean'
            calc=p['calculation'] if p['kind']=='checked_payload_binary' else p
            assert calc['api']=='text_'+op+'_string_string'
            assert calc['argument_order']=='left_right'
    for bad in ['int64','boolean','mixed','dynamic','nullable<int64>','result<nullable<string>>']:
        resolve('nullable<string>',bad,op,error='Unsupported')
for op in ['add','subtract','multiply','divide','remainder','compare','bit_and','power']:
    resolve('nullable<string>','string',op,error='Unsupported')
changed=copy.deepcopy(metadata);changed['families']['holder']=copy.deepcopy(changed['families']['nullable'])
assert resolve('holder<string>','string','less',changed)['kind']=='checked_payload_binary'
print('PASS: 100 plain/carrier ordering plans, renamed family and rejected cross-domain arithmetic/ordering')
headers=set();functions=[];checks=[];failures=[]
def cpp(t):return 'scpp::'+names[t] if t in names else 'scpp::string'
def project(plan,v):
    if plan['kind']=='identity':return v
    headers.add(plan['header']);return plan['expression'].replace('${value}',v)
# Execute every operation/family with both plain/carrier operand orders and
# same-family aliasing, without compiling the full cross-family product.
for op in ops:
    for family in families:
        t=family+'<string>'
        for left,right in [(t,'string'),('string',t),(t,t)]:
            p=resolve(left,right,op);calc=p['calculation'];headers.add(calc['header'])
            fn='order_'+str(len(functions))
            body='const auto saved_left='+project(p['left_projection'],'left')+'; ++effects; if(mutate) left=scpp::string("z"); const auto saved_right='+project(p['right_projection'],'right')+'; return '+calc['function']+'(saved_left,saved_right);'
            functions.append('static scpp::boolean '+fn+'('+cpp(left)+' &left,const '+cpp(right)+' &right,int &effects,bool mutate=false) { '+body+' }')
            for a,b,cmp in [('a','b',-1),('b','a',1),('a','a',0),('','a',-1),('a\\0b','a\\0c',-1)]:
                def expr(t,text):
                    if '\\0' in text:return cpp(t)+'(scpp::string("'+text.replace('\\0',r'\0')+'",scpp::uint64(3)))'
                    return cpp(t)+'(scpp::string("'+text+'"))'
                expected={'less':cmp<0,'less_equal':cmp<=0,'greater':cmp>0,'greater_equal':cmp>=0}[op]
                checks.append(f'{{ auto left={expr(left,a)}; const auto right={expr(right,b)}; int effects=0; CHECK({fn}(left,right,effects).native_value()=={str(expected).lower()} && effects==1); }}')
            if left==right:
                expected=op in ['less','less_equal']
                checks.append(f'{{ {cpp(left)} left(scpp::string("a")); int effects=0; CHECK({fn}(left,left,effects,true).native_value()=={str(expected).lower()} && effects==1 && left.value().equals("z")); }}')
            carrier=cpp(t)
            states=[carrier+'()'] if family=='nullable' else [carrier+'(failure)'] if family=='result' else [carrier+'::from_false()']
            if family=='result_or_bool':states.append(carrier+'::from_true()')
            for initial in states:
                for side in (['left','right'] if left==right else ['left' if left==t else 'right']):
                    l=initial if side=='left' else cpp(left)+'(scpp::string("a"))'
                    r=initial if side=='right' else cpp(right)+'(scpp::string("b"))'
                    failures.append(f'{{ auto left={l}; const auto right={r}; int effects=0; bool caught=false; try {{ (void){fn}(left,right,effects); }} catch (...) {{ caught=true; }} CHECK(caught && effects=={0 if side=="left" else 1}); }}')
print(f'PASS: {len(checks)} successful byte/order cases and {len(failures)} unavailable-state cases')
if not args.core:sys.exit(0)
with tempfile.TemporaryDirectory(prefix='scpp-carrier-order-') as directory:
    work=Path(directory);sdk=work/'sdk';app=work/'app'
    for folder in ['metadata','include','public']:shutil.copytree(root/'runtime'/folder,sdk/folder)
    (sdk/'lib').mkdir();(sdk/'tools').mkdir()
    shutil.copy2(args.core,sdk/'lib/libcore.a');shutil.copy2(args.tool,sdk/'tools/scpp_api_generator')
    seal_package(sdk,['core']);os.environ['SCPP_API_GENERATOR']=str(sdk/'tools/scpp_api_generator')
    request_file=work/'request.json';request_file.write_text(json.dumps(request))
    manifest=concrete_types.generate(request_file,app,sdk,[sdk],'clang++',[])
    assert len(manifest['sources'])==sum(identity not in published_types for identity in names)
    code='\n'.join('#include "'+h+'"' for h in sorted(headers))+r'''
#include "public_api_guard.hpp"
#include <cstdlib>
#include <cstdio>
#include <new>
static int allocations=0;
void *operator new(__SIZE_TYPE__ n) { ++allocations; if(auto p=std::malloc(n)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,__SIZE_TYPE__) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"failed line %d\n",__LINE__);std::abort(); } } while(false)
'''
    code+='\n'.join(functions)+'\nint main() { const int before=allocations;\n'+'\n'.join(checks)
    code+='\nCHECK(allocations==before); const auto failure=scpp::error_value::make(scpp::string("failure"),scpp::int64(7),scpp::string("input"));\n'+'\n'.join(failures)
    code+='\nstd::puts("PASS: string carrier ordering, byte semantics and checked extraction; success allocations=0"); }'
    caller=work/'caller.cpp';caller.write_text(code)
    flags=['-std=c++23','-O1']
    if args.sanitize:flags+=['-fsanitize=address,undefined','-fno-omit-frame-pointer']
    common=['clang++',*flags,'-I'+str(app/'public'),'-I'+str(sdk/'public')]
    objects=[];start=time.perf_counter()
    for i,source in enumerate(manifest['sources']):
        obj=work/f'{i}.o';objects.append(str(obj))
        subprocess.run([*common,'-I'+str(app/'include'),'-I'+str(sdk/'include'),'-c',str(app/source),'-o',str(obj)],check=True)
    storage=time.perf_counter()-start;start=time.perf_counter()
    subprocess.run([*common,'-I'+str(root/'tests/runtime/native'),'-c',str(caller),'-o',str(work/'caller.o')],check=True)
    caller_time=time.perf_counter()-start
    subprocess.run(['clang++',*flags,str(work/'caller.o'),*objects,str(args.core.resolve()),'-pthread','-o',str(work/'proof')],check=True)
    subprocess.run([str(work/'proof')],check=True)
    (app/'lib').mkdir();subprocess.run(['ar','rcs',str(app/'lib/liborder.a'),*objects],check=True)
    seal_package(app,['order']);reused=concrete_types.generate(request_file,work/'reuse',sdk,[sdk,app],'clang++',[])
    assert not reused['sources']
    print(f'PASS: sealed reuse; storage compile={storage:.4f}s caller compile={caller_time:.4f}s')
