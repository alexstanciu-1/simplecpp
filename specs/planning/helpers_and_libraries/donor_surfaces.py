"""Index pinned donor call paths and receiver contracts outside the callable crosswalk."""
import argparse
import json
import re
from pathlib import Path

from inventory import HERE, MY_TRY, RELEASE, donor, git, write

GEN = 'generators/php/src/Generator/Generator.php'
PARSER = 'compiler/my-try/03_parse/parser.php'
JSS = 'generators/php/src/Jss/JssCallSurface.php'
INSTALL = 'compiler/my-try/compiler/callables/language.php'
CPP = 'runtime/metadata/public_api'
DECL = 'runtime/specs/catalog.md'


def source(rev, path):
    return git('show', f'{rev}:{path}')


def compact(value):
    return json.dumps(value, separators=(',', ':'), ensure_ascii=False)


def family(name):
    for prefix, group in [('async_', 'async'), ('layout_', 'layout'), ('enum_', 'enums'),
                          ('vector_', 'vectors'), ('source_', 'source'), ('byte_span', 'source'),
                          ('hash_bytes', 'source'), ('stable_hash', 'hashing'), ('text_builder', 'strings'),
                          ('string_parts', 'strings')]:
        if name.startswith(prefix):
            return group
    return 'debug' if 'dbg' in name or 'debug' in name else 'carriers'


