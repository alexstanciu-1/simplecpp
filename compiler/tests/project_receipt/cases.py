import copy,json

def build():
    key=json.dumps(['source','p',['r.phs','','R'],[]],separators=(',',':'))
    names=['default_construct','copy_construct','move_construct','copy_assign','move_assign','destroy']
    encode=lambda s: ''.join(chr(b) if (48<=b<=57 or 65<=b<=90 or 97<=b<=122) else '__' if b==95 else '_x%02X_'%b for b in s.encode())
    operations={};states={}
    for role in names:
        unavailable=role in ['move_construct','move_assign'];states[role]='unsupported' if unavailable else 'available'
        if unavailable:operations[role]=None;continue
        binary=role in ['copy_construct','copy_assign'];creates=role in ['default_construct','copy_construct']
        semantics=dict(destination_before='uninitialized_aligned' if creates else 'live',destination_after='dead' if role=='destroy' else 'live_owned',source_access='const_live' if binary else 'none',source_after='live_preserved' if binary else 'none',aliasing='disjoint' if creates else 'self_assignment_or_disjoint' if binary else 'exclusive_destination',payload_escape='call_scoped',failure='terminate',unwind='none',resources='selected_field_contracts')
        signature='ccc:void(ptr,ptr)' if binary else 'ccc:void(ptr)'
        symbol='scpp_source_'+'_X_'.join(map(encode,['inline_source_payload_v1',key,role,signature]))
        operations[role]=dict(symbol=symbol,role=role,abi=dict(calling_convention='ccc',return_type='void',parameters=[dict(type='ptr',extension='') for _ in range(2 if binary else 1)],return_extension=''),semantics=semantics)
    rows={v['symbol']:v for v in operations.values() if v is not None}
    source=dict(profile='inline_source_payload_v1',key=key,project='p',size=1,alignment=1,target=dict(triple='t',data_layout='e'),states=states,operations=operations)
    variants=['runtime.ll','runtime.bc','runtime.lto.bc','runtime.thin.bc']
    base=dict(schema_version=1,contract=dict(project='p',sources={key:source}),required_imports={v:copy.deepcopy(rows) for v in variants})
    out=[]
    def add(data,accept=True,**kw):out.append(dict(receipt=json.dumps(data,separators=(',',':')),accept=accept,empty=False,changed=False,required=sorted(rows),**kw))
    add(base)
    def change(path,value):
        d=copy.deepcopy(base);at=d
        for k in path[:-1]:at=at[k]
        at[path[-1]]=value;add(d,False)
    root=['contract','sources',key]
    for path,value in [(['schema_version'],2),(['schema_version'],'1'),(['contract','project'],'wrong'),(['contract','sources'],[]),(['contract','sources'],[source]),(['required_imports'],[]),(['required_imports'],{})]:change(path,value)
    for field,value in [('profile','wrong'),('key','wrong'),('project','wrong'),('size',2),('size','1'),('alignment',2),('target',dict(triple='x',data_layout='e')),('states',[]),('operations',[])]:change(root+[field],value)
    for name in names:
        change(root+['states',name],'forbidden')
        if operations[name] is None:change(root+['operations',name],{})
        else:
            rowpath=root+['operations',name]
            for field,value in [('symbol','wrong'),('role','wrong'),('abi',{}),('semantics',{}),('extra',True)]:change(rowpath+[field],value)
            for field in operations[name]['semantics']:change(rowpath+['semantics',field],'wrong')
            for field,value in [('calling_convention','fastcc'),('return_type','i32'),('return_extension','signext'),('parameters',[])]:change(rowpath+['abi',field],value)
            change(rowpath+['abi','parameters',0,'type'],'i32')
            change(rowpath+['abi','parameters',0,'extension'],'signext')
    first=next(iter(rows))
    for v in variants:
        change(['required_imports',v,'unknown'],next(iter(rows.values())))
        change(['required_imports',v,first,'semantics','failure'],'throw')
        change(['required_imports',v],[next(iter(rows.values()))])
    d=copy.deepcopy(base);d['required_imports']={v:[] for v in variants};add(d);out[-1]['required']=[]
    d=copy.deepcopy(base);d['required_imports']={v:{} for v in variants};d['required_imports']['runtime.bc']={first:rows[first]};add(d);out[-1]['required']=[first]
    d=copy.deepcopy(base);d['contract']['sources']=[];d['required_imports']={v:[] for v in variants};add(d);out[-1].update(empty=True,required=[])
    add(base);out[-1].update(changed=True,accept=False)
    # Object order is not semantic; parameter list order still is.
    d=copy.deepcopy(base);d['required_imports']=dict(reversed(list(d['required_imports'].items())));d['contract']['sources'][key]['states']=dict(reversed(list(states.items())));add(d)
    return out
