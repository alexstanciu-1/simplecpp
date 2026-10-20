#!/usr/bin/env python3
"""Separate memory/undefined-behavior proof for fully concrete specialization."""
import argparse
import json
import os
from pathlib import Path
import subprocess
import tempfile
from concrete_fixtures import emit
HERE=Path(__file__).resolve().parent
parser=argparse.ArgumentParser()
parser.add_argument('--no-leaks',action='store_true')
args=parser.parse_args()
with tempfile.TemporaryDirectory(prefix='vector-sanitize-') as tmp:
    root=Path(tmp)
    spec=emit(root,'concrete','specialization')
    cmd=['clang++','-std=c++23','-O1','-g','-fsanitize=address,undefined','-fno-omit-frame-pointer']+spec['sources']+['-o','check']
    subprocess.run(cmd,cwd=root,check=True,capture_output=True,text=True)
    env=dict(os.environ,ASAN_OPTIONS='detect_leaks='+('0' if args.no_leaks else '1'),UBSAN_OPTIONS='halt_on_error=1')
    run=subprocess.run([root/'check'],env=env,capture_output=True,text=True)
    report={'command':cmd,'exit_code':run.returncode,'stdout':run.stdout,'stderr':run.stderr,
            'asan_leak_detection':not args.no_leaks,'ubsan_halt_on_error':True}
    (HERE/'sanitizer_results.json').write_text(json.dumps(report,indent=2)+'\n')
    assert run.returncode==0 and run.stdout==spec['expected'],report
print('ASan and UBSan checks passed; leak detection:', not args.no_leaks)
