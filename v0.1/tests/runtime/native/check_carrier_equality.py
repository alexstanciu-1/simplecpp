"""Execute metadata-derived carrier equality plans through public-only APIs."""
import argparse
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
families=['nullable','result','result_or_false','result_or_bool']
names={f+'<boolean>':'boolean_carrier_'+str(i) for i,f in enumerate(families)}
names.update({'nullable<string>':'optional_text','result_or_bool<string>':'sentinel_text',
              'result<mixed>':'result_box','nullable<dynamic>':'optional_box'})
request={'package':'application.equality','roots':list(names),'names':names}

def resolve(left,right,op,error=None):
    p=subprocess.run([str(args.tool)],input=json.dumps({'metadata':metadata,'names':names,
        'action':'resolve_operator','request':{'operation':op,'left':left,'right':right}}),text=True,capture_output=True)
    if error:
        assert p.returncode and error in p.stderr,p.stderr
        return
    assert p.returncode==0,p.stderr
    return json.loads(p.stdout)

for f in families:
    for t in ['boolean','string','mixed','dynamic']:
        for op in ['equal','not_equal','strict_identical','strict_not_identical']:
            p=resolve(f+'<'+t+'>',t,op)
            assert p['kind']=='state_comparison'
            assert p['steps'][:2]==['evaluate_left_snapshot','evaluate_right_snapshot']
    for a,b in [('boolean','int64'),('string','boolean'),('string','int64'),('mixed','dynamic')]:
        resolve(f+'<'+a+'>',f+'<'+b+'>','equal','Unsupported operator')
    resolve(f+'<boolean>',f+'<string>','strict_identical')
    resolve(f+'<boolean>',f+'<boolean>','add','Unsupported carrier payload')
print('PASS: matching scalar/boxed carrier equality, distinct strict kinds, rejected coercions and arithmetic')
headers=set();functions=[];checks=[]

def emit(left,right,op):
    plan=resolve(left,right,op);fname='compare_'+str(len(functions))
    tags={tag:i for i,tag in enumerate(sorted({s['tag'] for side in ['left','right'] for s in plan[side+'_states']}))}
    body='const auto left=original_left; ++effects; const auto right=original_right; ++effects;\n'
    for side in ['left','right']:
        code=''
        for state in plan[side+'_states']:
            branch='return '+str(tags[state['tag']])+';'
            code+=('if ('+state['test_expression'].replace('${value}',side)+') { '+branch+' }' if 'test_expression' in state else branch)
        body+='const int '+side+'_state=([&]() { '+code+' })();\n'
    body+='if (left_state!=right_state) return scpp::boolean('+plan['different_states']+');\n'
    for tag,result in plan['same_states'].items():
        body+=f'if (left_state=={tags[tag]}) return scpp::boolean({result});\n'
    for tag,calc in plan['same_state_calculations'].items():
        headers.add(calc['header']);body+=f'if (left_state=={tags[tag]}) {{ '
        for side in ['left','right']:
            body+='const auto '+side+'_error='+calc[side+'_projection']['expression'].replace('${value}',side)+';'
        body+='return '+calc['function']+'(left_error,right_error); }\n'
    calc=plan['calculation']
    if calc['kind']=='constant_after_evaluation': body+='return scpp::boolean('+calc['value']+');'
    else:
        headers.add(calc['header'])
        for side in ['left','right']:
            projection=plan[side+'_projection']
            expr=projection['expression'].replace('${value}',side) if projection['kind']!='identity' else side
            assert calc[side+'_conversion']['kind']=='identity'
            body+='const auto '+side+'_payload='+expr+';\n'
        body+='return '+calc['function']+'(left_payload,right_payload);'
    for t in [left,right]: headers.add('scpp/'+names[t]+'.hpp')
    functions.append('static scpp::boolean '+fname+'(const scpp::'+names[left]+' &original_left, const scpp::'+names[right]+' &original_right, int &effects) { '+body+' }')
    return fname

# Exhaustive boolean state matrix across the four carrier families, four comparisons.
def states(family):
    cpp='scpp::'+names[family+'<boolean>']
    result=[('payload_false',cpp+'(scpp::boolean(false))'),('payload_true',cpp+'(scpp::boolean(true))')]
    if family=='nullable':result.append(('absent',cpp+'()'))
    elif family=='result':result.append(('error',cpp+'(failure)'))
    else:
        result.append(('false',cpp+'::from_false()'))
        if family=='result_or_bool':result.append(('true',cpp+'::from_true()'))
    return result
