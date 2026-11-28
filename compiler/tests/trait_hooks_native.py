"""Bodyless trait hooks on concrete consumers, including inherited implementations and uncalled requirements."""
from source_suffix import source_suffix
import argparse
import tempfile
from pathlib import Path

from app_namespace_native import artifacts
from collection_goal_native import revisions
from parent_methods_native import syntax


def prove(work, flavor):
    s, _, klass, _, method = syntax(flavor)
    def trait(name, body):
        return f'trait {name} {{\n{body}\n}}' + (';' if flavor == 'cpp' else '')
    def hook(name, visibility='protected', result='int', params=''):
        return {
            'php': f'abstract {visibility} function {name}({params}):{result};',
            'cpp': f'{visibility}: virtual {result} {name}({params}) = 0;',
            'js': f'{visibility} abstract {name}({params}):{result};',
        }[flavor]
    step = method('step', 'return 4;', access='protected')
    idle = method('unused', 'return 2;', access='public')
    def base(implementation):
        return klass('Base', step + implementation)
    def get(name):
        return s['v'] + name + s['access']
    counter = trait('Counter', hook('step') + hook('unused') + method('read', f'return {get("this")}step();'))
    additional = trait('Extra', hook('unused', 'public'))
    local = klass('Local', 'use Counter;' + step.replace('4', '3') + idle)
    supplied = trait('Supply', step.replace('4', '5') + idle)
    def arg(type_name):
        return {'php': f'{type_name} $item', 'cpp': f'{type_name} item', 'js': f'item:{type_name}'}[flavor]
    accept = method('accept', 'return 1;', params=arg('Payload'))
    additional += trait('AcceptsNarrow', hook('accept', params=arg('Narrow')))
    derived = klass('Derived', 'use Counter, Extra, AcceptsNarrow;' + accept, 'Base')
    composed = klass('Composed', 'use Counter, Supply;')
    main = ''.join(s['infer'](n, f'new {t}()') for n, t in [('l', 'Local'), ('d', 'Derived'), ('c', 'Composed')])
    main += f'echo {get("l")}read(),{get("d")}read(),{get("c")}read(); return 8;'
    ok = b'345'
    static_idle = {'php': 'public static function unused():int', 'cpp': 'public: static int unused()',
                   'js': 'static unused():int'}[flavor] + ' {return 2;}'
    revisions(work, flavor, dict(counter=counter, extra=additional, local=local, base=base(idle), derived=derived,
                                supply=supplied, composed=composed, main=main,
                                payload=klass('Payload'), narrow=klass('Narrow', base='Payload')), [
        (None, None, ok), (None, None, ok),
        ('base', base(method('unused', 'return true;', result='bool')), 'Trait hook signature'),
        ('base', base(method('unused', 'return 2;', access='protected')), 'Trait hook cannot narrow visibility'),
        ('base', base(''), 'Trait hook requires a concrete instance method'),
        (None, None, 'Derived::unused'), ('base', base(idle), ok),
        ('extra', additional.replace(hook('unused', 'public'), hook('unused', result='bool')), 'Trait hook signature'),
        ('extra', trait('Extra', hook('unused', visibility='private')), 'Trait hooks require'),
        ('extra', additional, ok),
        ('base', base(idle).replace('return 4', 'return 8'), b'385'),
        ('narrow', klass('Narrow'), 'Trait hook signature'),
        ('narrow', klass('Narrow', base='Payload'), b'385'),
        ('extra', additional.replace(hook('unused', 'public'), ''), b'385'),
        ('base', base(static_idle), 'Trait hook requires a concrete instance method'),
        ('base', base(idle), ok),
    ], unaffected_source='local.' + source_suffix(flavor))
    assert artifacts(work / f'{flavor}-fresh-12') == artifacts(work / f'{flavor}-fresh-13'), 'Hooks changed native output'


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-trait-hooks-') as tmp:
        work = args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        for flavor in ('php', 'cpp', 'js'):
            prove(work, flavor)
