"""Check whether allocator trimming contributes to the 4096-byte document timing cliff."""
import json
import os
from pathlib import Path
import random
import resource
import subprocess
import sys

old, work = (Path(p).resolve() for p in sys.argv[1:])
drivers = {'base': old/'bin/avx2/time', 'reserve': work/'bin/reserve/time'}
rng = random.Random(8192)
rows = []
loops = 3000
for _ in range(5):
    cases = [(n, mode, trim) for n in (4095, 4096) for mode in drivers for trim in (False, True)]
    rng.shuffle(cases)
    for n, mode, trim in cases:
        word = ('abcdefghijklmnopqrstuvwxyz'*((n+25)//26))[:n]
        quoted = '"' + word[:-1] + ('\\u%04x' % ord(word[-1])) + '"'
        wire = ('{"value":[' + ','.join([quoted]*16) + ']}').encode()
        env = os.environ.copy()
        assert 'GLIBC_TUNABLES' not in env and 'MALLOC_TRIM_THRESHOLD_' not in env
        if trim:
            env['GLIBC_TUNABLES'] = 'glibc.malloc.trim_threshold=16777216'
        before = resource.getrusage(resource.RUSAGE_CHILDREN).ru_minflt
        output = subprocess.check_output([drivers[mode], 'vector', str(loops)],
                                         input=wire.hex()+'\n', text=True, env=env)
        faults = resource.getrusage(resource.RUSAGE_CHILDREN).ru_minflt - before
        ns, _, _, checksum = output.split()
        assert int(checksum) == 16*loops
        rows.append(dict(size=n, mode=mode, raised_trim=trim, doc_ns=float(ns), minor_faults=faults))
print(json.dumps(dict(loops=loops, warmup=loops//8, rows=rows), indent=2))
