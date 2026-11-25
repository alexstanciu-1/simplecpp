"""Whole-callable publication: identity, roles, payload crossings and effects."""
import copy

def operation(id='op',name='run',kind='free_function',result='void',params=()):
    hidden=result in ('object','other','owner')
    positions=[dict(type='ptr',attributes='')] if hidden else []
    parameters=[]
    for type_id,passing in params:
        offset=len(positions);span=passing=='byte_span'
        p=dict(type=type_id,passing=passing,ownership='value' if passing=='direct' else 'borrowed',borrow_scope='call',abi_indices=[offset,offset+1] if span else [offset])
        positions.append(dict(type='i32' if passing=='direct' else 'ptr',attributes=''))
        if span:p.update(length_signed=False,length_abi_type='i64');positions.append(dict(type='i64',attributes=''))
        parameters.append(p)
    row=dict(id=id,symbol='bridge_'+id,kind=kind,expose_as=dict(name=name,namespace=''),calling_convention='ccc',error_policy='terminate',exception_boundary='caught_in_bridge',parameters=parameters,result=dict(type=result,ownership='owned' if hidden else 'value',passing='caller_storage' if hidden else 'direct'),abi=dict(parameters=positions,return_type='i32' if result=='word' else 'void',return_attributes=''))
    if hidden:row['result']['abi_index']=0;row.update(storage_precondition='aligned_uninitialized_storage',storage_after='live_owned_object')
    return row

def build():
    cases=[]
    def add(rows,want,bindings=None,payloads=()):
        f=dict(rows=copy.deepcopy(rows),want=want,bindings=copy.deepcopy(bindings or {}),payloads=list(payloads));cases.append(f);return f
    # Summary: id/name/ns/count/result passing/language/default/conversion/effect
    base=add([operation()], 'op/run//0/0/-1/0/-1/0;')
    add([], '')
    hidden=operation('hidden','unused');del hidden['expose_as'];add([hidden], '')
    echo=operation('echo','print_it',params=[('object','const_address')]);echo['language_binding']='echo';add([echo],'echo/print_it//1/0/1/0/-1/0;')
    literal=operation('literal','make',kind='construct_from_bytes',result='object',params=[('span','byte_span')]);literal.update(language_binding='byte_literal',default_literal=True);add([literal],'literal/make//1/1/0/1/-1/0;')
    conversion=operation('convert','convert_it',result='object',params=[('word','direct')]);conversion['conversion_purpose']='explicit_cast';add([conversion],'convert/convert_it//1/1/-1/0/1/0;')
    release=operation('release','dispose',params=[('owner','mutable_address')]);release['allocation_effect']=dict(kind='release',owner=0);add([release],'release/dispose//1/0/-1/0/-1/2;')
    acquire=operation('acquire','make_owner',kind='construct',result='owner');add([acquire],'acquire/make_owner//0/1/-1/0/-1/0;')
    add([hidden],'hidden/internal/private/0/0/-1/0/-1/0;',{'hidden':dict(name='internal',namespace='private')})
    add([operation()],'op/internal/private/0/0/-1/0/-1/0;',{'op':dict(name='internal',namespace='private')})
    for key,value in [('id',''),('symbol','9bad'),('kind','destroy'),('calling_convention','fastcc'),('error_policy','throw'),('exception_boundary','uncaught')]:
        row=operation();row[key]=value;add([row],'error')
    add([operation(),operation()],'error')
    other=operation('other','different');other['symbol']='bridge_op';add([operation(),other],'error')
    add([operation(),operation('other','run')],'error')
    add([hidden,copy.deepcopy(hidden)],'error')
    bad=operation();bad['abi']['parameters'].append(dict(type='ptr',attributes=''));add([bad],'error')
    bad=copy.deepcopy(release);del bad['allocation_effect'];add([bad],'error')
    add([operation()],'error',{'missing':dict(name='unused',namespace='')})
    add([operation()],'error',{'op':dict(name='bad',namespace='',provided=True)})
    second=copy.deepcopy(echo);second.update(id='echo2',symbol='bridge_echo2',expose_as=dict(name='print_other',namespace=''));add([echo,second],'error')
    second=copy.deepcopy(conversion);second.update(id='convert2',symbol='bridge_convert2',expose_as=dict(name='convert_other',namespace=''));add([conversion,second],'error')
    second=copy.deepcopy(conversion);second.update(id='convert2',symbol='bridge_convert2',expose_as=dict(name='convert_text',namespace=''),conversion_purpose='text');add([conversion,second],'convert/convert_it//1/1/-1/0/1/0;convert2/convert_text//1/1/-1/0/3/0;')
    second=copy.deepcopy(literal);second.update(id='literal2',symbol='bridge_literal2',expose_as=dict(name='make_other',namespace=''));second['result']['type']='other';add([literal,second],'error')
    second['default_literal']=False;add([literal,second],'literal/make//1/1/0/1/-1/0;literal2/make_other//1/1/0/0/-1/0;')
    payload=operation('payload','receive',params=[('record','const_address')]);add([payload],'error',payloads=['record'])
    payload['parameters'][0]['payload_crossing']='copy_in';add([copy.deepcopy(payload)],'payload/receive//1/0/-1/0/-1/0;',payloads=['record'])
    payload['parameters'][0]['passing']='mutable_address';add([payload],'error',payloads=['record'])
    output=operation('payloadout','produce',result='object');add([copy.deepcopy(output)],'error',payloads=['object']);output['result']['payload_crossing']='copy_out';add([output],'payloadout/produce//0/1/-1/0/-1/0;',payloads=['object'])
    return cases
