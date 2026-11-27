"""Measure string suffix scanning on dev002 with the fused decoder as the reference."""
import argparse
import json
import os
from pathlib import Path
import platform
import random
import statistics
import string
import subprocess

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('bin', type=Path)
mode = parser.add_mutually_exclusive_group()
mode.add_argument('--nonempty', action='store_true', help='compare empty-prefix and nonempty-suffix shortcuts')
mode.add_argument('--nonempty-recheck', choices=('escape_prefix', 'suffix_quote', 'both_quotes'),
                  help='remeasure a candidate against the committed and portable controls')
mode.add_argument('--close-recheck', action='store_true', help='repeat the closing-quote comparison in a different order')
mode.add_argument('--short-escapes', action='store_true', help='compare isolated post-escape scanning changes')
mode.add_argument('--first8', action='store_true', help='compare word8, direct AVX2 dispatch and portable-first AVX2')
mode.add_argument('--dispatch', action='store_true', help='compare word8 with runtime AVX2 dispatch')
mode.add_argument('--scanner', action='store_true', help='compare bulk and integrated word8 builds')
mode.add_argument('--production', action='store_true', help='compare batched, bulk, scanner-only and combined builds')
mode.add_argument('--check-pair', nargs=2, metavar=('BASE', 'CANDIDATE'), help='check scalar and vector parity without timing')
args = parser.parse_args()
bins = args.bin.resolve()
if args.check_pair:
    variants = tuple(args.check_pair)
elif args.nonempty:
    variants = ('close_tail', 'word8', 'escape_prefix', 'suffix_quote', 'both_quotes')
elif args.nonempty_recheck:
    variants = ('close_tail', 'word8', args.nonempty_recheck)
elif args.close_recheck:
    variants = ('dispatch', 'word8', 'close_tail')
elif args.short_escapes:
    variants = ('dispatch', 'word8', 'portable_tail', 'close_tail', 'sse_tail')
elif args.first8:
    variants = ('word8', 'dispatch', 'first8')
elif args.dispatch:
    variants = ('word8', 'dispatch')
elif args.scanner:
    variants = ('bulk', 'word8')
elif args.production:
    variants = ('batched', 'bulk', 'scan_only', 'word8')
else:
    variants = ('fused', 'bulk', 'word8', 'avx2')
timed = (*variants, 'node') if 'avx2' in variants else variants
node = Path(__file__).with_name('node.js')
rng = random.Random(91357 if args.close_recheck or args.nonempty_recheck else 32768)


def invoke(mode, binary, docs, *args, shape='vector'):
    cmd = [bins/mode/binary, shape, *map(str, args)]
    return subprocess.run(cmd, input=''.join(doc.hex()+'\n' for doc in docs),
                          capture_output=True, text=True, check=True).stdout.splitlines()


def oracle(wire):
    values = json.loads(wire)['value']
    if isinstance(values, str):
        values = [values]
    return 'ok' + ''.join(' '+s.encode().hex() for s in values)


def fixture(size, position):
    value = (string.ascii_lowercase * ((size+25)//26))[:size]
    if position == 'plain':
        spelling = value
    else:
        simple = position.startswith('slash-')
        position = position.removeprefix('slash-')
        i = {'first':0, 'middle':size//2, 'last':size-1}[position]
        escape = r'\/' if simple else ('\\u%04x' % ord(value[i]))
        spelling = value[:i] + escape + value[i+1:]
    return ('{"value":[' + ','.join(['"'+spelling+'"']*16) + ']}').encode()


def verify(shape):
    opening = b'{"value":' if shape == 'scalar' else b'{"value":["previous",'
    closing = b'}' if shape == 'scalar' else b',"next"]}'

    def wrap(token):
        return opening + token + closing

    truncated = b'{"value":' if shape == 'scalar' else b'{"value":['
    valid, invalid = [], []
    for offset in range(65):
        prefix = b'p'*offset
        for suffix in [b'x'*offset, '😂é'.encode(), br'\uD83D\uDE03', br'\\', br'\"', br'\u0000']:
            valid.append(wrap(b'"'+prefix+br'\u0061'+suffix+b'"'))
        for bad in [b'\x1f', br'\q', br'\u12x4', br'\uD800x', b'\xff']:
            invalid.append(wrap(b'"'+prefix+br'\u0061'+prefix+bad+b'"'))
        invalid.append(truncated + b'"' + br'\u0061'+prefix)
    for char in range(32):
        invalid.append(wrap(br'"\u0061'+b'x'*31+bytes([char])+b'"'))
    corpus = valid + invalid
    reference = invoke(variants[0],'time',corpus,shape=shape)
    assert reference[:len(valid)] == [oracle(v) for v in valid]
    assert len(reference) == len(corpus) and all(x.startswith('error ') for x in reference[len(valid):])
    for mode in variants[1:]:
        assert invoke(mode,'time',corpus,shape=shape) == reference
    return len(valid), len(invalid)


valid, invalid = verify('vector')
result = dict(host=platform.node(), cpu=sorted(os.sched_getaffinity(0)), valid=valid, invalid=invalid, cases={})
if 'node' not in timed:
    result['scalar_valid'], result['scalar_invalid'] = verify('scalar')
else:
    result['node_version'] = subprocess.check_output(['node','--version'],text=True).strip()
if args.check_pair:
    print(json.dumps(result))
    raise SystemExit
sizes = [3, 7, 8, 9, 129, 4096] if args.scanner else [8, 129, 4096]
if args.dispatch or args.first8:
    sizes = [3, 7, 8, 9, 31, 32, 33, 129, 4096]
cases = [(size, position) for size in sizes for position in ['first','middle','last','plain']]
if args.short_escapes or args.close_recheck or args.nonempty or args.nonempty_recheck:
    positions = ['plain', 'first', 'middle', 'last', 'slash-first', 'slash-middle', 'slash-last']
    cases = [(size, position) for size in [3, 7, 8, 9, 129, 4096] for position in positions]
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
    if 'node' in timed:
        case['node']=dict(ns=[])
    for _ in range(11):
        order = list(timed)
        rng.shuffle(order)
        for mode in order:
            if mode == 'node':
                ns = subprocess.check_output(['node',node,'16',str(size),str(loops)],input=wire).strip()
            else:
                ns,_,_,checksum=invoke(mode,'time',[wire],loops)[0].split()
                assert int(checksum)==16*loops
            case[mode]['ns'].append(float(ns))
    for mode in timed:
        median=statistics.median(case[mode]['ns'])
        case[mode]['median_ns']=median
        case[mode]['MB_s']=1000*len(wire)/median
print(json.dumps(result,indent=2))
