"""One trait binds an ancestor instance call independently in two consumer classes."""
import argparse
import tempfile
from pathlib import Path

from collection_goal_native import revisions
from parent_methods_native import syntax


def prove(work, flavor):
    s, _, klass, parent, method = syntax(flavor)
    call = parent('offset', base='Parent')
    trait = 'trait Positioned {\n' + method('position', '\nreturn ' + call + ' + 1;') + '\n}'
    if flavor == 'cpp':
        trait += ';'

    def base(name, value, access='protected'):
        return klass(name, method('offset', f'return {value};', access=access))

    left = klass('Left', 'use Positioned;', 'LeftBase')
    right = klass('Right', 'use Positioned;', 'RightBase')
    main = s['infer']('l', 'new Left()') + s['infer']('r', 'new Right()')
    main += f'echo {s["v"]}l{s["access"]}position(),",",{s["v"]}r{s["access"]}position(); return 8;'
    sources = dict(positioned=trait, left=left, right=right, left_base=base('LeftBase', 10),
                   right_base=base('RightBase', 20), main=main)
    origin = f'positioned.{flavor}:3:'
    edits = [
        (None, None, b'11,21'), (None, None, b'11,21'),
        ('right_base', base('RightBase', 30), b'11,31'),
        ('right', klass('Right', 'use Positioned;', 'LeftBase'), b'11,11'),
        ('right', right, b'11,31'),
        ('right_base', klass('RightBase'), 'Unknown ancestor method: offset'),
        (None, None, origin),
        ('right_base', base('RightBase', 20), b'11,21'),
        ('right_base', base('RightBase', 20, 'private'), 'private'),
        ('right', klass('Right', 'use Positioned;'), 'Ancestor call requires a base type'),
        (None, None, origin),
        ('right_base', base('RightBase', 40), origin),
        ('right', right, b'11,41'),
    ]
    revisions(work, flavor, sources, edits, unaffected_source='left.' + flavor)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-trait-parent-') as tmp:
        work = args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        for flavor in ('php', 'cpp', 'js'):
            prove(work, flavor)
