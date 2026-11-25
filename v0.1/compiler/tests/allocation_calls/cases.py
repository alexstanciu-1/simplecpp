def build():
    cases=[]
    for state in range(16):
        for kind in ['acquire','release','inspect','mutate','observe']:
            for parameter in [False,True]:
                for validate in [False,True]:
                    cases.append(dict(state=state,destination=5,kind=kind,parameter=parameter,validate=validate,borrow=0,preceding=False,alias=False))
        for destination in range(16):
            for validate in [False,True]:
                cases.append(dict(state=state,destination=destination,kind='transfer',parameter=False,validate=validate,borrow=0,preceding=False,alias=False))
    for parameter in [False,True]:
        for validate in [False,True]:
            for borrow in range(4):
                for alias in [False,True]:
                    for preceding in [False,True]:
                        cases.append(dict(state=10,destination=5,kind='transfer',parameter=parameter,validate=validate,borrow=borrow,preceding=preceding,alias=alias))
    for case in cases:
        if case['validate'] and not case['parameter'] and not case['alias'] and not case['borrow'] and not case['preceding']:
            if case['state']==5 and case['destination']==5:
                if case['kind']=='acquire':case['expected']=[10,5,-1,-1,0,1,0,[],'',0]
                elif case['kind']=='release':case['expected']=[5,5,-1,-1,0,1,0,[],'',0]
                elif case['kind']=='inspect':case['expected']=[5,5,-1,-1,0,0,0,[],'Allocation operation requires an owned allocation',17]
            if case['kind']=='transfer' and case['state']==10 and case['destination']==5:
                case['expected']=[5,10,-1,-1,0,2,0,[],'',0]
    return cases
