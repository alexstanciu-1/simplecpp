"""Run with CPU affinity from the scratch directory containing the study drivers."""
import json
import os
import random
import statistics
import subprocess
from pathlib import Path

rng = random.Random(49153)
modes = ['base', 'general', 'candidate', 'fatal', 'checked', 'ordinary']
cases = {
    'consecutive': list(range(4096)),
    'signed': [(-i if i % 2 else i) for i in range(4096)],
    'wide': [34567890000 + i for i in range(4096)],
    'two': ['é' * 2048] * 16,
    'three': ['水' * 1365] * 16,
    'four': ['😀' * 1024] * 16,
    'mixed': ['é水😀' * 455] * 16,
}
result = {'cpu': sorted(os.sched_getaffinity(0)), 'node': subprocess.check_output(
    ['node', '-v'], text=True).strip(), 'seed': 49153, 'cases': {}}
for name, values in cases.items():
    wire = json.dumps({'value': values}, ensure_ascii=False, separators=(',', ':')).encode()
    Path('expected.json').write_text(json.dumps(values))
    shape = 'numbers' if isinstance(values[0], int) else 'words'
    oracle = 'ok ' + ' '.join(v.encode().hex() if isinstance(v, str) else str(v) for v in values)
    encoded = (wire.hex() + '\n').encode()
    loops = 24000000 // len(wire)
    samples = {m: [] for m in modes}
    for m in ('base', 'general', 'candidate'):
        assert subprocess.check_output([f'bin/{m}/driver', shape], input=encoded).decode().strip() == oracle
    for trial in range(7):
        rng.shuffle(modes)
        for m in modes:
            if m in ('base', 'general', 'candidate'):
                ns, checksum = subprocess.check_output([f'bin/{m}/driver', shape, str(loops)], input=encoded).split()
                assert int(checksum) == loops * len(values)
            else:
                ns = subprocess.check_output(['node', 'strict_bytes.js', m, str(loops)], input=wire)
            samples[m].append(float(ns))
    result['cases'][name] = {'bytes': len(wire), 'loops': loops, 'ns': samples, 'MB_s': {
        m: 1000 * len(wire) / statistics.median(v) for m, v in samples.items()}}
print(json.dumps(result, indent=2))
