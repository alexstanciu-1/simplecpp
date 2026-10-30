#!/usr/bin/env python3
"""Bounded boxed publication, preparation/rejection and independent-family proofs."""
import argparse
import copy
import json
from pathlib import Path
import subprocess
import tempfile

p = argparse.ArgumentParser()
p.add_argument('--generator', required=True)
p.add_argument('--source', type=Path, required=True)
p.add_argument('--facts', type=Path, required=True)
p.add_argument('--context', type=Path, required=True)
p.add_argument('--cxx', required=True)
a = p.parse_args()
metadata = json.loads((a.source/'runtime/metadata/runtime.json').read_text())
publication = json.loads((a.source/'tests/boxed/publication.json').read_text())
facts = json.loads(a.facts.read_text())
with tempfile.TemporaryDirectory(prefix='scpp-boxed-publication-') as tmp:
    root = Path(tmp)
    serial = 0
    def run(m, pub, f=None, accepted=True):
        global serial
        serial += 1
        case = root/str(serial); case.mkdir()
        for name, data in [('metadata', m), ('publication', pub), ('facts', facts if f is None else f)]:
            (case/(name+'.json')).write_text(json.dumps(data))
        command = [a.generator, 'emit-runtime', case/'metadata.json', case/'publication.json', case/'facts.json', a.context, case/'out']
        result = subprocess.run(list(map(str, command)), capture_output=True, text=True, timeout=60)
        assert result.returncode == (0 if accepted else 1), result.stderr
        if not accepted:
            assert result.stderr and not (case/'out').exists()
            return
        plan = json.loads((case/'out/prepared.json').read_text())
        checked = subprocess.run(list(map(str, command+['--check'])), capture_output=True, text=True, timeout=60)
        assert checked.returncode == 0, checked.stderr
        return case, plan
    case, plan = run(metadata, publication)
    assert [f['name'] for f in plan['boxed']['families']] == ['dynamic', 'mixed']
    assert plan['boxed']['integer_keys'] == [0, 2147483647]
    # Rendering is deterministic when request order changes.
    reverse = copy.deepcopy(publication); reverse['boxed']['families'].reverse()
    other, _ = run(metadata, reverse)
    for artifact in json.loads((case/'out/inventory.json').read_text())['files']:
        assert (case/'out'/artifact).read_bytes() == (other/'out'/artifact).read_bytes(), artifact
    for family, absent in [('mixed', 'dynamic'), ('dynamic', 'mixed')]:
        pub = copy.deepcopy(publication)
        pub['boxed']['families'] = [family]; pub['boxed']['scalar_conversions'] = False
        one, _ = run(metadata, pub)
        source = one/'consumer.cpp'
        source.write_text('#include "scpp/boxed.hpp"\nint main() { scpp::'+family+' value; return value.kind() != scpp::boxed_kind::null; }\n')
        command = [a.cxx, '-std=c++23', '-stdlib=libc++', '-O0', '-I'+str(one/'out/public')]
        for directory in ['numeric', 'sequence', 'boxed', 'hash', 'ownership']:
            command += ['-I'+str(a.source/'runtime/src'/directory)]
        command += [str(source), str(one/'out/src/runtime.cpp'), str(one/'out/src/boxed.cpp')]
        command += [str(a.source/'runtime/src'/name) for name in ['boxed/boxed_value.cpp', 'boxed/boxed_conversion.cpp', 'hash/packed_key.cpp']]
        result = subprocess.run(command+['-o', str(one/'consumer')], capture_output=True, text=True, timeout=60)
        assert result.returncode == 0, result.stderr
        subprocess.run([str(one/'consumer')], check=True, timeout=60)
        source.write_text('#include "scpp/boxed.hpp"\nscpp::'+absent+' forbidden;\n')
        result = subprocess.run([a.cxx, '-std=c++23', '-stdlib=libc++', '-I'+str(one/'out/public'), '-fsyntax-only', str(source)], capture_output=True, timeout=60)
        assert result.returncode != 0
    for families in [[], ['mixed', 'mixed'], ['object'], ['mixed']]:
        pub = copy.deepcopy(publication); pub['boxed']['families'] = families
        run(metadata, pub, accepted=False)
    for key in ['types', 'values', 'string']:
        pub = copy.deepcopy(publication)
        if key == 'string': del pub[key]
        else: pub[key] = []
        run(metadata, pub, accepted=False)
    for header in ['../bad.hpp', 'scpp/string.hpp', 'scpp/values.hpp']:
        pub = copy.deepcopy(publication); pub['boxed']['header'] = header
        run(metadata, pub, accepted=False)
    for field, value in [('string_keys', 'coerce'), ('integer_keys', 'int64'), ('conversion', 'containers')]:
        m = copy.deepcopy(metadata); m['boxed'][field] = value
        run(m, publication, accepted=False)
    pub = copy.deepcopy(publication); pub['values'].remove('float64')
    run(metadata, pub, accepted=False)
    m = copy.deepcopy(metadata); m['floating']['native_inputs'].remove('double')
    run(m, publication, accepted=False)
    for name in ['mixed', 'dynamic', 'boxed_kind']:
        pub = copy.deepcopy(publication)
        pub['ownership'] = {'header': 'scpp/owners.hpp', 'records': [
            {'name': name, 'header': 'scpp/record.hpp', 'constructors': [], 'families': ['shared']}]}
        run(metadata, pub, accepted=False)
    pub = copy.deepcopy(publication); del pub['boxed']
    m = copy.deepcopy(metadata); m['boxed']['conversion'] = 'containers'
    run(m, pub, accepted=False)  # optional metadata is still validated when not demanded
    m = copy.deepcopy(metadata); del m['boxed']
    run(m, publication, accepted=False)
    f = copy.deepcopy(facts); del f['boxed']
    run(metadata, publication, f, accepted=False)
    for field, value in [('alignment', 3), ('size', 0)]:
        f = copy.deepcopy(facts); f['boxed']['mixed'][field] = value
        run(metadata, publication, f, accepted=False)
    # Structurally valid but wrong facts must fail the generated ABI assertions.
    f = copy.deepcopy(facts); f['boxed']['mixed']['size'] += f['boxed']['mixed']['alignment']
    wrong, _ = run(metadata, publication, f)
    command = [a.cxx, '-std=c++23', '-stdlib=libc++', '-fsyntax-only', '-I'+str(wrong/'out/public')]
    command += ['-I'+str(a.source/'runtime/src'/directory) for directory in ['numeric', 'boxed', 'ownership']]
    result = subprocess.run(command+[str(wrong/'out/src/boxed.cpp')], capture_output=True, text=True, timeout=60)
    assert result.returncode != 0 and 'static assertion failed' in result.stderr
    print('boxed publication passed:', serial, 'cases')