for a in families:
    for b in families:
        for op in ['equal','not_equal','strict_identical','strict_not_identical']:
            fn=emit(a+'<boolean>',b+'<boolean>',op)
            for tag_a,expr_a in states(a):
                for tag_b,expr_b in states(b):
                    expected=tag_a==tag_b
                    if op in ['not_equal','strict_not_identical']:expected=not expected
                    checks.append(f'CHECK({fn}({expr_a},{expr_b},effects).native_value()=={str(expected).lower()});')
text_equal=emit('nullable<string>','result_or_bool<string>','equal')
text_identity=emit('nullable<string>','result_or_bool<string>','strict_identical')
cross_identity=emit('nullable<boolean>','result_or_bool<string>','strict_identical')
box_equal=emit('result<mixed>','result<mixed>','equal')
dynamic_equal=emit('nullable<dynamic>','nullable<dynamic>','equal')
print(f'PASS: {len(checks)} boolean state expectations from {len(functions)} compact plan consumers')
if not args.core:sys.exit(0)
with tempfile.TemporaryDirectory(prefix='scpp-carrier-equality-') as directory:
    work=Path(directory);sdk=work/'sdk';app=work/'app'
    for folder in ['metadata','include','public']:shutil.copytree(root/'runtime'/folder,sdk/folder)
    (sdk/'lib').mkdir();(sdk/'tools').mkdir()
    shutil.copy2(args.core,sdk/'lib/libcore.a');shutil.copy2(args.tool,sdk/'tools/scpp_api_generator')
    seal_package(sdk,['core']);os.environ['SCPP_API_GENERATOR']=str(sdk/'tools/scpp_api_generator')
    request_file=work/'request.json';request_file.write_text(json.dumps(request))
    manifest=concrete_types.generate(request_file,app,sdk,[sdk],'clang++',[])
    assert len(manifest['sources'])==8
    code='\n'.join('#include "'+h+'"' for h in sorted(headers))
    code+=r'''
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
    code+='\n'.join(functions)
    code+='\nint main() {\nconst auto failure=scpp::error_value::make(scpp::string("failed"));\nint effects=0; const int before=allocations;\n'
    code+='\n'.join(checks)
    code+=f'\nCHECK(effects=={2*len(checks)});CHECK(allocations==before);\n'
    code+=f'''
scpp::optional_text left(scpp::string("short"));
scpp::sentinel_text right(scpp::string("short"));
CHECK({text_equal}(left,right,effects)); CHECK({text_identity}(left,right,effects));
CHECK(!{cross_identity}(scpp::boolean_carrier_0(scpp::boolean(true)),right,effects));
scpp::result_box integer(scpp::mixed(scpp::int64(1))), floating(scpp::mixed(scpp::float64(1)));
CHECK({box_equal}(integer,floating,effects));
scpp::optional_box a(scpp::dynamic(scpp::int64(1))), b(scpp::dynamic(scpp::float64(1)));
CHECK({dynamic_equal}(a,b,effects));
CHECK(allocations==before);
const scpp::string long_text("A long string that exceeds inline storage and exposes the current owned snapshot and payload extraction allocation costs.");
left=long_text;right=long_text;const int long_before=allocations;
CHECK({text_equal}(left,right,effects));
std::printf("Long string equality allocations=%d\\n",allocations-long_before);
CHECK(allocations-long_before==4);
bool threw=false;
try {{ (void){box_equal}(integer,scpp::result_box(scpp::mixed(scpp::string("1"))),effects); }} catch (...) {{ threw=true; }}
CHECK(threw);
std::puts("PASS: carrier state equality/identity and boxed checked equality; short/scalar allocations=0");
}}
'''
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
    (app/'lib').mkdir();subprocess.run(['ar','rcs',str(app/'lib/libequality.a'),*objects],check=True)
    seal_package(app,['equality']);reused=concrete_types.generate(request_file,work/'reuse',sdk,[sdk,app],'clang++',[])
    assert not reused['sources']
    print(f'PASS: sealed reuse; storage compile={storage:.4f}s caller compile={caller_time:.4f}s')
