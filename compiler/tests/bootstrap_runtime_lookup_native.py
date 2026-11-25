"""Bootstrap lookup representation, real generated overload selection and source-shadow recovery."""
import argparse
import tempfile
from pathlib import Path

from collection_goal_native import revisions
from object_access_native import syntax


def prove(work, flavor, quick=False):
    forms, method, klass, cast = syntax(flavor)
    fn, var, access = forms['function'], forms['v'], forms['access']
    # The registry owns the two descriptors. Lookup returns an existing owner or absence.
    types = klass('Binding', method('virtual')) + klass('Fixed', '', 'Binding') + klass('Overloads', '', 'Binding')
    registry_type = 'hash<Binding,int>'
    fixed, group = var+'fixed', var+'group'
    table = forms['local']('table', registry_type).removesuffix(';')
    lookup = fn('bindings', registry_type, [('fixed', 'Fixed'), ('group', 'Overloads')],
                f'{table} = [1 => {fixed}, 2 => {group}]; return {var}table;')
    def query(key):
        return f'hash_get_or_null({var}registry,{key})'
    main = forms['infer']('fixed', 'new Fixed()') + forms['infer']('group', 'new Overloads()')
    main += forms['infer']('registry', f'bindings({fixed},{group})')
    main += ('echo (int)('+cast(query(1), 'Fixed')+' === '+fixed+'),'
             '(int)('+cast(query(2), 'Overloads')+' === '+group+'),(int)('+query(3)+' === null);')
    # After the factory arguments expire, this table is the only strong owner of key 1.
    main += forms['infer']('owned', 'bindings(new Fixed(),new Overloads())')
    main += forms['infer']('held', f'hash_get_or_null({var}owned,1)') + forms['infer']('watch', f'weak({var}held)')
    main += (f'{var}held=null;if (lock({var}watch) === null) {{return 91;}}'
             f'{var}owned{access}remove(1);if (lock({var}watch) !== null) {{return 92;}}')
    container = {'php': '$box mixed = [];', 'cpp': 'mixed box = [];', 'js': 'let box:mixed = [];'}[flavor]
    main += container + f'{var}box["a"]=7; echo strlen("abc"), (int)({var}box{access}has(selector()));return 8;'
    selector = fn('selector', 'string', [], 'echo "K";return "a";')
    shadow = fn('strlen', 'int', [('value', 'string')], 'return 9;')
    apply = fn('apply', 'int', [('op', 'callback<int,int>')], f'return {var}op(4);')
    identity = fn('identity', 'int', [('n', 'int')], f'return {var}n;')
    main = main.replace('return 8;', 'echo apply(identity(...));return 8;')
    output = b'1113K14'
    edits = [
        (None, None, output), (None, None, output),
        ('lookup', lookup.replace('2 =>', f'3 => {group}, 2 =>'), b'1103K14'),
        ('lookup', lookup.replace('2 =>', '"wrong" =>'), 'Hash key requires'),
        ('lookup', None, 'Unknown function'),
        ('lookup', lookup, output),
        ('shadow', shadow, b'1119K14'), ('shadow', None, output),
        ('selector', fn('selector', 'int', [], 'echo "I";return 42;'), b'1113I04'),
        ('selector', fn('selector', 'bool', [], 'return true;'), 'Unsupported argument type for has'),
        ('selector', None, 'Unknown function: selector'), ('selector', selector, output),
        ('main', main.replace('has(selector())', 'has()'), 'Argument count mismatch'),
        ('main', main, output),
        ('apply', apply.replace('op(4)', 'op()'), 'Argument count mismatch'),
        ('apply', apply.replace('op(4)', 'op(4,4)'), 'Argument count mismatch'),
        ('apply', apply, output),
    ]
    revisions(work, flavor, dict(types=types, lookup=lookup, main=main, selector=selector, apply=apply, identity=identity),
              [edits[i] for i in (0, 2, 3, 5, 6)] if quick else edits,
              unaffected_source='main.' + flavor, unaffected_revisions={1} if quick else {2})


def lazy_session(work, flavor):
    forms, _, klass, _ = syntax(flavor)
    fn, infer = forms['function'], forms['infer']
    v, access = forms['v'], forms['access']
    types = klass('Descriptor') + klass('RegistrySession', forms['field']('entries', 'hash<Descriptor,string>'))
    entries = f'{v}session{access}entries'
    factory = fn('make_descriptor', 'Descriptor', [], 'echo "F"; return new Descriptor();')
    resolve = fn('resolve', '?Descriptor', [('session', 'RegistrySession'), ('name', 'string')],
                 infer('cached', f'hash_get_or_null({entries},{v}name)') +
                 f'if ({v}cached !== null) {{return {v}cached;}}'
                 f'if ({v}name !== "fixed") {{return null;}}' +
                 infer('created', 'make_descriptor()') +
                 f'{entries}[{v}name]={v}created; return {v}created;')

    # A tiny source consumer for bodies of the form name(...). Each edit passes
    # a new body to the same live session, independently of the host rebuild.
    name_slice = {'php': 'substr($body,0,$end)', 'cpp': 'body.substr(0,end)', 'js': 'body.slice(0,end)'}[flavor]
    consumer = fn('bind_body', '?Descriptor', [('session', 'RegistrySession'), ('body', 'string')],
                  infer('end', '0') +
                  f'while ({v}end < strlen({v}body) && {v}body[{v}end] != 40) {{{v}end++;}}'
                  f'return resolve({v}session,{name_slice});')
    main = infer('session', 'new RegistrySession()')
    main += infer('first', f'bind_body({v}session,"fixed(1)")')
    main += infer('edited', f'bind_body({v}session,"fixed(2)")')
    main += f'if ({v}first === null || {v}first !== {v}edited) {{return 91;}}'
    main += f'if (bind_body({v}session,"missing()") !== null || count({entries}) != 1) {{return 92;}}'
    main += infer('fresh', 'new RegistrySession()')
    main += f'if (count({v}fresh{access}entries) != 0) {{return 93;}}'
    main += infer('other', f'bind_body({v}fresh,"fixed(2)")')
    main += f'if ({v}other === null || {v}other === {v}first) {{return 94;}} return 8;'
    sources = dict(types=types, factory=factory, registry=resolve, consumer=consumer, main=main)
    revisions(work, flavor, sources, [
        (None, None, b'FF'), (None, None, b'FF'),
        ('factory', factory.replace('"F"', '"G"'), b'GG'),
        ('factory', None, 'Unknown function'), ('factory', factory, b'FF'),
    ], unaffected_source='registry.' + flavor, unaffected_revisions={2})


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--quick", action="store_true")
    quick = parser.parse_args().quick
    with tempfile.TemporaryDirectory(prefix='scpp-runtime-lookup-') as tmp:
        for flavor in (('php',) if quick else ('php', 'cpp', 'js')):
            prove(Path(tmp), flavor, quick)
        lazy = Path(tmp) / "lazy"
        lazy.mkdir()
        for flavor in (('php',) if quick else ('php', 'cpp', 'js')):
            lazy_session(lazy, flavor)
