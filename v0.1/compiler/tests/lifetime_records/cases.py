import itertools

def build():
    cases=[]
    names=['discard','condition','return_copy','return_construct','local_copy','argument_copy','argument_borrow','local_construct','copy_source','assignment_source','conversion_input','operation_input','index_input','target_index']
    for end,(value,statement,consumer) in itertools.product(range(1,15),itertools.product([-1,0,1,7],[-1,0,1,8],[-1,0,1,9])):
        cases.append(dict(kind='value',tag=end,name=names[end-1],a=value,b=statement,c=consumer,d=0))
    for subject,(identity,boundary,block) in itertools.product(range(1,3),itertools.product([-1,0,1,7],[-1,0,1,8],[-1,0,1,9])):
        cases.append(dict(kind='cleanup',tag=subject,name=['local','temporary'][subject-1],a=identity,b=boundary,c=block,d=0))
    for end,(identity,start,boundary,block) in itertools.product(range(1,3),itertools.product([0,1,7],[0,1,5],[0,5],[1,3])):
        cases.append(dict(kind='local',tag=end,name=['scope_exit','return_exit'][end-1],a=identity,b=start,c=boundary,d=block))
    for identity,start in itertools.product([0,1,7],[0,1,5]):
        cases.append(dict(kind='active',tag=0,name='',a=identity,b=start,c=0,d=0))
    return cases
