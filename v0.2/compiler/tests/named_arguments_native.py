"""Direct by-value call labels, written evaluation order, defaults and resident rename recovery."""
from pathlib import Path
from tempfile import TemporaryDirectory

from classes_native import fixture
from collection_goal_native import guard, revisions
from protected_static_native import sources as static_sources


def prove(work, flavor):
    forms = fixture(flavor)
    fn, v = forms['function'], forms['v']
    difference = fn('difference', 'int', [('left', 'int'), ('right', 'int')],
                    f'return {v}left - {v}right;')
    defaults = fn('digits', 'int', [('a', 'int'), ('b', 'int'), ('c', 'int')],
                  f'return {v}a * 100 + {v}b * 10 + {v}c;')
    defaults = defaults.replace('b:int' if flavor == 'js' else 'int ' + v + 'b',
                                'b:int=2' if flavor == 'js' else 'int ' + v + 'b=2')
    defaults = defaults.replace('c:int' if flavor == 'js' else 'int ' + v + 'c',
                                'c:int=3' if flavor == 'js' else 'int ' + v + 'c=3')
    effects = fn('nextRight', 'int', [], 'echo "R"; return 3;') + fn('nextLeft', 'int', [], 'echo "L"; return 10;')
    main = ('echo difference(right: nextRight(), left: nextLeft()),'
            'digits(4, c: 9),difference(left: 20, right: 3);return 8;')
    answer = b'RL742917'
    revisions(work, flavor, {'main': main, 'target': difference, 'defaults': defaults, 'effects': effects,
                             'plain': fn('plain', 'int', [], 'return difference(9, 2);')}, [
        (None, None, answer), (None, None, answer),
        ('main', main.replace('left: nextLeft()', 'right: nextLeft()'), 'Parameter supplied twice'),
        ('main', 'return digits(1, a: 2);', 'Parameter supplied twice'),
        ('main', 'return digits(b: 5);', 'Missing required argument: a'),
        ('main', 'return digits(a: 1, 5);', 'Positional argument after named argument'),
        ('main', 'return strlen(text: "a");', 'Named arguments require a direct source function'),
        ('main', main, answer),
        ('target', difference.replace('right', 'offset'), 'Unknown named argument: right'),
        ('main', main.replace('right:', 'offset:'), answer),
    ], unaffected_source='plain.' + flavor, unaffected_revisions={9})

    if flavor == 'php':
        ref = work / 'by-reference'
        ref.mkdir()
        revisions(ref, flavor, {'main': '$v int=8; return read(slot: $v);',
                                'source': 'function read(int &$slot):int {return $slot;}'}, [
                                    (None, None, 'Named arguments require by-value parameters'),
                                    ('main', '$v int=8; return read($v);', b'')])
        guard(work, flavor, 'conversion-order',
              'function later():int {echo "L";return 1;} '
              'function large():int {echo "R";return 999;} '
              'function useByte(int $left, byte $right):int {return $left;} '
              'return useByte(right: large(), left: later());',
              'integer_cast_out_of_range', expected_stdout=b'R')


def prove_methods(work, flavor):
    if flavor == 'py':
        target = ('class Writer:\n'
                  '    def write(self, offset: int, count: int = 2) -> int:\n'
                  '        return offset * 10 + count')
        effects = 'def writer() -> Writer:\n    return Writer()'
        main = ('w: Writer = writer()\n'
                'if w.write(count=3, offset=4) != 43:\n    return 1\n'
                'return w.write(0, 8)')
        answer = b''
    else:
        forms = fixture(flavor)
        fn, v, access = forms['function'], forms['v'], forms['access']
        signature = {'php': 'public function write(int $offset, int $count=2):int',
                     'cpp': 'public: int write(int offset, int count=2)',
                     'js': 'write(offset:int, count:int=2):int'}[flavor]
        target = forms['klass']('Writer', signature + f' {{return {v}offset * 10 + {v}count;}}')
        effects = (fn('writer', 'Writer', [], 'echo "W"; return new Writer();')
                   + fn('first', 'int', [], 'echo "C"; return 3;')
                   + fn('second', 'int', [], 'echo "O"; return 4;'))
        main = (f'echo writer(){access}write(count: first(), offset: second());'
                + forms['infer']('w', 'new Writer()') + f'echo {v}w{access}write(offset: 1);'
                + f'return {v}w{access}write(0, 8);')
        answer = b'WCO4312'
    edits = [(None, None, answer), (None, None, answer),
             ('target', target.replace('offset', 'start'), 'Unknown named argument: offset'),
             ('main', main.replace('offset', 'start'), answer)]
    if flavor == 'php':
        edits.append(('target', target.replace('offset', 'start').replace('public function', 'public virtual function'),
                      'Named arguments require a direct source function'))
        by_reference = target.replace('offset', 'start').replace('int $start', 'int &$start')
        edits.append(('target', by_reference, 'Named arguments require by-value parameters'))
        interface = ('interface Output {public function write(int $offset, int $count=2):int;} '
                     + target.replace('class Writer', 'class Writer implements Output')
                             .replace('public function', 'public override function'))
        edits.extend([
            ('target', interface, 'Named arguments require a direct source function'),
            ('main', 'function emit(Output $out):int {return $out->write(offset: 1);} return 8;',
             'Named arguments require a direct source function'),
        ])
    revisions(work, flavor, {'main': main, 'target': target, 'effects': effects}, edits)


