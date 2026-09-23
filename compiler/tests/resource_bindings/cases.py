def build():
    cases=[]
    def add(mode,source,expected,position=0):cases.append(dict(mode=mode,source=source,expected=expected,position=position))
    add('operand','function g(const Box &$b): int { return 0; } function f(const Box &$b): int { return g($b); } return 0;','1')
    add('operand','function f(const Box &$b): int { return storage_3<int32>($b->items); } return 0;','1:0')
    add('operand','function f(const Nested &$n): int { return storage_3<int32>($n->box->items); } return 0;','1:0.0')
    source='function g(const Box &$a,const Box &$b): int { return 0; } function f(const Box &$a,const Box &$b): int { return g($b,$a); } return 0;'
    add('operand',source,'2',0);add('operand',source,'1',1);add('summary',source,'1>2');add('allocation',source,'1>2')
    add('operand','function g($n int): int { return $n; } function f(): int { return g(1); } return 0;','Allocation operation requires an existing local owner')
    add('operand','function g(const Point &$p): int32 { return $p->value; } function f(): int32 { $s Storage<Point>; return g($s[0]); } return 0;','Dynamically selected owner subobjects require an ownership contract')
    return cases
