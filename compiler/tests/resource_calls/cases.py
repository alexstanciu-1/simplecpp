def build():
    cases=[]
    patterns=[('release','inspect'),('inspect','release'),('release','release'),('acquire','inspect'),('observe','unused'),('mutate','inspect')]
    for state in range(16):
        for first,second in patterns:
            for alias in [False,True]:
                for parameter in [False,True]:
                    cases.append(dict(state=state,first=first,second=second,alias=alias,parameter=parameter,validate=True,borrow=0,preceding=False,distinct=False,fields=False))
    for validate in [False,True]:
        for borrow in range(4):
            for distinct in [False,True]:
                for preceding in [False,True]:
                    cases.append(dict(state=9,first='release',second='inspect',alias=False,parameter=True,validate=validate,borrow=borrow,preceding=preceding,distinct=distinct,fields=False))
    for validate in [False,True]:
        for state in range(16):
            cases.append(dict(state=state,first='release',second='inspect',alias=True,parameter=True,validate=validate,borrow=0,preceding=False,distinct=False,fields=True))
    cases.append(dict(state=10,first='observe',second='observe',alias=True,parameter=False,validate=True,borrow=0,preceding=False,distinct=True,fields=False))
    for case in cases:
        if case['validate'] and case['borrow']==0 and not case['preceding'] and not case['fields']:
            if case['state']==10 and case['alias'] and not case['parameter']:
                if (case['first'],case['second']) in [('release','inspect'),('inspect','release')]:
                    case['expected']=[5,10,-1,-1,0,1,0,[],'',0]
                elif case['first']==case['second']=='release':
                    case['expected']=[10,10,-1,-1,0,0,0,[],'Aliased arguments require a single resource writer',17]
                elif case['distinct']:
                    case['expected']=[10,10,-1,-1,0,0,0,[],'Aliased arguments violate resource access order or allocation stability',17]
            elif case['state']==9 and not case['alias'] and case['parameter'] and case['first']=='release' and case['second']=='inspect':
                keys=['0:0|1:0'] if case['distinct'] else []
                case['expected']=[5,10,2,3,1,1,2,keys,'',0]
    return cases
