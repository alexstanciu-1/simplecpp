"""Prepared typed paths compose private borrows without intermediate container copies."""
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
from concurrent.futures import ThreadPoolExecutor
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
names={'vector<int64>':'path_row','vector<vector<int64>>':'path_matrix','hash<vector<int64>,string>':'path_rows','hash<int64,string>':'path_table','vector<hash<int64,string>>':'path_tables'}
paths={name:{'root':container,'depth':depth,'operation':operation} for name,container,depth,operation in [
 ('matrix_read','vector<vector<int64>>',2,'existing_read'),('matrix_store','vector<vector<int64>>',2,'existing_store'),
 ('rows_read','hash<vector<int64>,string>',2,'existing_read'),('rows_store','hash<vector<int64>,string>',2,'existing_store'),
 ('row_read','vector<vector<int64>>',1,'existing_read'),
 ('matrix_ready','vector<vector<int64>>',1,'prefix_ready'),('rows_ready','hash<vector<int64>,string>',1,'prefix_ready'),
 ('rows_erase','hash<vector<int64>,string>',1,'existing_erase'),
 ('tables_erase','vector<hash<int64,string>>',2,'existing_erase')]}
names.update({'nullable<int64>':'path_optional','vector<nullable<int64>>':'path_optionals','vector<mixed>':'vector_mixed',
              'result<mixed>':'path_result','vector<result<mixed>>':'path_results'})
for name,container,depth,observation in [
 ('matrix_isset','vector<vector<int64>>',2,'isset'),('row_empty','vector<vector<int64>>',1,'empty'),
 ('optional_isset','vector<nullable<int64>>',1,'isset'),('result_isset','vector<result<mixed>>',1,'isset'),
 ('box_isset','vector<mixed>',1,'isset'),('box_empty','vector<mixed>',1,'empty')]:
    paths[name]={'root':container,'depth':depth,'operation':'observe','observation':observation}
names['vector<dynamic>']='path_dynamic_vector'
for name,container in [('mixed','vector<mixed>'),('dynamic','vector<dynamic>')]:
    for suffix,operation in [('read','existing_read'),('store','existing_store')]:
        paths[name+'_'+suffix]={'root':container,'depth':3,'operation':operation}
for name,container in [('mixed','vector<mixed>'),('dynamic','vector<dynamic>')]:
    paths[name+'_ready']={'root':container,'depth':2,'operation':'prefix_ready'}
    paths[name+'_erase']={'root':container,'depth':3,'operation':'existing_erase'}
    paths[name+'_isset']={'root':container,'depth':3,'operation':'observe','observation':'isset'}
paths['mixed_empty']={'root':'vector<mixed>','depth':3,'operation':'observe','observation':'empty'}
paths['mixed_remove_ready']={'root':'vector<mixed>','depth':2,'operation':'prefix_ready','purpose':'remove'}
paths['box_ready']={'root':'mixed','depth':0,'operation':'prefix_ready'}
paths['box_remove_ready']={'root':'mixed','depth':0,'operation':'prefix_ready','purpose':'remove'}
data={'package':'application.paths','roots':[],'paths':paths,'apis':list(paths),'names':names}
base={'metadata':metadata,'request':data,'leaves':{},'names':names,'imports':{},'layouts':{}}
def plan(request,error=None):
    out=subprocess.run([str(args.tool)],input=json.dumps(request),text=True,capture_output=True)
    if error:
        assert out.returncode and error in out.stderr,out.stderr
        return
    assert out.returncode==0,out.stderr
    return json.loads(out.stdout)
physical=plan(base)
assert set(paths).issubset(physical['apis']), list(physical['apis'])
assert len({u['identity'] for u in physical['units']})==len(physical['units'])
for api in (physical['apis'][name] for name in paths):
    assert api['operation']['returns'] in ['void','boolean','int64','vector<int64>','mixed','dynamic']
    assert api['operation']['body']
for depth in [0,129,1.5]:
    bad=copy.deepcopy(base);bad['request']['paths']['matrix_read']['depth']=depth
    plan(bad,'depth')
bad=copy.deepcopy(base);bad['request']['paths']['matrix_read']['depth']=3
plan(bad,'Expected family application')
bad=copy.deepcopy(base);bad['request']['paths']['matrix_read']['operation']='bootstrap'
plan(bad,'Unsupported access path operation')
# Depth is composed, not dispatched through a two-level special case.
three=copy.deepcopy(base);three['request']['paths']={'cube_read':{'root':'vector<vector<vector<int64>>>','depth':3,'operation':'existing_read'}};three['request']['apis']=['cube_read']
assert 'path_value_1' in plan(three)['apis']['cube_read']['operation']['body']
arguments={'vector<int64>':{'T':'int64'},'vector<vector<int64>>':{'T':'vector<int64>'},
           'hash<vector<int64>,string>':{'V':'vector<int64>','K':'string'},
           'hash<int64,string>':{'V':'int64','K':'string'},'vector<hash<int64,string>>':{'T':'hash<int64,string>'},
           'nullable<int64>':{'T':'int64'},'vector<nullable<int64>>':{'T':'nullable<int64>'},
           'result<mixed>':{'T':'mixed'},'vector<result<mixed>>':{'T':'result<mixed>'},'vector<mixed>':{'T':'mixed'},'vector<dynamic>':{'T':'dynamic'}}
