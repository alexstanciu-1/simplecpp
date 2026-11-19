"""Demanded static bodies survive sibling edits and retire when their last root disappears."""
import argparse
from pathlib import Path
import subprocess
import tempfile

from collection_goal_native import revisions
from protected_static_native import sources


def prove(work, flavor):
    _, (s, klass, _, static, sep) = sources(flavor)
    # The recursive edge is never executed. It proves that an internal cycle cannot keep itself live.
    base = klass('Base', static('read', 'if (false) {return Left'+sep+'read();} return 3;'))
    main = 'echo Right'+sep+'read();return 8;'
    caller = s['function']('extra', 'int', [], 'return Left'+sep+'read();')
    # Keep the sibling demand independent of the cycle.
    plain = klass('Base', static('read', 'return 3;'))
    local = {'php': '$n int=4;', 'cpp': 'int n=4;', 'js': 'let n:int=4;'}[flavor]
    duplicate = klass('Base', static('read', local + 'return 3;', [('n', 'int')]).replace(')', '=1)', 1))
    invalid = klass('Base', static('read', 'return missing();'))
    source = dict(base=plain, left=klass('Left', '', 'Base'), right=klass('Right', '', 'Base'),
                  caller=caller, main=main)
    revisions(work, flavor, source, [
        (None, None, b'3'), (None, None, b'3'),
        ('caller', None, b'3'), ('base', invalid, 'Unknown function'), (None, None, 'Unknown function'),
        ('main', 'return 8;', b''), ('base', base, b''), ('caller', caller, b''),
        ('caller', None, b''), ('base', plain, b''), ('main', main, b'3'), ('caller', caller, b'3'),
        ('base', duplicate, 'Duplicate local'), (None, None, 'Duplicate local'), ('base', plain, b'3'),
    ])
    for revision, left, right in [(0, True, True), (2, False, True), (5, False, False),
                                  (7, True, False), (8, False, False), (11, True, True)]:
        units = work / f'{flavor}-fresh-{revision}' / 'units'
        cpp = ''.join(p.read_text() for p in units.glob('*.cpp'))
        headers = ''.join(p.read_text() for p in units.glob('*.hpp'))
        for name, active in [('Left', left), ('Right', right)]:
            native = f'called_{len(name)}_{name}_4_Base_read('
            assert (native in cpp) == active and (native in headers) == active, (revision, name, active)

    app = work / flavor
    subprocess.run(['php', Path(__file__).with_name('called_method_lifetime.php'), app,
                    work / (flavor + '-lifetime'), app / ('caller.' + flavor)], check=True)


def forwarding(work, flavor):
    _, (_, klass, _, static, sep) = sources(flavor)
    lexical = {'php': 'self', 'cpp': 'Self', 'js': 'class'}[flavor]
    parent = {'php': 'parent', 'cpp': 'Parent', 'js': 'super'}[flavor]

    def middle(first=lexical, second=parent):
        return klass('Middle', static('run', f'return {first}{sep}hop();')
                     + static('hop', f'return {second}{sep}step();')
                     + static('step', 'return 9;'), 'Base')

    base = klass('Base', static('step', f'return {lexical}{sep}tag();')
                 + static('tag', 'return 3;', visibility='private'))
    main = f'echo Left{sep}run(),Right{sep}run();return 8;'
    src = dict(base=base, middle=middle(), left=klass('Left', '', 'Middle'),
               right=klass('Right', '', 'Middle'), main=main)
    revisions(work, flavor, src, [
        (None, None, b'33'), (None, None, b'33'),
        ('middle', middle(first='Middle'), b'33'), ('middle', middle(second='Base'), b'33'),
        ('middle', middle(), b'33'), ('base', base.replace('return 3;', 'return 7;'), b'77'),
        ('base', None, None), (None, None, None), ('base', base, b'33'),
        ('main', f'echo Right{sep}run();return 8;', b'3'), ('main', main, b'33'),
    ])
    # Output alone cannot distinguish two called types executing the same declaration.
    # Check every selected implementation along the chain, including retirement after edits.
    for revision, hops, steps in [
        (0, ('Left', 'Right'), ('Left', 'Right')), (2, ('Middle',), ('Middle',)),
        (3, ('Left', 'Right'), ('Base',)), (8, ('Left', 'Right'), ('Left', 'Right')),
        (9, ('Right',), ('Right',)), (10, ('Left', 'Right'), ('Left', 'Right')),
    ]:
        units = work / f'{flavor}-fresh-{revision}' / 'units'
        cpp = ''.join(p.read_text() for p in units.glob('*.cpp'))
        for owner, method, wanted in [('Middle', 'hop', hops), ('Base', 'step', steps), ('Base', 'tag', steps)]:
            for called in ('Left', 'Right', 'Middle', 'Base'):
                prefix = 'method_' if called == owner else f'called_{len(called)}_{called}_'
                symbol = f'{prefix}{len(owner)}_{owner}_{method}('
                assert (symbol in cpp) == (called in wanted), (flavor, revision, symbol)


