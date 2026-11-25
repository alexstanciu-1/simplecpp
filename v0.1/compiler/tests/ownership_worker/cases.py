def build():
    cases=[]
    roles=['default_construct','destroy','copy_construct','move_construct','copy_assign']
    for role in roles:
        cases.append(dict(role=role,child=False,body=False,required=3,result=9,source=False))
        for required in [1,2,3]:
            for result in [5,9,10]:
                cases.append(dict(role=role,child=True,body=False,required=required,result=result,source=False))
    for role in ['default_construct','destroy']:
        cases.append(dict(role=role,child=True,body=True,required=2,result=5,source=False))
    cases.append(dict(role='copy_construct',child=True,body=False,required=1,result=10,source=True))
    return cases
