"""PHP donor cases supplementing the namespace, member, foreach and enum fixtures."""
from pathlib import Path
from tempfile import TemporaryDirectory

from app_namespace_native import artifacts, invoke, service
from exceptions_native import fresh_build, native
from extends_native import runtime, source_edit


# Revisions of main.php in one compiler. Error strings include the source location.
CASES = [
    ('chained-assignment',
     '$a = $b = 1; return 8;',
     'main.php:1:9: Expected ;'),
    ('split-assignment',
     'class Box {public int $n=7;} function makeBox():Box {echo "M"; return new Box();} '
     '$b=makeBox(); $a=$b; $b->n=9; echo $a->n; return 8;',
     (8, b'M9', b'')),
    ('discarded-arithmetic',
     '$a=1; $b=2; $c=3; $a + 1; return 8;',
     'main.php:1:22: Expected identifier'),
    ('discarded-call-result',
     'function observed():int {echo "C"; return 2;} observed(); return 8;',
     (8, b'C', b'')),
    ('unused-result-still-checked',
     '$x=2; $divisor=0; $unused=$x / $divisor; return 8;',
     (1, b'', b'division_by_zero\n')),
    ('nullable-string-field',
     'class A {public ?string $x;} $a=new A(); echo (int)isset($a->x); return 8;',
     'main.php:1:17: Unsupported instance field type: nullable<string>'),
    ('nullable-class-field',
     'class B {} class A {public ?B $b;} $a=new A(); if ($a->b===null) {echo 1;} return 8;',
     (8, b'1', b'')),
    ('scope',
     '$f /** function<int(int)> */; if (true) { $f = function (int $x): int { return $x + 1; }; } echo $f(5); return 8;',
     'main.php:1:29: Expected identifier'),
    ('declared-callable',
     '$f function<int(int)>; if (true) { $f = function (int $x):int {return $x+1;}; } echo $f(5); return 8;',
     'main.php:1:4: Expected identifier'),
    ('scope-control',
     '$f = function (int $x): int { return $x + 1; }; echo $f(5); return 8;',
     (8, b'6', b'')),
    ('unqualified-call',
     'namespace App; function f():void {g();} function g():void {echo "local";} f(); return 8;',
     (8, b'local', b'')),
    ('order',
     '$a = $b; $b = 1; return 8;',
     'main.php:1:6: Unknown or not-yet-initialized local/global: b'),
    ('used-import',
     'namespace App; use Lib\\X; $a=new X(); echo $a->n; return 8;',
     (8, b'7', b'')),
    ('exec1',
     'namespace A { compute(1); }',
     'main.php:1:28: Entry slice requires an explicit value return'),
    ('exec1-control',
     'namespace A { compute(1); return 8; }',
     (8, b'1', b'')),
    ('exec2',
     'namespace A { compute(1); return 1; }',
     (1, b'1', b'')),
    ('exec3',
     'namespace A { compute(1); class X {} compute(2); }',
     'main.php:1:51: Entry slice requires an explicit value return'),
    ('exec3-control',
     'namespace A { compute(1); class X {} compute(2); return 8; }',
     (8, b'12', b'')),
    ('exec5',
     'namespace A { compute(1); namespace B { class X {} } compute(3); }',
     'main.php:1:27: Nested/additional namespaces, body-local use and export are not supported in this slice'),
    ('resolve',
     'class A {public function __construct(){echo "ctor";}} $a=new A(); A\\B\\f(); return 8;',
     (8, b'ctorfunc', b'')),
    ('ns-class',
     'namespace A; class X {public int $n=7; public static function make():int{return 9;}} function f():X{return new X();} function rooted():X{return new \\A\\X();} $a=f(); $b=rooted(); echo $a->n,$b->n,X::make(),\\A\\X::make(),$a::make(); return 8;',
     (8, b'77999', b'')),
    ('ns-relative',
     r'namespace A\B; class X { public int $n=99; } '
     r'function makeLocal():int { $x=new A\B\X(); return $x->n; } echo makeLocal(); return 8;',
     (8, b'7', b'')),
    ('untyped-const',
     'class A { public const X = 1; } echo A::X; return 8;',
     'main.php:1:26: Expected identifier'),
    ('typed-const',
     'class A { public const int X = 1; } echo A::X; return 8;',
     (8, b'1', b'')),
    ('unknown-field',
     'class A { public function assignField():void { $this->y = 1; } } $a=new A(); $a->assignField(); return 8;',
     'main.php:1:48: Unknown field A.y'),
    ('dynamic-field',
     'class A { public int $x=0; public function assignField(string $name):void { $this->$name = 1; } } $a=new A(); $a->assignField("x"); return 8;',
     'main.php:1:84: Expected identifier'),
    ('direct-field',
     'class A {public int $x=0; public function assignField():void {$this->x=1;}} $a=new A(); $a->assignField(); echo $a->x; return 8;',
     (8, b'1', b'')),
    ('dynamic-method',
     'class A { public function f():void { echo 1; } } $a=new A(); $name="f"; $a->$name(); return 8;',
     'main.php:1:77: Expected identifier'),
    ('direct-method',
     'class A {public function f():void {echo 1;}} $a=new A(); $a->f(); return 8;',
     (8, b'1', b'')),
    ('destructor',
     'class A { public function __destruct(){} } $a=new A(); $a->__destruct(); return 8;',
     'main.php:1:56: Explicit destructor invocation is forbidden'),
    ('abstract-static',
     'abstract class A { abstract static function f(): int; } return 8;',
     'main.php:1:20: Virtual modifiers require a public or protected instance method: A::f'),
    ('intmax',
     '$a = PHP_INT_MAX; echo $a; return 8;',
     'main.php:1:6: Unknown constant: PHP_INT_MAX'),
    ('intmax-control',
     '$a = 9223372036854775807; echo $a; return 8;',
     (8, b'9223372036854775807', b'')),
]


def prove(work):
    app = work / 'app'
    app.mkdir()
    (app / 'module.json').write_text('{}')
    source_edit(app / 'lib.php', r'namespace Lib; class X {public int $n=7;}')
    source_edit(app / 'compute.php', 'namespace A; function compute(int $i):void {echo $i;}')
    source_edit(app / 'f.php', r'namespace A\B; function f():void {echo "func";}')
    source_edit(app / 'deep.php', r'namespace A\B\A\B; class X { public int $n=7; }')
    output = work / 'out'
    library = runtime(work, 'clang++')
    resident = service(app, output)
    try:
        for label, source, expected in CASES:
            source_edit(app / 'main.php', source)
            before = artifacts(output)
            actual = invoke(resident)
            fresh = work / ('fresh-' + label)
            control = fresh_build(app, fresh)
            if isinstance(expected, str):
                assert actual.get('error', '').endswith('/' + expected), (label, actual)
                assert control.get('error') == actual['error'], (label, control)
                assert artifacts(output) == before, label
            else:
                assert 'error' not in actual and 'error' not in control, (label, actual, control)
                assert artifacts(output) == artifacts(fresh), label
                native(output, work / label, expected, library)
    finally:
        resident.stdin.close()
        assert resident.wait() == 0


if __name__ == '__main__':
    with TemporaryDirectory() as tmp:
        prove(Path(tmp))
