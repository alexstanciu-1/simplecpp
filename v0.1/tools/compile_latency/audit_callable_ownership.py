#!/usr/bin/env python3
"""Check class preservation and exact function ownership in the uniform experiment."""
import argparse
import json
from pathlib import Path
from callable_surface import CLASS, catalog
from experiment import functions, require_scratch
from stable_locals import normalize_locals

p = argparse.ArgumentParser()
p.add_argument('--app', type=Path, required=True)
p.add_argument('--out', type=Path, required=True)
p.add_argument('--isolate-adapters', action='store_true')
a = p.parse_args()
app = a.app.resolve()
require_scratch(app)
targets, rejected = catalog(app, include_adapters=a.isolate_adapters)
original = app / '.prism/generated'
mirror = app / '.prism/callables/generated'
state = json.loads((app / '.prism/build/latency-uniform-callable-groups.json').read_text())
carriers, mixed = 0, []
for source in sorted(set(targets.values())):
    before = {m[1]: m[0] for m in CLASS.finditer((original / (source + '.hpp')).read_text())}
    after = {m[1]: m[0] for m in CLASS.finditer((mirror / (source + '.hpp')).read_text())}
    assert before.keys() == after.keys(), source
    for cls in before:
        if cls not in targets:
            assert before[cls] == after[cls], cls
            carriers += 1
    if any(cls not in targets for cls in before):
        mixed.append(source)
for cls, source in targets.items():
    expected = {cls + '_' + identity.split('::')[1] + '.cpp'
                for identity, _ in functions((original / (source + '.cpp')).read_text())
                if identity.split('::')[0] == cls}
    # Removed methods retain IDs and old files; only current membership matters.
    assert expected <= set(state[cls]), cls
    grouped = {}
    for identity, _ in functions((original / (source + '.cpp')).read_text()):
        if identity.split('::')[0] != cls:
            continue
        filename = cls + '_' + identity.split('::')[1] + '.cpp'
        grouped.setdefault(state[cls][filename], []).append((mirror / '__callable' / filename).read_text())
    for group, contents in grouped.items():
        actual = (mirror / '__callable' / f'{cls}_group_{group}.cpp').read_text()
        assert actual == '\n'.join(contents), (cls, group)
graph = (app / '.prism/build/latency-expanded.ninja').read_text()
objects = next(line for line in graph.splitlines() if line.startswith('build main: link ')).split()[3:]
assert len(objects) == len(set(objects))
assert normalize_locals('', '', 'string_t("SCPP_V2_PROJECT_DIR")') == 'string_t("SCPP_V2_PROJECT_DIR")'
try:
    normalize_locals('', '', 'R"(text)"')
except ValueError:
    pass
else:
    raise AssertionError('Raw literal was not rejected')
result = {'eligible_owners': len(targets), 'rejected_classes': len(rejected),
          'preserved_colocated_classes': carriers, 'mixed_sources': mixed,
          'unique_link_inputs': len(objects), 'targets': targets, 'rejected': rejected}
a.out.parent.mkdir(parents=True, exist_ok=True)
a.out.write_text(json.dumps(result, indent=2) + '\n')
print(json.dumps({k: v for k, v in result.items() if k not in ['targets', 'rejected']}))
