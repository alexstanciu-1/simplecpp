"""Constructor label ownership, ordered base initialization and visible dispatch contracts."""
import argparse
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import guard, revisions
from protected_static_native import sources
from interfaces_native import forms as interface_forms


def constructors(work, flavor, quick=False):
    _, (s, klass, _, static, sep) = sources(flavor)
    v, fn = s['v'], s['function']
    params = {'php': 'int $left,int $middle=5,int $right=7',
              'js': 'left:int,middle:int=5,right:int=7',
              'cpp': 'int left,int middle=5,int right=7'}[flavor]
    def ctor(owner):
        return {'php': 'public function __construct', 'cpp': 'public: '+owner, 'js': 'constructor'}[flavor]
    named = 'right: first(), left: second()'
    effects = fn('first', 'int', [], 'echo "R";return 3;') + fn('second', 'int', [], 'echo "L";return 2;')
    output = f'{{echo {v}left,{v}middle,{v}right;}}'
    late = 'Static' if flavor == 'cpp' else 'static'
    factory = {'php': 'public static function build():Base', 'cpp': 'public: static Base build()',
               'js': 'static build():Base'}[flavor] + f' {{return new {late}({named});}}'
    base = klass('Base', ctor('Base')+'('+params+')'+output+factory)
    inline = s['record']('Point', ctor('Point')+'('+params+')'+output)
    middle = klass('Middle', '', 'Base')
    def child(args):
        init = {'php': '{parent::__construct('+args+');', 'cpp': ': Middle('+args+'){',
                'js': '{super('+args+');'}[flavor]
        return klass('Child', ctor('Child')+'()'+init+'echo "C";}', 'Middle')
    local = {'php': '$p Point', 'cpp': 'Point p', 'js': 'let p:Point'}[flavor]
    main = (s['infer']('b', 'new Middle('+named+')') + local+'('+named+');'
            + s['infer']('c', 'new Child()') + s['infer']('d', 'Middle'+sep+'build()') + 'return 8;')
    trace = b'RL253'
    plain_child = child('2,5,3')
    result = trace*3 + b'C' + trace
    edits = [
        (None, None, trace*2 + b'253C' + trace), ('child', child(named), result),
        ('point', inline.replace('right', 'count'), 'Unknown named argument: right'),
        ('point', inline, result), ('child', plain_child, trace*2 + b'253C' + trace),
        ('child', child(named), result), ('point', None, None), ('point', inline, result),
        ('child', plain_child, trace*2 + b'253C' + trace),
        ('main', s['infer']('d', 'Middle'+sep+'build()') + 'return 8;', trace),
        ('middle', klass('Middle', ctor('Middle')+'('+params.replace('right', 'count')+')'
                         + {'php': '{parent::__construct(0);}', 'cpp': ': Base(0) {}',
                            'js': '{super(0);}'}[flavor], 'Base'), 'Unknown named argument: right on Middle::__construct'),
        ('main', s['infer']('d', 'Base'+sep+'build()') + 'return 8;', trace),
        ('middle', middle, trace), ('main', main, trace*2 + b'253C' + trace),
    ]
    revisions(work, flavor, dict(main=main, base=base, middle=middle, child=plain_child,
                                 effects=effects, point=inline), edits[:4] if quick else edits)


def dispatch(work, flavor):
    s, _, klass, method, interface, implementation = interface_forms(flavor)
    v, access, fn = s['v'], s['access'], s['function']
    def param(name):
        return name+':int' if flavor == 'js' else 'int '+v+name
    base = implementation('Base', method('evaluate', f'return {v}baseValue;', params=param('baseValue')+'=2',
                                         intent='override'))
    parent = {'php': 'parent::', 'js': 'super.', 'cpp': 'Base::'}[flavor]
    child = klass('Child', method('evaluate', f'return {parent}evaluate(baseValue: {v}childValue)+10;',
                                  params=param('childValue')+'=2', intent='override'), 'Base')
    readers = (fn('readBase', 'int', [('obj', 'Base')], f'return {v}obj{access}evaluate(baseValue: 1);')
               + fn('readView', 'int', [('obj', 'Readable')], f'return {v}obj{access}evaluate(delta: 2);'))
    main = s['infer']('obj', 'new Child()') + f'echo readBase({v}obj),readView({v}obj),{v}obj{access}evaluate(childValue: 3);return 8;'
    revisions(work, flavor, dict(main=main, base=base, child=child, view=interface(), readers=readers), [
        (None, None, b'111213'), (None, None, b'111213'),
        ('view', interface().replace('delta', 'amount'), 'Unknown named argument: delta'),
        ('readers', readers.replace('delta:', 'amount:'), b'111213'),
        ('child', child.replace('childValue', 'n'), 'Unknown named argument: childValue'),
        ('main', main.replace('childValue:', 'n:'), b'111213'),
    ])


