"""Compile bench.cpp first, then run on dev002 with CPU affinity set by the caller."""
import json
import os
from pathlib import Path
import platform
import random
import statistics
import subprocess
import sys

bins = Path(sys.argv[1]).resolve()
here = Path(__file__).resolve().parent
programs = {'PHP': ['php8.5', here/'bench.php'], 'Node.js': ['node', here/'bench.js'],
            'Python': ['python3', here/'bench.py'], 'nlohmann/json': [here/'nlohmann']}
variants = ['baseline', 'grouped', 'fused']
rng = random.Random(18139)
def output(cmd, data):
    return subprocess.check_output(cmd, input=data, text=True).rstrip('\n')

result = dict(host=platform.node(), cpu=sorted(os.sched_getaffinity(0)), versions={}, cases={})
for name,cmd in [('PHP',['php8.5','-v']),('Node.js',['node','-v']),('Python',['python3','--version']),
                 ('clang',['clang++','--version'])]:
    result['versions'][name] = output(cmd,'').splitlines()[0]

shapes = [(0,8,False)] + [(n,s,e) for n,s in [(1,8),(3,8),(16,8),(16,129),(16,4096),(128,8),(128,129)]
                           for e in (False,True)]
rng.shuffle(shapes)
for count,length,escaped in shapes:
    half = length//2
    value = 'a'*half + 'b' + 'z'*(length-half-1)
    token = '"'+'a'*half + (r'\u0062' if escaped else 'b') + 'z'*(length-half-1)+'"'
    wire = '{"value":['+','.join([token]*count)+']}'
    hexwire = wire.encode().hex()+'\n'
    oracle = 'ok'+(' '+value.encode().hex())*count
    for variant in variants:
        assert output([bins/variant/'time','vector'],hexwire) == oracle
    loops = min(100000,max(160,8000000//len(wire)))
    labels = [*variants,*programs]
    case = result['cases'][f"{count}x{length}-{'escaped' if escaped else 'plain'}"] = {
        'bytes':len(wire),'loops':loops,'ns':{l:[] for l in labels}}
    for _ in range(7):
        rng.shuffle(labels)
        for label in labels:
            if label in variants:
                ns,_,_,checksum = output([bins/label/'time','vector',str(loops)],hexwire).split()
                assert int(checksum) == loops*count
            else:
                ns = output([*programs[label],str(count),str(length),str(loops)],wire)
            case['ns'][label].append(float(ns))
    case['MB_s'] = {label:1000*len(wire)/statistics.median(times) for label,times in case['ns'].items()}
print(json.dumps(result,indent=2))