def prove_static_methods(work, flavor):
    forms = fixture(flavor)
    fn, v = forms['function'], forms['v']
    access = '.' if flavor == 'js' else '::'
    signature = {'php': 'public static function make(int $offset, int $count=2):int',
                 'cpp': 'public: static int make(int offset, int count=2)',
                 'js': 'static make(offset:int, count:int=2):int'}[flavor]
    base = forms['klass']('Window', signature + f' {{return {v}offset * 10 + {v}count;}}')
    child = {'cpp': 'class Child : public Window {};', 'php': 'class Child extends Window {}',
             'js': 'class Child extends Window {}'}[flavor]
    effects = (fn('countFirst', 'int', [], 'echo "C";return 3;')
               + fn('offsetSecond', 'int', [], 'echo "O";return 4;'))
    positional = fn('plain', 'int', [], f'return Child{access}make(0, 8);')
    main = (f'echo Child{access}make(count: countFirst(), offset: offsetSecond()),'
            f'Window{access}make(offset: 1); return plain();')
    renamed = base.replace('offset', 'start')
    edits = [(None, None, b'CO4312'), (None, None, b'CO4312'),
             ('base', renamed, 'Unknown named argument: offset'),
             ('main', 'return plain();', b''),
             ('main', main.replace('offset:', 'start:'), b'CO4312')]
    collision = 'namespace Window {' + fn('make', 'int', [('start', 'int')], 'return 66;') + '}'
    edits.extend([('collision', collision, b'CO4312' if flavor == 'php' else b'CO4366'),
                  ('collision', None, b'CO4312')])
    if flavor == 'php':
        edits.append(('main', '$w=new Child();return $w::make(start: 1);',
                      'Named arguments require a direct source function'))
    revisions(work, flavor, {'main': main, 'base': base, 'child': child,
                             'effects': effects, 'positional': positional}, edits)


def prove_lexical_static(work, flavor):
    _, (syntax, klass, method, static, sep) = static_sources(flavor)
    v = syntax['v']
    lexical, parent, late = {
        'php': ('self::', 'parent::', 'static::'),
        'cpp': ('Self::', 'Parent::', 'Static::'),
        'js': ('class.', 'super.', 'static::'),
    }[flavor]
    params = [('offset', 'int'), ('count', 'int')]
    make = static('make', f'return {late}tag() + {v}offset * 10 + {v}count;', params)
    root = klass('Root', make + static('tag', 'return 1;') + method('read', 'return 1;', params))
    args = 'count: first(), offset: second()'
    callers = static('run', f'return {lexical}make({args});') + static('fromParent', f'return {parent}make({args});')
    base = klass('Base', callers, 'Root')
    child = klass('Child', static('make', 'return 99;', params) + static('tag', 'return 5;'), 'Base')
    fn = fixture(flavor)['function']
    effects = fn('first', 'int', [], 'echo "C";return 3;') + fn('second', 'int', [], 'echo "O";return 4;')
    main = f'echo Base{sep}run(),Child{sep}run(),Child{sep}fromParent();return 8;'
    answer = b'CO44CO48CO48'
    revisions(work, flavor, dict(main=main, root=root, base=base, child=child, effects=effects), [
        (None, None, answer), (None, None, answer),
        ('base', klass('Base', callers + static('make', 'return 77;', params), 'Root'), b'CO77CO77CO48'),
        ('base', base, answer),
        ('root', root.replace('offset', 'start'), 'Unknown named argument: offset'),
        ('base', base.replace('offset:', 'start:'), answer),
        ('base', base.replace('offset:', 'start:').replace(parent + 'make', parent + 'read'),
         'Named arguments require a direct source function'),
    ])


