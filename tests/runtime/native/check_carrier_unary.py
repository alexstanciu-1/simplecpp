"""Canonical carrier unary values and ordered prefix/postfix update snapshots."""
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
names={f+'<'+t+'>':'unary_'+str(i)+'_'+t for i,f in enumerate(families) for t in ['int64','float64']}
request={'package':'application.carrier_unary','roots':list(names),'names':names}
ops=['positive','negative','complement','pre_increment','post_increment','pre_decrement','post_decrement']

def resolve(source,op,meta=metadata,error=None):
    p=subprocess.run([str(args.tool)],input=json.dumps({'metadata':meta,'names':names,'action':'resolve_operator',
        'request':{'operation':op,'operand':source}}),text=True,capture_output=True)
    if error:
        assert p.returncode and error in p.stderr,p.stderr
        return
    assert p.returncode==0,p.stderr
    return json.loads(p.stdout)
plans={}
for source in names:
    for op in ops:
        if source.endswith('<float64>') and op=='complement':
            resolve(source,op,error='Unsupported carrier unary payload');continue
        p=resolve(source,op);plans[source,op]=p
        assert p['kind']=='carrier_unary'
        if op.startswith(('pre_','post_')):
            assert p['result_type']==source and p['writeback_conversion']['kind']=='construct_payload'
            assert p['steps'][0]=='capture_destination_and_retain_owners' and p['steps'][-1]=='return_value_snapshot'
            if op.startswith('post_'):assert p['steps'].index('project_payload')<p['steps'].index('snapshot_old_carrier')<p['steps'].index('calculate')
        else:assert p['result_policy']=='payload' and p['result_type']==p['projection']['target']
for family in families:
    for bad in ['int8','boolean','string','mixed','dynamic','nullable<int64>']:
        for op in ['negative','post_increment']:resolve(family+'<'+bad+'>',op,error='Unsupported carrier unary payload')
changed=copy.deepcopy(metadata);changed['global_operators']['operations']['negative']['carrier_unary']['result']='updated_carrier'
resolve('nullable<int64>','negative',changed,'disagrees with operand mutation')
changed=copy.deepcopy(metadata);changed['global_operators']['operations']['negative']['carrier_unary']['payloads']=['boolean']
resolve('nullable<int64>','negative',changed,'outside scalar admission')
changed=copy.deepcopy(metadata);changed['families']['holder']=copy.deepcopy(changed['families']['nullable'])
assert resolve('holder<int64>','post_increment',changed)['result_type']=='holder<int64>'
changed=copy.deepcopy(metadata);del changed['families']['nullable']['carrier']['states'][0]['reconstruct']
resolve('nullable<int64>','post_increment',changed,'requires one payload construction')
print('PASS: canonical unary/update admission, snapshot policies, named bindings and rejected widening')
headers=set();functions=[];checks=[];failures=[]
for (source,op),p in plans.items():
    cpp='scpp::'+names[source];payload=p['projection']['target'];fn='operate_'+str(len(functions))
    headers.update([p['projection']['header'],p['calculation']['header']])
    mutation=p['result_policy']!='payload'
    expr=p['projection']['expression'].replace('${value}','source')
    body='++captures; '+('auto' if mutation else 'const auto')+' payload='+expr+';'
    if p['result_policy']=='old_carrier':body+='const auto old=source;'
    result_type=cpp if mutation else public_types[p['result_type']]['cpp']
    if not mutation:body+='return '+p['calculation']['function']+'(payload);'
    else:
        wb=p['writeback_conversion'];headers.add(wb['header'])
        body+='(void)'+p['calculation']['function']+'(payload); const auto prepared='+wb['expression'].replace('${value}','payload')+'; source=prepared; return '+('old' if p['result_policy']=='old_carrier' else 'source')+';'
    functions.append('static '+result_type+' '+fn+'('+('' if mutation else 'const ')+cpp+' &source,int &captures) { '+body+' }')
    value='scpp::'+payload+'(5)'
    if mutation:
        updated=6 if op.endswith('increment') else 4
        returned=5 if op.startswith('post_') else updated
        checks.append(f'{{ {cpp} source({value}); int captures=0; auto result={fn}(source,captures); CHECK(captures==1 && source.value().native_value()=={updated} && result.value().native_value()=={returned}); source=scpp::{payload}(19); CHECK(result.value().native_value()=={returned}); }}')
    else:
        expected='5' if op=='positive' else '-5' if op=='negative' else '-6'
        checks.append(f'{{ const {cpp} source({value}); int captures=0; CHECK({fn}(source,captures).native_value()==({expected})); CHECK(captures==1 && source.value().native_value()==5); }}')
    family=source.split('<')[0]
    states=[(cpp+'()','!source.has_value()')] if family=='nullable' else [(cpp+'(failure)','source.has_error() && source.error().get_line().native_value()==7 && source.error().get_message().equals("failure") && source.error().get_file().equals("input")')] if family=='result' else [(cpp+'::from_false()','source.is_false()')]
    if family=='result_or_bool':states.append((cpp+'::from_true()','source.is_true()'))
    for initial,state in states:
        failures.append(f'{{ auto source={initial}; int captures=0; bool caught=false; try {{ (void){fn}(source,captures); }} catch (...) {{ caught=true; }} CHECK(caught && captures==1 && ({state})); }}')
print(f'PASS: {len(plans)} operation plans; {len(checks)} value/snapshot cases and {len(failures)} unavailable-state cases')
if not args.core:sys.exit(0)
with tempfile.TemporaryDirectory(prefix='scpp-carrier-unary-') as directory:
    work=Path(directory);sdk=work/'sdk';app=work/'app'
    for folder in ['metadata','include','public']:shutil.copytree(root/'runtime'/folder,sdk/folder)
    (sdk/'lib').mkdir();(sdk/'tools').mkdir()
    shutil.copy2(args.core,sdk/'lib/libcore.a');shutil.copy2(args.tool,sdk/'tools/scpp_api_generator')
    seal_package(sdk,['core']);os.environ['SCPP_API_GENERATOR']=str(sdk/'tools/scpp_api_generator')
    request_file=work/'request.json';request_file.write_text(json.dumps(request))
    manifest=concrete_types.generate(request_file,app,sdk,[sdk],'clang++',[])
    assert len(manifest['sources'])==8
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
    code+='\nstd::puts("PASS: carrier unary values, prefix/postfix snapshots, preserved failure states; success allocations=0"); }'
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
    (app/'lib').mkdir();subprocess.run(['ar','rcs',str(app/'lib/libunary.a'),*objects],check=True)
    seal_package(app,['unary']);reused=concrete_types.generate(request_file,work/'reuse',sdk,[sdk,app],'clang++',[])
    assert not reused['sources']
    print(f'PASS: sealed reuse; storage compile={storage:.4f}s caller compile={caller_time:.4f}s')