def source_rows():
    rows = []

    def add(group, rev, kind, owner, name, detail, path):
        rows.append([group, rev, kind, owner, name, detail, path])

    for name in re.findall(r"new compiler_function_definition\('([^']+)'", source(MY_TRY, INSTALL)):
        add(family(name), MY_TRY, 'installed call', 'language', name, 'after lexical lookup', INSTALL)
    syntax = {
        'carriers': ['take'], 'ownership': ['move', 'weak', 'lock', 'object_cast', 'instanceof'],
        'containers and state': ['isset', 'empty', 'unset', 'clean', 'foreach'],
        'output': ['echo'], 'async': ['await'], 'object lifecycle': ['__construct', '__destruct'],
    }
    parser = source(MY_TRY, PARSER)
    for group, names in syntax.items():
        for name in names:
            assert f"'{name}'" in parser, name
            add(group, MY_TRY, 'syntax', 'language', name, 'parsed form', PARSER)

    gen = source(RELEASE, GEN)
    for name in ['async_wait', 'async_sleep_ms', 'take', 'dbg', 'dbg_if', '__scpp_debug_dump',
                 '__scpp_debug_exit', '__scpp_debug_break', 'layout_sizeof', 'layout_alignof',
                 'layout_offsetof', 'layout_field_sizeof', 'enum_name', 'enum_value', 'enum_from_value']:
        assert f"'{name}'" in gen
        add(family(name), RELEASE, 'generator special case', 'PHP', name, 'dedicated lowering', GEN)
    for form, marker, detail in [('new stdClass()', 'isStdClassNewExpr', 'boxed dynamic object construction'),
                                 ('->field', 'AstKind::PROP', 'named read on a mixed receiver'),
                                 ('?->field', 'AstKind::NULLSAFE_PROP', 'guarded property read'),
                                 ('(Type) value', 'AstKind::CAST', 'source cast')]:
        assert marker in gen
        add('conversions' if marker.endswith('CAST') else 'construction and fields', RELEASE,
            'generator syntax', 'PHP', form, detail, GEN)
    assert "$method === 'error'" in gen
    add('carriers', RELEASE, 'method lowering', 'result<T>', 'error', 'zero arguments', GEN)
    path = 'generators/php/src/Stan/StanExpressionTypeResolver.php'
    stan = source(RELEASE, path).split('private function buildFunctionCatalog(', 1)[1].split('return $catalog;', 1)[0]
    names = set(re.findall(r"\$catalog\['(\w+)'\] =", stan))
    names.update(re.findall(r"'(\w+)' => \[\s*'return_type'", stan))
    for name in names:
        add(family(name), RELEASE, 'analysis signature', 'PHP STAN', name, 'analyzer evidence only', path)

    for name, target, result in re.findall(r"'([\w.]+)' => \['target' => '(\w+)', 'return_type' => '([^']+)'", source(RELEASE, JSS)):
        group = {'fs': 'filesystem', 'dt': 'datetime', 'io': 'io', 'json': 'json'}[name.split('.')[0]]
        add(group, RELEASE, 'frontend alias', 'JSS', name, f'{target} -> {result}', JSS)
    path = 'generators/php/src/Jss/JssEmitter.php'
    emitter = source(RELEASE, path)
    for name in ['print', 'console_log']:
        assert f"'{name}'" in emitter
        add('output', RELEASE, 'frontend output', 'JSS', name, 'output statement', path)
    for name in re.findall(r"'(CURL[A-Z_0-9]+)'", emitter.split('private function isKnownRuntimeConstant', 1)[1].split('], true)', 1)[0]):
        add('curl', RELEASE, 'frontend constant', 'JSS', name, 'recognized constant', path)

    path = 'compiler/my-try/compiler/constants/language.php'
    constants = source(MY_TRY, path)
    for name in re.findall(r"new registered_constant\('([^']+)'|'(\w+)' => \$types", constants):
        add('language constants', MY_TRY, 'installed constant', 'language', name[0] or name[1], 'typed constant', path)

    for module in git('ls-tree', '--name-only', MY_TRY + ':runtime/metadata/modules').splitlines():
        path = 'runtime/metadata/modules/' + module
        catalog = donor(MY_TRY, path)
        for call in catalog['functions']:
            if 'member' in call:
                member = call['member']
                add(catalog['module'], MY_TRY, 'catalog ' + member['dispatch'] + ' method',
                    member['owner'], call['name'], call['id'], path)
        for field in catalog.get('properties', []):
            add(catalog['module'], MY_TRY, 'catalog property', field['owner'], field['name'], field['id'], path)
        for record in catalog.get('types', []):
            for key in ('indexing', 'iteration', 'initialization', 'empty_initialization', 'construction'):
                if key in record:
                    add('container and type protocols', MY_TRY, 'type protocol', record['id'], key,
                        compact(record[key]), path)
    for protocol in ('indexing', 'iteration'):
        path = f'compiler/my-try/compiler/{protocol}/contracts.php'
        assert "['mixed', 'dynamic']" in source(MY_TRY, path)
        for owner in ('mixed', 'dynamic'):
            add('container and type protocols', MY_TRY, 'installed protocol', owner, protocol,
                'boxed language registration', path)
    path = 'compiler/my-try/04_analyze/prepare/semantics/iterator.php'
    for name in re.findall(r"weak\(self::method\(\$owner, '(\w+)'", source(MY_TRY, path)):
        add('iteration', MY_TRY, 'required user method', 'Iterator implementation', name, 'foreach protocol', path)

    path = 'specs/array_semantics.md'
    for name, detail in [
            ('[] read', 'value read, including missing keys'), ('[] write', 'keyed and nested write'),
            ('[] append', 'receiver-specific append'), ('isset', 'null-sensitive keyed probe'),
            ('empty', 'value-sensitive keyed probe'), ('unset', 'keyed removal'),
            ('count', 'fixed array returns its extent')]:
        add('containers and state', RELEASE, 'source contract', 'see admitted receivers', name, detail, path)
    path = 'specs/php/catalog.md'
    for name in ['$argc', '$argv']:
        assert name in source(RELEASE, path)
        add('cli', RELEASE, 'root binding', 'executable root', name, 'root CLI variable', path)
    for name in ['__construct', '__destruct', 'foreach', 'echo']:
        assert name in gen
        add('iteration' if name == 'foreach' else 'output' if name == 'echo' else 'object lifecycle',
            RELEASE, 'generator syntax', 'PHP', name, 'dedicated source form', GEN)

    path = 'specs/builtins/curl/first_pass.md'
    fields = source(RELEASE, path).split('object with public fields:', 1)[1].split('##', 1)[0]
    for name in re.findall(r'^- `(\w+)`', fields, re.M):
        add('curl', RELEASE, 'strict response field', 'curl_response', name, 'strict curl_exec success', path)
    path = 'runtime/include/lang/php/support/php_common.hpp'
    for name in re.findall(r'^using ::scpp::(\w+);', source(RELEASE, path), re.M):
        group = 'strings' if name.startswith('STR_') else 'debug' if name.startswith('DBG_') else 'language constants and types'
        add(group, RELEASE, 'PHP native alias', 'scpp::php', name, 'provider export', path)
    path = 'specs/builtins/regex/preg_split.md'
    for name in re.findall(r'^  - `[12]` = `(PREG_\w+)`', source(RELEASE, path), re.M):
        add('regex', RELEASE, 'supported flag', 'preg_split', name, 'split flag', path)
    for group, form, handler in [
            ('containers and state', 'T[N]', 'array_type'), ('construction', 'Type::[...] / Type::{...}', 'data_construction'),
            ('object lifecycle', 'new', 'object_construction'), ('conversions', '(Type) value', 'cast_expression')]:
        assert f'function {handler}(' in parser
        add(group, MY_TRY, 'syntax', 'language', form, handler, PARSER)

    runtime_doc = source(RELEASE, DECL)
    groups = {'5.1': 'strings', '5.2': 'output', '5.3': 'containers and state', '5.4': 'process and memory',
              '6': 'json', '7': 'filesystem', '8.2': 'io'}
    group = None
    for line in runtime_doc.splitlines():
        heading = re.match(r'^#{1,2} ([0-9.]+) ', line)
        if heading:
            group = groups.get(heading[1].rstrip('.'))
        signature = re.match(r'^### `((\w+)\([^`]+)`', line)
        if signature and group:
            name = signature[2]
            owner = group
            if name.startswith('cli_') or name == 'set_cli_args':
                owner = 'cli'
            elif name == 'shell_exec':
                owner = 'process and memory'
            elif name.startswith('weakref') or name == 'debug_use_count':
                owner = 'ownership'
            add(owner, RELEASE, 'documented runtime function', 'PHP runtime', name, signature[1], DECL)
    for section in re.split(r'(?m)(?=^#{2,3} )', runtime_doc):
        header = re.match(r'^#{2,3} [^\n`]*`([^`]+)`', section)
        if not header:
            continue
        owner = header[1]
        for block in re.finditer(r'(?m)^(Public members|Public fields|Methods|Constructor):\n((?:- `[^\n]+\n)+)', section):
            for detail in re.findall(r'^- `([^`]+)`', block[2], re.M):
                name = re.split(r'[ (]', detail)[0]
                group = 'mysqli' if owner.startswith('mysqli') else 'io' if 'resource' in owner else 'carriers'
                add(group, RELEASE, 'runtime contract ' + block[1].lower(), owner, name, detail, DECL)
    return sorted(rows)


