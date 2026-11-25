#!/usr/bin/env python3
"""Measure an added local with stable separately compiled diagnostic metadata."""
import argparse
import json
import subprocess
from pathlib import Path
from experiment import measure
from location_metadata import prepare_locations
from run_corpus import regen, warm, verify

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--app', type=Path, required=True)
parser.add_argument('--repo', type=Path, required=True)
parser.add_argument('--out', type=Path, required=True)
parser.add_argument('--keep-unstable-local-names', action='store_true')
args = parser.parse_args()
app, repo, out = args.app.resolve(), args.repo.resolve(), args.out.resolve()
out.mkdir(parents=True, exist_ok=True)
corpus = json.loads((app.parent / 'corpus.json').read_text())
sources = [r['source'] for r in corpus[:12]]
prepare_locations(app, sources, stable_locals=not args.keep_unstable_local_names)
measure(app, 'latency-locations.ninja', 'locations-setup', out, [])
verify(app, corpus, out, 'locations-setup')
parts = list((app / '.prism/generated/__latency').rglob('*.cpp'))
before = {str(p): p.stat().st_mtime_ns for p in parts}
prepare_locations(app, sources, stable_locals=not args.keep_unstable_local_names)
assert before == {str(p): p.stat().st_mtime_ns for p in parts}, 'Unchanged generation rewrote methods'
measure(app, 'latency-locations.ninja', 'locations-noop', out, [])
verifier = Path(__file__).with_name('verify_locations.py')
subprocess.run(['python3', str(verifier), '--app', str(app)], check=True)
row = corpus[0]
path = app / row['source']
original = path.read_text()
try:
    changed_method = row['original'].replace('{\n', '{\n\t\t$latency_line_insert int = 0;\n', 1)
    path.write_text(original.replace(row['original'], changed_method))
    regen(repo, app, row['source'])
    prepare_locations(app, sources, stable_locals=not args.keep_unstable_local_names)
    measure(app, 'latency-locations.ninja', 'locations-line-insertion', out, [])
    verify(app, corpus, out, 'locations-line-insertion')
    subprocess.run(['python3', str(verifier), '--app', str(app)], check=True)
finally:
    path.write_text(original)
    regen(repo, app, row['source'])
    prepare_locations(app, sources, stable_locals=not args.keep_unstable_local_names)
    warm(app, 'latency-locations.ninja')
    verify(app, corpus, out, 'locations-restored')
