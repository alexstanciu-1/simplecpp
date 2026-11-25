import itertools

def build():
    cases=[dict(mode='value',kind=k,hex=bytes(range(256)).hex()) for k in range(1,10)]
    for kind,write,ret,target,value,scope in itertools.product(range(1,6),range(1,6),range(1,6),[False,True],[0,1],[0,1]):
        writes=kind in (3,4)
        valid=(ret==1 or kind==1) and scope>0 and writes==target and (not writes or value>0)
        valid=valid and (write not in (2,3,4) or kind==3) and (write!=5 or kind==4)
        cases.append(dict(mode='statement',kind=kind,write=write,ret=ret,target=target,value=value,scope=scope,valid=valid))
    return cases
