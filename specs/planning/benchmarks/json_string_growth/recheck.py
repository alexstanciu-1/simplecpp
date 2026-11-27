"""Interleave the three neighboring lengths on dev002 with longer timing samples."""
import json
import random
import subprocess
import sys
from pathlib import Path

work = Path(sys.argv[1])
rng = random.Random(4096)
loops = 1500
rows = []
for _ in range(15):
    sizes = [4095, 4096, 4097]
    rng.shuffle(sizes)
    for n in sizes:
        word = ('abcdefghijklmnopqrstuvwxyz'*((n+25)//26))[:n]
        encoded = '"' + word[:-1] + ('\\u%04x' % ord(word[-1])) + '"'
        wire = ('{"value":[' + ','.join([encoded]*16) + ']}').encode()
        modes = ['base', 'fallback', 'selective']
        rng.shuffle(modes)
        row = dict(size=n)
        for mode in modes:
            ns, _, _, checksum = subprocess.check_output([work/'bin'/mode/'time', 'vector', str(loops)],
                input=wire.hex()+'\n', text=True).split()
            assert int(checksum) == loops*16
            row[mode] = float(ns)
        rows.append(row)
print(json.dumps(dict(loops=loops, rows=rows), indent=2))
