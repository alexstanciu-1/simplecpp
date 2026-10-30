#!/usr/bin/env python3
"""JSON demand, dependency, public purity and independently selected operation proofs."""
import argparse
import copy
import json
from pathlib import Path
import re
import subprocess
import tempfile
p = argparse.ArgumentParser()
for name in ['generator', 'cxx']: p.add_argument('--'+name, required=True)
for name in ['source', 'facts', 'context']: p.add_argument('--'+name, type=Path, required=True)
a = p.parse_args()
metadata = json.loads((a.source/'runtime/metadata/runtime.json').read_text())
publication = json.loads((a.source/'tests/json/publication.json').read_text())
with tempfile.TemporaryDirectory(prefix='scpp-json-') as tmp:
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
    def compile(out, body, accepted=True, preprocess=False):
        source = out/'consumer.cpp'; source.write_text('#include "scpp/json.hpp"\n'+body+'\n')
        flags = ['-E', '-P'] if preprocess else ['-fsyntax-only']
        result = subprocess.run([a.cxx, '-std=c++23', '-stdlib=libc++', *flags, '-I'+str(out/'public'), str(source)], capture_output=True, text=True, timeout=60)
        assert (result.returncode == 0) == accepted, result.stderr
        if preprocess: assert not re.search(r'\b(template|typename)\b|\bstd::', result.stdout), result.stdout
    for operation, family, payload in [('decode','dynamic','dynamic'), ('decode_mixed','mixed','mixed'), ('encode_mixed','mixed','string'), ('encode_dynamic','dynamic','string')]:
        pub = copy.deepcopy(publication)
        pub['boxed'] = dict(pub['boxed'], families=[family], scalar_conversions=False)
        pub['carriers']['specializations'] = [{'family':'result', 'payload':payload}]
        pub['json']['operations'] = [operation]
        out, plan = generate(metadata, pub)
        assert [op['id'] for op in plan['json']['operations']] == [operation]
        assert plan['json']['key_limit'] == 'checked_decode_error'
        argument = 'scpp::string{}' if operation.startswith('decode') else 'scpp::'+family+'{}'
        name = operation if operation.startswith('decode') else 'encode'
        compile(out, f'int main() {{ auto r = scpp::json::{name}({argument}); }}')
        compile(out, '', preprocess=True)
        compile(out, 'int main() { auto r = scpp::json::encode(scpp::int64{1LL}); }', False)
        # Compile the generated definitions as well as the public surface with a single family.
        includes = ['numeric','sequence','ownership','boxed','hash','json']
        cmd = [a.cxx, '-std=c++23', '-stdlib=libc++', '-fsyntax-only', '-I'+str(out/'public')]
        cmd += ['-I'+str(a.source/'runtime/src'/folder) for folder in includes]
        result = subprocess.run(cmd+[str(out/'src/json.cpp')], capture_output=True, text=True, timeout=60)
        assert result.returncode == 0, result.stderr
        missing = copy.deepcopy(pub); del missing['carriers']; generate(metadata, missing, False)
        missing = copy.deepcopy(pub); missing['boxed']['families'] = ['mixed' if family == 'dynamic' else 'dynamic']
        generate(metadata, missing, False)
    for argument, expression in [('int64','scpp::int64{42LL}'), ('float64','scpp::float64{1.25}'), ('bool_t','scpp::bool_t{false}'), ('string','scpp::string{"null"}')]:
        pub = copy.deepcopy(publication)
        del pub['boxed']
        pub['carriers']['specializations'] = [{'family':'result', 'payload':'string'}]
        pub['json']['operations'] = ['encode_'+argument]
        out, plan = generate(metadata, pub)
        assert 'boxed' not in plan and not (out/'public/scpp/boxed.hpp').exists()
        assert plan['json']['operations'][0]['transport'] == ('bytes' if argument == 'string' else argument)
        compile(out, f'int main() {{ auto r = scpp::json::encode({expression}); }}')
        compile(out, '', preprocess=True)
        for rejected in ['42LL', 'true', '1.25', '"text"', 'scpp::null_t{}']:
            compile(out, f'int main() {{ auto r = scpp::json::encode({rejected}); }}', False)
        compile(out, 'int main() { auto r = scpp::json::decode(scpp::string{}); }', False)
        for other in ['int64', 'float64', 'bool_t', 'string']:
            if other != argument:
                compile(out, f'int main() {{ auto r = scpp::json::encode(scpp::{other}{{}}); }}', False)
        cmd = [a.cxx, '-std=c++23', '-stdlib=libc++', '-fsyntax-only', '-I'+str(out/'public')]
        cmd += ['-I'+str(a.source/'runtime/src'/folder) for folder in ['numeric','sequence','ownership','boxed','hash','json']]
        result = subprocess.run(cmd+[str(out/'src/json.cpp'),str(out/'src/runtime.cpp')], capture_output=True, text=True, timeout=60)
        assert result.returncode == 0, result.stderr
        missing = copy.deepcopy(pub); del missing['carriers']; generate(metadata, missing, False)
        missing = copy.deepcopy(pub)
        if argument == 'string': del missing['string']
        else:
            field = 'types' if argument == 'int64' else 'values'
            missing[field].remove(argument)
        generate(metadata, missing, False)
    for payload in ['int64', 'float64', 'bool_t', 'string']:
        pub = copy.deepcopy(publication)
        del pub['boxed']
        pub['carriers']['specializations'] = [{'family':'result', 'payload':'string'}, {'family':'nullable', 'payload':payload}]
        pub['json']['operations'] = ['encode_nullable_'+payload]
        for policy in ['implicit_checked_one_layer_value_copy', 'explicit_checked_one_layer_value_copy']:
            meta = copy.deepcopy(metadata); meta['carriers']['extraction'] = policy
            out, plan = generate(meta, pub)
            assert 'boxed' not in plan and plan['json']['operations'][0]['nullablePayload'] == payload
            compile(out, f'int main() {{ auto r = scpp::json::encode(scpp::nullable_{payload}{{}}); }}')
            compile(out, '', preprocess=True)
            compile(out, f'int main() {{ auto r = scpp::json::encode(scpp::{payload}{{}}); }}', False)
            includes = ['numeric','sequence','ownership','boxed','hash','json']
            cmd = [a.cxx, '-std=c++23', '-stdlib=libc++', '-fsyntax-only', '-I'+str(out/'public')]
            cmd += ['-I'+str(a.source/'runtime/src'/folder) for folder in includes]
            result = subprocess.run(cmd+[str(out/'src/json.cpp')], capture_output=True, text=True, timeout=60)
            assert result.returncode == 0, result.stderr
        missing = copy.deepcopy(pub); missing['carriers']['specializations'].pop(); generate(metadata, missing, False)
        wrong = copy.deepcopy(pub); wrong['carriers']['specializations'][-1]['family'] = 'result'; generate(metadata, wrong, False)
    for bad in [[], ['decode','decode'], ['from_json'], ['encode']]:
        pub = copy.deepcopy(publication); pub['json']['operations'] = bad; generate(metadata, pub, False)
    for header in ['scpp/boxed.hpp', 'scpp/carriers.hpp', '../json.hpp']:
        pub = copy.deepcopy(publication); pub['json']['header'] = header; generate(metadata, pub, False)
    meta = copy.deepcopy(metadata); del meta['json']; generate(meta, publication, False)
    meta = copy.deepcopy(metadata); meta['json']['key_limit'] = 'throw'; generate(meta, publication, False)
    out, _ = generate(metadata, publication)
    pub = copy.deepcopy(publication); pub['json']['operations'].reverse()
    reverse, _ = generate(metadata, pub)
    for artifact in json.loads((out/'inventory.json').read_text())['files']:
        assert (out/artifact).read_bytes() == (reverse/artifact).read_bytes(), artifact
    pub = copy.deepcopy(publication); del pub['json']
    absent, plan = generate(metadata, pub)
    assert 'json' not in plan and not (absent/'src/json.cpp').exists()
    print('JSON publication passed:', serial, 'cases')
