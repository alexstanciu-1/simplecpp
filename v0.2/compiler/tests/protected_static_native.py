"""Declaring-type protected static calls, lexical access and incremental/fresh equivalence."""
import argparse
from pathlib import Path
import tempfile

from app_namespace_native import invoke, service
from collection_goal_native import revisions
from extends_native import source_edit
from protected_methods_native import forms


def sources(flavor):
    s, klass, method = forms(flavor)
    v, access = s['v'], s['access']
    qualifier = '.' if flavor == 'js' else '::'
    self_name = {'php': 'self', 'cpp': 'Self', 'js': 'class'}[flavor]

    def static(name, body, parameters=(), visibility='public'):
        text = method(name, body, parameters, visibility)
        return text.replace('int '+name, 'static int '+name, 1) if flavor == 'cpp' else text.replace(visibility+' ', visibility+' static ', 1)

    hidden = static('hidden', f'return {v}value;', [('value', 'int')], 'protected').replace(')', '=3)', 1)
    base = klass('Base', hidden + static('read', f'return {self_name}{qualifier}hidden();'))
    child = klass('Child', static('childRead', f'return {self_name}{qualifier}hidden();')
                  + method('instance', f'return Child{qualifier}hidden(9);'), 'Middle')
    inline = s['record']('Inline', static('hidden', 'return 4;', visibility='protected')
                         + static('read', f'return {self_name}{qualifier}hidden();'))
    main = s['infer']('p', 'new Child()') + f'echo Base{qualifier}read(),Child{qualifier}childRead(),{v}p{access}instance(),Inline{qualifier}read();return 8;'
    return dict(base=base, middle=klass('Middle', '', 'Base'), child=child, inline=inline,
                other=klass('Other'), main=main), (s, klass, method, static, qualifier)


def rejections(work, flavor, src, helpers):
    s, klass, method, static, qualifier = helpers
    app = work/flavor
    p = service(app, work/(flavor+'-reject'))
    try:
        cases = [
            ('main', f'echo Base{qualifier}hidden();return 8;', 'Cannot call protected method'),
            ('other', klass('Other', static('read', f'return Base{qualifier}hidden();')), 'Cannot call protected method'),
            ('main', f'echo Child{qualifier}hidden();return 8;', 'Cannot call protected method'),
        ]
        for name, text, message in cases:
            source_edit(app/(name+'.'+flavor), text)
            got = invoke(p)
            assert message in got.get('error', ''), (flavor, message, got)
            source_edit(app/(name+'.'+flavor), src[name])
            assert 'error' not in invoke(p)
    finally:
        p.stdin.close()
        p.wait()


def ancestor_calls(work, flavor, helpers):
    work.mkdir(exist_ok=True)
    s, klass, method, static, qualifier = helpers
    parent = {'php': 'parent::', 'js': 'super.', 'cpp': 'Middle::'}[flavor]
    def provider(name, value):
        return klass(name, static('answer', 'return '+value+';', visibility='protected'))
    body = static('read', 'return '+parent+'answer();') + method('readInstance', 'return '+parent+'answer();')
    child = klass('Child', body, 'Middle')
    base, other = provider('Base', '3'), provider('Other', '7')
    middle = klass('Middle', '', 'Base')
    main = s['infer']('p', 'new Child()') + f"echo Child{qualifier}read(),{s['v']}p{s['access']}readInstance();return 8;"
    src = dict(base=base, middle=middle, other=other, child=child, main=main)
    revisions(work, flavor, src, [
        (None, None, b'33'), (None, None, b'33'),
        ('middle', klass('Middle', '', 'Other'), b'77'),
        ('other', other.replace('protected', 'private'), None), (None, None, None),
        ('other', other, b'77'), ('other', klass('Other'), None), ('other', other, b'77'),
        ('middle', middle, b'33'), ('base', base.replace('protected', 'public'), b'33'),
        ('child', klass('Child', body, 'Other'), b'33' if flavor == 'cpp' else b'77'),
        ('child', child, b'33'),
    ])


