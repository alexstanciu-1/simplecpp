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


if __name__ == '__main__':
    with TemporaryDirectory(prefix='scpp-named-arguments-') as tmp:
        for flavor in ('php', 'cpp', 'js'):
            prove(Path(tmp), flavor)
        revisions(Path(tmp), 'py', {'main': 'return difference(right=3, left=11)',
                  'target': 'def difference(left: int, right: int) -> int:\n    return left - right'}, [
                      (None, None, b''), ('target', 'def difference(left: int, offset: int) -> int:\n    return left - offset', 'Unknown named argument: right'),
                      ('main', 'return difference(offset=3, left=11)', b'')])
