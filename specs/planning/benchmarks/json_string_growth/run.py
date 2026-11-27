"""Run pinned on dev002. Arguments are the previous suffix study and this build directory."""
import json
import os
from pathlib import Path
import platform
import random
import string
import subprocess
import sys

old, work, *extra = (Path(p).resolve() for p in sys.argv[1:])
assert len(extra) <= 1
modes = ('base', 'reserve')
drivers = {'base': old/'bin/avx2', 'reserve': work/'bin/reserve'}
probes = {m: work/('probe-'+m)/'probe' for m in modes}
if extra:
    modes += ('selective',)
    drivers['selective'] = extra[0]/'bin/selective'
    probes['selective'] = extra[0]/'probe-selective/probe'
rng = random.Random(4096)
sizes = [8, 23, 129, 4095, 4096, 4097]


def command(args, data=''):
    return subprocess.run(args, input=data, text=True, capture_output=True, check=True).stdout.strip()


def fixture(size, plain=False):
    value = (string.ascii_lowercase * ((size + 25)//26))[:size]
    spelling = value if plain else value[:-1] + ('\\u%04x' % ord(value[-1]))
    return ('{"value":[' + ','.join(['"' + spelling + '"']*16) + ']}').encode()


def verify(docs):
    data = ''.join(d.hex()+'\n' for d in docs)
    outputs = [command([drivers[m]/'time', 'vector'], data).splitlines() for m in modes]
    assert len(outputs[0]) == len(docs) and all(out == outputs[0] for out in outputs[1:])
    for wire, answer in zip(docs, outputs[0]):
        try:
            values = json.loads(wire)['value']
            expected = 'ok' + ''.join(' '+v.encode().hex() for v in values)
        except (ValueError, UnicodeError):
            assert answer.startswith('error '), answer
        else:
            assert answer == expected


result = dict(host=platform.node(), affinity=sorted(os.sched_getaffinity(0)), cases={})
docs = []
for n in sizes:
    for suffix in [br'\u0061"', br'\u0000"', br'\uD83D\uDE03"', br'\u0061\q"', br'\u12x4"', b'\xff"', b'\x01"', b'']:
        docs.append(b'{"value":["' + b'a'*(n-1) + suffix + b']}')
if extra:
    for n in [1023, 1024, 1031, 4095]:
        prefix = b'a'*n
        for tail in [br'\u0000', br'\u00e9', br'\uD83D\uDE03', br'\\', br'\"', br'\n',
                     br'\u0061suffix\u0062', br'\u12x4', br'\uDC00', br'\uD800x',
                     br'\u0061\q', b'\x1f', b'\xff']:
            docs.append(b'{"value":["previous","'+prefix+tail+b'","next"]}')
verify(docs)
result['validation_documents'] = len(docs)
for n in sizes:
    case = result['cases'][n] = {}
    for mode in modes:
        case[mode] = {}
    wire = fixture(n)
    verify([wire, fixture(n, True)])
    loops = max(300, min(90000, 24000000//len(wire)))
    case.update(bytes=len(wire), loops=loops)
    for mode in modes:
        _, calls, allocated, checksum = command([drivers[mode]/'counts', 'vector', '1'], wire.hex()+'\n').split()
        assert int(checksum) == 16
        case[mode].update(new_calls=int(calls), new_bytes=int(allocated), append_ns=[], doc_ns=[], plain_ns=[])
    for _ in range(11):
        for metric in ['append_ns', 'doc_ns', 'plain_ns']:
            order = list(modes)
            rng.shuffle(order)
            for m in order:
                stats = case[m]
                if metric == 'append_ns':
                    data = command([probes[m], str(n), '100000']).split()
                    count, growth, moved, before, after = map(int, data[:5])
                    observed = [count, growth, moved, before, after]
                    assert stats.setdefault('observation', observed) == observed
                    ns = data[5]
                else:
                    text = wire if metric == 'doc_ns' else fixture(n, True)
                    ns, _, _, checksum = command([drivers[m]/'time', 'vector', str(loops)], text.hex()+'\n').split()
                    assert int(checksum) == 16*loops
                stats[metric].append(float(ns))
print(json.dumps(result, indent=2))
