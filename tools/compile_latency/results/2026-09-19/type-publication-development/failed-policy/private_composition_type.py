"""Bounded per-type publication for the historical composition-input addition.

Keeps the exact shared object's class definition. No field accessors, layout
changes, reference bridges, or global PCH declarations are introduced.
"""
import re
from callable_surface import CLASS
from stable_locals import TOKENS
from experiment import require_scratch, write_changed

OWNER = 'compile/backend/llvm_text_from_plan.hpp'
TYPE = 'BackendFunctionCompositionInput'
TYPE_HEADER = '__private_types/' + TYPE + '.hpp'
EXPECTED_FIELDS = {'caller_function_name', 'return_type_text', 'target_function_text',
                   'target_function_name', 'argument_list_text',
                   'local_reference_argument_enabled', 'local_argument_type_ref_id',
                   'local_argument_initial_value'}


def mentions(text):
    return any(token[0] == TYPE for token in TOKENS.finditer(text))


def prepare(app, generated, graph):
    require_scratch(app)
    if not generated.resolve().is_relative_to((app / '.prism').resolve()):
        raise ValueError('Expected a generated scratch mirror')
    owner = generated / OWNER
    header = owner.read_text()
    matches = [m for m in CLASS.finditer(header) if m[1] == TYPE]
    if not matches:
        if re.search(r'class\s+' + TYPE + r'\s*[^;]*\{', header):
            raise ValueError('Unsupported composition type definition')
        return {'type': TYPE, 'present': False, 'cpp_consumers': []}
    if len(matches) != 1:
        raise ValueError('Ambiguous composition type ownership')
    match = matches[0]
    fields = re.findall(r'^\t(?:string_t|bool_t|int_t<[^>]*>) (\w+) = .+;$', match[2], re.M)
    for line in match[2].splitlines():
        if not line.strip() or line.strip().startswith(('static const void* __scpp_static_token()', 'static bool_t __scpp_static_accepts(')):
            continue
        if not re.fullmatch(r'\t(?:string_t|bool_t|int_t<[^>]*>) \w+ = .+;', line):
            raise ValueError('Non-field member outside composition metadata')
    if set(fields) != EXPECTED_FIELDS or len(fields) != len(EXPECTED_FIELDS):
        raise ValueError('Composition type outside the known eight-field metadata')
    write_changed(generated / TYPE_HEADER,
                  '#pragma once\n#include <scpp/lang/php.hpp>\nnamespace scpp {\n' + match[0] + '\n}\n')
    # Consume the following separator, retaining the separator before the class.
    header = header[:match.start()] + header[match.end():].lstrip('\n')
    write_changed(owner, header)
    declarations, consumers = [], []
    for path in sorted(generated.rglob('*.hpp')):
        rel = str(path.relative_to(generated))
        if rel == TYPE_HEADER:
            continue
        text = path.read_text()
        text = re.sub(r'^class ' + TYPE + r';\n', '', text, flags=re.M)
        if mentions(text):
            if not rel.startswith('__callable/'):
                raise ValueError('Non-callable header requires private type: ' + rel)
            if 'namespace scpp {\n' not in text:
                raise ValueError('Unsupported callable declaration namespace')
            text = text.replace('namespace scpp {\n', 'namespace scpp {\nclass ' + TYPE + ';\n', 1)
            declarations.append(rel)
        write_changed(path, text)
    active = set()
    for source in re.findall(r'^build \S+: compile\w* (\S+)', graph, re.M):
        if '/generated/' in source:
            active.add(source.split('/generated/', 1)[1])
    for path in sorted(generated.rglob('*.cpp')):
        text = path.read_text()
        if mentions(text):
            rel = str(path.relative_to(generated))
            write_changed(path, '#include "' + TYPE_HEADER + '"\n' + text)
            if rel in active:
                consumers.append(rel)
    return {'type': TYPE, 'present': True, 'header': TYPE_HEADER,
            'callable_forward_declarations': declarations, 'cpp_consumers': consumers,
            'representation': 'original class bytes; shared_p interfaces unchanged'}