def binding(type):
    return {'source':'source.'+type,'physical':type,'arguments':{k:'source.'+v for k,v in arguments.get(type,{}).items()}}
bindings={}
for name,path in paths.items():
    prefix='path_'+name+'_';bindings[prefix+'root']=binding(path['root'])
    receiver=path['root'];keys=[];elements=[]
    for _ in range(path['depth']):
        if receiver in ['mixed','dynamic']:
            keys.append('mixed');elements.append(receiver)
        else:
            params=arguments[receiver]
            keys.append(params.get('K','uint64'));receiver=params.get('T',params.get('V'));elements.append(receiver)
    bindings[prefix+'value']=binding(elements[-1] if elements else path['root'])
    for i,(key,element) in enumerate(zip(keys,elements)):
        bindings[prefix+'key_'+str(i)]=binding(key);bindings[prefix+'element_'+str(i)]=binding(element)
requirements=[{'subject':slot,'contract':'application_formed'} for slot in bindings]+[{'subject':'operation','contract':'existing_path_admitted'}]
prepared={'version':'prepared-api-v1','producer':'fixture.semantic-owner','revision':'fixture-v1','action':'generate',
 'request':copy.deepcopy(data),'admission':'fixture/typed-path','bindings':bindings,'requirements':requirements,
 'facts':[{**r,'evidence':'fixture/'+r['subject']} for r in requirements]}
source={**base,'action':'generate','mode':'source','prepared':prepared}
actual=plan(source);assert actual.pop('preparation')==prepared and actual==physical
for imported in [{},physical['apis']]:
    bad=copy.deepcopy(source);bad['api_imports']=imported;bad['prepared']['facts']=[f for f in bad['prepared']['facts'] if f['subject']!='operation']
    plan(bad,'operation/existing_path_admitted')
