def build():
    cases=[]
    def add(body,reason=''):
        cases.append(dict(source='function f(): int { '+body+' } return 0;',expected=reason))
    add('$s Storage<int32>; storage_0<int32>($s,4); storage_4<int32>($s); return 0;')
    add('$s Storage<int32>; storage_0<int32>($s,4); return 0;','Allocation must be released or transferred before its owner leaves scope')
    add('$s Storage<int32>; storage_4<int32>($s); return 0;')
    add('{ $s Storage<int32>; storage_0<int32>($s,4); storage_4<int32>($s); } return 0;')
    add('{ $s Storage<int32>; storage_0<int32>($s,4); } return 0;','Allocation must be released or transferred before its owner leaves scope')
    add('$s Storage<int>; storage_0<int>($s,4); $s[0] = 1; storage_4<int>($s); return 0;')
    add('$s Storage<int>; $s[0] = 1; return 0;','Element access requires an owned allocation')
    add('$b Box; return 0;');cases[-1]['lifecycle']=True
    return cases
