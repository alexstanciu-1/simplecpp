"""Guarded lazy logical plans, static admission and conditional runtime extraction."""
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
parser.add_argument('--prepared',action='store_true')
args=parser.parse_args();args.tool=args.tool.resolve()
metadata=concrete_types.metadata(root/'runtime')
families=['nullable','result','result_or_false','result_or_bool']
names={f+'<'+t+'>':'logical_'+str(i)+'_'+t for i,f in enumerate(families) for t in ['int64','float64','boolean']}
request={'package':'application.carrier_logical','roots':list(names),'names':names}

def resolve(left,right,op='logical_and',meta=metadata,error=None):
    envelope={'metadata':meta,'names':names,'action':'resolve_logical',
        'request':{'operation':op,'left':left,'right':right}}
    if args.prepared:
        from prepared_fixture import logical
        envelope=logical(envelope)
    p=subprocess.run([str(args.tool)],input=json.dumps(envelope),text=True,capture_output=True)
    if error:
        assert p.returncode and error in p.stderr,p.stderr
        return
    assert p.returncode==0,p.stderr
    return json.loads(p.stdout)

# All canonical plain/carrier combinations, with bool/numeric mixing rejected.
operands=list(names)+['int64','float64','boolean']
for left in operands:
    for right in operands:
        is_bool=lambda t:t=='boolean' or t.endswith('<boolean>')
        for op in ['logical_and','logical_or']:
            p=resolve(left,right,op,error='Unsupported logical operand pair' if is_bool(left)!=is_bool(right) else None)
            if p:
                assert p['kind']=='lazy_logical' and p['result_type']=='boolean'
                assert p['short_circuit']==(op=='logical_or')
                assert p['steps'].index('observe_left') < p['steps'].index('evaluate_right_once')
for bad in ['string','null','vector<int64>','nullable<string>','result<nullable<int64>>']:
    resolve('boolean',bad,error='Unsupported logical')
for box in ['mixed','dynamic']:
    for op in ['logical_and','logical_or']:
        for peer in [box,'boolean','int64','float64']:
            assert resolve(box,peer,op)['left']['projection']['kind']=='identity'
            assert resolve(peer,box,op)['right']['projection']['kind']=='identity'
        for carrier in names:
            resolve(box,carrier,op,error='Unsupported logical operand pair')
            resolve(carrier,box,op,error='Unsupported logical operand pair')
        resolve(box,'dynamic' if box=='mixed' else 'mixed',op,error='Unsupported logical operand pair')
bad=copy.deepcopy(metadata);bad['selections']['logical']['operand_groups'].append(['vector<int64>'])
resolve('boolean','boolean',meta=bad,error='requires a callable condition')
bad=copy.deepcopy(metadata);bad['selections']['logical']['operations']['logical_and']['short_circuit']='false'
resolve('boolean','boolean',meta=bad,error='requires boolean')
print('PASS: canonical logical operand matrix; static RHS rejection and metadata validation')
headers=set();functions=[];checks=[];failures=[]
for source in names:
    cpp='scpp::'+names[source];payload=source.split('<')[1][:-1]
    for op in ['logical_and','logical_or']:
        p=resolve(source,source,op);fn='operate_'+str(len(functions))
        projection=p['left']['projection'];condition=p['left']['condition']
        headers.update([projection['header'],condition['header']])
        short='true' if p['short_circuit'] else 'false'
        left=projection['expression'].replace('${value}','left')
        right=p['right']['projection']['expression'].replace('${value}','right')
        functions.append(f'static scpp::boolean {fn}(const {cpp} &left, const {cpp} &right, int &effects, bool throw_right=false) {{ '
            f'++effects; const auto left_payload={left}; const auto observed={condition["function"]}(left_payload); '
            f'if (observed.native_value()=={short}) return scpp::boolean({short}); '
            f'++effects; if (throw_right) throw 42; const auto right_payload={right}; return {condition["function"]}(right_payload); }}')
        for a in [0,1]:
            for b in [0,1]:
                expected='true' if (a and b if op=='logical_and' else a or b) else 'false'
                effects=1 if bool(a)==p['short_circuit'] else 2
                checks.append(f'{{ const {cpp} a(scpp::{payload}({a})), b(scpp::{payload}({b})); int effects=0; CHECK({fn}(a,b,effects).native_value()=={expected} && effects=={effects}); }}')
        checks.append(f'{{ const {cpp} a(scpp::{payload}({short})); int effects=0; CHECK({fn}(a,a,effects,true).native_value()=={short} && effects==1); }}')
        family=source.split('<')[0]
        states=[cpp+'()'] if family=='nullable' else [cpp+'(failure)'] if family=='result' else [cpp+'::from_false()']
        if family=='result_or_bool':states.append(cpp+'::from_true()')
        for state in states:
            failures.append(f'{{ const {cpp} absent={state}; const {cpp} skip(scpp::{payload}({short})), run(scpp::{payload}(!{short})); int effects=0; '
                f'CHECK({fn}(skip,absent,effects).native_value()=={short} && effects==1); '
                f'bool caught=false; effects=0; try {{ (void){fn}(run,absent,effects); }} catch (...) {{ caught=true; }} CHECK(caught && effects==2); '
                f'caught=false; effects=0; try {{ (void){fn}(absent,skip,effects); }} catch (...) {{ caught=true; }} CHECK(caught && effects==1); }}')
        failures.append(f'{{ const {cpp} run(scpp::{payload}(!{short})); int effects=0; bool caught=false; try {{ (void){fn}(run,run,effects,true); }} catch (int value) {{ caught=value==42; }} CHECK(caught && effects==2); }}')
