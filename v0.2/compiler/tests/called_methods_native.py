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


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory() as temporary:
        root = args.output or Path(temporary)
        root.mkdir(exist_ok=True)
        for flavor in ('php', 'cpp', 'js'):
            prove(root, flavor)
