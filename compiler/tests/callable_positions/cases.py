"""Physical result/parameter boundary schemas and independently expected normalized slots."""
import copy

def build():
    cases=[]
    ptr={'type':'ptr','attributes':''}
    def add(mode,row,positions,offset,want):
        f=dict(mode=mode,row=row,positions=positions,offset=offset,want=want);cases.append(f);return f
    def bad(base,path,value):
        f=copy.deepcopy(base);node=f
        for key in path[:-1]:node=node[key]
        node[path[-1]]=value;f['want']='error';cases.append(f);return f
    word_result=add(0,dict(kind='free_function',result=dict(type='word',passing='direct',ownership='value'),abi=dict(return_type='i32',return_attributes='signext')),[],0,'Word:0:1:integer:32:1:0')
    void_result=add(0,dict(kind='free_function',result=dict(type='void',passing='direct',ownership='value'),abi=dict(return_type='void',return_attributes='')),[],0,'Void:0:0:none:0')
    for kind in ['construct','construct_from_bytes','free_function']:
        hidden=add(0,dict(kind=kind,result=dict(type='object',passing='caller_storage',ownership='owned',abi_index=0),abi=dict(return_type='void',return_attributes=''),storage_precondition='aligned_uninitialized_storage',storage_after='live_owned_object'),[ptr.copy()],0,'Object:1:2:none:1')
    for path,value in [(('row','result','type'),'missing'),(('row','result','type'),'record'),(('row','result','passing'),'other'),(('row','result','ownership'),'owned'),(('row','abi','return_type'),'i64'),(('row','abi','return_attributes'),'noalias')]:bad(word_result,path,value)
    bad(void_result,('row','abi','return_type'),'i32');bad(void_result,('row','abi','return_attributes'),'noundef')
    for path,value in [(('row','kind'),'const_method'),(('row','result','type'),'word'),(('row','result','ownership'),'value'),(('row','result','abi_index'),1),(('row','result','abi_index'),0.0),(('row','storage_precondition'),'live_object'),(('row','storage_after'),'uninitialized'),(('row','abi','return_type'),'i32'),(('row','abi','return_attributes'),'noundef'),(('positions',),[])]:bad(hidden,path,value)
    examples=[]
    for offset in [0,2]:
        for type_id,name,passing in [('word','Word','direct'),('word','Word','const_address'),('object','Object','const_address'),('object','Object','mutable_address'),('record','Row','const_address'),('record','Row','mutable_address'),('span','Span','byte_span')]:
            row=dict(type=type_id,passing=passing,ownership='value' if passing=='direct' else 'borrowed',abi_indices=[offset],borrow_scope='call')
            positions=[ptr.copy() for _ in range(offset)]
            if passing=='direct':positions.append({'type':'i32','attributes':'signext'});mode=0;abi='integer:32:1'
            elif passing=='byte_span':
                row.update(abi_indices=[offset,offset+1],length_signed=False,length_abi_type='i64');positions += [ptr.copy(),{'type':'i64','attributes':'noundef'}];mode=3;abi='span:64'
            else:positions.append(ptr.copy());mode=2 if passing=='mutable_address' else 1;abi='mutable' if mode==2 else 'const'
            examples.append(add(1,row,positions,offset,f'{name}:{mode}:{abi}:{offset+(2 if passing=="byte_span" else 1)}'))
    for base in examples[:7]:
        for path,value in [(('row','type'),'missing'),(('row','type'),'hidden'),(('row','passing'),'other'),(('row','ownership'),'invalid'),(('row','abi_indices'),[]),(('row','abi_indices'),[9]),(('positions',),[]),(('offset',),-1)]:bad(base,path,value)
    span=examples[6]
    for path,value in [(('row','type'),'object'),(('row','length_signed'),True),(('row','length_signed'),0),(('row','borrow_scope'),'escape'),(('row','length_abi_type'),'i32'),(('positions',1,'attributes'),'signext'),(('positions',1,'attributes'),' noundef '),(('positions',0,'type'),'i64')]:bad(span,path,value)
    for spelling in ['i0','i01','i-1','i','u64','i1x','i9223372036854775808']:
        f=bad(span,('positions',1,'type'),spelling);f['row']['length_abi_type']=spelling
        if spelling=='i9223372036854775808':f['retained_check']=False
    for spelling in ['i1','i128','i9223372036854775807']:
        f=copy.deepcopy(span);f['positions'][1]['type']=spelling;f['row']['length_abi_type']=spelling;f['want']='Span:3:span:'+spelling[1:]+':2';cases.append(f)
    bad(examples[1],('row','passing'),'mutable_address')
    bad(examples[2],('row','borrow_scope'),'escape')
    return cases
