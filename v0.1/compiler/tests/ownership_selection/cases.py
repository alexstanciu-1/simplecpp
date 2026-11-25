def build():
    cases=[]
    def add(source,dependencies,selected=True):
        cases.append(dict(source=source+' return 0;',dependencies=dependencies,selected=selected,owned_return='function f(): Nested' in source,expected='accepted'))
    add('function f(): int { return 0; }',[],False)
    add('function f(const Nested &$n): int { return 0; }',[])
    add('function f(): int { $n Nested; return 0; }',[['construct:','Nested'],['destroy:','Nested']])
    add('function f(): int { $s Storage<int32>; return 0; }',[],False)
    add('function f(): Nested { return new Nested(); }',[['construct:','Nested'],['destroy:','Nested']])
    add('function f(const Nested &$n): int { return g($n); } function g(const Nested &$n): int { return 0; }',[['body:','g']])
    add('function f(): Nested { return g(); } function g(): Nested { return new Nested(); }',[['body:','g'],['destroy:','Nested']])
    add('function f(): int { $a Nested; $b Nested; return 0; }',[['construct:','Nested'],['destroy:','Nested']])
    return cases
