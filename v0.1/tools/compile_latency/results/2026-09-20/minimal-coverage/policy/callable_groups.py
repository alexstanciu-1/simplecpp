#!/usr/bin/env python3
"""Stable implementation buckets; declaration ownership remains per callable."""
import json
import re
from pathlib import Path
from experiment import functions, write_changed


def prepare(app, targets, limit=180):
    build = app / '.prism/build'
    mirror = app / '.prism/callables/generated'
    state_path = build / 'latency-uniform-callable-groups.json'
    state = json.loads(state_path.read_text()) if state_path.exists() else {}
    graph = (build / 'latency-callables.ninja').read_text()
    replacements, additions = {}, []
    for cls, source in targets.items():
        owned = {cls + '_' + identity.split('::')[1] + '.cpp'
                 for identity, _ in functions((app / '.prism/generated' / (source + '.cpp')).read_text())
                 if identity.split('::')[0] == cls}
        edges = list(re.finditer(r'^build (callables/__callable/' + re.escape(cls) + r'_\w+\.o): compile (\S+) [^\n]*\n(?:  [^\n]+\n)*', graph, re.M))
        assignments = state.setdefault(cls, {})
        next_group = max(assignments.values(), default=-1) + 1
        allocated = 0
        groups = {}
        for edge in edges:
            obj, path = edge[1], edge[2]
            if obj.endswith('_locations.o'):
                continue
            identity = path.rsplit('/', 1)[1]
            if identity not in owned:
                continue
            text = Path(path).read_text()
            if identity not in assignments:
                if allocated >= limit:
                    next_group += 1
                    allocated = 0
                assignments[identity] = next_group
                allocated += len(text.splitlines())
            groups.setdefault(assignments[identity], []).append((obj, text))
            graph = graph.replace(edge[0], '')
        for group, members in sorted(groups.items()):
            target = mirror / '__callable' / f'{cls}_group_{group}.cpp'
            write_changed(target, '\n'.join(text for _, text in members))
            obj = f'callables/__callable/{cls}_group_{group}.o'
            additions.append(f'build {obj}: compile {target} | runtime_signature.txt callables_runtime_pch.hpp.gch\n')
            for old, _ in members:
                replacements[old] = obj
    link = re.search(r'^build main: link .+$', graph, re.M)
    tokens = [replacements.get(token, token) for token in link[0].split(' ')]
    graph = graph[:link.start()] + ' '.join(dict.fromkeys(tokens)) + graph[link.end():]
    write_changed(build / 'latency-callables.ninja', graph + '\n' + ''.join(additions))
    write_changed(state_path, json.dumps(state, indent=2) + '\n')
