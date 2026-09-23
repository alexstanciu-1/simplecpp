import itertools

def build():
    cases=[]
    for kinds in [()] + [seq for size in (1,2,3) for seq in itertools.product((1,2,3),repeat=size)]:
        rows=[[kind,0 if kind==1 else i+1,9+i,i*2] for i,kind in enumerate(kinds)]
        cases.append(dict(local=7,rows=rows,error=False,allocation=3 in kinds,
                          indices=[i for i,k in enumerate(kinds) if k!=1]))
    for row in [[1,-1,1,0],[2,0,1,0],[3,0,1,0],[1,0,0,0],[1,0,1,-1]]:
        cases.append(dict(local=1,rows=[row],error=True,allocation=False,indices=[]))
    for local in [0,-1]:
        cases.append(dict(local=local,rows=[],error=True,allocation=False,indices=[]))
    return cases
