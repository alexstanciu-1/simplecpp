#!/usr/bin/env python3
"""Compare original and extracted adapters against the real safe-subset runtime.

Typed references alias and mutate. Matching-kind mixed references are disabled;
wrong kinds retain parameter diagnostics. Runs outside native latency trials.
"""
import argparse
import json
import re
import subprocess
from pathlib import Path
from adapter_catalog import audit
from callable_surface import CLASS
from adapter_surface import INLINE
from experiment import require_scratch, write_changed

p = argparse.ArgumentParser()
p.add_argument('--app', type=Path, required=True)
p.add_argument('--out', type=Path, required=True)
a = p.parse_args()
app = a.app.resolve()
require_scratch(app)
build = app / '.prism/build'
a.out.mkdir(parents=True, exist_ok=True)
metadata = audit(app / '.prism/generated')
assert not metadata['rejected']
kinds = {'bool_t&': ('bool_t', 'false', 'true', 'as_bool_ref', 'string_t("wrong")'),
         'int_t<>&': ('int_t<>', '7', '17', 'as_int_ref', 'string_t("wrong")'),
         'string_t&': ('string_t', '"before"', '"after"', 'as_string_ref', 'bool_t(false)')}
includes, checks, originals = [], [], []
for owner in metadata['accepted']:
    header = (app / '.prism/generated' / owner['header']).read_text()
    header = next(m[0] for m in CLASS.finditer(header) if m[1] == owner['owner'])
    for name, info in owner['normalizers'].items():
        typ, initial, updated, getter, wrong = kinds[info['return_type']]
        symbol = '__latency_fn_' + owner['owner'] + '_' + name
        includes.append(f'#include "__callable/{symbol}.hpp"')
        adapter = next(m[0] for m in INLINE.finditer(header) if m[3] == name)
        message = re.search(r'throw std::runtime_error\("([^"\n]+)"\)', adapter)[1]
        matching_error = f'mixed_t::{getter} is disabled in the current safe subset: native references to dynamic/interior storage are not supported'
        if '.' + getter + '()' not in adapter:
            matching_error = message
        check = f'''{{
    {typ} typed({initial});
    auto& direct = {symbol}(typed);
    if (&direct != &typed) return 1;
    direct = {typ}({updated});
    if (!static_cast<bool>(php::identical(typed, {typ}({updated})))) return 2;
    mixed_t matching{{{typ}({initial})}};
    try {{ (void){symbol}(matching); return 3; }}
    catch (const std::runtime_error& error) {{
        if (std::string(error.what()) != {json.dumps(matching_error)}) {{ std::fprintf(stderr, "{symbol}: %s\\n", error.what()); return 4; }}
    }}
    mixed_t wrong{{{wrong}}};
    try {{ (void){symbol}(wrong); return 5; }}
    catch (const std::runtime_error& error) {{
        if (std::string(error.what()) != {json.dumps(message)}) return 6;
    }}
}}'''
        checks.append(check)
        originals.append(check.replace(symbol, owner['owner'] + '::' + name))
count = len(checks)
fixture = a.out.resolve() / 'adapter-input.txt'
fixture.write_text('adapter-reference-witness')
includes.append('#include "__callable/__latency_fn_source_files_read_text.hpp"')
wrapper = f'''{{
    string_t path({json.dumps(str(fixture))});
    string_t typed("before");
    if (!static_cast<bool>(__latency_fn_source_files_read_text(path, typed))) return 7;
    if (!static_cast<bool>(php::identical(typed, string_t("adapter-reference-witness")))) return 8;
    mixed_t mixed{{string_t("before")}};
    try {{ (void)__latency_fn_source_files_read_text(path, mixed); return 9; }}
    catch (const std::runtime_error& error) {{
        if (std::string(error.what()) != "mixed_t::as_string_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") return 10;
    }}
}}'''
checks.append(wrapper)
originals.append(wrapper.replace('__latency_fn_source_files_read_text', 'source_files::read_text'))

def program(includes, checks):
    return '\n'.join(includes) + '\n#include <cstdio>\nusing namespace scpp;\nint main() {\n' + '\n'.join(checks) + f'\nstd::puts("normalizers={count};typed_aliasing_and_writes=ok;mixed_rejection=ok;wrapper=ok");\n}}\n'

write_changed(build / 'adapter-semantics.cpp', program(includes, checks))
write_changed(build / 'adapter-original.cpp', program(['#include "' + str(app / '.prism/generated/__project_units.hpp') + '"'], originals))
graph = (build / 'latency-expanded.ninja').read_text()
link = re.search(r'^build main: link (.+)$', graph, re.M)
objects = link[1].split()
assert objects.count('expanded_parts/main.o') == 1
objects[objects.index('expanded_parts/main.o')] = 'adapter-semantics.o'
non_app_inputs = [obj for obj in objects if not obj.endswith('.o')]
assert any(obj.endswith('libruntime.so') for obj in non_app_inputs)
graph = graph[:link.start()] + 'build adapter-semantics: link ' + ' '.join(objects) + graph[link.end():]
graph = graph.replace('default main', '')
graph += '\nbuild adapter-semantics.o: compile_callable_part adapter-semantics.cpp | expanded-project.pch\n'
graph += 'build adapter-original.o: compile adapter-original.cpp | expanded_runtime_pch.hpp.gch\n'
graph += f'build adapter-original-source-files.o: compile {app / ".prism/generated/compile/support/source_files.cpp"} | expanded_runtime_pch.hpp.gch\n'
graph += 'build adapter-original: link adapter-original.o adapter-original-source-files.o ' + ' '.join(non_app_inputs) + '\n'
graph += 'default adapter-semantics adapter-original\n'
write_changed(build / 'adapter-semantics.ninja', graph)
r = subprocess.run(['ninja', '-f', 'adapter-semantics.ninja', '-j12'], cwd=build, capture_output=True, text=True)
(a.out / 'adapter-semantics-build.log').write_text(r.stdout + r.stderr)
r.check_returncode()
outputs = []
for name in ['adapter-original', 'adapter-semantics']:
    r = subprocess.run([str(build / name)], cwd=app, capture_output=True, text=True)
    (a.out / (name + '-run.log')).write_text(r.stdout + r.stderr)
    r.check_returncode()
    outputs.append(r.stdout)
    (a.out / (name + '.cpp')).write_text((build / (name + '.cpp')).read_text())
assert outputs[0] == outputs[1]
print(outputs[1])
