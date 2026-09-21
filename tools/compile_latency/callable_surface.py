#!/usr/bin/env python3
"""Bounded static-method lowering with stable per-callable declarations.

All generated units are rebuilt into a separate object tree for consistent class
identity. Runtime PCH only. Class identity helpers remain; user static methods
become uniquely named free functions. No virtual/instance/inheritance support.
"""
import json
import re
from pathlib import Path
from experiment import functions, require_scratch, write_changed
from stable_locals import normalize_locals, validate_source_names

TARGETS = {
    'compiler_profile_events': 'compile/support/compiler_profile_events',
    'token_kinds': 'compile/frontend_adapter/phs/token_kinds',
}


CLASS = re.compile(r'^class (\w+) \{\npublic:\n([\s\S]*?)^};', re.M)


def class_surface(header, cls, include_adapters=False):
    matches = [m for m in CLASS.finditer(header) if m[1] == cls]
    if len(matches) != 1:
        raise ValueError('Expected one non-inherited public class: ' + cls)
    match = matches[0]
    body = match[2]
    if include_adapters:
        from adapter_surface import extract
        body, _ = extract(body)
    stripped = re.sub(r'^\tstatic (?!.*\b__scpp_).+;\n', '', body, flags=re.M)
    if any(line.strip() and not line.strip().startswith(('static const void* __scpp_static_token()', 'static bool_t __scpp_static_accepts(')) for line in stripped.splitlines()):
        raise ValueError('Data or unsupported members: ' + cls)
    if include_adapters:
        stripped = ''.join(line + '\n' for line in stripped.splitlines() if line.strip())
    declarations = re.findall(r'^\tstatic (.+);$', body, re.M)
    return match, stripped, declarations


def catalog(app, include_adapters=False):
    targets, rejected = {}, []
    original = app / '.prism/generated'
    for path in sorted(original.rglob('*.hpp')):
        rel = path.relative_to(original)
        if any(part.startswith('__') for part in rel.parts):
            continue
        header = path.read_text()
        for match in CLASS.finditer(header):
            cls = match[1]
            try:
                _, _, declarations = class_surface(header, cls, include_adapters)
                if not any('__scpp_' not in decl for decl in declarations):
                    continue
                source = str(rel.with_suffix(''))
                functions((original / (source + '.cpp')).read_text())
                if cls in targets:
                    raise ValueError('Duplicate owner: ' + cls)
                targets[cls] = source
            except ValueError as error:
                rejected.append({'owner': cls, 'source': str(rel), 'reason': str(error)})
    return targets, rejected


