#!/usr/bin/env python3
"""Change a real profiling signature and update its thirteen real call sites."""
import argparse
import hashlib
import json
from pathlib import Path
from callable_surface import prepare
from partitioned_callables import prepare as prepare_parts
from experiment import measure, require_scratch
from run_corpus import regen, warm, verify
from signature_case import make_case

p = argparse.ArgumentParser()
p.add_argument('--app', type=Path, required=True)
args = p.parse_args()
app = args.app.resolve()
require_scratch(app)
repo = Path(__file__).resolve().parents[2]
corpus = json.loads((app.parent / 'corpus.json').read_text())
out = app.parent / 'signature-results'
out.mkdir(exist_ok=True)
originals, edited, counts = make_case(app)
prepare(app)
warm(app, 'latency-baseline.ninja')
warm(app, 'latency-callables.ninja')
prepare_parts(app)
warm(app, 'latency-callable-parts.ninja')
mirror = app / '.prism/callables/generated'
before = {p: hashlib.sha256(p.read_bytes()).hexdigest() for p in mirror.rglob('*') if p.is_file()}
try:
    for source_key, changed in edited.items():
        (app / source_key).write_text(changed)
        regen(repo, app, source_key)
    (out / 'edit-manifest.json').write_text(json.dumps({'call_sites': counts, 'new_parameter': 'minimumUs uint32'}, indent=2))
    prepare(app)
    prepare_parts(app)
    changed_native = [p for p in mirror.rglob('*') if p.is_file() and hashlib.sha256(p.read_bytes()).hexdigest() != before.get(p)]
    (out / 'changed-native-inputs.json').write_text(json.dumps([str(p.relative_to(mirror)) for p in changed_native], indent=2))
    for config in ['latency-baseline.ninja', 'latency-callables.ninja', 'latency-callable-parts.ninja']:
        if config != 'latency-baseline.ninja':
            # These two layouts share some unchanged object paths. Force equivalent
            # edit invalidation for BOTH, including genuinely changed sidecar data.
            for path in changed_native:
                path.touch()
        label = config.removesuffix('.ninja')
        measure(app, config, label, out, [])
        verify(app, corpus, out, label)
        assert 'signature_probe=123456789' in (out / (label + '.run.log')).read_text().splitlines()
finally:
    for source_key, original in originals.items():
        (app / source_key).write_text(original)
        regen(repo, app, source_key)
    prepare(app)

# Restored source and both generated layouts; build readiness is measured separately.
prepare_parts(app)
