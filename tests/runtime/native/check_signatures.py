"""Normalize prepared callable signature shape without assuming source admission."""
import argparse
import copy
import json
import os
import shutil
import tempfile
from pathlib import Path
import subprocess
import sys
root=Path(__file__).resolve().parents[3]
sys.path.insert(0,str(root/'runtime/tools'))
import concrete_types
from public_package import seal_package
parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--tool',type=Path,required=True)
parser.add_argument('--core',type=Path)
args=parser.parse_args();args.tool=args.tool.resolve()
base={'action':'resolve_signature','metadata':concrete_types.metadata(root/'runtime'),
      'names':{'vector<int64>':'signature_vector'},'leaves':{},'imports':{}}
def resolve(request,error=None,envelope=None):
    value={**base,'request':request} if envelope is None else envelope
    result=subprocess.run([str(args.tool)],input=json.dumps(value),text=True,capture_output=True)
    if error:
        assert result.returncode and error in result.stderr,result.stderr
        return
    assert result.returncode==0,result.stderr
    return json.loads(result.stdout)
request={'returns':'int64','parameters':[{'name':'value','type':'int64'}]}
normal=resolve(request)
renamed={'returns':'int64','parameters':[{'name':'other','type':'int64','passing':'value','variadic':False}]}
assert resolve(renamed)==normal
identities={normal['identity']}
for passing in ['ref','const_ref']:
    variant=copy.deepcopy(request);variant['parameters'][0]['passing']=passing
    identities.add(resolve(variant)['identity'])
assert len(identities)==3
pack={'returns':'void','parameters':[{'type':'signature_vector','variadic':True}]}
packed=resolve(pack)
ordinary=copy.deepcopy(pack);ordinary['parameters'][0]['variadic']=False
assert packed['identity']!=resolve(ordinary)['identity']
assert packed['operation_signature']==resolve(ordinary)['operation_signature']
assert packed['descriptor']['parameters'][0]['type']=='vector<int64>'
assert resolve({'returns':'void','parameters':[]})['operation_signature']['parameters']==[]
nested=resolve({'returns':'hash<string>','parameters':[{'type':'vector<result<int64>>'}]})
assert nested['descriptor']['returns']=='hash<string,string>'
assert nested['descriptor']['parameters'][0]['type']=='vector<result<int64>>'
reference={'returns':'int64','return_origin':1,'parameters':[{'type':'string'},{'type':'int64','passing':'ref'}]}
ref=resolve(reference)
assert ref['operation_signature']['return_origin']=='argument_1'
value_result=copy.deepcopy(reference);value_result.pop('return_origin')
assert resolve(value_result)['identity']!=ref['identity']
for change,error in [('missing','out of range'),('negative','parameter index'),('fraction','parameter index'),
                     ('mode','matching writable'),('ambiguous','matching writable'),('wrong','matching writable')]:
    bad=copy.deepcopy(reference)
    if change=='missing':bad['return_origin']=2
    elif change=='negative':bad['return_origin']=-1
    elif change=='fraction':bad['return_origin']=0.5
    elif change=='mode':bad['parameters'][1]['passing']='const_ref'
    elif change=='ambiguous':bad['parameters'][0]={'type':'int64','passing':'ref'}
    else:bad['returns']='string'
    resolve(bad,error)
bad=copy.deepcopy(reference);bad['return_origin']=10**100
resolve(bad,'out of range')
for parameter,error in [({'type':'void'},'cannot be void'),({'type':'int64','passing':'move_ref'},'input mode'),
                        ({'type':'int64','variadic':'yes'},'must be boolean'),
                        ({'type':'signature_vector','variadic':True,'passing':'ref'},'final and by value'),
                        ({'type':'unregistered'},'Unregistered'),({'type':'int64','default':'0'},'signature parameter')]:
    resolve({'returns':'int64','parameters':[parameter]},error)
resolve({'returns':'void','parameters':[{'type':'signature_vector','variadic':True},{'type':'int64'}]},'final and by value')
# Source provenance is checked independently from physical shape normalization.
bindings={'return':{'source':'source.int','physical':'int64','arguments':{}},
          'parameter_0':{'source':'source.int','physical':'int64','arguments':{}}}
requirements=[{'subject':slot,'contract':'signature_type_admitted'} for slot in bindings]
requirements.append({'subject':'operation','contract':'callable_signature_admitted'})
prepared={'version':'prepared-api-v1','producer':'fixture.semantic-owner','revision':'fixture-v1',
          'action':'resolve_signature','request':request,'admission':'fixture/signature','bindings':bindings,
          'requirements':requirements,'facts':[{**r,'evidence':'fixture/'+r['subject']} for r in requirements]}