bad=copy.deepcopy(source);del bad['prepared']['bindings']['path_rows_store_key_0']
plan(bad,'path_rows_store_key_0')
bad=copy.deepcopy(base);bad['api_imports']=physical['apis'];bad['request']['paths']['matrix_store']['depth']=1
plan(bad,'Conflicting API ownership')
bad=copy.deepcopy(base);bad['metadata']['families']['vector']['access']['read']+=' /*'+('x'*200000)+'*/'
plan(bad,'below 200KB')
bad=copy.deepcopy(base);bad['request']['paths']['matrix_store']['operation']='existing_erase'
plan(bad,'erase') # Vector final-element removal has no protocol.
bad=copy.deepcopy(base);bad['request']['paths']['matrix_read']['operation']='prefix_ready'
plan(bad,'Expected family application') # A scalar is not a ready keyed receiver.
bad=copy.deepcopy(base);bad['request']['paths']['matrix_isset']['observation']='empty'
plan(bad,'Unsupported value observation')
bad=copy.deepcopy(base);bad['request']['paths']['optional_isset']['observation']='empty'
plan(bad,'Unsupported value observation')
bad=copy.deepcopy(base);bad['request']['paths']['mixed_read']['purpose']='remove'
plan(bad,'Prefix purpose')
bad=copy.deepcopy(base);bad['metadata']['families']['mixed']['access']['element_storage']='pointer'
plan(bad,'Invalid access element storage')
print('PASS: generic typed path composition, bounds on depth, prepared slot/fact checks')
if not args.core:sys.exit(0)
with tempfile.TemporaryDirectory(prefix='scpp-typed-paths-') as directory:
    work=Path(directory);sdk=work/'sdk';app=work/'app'
    for folder in ['metadata','include','public']:shutil.copytree(root/'runtime'/folder,sdk/folder)
    (sdk/'lib').mkdir();(sdk/'tools').mkdir()
    shutil.copy2(args.core,sdk/'lib/libcore.a');shutil.copy2(args.tool,sdk/'tools/scpp_api_generator')
    seal_package(sdk,['core']);os.environ['SCPP_API_GENERATOR']=str(sdk/'tools/scpp_api_generator')
    request=work/'request.json';wrapper={'mode':'source','prepared':prepared,'request':data};request.write_text(json.dumps(wrapper))
    manifest=concrete_types.generate(request,app,sdk,[sdk],'clang++',[])
    caller='\n'.join('#include "'+manifest['apis'][name]['header']+'"' for name in paths)+'\n'
    caller+='\n'.join('#define '+name+' scpp::'+manifest['apis'][name]['operation']['name'] for name in paths)+'\n'
    caller+=(root/'tests/runtime/native/public_typed_access_paths.cpp').read_text();(work/'caller.cpp').write_text(caller)
    flags=['-std=c++23','-O1']
    if args.sanitize:flags+=['-fsanitize=address,undefined','-fno-omit-frame-pointer']
    public=['clang++',*flags,'-I'+str(app/'public'),'-I'+str(sdk/'public')]
    private=[*public,'-I'+str(app/'include'),'-I'+str(sdk/'include')]
    def compile_source(item):
        i,path=item;obj=work/f'{i}.o';subprocess.run([*private,'-c',str(app/path),'-o',str(obj)],check=True);return str(obj)
    start=time.perf_counter()
    with ThreadPoolExecutor(max_workers=8) as pool:objects=list(pool.map(compile_source,enumerate(manifest['sources'])))
    storage=time.perf_counter()-start;start=time.perf_counter()
    subprocess.run([*public,'-I'+str(root/'tests/runtime/native'),'-c',str(work/'caller.cpp'),'-o',str(work/'caller.o')],check=True)
    consumer=time.perf_counter()-start
    subprocess.run(['clang++',*flags,str(work/'caller.o'),*objects,str(args.core.resolve()),'-pthread','-o',str(work/'proof')],check=True)
    subprocess.run([str(work/'proof')],check=True)
    for name in paths:
        subprocess.run([*public,'-I'+str(root/'tests/runtime/native'),'-fsyntax-only','-x','c++','-'],
          input='#include "'+manifest['apis'][name]['header']+'"\n#include "public_api_guard.hpp"\n',text=True,check=True)
    (app/'lib').mkdir();subprocess.run(['ar','rcs',str(app/'lib/libpaths.a'),*objects],check=True);seal_package(app,['paths'])
    reused=concrete_types.generate(request,work/'reuse',sdk,[sdk,app],'clang++',[]);assert not reused['sources']
    bad=copy.deepcopy(wrapper);bad['prepared']['facts']=[f for f in bad['prepared']['facts'] if f['subject']!='operation'];request.write_text(json.dumps(bad))
    rejected=False
    try:concrete_types.generate(request,work/'rejected',sdk,[sdk,app],'clang++',[])
    except subprocess.CalledProcessError as error:rejected=error.returncode==1
    assert rejected and not (work/'rejected').exists()
    # A new path demand reuses published inner/outer storage and emits one operation.
    alias='matrix_store_again';new_data=copy.deepcopy(data)
    new_data['paths']={alias:paths['matrix_store']};new_data['apis']=[alias]
    new_prepared=copy.deepcopy(prepared);new_prepared['request']=copy.deepcopy(new_data)
    new_prepared['bindings']={slot.replace('path_matrix_store_','path_'+alias+'_'):value
        for slot,value in bindings.items() if slot.startswith('path_matrix_store_')}
    new_prepared['requirements']=[{'subject':slot,'contract':'application_formed'} for slot in new_prepared['bindings']]+[{'subject':'operation','contract':'existing_path_admitted'}]
    new_prepared['facts']=[{**r,'evidence':'fixture/'+r['subject']} for r in new_prepared['requirements']]
    request.write_text(json.dumps({'mode':'source','prepared':new_prepared,'request':new_data}))
    extra_output=work/'extra';extra=concrete_types.generate(request,extra_output,sdk,[sdk,app],'clang++',[])
    assert len(extra['sources'])==1
    extra_object=work/'extra.o';subprocess.run(['clang++',*flags,'-I'+str(extra_output/'public'),*private[len(['clang++',*flags]):],
        '-c',str(extra_output/extra['sources'][0]),'-o',str(extra_object)],check=True)
    extra_caller=work/'extra_caller.cpp';extra_caller.write_text('#include "'+extra['apis'][alias]['header']+'"\n#include "scpp/access/matrix_read.hpp"\n#include "public_api_guard.hpp"\n'
        'int main() { scpp::path_row row;row.append(scpp::int64(1));scpp::path_matrix matrix;matrix.append(row);'
        'scpp::'+extra['apis'][alias]['operation']['name']+'(matrix,scpp::uint64(0),scpp::uint64(0),scpp::int64(41));'
        'return scpp::path_matrix_read(matrix,scpp::uint64(0),scpp::uint64(0))==scpp::int64(41) ? 0 : 1; }')
    subprocess.run(['clang++',*flags,'-I'+str(extra_output/'public'),*public[len(['clang++',*flags]):],'-I'+str(root/'tests/runtime/native'),
        str(extra_caller),str(extra_object),str(app/'lib/libpaths.a'),str(args.core.resolve()),'-pthread','-o',str(work/'extra_proof')],check=True)
    subprocess.run([str(work/'extra_proof')],check=True)
    print(f'PASS: independent headers and sealed path reuse; {len(objects)} units at 8 jobs={storage:.4f}s public caller={consumer:.4f}s')
