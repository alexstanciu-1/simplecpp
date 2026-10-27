"""Canonical carrier arithmetic compounds through shared ordered plans."""
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
names={f+'<'+t+'>':'compound_'+str(i)+'_'+t for i,f in enumerate(families) for t in ['int64','float64']}
request={'package':'application.carrier_compound','roots':list(names),'names':names}

ops=['add','subtract','multiply','divide','remainder']
def resolve(target,right,op,meta=metadata,error=None):
    p=subprocess.run([str(args.tool)],input=json.dumps({'metadata':meta,'names':names,'action':'resolve_compound',
        'request':{'operation':op+'_assign','target':target,'right':right}}),text=True,capture_output=True)
    if error:
        assert p.returncode and error in p.stderr,p.stderr
        return
    assert p.returncode==0,p.stderr
    return json.loads(p.stdout)
plans={}
for family in families:
    for op in ops:
        for left,right in [('int64','int64'),('float64','int64'),('float64','float64')]:
            if op=='remainder' and left!='int64':continue
            for target,rhs in [(family+'<'+left+'>',right),(family+'<'+left+'>',family+'<'+right+'>'),(left,family+'<'+right+'>')]:
                p=resolve(target,rhs,op);plans[target,rhs,op]=p
                assert p['kind']=='ordered_compound' and p['result_type']==target
                assert p['steps']==['capture_destination_and_retain_owners','snapshot_left','project_left','convert_left','evaluate_right','project_right','convert_right','calculate','convert_writeback','commit_destination','return_value_snapshot']
                assert p['failure']=='no_writeback_preserve_prior_effects'
        resolve(family+'<int64>','float64',op,error='Unsupported compound payload operands')
    for bad in ['int8','uint64','boolean','string','mixed','dynamic','nullable<int64>']:
        resolve(family+'<'+bad+'>','int64','add',error='Unsupported compound destination payload')
        resolve('int64',family+'<'+bad+'>','add',error='Unsupported compound payload operands')
changed=copy.deepcopy(metadata);changed['families']['holder']=copy.deepcopy(changed['families']['nullable'])
assert resolve('holder<int64>','result_or_bool<int64>','add',changed)['writeback_conversion']['kind']=='construct_payload'
changed=copy.deepcopy(metadata);changed['global_operators']['compounds']['add_assign']['rules'][0]['right']=['float64']
resolve('nullable<int64>','int64','add',changed,error='Unsupported compound payload operands')
changed=copy.deepcopy(metadata);changed['global_operators']['compounds']['add_assign']['operation']='numeric_equal'
resolve('nullable<int64>','int64','add',changed,error='requires a checked payload operation')
headers=set();functions=[];checks=[];failures=[]
def cpp(t):return 'scpp::'+names[t] if t in names else public_types[t]['cpp']
def payload(t):return t.split('<')[1][:-1] if '<' in t else t
def value(t,n):return cpp(t)+'(scpp::'+payload(t)+'('+str(n)+'))' if '<' in t else cpp(t)+'('+str(n)+')'
def read(t,v):return v+('.value()' if '<' in t else '')+'.native_value()'
def convert(p,v):
    if p['kind']=='identity':return v
    if 'header' in p:headers.add(p['header'])
    if 'expression' in p:return p['expression'].replace('${value}',v)
    return p['function']+'('+v+')'