def redeclarations(work, flavor, helpers):
    work.mkdir(exist_ok=True)
    s, klass, method, static, q = helpers
    self_name = {'php': 'self', 'cpp': 'Self', 'js': 'class'}[flavor]
    parent = {'php': 'parent::', 'cpp': 'Base::', 'js': 'super.'}[flavor]
    def answer(name, default, visibility):
        return static('answer', 'return '+s['v']+name+';', [(name, 'int')], visibility).replace(')', '='+default+')', 1)
    def final(text):
        return text.replace(') {', ') final {', 1) if flavor == 'cpp' else text.replace('static ', 'final static ', 1)
    base_method = answer('n', '3', 'protected')
    child_method = answer('value', '7', 'public')
    def base(member):
        return klass('Base', member + static('readBase', f'return {self_name}{q}answer();'))
    def child(member):
        return klass('Child', member + static('readChild', f'return {self_name}{q}answer();')
                     + static('readParent', f'return {parent}answer();'), 'Base')
    main = f'echo Child{q}readBase(),Leaf{q}readChild(),Child{q}readParent();return 8;'
    leaf = klass('Leaf', '', 'Child')
    src = dict(base=base(base_method), child=child(''), leaf=leaf, main=main)
    revisions(work, flavor, src, [
        (None, None, b'333'), (None, None, b'333'),
        ('child', child(child_method), b'373'), ('child', child(''), b'333'),
        ('child', child(child_method), b'373'), ('base', base(final(base_method)), None),
        (None, None, None), ('child', child(''), b'333'), ('base', src['base'], b'333'),
        ('child', child(child_method.replace('public', 'private', 1)), None),
        ('child', child(child_method.replace('public', 'protected', 1)), b'373'),
        ('base', base(base_method.replace('protected', 'public', 1)), None),
        ('base', src['base'], b'373'),
        ('child', child(child_method.replace('static ', 'override static ', 1)), None),
        ('child', child(child_method.replace('=7', '')), None),
        ('child', child(child_method.replace('int', 'float')), None),
        ('child', child(method('answer', 'return 7;')), None),
        ('child', child(final(child_method)), b'373'),
        ('leaf', klass('Leaf', child_method, 'Child'), None), ('leaf', leaf, b'373'),
        ('child', None, None), ('child', child(child_method), b'373'),
        ('main', f'echo Leaf{q}answer();return 8;', b'7'),
        ('base', base(base_method.replace('protected', 'private', 1)), None),
        ('child', child(child_method).replace(parent+'answer()', '3'), b'7'),
        ('base', base(method('answer', 'return 3;')), None),
        ('base', src['base'], b'7'),
        ('main', s['record']('ParentValue', base_method) + s['record']('Value', final(child_method), 'ParentValue')
         + f'echo Value{q}answer();return 8;', b'7'),
    ])


def prove(work):
    for flavor in ['php', 'cpp', 'js']:
        src, helpers = sources(flavor)
        _, klass, _, static, qualifier = helpers
        external = f'echo Base{qualifier}hidden();return 8;'
        revisions(work, flavor, src, [
            (None, None, b'3394'), (None, None, b'3394'),
            ('base', src['base'].replace('=3)', '=5)'), b'5594'),
            ('base', src['base'].replace('protected', 'private', 1), None),
            (None, None, None), ('base', src['base'], b'3394'),
            ('other', klass('Other', static('hidden', 'return 7;', [('value', 'int')], 'protected').replace(')', '=7)', 1)), b'3394'),
            ('middle', klass('Middle', '', 'Other'), b'3774'),
            ('other', src['other'], None),
            (None, None, None), ('middle', src['middle'], b'3394'),
            ('base', None, None), ('base', src['base'], b'3394'),
            ('base', src['base'].replace('protected', 'public', 1), b'3394'),
            ('main', external, b'3'), ('base', src['base'], None),
            ('main', f'echo Child{qualifier}read();return 8;', b'3'),
            ('main', src['main'], b'3394'), (None, None, b'3394'),
        ])
        rejections(work, flavor, src, helpers)
        ancestor_calls(work/'ancestor', flavor, helpers)
        redeclarations(work/'redeclare', flavor, helpers)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-protected-static-') as temporary:
        work = args.output or Path(temporary)
        work.mkdir(parents=True, exist_ok=True)
        prove(work)


if __name__ == '__main__':
    main()
