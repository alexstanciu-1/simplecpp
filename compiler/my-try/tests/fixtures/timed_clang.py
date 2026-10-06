#!/usr/bin/python3
"""Test-only compiler proxy; preserve arguments, diagnostics and process exit status."""
import json
from pathlib import Path
import subprocess
import sys
import time

args = sys.argv[1:]
phase = ('version' if '--version' in args else
         'dependency_scan' if '-M' in args else
         'pch_validation' if '-fsyntax-only' in args else
         'pch_creation' if 'c++-header' in args else
         'object_compile' if '-c' in args else 'link')
started = time.monotonic()
result = subprocess.run(['/usr/bin/clang++', *args])
with Path(__file__).with_suffix('.jsonl').open('a') as log:
    log.write(json.dumps(dict(phase=phase, seconds=time.monotonic()-started,
                             arguments=args, exit_code=result.returncode)) + '\n')
sys.exit(result.returncode if result.returncode >= 0 else 128-result.returncode)