# Execute representative mixed numeric, cross-carrier and plain/carrier paths too.
for left_type,right_type in [('nullable<int64>','result<float64>'),('result<float64>','nullable<int64>'),
        ('result_or_false<boolean>','result_or_bool<boolean>'),('boolean','nullable<boolean>'),
        ('result<int64>','float64'),('int64','result_or_bool<float64>')]:
    for op in ['logical_and','logical_or']:
        p=resolve(left_type,right_type,op);fn='operate_'+str(len(functions))
        cpps=[];inits=[];parts=[]
        for side,source in [('left',left_type),('right',right_type)]:
            part=p[side];projection=part['projection'];condition=part['condition']
            headers.add(condition['header'])
            if projection['kind']=='identity':
                cpp='scpp::'+source;expr=side;init=cpp+'(1)'
            else:
                headers.add(projection['header']);cpp='scpp::'+names[source]
                expr=projection['expression'].replace('${value}',side)
                init=cpp+'(scpp::'+projection['target']+'(1))'
            cpps.append(cpp);inits.append(init)
            parts.append('const auto '+side+'_payload='+expr+'; const auto '+side+'_truth='+condition['function']+'('+side+'_payload);')
        short='true' if p['short_circuit'] else 'false'
        functions.append(f'static scpp::boolean {fn}(const {cpps[0]} &left,const {cpps[1]} &right,int &effects) {{ '
            '++effects; '+parts[0]+f' if(left_truth.native_value()=={short}) return scpp::boolean({short}); '
            '++effects; '+parts[1]+' return right_truth; }')
        expected_effects=1 if p['short_circuit'] else 2
        checks.append(f'{{ int effects=0; CHECK({fn}({inits[0]},{inits[1]},effects).native_value()); CHECK(effects=={expected_effects}); }}')
print(f'PASS: {len(functions)} logical consumers, {len(checks)} success cases, {len(failures)} failure/skip groups')
if not args.core:sys.exit(0)
with tempfile.TemporaryDirectory(prefix='scpp-carrier-logical-') as directory:
    work=Path(directory);sdk=work/'sdk';app=work/'app'
    for folder in ['metadata','include','public']:shutil.copytree(root/'runtime'/folder,sdk/folder)
    (sdk/'lib').mkdir();(sdk/'tools').mkdir()
    shutil.copy2(args.core,sdk/'lib/libcore.a');shutil.copy2(args.tool,sdk/'tools/scpp_api_generator')
    seal_package(sdk,['core']);os.environ['SCPP_API_GENERATOR']=str(sdk/'tools/scpp_api_generator')
    from prepared_fixture import scalar_carrier_storage
    storage_request=scalar_carrier_storage(request) if args.prepared else request
    request_file=work/'request.json';request_file.write_text(json.dumps(storage_request))
    manifest=concrete_types.generate(request_file,app,sdk,[sdk],'clang++',[])
    assert len(manifest['sources'])==12
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
    code+='\nstd::puts("PASS: guarded logical truth tables, short circuit, effects and unavailable states; success allocations=0"); }'
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
    (app/'lib').mkdir();subprocess.run(['ar','rcs',str(app/'lib/liblogical.a'),*objects],check=True)
    seal_package(app,['logical']);reused=concrete_types.generate(request_file,work/'reuse',sdk,[sdk,app],'clang++',[])
    assert not reused['sources']
    if args.prepared:
        assert 'prepared' not in reused and 'preparation' not in reused
        bad=copy.deepcopy(storage_request);bad['prepared']['facts']=[]
        request_file.write_text(json.dumps(bad));rejected=False
        try:concrete_types.generate(request_file,work/'rejected',sdk,[sdk,app],'clang++',[])
        except subprocess.CalledProcessError as error:rejected=error.returncode==1
        assert rejected and not (work/'rejected').exists()
        print('PASS: combined prepared storage/operation execution and sealed reuse; missing source facts reject')
    print(f'PASS: sealed reuse; storage compile={storage:.4f}s caller compile={caller_time:.4f}s')