def prepare(app, targets=None, include_adapters=False):
    targets = dict(TARGETS if targets is None else targets)
    require_scratch(app)
    validate_source_names(app, [source + '.phs' for source in targets.values()])
    build = app / '.prism/build'
    original = app / '.prism/generated'
    mirror = app / '.prism/callables/generated'
    baseline = (build / 'latency-baseline.ninja').read_text()
    retained_path = build / 'latency-callable-locations.json'
    retained = json.loads(retained_path.read_text()) if retained_path.exists() else {}
    methods, bodies, headers, symbols = {}, {}, {}, {}
    adapters = {}
    for cls, source in targets.items():
        header = headers.get(source + '.hpp', (original / (source + '.hpp')).read_text())
        class_match, stripped, declarations = class_surface(header, cls, include_adapters)
        if include_adapters:
            from adapter_surface import extract
            _, inline = extract(class_match[2])
            for name, adapter in inline.items():
                key = cls + "::" + name
                adapters[key] = adapter
                symbols[key] = "__latency_fn_" + cls + "_" + name
        for decl in declarations:
            match = re.fullmatch(r'(.+?) (\w+)\((.*)\)', decl)
            if not match:
                raise ValueError('Unsupported declaration: ' + decl)
            ret, name, params = match.groups()
            if name.startswith('__scpp_'):
                continue
            key = cls + '::' + name
            symbol = '__latency_fn_' + cls + '_' + name
            symbols[key] = symbol
            methods[key] = f'{ret} {symbol}({params});'
        headers[source + '.hpp'] = header[:class_match.start(2)] + stripped + header[class_match.end(2):]
        bodies[cls] = [(identity, body) for identity, body in functions((original / (source + '.cpp')).read_text()) if identity.split('::')[0] == cls]
        declared = {key for key in methods if key.startswith(cls + '::')}
        defined = {identity for identity, _ in bodies[cls] if not identity.split('::')[1].startswith('__scpp_')}
        if declared != defined:
            raise ValueError('Declaration/body ownership mismatch: ' + cls)

    if include_adapters:
        from stable_locals import TOKENS
        for source, header in headers.items():
            without_forwards = re.sub(r'^class \w+;\n', '', header, flags=re.M)
            required = {token[0] for token in TOKENS.finditer(without_forwards)}
            header = re.sub(r'^class (\w+);\n', lambda m: m[0] if m[1] in required else '', header, flags=re.M)
            headers[source] = header

    if len(set(symbols.values())) != len(symbols):
        raise ValueError('Native symbol collision')

    # Token-aware substitution preserves diagnostic strings and comments.
    pattern = re.compile(r'//[^\n]*|/\*[\s\S]*?\*/|"(?:\\[\s\S]|[^"\\])*"|\'(?:\\[\s\S]|[^\'\\])*\'|\b(?:' + '|'.join(targets) + r')::\w+')
    def rewrite(text):
        if re.search(r'\bR"[^ ()\\\t\r\n]{0,16}\(', text):
            raise ValueError('Raw strings unsupported')
        used = set()
        def sub(m):
            key = m[0]
            if key in symbols:
                used.add(key)
                return symbols[key]
            return key
        rewritten = pattern.sub(sub, text)
        includes = ''.join(f'#include "__callable/{symbols[key]}.hpp"\n' for key in sorted(used))
        return includes + rewritten

    for key, declaration in methods.items():
        # Signature types are declared by the same scoped pack as original code.
        write_changed(mirror / '__callable' / (symbols[key] + '.hpp'),
                      '#pragma once\nnamespace scpp {\n' + declaration + '\n}\n')
    if include_adapters:
        from adapter_surface import lower
        for key, adapter in adapters.items():
            contents, dependencies = lower(key.split('::')[0], adapter, symbols)
            includes = ''.join(f'#include "__callable/{symbols[dep]}.hpp"\n' for dep in sorted(dependencies))
            write_changed(mirror / '__callable' / (symbols[key] + '.hpp'),
                          '#pragma once\n' + includes + 'namespace scpp {\n' + contents + '\n}\n')
    for path in original.rglob('*'):
        rel = path.relative_to(original)
        if not path.is_file() or any(part.startswith('__latency') for part in rel.parts):
            continue
        if path.suffix not in ['.hpp', '.cpp']:
            continue
        text = path.read_text()
        if str(rel) in headers:
            text = headers[str(rel)]
        elif path.suffix == '.cpp':
            if str(rel.with_suffix('')) in targets.values():
                continue
            text = rewrite(text)
        write_changed(mirror / rel, text)

    ninja = baseline.replace('../generated', '../callables/generated')
    # PCH action flags differ between layouts; never share its mutable output.
    write_changed(build / 'callables_runtime_pch.hpp', (build / 'app_pch.hpp').read_text())
    ninja = ninja.replace('app_pch.hpp', 'callables_runtime_pch.hpp')
    # Every app object has a separate path. Runtime library and runtime-only PCH reuse.
    objects = re.findall(r'^build (\S+): compile ', ninja, re.M)
    for obj in sorted(objects, key=len, reverse=True):
        ninja = re.sub(r'(?<!\S)' + re.escape(obj) + r'(?=[:\s])', 'callables/' + obj, ninja)
    additional = []
    replacements = {}
    for source in sorted(set(targets.values())):
        oldobj = 'callables/' + source + '.o'
        edge = re.search(r'^build ' + re.escape(oldobj) + r': compile [^\n]+\n(?:  [^\n]+\n)*', ninja, re.M)
        if edge is None:
            raise ValueError('Missing original compile edge: ' + source)
        flags = re.search(r'^  more_cxxflags = (.+)$', edge[0], re.M)[1]
        residual = [(identity, body) for identity, body in functions((original / (source + '.cpp')).read_text()) if identity.split('::')[0] not in targets]
        replacements[source] = {'flags': flags, 'objects': []}
        if residual:
            contents = '#include "' + source + '.hpp"\nnamespace scpp {\n\tusing namespace ::scpp;\n' + '\n'.join(body for _, body in residual) + '\n}\n'
            write_changed(mirror / (source + '.cpp'), rewrite(contents))
            replacements[source]['objects'].append(oldobj)
        else:
            ninja = ninja[:edge.start()] + ninja[edge.end():]
    for cls, source in targets.items():
        oldobj = 'callables/' + source + '.o'
        flags = replacements[source]['flags']
        ids = retained.setdefault(cls, {})
        values = {}
        newobjects = []
        for identity, body in bodies[cls]:
            name = identity.split('::')[1]
            target = mirror / '__callable' / (cls + '_' + name + '.cpp')
            body = normalize_locals(source + '.phs', identity, body)
            def location(match):
                if identity not in ids:
                    ids[identity] = max(ids.values(), default=-1) + 1
                index = ids[identity]
                values[index] = int(match[2])
                return match[1] + f'__latency_lines_{cls}[{index}]);'
            body = re.sub(r'(SCPP_CALL_DEPTH_GUARD\("[^"\n]+", "[^"\n]+", )(\d+)\);', location, body)
            contents = '#include "' + source + '.hpp"\nnamespace scpp {\n' + body + '\n}\n'
            contents = rewrite(contents)
            contents = f'namespace scpp {{ extern const int __latency_lines_{cls}[]; }}\n' + contents
            write_changed(target, contents)
            obj = 'callables/__callable/' + cls + '_' + name + '.o'
            newobjects.append(obj)
            additional.append(f'build {obj}: compile {target} | runtime_signature.txt callables_runtime_pch.hpp.gch\n  more_cxxflags = {flags}\n')
        meta = mirror / '__callable' / (cls + '_locations.cpp')
        write_changed(meta, 'namespace scpp { extern const int __latency_lines_' + cls + '[] = {' + ','.join(str(values.get(i, 0)) for i in range(max(ids.values(), default=0) + 1)) + '}; }\n')
        obj = 'callables/__callable/' + cls + '_locations.o'
        newobjects.append(obj)
        additional.append(f'build {obj}: compile {meta} | callables_runtime_pch.hpp.gch\n')
        replacements[source]['objects'].extend(newobjects)
    link = re.search(r'^build main: link .+$', ninja, re.M)
    tokens = link[0].split(' ')
    remap = {'callables/' + source + '.o': value['objects'] for source, value in replacements.items()}
    replacement = ' '.join(item for token in tokens for item in remap.get(token, [token]))
    ninja = ninja[:link.start()] + replacement + ninja[link.end():]
    ninja += '\n' + ''.join(additional)
    write_changed(build / 'latency-callables.ninja', ninja)
    write_changed(retained_path, json.dumps(retained, indent=2) + '\n')
    write_changed(build / 'latency-callables-manifest.json', json.dumps({'targets': targets, 'methods': methods, 'adapter_symbols': {key: symbols[key] for key in adapters}, 'limitations': 'static public methods only, no overloads, no virtual dispatch; original scoped signature dependencies'}, indent=2) + '\n')


if __name__ == '__main__':
    import argparse
    parser = argparse.ArgumentParser()
    parser.add_argument('--app', type=Path, required=True)
    prepare(parser.parse_args().app.resolve())
