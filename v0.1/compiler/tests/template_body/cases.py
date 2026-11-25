from pathlib import Path
import re

def build():
    original = Path(__file__).resolve().parents[2] / 'reference/pre-rewrite/tests/04_analyze/check_templates/generic_contracts.php'
    text = original.read_text()
    definitions = text.split("$definitions = <<<'PHS'\n",1)[1].split('\nPHS;',1)[0]
    cases = [{'source':definitions, 'reason':'', 'count':8}]
    negatives = text.split('// Definition diagnostics',1)[1].split('// Imported contracts',1)[0]
    for source,reason in re.findall(r"\['([^']*)', '([^']*)'\]",negatives):
        if reason == 'value lifetime': continue # Concrete instantiation requirement, not definition checking.
        cases.append({'source':source,'reason':reason,'count':0})
    extras = [
        ('template<typename T> function ok($x T): T { if (true) { return $x; } else { return $x; } }','',1),
        ('template<typename T> function bad($x T): T { if (true) { return $x; } else { return 1; } }','same declared type',0),
        ('template<typename T> function bad($x T): int { while ($x) { return 1; } return 0; }','condition',0),
        ('template<typename T> function bad($x T): int { echo $x; return 0; }','output conversion',0),
        ('template<typename T> function bad($x T): int { return $x[0]; }','index access',0),
        ('template<typename T> function bad($x T): int { if constexpr (true) { return 0; } return 1; }','compile-time branch',0),
        ('template<typename T> function good(): int { $a int; $b int = 3; while (false) { $b = $b + 1; } echo "ok"; return $b; }','',1),
        ('template<typename T> function id($x T): T { return $x; } template<typename T> function bad($x T): T { return id<T>(); }','argument count',0),
        ('struct S { public int $x; public function set(): void {} } template<typename T> function bad(const S &$s): void { $s->set(); }','non-const receiver',0),
        ('struct S { public int $x; } function write(S &$s): void {} template<typename T> function bad(const S &$s): void { write($s); }','mutable parameter',0),
        ('template<typename T> struct S { public T $x; public function __copy_construct(const S<T> &$s): void {} }','field default construction',0),
        ('template<typename T> struct S { public T $x; public function __destruct(): void {} }','',2),
        ('template<typename T> struct S { public T $x; public function __copy_assign(const S<T> &$s): void {} }','',2),
        ('template<typename T> struct S { public int $xs[3]; } template<typename T> function read(const S<T> &$s): int { return $s->xs[0]; }','',2),
        ('template<typename T> struct S { public int $xs[3]; } template<typename T> function bad(const S<T> &$s, $i T): int { return $s->xs[$i]; }','index conversion',0),
    ]
    cases += [{'source':s,'reason':r,'count':n} for s,r,n in extras]
    cases += [
        {'source':'template<typename T> function deep(): int { return '+('1+'*300)+'1; }','reason':'','count':1},
        {'source':'template<typename T> function bad($x T): T { return true; }','reason':'same declared type','count':0},
        {'source':'template<typename T> function bad($x T): bool { return $x; }','reason':'same declared type','count':0},
        {'source':'struct S { public int $x; } template<typename T> function good(): S { return new S(); }','reason':'','count':1},
        {'source':'template<typename T> struct S { public T $x; } template<typename T> function bad(const S<T> &$s, $x T): void { $s->x = $x; }','reason':'const reference','count':0},
        {'source':'template<typename T> struct S { public int $xs[3]; } template<typename T> function bad(const S<T> &$s): void { $s->xs[0] = 1; }','reason':'const reference','count':0},
    ]
    providers = [
        ('template<typename T> function good(const Family<T> &$f, const T &$x): T { return $f->get($x); }','',1),
        ('template<typename T> function bad(const Family<T> &$f, const T &$x): T { return $f->touch($x); }','Mutable borrowing',0),
        ('template<typename T> function bad(const Family<T> &$f): int { $f->get(); return 0; }','argument count',0),
        ('template<typename T> function bad(const Family<T> &$f, $x int): T { return $f->get($x); }','same declared type',0),
        ('template<typename T> function bad($f Family<T>): int { return 0; }','Whole provider value',0),
        ('template<typename T> function bad($x T): int { concrete_provider($x); return 0; }','concrete provider parameter',0),
        ('template<typename T> function good($x int): int { concrete_provider($x); return 0; }','',1),
        ('template<typename T> function bad(const Family<T> &$f): int { $copy Family<T> = $f; return 0; }','Whole provider value',0),
        ('template<typename T> function bad(): int { $f Family<T>; return 0; }','empty constructor',0),
        ('template<typename T> function bad(const Family<T> &$f): Family<T> { return $f; }','Whole provider value',0),
    ]
    for item in cases: item['providers'] = False
    cases += [{'source':s,'reason':r,'count':n,'providers':True} for s,r,n in providers]
    return cases
