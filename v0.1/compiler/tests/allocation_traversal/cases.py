def build():
    cases=[]
    def add(body,states,reason='',ret='int',extra=''):
        cases.append(dict(source=f'function f(): {ret} {{ {body} }} '+extra+' return 0;',expected=[states,reason]))
    add('$s Storage<int32>; storage_0<int32>($s,4); storage_4<int32>($s); return 0;',[5])
    # Count is metadata-observe and accepts an empty descriptor; indexed reads require ownership.
    add('$s Storage<int32>; return storage_3<int32>($s);',[5])
    add('$s Storage<int32>; storage_4<int32>($s); return 0;',[5])
    add('$s Storage<int32>; storage_0<int32>($s,4); return storage_3<int32>($s);',[10])
    add('$s Storage<int32>; storage_0<int32>($s,4); storage_0<int32>($s,8); return 0;',[10],'Allocation acquisition requires an empty owner')
    add('$s Storage<int32>; $t Storage<int32>; storage_0<int32>($s,4); storage_5<int32>($s,$t); return 0;',[5,10])
    add('$s Storage<int32>; storage_0<int32>($s,4); storage_5<int32>($s,$s); return 0;',[10],'Allocation transfer requires a distinct empty destination')
    add('$s Storage<int32>; return $s[0];',[5],'Element access requires an owned allocation',ret='int32')
    add('$s Storage<int32>; storage_0<int32>($s,4); return $s[0];',[10],ret='int32')
    add('$s Storage<Point>; storage_0<Point>($s,4); return consume($s[0],storage_3<Point>($s));',[10],ret='int32',extra='function consume(const Point &$p,$n int): int32 { return $p->value; }')
    add('$s Storage<Point>; storage_0<Point>($s,4); storage_1<Point>($s,$s[0]); return 0;',[10])
    add('$s Storage<int32>; storage_4<int32>($s); return 0;',[5],'Allocation mutation would invalidate an active call borrow')
    cases[-1]['pin']=True
    add('$b Box; return read($b);',[5],extra='function read(const Box &$b): int { return 0; }')
    cases[-1]['summary_call']=True
    add('$b Box; return 0;',[5]);cases[-1]['construct_dependency']=True
    for case in cases:
        case['mode']='expression'
    for required,result,state,expected in [(1,10,10,''),(2,10,10,'Complete construction requires empty field storage'),(3,9,5,''),(1,5,5,'')]:
        cases.append(dict(mode='construct',source='function f(): int { $b Box; return 0; } return 0;',required=required,result=result,state=state,expected=expected))
    for required,state,expected in [(1,5,''),(2,10,''),(1,10,'Temporary destruction does not satisfy its ownership contract'),(3,15,'')]:
        cases.append(dict(mode='destroy',source='function f(): int { $b Box; return 0; } return 0;',required=required,result=5,state=state,expected=expected))
    for mode,initial,expected in [('copy',10,''),('move',10,''),('copy',5,'Source call ownership requirements are not satisfied')]:
        cases.append(dict(mode=mode,source='function read(const Box &$b): int { return 0; } function f(): int { $b Box; return read($b); } return 0;',required=1,result=10,state=10,initial=initial,expected=expected))
    return cases