def native_rows():
    rows = []
    path = CPP + '/families.json'
    for group, record in donor(MY_TRY, path).items():
        for owner in record['classes']:
            for method in owner['methods']:
                rows.append([group, owner['id'], method.get('kind', 'method'), method.get('name', group), compact(method.get('parameters', [])),
                             method.get('returns', 'constructed object' if method.get('kind') == 'constructor' else 'void'), path])
        for fn in record.get('functions', []):
            rows.append([group, 'free', 'function', fn['name'], compact(fn.get('parameters', [])),
                         fn.get('returns', 'void'), path])
    for suffix, kind in [('/function_sets', 'function set'), ('/function_families', 'function family')]:
        path = CPP + suffix + '.json'
        for name in donor(MY_TRY, path):
            rows.append([name, 'free', kind, name, 'see parameterized definition', 'see definition', path])
    path = CPP + '.json'
    for name, fn in donor(MY_TRY, path)['functions'].items():
        op = fn['operation']
        rows.append([name, fn['namespace'], 'function', op['name'], compact(op['parameters']), op['returns'], path])
    return sorted(rows)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path, default=HERE)
    directory = parser.parse_args().output
    directory.mkdir(parents=True, exist_ok=True)
    write(directory / 'donor_surfaces.tsv', ['family', 'revision', 'evidence', 'receiver or surface',
          'operation', 'detail', 'donor path'], source_rows())
    write(directory / 'donor_native_methods.tsv', ['family', 'owner', 'kind', 'operation', 'parameters',
          'returns', 'my-try donor path'], native_rows())
