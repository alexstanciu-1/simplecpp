"""Build one generated typed driver and a separately compiled copy of its UTF-8 loop."""
import argparse
import json
from pathlib import Path
import subprocess
import textwrap

parser = argparse.ArgumentParser()
parser.add_argument('root', type=Path)
parser.add_argument('work', type=Path)
args = parser.parse_args()
root, work = args.root.resolve(), args.work.resolve()
app, out = work/'app', work/'out'
app.mkdir(parents=True, exist_ok=True)
(app/'module.json').write_text('{"dependencies":{"runtime":{"modules":["json"]}},"general_api":true}')
fields = {'Scalar': 'string', 'Words': 'vector<string>', 'Numbers': 'vector<int>'}
(app/'records.php').write_text('\n'.join(f'data struct {r} {{public {t} $value;}}' for r,t in fields.items()))
(app/'main.php').write_text('\n'.join(f'${r.lower()} {r}=json_decode_as("{{}}",{r}::class);' for r in fields)+'\nreturn 0;')
generated = subprocess.run(['php', root/'compiler/tests/incremental_executable.php', app, out],
                           input='"run"\n', text=True, capture_output=True, check=True)
assert 'error' not in json.loads(generated.stdout), generated.stdout
manifest = json.loads((out/'manifest.json').read_text())
unit = out/manifest['sources']['app/records.php']
native = root/'runtime/src/json'
reader = (native/'structural_reader.hpp').read_text()
if 'static void validate_bytes(' in reader:
    section = reader.split('static void validate_bytes(', 1)[1].split('\n\t}', 1)[0]
    body = textwrap.dedent(section.split('{\n', 1)[1])
else:
    section = reader.split('void validate_input(', 1)[1]
    start = section.index('\t\t\tfor (std::size_t i = 0;')
    body = textwrap.dedent(section[start:section.index('\n\t\t});', start)])
isolated = work/'isolated.cpp'
isolated.write_text('#include "structural_reader.hpp"\nusing namespace scpp::detail;\n'
                    'using namespace scpp::detail::json;\n'
                    'std::size_t validate(std::string_view input_) {\n'+body+'\nreturn input_.size();\n}\n')
flags = ['clang++', '-O3', '-DNDEBUG', '-stdlib=libc++', '-std=c++23']
subprocess.run([*flags, '-I', native, '-c', isolated, '-o', work/'isolated.o'], check=True)
for record in fields:
    header, = [out/h for h in manifest['headers'] if f'struct {record} {{' in (out/h).read_text()]
    flags.extend(['-include', header])
subprocess.run([*flags, '-I', root/'runtime/generated/public', Path(__file__).with_name('driver.cpp'),
                unit, work/'isolated.o', *manifest['link_inputs'], '-o', work/'driver'], check=True)
