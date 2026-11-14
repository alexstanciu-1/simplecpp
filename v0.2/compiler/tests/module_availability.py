"""Isolated optional-byte fixture proves module gates independently of production numeric widths."""
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[2]
with tempfile.TemporaryDirectory(prefix='scpp-availability-') as directory:
    work = Path(directory)
    compiler = work / 'compiler'
    shutil.copytree(ROOT / 'compiler', compiler, ignore=shutil.ignore_patterns('tests', '__pycache__'))
    meta = json.loads((ROOT / 'runtime/metadata/runtime.json').read_text())
    meta['core_integers'].remove('uint8')
    meta['compiler']['modules']['default-runtime']['types'].remove('uint8')
    meta['compiler']['modules']['test-numeric'] = {'always_available': False, 'types': ['uint8']}
    metadata = work / 'metadata.json'
    for export in meta['type_exports']:
        if export['identity'] == 'uint8':
            export['declaration_group'] = 'scalar_numeric'
    metadata.write_text(json.dumps(meta))
    publication = json.loads((ROOT / 'build/clang/runtime-publication.json').read_text())
    publication['modules'] = list(set(publication.get('modules', []) + ['numeric']))
    request = work / 'publication.json'
    request.write_text(json.dumps(publication))
    subprocess.run(['php', ROOT / 'tools/api_generator/generate.php', 'emit-compiler', metadata,
                    request, ROOT / 'build/clang/native-layout/facts.json',
                    ROOT / 'build/clang/native-layout/context.json', compiler / 'generated'], check=True)
    subprocess.run(['php', ROOT / 'compiler/tests/module_availability.php', compiler / 'boot.php', work],
                   check=True, env={**os.environ, 'XDEBUG_MODE': 'off'})
