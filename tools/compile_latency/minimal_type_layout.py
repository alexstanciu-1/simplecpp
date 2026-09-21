#!/usr/bin/env python3
"""Experimental per-type publication over the bounded generated C++ grammar.

Preserves definitions byte-for-byte. Dependency extraction is conservative: a
referenced callable's signature supplies complete types to its implementation
consumer, including inferred return values. This is not a resolved-AST extractor.
No aggregate application header or project PCH is reachable in this candidate.
"""
import argparse
import hashlib
import json
import re
from pathlib import Path
from experiment import require_scratch, write_changed
from stable_locals import TOKENS

DEFINITION = re.compile(r'^(class|struct) (\w+)([^;\n]*)\{\n[\s\S]*?^};', re.M)
INCLUDE = re.compile(r'^#include "([^"]+)"\n', re.M)
FORWARD = re.compile(r'^(?:class|struct) \w+;\n', re.M)

def identifiers(text):
    return {m[0] for m in TOKENS.finditer(text) if re.fullmatch(r'[A-Za-z_]\w*', m[0])}

def prepare(app):
    require_scratch(app)
    src = app / '.prism/expanded/generated'
    dst = app / '.prism/minimal/generated'
    build = app / '.prism/build'
    files = {str(p.relative_to(src)): p.read_text() for p in src.rglob('*') if p.suffix in {'.hpp', '.cpp'}}
    types, residuals = {}, {}
    original_names = {m[2] for path in (app / '.prism/generated').rglob('*.hpp') for m in DEFINITION.finditer(path.read_text())}
    for rel, text in sorted(files.items()):
        if not rel.endswith('.hpp') or (any(p.startswith('__') for p in Path(rel).parts) and not rel.startswith('__private_types/')):
            continue
        for match in DEFINITION.finditer(text):
            kind, name, base = match.group(1, 2, 3)
            if rel.startswith('__private_types/') and name not in original_names:
                continue
            if base.strip():
                raise ValueError('Inheritance/attributes require resolved dependency metadata: ' + name)
            if name in types:
                raise ValueError('Duplicate type owner: ' + name)
            types[name] = {'kind': kind, 'definition': match[0], 'owner': rel}
        residual = FORWARD.sub('', INCLUDE.sub('', DEFINITION.sub('', text)))
        residuals[rel] = residual
    names = set(types)
    def forwards(used):
        return ''.join(types[n]['kind'] + ' ' + n + ';\n' for n in sorted(used))
    def header(used):
        return ''.join('#include "__types/' + n + '.hpp"\n' for n in sorted(used))
    fields = {}
    for name, spec in types.items():
        fields[name] = {m[2]: identifiers(m[1]) & names for m in re.finditer(r'^\t([^\n;()]+?) (\w+)(?: = [^\n]+)?;', spec['definition'], re.M)}
    def member_closure(used, text):
        # Bounded conservative approximation of resolved member chains. A field
        # mentioned by the body contributes its type only for known receiver
        # types; iterate to cover nested handles and inferred intermediate values.
        members = set(re.findall(r'(?:->|\.)\s*(\w+)', text))
        result = set(used)
        while True:
            extra = set().union(*(deps for name in result for field, deps in fields[name].items() if field in members))
            if extra <= result: return result
            result |= extra
    dependencies = {}
    for name, spec in types.items():
        definition = spec['definition']
        used = identifiers(definition) & names - {name}
        # shared handles permit forward declarations; by-value fields/templates
        # and inline/static member uses retain complete definitions.
        without_handles = re.sub(r'shared_p\s*<\s*\w+\s*>', '', definition)
        complete = identifiers(without_handles) & used
        dependencies[name] = sorted(complete)
        text = '#pragma once\n#include <scpp/lang/php.hpp>\n#include <cstddef>\n#include <type_traits>\n#include <utility>\n' + header(complete)
        text += 'namespace scpp {\n' + forwards(used - complete) + definition + '\n}\n'
        write_changed(dst / ('__types/' + name + '.hpp'), text)
    visiting, visited = set(), set()
    def visit(name):
        if name in visiting: raise ValueError('Complete-definition cycle: ' + name)
        if name in visited: return
        visiting.add(name)
        for dep in dependencies[name]: visit(dep)
        visiting.remove(name); visited.add(name)
    for name in types: visit(name)
    # Callable headers declare types locally and never import unrelated owners.
    signatures = {}
    for rel, text in files.items():
        if rel.startswith('__callable/') and rel.endswith('.hpp'):
            required_headers = [inc for inc in INCLUDE.findall(text) if inc.startswith(('__callable/', '__counter/'))]
            text = FORWARD.sub('', INCLUDE.sub('', text))
            used = identifiers(text) & names
            text = text.replace('namespace scpp {\n', 'namespace scpp {\n' + forwards(used), 1)
            text = '#include <scpp/lang/php.hpp>\n' + (header(member_closure(used, text)) if re.search(r'\b(?:template|inline)\b', text) else '') + ''.join('#include \"' + inc + '\"\n' for inc in required_headers) + text
            write_changed(dst / rel, text)
            for symbol in re.findall(r'\b(__latency_fn_\w+)\s*\(', text):
                signatures.setdefault(symbol, set()).update(used)
    graph = (build / 'latency-expanded.ninja').read_text()
    active = {p.split('/generated/', 1)[1] for p in re.findall(r'^build \S+: compile\w* (\S+)', graph, re.M) if '/generated/' in p and p.endswith('.cpp')}
    free_functions = {}
    for rel, text in residuals.items():
        for symbol in re.findall(r'^\w[^\n;{}]*?\b(\w+)\([^\n]*\);$', text, re.M):
            if symbol in free_functions: raise ValueError('Ambiguous free declaration: ' + symbol)
            free_functions[symbol] = rel
    consumers = {}
    for rel, text in files.items():
        if not rel.endswith('.cpp'): continue
        original_includes = INCLUDE.findall(text)
        text = INCLUDE.sub('', text)
        tokens = identifiers(text)
        used = tokens & names
        inferred = set().union(*(signatures.get(token, set()) for token in tokens))
        used |= inferred
        used = member_closure(used, text)
        retained = sorted({free_functions[token] for token in tokens if token in free_functions})
        for inc in original_includes:
            if inc not in files and str(Path(rel).parent / inc) in files:
                inc = str(Path(rel).parent / inc)
            if inc.startswith(('__callable/', '__counter/')):
                retained.append(inc)
            elif inc in residuals:
                rest = re.sub(r'^#.*$|namespace scpp \{|^}\s*$', '', residuals[inc], flags=re.M).strip()
                if rest: retained.append(inc)
            elif inc.startswith(('__project_', '__private_types/')):
                pass
            else:
                raise ValueError('Unclassified include: ' + rel + ': ' + inc)
        text = '#include <scpp/lang/php.hpp>\n' + header(used) + ''.join('#include "' + inc + '"\n' for inc in retained) + text
        write_changed(dst / rel, text)
        if rel in active: consumers[rel] = {'direct_types': sorted(tokens & names), 'signature_types': sorted(inferred), 'complete_types': sorted(used)}
    for rel, text in residuals.items():
        used = identifiers(text) & names
        write_changed(dst / rel, '#include <scpp/lang/php.hpp>\n' + header(used) + text)
    for rel, text in files.items():
        if rel.startswith('__counter/') and rel.endswith('.hpp'):
            write_changed(dst / rel, text)
    # Every compile uses the runtime-only PCH; discard forced scoped inventories.
    graph = re.sub(r'\nrule compile_callable_project_pch\n[\s\S]*?(?=rule compile_callable_part)', '\n', graph)
    graph = re.sub(r'^build expanded-project\.pch:.*\n', '', graph, flags=re.M)
    graph = graph.replace('-include-pch expanded-project.pch', '$app_pchflags')
    graph = graph.replace('expanded-project.pch', 'expanded_runtime_pch.hpp.gch')
    graph = re.sub(r'^  more_cxxflags = -include .*__project_units.*\n', '', graph, flags=re.M)
    graph = graph.replace(str(src), str(dst)).replace('../expanded/generated', '../minimal/generated')
    graph = graph.replace('expanded_parts/', 'minimal_parts/').replace('expanded/', 'minimal/')
    graph = graph.replace('build main: link ', 'build minimal-main: link ').replace('default main', 'default minimal-main')
    write_changed(build / 'latency-minimal.ninja', graph)
    # Structural proof: every reachable generated header exists; no aggregate
    # inventory remains, and all original definitions have exactly one owner.
    reachable = set()
    def scan(rel):
        if rel in reachable: return
        assert not rel.startswith(('__project_', '__private_types/')), rel
        reachable.add(rel)
        for inc in INCLUDE.findall((dst / rel).read_text()): scan(inc)
    for rel in active: scan(rel)
    assert '#include' not in (build / 'expanded_runtime_pch.hpp').read_text().replace('#include <scpp/lang/php.hpp>', '')
    for name, spec in types.items():
        assert spec['definition'] in (dst / ('__types/' + name + '.hpp')).read_text()
    manifest = {'scope': 'bounded conservative dependency extraction, not exact resolved AST minimality', 'type_count': len(types), 'active_units': len(active), 'reachable_files': sorted(reachable), 'types': {n: {'owner': s['owner'], 'definition_sha256': hashlib.sha256(s['definition'].encode()).hexdigest(), 'complete_dependencies': dependencies[n]} for n, s in types.items()}, 'consumers': consumers, 'aggregate_headers_reachable': False, 'runtime_only_pch': True}
    write_changed(build / 'latency-minimal-manifest.json', json.dumps(manifest, indent=2) + '\n')
    return manifest

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--app', type=Path, required=True)
    args = parser.parse_args()
    result = prepare(args.app.resolve())
    print(json.dumps({'types': result['type_count'], 'active_units': result['active_units'], 'aggregate_headers_reachable': False}))