def prove_late_static(work, flavor):
    _, (syntax, klass, _, static, sep) = static_sources(flavor)
    v = syntax['v']
    late = 'Static::' if flavor == 'cpp' else 'static::'
    params = [('offset', 'int'), ('count', 'int')]
    digits = f'{v}offset * 10 + {v}count'
    base = klass('Base', static('make', f'return {digits};', params)
                 + static('run', f'return {late}make(count: 3, offset: 4);'))
    def child(parameters):
        return klass('Child', static('make', f'return 100 + {digits};', parameters), 'Base')
    original = child(params)
    renamed = original.replace('count', 'length')
    base_only = f'echo Base{sep}run();return 8;'
    both = f'echo Base{sep}run(),Child{sep}run();return 8;'
    error = 'Unknown named argument: count on Child::make'
    revisions(work, flavor, dict(main=both, base=base, child=original), [
        (None, None, b'43143'), (None, None, b'43143'),
        ('child', renamed, error), ('main', base_only, b'43'), ('main', both, error),
        ('child', original, b'43143'), ('child', klass('Child', '', 'Base'), b'4343'),
        ('child', child(list(reversed(params))), b'43143'),
    ])


if __name__ == '__main__':
    with TemporaryDirectory(prefix='scpp-named-arguments-') as tmp:
        for flavor in ('php', 'cpp', 'js'):
            prove(Path(tmp), flavor)
        revisions(Path(tmp), 'py', {'main': 'return difference(right=3, left=11)',
                  'target': 'def difference(left: int, right: int) -> int:\n    return left - right'}, [
                      (None, None, b''), ('target', 'def difference(left: int, offset: int) -> int:\n    return left - offset', 'Unknown named argument: right'),
                      ('main', 'return difference(offset=3, left=11)', b'')])

        static_methods = Path(tmp) / 'static-methods'
        static_methods.mkdir()
        for flavor in ('php', 'cpp', 'js'):
            prove_static_methods(static_methods, flavor)

        lexical_methods = Path(tmp) / 'lexical-methods'
        lexical_methods.mkdir()
        for flavor in ('php', 'cpp', 'js'):
            prove_lexical_static(lexical_methods, flavor)

        late_methods = Path(tmp) / 'late-methods'
        late_methods.mkdir()
        for flavor in ('php', 'cpp', 'js'):
            prove_late_static(late_methods, flavor)

        guard(static_methods, 'php', 'static-conversion-order',
              'class Window {public static function make(int $offset, byte $count):int {echo "X";return 8;}} '
              'function first():int {echo "C";return 999;} function later():int {echo "O";return 1;} '
              'return Window::make(count: first(), offset: later());',
              'integer_cast_out_of_range', expected_stdout=b'C')

        methods = Path(tmp) / 'methods'
        methods.mkdir()
        for flavor in ('php', 'cpp', 'js', 'py'):
            prove_methods(methods, flavor)
        guard(methods, 'php', 'method-conversion-order',
              'class Writer {public function write(int $offset, byte $count):int {echo "X";return 8;}} '
              'function writer():Writer {echo "W";return new Writer();} '
              'function first():int {echo "C";return 999;} function later():int {echo "O";return 1;} '
              'return writer()->write(count: first(), offset: later());',
              'integer_cast_out_of_range', expected_stdout=b'WC')

        retention = Path(tmp) / 'receiver'
        retention.mkdir()
        revisions(retention, 'php', {
            'target': 'class Writer {public int $base=1; public function write(int $offset, int $count):int '
                      '{return $this->base + $offset * 10 + $count;}} '
                      'function replace(Writer &$slot):int {$slot=new Writer(); return 3;} '
                      'enum Kind {case A=1; public function add(int $n):int {return enum_value($this)+$n;}} '
                      'function pick(int &$n):Kind {$n=7;return Kind::A;}',
            'main': '$w=new Writer(); $w->base=9; echo $w->write(count: replace($w), offset: 4),$w->base; '
                    '$n int=0; return pick($n)->add(n: $n);'
        }, [(None, None, b'521')])
