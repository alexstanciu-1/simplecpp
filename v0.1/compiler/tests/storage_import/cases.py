"""Complete storage protocol and mutations of its semantic/physical evidence."""
import copy
ROLES=['allocate','next','commit','at','pop','count','release','transfer']

def build():
    operations=[]
    for role in ROLES:
        readonly=role in ['at','count'];expected=['descriptor']+(['descriptor'] if role=='transfer' else ['counter']*({'allocate':3,'at':1}.get(role,0)))
        params=[];positions=[]
        for i,t in enumerate(expected):
            params.append(dict(type=t,passing=('const_address' if readonly else 'mutable_address') if t=='descriptor' else 'direct',ownership='borrowed' if t=='descriptor' else 'value',borrow_scope='call',abi_indices=[i]))
            positions.append(dict(type='ptr' if t=='descriptor' else 'i64',attributes=''))
        result='address' if role in ['next','at'] else ('counter' if role=='count' else 'void')
        operations.append(dict(id=role,symbol='primitive_'+role,kind='free_function',calling_convention='ccc',error_policy='terminate',exception_boundary='caught_in_bridge',parameters=params,result=dict(type=result,passing='direct',ownership='value'),abi=dict(parameters=positions,return_type={'address':'ptr','counter':'i64','void':'void'}[result],return_attributes='')))
    row=dict(id='descriptor',kind='runtime_value',storage_family=dict(counter_type='counter',primitives={r:r for r in ROLES},operations={r:'storage_'+r for r in ['allocate','push','pop','count','release','transfer']}))
    base=dict(rows=[row],operations=operations,descriptor_mode=0,counter_mode=0,void_mode=0,accept=True);cases=[base]
    def bad(path,value):
        f=copy.deepcopy(base);node=f
        for p in path[:-1]:node=node[p]
        node[path[-1]]=value;f['accept']=False;cases.append(f)
    for i,role in enumerate(ROLES):
        for path,value in [(('kind',),'method'),(('calling_convention',),'fastcc'),(('error_policy',),'throw'),(('exception_boundary',),'uncaught'),(('symbol',),'9bad'),(('expose_as',),'visible'),(('allocation_effect',),{}),(('conversion_purpose',),'text'),(('parameters',0,'passing'),'direct'),(('parameters',0,'abi_indices'),[1]),(('abi','parameters',0,'type'),'i64'),(('abi','parameters',0,'attributes'),'noalias'),(('result','passing'),'caller_storage'),(('result','ownership'),'owned'),(('result','type'),'missing')]:bad(('operations',i)+path,value)
    bad(('operations',0,'parameters',1,'type'),'descriptor')
    bad(('operations',0,'abi','parameters',1,'type'),'i32')
    bad(('operations',5,'result','type'),'other_counter')
    bad(('operations',7,'parameters',1,'type'),'counter')
    bad(('operations',1,'abi','return_type'),'i64')
    bad(('operations',2,'abi','return_attributes'),'noundef')
    bad(('operations',0,'parameters'),[])
    for path,value in [(('rows',0,'storage_family','counter_type'),'missing'),(('rows',0,'storage_family','primitives','allocate'),'missing'),(('rows',0,'storage_family','primitives','extra'),'allocate'),(('rows',0,'storage_family','operations','push'),'storage_allocate'),(('rows',0,'storage_family','operations','push'),'9bad'),(('rows',0,'storage_family','operations','extra'),'extra'),(('rows',0,'resource'),'allocation'),(('rows',0,'kind'),'integer')]:bad(path,value)
    for mode in [1,2,3,4]:bad(('descriptor_mode',),mode)
    for mode in [1,2]:bad(('counter_mode',),mode)
    bad(('void_mode',),1)
    skip=copy.deepcopy(base);skip['rows']=[{'id':'ignored'}];cases.append(skip)
    return cases
