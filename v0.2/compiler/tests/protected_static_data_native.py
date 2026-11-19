"""Protected static storage and constants through declaring, descendant and lexical self names."""
import tempfile
from pathlib import Path

from collection_goal_native import revisions
from protected_static_native import sources


def prove(work, flavor):
    _, (syntax, klass, method, static, sep) = sources(flavor)
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
        ('child', child(f'return {lexical}STEP;'), b'22'),
        ('child', child(f'return Child{sep}{name};'), b'22'), ('child', update, b'24'),
        ('base', None, None), ('base', base(), b'24'),
    ])

    inherited = work/'inherited'
    inherited.mkdir(exist_ok=True)
    def provider(owner, step, property_present=True, constant_present=True):
        prefix = 'public: ' if flavor == 'cpp' else 'public '
        members = (prefix+prop if property_present else '')
        if constant_present:
            members += prefix+const.replace('2', str(step))
        return klass(owner, members)
    update = child(f'Child{sep}{name}=5;{lexical}{name}+={lexical}STEP;return Child{sep}{name};')
    main = f'echo Child{sep}update(),Base{sep}{name},Other{sep}{name};return 8;'
    other = provider('Other', 4)
    src = dict(base=provider('Base', 2), other=other, middle=middle, child=update, main=main)
    revisions(inherited, flavor, src, [
        (None, None, b'771'), (None, None, b'771'),
        ('middle', klass('Middle', '', 'Other'), b'919'),
        ('other', provider('Other', 3), b'818'),
        ('other', provider('Other', 4, property_present=False), 'Unknown static property:' if flavor == 'php' else 'Unknown constant:'), (None, None, None),
        ('other', other, b'919'), ('other', provider('Other', 4, constant_present=False), 'Unknown constant:'),
        ('other', other, b'919'), ('middle', middle, b'771'),
        ('base', base(), 'Cannot access protected static property Base::n'),
        ('main', f'echo Child{sep}{name};return 8;', 'Cannot access protected static property Base::n'),
        ('main', f'echo Child{sep}STEP;return 8;', 'Cannot access protected constant Base::STEP'),
        ('main', f'echo Child{sep}update();return 8;', b'7'),
        ('base', base('private'), 'Cannot access private static property Base::n'),
        ('base', provider('Base', 2), b'7'), ('main', main, b'771'),
    ])

    collisions = work/'collisions'
    collisions.mkdir(exist_ok=True)
    # Insert a declaration into the intermediate record while Child still reads Base's data.
    prefix = 'public: ' if flavor == 'cpp' else 'public '
    revisions(collisions, flavor, src, [
        (None, None, b'771'), (None, None, b'771'),
        ('middle', klass('Middle', prefix+prop, 'Base'), 'Inherited property redeclaration'),
        (None, None, None), ('middle', middle, b'771'),
        ('middle', klass('Middle', prefix+const, 'Base'), 'Inherited constant redeclaration'),
        ('middle', middle, b'771'),
        ('middle', klass('Middle', syntax['field']('n','int','4'), 'Base'), 'Inherited property redeclaration'),
        ('middle', middle, b'771'),
    ])

    ancestor = work/'ancestor'
    ancestor.mkdir(exist_ok=True)
    qualifier = {'php': 'parent::', 'cpp': 'Middle::', 'js': 'super.'}[flavor]
    n = qualifier + name
    step = qualifier + 'STEP'
    body = static('update', f'{n}=5;++{n};{n}--;{n}+={step};return {n};')
    def derived(parent):
        return klass('Child', body, parent)
    src['child'] = derived('Middle')
    instance = method('read', f'return {n}+{step};')
    instance_main = syntax['infer']('p', 'new Child()') + f"echo {syntax['v']}p{syntax['access']}read();return 8;"
    edits = [
        (None, None, b'771'), (None, None, b'771'),
        ('middle', klass('Middle', '', 'Other'), b'919'),
        ('other', provider('Other', 3), b'818'),
        ('other', provider('Other', 4, constant_present=False), 'Unknown constant:'),
        (None, None, None), ('other', other, b'919'),
        ('other', provider('Other', 4, property_present=False),
         'Unknown static property:' if flavor == 'php' else 'Unknown constant:'),
        ('other', other, b'919'), ('middle', middle, b'771'),
        ('child', derived('Other'), b'771' if flavor == 'cpp' else b'919'),
        ('child', derived('Middle'), b'771'), ('middle', None, None), ('middle', middle, b'771'),
        ('base', base('private'), 'Cannot access private static property Base::n'),
        ('base', provider('Base', 2), b'771'),
        ('child', klass('Child', body + instance, 'Middle'), b'771'),
        ('main', instance_main, b'3'),
        ('child', klass('Child', method('read', f'{step}=0;return 0;'), 'Middle'),
         'Assignment requires a writable static property'),
        ('child', klass('Child', instance, 'Middle'), b'3'),
    ]
    if flavor != 'cpp':
        edits += [
            ('child', klass('Child', instance), 'Ancestor access requires a base type'),
            ('child', klass('Child', instance, 'Middle'), b'3'),
            ('main', f'echo {step};return 8;', 'Ancestor access requires a declaring record'),
            ('main', instance_main, b'3'),
        ]
    revisions(ancestor, flavor, src, edits)


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-static-data-') as tmp:
        for flavor in ['php', 'cpp', 'js']:
            prove(Path(tmp), flavor)
