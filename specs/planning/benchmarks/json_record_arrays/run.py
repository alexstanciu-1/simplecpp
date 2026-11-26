"""Differential correctness checks followed by shuffled dev002 measurements."""
import argparse
import itertools
import json
import os
from pathlib import Path
import platform
import random
import statistics
import subprocess

parser = argparse.ArgumentParser()
parser.add_argument('work', type=Path)
parser.add_argument('--check', action='store_true')
opts = parser.parse_args()
modes = ['baseline', 'field', 'mask', 'row', 'ordered']
rng = random.Random(12473)

def execute(mode, tool, documents, *args):
    return subprocess.run([opts.work.resolve()/mode/tool, *map(str, args)], input='\n'.join(documents)+'\n',
                          check=True, capture_output=True, text=True).stdout.splitlines()

def encode(rows):
    return json.dumps({'rows': rows}, separators=(',', ':'))

def document(n, shape):
    rows = []
    for i in range(n):
        fields = [('id', i), ('x', i % 23), ('ok', bool(i % 2)), ('tag', i % 7)]
        if shape == 'two':
            fields = fields[:2]
        if shape in ('shuffled', 'escaped'):
            rng.shuffle(fields)
        rows.append(dict(fields))
    text = encode(rows)
    return text.replace('"id"', '"\\u0069d"') if shape == 'escaped' else text

valid = [encode([]), '{}']
fields = [('id', -4), ('x', 17), ('ok', True), ('tag', 15)]
for n in range(1, 5):
    valid.extend(encode([dict(v)]) for v in itertools.permutations(fields, n) if ('id', -4) in v)
valid += [document(64, shape) for shape in ['ordered', 'shuffled', 'escaped', 'two']]
valid += [r'{"rows":[{"\u0069d":9223372036854775807},{"id":-9223372036854775808}]}']
def oracle(doc):
    return 'ok' + ''.join(' %d,%d,%d,%d' % (r['id'], r.get('x',3), r.get('ok', False), r.get('tag',5))
                          for r in json.loads(doc).get('rows', []))
invalid = ['[]', '{"rows":[],"rows":[]}', '{"rows":{}}', '{"rows":[]} true']
bad_rows = ['{}', 'null', '[]', '{"id":false}', '{"unexpected":0}', '{"unknown" 0}',
            '{"id":2,"id" 3}', r'{"id":2,"\u0069d":3}', '{"id":3,"ok":null}',
            '{"id":1,}', '{"id":9223372036854775808}', r'{"\uD800":1}']
for i, (key, value) in enumerate(fields):
    rest = fields[:i]+fields[i+1:]
    for pairs in [[], rest[:1], rest]:
        members = [*pairs, (key, value), (key, value)]
        bad_rows.append('{'+','.join(json.dumps(k)+':'+json.dumps(v) for k,v in members)+'}')
for prefix in [[], [{'id':1}], [{'id':j} for j in range(64)]]:
    for row in bad_rows:
        invalid.append('{"rows":[' + ','.join([*(json.dumps(r) for r in prefix),row]) + ']}')
corpus = valid + invalid
reference = execute('baseline', 'time', corpus)
assert reference[:len(valid)] == list(map(oracle, valid))
assert len(reference) == len(corpus) and all('byte ' in r for r in reference[len(valid):])
for mode in modes[1:]:
    lines = execute(mode, 'time', corpus)
    assert lines == reference, [(mode, doc, a, b) for doc,a,b in zip(corpus,reference,lines) if a!=b][:2]
if opts.check:
    print(f'{len(valid)} valid and {len(invalid)} invalid documents matched')
    raise SystemExit
result = dict(host=platform.node(), cpu=sorted(os.sched_getaffinity(0)), valid=len(valid), invalid=len(invalid), cases={})
for n, shape in [(0,'ordered'), (1,'ordered'), (64,'two'), (64,'ordered'), (64,'shuffled'), (64,'escaped'), (1024,'shuffled')]:
    doc = document(n, shape)
    assert execute('baseline','time',[doc]) == [oracle(doc)]
    loops = 60000 // max(1,n)
    entries = {}
    result['cases'][f'{n}_{shape}'] = dict(input_bytes=len(doc), loops=loops, entries=entries)
    checksums = set()
    for mode in modes:
        assert execute(mode,'time',[doc]) == [oracle(doc)]
        _, calls, size, checksum = execute(mode,'counts',[doc],1)[0].split()
        entries[mode] = dict(allocs=int(calls), requested=int(size), ns=[])
        checksums.add(int(checksum))
        execute(mode,'time',[doc],max(1,loops//10))
    assert len(checksums) == 1
    for _ in range(9):
        order = modes[:]
        rng.shuffle(order)
        for mode in order:
            elapsed, _, _, checksum = execute(mode,'time',[doc],loops)[0].split()
            assert int(checksum) == next(iter(checksums))*loops
            entries[mode]['ns'].append(float(elapsed))
    for entry in entries.values():
        entry['median_ns'] = statistics.median(entry['ns'])
print(json.dumps(result, indent=2))
