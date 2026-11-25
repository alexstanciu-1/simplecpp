import random

def build():
    cases=[]
    def add(nodes,roots):
        def spelling(i):
            n=nodes[i-1];k=n['kind']
            if k=='integer':return 'i'+str(n['width'])
            if k=='opaque':return '['+str(n['size'])+' x i8]'
            if k=='float':return dict(ieee_binary16='half',bfloat16='bfloat',ieee_binary32='float',ieee_binary64='double',ieee_binary128='fp128')[n['format']]
            if k=='record':return '{ '+', '.join(spelling(c) for c in n['children'])+' }'
            if k=='array':return '['+str(n['count'])+' x '+spelling(n['children'][0])+']'
            raise ValueError(k)
        def opaque(i):
            n=nodes[i-1]
            return n['kind']=='opaque' or any(opaque(c) for c in n.get('children',[]))
        cases.append(dict(nodes=nodes,roots=roots,texts=[spelling(i) for i in roots],aligned=[opaque(i) for i in roots],fields=[[spelling(c) for c in nodes[i-1].get('children',[])] if nodes[i-1]['kind']=='record' else [] for i in roots]))
    integer=lambda w:dict(kind='integer',width=w)
    record=lambda *ids:dict(kind='record',children=list(ids))
    add([],[])
    for width in [1,7,8,16,32,64,129]:add([integer(width)],[1])
    for f in ['ieee_binary16','bfloat16','ieee_binary32','ieee_binary64','ieee_binary128']:add([dict(kind='float',format=f)],[1])
    add([record()],[1])
    add([integer(7),dict(kind='opaque',size=32,alignment=16),record(1,2,1),dict(kind='array',children=[3],count=3),record(4,3)],[5,3,2])
    add([integer(8),dict(kind='array',children=[1],count=0),record(2)],[3,2])
    rng=random.Random(411)
    for _ in range(20):
        nodes=[integer(32),dict(kind='opaque',size=16,alignment=8)]
        for i in range(3,16):nodes.append(record(*[rng.randrange(1,i) for j in range(rng.randrange(4))]))
        add(nodes,[15,14,13])
    add([integer(8)]+[record(i) for i in range(1,201)],[201])
    return cases