def late_selection(work, flavor):
    _, (_, klass, method, static, sep) = sources(flavor)
    late = 'Static::' if flavor == 'cpp' else 'static::'
    lexical = {'php': 'self::', 'js': 'class.', 'cpp': 'Self::'}[flavor]
    parent = {'php': 'parent::', 'js': 'super.', 'cpp': 'Parent::'}[flavor]
    v = '$' if flavor == 'php' else ''

    def label(n, visibility='public'):
        return static('label', f'return {v}n;', [('n', 'int')], visibility).replace(')', f'={n})', 1)

    body = static('step', f'{late}label();return {late}label();')
    base = klass('Base', label(3, 'private') + body)
    middle = klass('Middle', static('run', f'return {lexical}relay();')
                   + static('relay', f'return {parent}step();')
                   + static('reset', f'return Base{sep}step();'), 'Base')
    left = klass('Left', '', 'Middle')
    changed = klass('Left', label(7), 'Middle')
    main = f'echo Left{sep}run(),Right{sep}run(),Left{sep}reset();return 8;'
    src = dict(base=base, middle=middle, left=left, right=klass('Right', '', 'Middle'), main=main)
    revisions(work, flavor, src, [
        (None, None, b'333'), (None, None, b'333'), ('left', changed, b'733'),
        ('left', klass('Left', label(7, 'private'), 'Middle'), 'Cannot call private method'),
        (None, None, 'Cannot call private method'), ('left', changed, b'733'),
        ('left', left, b'333'), ('left', None, None), ('left', changed, b'733'),
        ('base', klass('Base', body), 'Unknown static method'), ('base', base, b'733'),
        ('main', f'echo {late}label();return 8;', 'requires a called type'), ('main', main, b'733'),
        ('extra', klass('Extra', method('bad', f'return {late}label();')), 'requires a called type'),
        ('extra', None, b'733'),
        ('left', left, b'333'), ('base', klass('Base', method('label', 'return 3;') + body), 'requires a receiver'),
        ('base', base, b'333'),
        ('main', f'echo Left{sep}run();return 8;', b'3'), ('left', changed, b'7'),
        ('base', klass('Base', body), b'7'),
        ('base', klass('Base', body.replace('label();', 'label(1,2);')), 'Argument count mismatch'),
        ('base', klass('Base', body), b'7'),
    ])


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory() as temporary:
        root = args.output or Path(temporary)
        root.mkdir(exist_ok=True)
        for flavor in ('php', 'cpp', 'js'):
            prove(root, flavor)
            (root / 'forwarding').mkdir(exist_ok=True)
            forwarding(root / 'forwarding', flavor)
            (root / 'late').mkdir(exist_ok=True)
            late_selection(root / 'late', flavor)
