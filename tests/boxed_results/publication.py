#!/usr/bin/env python3
"""Boxed result dependency, extraction-policy and bounded composition proofs."""
import argparse
import copy
import json
from pathlib import Path
import subprocess
import tempfile
p = argparse.ArgumentParser()
for name in ['generator', 'cxx']: p.add_argument('--'+name, required=True)
for name in ['source', 'facts', 'context']: p.add_argument('--'+name, type=Path, required=True)
a = p.parse_args()
metadata = json.loads((a.source/'runtime/metadata/runtime.json').read_text())
publication = json.loads((a.source/'tests/boxed_results/publication.json').read_text())
with tempfile.TemporaryDirectory(prefix='scpp-boxed-results-') as tmp:
    root = Path(tmp)
    serial = 0
    def generate(m, p, accepted=True):
        global serial
        serial += 1
        case = root/str(serial); case.mkdir()
        (case/'m.json').write_text(json.dumps(m)); (case/'p.json').write_text(json.dumps(p))
        command = [a.generator, 'emit-runtime', case/'m.json', case/'p.json', a.facts, a.context, case/'out']
        result = subprocess.run(list(map(str, command)), capture_output=True, text=True, timeout=60)
        assert result.returncode == (0 if accepted else 1), result.stderr
        if not accepted:
            assert not (case/'out').exists()
            return
        return case/'out', json.loads((case/'out/prepared.json').read_text())
    def compile(out, body, accepted=True):
        source = out/'consumer.cpp'; source.write_text('#include "scpp/carriers.hpp"\nint main() { '+body+'; }\n')
        result = subprocess.run([a.cxx, '-std=c++23', '-stdlib=libc++', '-fsyntax-only', '-I'+str(out/'public'), str(source)], capture_output=True, text=True, timeout=60)
        assert (result.returncode == 0) == accepted, result.stderr
    for family, other, ownership in [('mixed', 'dynamic', 'cow'), ('dynamic', 'mixed', 'shared_container_identity')]:
        pub = copy.deepcopy(publication)
        pub['boxed']['families'] = [family]; pub['boxed']['scalar_conversions'] = False
        pub['carriers']['specializations'] = [{'family':'result', 'payload':family}]
        out, plan = generate(metadata, pub)
        assert plan['carriers'][0]['payload_copy'] == ownership
        assert plan['carriers'][0]['payload_header'] == 'scpp/boxed.hpp'
        compile(out, f'scpp::result_{family} r; scpp::{family} v = r')
        compile(out, f'scpp::result_{other} r', False)
        explicit = copy.deepcopy(metadata)
        explicit['carriers']['extraction'] = 'explicit_checked_one_layer_value_copy'
        out, _ = generate(explicit, pub)
        compile(out, f'scpp::result_{family} r; auto v = static_cast<scpp::{family}>(r)')
        compile(out, f'scpp::result_{family} r; scpp::{family} v = r', False)
        pub['carriers']['specializations'][0]['payload'] = other
        generate(metadata, pub, False)
    for family in ['nullable', 'result_or_false', 'result_or_bool']:
        pub = copy.deepcopy(publication); pub['carriers']['specializations'] = [{'family':family, 'payload':'dynamic'}]
        generate(metadata, pub, False)
    for payload in ['result_dynamic', 'nullable_mixed', 'hash_int64_string']:
        pub = copy.deepcopy(publication); pub['carriers']['specializations'] = [{'family':'result', 'payload':payload}]
        generate(metadata, pub, False)
    pub = copy.deepcopy(publication); del pub['boxed']; generate(metadata, pub, False)
    pub = copy.deepcopy(publication); pub['carriers']['header'] = pub['boxed']['header']; generate(metadata, pub, False)
    pub = copy.deepcopy(publication); pub['carriers']['specializations'].append(pub['carriers']['specializations'][0]); generate(metadata, pub, False)
    out, _ = generate(metadata, publication)
    shuffled = copy.deepcopy(publication); shuffled['carriers']['specializations'].reverse()
    reverse, _ = generate(metadata, shuffled)
    for artifact in json.loads((out/'inventory.json').read_text())['files']:
        assert (out/artifact).read_bytes() == (reverse/artifact).read_bytes(), artifact
    print('boxed result publication passed:', serial, 'cases')
