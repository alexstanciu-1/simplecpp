"""Run on dev002 from an isolated directory with bin/base and bin/candidate."""
import json
import os
from pathlib import Path
import random
import statistics
import subprocess
import sys

rng = random.Random(int(sys.argv[1]))
candidate = sys.argv[2] if len(sys.argv) > 2 else 'candidate'
def output(mode, shape, docs, loops=None):
    cmd = [Path('bin')/mode/'driver', shape]
    if loops: cmd.append(str(loops))
    return subprocess.check_output(cmd, input=''.join(d.hex()+'\n' for d in docs),text=True).splitlines()

def parity(shape, docs):
    base = output('base', shape, docs)
    assert len(base) == len(docs) and base == output(candidate, shape, docs)
    return dict(valid=sum(x.startswith('ok') for x in base), invalid=sum(x.startswith('error') for x in base))

prefix=b'{"value":['
nums=[prefix+b'1,'*n+x+b']}' for n in (0,1,7,32) for x in (
    b'01',b'00',b'-0',b'0',b'-01',b'12345',b'2.5',b'1e2',b'1234x',b'1 2',b'-',b'1,',
    b'9223372036854775807',b'-9223372036854775808',b'9223372036854775808',b'-9223372036854775809')]
bad=[]
for ch in ('é','😀'):
    for n in range(32):
        s=ch.encode()*n
        for invalid in (b'\xc0\x80',b'\xc1\xbf',b'\xdfx',b'\xf0\x8f\xbf\xbf',b'\xf4\x90\x80\x80',b'\xf5\x80\x80\x80',b'\xed\xa0\x80',b'\xf0\x9f\x98',b'\x80'):
            bad.append(prefix+b'"'+s+invalid+s+b'"]}')
r={'cpu':sorted(os.sched_getaffinity(0)), 'seed':int(sys.argv[1]), 'diagnostics':{
    'numbers':parity('numbers',nums),'unicode':parity('words',bad)},'cases':{}}
assert r['diagnostics']['unicode']['invalid']==len(bad)
cases=[('ints-'+str(n),'numbers',list(range(n))) for n in (3,128,4096)]
cases+= [('ints-signed','numbers',[i*(-1 if i%2 else 1) for i in range(4096)]),
         ('ints-wide','numbers',[34567890000+i for i in range(4096)])]
for label,word in [('ascii','a'*4096),('two','é'*2048),('three','水'*1365),('four','😀'*1024),
                   ('mixed','é水😀'*455),('varied-two', ''.join(chr(128+(i*7)%1920) for i in range(2048))),
                   ('varied-four',''.join(chr(0x10000+(i*1777)%0x100000) for i in range(1024)))]:
    cases.append((label,'words',[word]*16))
rng.shuffle(cases)
for label,shape,values in cases:
    wire=json.dumps({'value':values},ensure_ascii=False,separators=(',',':')).encode()
    Path('expected.json').write_text(json.dumps(values))
    oracle='ok'+''.join(' '+(v.encode().hex() if shape=='words' else str(v)) for v in values)
    for mode in ('base',candidate): assert output(mode,shape,[wire])==[oracle]
    loops=min(100000,max(160,12000000//len(wire)))
    ns={m:[] for m in ('base',candidate,'node-text','node-bytes')}
    for _ in range(7):
        order=list(ns);rng.shuffle(order)
        for m in order:
            if m.startswith('node-'):
                val=subprocess.check_output(['node','bench.js',m[5:],str(loops)],input=wire)
            else:
                val,checksum=output(m,shape,[wire],loops)[0].split()
                assert int(checksum)==len(values)*loops
            ns[m].append(float(val))
    r['cases'][label]={'bytes':len(wire),'loops':loops,'samples_ns':ns,
                       'MB_s':{m:1000*len(wire)/statistics.median(v) for m,v in ns.items()}}
print(json.dumps(r,indent=2))
