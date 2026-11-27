"""Compile the lifetime probe against a generated typed_json_strings driver directory."""
import json
from pathlib import Path
import subprocess
import sys

root, driver = (Path(p).resolve() for p in sys.argv[1:])
out = driver/'out'
manifest = json.loads((out/'manifest.json').read_text())
includes = []
for record in ('Scalar', 'Words'):
    header, = [out/h for h in manifest['headers'] if f'struct {record} {{' in (out/h).read_text()]
    includes += ['-include', header]
unit = out/manifest['sources']['app/records.php']
assert unit.read_text().count('::scpp::json::read_leaf_record(') == 2
subprocess.run(['clang++', '-std=c++23', '-stdlib=libc++', '-O2', '-I', root/'runtime/generated/public',
                *includes, Path(__file__).with_name('ownership.cpp'), unit, *manifest['link_inputs'],
                '-o', driver/'ownership'], check=True)
subprocess.run([driver/'ownership'], check=True)
