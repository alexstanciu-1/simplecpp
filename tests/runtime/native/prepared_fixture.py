"""Fixture-only compiler attestations; not a source semantic validator."""
import copy

def request(envelope, slots, contract="operation_admission"):
    data=copy.deepcopy(envelope)
    bindings={slot:{'source':'fixture.source.'+envelope['request'][slot],
                    'physical':envelope['request'][slot], 'arguments':{}}
              for slot in slots}
    for slot,binding in bindings.items():
        physical=envelope['request'][slot]
        if '<' in physical and physical.endswith('>'):
            binding['arguments']={'T':'fixture.source.'+physical.split('<',1)[1][:-1]}
    requirements=[{'subject':slot,'contract':contract}
                  for slot in slots for contract in ['application_formed','boundary_admitted']]
    requirements.append({'subject':'operation','contract':contract})
    data['mode']='source'
    data['prepared']={'version':'prepared-api-v1','producer':'fixture.semantic-owner',
        'revision':'fixture-v1','action':envelope['action'],'request':copy.deepcopy(envelope['request']),
        'admission':'fixture/approved-request','bindings':bindings,'requirements':requirements,
        'facts':[{**requirement,'evidence':'fixture/'+requirement['subject']+'/'+requirement['contract']}
                 for requirement in requirements]}
    return data

def logical(envelope):
    return request(envelope, ['left', 'right'], 'logical_admission')


def scalar_carrier_storage(data):
    """Explicit fixture declarations for the four canonical scalar carrier families."""
    bindings={};dependencies={}
    for index,physical in enumerate(data['roots']):
        family,payload=physical[:-1].split('<',1)
        assert family in ['nullable','result','result_or_false','result_or_bool']
        assert payload in ['boolean','int64','float64']
        bindings['root_'+str(index)]={'source':'fixture.source.'+physical,'physical':physical,
                                     'arguments':{'T':'fixture.source.'+payload}}
        dependencies[payload]={'source':'fixture.source.'+payload,'physical':payload,'arguments':{}}
    requirements=[{'subject':slot,'contract':'application_formed'} for slot in bindings]
    requirements += [{'subject':'dependency:'+payload,'contract':'copyable_value'} for payload in dependencies]
    prepared={'version':'prepared-api-v1','producer':'fixture.semantic-owner','revision':'fixture-v1',
        'action':'generate','request':copy.deepcopy(data),'admission':'fixture/scalar-carrier-storage',
        'bindings':bindings,'dependencies':dependencies,'requirements':requirements,
        'facts':[{**r,'evidence':'fixture/'+r['subject']+'/'+r['contract']} for r in requirements]}
    return {'mode':'source','prepared':prepared,'request':data}
