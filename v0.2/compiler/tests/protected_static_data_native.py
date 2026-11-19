"""Protected static storage and constants through declaring-type and lexical self names."""
import tempfile
from pathlib import Path

from collection_goal_native import revisions
from protected_static_native import sources


def prove(work, flavor):
    _, (_, klass, _, static, sep) = sources(flavor)
    prop, const, name, lexical = {
        'php': ('static int $n=1;', 'const int STEP=2;', '$n', 'self::'),
        'cpp': ('static int n=1;', 'static constexpr int STEP=2;', 'n', 'Self::'),
        'js': ('static n:int=1;', 'const STEP:int=2;', 'n', 'class.'),
    }[flavor]
    def base(property_visibility='protected', constant_visibility='protected'):
        prefix = ': ' if flavor == 'cpp' else ' '
        return klass('Base', property_visibility+prefix+prop + constant_visibility+prefix+const
                     + static('start', f'{lexical}{name}={lexical}STEP;return {lexical}{name};'))
    middle = klass('Middle', '', 'Base')
    prop_name, constant = 'Base'+sep+name, 'Base'+sep+'STEP'
    def child(body):
        return klass('Child', static('update', body), 'Middle')
    update = child(f'{prop_name}+={constant};return {prop_name};')
    main = f'echo Base{sep}start(),Child{sep}update();return 8;'
    src = dict(base=base(), child=update, middle=middle, other=klass('Other'), main=main)
    revisions(work, flavor, src, [
        (None, None, b'24'), (None, None, b'24'),
        ('middle', klass('Middle', '', 'Other'), 'Cannot access protected static property Base::n'), (None, None, None), ('middle', middle, b'24'),
        ('base', base('private'), 'Cannot access private static property Base::n'), ('base', base(), b'24'),
        ('base', base(constant_visibility='private'), 'Cannot access private constant Base::STEP'), ('base', base(), b'24'),
        ('main', f'{prop_name}=8;echo {prop_name};return 8;', 'Cannot access protected static property Base::n'),
        ('base', base('public'), b'8'), ('base', base(), None),
        ('main', f'echo {constant};return 8;', 'Cannot access protected constant Base::STEP'), ('base', base(constant_visibility='public'), b'2'),
        ('base', base(), None), ('main', main, b'24'),
        ('child', child(f'return {lexical}STEP;'), None),
        ('child', child(f'return Child{sep}{name};'), None), ('child', update, b'24'),
        ('base', None, None), ('base', base(), b'24'),
    ])


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-static-data-') as tmp:
        for flavor in ['php', 'cpp', 'js']:
            prove(Path(tmp), flavor)
