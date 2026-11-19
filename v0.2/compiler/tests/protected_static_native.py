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
        property_text = {'php':'protected static int $value = 1;', 'cpp':'protected: static int value = 1;', 'js':'protected static value:int = 1;'}[flavor]
        constant_text = {'php':'protected const int VALUE = 1;', 'cpp':'protected: static constexpr int VALUE = 1;', 'js':'protected const VALUE:int = 1;'}[flavor]
        cases += [('other', klass('Other', text), 'Protected static members are deferred') for text in [property_text, constant_text]]
        for name, text, message in cases:
            source_edit(app/(name+'.'+flavor), text)
            got = invoke(p)
            assert message in got.get('error', ''), (flavor, message, got)
            source_edit(app/(name+'.'+flavor), src[name])
            assert 'error' not in invoke(p)
    finally:
        p.stdin.close()
        p.wait()


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
