"""Check reproducible physical generation, complete public closure, and naming parity."""
from pathlib import Path
import shutil
import subprocess
import tempfile
import json

root = Path(__file__).resolve().parents[3]
runtime = root/'runtime'
with tempfile.TemporaryDirectory(prefix='scpp-concrete-check-') as directory:
    stage = Path(directory)
    shutil.copytree(runtime/'public',stage/'public')
    subprocess.run(['python3',str(runtime/'tools/concrete_types.py'),'--request',str(runtime/'specs/public_specializations.json'),
                    '--output',str(stage)],check=True)
    manifest = json.loads((stage/'public/specializations.json').read_text())
    paths = ['public/specializations.json', *manifest['generated_files']]
    for record in manifest['types'].values():
        paths += ['public/'+record['header'], 'include/'+record['header']]
    for relative in paths:
        assert (stage/relative).read_bytes() == (runtime/relative).read_bytes(), relative
    request = json.loads((runtime/'specs/public_specializations.json').read_text())
    request['roots'] = ['vector<string>']
    request['apis'] = []
    reduced = stage/'reduced.json'
    reduced.write_text(json.dumps(request))
    result = subprocess.run(['python3', str(runtime/'tools/concrete_types.py'),
        '--request', str(reduced), '--output', str(stage)], capture_output=True, text=True)
    assert result.returncode != 0 and 'fresh output staging' in result.stderr
subprocess.run(['python3',str(root/'compiler/my-try-cpp/tests/type_names.py')],check=True)
print('Physical generation reproducible; runtime and compiler share naming implementation')
