"""Measure string suffix scanning on dev002 with the fused decoder as the reference."""
import json
import os
from pathlib import Path
import platform
import random
import statistics
import string
import subprocess
import sys

bins = Path(sys.argv[1]).resolve()
variants = ('fused', 'bulk', 'word8', 'avx2')
node = Path(__file__).with_name('node.js')
rng = random.Random(32768)


def invoke(mode, binary, docs, *args):
    cmd = [bins/mode/binary, 'vector', *map(str, args)]
    return subprocess.run(cmd, input=''.join(doc.hex()+'\n' for doc in docs),
                          capture_output=True, text=True, check=True).stdout.splitlines()


def doc(token):
    return b'{"value":["previous",'+token+b',"next"]}'


def oracle(wire):
    return 'ok' + ''.join(' '+s.encode().hex() for s in json.loads(wire)['value'])


def fixture(size, position):
    value = (string.ascii_lowercase * ((size+25)//26))[:size]
    if position == 'plain':
        spelling = value
    else:
        i = {'first':0, 'middle':size//2, 'last':size-1}[position]
        spelling = value[:i] + ('\\u%04x' % ord(value[i])) + value[i+1:]
    return ('{"value":[' + ','.join(['"'+spelling+'"']*16) + ']}').encode()


valid, invalid = [], []
for offset in range(65):
    prefix = b'p'*offset
    for suffix in [b'x'*offset, '😂é'.encode(), br'\uD83D\uDE03', br'\\', br'\"', br'\u0000']:
        valid.append(doc(b'"'+prefix+br'\u0061'+suffix+b'"'))
    for bad in [b'\x1f', br'\q', br'\u12x4', br'\uD800x', b'\xff']:
        invalid.append(doc(b'"'+prefix+br'\u0061'+prefix+bad+b'"'))
    invalid.append(b'{"value":["'+br'\u0061'+prefix)
for char in range(32):
    invalid.append(doc(br'"\u0061'+b'x'*31+bytes([char])+b'"'))
corpus = valid + invalid
reference = invoke('fused','time',corpus)
assert reference[:len(valid)] == [oracle(v) for v in valid]
assert len(reference) == len(corpus) and all(x.startswith('error ') for x in reference[len(valid):])
for mode in variants[1:]:
    assert invoke(mode,'time',corpus) == reference

result = dict(host=platform.node(), cpu=sorted(os.sched_getaffinity(0)), node_version=subprocess.check_output(
    ['node','--version'],text=True).strip(), valid=len(valid), invalid=len(invalid), cases={})
cases = [(size, position) for size in [8,129,4096] for position in ['first','middle','last','plain']]
rng.shuffle(cases)
for size,position in cases:
    wire = fixture(size,position)
    loops = min(100000,max(160,8000000//len(wire)))
    case = result['cases'][f'{size}-{position}'] = dict(bytes=len(wire),loops=loops)
    for mode in variants:
        assert invoke(mode,'time',[wire]) == [oracle(wire)]
        _,calls,allocated,checksum = invoke(mode,'counts',[wire],1)[0].split()
        assert int(checksum)==16
        case[mode]=dict(allocations=int(calls),allocated_bytes=int(allocated),ns=[])
    case['node']=dict(ns=[])
    for _ in range(11):
        order = [*variants,'node']
        rng.shuffle(order)
        for mode in order:
            if mode == 'node':
                ns = subprocess.check_output(['node',node,'16',str(size),str(loops)],input=wire).strip()
            else:
                ns,_,_,checksum=invoke(mode,'time',[wire],loops)[0].split()
                assert int(checksum)==16*loops
            case[mode]['ns'].append(float(ns))
    for mode in [*variants,'node']:
        median=statistics.median(case[mode]['ns'])
        case[mode]['median_ns']=median
        case[mode]['MB_s']=1000*len(wire)/median
print(json.dumps(result,indent=2))
