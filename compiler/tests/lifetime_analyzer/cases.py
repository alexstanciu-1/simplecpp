def build():
    cases=[]
    def add(program,expected='accepted'):
        cases.append(dict(source=program+' function g(): int { return 0; } return 0;',expected=expected))
    add('function f(): int { return 0; }')
    add('function f(const Nested &$n): int { return 0; }')
    add('function f(): Nested { return new Nested(); }')
    add('function f(): int { $s Storage<int32>; storage_0<int32>($s,4); storage_4<int32>($s); return 0; }')
    add('function f(): int { $s Storage<int32>; storage_0<int32>($s,4); return 0; }','Allocation must be released or transferred before its owner leaves scope')
    add('function f(): int { $n Nested; storage_0<int32>($n->box->items,4); return 0; }','Source call ownership requirements are not satisfied')
    add('function f(const Nested &$n): int { return f($n); }','cycle rejected')
    return cases
