"""Compare prevalidation and full typed decoding on identical wire bytes."""
import argparse
import json
import os
from pathlib import Path
import platform
import random
from statistics import median
import subprocess

parser = argparse.ArgumentParser()
parser.add_argument('bin', type=Path)
parser.add_argument('--seed', type=int, default=8407)
parser.add_argument('--leading', action='store_true', help='include the first-byte-gated candidate')
parser.add_argument('--dense', nargs='+', choices=('retry64', 'remainder', 'width', 'separate'),
                    help='compare scalar fallback policies with the committed decoder and eight-byte check')
args = parser.parse_args()
modes = ('base', 'ascii8', 'leading') if args.leading else ('base', 'ascii8')

if args.dense:
    modes = ('base', 'ascii8', *args.dense)

def wire(value, escaped=False):
    return json.dumps({'value': value}, ensure_ascii=escaped, separators=(',', ':')).encode()

def invoke(mode, shape, docs, loops=None):
    command = [args.bin / mode / 'driver', shape]
    if loops is not None:
        command.append(str(loops))
    return subprocess.check_output(command, input=''.join(d.hex()+'\n' for d in docs),
                                   text=True).splitlines()

def oracle(doc):
    value = json.loads(doc)['value']
    items = value if isinstance(value, list) else [value]
    return 'ok'+''.join(' '+(v.encode().hex() if isinstance(v,str) else str(v)) for v in items)

def check(shape):
    opening, closing = (b'{"value":"', b'"}') if shape == 'scalar' else (b'{"value":["', b'"]}')
    valid, invalid = [], []
    for offset in range(32):
        prefix = opening + b'a'*offset
        for char in ('¢','水','😀','\u007f','\u0080','\u07ff','\u0800','\U0010ffff'):
            valid.append(prefix + char.encode() + b'xyz' + closing)
        valid.append(prefix + br'\uD83D\uDE00' + closing)
        for bad in (b'\x80', b'\xc0\xaf', b'\xe0\x80\x80', b'\xed\xa0\x80', b'\xf4\x90\x80\x80',
                    b'\xf5\x80\x80\x80', b'\xc2x', b'\xe2\x82x', b'\xf0\x9f\x98x', b'\xff'):
            invalid.append((prefix + bad + closing, len(prefix)))
        for cut in (b'\xc2', b'\xe2', b'\xe2\x82', b'\xf0\x9f\x98'):
            invalid.append((prefix + cut, len(prefix)))
        # Earlier syntax failure must still yield the later prevalidation failure.
        broken = b'!'+b' '*offset
        invalid.append((broken + b'\xff', len(broken)))
        invalid.append((prefix + b'\x00' + closing, None))
        invalid.append((prefix + br'\q' + closing, None))
    if args.dense:
        for pad in range(72):
            head = opening + 'é'.encode() + b'a'*pad
            for char in ('¢', '水', '😀'):
                valid.append(head + char.encode() + b'b'*80 + closing)
            for bad in (b'\xff', b'\xc2', b'\xe2\x82', b'\xf0\x9f\x98', b'\xed\xa0\x80'):
                invalid.append((head + bad, len(head)))
            broken = b'!' + head
            invalid.append((broken + b'\xff', len(broken)))
    docs = valid + [d for d,_ in invalid]
    output = invoke('base',shape,docs)
    assert len(output)==len(docs)
    for mode in modes[1:]:
        assert invoke(mode,shape,docs)==output
    assert output[:len(valid)]==[oracle(d) for d in valid]
    for result, (_, offset) in zip(output[len(valid):],invalid):
        assert result.startswith('error ')
        msg = bytes.fromhex(result[6:]).decode()
        if offset is not None:
            assert 'invalid UTF-8' in msg and f'byte {offset} ' in msg, msg
    return {'valid':len(valid),'invalid':len(invalid)}

# Most long string fixtures have exactly 4096 decoded UTF-8 bytes.
fixtures = [
    ('empty','scalar',''), ('short-ascii','scalar','abcdefg'),
    ('short-unicode','scalar','é水😀'), ('ascii-4096','scalar','a'*4096),
    ('sparse-4096','scalar',('a'*62+'é')*64),
    ('dense-2byte','scalar','é'*2048), ('dense-3byte','scalar','水'*1365),
    ('dense-4byte','scalar','😀'*1024), ('escaped-unicode','scalar','😀'*1024),
    ('words-short','words',['abcdefg']*16), ('words-long','words',['a'*4096]*16),
    ('numbers-3','numbers',list(range(3))), ('numbers-4096','numbers',list(range(4096))),
]
if args.dense:
    fixtures.extend([
        ('unicode-then-ascii','scalar','é'+'a'*4094),
        ('ascii-then-unicode','scalar','a'*2048+'😀'*512),
        ('alternating','scalar','a水'*1024),
        ('mixed-width-dense','scalar','é水😀'*455),
    ])
rng=random.Random(args.seed)
rng.shuffle(fixtures)
result={'host':platform.node(),'cpu':sorted(os.sched_getaffinity(0)), 'seed':args.seed,
        'diagnostics':{s:check(s) for s in ('scalar','words')},'cases':{}}
for name,shape,value in fixtures:
    doc=wire(value,escaped=name=='escaped-unicode')
    for m in modes:
        assert invoke(m,shape,[doc])==[oracle(doc)]
    loops=min(150000,max(256,24000000//len(doc)))
    case=result['cases'][name]={'wire_bytes':len(doc),'loops':loops}
    for phase in ('validate',shape):
        measurements={m:[] for m in modes}
        for _ in range(11):
            order=list(modes)
            rng.shuffle(order)
            for m in order:
                ns,total=invoke(m,phase,[doc],loops)[0].split()
                expected=len(doc) if phase=='validate' else len(value.encode() if shape=='scalar' else value)
                assert int(total)==loops*expected
                measurements[m].append(float(ns))
        case['validate' if phase=='validate' else 'typed']={
            m:{'samples_ns':v,'median_ns':median(v)} for m,v in measurements.items()}
print(json.dumps(result,indent=2))
