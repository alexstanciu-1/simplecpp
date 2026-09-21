#!/usr/bin/env python3
"""Replay the public profiling-helper addition shape from commit 86445c02.

The current workload has evolved; this is a similar edit, not historical checkout
reproduction. Add a public stage ID and dispatch branch, exercise it from main.
"""
import json
from pathlib import Path
from experiment import measure, require_scratch
from run_corpus import regen, warm, verify
from location_metadata import prepare_locations

app = Path('/tmp/scpp-edit-latency-20260919/app')
repo = Path(__file__).resolve().parents[2]
require_scratch(app)
corpus = json.loads((app.parent / 'corpus.json').read_text())
sources = [r['source'] for r in corpus[:12]]
out = app.parent / 'historical-profile-results'
out.mkdir(exist_ok=True)
source = 'compile/support/compiler_profile_events.phs'
original = (app / source).read_text()
main = (app / 'main.phs').read_text()
(out / 'original-profile.phs').write_text(original)
(out / 'original-main.phs').write_text(main)
# Both layouts start correctly linked with the unedited full application.
prepare_locations(app, sources)
warm(app, 'latency-baseline.ninja')
warm(app, 'latency-locations.ninja')
changed = original.replace('final class compiler_profile_events {', '''final class compiler_profile_events {
\tpublic static function stage_latency_probe_id(): uint16 { return structure_row_ids::uint16_from_int(28); }
''', 1).replace('public static function stage_name(uint16 $stageId): string {', '''public static function stage_name(uint16 $stageId): string {
\t\tif ((int)$stageId === (int)compiler_profile_events::stage_latency_probe_id()) { return "latency_probe"; }
''', 1)
assert changed != original
try:
    (app / source).write_text(changed)
    (app / 'main.phs').write_text(main + '\necho "historical_profile=" . compiler_profile_events::stage_name(compiler_profile_events::stage_latency_probe_id()) . "\\n";\n')
    regen(repo, app, source)
    regen(repo, app, 'main.phs')
    prepare_locations(app, sources)
    for config in ['latency-baseline.ninja', 'latency-locations.ninja']:
        # Header timestamp explicitly forces equivalent invalidation in both layouts;
        # otherwise shared objects from the first build could hide second-build work.
        (app / '.prism/generated' / Path(source).with_suffix('.hpp')).touch()
        label = config.removesuffix('.ninja')
        measure(app, config, label, out, [])
        verify(app, corpus, out, label)
        assert 'historical_profile=latency_probe' in (out / (label + '.run.log')).read_text().splitlines()
finally:
    (app / source).write_text(original)
    (app / 'main.phs').write_text(main)
    regen(repo, app, source)
    regen(repo, app, 'main.phs')
    prepare_locations(app, sources)
# Deliberately leave native rebuild to the next experiment, restoring all source.
