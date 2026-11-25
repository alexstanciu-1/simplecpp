"""Selected registry overloads resolve to shared physical API recipes."""
import argparse
import copy
import json
from pathlib import Path
import subprocess
import sys
root=Path(__file__).resolve().parents[3]
sys.path.insert(0,str(root/'runtime/tools'))
import concrete_types
parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--tool',type=Path,required=True)
args=parser.parse_args()
metadata=concrete_types.metadata(root/'runtime')
def run(envelope,error=None):
    output=subprocess.run([str(args.tool)],input=json.dumps(envelope),capture_output=True,text=True)
    if error:
        assert output.returncode and error in output.stderr,output.stderr
        return
    assert output.returncode==0,output.stderr
    return json.loads(output.stdout)
# Compare the physical binding inventory with the existing source registrations.
core=json.loads((root/'runtime/metadata/modules/core.json').read_text())
def collect(value):
    if isinstance(value,dict):
        if 'id' in value and 'parameters' in value:yield value['id']
        for child in value.values():yield from collect(child)
    elif isinstance(value,list):
        for child in value:yield from collect(child)
ids={name for name in collect(core) if any(name.startswith('core.'+prefix+'_') for prefix in ['collection','sequence','keyed'])}
assert len(ids)==12 and ids==set(metadata['api_bindings'])
resolved={}
for identity in sorted(ids):
    family=identity.rsplit('.',1)[1];mapping='_map.' in identity
    payload='int64' if family in ['vector','hash'] else family
    returns='boolean' if not mapping or family in ['vector','hash'] else family
    signature={'returns':returns,'parameters':[{'type':payload}]}
    signature_id=run({'action':'resolve_signature','metadata':metadata,'request':signature,'leaves':{},'names':{}})['key']
    leaves={signature_id:{'signature':signature}}
    callback='callable<'+signature_id+'>'
    values={'T':payload,'R':returns,'K':'string','Callback':callback}
    types={name:values[name] for name in metadata['api_bindings'][identity]['parameters']}
    request={'binding':identity,'types':types}
    envelope={'action':'resolve_api_binding','metadata':metadata,'request':request,'leaves':leaves,'names':{}}
    result=run(envelope);resolved[identity]=result['api']
    assert result['binding']==identity and result['types']==types
    bindings={name:{'source':'source.'+type,'physical':type,'arguments':{'Signature':'source.'+signature_id} if name=='Callback' else {}} for name,type in types.items()}
    dependencies={'signature':{'source':'source.'+signature_id,'physical':signature_id,'arguments':{}}}
    requirements=[{'subject':'operation','contract':'selected_overload_and_exact_signature'}]
    prepared={'version':'prepared-api-v1','producer':'fixture.semantic-owner','revision':'fixture-v1','action':'resolve_api_binding',
              'request':copy.deepcopy(request),'admission':'fixture/collection-binding','bindings':bindings,'dependencies':dependencies,
              'requirements':requirements,'facts':[{**r,'evidence':'fixture/admitted'} for r in requirements]}
    source={**envelope,'mode':'source','prepared':prepared}
    assert run(source)['api']==result['api']
    bad=copy.deepcopy(source);bad['prepared']['facts']=[];run(bad,'selected_overload_and_exact_signature')
    bad=copy.deepcopy(envelope);bad['request']['types']['Extra']='int64';run(bad,'Unexpected API binding parameter')
    bad=copy.deepcopy(envelope);del bad['request']['types']['Callback'];run(bad,'Callback')
    bad=copy.deepcopy(envelope);bad['metadata']['api_bindings'][identity]['parameters'].append('Callback');run(bad,'Duplicate API binding parameter')
    bad=copy.deepcopy(envelope);bad['metadata']['api_bindings'][identity]['api']='vector<int64>';run(bad,'must select a function family')
for op in ['map','filter']:
    assert resolved['core.sequence_'+op+'.vector']==resolved['core.collection_'+op+'.vector']
    assert resolved['core.keyed_'+op+'.hash']==resolved['core.collection_'+op+'.hash']
print('PASS: all twelve registry overloads, shared physical identities, prepared admission and malformed bindings')
