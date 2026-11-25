import itertools

def build():
    cases=[]
    paths=[[],[0],[1],[1,23],[12,3],[0,0],[3,4,5],[9223372036854775807]]
    for local,path in itertools.product([1,4],paths):
        for suffix in paths:
            cases.append(dict(kind='project',local=local,path=path,suffix='.'.join(map(str,suffix))))
    for local,right_local,path,other in itertools.product([1,4],[1,4],paths,paths):
        cases.append(dict(kind='overlaps',local=local,right_local=right_local,path=path,other=other))
    endpoints=['0:','2:0','12:3.4','9223372036854775807:9223372036854775807','0:0.0',
        '-1:0','01:0','0:01','0:.1','0:1.','0:1..2','0:missing','0:1|1:0','0:0:1',
        '999999999999999999999999999999:0','9223372036854775808:0','0:9223372036854775808',
        ':','0','0:+1','+0:','0:1\n',' 0:1','0: 1','0:١','0:1e2','0:00','0:0.01','0:000000000000000000000']
    for key in endpoints:cases.append(dict(kind='endpoint',key=key))
    pairs=['0:','1:0','0:1','10:2','2:10','0:0.1','0:0.10','0:0.2']
    for left,right in itertools.product(pairs,pairs):cases.append(dict(kind='distinct',left=left,right=right))
    for kinds in [[],[1],[1,1],[3],[1,3],[1,2,1],[1,1,3,1]]:
        cases.append(dict(kind='place',kinds=kinds))
    # Body cases use an independently stated expected descriptor/parameter inventory.
    for params,body,expected in [
        ('','$s Storage<int32>; return 0;',[['1'],[]]),
        ('const Storage<int32> &$s','return 0;',[['1'],[[0,['']]]]),
        ('$x int,const Storage<int32> &$s','$t Storage<Point>; return $x;',[['2','3'],[[1,['']]]]),
        ('','$x int=0; return $x;',[[],[]]),
        ('const Box &$b','return 0;',[['1:0'],[[0,['0']]]]),
        ('const Nested &$n','return 0;',[['1:0.0'],[[0,['0.0']]]]),
        ('const Box &$b,const Nested &$n,$x int','return $x;',[['1:0','2:0.0'],[[0,['0']],[1,['0.0']]]]),
    ]:cases.append(dict(kind='body',source=f'function f({params}): int {{ {body} }} return 0;',expected=expected))
    return cases
