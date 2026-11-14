"""Ordinary struct methods reuse the source-function pipeline across units and flavors."""
import argparse
import json
import os
from pathlib import Path
import tempfile

from app_namespace_native import artifacts, invoke, service
from structs_native import build, check_empty_access


def prove(work, flavor):
    app = work / flavor
    app.mkdir()
    (app / 'module.json').write_text(json.dumps({'entry': 'main.' + flavor}))
    model = app / ('point.' + flavor)
    entry = app / ('main.' + flavor)
    model_src, source, field = {
        'php': ('struct Point { public int $x; public function getX(): int { return $this->x; } }',
                '$p Point; $q=new Point(); $p->x=7; $q->x=9; echo $p->getX(),$q->getX(),other($q);', '$this->x'),
        'cpp': ('struct Point { public: int x; int getX() { return this->x; } };',
                'Point p; auto q=new Point(); p->x=7; q->x=9; echo p->getX(),q->getX(),other(q);', 'this->x'),
        'js': ('struct Point { x:int; getX():int { return this.x; } }',
               'let p:Point; let q=new Point(); p.x=7; q.x=9; echo p.getX(),q.getX(),other(q);', 'this.x'),
    }[flavor]
    # Field and method names may coincide. A nested inline receiver and a fresh shared temporary use the same method.
    model_src = model_src.replace(' getX()', ' x()')
    # Keep the cross-unit method name getX as well; it deliberately calls a free function.
    extra = {'php': 'public function getX(): int { return passthrough($this->x); }',
             'cpp': 'int getX() { return passthrough(this->x); }',
             'js': 'getX():int { return passthrough(this.x); }'}[flavor]
    close = model_src.rindex('}')
    model_src = model_src[:close] + extra + model_src[close:]
    nested = {'php': 'struct Box { public Point $point; } $b Box; echo $b->point->x(),(new Point())->x();',
              'cpp': 'struct Box { Point point; }; Box b; echo b->point->x(),(new Point())->x();',
              'js': 'struct Box { point:Point; } let b:Box; echo b.point.x(),(new Point()).x();'}[flavor]
    source = nested + source
    # A foreign caller and a second receiver type with the same method name.
    foreign = app / ('foreign.cpp' if flavor == 'php' else 'foreign.php')
    if foreign.suffix == '.cpp':
        other = 'struct Other { int getX() { return 4; } }; int other(shared<Point> p) { Other x; return p->getX()+x->getX(); }'
    else:
        other = 'struct Other { public function getX(): int { return 4; } } function other(shared<Point> $p): int { $x Other; return $p->getX()+$x->getX(); }'
    free = ('int passthrough(int n) { return n; }' if foreign.suffix == '.cpp'
            else 'function passthrough(int $n): int { return $n; }')
    foreign.write_text(other + free)
    model.write_text(model_src)
    entry.write_text(source + 'return 8;')
    out = work / ('out-' + flavor)
    empty = {'php': '$q shared<Point>; echo $q->getX();', 'cpp': 'shared<Point> q; echo q->getX();',
             'js': 'let q:shared<Point>; echo q.getX();'}[flavor]
    revisions = [(None, '', None, b'007913'), (model, model_src.replace(field, field+'+1'), None, b'1181014'),
                 (model, model_src.replace('getX', 'x'), 'Duplicate method: Point::x', None),
                 (model, model_src.replace('getX', 'readX'), 'Unknown method Point::getX', None),
                 (model, model_src, None, b'007913'),
                 (entry, source.replace('getX()', 'getX(3)')+'return 8;', 'Argument count mismatch', None),
                 (model, model_src.replace(field, field.replace('x', 'missing')), 'Unknown field Point.missing', None),
                 (model, model_src, 'Argument count mismatch', None),
                 (entry, empty+'return 8;', None, None), (entry, source+'return 8;', None, b'007913')]
    methods, calls, empty_call = {
        'php': ('public function add(int $a,int $b): int { $this->x=$this->x+$a+$b; return $this->x; } '
                'public function setX(int $x): void { $this->x=$x; } public function label(string $s): string { return $s; }',
                '$p=new Point(); $p->setX(7); echo $p->add(replacePoint($p),2),":",$p->x,$p->label("!"); '
                '$box=new Holder(); echo $box->p->add(replaceHolder($box),2),":",$box->p->x;',
                '$p shared<Point>; echo $p->add(side(),side());'),
        'cpp': ('int add(int a,int b) { this->x=this->x+a+b; return this->x; } '
                'void setX(int x) { this->x=x; } string label(string s) { return s; }',
                'auto p=new Point(); p->setX(7); echo p->add(replacePoint(p),2),":",p->x,p->label("!"); '
                'auto box=new Holder(); echo box->p->add(replaceHolder(box),2),":",box->p->x;',
                'shared<Point> p; echo p->add(side(),side());'),
        'js': ('add(a:int,b:int):int { this.x=this.x+a+b; return this.x; } '
               'setX(x:int):void { this.x=x; } label(s:string):string { return s; }',
               'let p=new Point(); p.setX(7); echo p.add(replacePoint(p),2),":",p.x,p.label("!"); '
               'let box=new Holder(); echo box.p.add(replaceHolder(box),2),":",box.p.x;',
               'let p:shared<Point>; echo p.add(side(),side());'),
    }[flavor]
    (app / 'helpers.php').write_text(
        'struct Holder { public Point $p; } '
        'function replacePoint(shared<Point> &$p):int { $p=new Point(); return 3; } '
        'function replaceHolder(shared<Holder> &$h):int { $h=new Holder(); return 6; } '
        'function side():int { echo "SIDE"; return 1; }')
    expanded = model_src[:close] + methods + model_src[close:]
    param, reference, default = {
        'php': ('setX(int $x)', 'setX(int &$x)', 'setX(int $x=0)'),
        'cpp': ('setX(int x)', 'setX(int &x)', 'setX(int x=0)'),
        'js': ('setX(x:int)', 'setX(&x:int)', 'setX(x:int=0)'),
    }[flavor]
    revisions.extend([
        (model, expanded, None, b'007913'),
        (entry, calls+'return 8;', None, b'12:0!8:0'),
        (model, expanded.replace(param, reference), 'a named local or parameter', None),
        (model, expanded.replace(param, default), None, b'12:0!8:0'),
        (model, expanded.replace('add(', 'renamed('), 'Unknown method Point::add', None),
        (None, '', 'Unknown method Point::add', None),
        (model, expanded, None, b'12:0!8:0'),
        (entry, empty_call+'return 8;', None, None),
        (entry, calls+'return 8;', None, b'12:0!8:0'),
    ])
    first, second, constructor, caller = {
        'php': ('int $a', 'int $b', 'public function __construct(int $x=0) {}',
                '$p Point; echo $p->add(1),":"; $q=new Point(); echo $q->add(1,2);'),
        'cpp': ('int a', 'int b', 'Point(int x=0) {}',
                'Point p; echo p->add(1),":"; auto q=new Point(); echo q->add(1,2);'),
        'js': ('a:int', 'b:int', 'constructor(x:int=0) {}',
               'let p:Point; echo p.add(1),":"; let q=new Point(); echo q.add(1,2);'),
    }[flavor]
    optional = expanded.replace(second+')', second+'=0)')
    edited = optional.replace(second+'=0)', second+'=5)')
    revisions.extend([
        (model, optional, None, b'12:0!8:0'),
        (entry, caller+'return 8;', None, b'1:3'),
        (model, edited, None, b'6:3'),
        (model, expanded, 'Argument count mismatch', None),
        (model, expanded.replace(first+',', first+'=1,'), 'Required parameter follows optional parameter', None),
        (model, expanded.replace(second+')', second+'=side())'), 'Parameter defaults require literals', None),
        (model, edited[:close]+constructor+edited[close:], 'Constructors require by-value parameters without defaults', None),
        (model, edited, None, b'6:3'),
    ])
    ref_method, ref_call, slot, ref_param, value_param, field_arg, wrong_arg = {
        'php': ('public function bump(int &$slot,int $delta=2):void { $slot=$slot+$delta; }',
                'function relay(shared<Point> $p,int &$n):void { $p->bump($n,3); } '
                '$p=new Point(); $n=4; $p->bump($n); echo $n,":"; relay($p,$n); echo $n;',
                '$n', 'int &$slot', 'int $slot', '$p->x', '$b byte=4;'),
        'cpp': ('void bump(int &slot,int delta=2) { slot=slot+delta; }',
                'void relay(shared<Point> p,int &n) { p->bump(n,3); } '
                'auto p=new Point(); int n=4; p->bump(n); echo n,":"; relay(p,n); echo n;',
                'n', 'int &slot', 'int slot', 'p->x', 'byte b=4;'),
        'js': ('bump(&slot:int,delta:int=2):void { slot=slot+delta; }',
               'function relay(p:shared<Point>,&n:int):void { p.bump(n,3); } '
               'let p=new Point(); let n:int=4; p.bump(n); echo n,":"; relay(p,n); echo n;',
               'n', '&slot:int', 'slot:int', 'p.x', 'let b:byte=4;'),
    }[flavor]
    ref_model = edited[:close] + ref_method + edited[close:]
    ref_constructor = constructor.replace(default.removeprefix('setX'), reference.removeprefix('setX'))

    def with_argument(argument):
        return ref_call.replace('bump('+slot+')', 'bump('+argument+')')+'return 8;'
    revisions.extend([
        (model, ref_model, None, b'6:3'),
        (entry, with_argument(slot), None, b'6:9'),
        (model, ref_model.replace(ref_param, value_param), None, b'4:4'),
        (model, ref_model, None, b'6:9'),
        (entry, with_argument(field_arg), 'a named local or parameter', None),
        (entry, wrong_arg+with_argument('$b' if flavor == 'php' else 'b'), 'exact storage type', None),
        (entry, with_argument(slot), None, b'6:9'),
        (model, ref_model[:close]+ref_constructor+ref_model[close:], 'Constructors require by-value parameters without defaults', None),
        (model, ref_model.replace(ref_param, ref_param+'=0'), 'Reference parameter defaults are deferred', None),
        (model, ref_model, None, b'6:9'),
    ])
    # Copy both receiver forms. Mutate the scalar, then mutate and release the shared child via separate handles.
    copy_record, inline, alloc, define, var, member = {
        'php': ('struct Pair { public int $x; public shared<Point> $child; '
                'public function copy(): Pair { return $this; } }', '$i Pair;', '$s=new Pair();', '', '$', '->'),
        'cpp': ('struct Pair { int x; shared<Point> child; Pair copy() { return this; } };',
                'Pair i;', 'auto s=new Pair();', 'auto ', '', '->'),
        'js': ('struct Pair { x:int; child:shared<Point>; copy():Pair { return this; } }',
               'let i:Pair;', 'let s=new Pair();', 'let ', '', '.'),
    }[flavor]
    statements = [inline, alloc]
    for receiver, dest in [('i', 'a'), ('s', 'b')]:
        r, c = var+receiver, var+dest
        child, copied = r+member+'child', c+member+'child'
        statements.extend([
            f'{r}{member}x=7; {child}=new Point(); {define}{c}={r}{member}copy();',
            f'{c}{member}x=9; echo {r}{member}x,{c}{member}x;',
            f'if ({child} === {copied}) {{ echo "I"; }}',
            f'{copied}{member}x=3; echo {child}{member}x;',
            f'{child}=null; echo {copied}{member}x;',
        ])
    copied_output = b'79I3379I33'
    copy_model = ref_model + copy_record
    revisions.extend([
        (model, copy_model, None, b'6:9'),
        (entry, ''.join(statements)+'return 8;', None, copied_output),
        (model, ref_model + copy_record.replace('Pair copy()', 'shared<Pair> copy()')
         .replace('): Pair', '): shared<Pair>').replace('):Pair', '):shared<Pair>'),
         'conversion from Pair to shared<Pair>', None),
        (model, ref_model + copy_record.replace('copy()', 'renamed()'), 'Unknown method Pair::copy', None),
        (model, copy_model, None, copied_output),
    ])
    prop, advance, usage, absent_this, bad_instance = {
        'php': ('public static int $total=0;',
                'public static function advance(int &$n,int $step=2):int { $n=$n+$step; Counter::$total=$n; return $n; }',
                '$n=1; Counter::advance($n); echo Counter::advance($n,4),$n,Counter::$total;',
                '$this->total', '$p=new Counter(); $n=1; $p->advance($n);'),
        'cpp': ('static int total=0;',
                'static int advance(int &n,int step=2) { n=n+step; Counter::total=n; return n; }',
                'int n=1; Counter::advance(n); echo Counter::advance(n,4),n,Counter::total;',
                'this->total', 'auto p=new Counter(); int n=1; p->advance(n);'),
        'js': ('static total:int=0;',
               'static advance(&n:int,step:int=2):int { n=n+step; Counter.total=n; return n; }',
               'let n:int=1; Counter.advance(n); echo Counter.advance(n,4),n,Counter.total;',
               'this.total', 'let p=new Counter(); let n:int=1; p.advance(n);'),
    }[flavor]
    # Put the type and the caller in separate units, with a stable method key across the mode edit.
    static_model = copy_model + 'struct Counter { ' + prop + advance + ' }'
    shadow = ''
    if flavor == 'js':
        shadow = ('struct OtherCounter { advance():int { return 9; } } '
                  'function localTest():int { let Counter:OtherCounter; return Counter.advance(); } '
                  'function paramTest(Counter:shared<OtherCounter>):int { return Counter.advance(); } '
                  'if (localTest()+paramTest(new OtherCounter()) != 18) { return 1; } ')
    static_call = shadow + usage + 'return 8;'
    revisions.extend([
        (model, static_model, None, copied_output),
        (entry, static_call, None, b'777'),
        (model, static_model.replace(advance, advance.replace('static ', '', 1)), 'Instance method requires a receiver', None),
        (None, '', 'Instance method requires a receiver', None),
        (model, static_model, None, b'777'),
        (entry, bad_instance+'return 8;', 'Static method requires a type-qualified call', None),
        (entry, static_call, None, b'777'),
        (model, static_model.replace(advance, advance[:advance.rindex('return ')]+'return '+absent_this+'; }'),
         'Static method has no this receiver', None),
        (model, static_model, None, b'777'),
    ])
    factory, factory_call = {
        'php': ('public static function make():Counter { $x Counter; return $x; }', 'Counter::make();'),
        'cpp': ('static Counter make() { Counter x; return x; }', 'Counter::make();'),
        'js': ('static make():Counter { let x:Counter; return x; }', 'Counter.make();'),
    }[flavor]
    foreign_namespace = app / 'namespace.php'
    factory_model = static_model[:static_model.rindex('}')] + factory + ' }'
    revisions.extend([
        (model, factory_model, None, b'777'),
        (entry, factory_call + static_call, None, b'777'),
        (foreign_namespace, 'namespace Counter { function advance(int &$n,int $step=2):int { return 9; } }',
         None, b'777' if flavor == 'php' else b'910'),
        (foreign_namespace, '', None, b'777'),
    ])
    # A same-spelled namespace function must never capture a declaring-type call.
    (app / 'collision.php').write_text('namespace Meter { function tick():int { return 99; } }')
    lexical_model, lexical_entry, qualifier, storage = {
        'php': ('struct Meter { public const int STEP=2; public static int $total=0; '
                'public function __construct() { self::tick(); } '
                'public static function tick():int { self::$total=self::$total+self::STEP; return self::$total; } '
                'public function observe():int { return self::tick(); } }',
                '$m=new Meter(); echo $m->observe();', 'self::', '$total'),
        'cpp': ('struct Meter { static constexpr int STEP=2; static int total=0; '
                'Meter() { Self::tick(); } '
                'static int tick() { Self::total=Self::total+Self::STEP; return Self::total; } '
                'int observe() { return Self::tick(); } };',
                'auto m=new Meter(); echo m->observe();', 'Self::', 'total'),
        'js': ('struct Meter { const STEP:int=2; static total:int=0; '
               'constructor() { class.tick(); } '
               'static tick():int { class.total=class.total+class.STEP; return class.total; } '
               'observe():int { let Meter:int=9; return class.tick()+Meter; } }',
               'let m=new Meter(); echo m.observe();', 'class.', 'total'),
    }[flavor]
    value = b'13' if flavor == 'js' else b'4'
    lexical_source = lexical_entry + 'return 8;'
    static_tick = {'php': 'static function tick', 'cpp': 'static int tick', 'js': 'static tick'}[flavor]
    revisions.extend([
        (model, factory_model + lexical_model, None, b'777'),
        (entry, lexical_source, None, value),
        (model, factory_model + lexical_model.replace('=2;', '=3;'), None, b'15' if flavor == 'js' else b'6'),
        (model, factory_model + lexical_model.replace(static_tick, static_tick[7:]), 'Instance method requires a receiver', None),
        (model, factory_model + lexical_model, None, value),
        (entry, 'echo '+qualifier+storage+'; return 8;', 'Declaring-type qualifier requires a struct body', None),
        (entry, lexical_source, None, value),
    ])
    vault, setup, internal, external, outsider = {
        'php': ('struct Vault { public int $n; private function readN():int { return $this->n; } '
                'public function pair(shared<Vault> $v):int { return $this->readN()*10+$v->readN(); } }',
                '$a Vault; $b=new Vault(); $a->n=4; $b->n=7;', '$a->pair($b)', '$a->readN()',
                'struct Outsider { public function peek(shared<Vault> $v):int { return $v->readN(); } }'),
        'cpp': ('struct Vault { int n; private: int readN() { return this->n; } '
                'public: int pair(shared<Vault> v) { return this->readN()*10+v->readN(); } };',
                'Vault a; auto b=new Vault(); a->n=4; b->n=7;', 'a->pair(b)', 'a->readN()',
                'struct Outsider { int peek(shared<Vault> v) { return v->readN(); } };'),
        'js': ('struct Vault { n:int; private readN():int { return this.n; } '
               'public pair(v:shared<Vault>):int { return this.readN()*10+v.readN(); } }',
               'let a:Vault; let b=new Vault(); a.n=4; b.n=7;', 'a.pair(b)', 'a.readN()',
               'struct Outsider { peek(v:shared<Vault>):int { return v.readN(); } }'),
    }[flavor]
    private_model = factory_model + lexical_model + vault
    public_model = private_model.replace('private', 'public')
    inside = setup + 'echo '+internal+'; return 8;'
    outside = setup + 'echo '+internal+','+external+'; return 8;'
    denied = 'Cannot call private method Vault::readN'
    revisions.extend([
        (model, public_model, None, value),
        (entry, outside, None, b'474'),
        (model, private_model, denied, None), (None, '', denied, None),
        (model, public_model, None, b'474'),
        (entry, inside, None, b'47'), (model, private_model, None, b'47'),
        (model, private_model+outsider, denied, None), (model, private_model, None, b'47'),
    ])
    # Make the external read and write fail independently by editing only the declaring file.
    field, pair_method, declare, check, read, write = {
        'php': ('public int $n;', 'public function pair(shared<Vault> $v):int { $this->n=4; $v->n=7; return $this->n*10+$v->n; }',
                '$a Vault; $b=new Vault();', '$a->pair($b)', '$a->n', '$b->n=9;'),
        'cpp': ('public: int n;', 'public: int pair(shared<Vault> v) { this->n=4; v->n=7; return this->n*10+v->n; }',
                'Vault a; auto b=new Vault();', 'a->pair(b)', 'a->n', 'b->n=9;'),
        'js': ('public n:int;', 'public pair(v:shared<Vault>):int { this.n=4; v.n=7; return this.n*10+v.n; }',
               'let a:Vault; let b=new Vault();', 'a.pair(b)', 'a.n', 'b.n=9;'),
    }[flavor]
    model_public = factory_model + lexical_model + 'struct Vault { '+field+pair_method+' }'
    model_private = model_public.replace(field, field.replace('public', 'private'), 1)
    pair_test = declare + 'echo '+check+';'
    denied_field = 'Cannot access private field Vault::n'
    revisions.extend([
        (model, model_public, None, b'47'),
        (entry, pair_test+'echo '+read+'; return 8;', None, b'474'),
        (model, model_private, denied_field, None), (None, '', denied_field, None),
        (model, model_public, None, b'474'),
        (entry, pair_test+write+'return 8;', None, b'47'),
        (model, model_private, denied_field, None), (model, model_public, None, b'47'),
        (entry, pair_test+'return 8;', None, b'47'), (model, model_private, None, b'47'),
    ])
    # A zero-parameter mutator must receive the original inline storage.
    # Fold this control into the existing incremental/fresh revision sequence.
    receiver_model = ('struct CounterValue { public int $n; public function bump():void { $this->n+=1; } '
                      'public function twice():void { $this->bump(); $this->bump(); } '
                      'public static function make():CounterValue { $c CounterValue; return $c; } } '
                      'struct Wrapper { public CounterValue $counter; }')
    receiver_setup = {'php': '$c CounterValue; $w Wrapper; $owner=new Wrapper();',
                      'cpp': 'CounterValue c; Wrapper w; auto owner=new Wrapper();',
                      'js': 'let c:CounterValue; let w:Wrapper; let owner=new Wrapper();'}[flavor]
    c, w, owner = [var + n for n in ('c', 'w', 'owner')]
    inner, shared = w + member + 'counter', owner + member + 'counter'
    temporary = 'CounterValue.make()' if flavor == 'js' else 'CounterValue::make()'
    receiver_calls = (receiver_setup + f'{c}{member}twice(); {inner}{member}bump(); {shared}{member}bump(); '
                      f'{temporary}{member}bump(); echo {c}{member}n,{inner}{member}n,{shared}{member}n; return 8;')
    receiver_file = app / 'receivers.php'
    revisions.extend([(receiver_file, receiver_model, None, b'47'), (entry, receiver_calls, None, b'211')])
    with service(app, out) as resident:
        for rev, (path, text, error, stdout) in enumerate(revisions):
            if path:
                path.write_text(text)
                os.utime(path, (1980000000 + rev,) * 2)
            before = artifacts(out)
            current = invoke(resident)
            baseline = work / f'fresh-{flavor}-{rev}'
            with service(app, baseline) as fresh:
                expected = invoke(fresh)
                fresh.stdin.close()
            if error:
                assert error in current.get('error', '') and expected['error'] == current['error'], (flavor, rev, current, expected)
                assert before == artifacts(out)
                continue
            assert 'error' not in current and 'error' not in expected, (flavor, rev, current, expected)
            assert artifacts(out) == artifacts(baseline)
            if text == receiver_calls:
                code = b'\n'.join(artifacts(out).values())
                assert b'method_12_CounterValue_twice(var_c_0);' in code
                assert b'method_12_CounterValue_bump(var_w_1.counter);' in code
                assert b'method_12_CounterValue_bump(scpp_self);' in code
                assert b'auto receiver_owner = var_owner_2;' in code
                assert b'auto&& receiver_payload = method_12_CounterValue_make();' in code
            if stdout is None:
                check_empty_access(out, work / f'empty-{flavor}-{rev}')
            else:
                build(out, work / ('native-' + flavor), stdout)
                build(baseline, work / f'fresh-native-{flavor}-{rev}', stdout)
        resident.stdin.close()


