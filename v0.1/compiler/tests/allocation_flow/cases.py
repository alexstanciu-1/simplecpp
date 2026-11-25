def build():
    cases=[]
    def add(body,params='',ret='int',analysis=True,rows=None,reason=''):
        cases.append(dict(source=f'function f({params}): {ret} {{ {body} }} return 0;',expected=[analysis,rows or [],reason]))
    add('return 0;',analysis=False)
    add('$s Storage<int32>; storage_0<int32>($s,4); storage_4<int32>($s); return 0;')
    add('$s Storage<int32>; storage_0<int32>($s,4); return 0;',analysis=False,reason='Allocation must be released or transferred before its owner leaves scope')
    add('$s Storage<int32>; if ($p) { storage_0<int32>($s,4); } else { storage_0<int32>($s,8); } storage_4<int32>($s); return 0;','$p int')
    add('$s Storage<int32>; if ($p) { storage_0<int32>($s,4); } storage_4<int32>($s); return 0;','$p int',analysis=False,reason='Allocation ownership differs across control-flow paths')
    add('$s Storage<int32>; while ($p) { storage_0<int32>($s,4); storage_4<int32>($s); $p=0; } return 0;','$p int')
    add('$s Storage<int32>; while ($p) { storage_0<int32>($s,4); $p=0; } return 0;','$p int',analysis=False,reason='Allocation ownership differs across control-flow paths')
    add('while ($p) { $s Storage<int32>; storage_0<int32>($s,4); storage_4<int32>($s); $p=0; } return 0;','$p int')
    add('return 0; $s Storage<int32>; storage_0<int32>($s,4);')
    add('return 0;','const Box &$b',rows=[[0,'0',3,9,False,False]])
    add('return storage_3<int32>($b->items);','const Box &$b',rows=[[0,'0',3,9,False,True]])
    add('return $b->items[0];','const Box &$b',ret='int32',rows=[[0,'0',2,9,False,True]])
    add('return 0;','const Storage<int32> &$s',analysis=False,reason='Allocation owner parameters require an ownership contract')
    cases[3]['entry_states']=[5,10]
    cases[5]['entry_states']=[5]
    for index in [9,10,11]:cases[index]['entry_states']=[9]
    add('$s Storage<int32>; storage_0<int32>($s,4); while ($p) { storage_3<int32>($s); $p=0; } storage_4<int32>($s); return 0;','$p int')
    cases[-1]['entry_states']=[10]
    add('$s Storage<int32>; '+ ' '.join('if ($p) { storage_0<int32>($s,4); storage_4<int32>($s); }' for _ in range(32))+' return 0;','$p int')
    cases[-1]['entry_states']=[5]
    return cases
