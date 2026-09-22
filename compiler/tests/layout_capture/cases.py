import random

def build():
    cases=[]
    def add(nodes,roots,accept=True,changed=0):
        edges={i+1:(row['children'] if row['kind'] in ['record','array'] else []) for i,row in enumerate(nodes)}
        def reach(roots):
            found=set();todo=list(roots)
            while todo:
                i=todo.pop()
                if i in found:continue
                found.add(i);todo+=edges.get(i,[])
            return sorted(found)
        selected=reach(roots) if accept else []
        subset=sorted(set(roots))[:1] if accept else []
        affected=[i for i in selected if changed in reach([i])] if changed else []
        cases.append(dict(nodes=nodes,roots=roots,accept=accept,expected=selected,ordered=sorted(set(roots)),subset=subset,subset_nodes=reach(subset),changed=changed,affected=affected))
    scalar=lambda:dict(kind='scalar',children=[])
    record=lambda *ids:dict(kind='record',children=list(ids))
    add([],[]);add([scalar()],[1]);add([scalar()],[1,1]);add([record()], [1])
    add([scalar(),record(1,1),record(1,2)],[3,2,3],changed=1)
    add([dict(kind='pointer',children=[1])],[1],changed=1)
    add([scalar(),dict(kind='array',children=[1]),record(2)],[3],changed=1)
    add([record(1)],[1],False);add([record(2),record(1)],[1],False)
    add([scalar(),record(3),record(2)],[1],changed=1) # unreachable cycle ignored
    add([scalar()],[2],False)
    rng=random.Random(721)
    for _ in range(30):
        nodes=[scalar()]
        for i in range(2,25):
            children=[rng.randrange(1,i) for _ in range(rng.randrange(0,4))]
            nodes.append(record(*children))
        roots=[rng.randrange(1,25) for _ in range(8)]
        add(nodes,roots,changed=1)
    nodes=[scalar()]+[record(i) for i in range(1,513)]
    add(nodes,[513],changed=1)
    return cases
