"""Run a differential string-vector study. Use --check for a focused untimed run."""
import argparse
import json
import os
from pathlib import Path
import platform
import random
import statistics
import subprocess

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('bin', type=Path, help='parent of baseline, grouped and fused driver directories')
parser.add_argument('--check', action='store_true')
args = parser.parse_args()
variants = ('baseline', 'grouped', 'fused')
rng = random.Random(22064)


def invoke(variant, binary, documents, loops=None):
    cmd = [args.bin.resolve()/variant/binary, 'vector']
    if loops is not None:
        cmd.append(str(loops))
    payload = ''.join(doc.hex() + '\n' for doc in documents)
    return subprocess.run(cmd, input=payload, text=True, capture_output=True, check=True).stdout.splitlines()


def oracle(doc):
    return 'ok' + ''.join(' ' + word.encode().hex() for word in json.loads(doc)['value'])


def fixture(length, count, escaped):
    half = length // 2
    token = b'"' + b'a'*half + (br'\u0062' if escaped else b'b') + b'z'*(length-half-1) + b'"'
    return b'{"value":[' + b','.join([token]*count) + b']}'


valid = [b'{"value":[]}', b'{"value": [ \n\t ]}', br'{"val\u0075e":["","\u0000","\ud83d\ude03"]}']
invalid = [b'{}garbage', b'{"value":{}}', b'{"value":["ok"],"val\\u0075e":[]}', b'{"value":[],"value" 2}']
for count in [0, 1, 9, 10, 99, 100, 128]:
    words = [json.dumps(s).encode() for s in (['abc', 'x\x00y', '🐍']*count)[:count]]
    prefix = b'{"value": [ \t' + b',\n'.join(words)
    valid.append(prefix + b' \r ] }')
    start = prefix + (b' , \n' if count else b'')
    for tail in [b'false]}', b'null]}', b'2]}', b'{}]}', b'[]]}', br'"\q"]}', br'"\u12xf"]}',
                 br'"\uD800X"]}', br'"\uD800\u1222"]}', b'"\x01"]}', b'"unfinished',
                 b'"unfinished\\', b'"ok" "missing delimiter"]}', b'"ok",]}', b'"ok"', b'"\xff"]}']:
        invalid.append(start + tail)
    if count:
        invalid.extend([prefix + b' false]}', prefix + b';"ok"]}', prefix + b', \n]}'])
documents = valid + invalid
reference = invoke('baseline', 'time', documents)
assert reference[:len(valid)] == list(map(oracle, valid))
assert len(reference) == len(documents) and all(x.startswith('error ') for x in reference[len(valid):])
for variant in variants[1:]:
    observed = invoke(variant, 'time', documents)
    assert observed == reference, [(i, a, b) for i,(a,b) in enumerate(zip(reference, observed)) if a != b][:1]

result = dict(host=platform.node(), cpu=sorted(os.sched_getaffinity(0)), valid=len(valid), invalid=len(invalid), cases={})
if args.check:
    print(result)
    raise SystemExit

cases = [(0, 8, False)] + [(n, size, escaped) for n,size in [(1,8),(3,8),(16,8),(16,129),(16,4096),(128,8),(128,129)]
                           for escaped in [False, True]]
rng.shuffle(cases)
for count,length,escaped in cases:
    wire = fixture(length, count, escaped)
    loops = min(100000, max(160, 8000000 // len(wire)))
    shape = f"{count}x{length}-{'escaped' if escaped else 'plain'}"
    case = result['cases'][shape] = dict(wire_bytes=len(wire), loops=loops)
    for variant in variants:
        assert invoke(variant, 'time', [wire]) == [oracle(wire)]
        _, allocs, size, checksum = invoke(variant, 'counts', [wire], 1)[0].split()
        assert int(checksum) == count
        case[variant] = dict(allocations=int(allocs), allocated_bytes=int(size), ns=[])
    for _ in range(11):
        order = list(variants)
        rng.shuffle(order)
        for variant in order:
            ns, _, _, checksum = invoke(variant, 'time', [wire], loops)[0].split()
            assert int(checksum) == count * loops
            case[variant]['ns'].append(float(ns))
    for variant in variants:
        case[variant]['median_ns'] = statistics.median(case[variant]['ns'])
print(json.dumps(result, indent=2))