source={**base,'mode':'source','request':request,'prepared':prepared}
actual=resolve(request,envelope=source)
assert actual.pop('preparation')==prepared and actual==normal
bad=copy.deepcopy(source);bad['prepared']['facts']=[f for f in bad['prepared']['facts'] if f['subject']!='operation']
resolve(request,'callable_signature_admitted',bad)
bad=copy.deepcopy(source);bad['prepared']['bindings']['parameter_0']['physical']='uint64'
resolve(request,'binding mismatch',bad)
print('PASS: canonical signature identities, modes, variadic distinction, origins, nested types and prepared admission')

# Signatures are canonical non-storage declarations, never fake C++ leaf types.
key=normal['key']
assert not key.endswith('_')
data={'package':'application.signatures','roots':[], 'leaves':{key:{'signature':request}}}
generation={**base,'action':'generate','request':data,'leaves':data['leaves'],'layouts':{}}
published=resolve(data,envelope=generation)
assert not published['units']
record=published['leaves'][key]
assert record=={'owner':'runtime.signature','signature':normal['descriptor']}
renamed_generation=copy.deepcopy(generation);renamed_generation['leaves'][key]['signature']=renamed
renamed_generation['request']['leaves']=renamed_generation['leaves']
renamed_generation['leaf_imports']=published['leaves']
assert resolve(data,envelope=renamed_generation)['leaves'][key]==record
for target in [key,'vector<'+key+'>']:
    bad=copy.deepcopy(generation);bad['request']['roots']=[target]
    resolve(data,'Signature descriptor is not storage',bad)
bad=copy.deepcopy(generation);bad['leaves'][key]['signature']['parameters'][0]['passing']='ref'
resolve(data,'Signature identity does not match',bad)
bad=copy.deepcopy(generation);bad['leaves'][key]['cpp']='int'
resolve(data,'signature leaf',bad)
prefix='signature_'+key+'_'
signature_bindings={prefix+'return':bindings['return'],prefix+'parameter_0':bindings['parameter_0']}
signature_requirements=[{'subject':slot,'contract':'signature_type_admitted'} for slot in signature_bindings]
signature_requirements.append({'subject':'operation','contract':'callable_signature_admitted'})
publication_prepared={**prepared,'action':'generate','request':data,'bindings':signature_bindings,
    'requirements':signature_requirements,'facts':[{**r,'evidence':'fixture/'+r['subject']} for r in signature_requirements]}
source_generation={**generation,'mode':'source','prepared':publication_prepared}
assert resolve(data,envelope=source_generation).pop('preparation')==publication_prepared
bad=copy.deepcopy(source_generation);bad['prepared']['facts']=[]
resolve(data,'Missing prepared semantic fact',bad)
print('PASS: canonical signature declarations, imported normalization and non-storage rejection')
if args.core:
    with tempfile.TemporaryDirectory(prefix='scpp-signature-publication-') as directory:
        work=Path(directory);sdk=work/'sdk';app=work/'app'
        for folder in ['metadata','include','public']:shutil.copytree(root/'runtime'/folder,sdk/folder)
        (sdk/'lib').mkdir();(sdk/'tools').mkdir()
        shutil.copy2(args.core,sdk/'lib/libcore.a');shutil.copy2(args.tool,sdk/'tools/scpp_api_generator')
        seal_package(sdk,['core']);os.environ['SCPP_API_GENERATOR']=str(sdk/'tools/scpp_api_generator')
        request_file=work/'request.json'
        request_file.write_text(json.dumps({'mode':'source','prepared':publication_prepared,'request':data}))
        manifest=concrete_types.generate(request_file,app,sdk,[sdk],'clang++',[])
        assert not manifest['sources'] and manifest['leaves'][key]==record
        (app/'lib').mkdir();subprocess.run(['ar','rcs',str(app/'lib/libsignatures.a')],check=True)
        seal_package(app,['signatures'])
        request_file.write_text(json.dumps(renamed_generation['request']))
        reused=concrete_types.generate(request_file,work/'reuse',sdk,[sdk,app],'clang++',[])
        assert not reused['sources'] and reused['leaves'][key]==record
        print('PASS: sealed signature-only package reuse without compilation or storage artifacts')
