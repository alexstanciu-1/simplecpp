#!/usr/bin/env python3
"""Same changed native inputs at several Ninja job counts; verify exact work set."""
import argparse
import hashlib
import json
import re
import subprocess
from pathlib import Path
from callable_surface import prepare
from partitioned_callables import prepare as prepare_parts
from layout_isolation import prepare as prepare_layout
from experiment import measure, require_scratch
from run_corpus import regen, warm, verify
from signature_case import make_case

p = argparse.ArgumentParser()
p.add_argument('--app', type=Path, required=True)
p.add_argument('--rounds', type=int, default=2)
p.add_argument('--jobs', type=int, nargs='+', default=[1, 2, 4, 8, 12, 16])
p.add_argument('--result-name', default='parallelism-results')
args = p.parse_args()
app = args.app.resolve()
require_scratch(app)
repo = Path(__file__).resolve().parents[2]
corpus = json.loads((app.parent / 'corpus.json').read_text())
assert all(j > 0 for j in args.jobs)
assert Path(args.result_name).name == args.result_name
out = app.parent / args.result_name
out.mkdir(exist_ok=True)
config = 'latency-layout.ninja'
build = app / '.prism/build'
mirror = app / '.prism/layout/generated'
originals, edited, counts = make_case(app)

def generate():
    prepare(app)
    prepare_parts(app)
    prepare_layout(app)

generate()
warm(app, config)
measure(app, config, 'settled-noop', out, [])
verify(app, corpus, out, 'settled-noop')
before = {p: hashlib.sha256(p.read_bytes()).hexdigest() for p in mirror.rglob('*') if p.is_file()}
try:
    for key, text in edited.items():
        (app / key).write_text(text)
        regen(repo, app, key)
    generate()
    changed = [p for p in mirror.rglob('*') if p.is_file() and hashlib.sha256(p.read_bytes()).hexdigest() != before.get(p)]
    plan = subprocess.run(['ninja', '-f', config, '-n', '-d', 'explain'], cwd=build, text=True, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, check=True)
    (out / 'dirty-plan.log').write_text(plan.stdout)
    link = re.search(r'^build main: link (.+)$', (build / config).read_text(), re.M)[1].split()
    objects = [build / item for item in link if item.endswith('.o')]
    manifest = {'source_call_sites': counts, 'source_files_changed': list(edited),
                'changed_generated_inputs': [str(p.relative_to(mirror)) for p in changed],
                'linked_object_count': len(objects), 'cache_launchers': 'disabled',
                'timing_boundary': 'Ninja invocation through full executable link'}
    (out / 'manifest.json').write_text(json.dumps(manifest, indent=2))
    expected = None
    for trial in range(args.rounds):
        jobs = list(args.jobs)
        if trial % 2:
            jobs.reverse()
        for count in jobs:
            # Replay precisely the same edit invalidation; no other source or
            # header is touched. Compiler cache launchers remain disabled.
            for path in changed:
                path.touch()
            mtimes = {path: path.stat().st_mtime_ns for path in objects}
            label = f'signature-r{trial}-j{count}'
            measure(app, config, label, out, [], jobs=count)
            row = json.loads((out / 'measurements.jsonl').read_text().splitlines()[-1])
            outputs = {step['output'] for step in row['native_steps']}
            if expected is None:
                expected = outputs
            assert outputs == expected, 'Scheduling comparison changed the native work set'
            modified = {str(path.relative_to(build)) for path in objects if path.stat().st_mtime_ns != mtimes[path]}
            assert modified == {name for name in outputs if name.endswith('.o')}, 'Unexpected object change outside recorded compilation'
            verify(app, corpus, out, label)
            assert 'signature_probe=123456789' in (out / (label + '.run.log')).read_text().splitlines()
    (out / 'verified-work-set.json').write_text(json.dumps({'rebuilt_outputs': sorted(expected), 'unchanged_objects': len(objects) - len([s for s in expected if s.endswith('.o')])}, indent=2))
finally:
    for key, text in originals.items():
        (app / key).write_text(text)
        regen(repo, app, key)
    generate()
warm(app, config)
verify(app, corpus, out, 'restored')
measure(app, config, 'restored-noop', out, [])