def value_signatures(work, flavor):
    app = work / ('values-' + flavor)
    app.mkdir()
    model, api, entry = [app / (n + '.' + flavor) for n in ('record', 'transform', 'main')]
    (app / 'module.json').write_text(json.dumps({'entry': entry.name}))
    param, byref, var, access, local, inferred, method, function, ctor = {
        'php': ('Packet $p', 'Packet &$p', '$', '->', '$NAME TYPE;', '$NAME=EXPR;',
                'public function shift(ARG): Packet', 'function NAME(ARG): Packet', 'new Cell()'),
        'cpp': ('Packet p', 'Packet &p', '', '->', 'TYPE NAME;', 'auto NAME=EXPR;',
                'Packet shift(ARG)', 'Packet NAME(ARG)', 'new Cell()'),
        'js': ('p:Packet', '&p:Packet', '', '.', 'let NAME:TYPE;', 'let NAME=EXPR;',
               'shift(ARG):Packet', 'function NAME(ARG):Packet', 'new Cell()'),
    }[flavor]
    field, edge = {'php': ('public int $x', 'public shared<Cell> $child'),
                   'cpp': ('int x', 'shared<Cell> child'), 'js': ('x:int', 'child:shared<Cell>')}[flavor]
    extra = {'php': 'public string $label;', 'cpp': 'string label;', 'js': 'label:string;'}[flavor]
    record = 'struct Cell { ' + field + '; } struct Packet { ' + field + '=3; ' + edge + '; }'
    def declare(name, typ):
        return local.replace('NAME', name).replace('TYPE', typ)
    def bind(name, expression):
        return inferred.replace('NAME', name).replace('EXPR', expression)
    p = var + 'p'
    x, child = access + 'x', access + 'child'
    make = (function.replace('NAME', 'makePacket').replace('ARG', '') + '{' + declare('p', 'Packet')
            + p + child + '=' + ctor + ';return ' + p + ';}')
    shift = method.replace('ARG', param) + '{' + p + x + '+=1;' + p + child + x + '=9;return ' + p + ';}'
    transform = (function.replace('NAME', 'shifted').replace('ARG', param) + '{' + declare('worker', 'Worker')
                 + p + x + '+=1;return ' + var + 'worker' + access + 'shift(' + p + ');}')
    api.write_text(make + transform)
    methods = app / ('worker.' + flavor)
    methods.write_text('struct Worker {' + shift + '}')
    # The entry never names Packet or Cell. Their types arrive only through callable signatures.
    q = var + 'q'
    entry.write_text(bind('p', 'makePacket()') + bind('q', 'shifted(' + p + ')')
                     + 'echo ' + p + x + ',' + q + x + ',' + p + child + x + ';'
                     + 'if (' + p + child + ' === ' + q + child + ') { return 8; } return 1;')
    out = work / ('values-out-' + flavor)
    with service(app, out) as resident:
        for rev, (path, text, error, stdout) in enumerate([
            (model, record, None, b'359'),
            (model, record.replace('=3;', '=6;' + extra), None, b'689'),
            (api, make + transform.replace('shift(' + p + ')', 'shift(' + var + 'worker)'), 'conversion from Worker to Packet', None),
            (api, make + transform, None, b'689'),
            (model, record.split('struct Packet')[0], 'type: Packet', None),
            (model, record, None, b'359'),
            (methods, 'struct Worker {' + shift.replace(param, byref) + '}', 'Writable inline struct parameters', None),
            (methods, 'struct Worker {' + shift + '}', None, b'359'),
        ]):
            path.write_text(text)
            os.utime(path, (1989200000 + rev,) * 2)
            previous = artifacts(out)
            current = invoke(resident)
            baseline = work / f'values-fresh-{flavor}-{rev}'
            with service(app, baseline) as fresh:
                other = invoke(fresh)
                fresh.stdin.close()
            if error:
                assert error in current.get('error', '') and current['error'] == other.get('error'), (rev, current, other)
                assert artifacts(out) == previous
                continue
            assert 'error' not in current and 'error' not in other, (rev, current, other)
            assert artifacts(out) == artifacts(baseline)
            build(out, work / ('values-native-' + flavor), stdout)
            build(baseline, work / f'values-fresh-native-{flavor}-{rev}', stdout)
        resident.stdin.close()


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory() as tmp:
        work = (args.output or Path(tmp)).resolve()
        work.mkdir(exist_ok=True)
        for flavor in ('php', 'cpp', 'js'):
            value_signatures(work, flavor)
            prove(work, flavor)
            print(flavor, 'passed', flush=True)
