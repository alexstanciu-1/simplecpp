"""Build the same generated scalar and vector record drivers against one JSON publication."""
import argparse
import json
import os
from pathlib import Path
import subprocess

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('root', type=Path)
parser.add_argument('work', type=Path)
parser.add_argument('--batched', action='store_true')
args = parser.parse_args()
root, work = args.root.resolve(), args.work.resolve()
app, out = work/'app', work/'out'
app.mkdir(parents=True, exist_ok=True)
(app/'module.json').write_text('{"dependencies":{"runtime":{"modules":["json"]}},"general_api":true}')
(app/'records.phs').write_text('data struct Scalar {public string $value;} data struct Words {public vector<string> $value;}')
(app/'main.phs').write_text('$s Scalar=json_decode_as("{}",Scalar::class);$w Words=json_decode_as("{}",Words::class);return 0;')
generated = subprocess.run(['php', root/'compiler/tests/incremental_executable.php', app, out],
                           input='"run"\n', text=True, capture_output=True, check=True,
                           env={**os.environ, 'XDEBUG_MODE':'off'})
assert 'error' not in json.loads(generated.stdout), generated.stdout
manifest = json.loads((out/'manifest.json').read_text())
unit = out/manifest['sources']['app/records.phs']
expected = 'd.read_strings(field_0)' if args.batched else '.append(d.read_string())'
assert expected in unit.read_text(), f'Expected generated string-vector operation {expected}'
flags = ['clang++', '-O3', '-DNDEBUG', '-stdlib=libc++', '-std=c++23', '-I', root/'runtime/generated/public']
for record in ['Scalar', 'Words']:
    header, = [out/h for h in manifest['headers'] if f'struct {record} {{' in (out/h).read_text()]
    flags.extend(['-include', header])
for name, options in [('time', []), ('counts', ['-DCOUNTERS', '-Wl,--wrap=_Znwm'])]:
    subprocess.run([*flags, Path(__file__).with_name('main.cpp'), unit, *options, *manifest['link_inputs'], '-o', work/name], check=True)
