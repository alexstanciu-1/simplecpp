#!/usr/bin/env python3
"""Experimental resolved-type boundary for one shared-pointer record.

The known counter type has no inheritance, const fields, native exports or by-value
storage. Keep its actual layout; emit per-field reference accessors and a factory.
This is explicit workload metadata, not a production C++ semantic analyzer.
"""
import json
import re
from experiment import write_changed

TYPE = 'FrontendModelKernelCounters'
HEADER = 'structure_kernel/frontend_model_kernel_counters.hpp'
TOKEN = re.compile(r'//[^\n]*|/\*[\s\S]*?\*/|"(?:\\.|[^"\\])*"|\'(?:\\.|[^\'\\])*\'')


def prepare(app, mirror, graph):
    header = (mirror / HEADER).read_text()
    fields = dict((name, native) for native, name in re.findall(r'^\t(int_t<[^\n]*?>) (\w+) = [^\n]+;$', header, re.M))
    assert len(fields) in (5, 6), 'Unexpected counter schema'
    assert 'update_call_count' in fields
    prefix = '__latency_counter_'
    declarations = {'create': f'shared_p<{TYPE}> {prefix}create();'}
    definitions = [f'shared_p<{TYPE}> {prefix}create() {{ return create<{TYPE}>(); }}']
    for name, native in fields.items():
        declarations[name] = f'{native}& {prefix}{name}(const shared_p<{TYPE}>& value);'
        definitions.append(f'{native}& {prefix}{name}(const shared_p<{TYPE}>& value) {{ return value->{name}; }}')
    # Snapshot before emitting derived headers; never read last-run derived output.
    files = [p for p in mirror.rglob('*') if p.is_file() and p.suffix in ('.hpp', '.cpp') and '__counter' not in p.parts]
    uses = {}
    for path in files:
        rel = path.relative_to(mirror)
        if str(rel) == HEADER:
            continue
        text = path.read_text()
        if path.suffix == '.hpp':
            text = re.sub(r'^#include "[^"\n]*frontend_model_kernel_counters\.hpp"\n', '', text, flags=re.M)
        elif str(rel) != HEADER.replace('.hpp', '.cpp'):
            variables = set(re.findall(r'shared_p<'+TYPE+r'>\s*&?\s*(\w+)', text))
            used = set()
            # Transform code spans only. Preserve strings, comments and diagnostics.
            def code(segment):
                if f'create<{TYPE}>()' in segment:
                    segment = segment.replace(f'create<{TYPE}>()', prefix+'create()'); used.add('create')
                for var in sorted(variables):
                    def field(m):
                        name = m[1]
                        if name not in fields:
                            raise ValueError('Unknown counter member: '+name)
                        used.add(name)
                        return prefix+name+'('+var+')'
                    segment = re.sub(r'\b'+re.escape(var)+r'->(\w+)', field, segment)
                return segment
            pieces=[]; offset=0
            for match in TOKEN.finditer(text):
                pieces.extend([code(text[offset:match.start()]),match[0]]);offset=match.end()
            pieces.append(code(text[offset:]));text=''.join(pieces)
            if used:
                text=''.join('#include "__counter/'+name+'.hpp"\n' for name in sorted(used))+text
                uses[str(rel)] = sorted(used)
        write_changed(path,text)
    for name, decl in declarations.items():
        write_changed(mirror/'__counter'/(name+'.hpp'), '#pragma once\n#include <scpp/lang/php.hpp>\nnamespace scpp { class '+TYPE+'; '+decl+' }\n')
    cpp=mirror/'__counter/boundary.cpp'
    write_changed(cpp, '#include "'+HEADER+'"\nnamespace scpp {\n'+'\n'.join(definitions)+'\n}\n')
    obj='expanded/__counter/boundary.o'
    graph=re.sub(r'^(build main: link .+)$',r'\1 '+obj,graph,flags=re.M)
    graph+=f'\nbuild {obj}: compile {cpp} | runtime_signature.txt expanded_runtime_pch.hpp.gch\n'
    write_changed(app/'.prism/build/latency-counter-boundary.json',json.dumps({'type':TYPE,'fields':fields,'implementation_uses':uses,'representation':'existing shared_p; same allocation, same actual fields; reference-returning accessors','limits':'known single non-inherited type; resolved variable names approximated from generated declarations; C++ compilation validates all uses'},indent=2)+'\n')
    return graph
