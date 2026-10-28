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
