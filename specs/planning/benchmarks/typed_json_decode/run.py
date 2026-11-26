"""Run with CPU affinity set by the caller. --check performs only differential checks."""
import argparse
import json
import os
import platform
import random
import statistics
import subprocess
from pathlib import Path

modes = ['baseline', 'borrow', 'group', 'fused', 'staged', 'exact', 'transfer']
cases = {'scalar': (-1, 0, 60000), 'empty': (0, 0, 30000), 'three': (3, 0, 24000),
         'two_short': (16, 12, 16000), 'medium': (256, 0, 2700), 'two_long': (2048, 2048, 180),
         'short_strings': (-2, 4, 12000), 'long_strings': (-2, 128, 5000)}
args = argparse.ArgumentParser(description=__doc__)
args.add_argument('work', type=Path)
args.add_argument('--check', action='store_true')
opts = args.parse_args()
work = opts.work.resolve()
rng = random.Random(71283)

def run(mode, name, *args, data=None):
    return subprocess.run([work/mode/name, *map(str, args)], input=data, capture_output=True,
                          check=True, text=True, encoding='utf-8').stdout

valid = [{}, {'version': 7}, {'words': ['a\x00b', '😃', '']}, {'data': [-(2**63), 2**63-1]}]
for _ in range(70):
    fields = [('data', [rng.randrange(-100, 100) for _ in range(rng.randrange(50))]),
              ('extra', [rng.randrange(10) for _ in range(rng.randrange(20))]), ('version', rng.randrange(15))]
    rng.shuffle(fields)
    valid.append(dict(fields))
documents = [json.dumps(v) for v in valid]
expected = []
for v in valid:
    numbers = [''.join(str(i) + ',' for i in v.get(key, [])) for key in ['data', 'extra']]
    words = ''.join(''.join(str(b) + ',' for b in word.encode()) + '/' for word in v.get('words', []))
    expected.append('|'.join([str(v.get('version', 1)), *numbers, words]))
invalid = ['{}garbage', '[]', '{"Data":[1]}', '{"data":[],"d\\u0061ta":[1]}',
           '{"words":["ok",{}]}', '{"words":["\\uD800"]}', '{"data":{}}']
for prefix in ['', '1,', ('1,' * 16), ('1,' * 129)]:
    for token in ['01', '-00', '2e1', '2.', '-', '9223372036854775808', 'true', 'null', '[2]', '"3"', '', '9 9', '2x']:
        invalid.append('{"extra":[' + prefix + token + ']}')
# With no prefix the empty spelling is a valid empty array.
invalid.remove('{"extra":[]}')
corpus = '\n'.join(documents + invalid) + '\n'
reference = run('baseline', 'time', data=corpus)
lines = reference.splitlines()
assert lines[:len(valid)] == expected
assert len(lines) == len(valid) + len(invalid) and all('byte ' in x for x in lines[len(valid):])
for mode in modes[1:]:
    assert run(mode, 'time', data=corpus) == reference, mode
if opts.check:
    print(f'{len(valid)} valid and {len(invalid)} invalid documents matched across {len(modes)} binaries')
    raise SystemExit

results = {'host': platform.node(), 'cpu_affinity': sorted(os.sched_getaffinity(0)),
           'valid': len(valid), 'invalid': len(invalid), 'samples': {}}
for case, (n, m, loops) in cases.items():
    group = results['samples'][case] = {}
    checksums = set()
    for mode in modes:
        length, _, allocs, allocated, copies, checksum = run(mode, 'counts', n, m, 1).split()
        group[mode] = dict(bytes=int(length), allocations=int(allocs), allocated_bytes=int(allocated),
                           vector_copies=int(copies), ns=[])
        checksums.add(int(checksum))
        run(mode, 'time', n, m, max(10, loops//8))
    assert len(checksums) == 1
    for _ in range(9):
        order = modes[:]
        rng.shuffle(order)
        for mode in order:
            fields = run(mode, 'time', n, m, loops).split()
            assert int(fields[5]) == loops * next(iter(checksums))
            group[mode]['ns'].append(float(fields[1]))
    for entry in group.values():
        entry['median_ns'] = statistics.median(entry['ns'])
print(json.dumps(results, indent=2))
