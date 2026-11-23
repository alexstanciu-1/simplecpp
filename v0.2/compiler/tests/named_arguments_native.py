"""Direct by-value call labels, written evaluation order, defaults and resident rename recovery."""
from pathlib import Path
from tempfile import TemporaryDirectory

from classes_native import fixture
from collection_goal_native import guard, revisions


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


if __name__ == '__main__':
    with TemporaryDirectory(prefix='scpp-named-arguments-') as tmp:
        for flavor in ('php', 'cpp', 'js'):
            prove(Path(tmp), flavor)
        revisions(Path(tmp), 'py', {'main': 'return difference(right=3, left=11)',
                  'target': 'def difference(left: int, right: int) -> int:\n    return left - right'}, [
                      (None, None, b''), ('target', 'def difference(left: int, offset: int) -> int:\n    return left - offset', 'Unknown named argument: right'),
                      ('main', 'return difference(offset=3, left=11)', b'')])

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
