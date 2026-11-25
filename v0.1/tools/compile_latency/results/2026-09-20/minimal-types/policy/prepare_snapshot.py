#!/usr/bin/env python3
"""Create a fresh read-only-input snapshot; never reuse a live project's caches."""
import argparse
import hashlib
import json
import platform
import shutil
import subprocess
from pathlib import Path

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--source', type=Path, required=True)
parser.add_argument('--vendor', type=Path, required=True)
parser.add_argument('--scratch', type=Path, required=True)
args = parser.parse_args()
source, vendor, scratch = args.source.resolve(), args.vendor.resolve(), args.scratch.resolve()
if scratch.exists() or source in scratch.parents or vendor in scratch.parents:
    raise ValueError('Use a new scratch directory outside both input trees')
scratch.mkdir(parents=True)
shutil.copytree(source, scratch / 'app', ignore=shutil.ignore_patterns('.prism', '.git'))
shutil.copytree(source.parent / 'tests', scratch / 'tests', ignore=shutil.ignore_patterns('.prism', '.git'))
shutil.copytree(vendor, scratch / 'vendor', ignore=shutil.ignore_patterns('.prism', '.git', 'node_modules'))
manifest = {
    'source': str(source),
    'source_sha256': {str(path.relative_to(source)): hashlib.sha256(path.read_bytes()).hexdigest()
                      for path in sorted(source.rglob('*')) if path.is_file() and '.prism' not in path.parts},
    'application_revision': subprocess.check_output(['git', '-C', str(source), 'rev-parse', 'HEAD'], text=True).strip(),
    'vendored_s2s_revision': subprocess.check_output(['git', '-C', str(vendor), 'rev-parse', 'HEAD'], text=True).strip(),
    'hardware': platform.uname()._asdict(),
    'cpu': subprocess.check_output(['lscpu'], text=True),
    'compiler': subprocess.check_output(['clang++', '--version'], text=True),
    'ninja': subprocess.check_output(['ninja', '--version'], text=True).strip(),
    'jobs': 16,
    'frontend_assumed_seconds': 1.5,
    'native_budget_seconds': 8.5,
    'cache_launchers': 'disabled',
}
(scratch / 'manifest.json').write_text(json.dumps(manifest, indent=2) + '\n')
print(scratch)
