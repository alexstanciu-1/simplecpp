"""Metadata-driven publication of unchanged class and value-struct definitions.

Keeps each exact type definition and its representation. No field accessors, layout
changes, reference bridges, or global PCH declarations are introduced.
"""
import re
from callable_surface import CLASS
from stable_locals import TOKENS
from experiment import require_scratch, write_changed

def prepare(app, generated, graph, spec):
    TYPE = spec["name"]
    OWNER = spec["owner"]
    kind = spec.get("kind", "class")
    pattern = CLASS if kind == "class" else re.compile(r"^struct (\w+) \{\n([\s\S]*?)^};", re.M)
    helpers = {f"{TYPE}* operator->() {{ return this; }}", f"const {TYPE}* operator->() const {{ return this; }}"} if kind == "struct" else set()
    TYPE_HEADER = "__private_types/" + TYPE + ".hpp"
    mentions = lambda text: any(t[0] == TYPE for t in TOKENS.finditer(text))
    require_scratch(app)
    if not generated.resolve().is_relative_to((app / '.prism').resolve()):
        raise ValueError('Expected a generated scratch mirror')
    owner = generated / OWNER
    header = owner.read_text()
    matches = [m for m in pattern.finditer(header) if m[1] == TYPE]
    if not matches:
        if re.search(kind + r'\s+' + TYPE + r'\s*[^;]*\{', header):
            raise ValueError('Unsupported type definition')
        declarations = normalize_declarations(generated, TYPE, TYPE_HEADER, kind, spec.get("complete_consumers", []))
        return {'type': TYPE, 'present': False, 'cpp_consumers': [],
                'callable_forward_declarations': declarations}
    if len(matches) != 1:
        raise ValueError('Ambiguous type ownership')
    match = matches[0]
    fields = {}
    for line in match[2].splitlines():
        if not line.strip() or line.strip() in helpers or line.strip().startswith(('static const void* __scpp_static_token()', 'static bool_t __scpp_static_accepts(')):
            continue
        field = re.fullmatch(r'\t(.+?) (\w+)(?: = [^\n]+)?;', line)
        if not field or field[2] in fields:
            raise ValueError('Unsupported or duplicate field: ' + line)
        fields[field[2]] = field[1]
    required, optional = spec['fields'], spec.get('optional_fields', {})
    if not set(required) <= set(fields) or any((required | optional).get(k) != v for k, v in fields.items()):
        raise ValueError('Class outside recorded field/type metadata: ' + TYPE)
    forwards = ''.join('class ' + name + ';\n' for name in spec.get('forward_dependencies', []))
    write_changed(generated / TYPE_HEADER,
                  '#pragma once\n#include <scpp/lang/php.hpp>\nnamespace scpp {\n' + forwards + match[0] + '\n}\n')
    # Consume the following separator, retaining the separator before the class.
    header = header[:match.start()] + header[match.end():].lstrip('\n')
    write_changed(owner, header)
    declarations = normalize_declarations(generated, TYPE, TYPE_HEADER, kind, spec.get("complete_consumers", []))
    # Resolved value-producing calls require complete types even when auto or
    # temporary expressions never spell the type name in this compilation unit.
    value_producers = set(spec.get('complete_value_producers', []))
    consumers = []
    active = set()
    for source in re.findall(r'^build \S+: compile\w* (\S+)', graph, re.M):
        if '/generated/' in source:
            active.add(source.split('/generated/', 1)[1])
    for path in sorted(generated.rglob('*.cpp')):
        text = path.read_text()
        if mentions(text) or any(t[0] in value_producers for t in TOKENS.finditer(text)):
            rel = str(path.relative_to(generated))
            write_changed(path, '#include "' + TYPE_HEADER + '"\n' + text)
            if rel in active:
                consumers.append(rel)
    return {'type': TYPE, 'present': True, 'header': TYPE_HEADER,
            'callable_forward_declarations': declarations, 'cpp_consumers': consumers,
            'representation': 'original definition bytes; ' + spec.get('representation', 'shared_p interfaces unchanged')}


def normalize_declarations(generated, TYPE, TYPE_HEADER, kind, complete_consumers):
    mentions = lambda text: any(t[0] == TYPE for t in TOKENS.finditer(text))
    declarations = []
    for path in sorted(generated.rglob('*.hpp')):
        rel = str(path.relative_to(generated))
        if rel == TYPE_HEADER:
            continue
        text = path.read_text()
        text = re.sub(r'^(?:class|struct) ' + TYPE + r';\n', '', text, flags=re.M)
        if mentions(text):
            if rel in complete_consumers:
                include = '#include \"' + TYPE_HEADER + '\"\n'
                if include not in text: text = include + text
                write_changed(path, text)
                declarations.append(rel)
                continue
            if not rel.startswith('__callable/'):
                raise ValueError('Non-callable header requires private type: ' + rel)
            if 'namespace scpp {\n' not in text:
                raise ValueError('Unsupported callable declaration namespace')
            text = text.replace('namespace scpp {\n', 'namespace scpp {\n' + kind + ' ' + TYPE + ';\n', 1)
            declarations.append(rel)
        write_changed(path, text)
    return declarations
