def build():
    cases=[]
    def add(body,params='',ret='int',ownership=False,allocations=False,error='accepted'):
        cases.append(dict(source=f'function f({params}): {ret} {{ {body} }} return 0;',ownership=ownership,allocations=allocations,expected=error))
    add('return 0;')
    add('$s Storage<int32>; return 0;',allocations=True)
    add('$s Storage<int32>; storage_0<int32>($s,4); storage_4<int32>($s); return 0;',allocations=True)
    add('$s Storage<int32>; storage_0<int32>($s,4); return 0;',error='Allocation must be released or transferred before its owner leaves scope')
    add('return 0;',params='const Nested &$n',ownership=True,allocations=True)
    add('$n Nested; return 0;',ownership=True,allocations=True)
    add('return new Nested();',ret='Nested',ownership=True,allocations=True)
    add('$s Storage<int32>; if ($p) { storage_0<int32>($s,4); storage_4<int32>($s); } return 0;',params='$p int',allocations=True)
    return cases
