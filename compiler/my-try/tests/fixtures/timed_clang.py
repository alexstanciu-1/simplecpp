#!/usr/bin/python3
"""Test-only compiler proxy; preserve arguments, diagnostics and process exit status."""
import json
import os
from pathlib import Path
import subprocess
import sys
import time

args = sys.argv[1:]
phase = ('version' if '--version' in args else
         'dependency_scan' if '-M' in args else
         'template_validation' if '-dump-tokens' in args else
         'pch_validation' if '-fsyntax-only' in args else
         'pch_creation' if 'c++-header' in args else
         'object_compile' if '-c' in args else 'link')
started = time.monotonic()
process = subprocess.Popen(['/usr/bin/clang++', *args])
with Path(__file__).with_suffix('.started.jsonl').open('a') as log:
    log.write(json.dumps(dict(proxy_pid=os.getpid(), compiler_pid=process.pid, phase=phase)) + '\n')
code = process.wait()
with Path(__file__).with_suffix('.jsonl').open('a') as log:
    log.write(json.dumps(dict(phase=phase, started=started, ended=time.monotonic(), seconds=time.monotonic()-started,
                             arguments=args, exit_code=code)) + '\n')
sys.exit(code if code >= 0 else 128-code)
