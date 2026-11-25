#!/usr/bin/env python3
"""Isolate full-application relink cost; keep all existing object files fixed."""
import argparse
import json
from pathlib import Path
from experiment import measure, require_scratch, write_changed
from run_corpus import warm, verify

p = argparse.ArgumentParser()
p.add_argument('--app', type=Path, required=True)
args = p.parse_args()
app = args.app.resolve()
require_scratch(app)
build = app / '.prism/build'
out = app.parent / 'linker-results'
out.mkdir(exist_ok=True)
corpus = json.loads((app.parent / 'corpus.json').read_text())
warm(app, 'latency-layout.ninja')
base = (build / 'latency-layout.ninja').read_text()
variants = {
    'mold-default': '-fuse-ld=mold',
    'mold-j1': '-fuse-ld=mold -Wl,--threads=1',
    'mold-j4': '-fuse-ld=mold -Wl,--threads=4',
    'mold-j8': '-fuse-ld=mold -Wl,--threads=8',
    'lld': '-fuse-ld=lld',
    'bfd': '-fuse-ld=bfd',
}
for name, flags in variants.items():
    write_changed(build / ('latency-link-' + name + '.ninja'), base.replace('-fuse-ld=mold', flags))
for trial in range(3):
    order = list(variants)
    if trial % 2:
        order.reverse()
    for name in order:
        # Remove only the disposable executable, never objects or external files.
        (build / 'main').unlink(missing_ok=True)
        label = f'{name}-r{trial}'
        measure(app, 'latency-link-' + name + '.ninja', label, out, [])
        row = json.loads((out / 'measurements.jsonl').read_text().splitlines()[-1])
        assert {s['output'] for s in row['native_steps']} == {'main'}, 'Unexpected non-link work'
        verify(app, corpus, out, label)
warm(app, 'latency-layout.ninja')
verify(app, corpus, out, 'restored')
