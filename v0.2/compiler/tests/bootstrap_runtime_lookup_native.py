"""Bootstrap lookup representation, real generated overload selection and source-shadow recovery."""
import tempfile
from pathlib import Path

from collection_goal_native import revisions
from object_access_native import syntax


def prove(work, flavor):
    forms, method, klass, cast = syntax(flavor)
    fn, var, access = forms['function'], forms['v'], forms['access']
    # The registry owns the two descriptors. Lookup returns an existing owner or absence.
    types = klass('Binding', method('virtual')) + klass('Fixed', '', 'Binding') + klass('Overloads', '', 'Binding')
    lookup = fn('lookup', '?Binding', [('key', 'int'), ('fixed', 'Fixed'), ('group', 'Overloads')],
                f'if ({var}key == 1) {{return {var}fixed;}} if ({var}key == 2) {{return {var}group;}} return null;')
    fixed, group = var+'fixed', var+'group'
    def query(key):
        return f'lookup({key},{fixed},{group})'
    main = forms['infer']('fixed', 'new Fixed()') + forms['infer']('group', 'new Overloads()')
    main += ('echo (int)('+cast(query(1), 'Fixed')+' === '+fixed+'),'
             '(int)('+cast(query(2), 'Overloads')+' === '+group+'),(int)('+query(3)+' === null);')
    container = {'php': '$box mixed = [];', 'cpp': 'mixed box = [];', 'js': 'let box:mixed = [];'}[flavor]
    main += container + f'{var}box["a"]=7; echo strlen("abc"), (int)({var}box{access}has(selector()));return 8;'
    selector = fn('selector', 'string', [], 'echo "K";return "a";')
    shadow = fn('strlen', 'int', [('value', 'string')], 'return 9;')
    apply = fn('apply', 'int', [('op', 'callback<int,int>')], f'return {var}op(4);')
    identity = fn('identity', 'int', [('n', 'int')], f'return {var}n;')
    main = main.replace('return 8;', 'echo apply(identity(...));return 8;')
    output = b'1113K14'
    revisions(work, flavor, dict(types=types, lookup=lookup, main=main, selector=selector, apply=apply, identity=identity), [
        (None, None, output), (None, None, output),
        ('shadow', shadow, b'1119K14'), ('shadow', None, output),
        ('selector', fn('selector', 'int', [], 'echo "I";return 42;'), b'1113I04'),
        ('selector', fn('selector', 'bool', [], 'return true;'), 'Unsupported argument type for has'),
        ('selector', None, 'Unknown function: selector'), ('selector', selector, output),
        ('main', main.replace('has(selector())', 'has()'), 'Argument count mismatch'),
        ('main', main, output),
        ('apply', apply.replace('op(4)', 'op()'), 'Argument count mismatch'),
        ('apply', apply.replace('op(4)', 'op(4,4)'), 'Argument count mismatch'),
        ('apply', apply, output),
    ])


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-runtime-lookup-') as tmp:
        for flavor in ('php', 'cpp', 'js'):
            prove(Path(tmp), flavor)
