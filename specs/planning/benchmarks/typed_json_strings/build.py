"""Build the same generated scalar and vector record drivers against one JSON publication."""
import json
import os
from pathlib import Path
import subprocess
import sys

root, work = [Path(arg).resolve() for arg in sys.argv[1:]]
app, out = work/'app', work/'out'
app.mkdir(parents=True, exist_ok=True)
(app/'module.json').write_text('{"dependencies":{"runtime":{"modules":["json"]}},"general_api":true}')
(app/'records.php').write_text('data struct Scalar {public string $value;} data struct Words {public vector<string> $value;}')
(app/'main.php').write_text('$s Scalar=json_decode_as("{}",Scalar::class);$w Words=json_decode_as("{}",Words::class);return 0;')
generated = subprocess.run(['php', root/'compiler/tests/incremental_executable.php', app, out],
                           input='"run"\n', text=True, capture_output=True, check=True,
                           env={**os.environ, 'XDEBUG_MODE':'off'})
assert 'error' not in json.loads(generated.stdout), generated.stdout
manifest = json.loads((out/'manifest.json').read_text())
unit = out/manifest['sources']['app/records.php']
assert '.append(d.read_string())' in unit.read_text(), 'Expected the ordinary per-element vector loop'
flags = ['clang++', '-O3', '-DNDEBUG', '-stdlib=libc++', '-std=c++23', '-I', root/'runtime/generated/public']
for record in ['Scalar', 'Words']:
    header, = [out/h for h in manifest['headers'] if f'struct {record} {{' in (out/h).read_text()]
    flags.extend(['-include', header])
for name, options in [('time', []), ('counts', ['-DCOUNTERS', '-Wl,--wrap=_Znwm'])]:
    subprocess.run([*flags, Path(__file__).with_name('main.cpp'), unit, *options, *manifest['link_inputs'], '-o', work/name], check=True)
