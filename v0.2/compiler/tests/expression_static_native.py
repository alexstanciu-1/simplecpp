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


def data_members(work, flavor):
    work.mkdir(exist_ok=True)
    _, (s, klass, _, _, sep) = sources(flavor)
    v, access = s['v'], s['access']
    prop, constant, name = {
        'php': ('public static int $n=1;', 'public const int STEP=2;', '$n'),
        'cpp': ('public: static int n=1;', 'public: static constexpr int STEP=2;', 'n'),
        'js': ('public static n:int=1;', 'public const STEP:int=2;', 'n'),
    }[flavor]
    def provider(owner, step, field=prop):
        return klass(owner, field + constant.replace('2', str(step)))
    box = klass('Box', s['field']('observer', 'weak<Child>'))
    produce = s['function']('produce', 'Child', [('box', 'Box')], 'echo "P";'+s['infer']('p','new Child()')
                            +f'{v}box{access}observer=weak({v}p);return {v}p;')
    rhs = s['function']('rhs', 'int', [('box', 'Box')],
                        f'echo "R";if (lock({v}box{access}observer)!==null) {{return 9;}}return 5;')
    expr = f'produce({v}box)::'
    empty = {'php': '$p ?Child=null;', 'cpp': '?Child p=null;', 'js': 'let p:?Child=null;'}[flavor]
    local = '(p)::' if flavor == 'cpp' else v+'p::'
    main = s['infer']('box','new Box()') + empty + f'{expr}{name}=rhs({v}box);'
    main += f'echo {expr}{name},{expr}STEP,{local}{name},Base{sep}{name},Other{sep}{name};return 8;'
    child = klass('Child', '', 'Base')
    src = dict(base=provider('Base', 2), other=provider('Other', 4), child=child, box=box, helpers=produce+rhs, main=main)
    text_property, text_constant = {
        'php': ('public static string $text="old";', 'public const string LABEL="new";'),
        'cpp': ('public: static string text="old";', 'public: static constexpr string LABEL="new";'),
        'js': ('public static text:string="old";', 'public const LABEL:string="new";'),
    }[flavor]
    text_base = klass('Base', prop+constant+text_property+text_constant)
    text_main = s['infer']('box', 'new Box()') + empty
    text_main += f'{local}{v}text={expr}LABEL;echo {expr}{v}text;return 8;'
    revisions(work, flavor, src, [
        (None, None, b'PRP5P2551'), (None, None, b'PRP5P2551'),
        ('child', klass('Child', '', 'Other'), b'PRP5P4515'),
        ('other', provider('Other', 7), b'PRP5P7515'),
        ('other', provider('Other', 7, ''), 'Unknown static property:' if flavor=='php' else 'Unknown constant:'),
        (None, None, None), ('other', provider('Other', 4), b'PRP5P4515'),
        ('other', provider('Other', 4, prop.replace('public','private')), 'Cannot access private static property'),
        ('other', provider('Other', 4), b'PRP5P4515'),
        ('child', None, None), ('child', child, b'PRP5P2551'),
        ('main', empty + f'{local}STEP=3;return 8;', 'Assignment requires a writable static property'),
        ('main', empty + f'echo match(2) {{{local}STEP=>1,default=>0}};return 8;', 'Match label requires'),
        ('main', main, b'PRP5P2551'),
        ('base', text_base, b'PRP5P2551'), ('main', text_main, b'PPnew'),
        ('base', src['base'], 'Unknown'), ('main', main, b'PRP5P2551'),
    ])
    absent = {'php': '$box ?Box=null;', 'cpp': '?Box box=null;', 'js': 'let box:?Box=null;'}[flavor]
    fail_target = f'({v}box{access}observer)::{name}'
    # On re-entry after the exception, inspect the same native static storage.
    retry_flag = 'Other'+sep+name
    retry = f'if ({retry_flag}==5) {{return 7+Base{sep}{name};}}{retry_flag}=5;'
    defs = src['base']+src['other']+child+box
    rhs_marker = s['function']('unexpected', 'int', [], 'echo "rhs";return 9;')
    guard(work, flavor, 'write-qualifier-failure', defs+rhs_marker+retry+absent+fail_target+'=unexpected();return 8;',
          'ownership_empty_access', retry_entry=True)
    fail_rhs = s['function']('failRhs', 'int', [], absent+'return '+fail_target+';')
    guard(work, flavor, 'write-rhs-failure', defs+fail_rhs+retry+empty+f'{local}{name}=failRhs();return 8;',
          'ownership_empty_access', retry_entry=True)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-expression-static-') as tmp:
        work = args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        for flavor in ['php', 'cpp', 'js']:
            prove(work, flavor)
            data_members(work/"data", flavor)
