"""Compare generated typed record decoders; run timings only on dev002."""
import argparse
import json
import os
from pathlib import Path
import platform
import random
import statistics
import subprocess

parser = argparse.ArgumentParser()
parser.add_argument('before', type=Path)
parser.add_argument('after', type=Path)
parser.add_argument('--check', action='store_true')
parser.add_argument('--transfer', type=Path, help='also measure the unconditional buffer-transfer build')
args = parser.parse_args()
builds = {'before':args.before.resolve(), 'after':args.after.resolve()}
if args.transfer:
    builds['transfer'] = args.transfer.resolve()
rng = random.Random(82648)

def run(mode, tool, shape, documents, *arguments):
    return subprocess.run([builds[mode]/tool, shape, *map(str,arguments)],
                          input=''.join(doc.hex()+'\n' for doc in documents),
                          capture_output=True, text=True, check=True).stdout.splitlines()

def document(shape, token):
    return b'{"value":'+(token if shape == 'scalar' else b'["first",'+token+b',"last"]')+b'}'

def oracle(doc):
    value = json.loads(doc)['value']
    values = value if isinstance(value,list) else [value]
    return 'ok'+''.join(' '+s.encode().hex() for s in values)

def fixture(shape, spelling, length):
    # Both spellings decode to exactly the same byte sequence.
    prefix = 'a'*(length//2)
    suffix = 'z'*(length-len(prefix)-1)
    token = ('"'+prefix+('b' if spelling=='plain' else r'\u0062')+suffix+'"').encode()
    body = token if shape=='scalar' else b'['+b','.join([token]*16)+b']'
    return b'{"value":'+body+b'}'

result = {'host':platform.node(), 'affinity':sorted(os.sched_getaffinity(0)), 'checks':{}, 'cases':{}}
for shape in ['scalar','vector']:
    valid = [document(shape,json.dumps(s,ensure_ascii=ascii_only).encode())
             for ascii_only in (True,False)
             for s in ['', 'a','a\0b','"\\\b\f\n\r\t','Grüße 世界 😃', '😃'*60, *('z'*n for n in (21,22,23,24,128,4096))]]
    valid += [document(shape,t) for t in [br'"\/"',br'"\u0000"',br'"\uD83D\uDE03"',br'"x\\y\tz"']]
    invalid_tokens = [br'"\q"',br'"\u12xb"',br'"\uDC00"',br'"\uD800X"',br'"\uD800\u1234"',
                      b'"x\x00y"', b'"x\xffy"', b'"\xed\xa0\x80"', b'1',b'false',b'null',b'{}']
    invalid = [document(shape,t) for t in invalid_tokens]
    start = b'{"value":' + (b'' if shape=='scalar' else b'["first",')
    for suffix in [b'"',b'"abc',b'"a\\',b'"\\u',b'"\\u123',br'"\uD800',br'"\uD800\u12',b'"abc" ']:
        invalid.append(start+suffix)
    for n in [22,128]:
        invalid.extend(document(shape,b'"'+b'x'*n+t) for t in [br'\q"',b'\x01"',br'\uD800"'])
    invalid += [valid[0]+b'!', valid[0]+b'\xff']
    corpus = valid+invalid
    reference = run('before','time',shape,corpus)
    assert reference[:len(valid)] == list(map(oracle,valid))
    assert len(reference)==len(corpus) and all(x.startswith('error ') for x in reference[len(valid):])
    for mode in builds.keys() - {'before'}:
        assert run(mode,'time',shape,corpus)==reference
    result['checks'][shape] = {'valid':len(valid),'invalid':len(invalid)}
if args.check:
    print(result['checks'])
    raise SystemExit

cases = [(s,t,n) for s in ['scalar','vector'] for t in ['plain','escaped'] for n in [8,129,4096]]
rng.shuffle(cases)
for shape,spelling,n in cases:
    doc = fixture(shape,spelling,n)
    loops = min(160000,max(200,8000000//len(doc)))
    case = result['cases'][f'{shape}-{spelling}-{n}'] = {'wire_bytes':len(doc),'loops':loops}
    for mode in builds:
        assert run(mode,'time',shape,[doc]) == [oracle(doc)]
        _,calls,size,checksum=run(mode,'counts',shape,[doc],1)[0].split()
        expected = n if shape=='scalar' else 16
        assert int(checksum)==expected
        case[mode]={'allocations':int(calls),'allocated_bytes':int(size),'ns':[]}
    for _ in range(9):
        order=list(builds)
        rng.shuffle(order)
        for mode in order:
            ns,_,_,checksum=run(mode,'time',shape,[doc],loops)[0].split()
            assert int(checksum)==loops*expected
            case[mode]['ns'].append(float(ns))
    for mode in builds:
        case[mode]['median_ns']=statistics.median(case[mode]['ns'])
print(json.dumps(result,indent=2))
