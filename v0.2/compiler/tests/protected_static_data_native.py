"""Protected static storage and constants through declaring, descendant and lexical self names."""
import tempfile
from pathlib import Path

from collection_goal_native import guard, revisions
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


def late_static(work, flavor):
    _, (_, klass, _, static, sep) = sources(flavor)
    prop, const, name = {
        'php': ('static int $n=0;', 'const int STEP=', '$n'),
        'cpp': ('static int n=0;', 'static constexpr int STEP=', 'n'),
        'js': ('static n:int=0;', 'const STEP:int=', 'n'),
    }[flavor]
    late = 'Static::' if flavor == 'cpp' else 'static::'
    vis = ': ' if flavor == 'cpp' else ' '
    n, step = late+name, late+'STEP'
    body = static('tick', f'{n}={n}+{step};return {n};')
    base = klass('Base', 'public'+vis+prop+body)
    def child(owner, value, visibility='public', field=''):
        return klass(owner, field+visibility+vis+const+str(value)+';', 'Base')
    main = f'echo Left{sep}tick(),Right{sep}tick(),Base{sep}{name};return 8;'
    left, right = child('Left', 2), child('Right', 5)
    src = dict(base=base, left=left, right=right, main=main)
    revisions(work, flavor, src, [
        (None, None, b'277'), (None, None, b'277'), ('left', child('Left', 4), b'499'),
        ('left', child('Left', 4, 'private'), 'Cannot access private constant Left::STEP'),
        (None, None, 'Cannot access private constant'), ('left', left, b'277'),
        ('main', f'echo Left{sep}tick(),Right{sep}tick();return 8;', b'27'),
        ('base', base.replace('public'+vis+prop, 'protected'+vis+prop), b'27'),
        ('left', child('Left', 2, 'protected'), 'Cannot access protected constant Left::STEP'),
        ('left', left, b'27'), ('base', base, b'27'), ('main', main, b'277'),
        ('right', klass('Right', '', 'Base'), 'Unknown constant:'), ('right', right, b'277'),
        ('main', f'echo {step};return 8;', 'requires a called type'), ('main', main, b'277'),
        ('base', base.replace(f'{n}={n}+{step}', f'{step}=1'), 'Assignment requires a writable static property'),
        ('base', base, b'277'),
        ('main', f'echo Left{sep}tick(),Right{sep}tick(),Left{sep}{name},Right{sep}{name};return 8;', b'2777'),
        ('base', klass('Base', body), None),
        ('left', child('Left', 2, field='public'+vis+prop), None),
        ('right', child('Right', 5, field='public'+vis+prop), b'2525'),
        ('left', child('Left', 2, field='private'+vis+prop), 'Cannot access private static property'),
        ('left', child('Left', 2, field='public'+vis+prop), b'2525'),
    ])


def late_updates(work, flavor):
    _, (syntax, klass, _, static, sep) = sources(flavor)
    name = '$n' if flavor == 'php' else 'n'
    late = 'Static::' if flavor == 'cpp' else 'static::'
    n = late + name
    def prop(t='int', value='0', var=name):
        declaration = f'{var}:{t}' if flavor == 'js' else f'{t} {var}'
        return ('public: ' if flavor == 'cpp' else 'public ') + f'static {declaration}={value};'
    counter = syntax['v']+'i'
    # Net +1 per call; cover both prefix/postfix, including both for update positions.
    loop = syntax['infer']('i', '0')
    body = f'{n} += {syntax["v"]}delta;{n}++;--{n};++{n};{n}--;'
    body += f'for ({loop}{counter}<1;++{n}) {{{counter}++;}}'
    body += f'for ({loop}{counter}<1;{n}--) {{{counter}++;}} ++{n};return {n};'
    def base(code, field=''):
        return klass('Base', field+static('tick', code, [('delta','int')]))
    left, right = klass('Left','','Base'), klass('Right','','Base')
    calls = f'echo Left{sep}tick(2),Right{sep}tick(5);'
    main = calls+f'echo Left{sep}{name},Right{sep}{name};return 8;'
    src = dict(base=base(body, prop()), left=left, right=right, main=main)
    separate = klass('Right',prop(),'Base')
    revisions(work, flavor, src, [
        (None, None, b'3999'), (None, None, b'3999'),
        ('base', base(body), None), ('left', klass('Left',prop(),'Base'), None),
        ('right', separate, b'3636'), ('right', klass('Right',prop('string','"text"'),'Base'), 'Unsupported compound operands'),
        (None, None, None), ('right', separate, b'3636'),
        ('right', None, None), ('right', separate, b'3636'),
        ('base', base(f'{n} <<= 1;{n} |= 3;{n} *= 2;return {n};'), b'6666'),
        ('base', klass('Base', static('tick', f'{n}=5;{n}+={late}bump();return {n};', [('delta','int')])
                       + static('bump', f'{n}+=10;return {n};')), b'20202020'),
        ('base', base(f'return {n}++;'), None), ('base', base(body), b'3636'),
        ('right', klass('Right',prop().replace('public','private'),'Base'), 'Cannot access private static property'),
        ('right', separate, b'3636'),
    ])
    # The second invocation checks the actual property after the first threw.
    flag = '$attempt' if flavor == 'php' else 'attempt'
    for label, op, error in [('narrow', '+= 10', 'integer_cast_out_of_range'), ('divide', '/= 0', 'division_by_zero')]:
        storage = prop('byte','250') + prop(var=flag)
        attempt = late+flag
        code = f'if ({attempt}) {{if ({n} == 250) {{return 8;}} return 90;}} ++{attempt};{n} {op};return 91;'
        source = klass('Base',storage+static('run',code))+left+f'return Left{sep}run();'
        guard(work, flavor, label, source, error, retry_entry=True)


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-static-data-') as tmp:
        for flavor in ['php', 'cpp', 'js']:
            prove(Path(tmp), flavor)
            late = Path(tmp)/'late'
            late.mkdir(exist_ok=True)
            late_static(late, flavor)
            updates = Path(tmp)/'updates'
            updates.mkdir(exist_ok=True)
            late_updates(updates, flavor)
