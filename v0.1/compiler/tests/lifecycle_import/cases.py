"""Independent provider schemas and single-field counterexamples for lifecycle ingestion."""
import copy

NAMES = ['default_construct', 'destroy', 'copy_construct', 'move_construct', 'copy_assign']

def fixture(role):
    name = NAMES[role-1]
    row = dict(id='op', symbol='bridge_op', kind=name, type='T', calling_convention='ccc',
               error_policy='terminate', exception_boundary='caught_in_bridge')
    ptr = dict(type='ptr', attributes='')
    row['abi'] = dict(return_type='void', return_attributes='', parameters=[ptr.copy()])
    result = dict(type='T', ownership='owned', passing='caller_storage', abi_index=0)
    borrow = dict(type='T', ownership='borrowed', passing='const_address', borrow_scope='call', abi_indices=[1])
    if role == 1:
        row.update(kind='construct', parameters=[], result=result, storage_precondition='aligned_uninitialized_storage')
    elif role == 2:
        row.update(parameters=[dict(type='T', ownership='consumed', passing='address', abi_indices=[0])], result=None,
                   storage_precondition='live_owned_object', storage_after='uninitialized_caller_storage')
    elif role in (3,4):
        if role == 4: borrow['passing'] = 'mutable_address'
        row.update(parameters=[borrow], result=result, storage_precondition='aligned_uninitialized_storage',
                   storage_after='live_owned_object', source_precondition='live_object', source_after='live_object')
        row['abi']['parameters'].append(ptr.copy())
    else:
        first=borrow.copy();first.update(passing='mutable_address',abi_indices=[0])
        row.update(parameters=[first,borrow],result=None,storage_precondition='live_object',storage_after='live_object',
                   source_precondition='live_object',source_after='live_object',self_assignment='native_call')
        row['abi']['parameters'].append(ptr.copy())
    return dict(role=role,type=dict(id='T',lifecycle={name:'op'},cpp_traits=dict(copy_constructible=True,
                move_constructible=True,copy_assignable=True,trivially_destructible=True)),operations={'op':row},accept=True)

def leaves(value,path=()):
    if isinstance(value,dict):
        for k,v in value.items(): yield from leaves(v,path+(k,))
    elif isinstance(value,list):
        for k,v in enumerate(value):yield from leaves(v,path+(k,))
    else:yield path,value

def build():
    cases=[]
    for role in range(1,6):
        base=fixture(role);cases.append(base)
        for path,value in leaves(base['operations']['op']):
            bad=copy.deepcopy(base);target=bad['operations']['op']
            for part in path[:-1]:target=target[part]
            replacement='wrong'
            if path==('symbol',):replacement='9invalid'
            elif path==('id',):replacement=''
            elif type(value) is int:replacement=value+1
            elif value is None:replacement={}
            target[path[-1]]=replacement;bad['accept']=False
            # Original package indexing rejected empty IDs before this private helper.
            if path==('id',):bad['retained_check']=False
            cases.append(bad)
        for key,value in [('allocation_effect',None),('expose_as','visible')]:
            bad=copy.deepcopy(base);bad['operations']['op'][key]=value;bad['accept']=False;cases.append(bad)
        for where in ['parameters','abi']:
            bad=copy.deepcopy(base)
            if where=='parameters':bad['operations']['op']['parameters'].append({'type':'T'})
            else:bad['operations']['op']['abi']['parameters']=[]
            bad['accept']=False;cases.append(bad)
        bad=copy.deepcopy(base);bad['operations']={};bad['accept']=False;cases.append(bad)
        if role in (3,4,5):
            bad=copy.deepcopy(base);key={3:'copy_constructible',4:'move_constructible',5:'copy_assignable'}[role]
            bad['type']['cpp_traits'][key]=False;bad['accept']=False;cases.append(bad)
    # Complete lifetime acceptance, independent permissions, cleanup evidence.
    all_ops={};lifecycle={}
    for role in range(1,6):
        f=fixture(role);key=NAMES[role-1];row=f['operations']['op'];row['id']=key
        all_ops[key]=row;lifecycle[key]=key
    base=dict(role=0,type=dict(id='T',lifecycle=lifecycle,cpp_traits=dict(copy_constructible=True,
        move_constructible=True,copy_assignable=True,trivially_destructible=True)),operations=all_ops,accept=True,policy='2:1:2:2:3')
    cases.append(base)
    empty=copy.deepcopy(base);empty['type']['lifecycle']={'cleanup':'none'};empty['operations']={};empty['policy']='0:0:0:0:0';cases.append(empty)
    for key in ['copy_constructible','move_constructible','copy_assignable']:
        free=copy.deepcopy(empty);free['type']['cpp_traits'][key]=False;cases.append(free)
    bad=copy.deepcopy(empty);bad['type']['cpp_traits']['trivially_destructible']=False;bad['accept']=False;cases.append(bad)
    bad=copy.deepcopy(empty);bad['type']['lifecycle']['cleanup']='other';bad['accept']=False;cases.append(bad)
    bad=copy.deepcopy(empty);bad['type']['lifecycle']={};bad['accept']=False;cases.append(bad)
    return cases
