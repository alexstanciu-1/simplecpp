#!/usr/bin/python3
"""Validation-only Clang proxy: compile the compiler at exactly O2 without debug."""
import json
from pathlib import Path
import subprocess
import sys

arguments = ['-O2' if flag.startswith('-O') else flag for flag in sys.argv[1:] if flag != '-g']
result = subprocess.run(['/usr/bin/clang++-18', *arguments])
with Path(__file__).with_suffix('.jsonl').open('a') as log:
    log.write(json.dumps(dict(arguments=arguments, exit_code=result.returncode)) + '\n')
sys.exit(result.returncode if result.returncode >= 0 else 128 - result.returncode)
