"""Independent measured layout bounds, alignment and field cardinality expectations."""
def build():
    cases=[]
    for size,alignment,offsets,fields,accept in [
        (8,8,[0,4],2,True),(1,1,[],0,True),(8,8,[1,7],2,True),
        (0,1,[],0,False),(-1,1,[],0,False),(8,0,[],0,False),(8,-1,[],0,False),
        (12,3,[0],1,False),(9,8,[0],1,False),(8,8,[0],2,False),
        (8,8,[-1],1,False),(8,8,[8],1,False),(8,8,[0,0],2,False),(8,8,[4,0],2,False),
        (4611686018427387904,4611686018427387904,[0],1,True),
        (9223372036854775807,9223372036854775807,[0],1,False),
        (9223372036854775807,1,[9223372036854775806],1,True)]:
        cases.append(dict(size=size,alignment=alignment,offsets=offsets,fields=fields,accept=accept))
    return cases