def python_construction(work):
    base = '''class Base:
    value: int = 0
    def __init__(self, left: int, right: int = 2):
        self.value = left * 10 + right
    @virtual
    def read(self, baseValue: int) -> int:
        return self.value + baseValue'''
    child = '''class Child(Base):
    def __init__(self, first: int):
        super().__init__(right=3, left=first)
    @override
    def read(self, childValue: int) -> int:
        return super().read(baseValue=childValue)'''
    main = '''def probe(obj: Base) -> int:
    return obj.read(baseValue=2)
obj: Child = Child(first=4)
if probe(obj) != 45 or obj.read(childValue=3) != 46:
    return 1
return 8'''
    revisions(work, 'py', dict(main=main, base=base, child=child), [
        (None, None, b''), (None, None, b''),
        ('base', base.replace('right', 'offset'), 'Unknown named argument: right'),
        ('child', child.replace('right=', 'offset='), b''),
        ('child', None, None), ('child', child.replace('right=', 'offset='), b''),
    ])


def references(work):
    api = '''function &pick(string $note, int &$slot):int {echo $note;return $slot;}
        function bump(int &$slot):int {$slot+=1;return $slot;}
        function snapshots(int $before, int &$slot, int $after):void {echo $before,$after;$slot=8;}
        function size(int $add, const vector<int> &$v):int {return count($v)+$add;}'''
    main = '''$n int=2; $a =& pick(slot: $n, note: "A"); $a=4;
        snapshots(before: $n, after: bump(slot: $n), slot: $n);
        $v vector<int> = [1,2];echo size(v: $v, add: 3);return $n;'''
    revisions(work, 'php', dict(main=main, api=api), [
        (None, None, b'A455'), (None, None, b'A455'),
        ('api', api.replace('slot', 'target'), 'Unknown named argument: slot'),
        ('main', main.replace('slot:', 'target:'), b'A455'),
        ('main', main.replace('slot:', 'target:').replace('target: $n, note:', 'target: 1, note:'),
         'Reference argument requires stable variable storage'),
    ])


def inline_lifetime(work):
    point = ('struct Point {public int $value; public function __construct(int $a,int $b)'
             '{$this->value=$a*10+$b;} public function __destruct(){echo "D";}}')
    main = '$p Point(b: mark(2),a: mark(1));echo $p->value;return 8;'
    revisions(work, 'php', dict(main=main, point=point, mark='function mark(int $n):int {echo $n;return $n;}'),
              [(None, None, b'2112D')])


def base_lifetime(work):
    token = 'struct Token {public int $value=4;public function __destruct(){echo "D";}} '
    token += 'class Holder {public Token $item;} '
    base = 'class Values {public function __construct(int $n, Token $t){echo "B",$t->value,$n;}} '
    child = 'class Child extends Values {public function __construct(Holder $h){parent::__construct(t: $h->item,n: mark($h));echo "C";}} '
    main = '$h=new Holder(); $c=new Child($h);return 8;'
    revisions(work, 'php', dict(main=main, types=token+base+child, mark='function mark(Holder $h):int {echo "M";$h->item->value=9;return 1;}'),
              [(None, None, b'MB41DCD')])
    narrow = 'class Base {public function __construct(int $left, byte $right){echo "B";}} '
    call = 'class Child extends Base {public function __construct(){parent::__construct(right: high(),left: later());echo "C";}} '
    helpers = 'function high():int {echo "H";return 999;} function later():int {echo "L";return 1;} '
    guard(work, 'php', 'throw-base', narrow+call+helpers+'$c=new Child();return 8;',
          'integer_cast_out_of_range', expected_stdout=b'H')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--quick", action="store_true")
    quick = parser.parse_args().quick
    with TemporaryDirectory(prefix='scpp-named-targets-') as tmp:
        if quick:
            constructors(Path(tmp), "php", quick=True)
            dispatch_dir = Path(tmp) / "dispatch"
            dispatch_dir.mkdir()
            dispatch(dispatch_dir, "php")
            lifetime_dir = Path(tmp) / "lifetime"
            lifetime_dir.mkdir()
            inline_lifetime(lifetime_dir)
            raise SystemExit(0)
        for proof in (constructors, dispatch):
            work = Path(tmp) / proof.__name__
            work.mkdir()
            for flavor in ('php', 'cpp', 'js'):
                proof(work, flavor)
        for proof in (python_construction, references, inline_lifetime, base_lifetime):
            work = Path(tmp) / proof.__name__
            work.mkdir()
            proof(work)
