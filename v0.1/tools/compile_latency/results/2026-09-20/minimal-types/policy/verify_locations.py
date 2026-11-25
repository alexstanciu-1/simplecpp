#!/usr/bin/env python3
"""Verify real call-depth diagnostics after location metadata is separated."""
import argparse
import json
import re
import subprocess
from pathlib import Path
from experiment import write_changed

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--app', type=Path, required=True)
args = parser.parse_args()
app = args.app.resolve()
build = app / '.prism/build'
source = 'compile/incremental/resident_source_unit_frontend_payload_tables.phs'
method = 'resident_source_unit_frontend_payload_tables::payload_segment_source_stride'
original_cpp = (app / '.prism/generated' / Path(source).with_suffix('.cpp')).read_text()
match = re.search(r'SCPP_CALL_DEPTH_GUARD\("' + re.escape(method) + r'", "([^"\n]+)", (\d+)\);', original_cpp)
if not match:
    raise RuntimeError('Missing original diagnostic witness')
source_text = (app / source).read_text()
source_offset = source_text.index('public static function payload_segment_source_stride(')
source_line = source_text[:source_offset].count('\n') + 1
if source_line != int(match[2]):
    raise RuntimeError('Generated diagnostic line disagrees with the source declaration')
cpp = build / 'latency-diagnostic.cpp'
write_changed(cpp, '''#include <cstdio>
#include <cstdlib>
int main(int argc, char** argv) {
    if (argc != 2) return 91;
    scpp::g_call_depth = SCPP_MAX_CALL_DEPTH;
    try {
        (void)scpp::resident_source_unit_frontend_payload_tables::payload_segment_source_stride();
    } catch (const scpp::runtime_error& error) {
        scpp::g_call_depth = 0;
        for (const auto& detail : error.details()) {
            if (detail.key == "source_line") {
                std::printf("source_line=%s\\n", detail.value.c_str());
                return detail.value == argv[1] ? 0 : 92;
            }
        }
        return 93;
    }
    scpp::g_call_depth = 0;
    return 94;
}
''')
ninja = (build / 'latency-locations.ninja').read_text()
lines = ninja.splitlines()
for i, line in enumerate(lines):
    if line.startswith('build main: link '):
        tokens = line.split(' ')
        if tokens.count('main.o') != 1:
            raise RuntimeError('Expected one main.o link input')
        tokens[1] = 'latency-diagnostic:'
        tokens[tokens.index('main.o')] = 'latency-diagnostic.o'
        lines[i] = ' '.join(tokens)
    if line == 'default main':
        lines[i] = 'default latency-diagnostic'
lines.append('build latency-diagnostic.o: compile_latency latency-diagnostic.cpp | latency-project.pch runtime_signature.txt')
write_changed(build / 'latency-diagnostic.ninja', '\n'.join(lines) + '\n')
subprocess.run(['ninja','-f','latency-diagnostic.ninja','-j16','latency-diagnostic'],cwd=build,check=True)
subprocess.run([str(build/'latency-diagnostic'),match[2]],cwd=app,check=True)
print(json.dumps({'method':method,'expected_source_line':int(match[2]),'verified':True}))
