"""Seal physical specialization exports inside an existing runtime publication."""
import hashlib
import json
from pathlib import Path


def sdk_identity(root):
    digest = hashlib.sha256()
    for base in ('include', 'metadata', 'tools/api_generator'):
        for path in sorted((root/base).rglob('*')):
            if path.is_file():
                digest.update(str(path.relative_to(root)).encode())
                digest.update(path.read_bytes())
    return digest.hexdigest()


def seal_package(root, targets):
    path = root/'public/specializations.json'
    manifest = json.loads(path.read_text())
    manifest['sdk_identity'] = sdk_identity(root)
    manifest['artifact_files'] = {
        'lib/lib'+target+'.a':hashlib.sha256((root/('lib/lib'+target+'.a')).read_bytes()).hexdigest()
        for target in targets
    }
    tool = root/'tools/scpp_api_generator'
    if tool.is_file():
        manifest['artifact_files']['tools/scpp_api_generator'] = hashlib.sha256(tool.read_bytes()).hexdigest()
    path.write_text(json.dumps(manifest, indent=2, sort_keys=True)+'\n')
