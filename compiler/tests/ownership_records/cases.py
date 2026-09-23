def build():
    cases=[]
    for required in range(5):
        for result in range(17):
            for mutates in [False,True]:
                for accessed in [False,True]:
                    cases.append(dict(required=required,result=result,mutates=mutates,accessed=accessed))
    return cases