for (target,right,op),p in plans.items():
    fn='update_'+str(len(functions));headers.add(p['calculation']['header'])
    fragments={
      'capture_destination_and_retain_owners':'++captures;',
      'snapshot_left':'const auto old_left=destination;',
      'project_left':'const auto left_payload='+convert(p['left_projection'],'old_left')+';',
      'convert_left':'const auto left='+convert(p['left_conversion'],'left_payload')+';',
      'evaluate_right':'++effects; if(mode) destination='+value(target,100)+'; if(mode==2) throw 42; const auto rhs=right;',
      'project_right':'const auto right_payload='+convert(p['right_projection'],'rhs')+';',
      'convert_right':'const auto converted_right='+convert(p['right_conversion'],'right_payload')+';',
      'calculate':'const auto calculated='+p['calculation']['function']+'(left,converted_right);',
      'convert_writeback':'const auto replacement='+convert(p['writeback_conversion'],'calculated')+';',
      'commit_destination':'destination=replacement;',
      'return_value_snapshot':'return replacement;',
    }
    functions.append('static '+cpp(target)+' '+fn+'('+cpp(target)+' &destination,const '+cpp(right)+' &right,int &captures,int &effects,int mode=0) { '+' '.join(fragments[s] for s in p['steps'])+' }')
    expected={'add':15,'subtract':9,'multiply':36,'divide':4,'remainder':0}[op]
    for mode in [0,1]:
        checks.append(f'{{ auto destination={value(target,12)}; int captures=0,effects=0; auto result={fn}(destination,{value(right,3)},captures,effects,{mode}); CHECK(captures==1 && effects==1 && {read(target,"destination")}=={expected} && {read(target,"result")}=={expected}); destination={value(target,8)}; CHECK({read(target,"result")}=={expected}); }}')
    failures.append(f'{{ auto destination={value(target,12)}; int captures=0,effects=0; bool caught=false; try {{ (void){fn}(destination,{value(right,3)},captures,effects,2); }} catch (...) {{ caught=true; }} CHECK(caught && captures==1 && effects==1 && {read(target,"destination")}==100); }}')
    if op in ['divide','remainder']:
        failures.append(f'{{ auto destination={value(target,12)}; int captures=0,effects=0; bool caught=false; try {{ (void){fn}(destination,{value(right,0)},captures,effects,1); }} catch (...) {{ caught=true; }} CHECK(caught && effects==1 && {read(target,"destination")}==100); }}')
    for t,side in [(target,'left'),(right,'right')]:
        if '<' not in t:continue
        family=t.split('<')[0];ct=cpp(t)
        states=[ct+'()'] if family=='nullable' else [ct+'(failure)'] if family=='result' else [ct+'::from_false()']
        if family=='result_or_bool':states.append(ct+'::from_true()')
        for initial in states:
            init=initial if side=='left' else value(target,12)
            rhs=value(right,3) if side=='left' else initial
            state=('destination.has_error() && destination.error().get_line().native_value()==7' if family=='result' else '!destination.has_value()') if side=='left' else read(target,'destination')+'==12'
            failures.append(f'{{ auto destination={init}; int captures=0,effects=0; bool caught=false; try {{ (void){fn}(destination,{rhs},captures,effects); }} catch (...) {{ caught=true; }} CHECK(caught && captures==1 && effects=={0 if side=="left" else 1} && ({state})); }}')
    if target==right:
        # RHS aliases the destination and observes its intervening mutation.
        expected={'add':112,'subtract':-88,'multiply':1200,'divide':0.12 if payload(target)=='float64' else 0,'remainder':12}[op]
        checks.append(f'{{ auto destination={value(target,12)}; int captures=0,effects=0; auto result={fn}(destination,destination,captures,effects,1); CHECK(captures==1 && effects==1 && {read(target,"result")}=={expected}); }}')
print(f'PASS: {len(plans)} compound plans, {len(checks)} successful cases, {len(failures)} failure cases; directional admission and renamed family')
if not args.core:sys.exit(0)
with tempfile.TemporaryDirectory(prefix='scpp-carrier-compound-') as directory:
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
    code+='\nstd::puts("PASS: carrier compounds, ordering, snapshots and preserved failure effects; success allocations=0"); }'
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
    (app/'lib').mkdir();subprocess.run(['ar','rcs',str(app/'lib/libcompound.a'),*objects],check=True)
    seal_package(app,['compound']);reused=concrete_types.generate(request_file,work/'reuse',sdk,[sdk,app],'clang++',[])
    assert not reused['sources']
    print(f'PASS: sealed reuse; storage compile={storage:.4f}s caller compile={caller_time:.4f}s')
