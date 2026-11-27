"""Compare two boxed drivers in the current directory on one pinned dev002 CPU."""
import argparse
import json
import random
import statistics
import subprocess
from pathlib import Path

parser = argparse.ArgumentParser()
parser.add_argument('--seed', type=int, default=91027)
parser.add_argument('--languages', action='store_true', help='compare optimized boxed decoders with PHP and Node')
args = parser.parse_args()
seed = args.seed
rng = random.Random(seed)
drivers = ['after'] if args.languages else ['before', 'after']
families = ['mixed', 'dynamic']

def run(driver, family, wire, loops):
    return subprocess.check_output(['./' + driver, family, str(loops)], input=wire)

tokens = ['0', '-0', '23', '-918', '9223372036854775807', '-9223372036854775808',
          '9223372036854775808', '-9223372036854775809', '1.0', '1e3', '0.0', '-0.1e-2',
          '01', '-01', '-', '1.', '1e-', '1e999', '3x', '1 2', '+3', '1e-999']
docs = [pattern.replace('TOKEN', token).encode() for token in tokens
        for pattern in ['TOKEN', ' [ true , TOKEN , null ] ', '{"x":TOKEN}', '[1,[TOKEN]]']]
for doc in docs:
    for family in families:
        assert run('before', family, doc, -1) == run('after', family, doc, -1), (doc, family)

cases = {
    'consecutive': list(range(4096)), 'signed': [(-i if i % 2 else i) for i in range(4096)],
    'wide': [34567890000+i for i in range(4096)], 'short': [0, 1, 2],
    'fractions': [i + 0.25 for i in range(4096)],
    'heterogeneous': [1, -2, 0.25, None, True, 'abc', {'k': 1}] * 512,
    'unicode': ['é水😀' * 455] * 16,
}
results = {'seed': seed, 'parity_documents_per_family': len(docs), 'cases': {}}
for name, values in cases.items():
    wire = json.dumps({'value': values}, ensure_ascii=False, separators=(',', ':')).encode()
    loops = min(120000, max(100, 8000000 // len(wire)))
    modes = [(d, f) for d in drivers for f in families]
    samples = {d+'-'+f: [] for d, f in modes}
    for d, f in modes:
        assert json.loads(run(d, f, wire, 0)) == {'value': values}
    if args.languages:
        Path('expected.json').write_text(json.dumps(values))
        modes.extend([('php', 'array'), ('node', 'ordinary')])
        samples.update({'php-array': [], 'node-ordinary': []})
    for trial in range(7):
        rng.shuffle(modes)
        for d, f in modes:
            if d == 'php':
                ns = subprocess.check_output(['php', 'boxed_language.php', str(loops)], input=wire)
            elif d == 'node':
                ns = subprocess.check_output(['node', 'strict_bytes.js', f, str(loops)], input=wire)
            else:
                ns, count = run(d, f, wire, loops).split()
                assert int(count) == loops * len(values)
            samples[d+'-'+f].append(float(ns))
    results['cases'][name] = {'bytes':len(wire), 'loops':loops, 'ns':samples,
                              'MB_s':{k:1000*len(wire)/statistics.median(v) for k,v in samples.items()}}
if args.languages:
    results['php'] = subprocess.check_output(['php', '-r', 'echo PHP_VERSION;'], text=True)
    results['node'] = subprocess.check_output(['node', '-v'], text=True).strip()
print(json.dumps(results, indent=2))
