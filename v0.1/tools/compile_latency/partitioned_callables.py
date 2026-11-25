#!/usr/bin/env python3
"""Combine isolated callable headers with stable implementation partitions.

Project PCH excludes the selected static-method surfaces through callable_surface.
Other project declarations remain in it: shared layout edits are NOT isolated.
"""
import json
import re
from pathlib import Path
from experiment import functions, write_changed, require_scratch
from stable_locals import normalize_locals, validate_source_names

SOURCES = [
    'compile/model/source_units',
    'compile/model/project_symbol_index',
    'compile/incremental/resident_source_unit_frontend_payload_tables',
    'compile/support/production_mt_heartbeat',
    'profile/gate_profile_helpers',
]


def prepare(app, sources=None):
    sources = list(SOURCES if sources is None else sources)
    require_scratch(app)
    validate_source_names(app, [s + '.phs' for s in sources])
    build = app / '.prism/build'
    generated = app / '.prism/callables/generated'
    state_path = build / 'latency-callable-partitions.json'
    state = json.loads(state_path.read_text()) if state_path.exists() else {}
    ninja = (build / 'latency-callables.ninja').read_text()
    edges = []
    for source in sources:
        text = (generated / (source + '.cpp')).read_text()
        parts = functions('#include "placeholder.hpp"\n' + text[text.index('namespace scpp {'):])
        retained = state.setdefault(source, {'groups': {}, 'lines': {}})
        groups, ids = retained['groups'], retained['lines']
        next_group = max(groups.values(), default=-1) + 1
        allocated_lines = 0
        grouped = {}
        values = {}
        line_symbol = '__latency_partition_lines_' + source.encode().hex()
        for identity, body in parts:
            body = normalize_locals(source, identity, body)
            if identity not in groups:
                if allocated_lines >= 180:
                    next_group += 1
                    allocated_lines = 0
                groups[identity] = next_group
                allocated_lines += len(body.splitlines())
            def location(m):
                if identity not in ids:
                    ids[identity] = max(ids.values(), default=-1) + 1
                index = ids[identity]
                values[index] = int(m[2])
                return m[1] + line_symbol + f'[{index}]);'
            body = re.sub(r'(SCPP_CALL_DEPTH_GUARD\("[^"\n]+", "[^"\n]+", )(\d+)\);', location, body)
            grouped.setdefault(groups[identity], []).append(body)
        objects = []
        for group, members in sorted(grouped.items()):
            body = '\n'.join(members)
            # Native callable identifiers are unique, validated by callable_surface.
            calls = sorted(set(re.findall(r'\b__latency_fn_\w+', body)))
            includes = ''.join(f'#include "__callable/{symbol}.hpp"\n' for symbol in calls)
            target = generated / '__partitions' / source / f'part_{group}.cpp'
            contents = '#include "' + source + '.hpp"\n' + includes
            contents += f'namespace scpp {{\nextern const int {line_symbol}[];\n' + body + '\n}\n'
            write_changed(target, contents)
            obj = 'callable_parts/' + source + f'/part_{group}.o'
            objects.append(obj)
            edges.append(f'build {obj}: compile_callable_part {target} | callable-project.pch runtime_signature.txt\n')
        target = generated / '__partitions' / source / 'locations.cpp'
        write_changed(target, 'namespace scpp { extern const int ' + line_symbol + '[] = {' + ','.join(str(values.get(i, 0)) for i in range(max(ids.values(), default=0) + 1)) + '}; }\n')
        obj = 'callable_parts/' + source + '/locations.o'
        objects.append(obj)
        edges.append(f'build {obj}: compile {target} | callables_runtime_pch.hpp.gch\n')
        oldobj = 'callables/' + source + '.o'
        ninja, count = re.subn(r'^build ' + re.escape(oldobj) + r': compile [^\n]+\n(?:  [^\n]+\n)*', '', ninja, flags=re.M)
        assert count == 1
        ninja = ninja.replace(' ' + oldobj + ' ', ' ' + ' '.join(objects) + ' ')
    # Reuse the declaration PCH only after volatile callable declarations have
    # been removed from it. Keep separate objects for fair layout comparisons.
    pattern = r'^build (callables/(?:main\.o|__callable/[^\s]+\.o)): compile (\S+) [^\n]*\n(?:  [^\n]+\n)*'
    replacements = {}
    def use_project_pch(match):
        old, cpp = match.groups()
        if old.endswith('_locations.o'):
            return match[0]
        new = old.replace('callables/', 'callable_parts/', 1)
        replacements[old] = new
        return f'build {new}: compile_callable_part {cpp} | callable-project.pch runtime_signature.txt\n'
    ninja = re.sub(pattern, use_project_pch, ninja, flags=re.M)
    def link_inputs(match):
        return ' '.join(replacements.get(token, token) for token in match[0].split(' '))
    ninja = re.sub(r'^build main: link .+$', link_inputs, ninja, flags=re.M)
    rules = '''
rule compile_callable_project_pch
  command = $cxx $cxxflags -x c++-header -MD -MF $out.d $in -o $out
  depfile = $out.d
  deps = gcc
rule compile_callable_part
  command = $cxx $cxxflags -include-pch callable-project.pch -MMD -MF $out.d -MT $out -c $in -o $out
  depfile = $out.d
  deps = gcc
  description = CALLABLE_PART $out
build callable-project.pch: compile_callable_project_pch ../callables/generated/__project_units.hpp
'''
    ninja = rules + ninja + ''.join(edges)
    write_changed(build / 'latency-callable-parts.ninja', ninja)
    write_changed(state_path, json.dumps(state, indent=2) + '\n')


if __name__ == '__main__':
    import argparse
    p = argparse.ArgumentParser()
    p.add_argument('--app', type=Path, required=True)
    prepare(p.parse_args().app.resolve())
