"""Declared-type static selection, qualifier lifetime and incremental/fresh equivalence."""
import argparse
import tempfile
from pathlib import Path

from collection_goal_native import guard, revisions
from protected_static_native import sources


def prove(work, flavor):
    _, (s, klass, method, static, _) = sources(flavor)
    v, access = s['v'], s['access']
    fn, infer = s['function'], s['infer']
    args = [('a', 'int'), ('b', 'int')]
    def implementation(owner):
        return static('pick', f'echo "{owner}";return {v}a+{v}b;', args)
    base = klass('Base', implementation('B') + method('ordinary', 'return 1;'))
    child = klass('Child', implementation('C'), 'Base')
    box = klass('Box', s['field']('observer', 'weak<Child>'))
    def factory(result):
        return fn('produce', result, [('box', 'Box')], 'echo "P";'+infer('p', 'new Child()')
                  +f'{v}box{access}observer=weak({v}p);return {v}p;')
    observer = fn('first', 'int', [('box', 'Box')], f'echo "1";if (lock({v}box{access}observer)!==null) {{return 9;}} return 1;')
    second = fn('second', 'int', [], 'echo "2";return 2;')
    local = '(p)' if flavor == 'cpp' else v+'p'
    call = f'produce({v}box)::pick(first({v}box),second())'
    main = infer('box', 'new Box()') + f'echo {call};'
    # An empty typed qualifier selects a method without a presence check.
    main += {'php': '$p ?Base=null;', 'cpp': '?Base p=null;', 'js': 'let p:?Base=null;'}[flavor] + f'{local}::pick(0,0);return 8;'
    src = dict(base=base, child=child, box=box, factory=factory('Base'), args=observer+second, main=main)
    # A weak qualifier is usable even after the last owner dies.
    weak = f'({v}box{access}observer)::pick(0,0);'
    weak_main = main.replace('return 8;', weak + 'return 8;')
    # Inline values can qualify void calls as statements, with ordinary trailing defaults.
    stamp = {'php': 'public static function stamp(int $n=0):void {echo "S";}',
             'cpp': 'public: static void stamp(int n=0) {echo "S";}',
             'js': 'public static stamp(n:int=0):void {echo "S";}'}[flavor]
    src['inline'] = s['record']('Inline', stamp)
    inline = '(i)' if flavor == 'cpp' else v+'i'
    items = {'php': '$items vector<Inline> = [];', 'cpp': 'vector<Inline> items=[];',
             'js': 'let items:vector<Inline> = [];'}[flavor]
    calls = s['local']('i', 'Inline') + f'{inline}::stamp();' + items + f'{v}items[]={v}i;{v}items[0]::stamp();'
    inline_main = main.replace('return 8;', calls+'return 8;')
    rejected_view = 'interface View {}' + (';' if flavor == 'cpp' else '')
    rejected_view += fn('inspect', 'int', [('p','View')], f'return {local}::pick(1,2);')
    revisions(work, flavor, src, [
        (None, None, b'P12B3B'), (None, None, b'P12B3B'),
        ('factory', factory('Child'), b'P12C3B'),
        ('child', klass('Child', '', 'Base'), b'P12B3B'), ('child', child, b'P12C3B'),
        ('factory', None, None), (None, None, None), ('factory', factory('Base'), b'P12B3B'),
        ('main', main.replace(call, f'produce({v}box)::ordinary()'), 'requires a static method'),
        ('main', main.replace(call, 'second()::pick(1,2)'), 'concrete record type'),
        ('main', weak_main, b'P12B3BC'), ('main', inline_main, b'P12B3BSS'),
        ('main', main, b'P12B3B'),
        ('base', base.replace('public','private',1), 'Cannot call private method'),
        ('base', base, b'P12B3B'),
        ('main', main.replace('first('+v+'box),second()', 'second()'), 'Argument count mismatch'),
        ('main', main, b'P12B3B'), ('view', rejected_view, 'concrete record type'), ('view', None, b'P12B3B'),
    ])

    # Dereferencing an empty Box fails during qualifier evaluation. Neither argument nor method may print.
    empty = {'php': '$box ?Box=null;', 'cpp': '?Box box=null;', 'js': 'let box:?Box=null;'}[flavor]
    failing = empty + f'({v}box{access}observer)::pick(second(),second());return 8;'
    guard(work, flavor, 'qualifier-failure', base+child+box+second+failing, 'ownership_empty_access')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-expression-static-') as tmp:
        work = args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        for flavor in ['php', 'cpp', 'js']:
            prove(work, flavor)
