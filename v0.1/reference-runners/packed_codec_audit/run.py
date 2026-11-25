#!/usr/bin/env python3
"""Observe current legacy donor defects; this is audit evidence, not a correctness suite."""
import argparse
import json
from pathlib import Path
import subprocess
import tempfile

parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--cxx',default='clang++')
parser.add_argument('--output',type=Path)
a=parser.parse_args()
here=Path(__file__).resolve().parent
root=here.parents[4]

def run(command):
    p=subprocess.run(list(map(str,command)),capture_output=True,text=True,timeout=60)
    if p.returncode: raise RuntimeError(p.stdout+p.stderr)
    return p.stdout

report={'revision':run(['git','-C',root,'rev-parse','HEAD']).strip(),
        'compiler':run([a.cxx,'--version']).splitlines()[0],
        'flags':['-std=c++23','-stdlib=libc++','-O0']}
with tempfile.TemporaryDirectory(prefix='scpp-packed-audit-') as tmp:
    tmp=Path(tmp)
    base=[a.cxx,*report['flags'],'-I'+str(root/'runtime/include'),'-I'+str(here)]
    pool=root/'runtime/include/scpp/util/global_string_pool.cpp'
    for php in [0,1]:
        exe=tmp/('probe'+str(php))
        run(base+['-DSCPP_LANGUAGE_TARGET_PHP='+str(php),here/'probe.cpp',pool,'-o',exe])
        report['php_'+str(php)]=run([exe]).splitlines()
    exe=tmp/'failures'
    run(base+[here/'failures.cpp',pool,'-o',exe])
    report['failures']=[run([exe,mode,str(n)]).strip() for mode in ['transition','pool'] for n in range(7)]
text=json.dumps(report,indent=2)+'\n'
if a.output: a.output.write_text(text)
print(text,end='')
