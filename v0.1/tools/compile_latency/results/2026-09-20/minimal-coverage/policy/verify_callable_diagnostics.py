#!/usr/bin/env python3
"""Check that native free-function lowering preserves source method diagnostics."""
import argparse
import re
import subprocess
from pathlib import Path
from experiment import require_scratch, write_changed

p = argparse.ArgumentParser()
p.add_argument('--app', type=Path, required=True)
p.add_argument('--expanded', action='store_true')
p.add_argument('--tables', action='store_true', help='Check the isolated model-table callable in the expanded layout')
args = p.parse_args()
if args.tables: args.expanded = True
app = args.app.resolve()
require_scratch(app)
build = app / '.prism/build'
source = 'compile/frontend_adapter/phs/frontend_model_builder.phs' if args.expanded else 'compile/support/compiler_profile_events.phs'
method = 'declaration_kind_function_id' if args.expanded else 'stage_name'
if args.tables:
    source = 'structure_kernel/frontend_model_tables.phs'
    method = 'none_id'
text = (app / source).read_text()
expected = str(text[:text.index('public static function ' + method + '(')].count('\n') + 1)
cpp = '''#include "__callable/__latency_fn_compiler_profile_events_stage_name.hpp"
#include <cstdio>
int main() {
    scpp::g_call_depth = SCPP_MAX_CALL_DEPTH;
    try { (void)scpp::__latency_fn_compiler_profile_events_stage_name(scpp::int_t<std::uint16_t>(1)); }
    catch (const scpp::runtime_error& error) {
        scpp::g_call_depth = 0;
        bool line_ok = false;
        for (const auto& detail : error.details()) {
            std::printf("%s=%s\\n", detail.key.c_str(), detail.value.c_str());
            if (detail.key == "source_line") line_ok = detail.value == "EXPECTED";
        }
        return line_ok ? 0 : 92;
    }
    return 93;
}
'''.replace('EXPECTED', expected)
if args.expanded:
    cpp = cpp.replace('compiler_profile_events_stage_name', 'frontend_model_builder_declaration_kind_function_id')
    cpp = cpp.replace('scpp::int_t<std::uint16_t>(1)', '')
if args.tables:
    cpp = cpp.replace('frontend_model_builder_declaration_kind_function_id', 'frontend_model_tables_none_id')
write_changed(build / 'callable-diagnostic.cpp', cpp)
ninja = (build / ('latency-expanded.ninja' if args.expanded else 'latency-callables.ninja')).read_text()
entry = 'expanded_parts/main.o' if args.expanded else 'callables/main.o'
match = re.search(r'^build main: link (.+)$', ninja, re.M)
objects = match[1].split()
assert objects.count(entry) == 1
objects[objects.index(entry)] = 'callable-diagnostic.o'
ninja = ninja[:match.start()] + 'build callable-diagnostic: link ' + ' '.join(objects) + ninja[match.end():]
ninja = ninja.replace('default main', 'default callable-diagnostic')
if args.expanded:
    ninja += '\nbuild callable-diagnostic.o: compile_callable_part callable-diagnostic.cpp | expanded-project.pch\n'
else:
    ninja += '\nbuild callable-diagnostic.o: compile callable-diagnostic.cpp | callables_runtime_pch.hpp.gch\n  more_cxxflags = -include ../callables/generated/__project_units.hpp\n'
write_changed(build / 'callable-diagnostic.ninja', ninja)
subprocess.run(['ninja', '-f', 'callable-diagnostic.ninja', '-j12'], cwd=build, check=True)
result = subprocess.run([str(build / 'callable-diagnostic')], cwd=app, text=True, capture_output=True, check=True)
identity = 'frontend_model_builder::declaration_kind_function_id' if args.expanded else 'compiler_profile_events::stage_name'
if args.tables: identity = 'frontend_model_tables::none_id'
assert identity in result.stdout, result.stdout
assert str(app / source) in result.stdout, result.stdout
print(result.stdout)
