#!/usr/bin/env python3
"""Boundary test: add a real field to a shared by-value profile row."""
import argparse
import json
from pathlib import Path
from callable_surface import prepare
from partitioned_callables import prepare as prepare_parts
from layout_isolation import prepare as prepare_layout
from experiment import measure, require_scratch
from run_corpus import regen, warm, verify

p = argparse.ArgumentParser()
p.add_argument('--app', type=Path, required=True)
p.add_argument('--isolated', action='store_true')
args = p.parse_args()
app = args.app.resolve()
config = 'latency-layout.ninja' if args.isolated else 'latency-callable-parts.ninja'
require_scratch(app)
repo = Path(__file__).resolve().parents[2]
corpus = json.loads((app.parent / 'corpus.json').read_text())
out = app.parent / ('layout-isolation-edit-results' if args.isolated else 'layout-results')
out.mkdir(exist_ok=True)
source = 'structures/artifacts/compiler_profile_event_row.phs'
original = (app / source).read_text()
original_main = (app / 'main.phs').read_text()
prepare(app)
prepare_parts(app)
if args.isolated:
    prepare_layout(app)
warm(app, config)
try:
    needle = 'struct CompilerProfileEventRow {'
    assert original.count(needle) == 1
    (app / source).write_text(original.replace(needle, needle + '\n\tpublic uint32 $latency_counter = 0;', 1))
    (app / 'main.phs').write_text(original_main + '''
$latency_row CompilerProfileEventRow = [];
$latency_row->latency_counter = structure_row_ids::uint32_from_int(7);
echo "layout_probe=", (int)$latency_row->latency_counter, "\\n";
''')
    regen(repo, app, source)
    regen(repo, app, 'main.phs')
    prepare(app)
    prepare_parts(app)
    if args.isolated:
        prepare_layout(app)
    label = 'shared-row-add-field'
    measure(app, config, label, out, [])
    verify(app, corpus, out, label)
    assert 'layout_probe=7' in (out / (label + '.run.log')).read_text().splitlines()
finally:
    (app / source).write_text(original)
    (app / 'main.phs').write_text(original_main)
    regen(repo, app, source)
    regen(repo, app, 'main.phs')
    prepare(app)
    prepare_parts(app)
# Native executable is the verified edited variant; rebuilding restored source is
# deliberately a separate operation, not hidden in the reported edit timing.

if args.isolated:
    prepare_layout(app)
