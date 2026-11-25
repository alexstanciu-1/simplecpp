"""Summarize the matched pass and run/core telemetry CSVs from rotation.py."""
import csv
import gzip
import sys
from collections import Counter, defaultdict
from statistics import median, mean
from scipy.stats import spearmanr

def rows(path):
    with gzip.open(path, "rt") as file:
        yield from csv.DictReader(file)

def key(r):
    return r["compiler"], int(r["repeat"]), int(r["slice_shift"]), int(r["cpu_shift"])

runs = defaultdict(dict)
for r in rows(sys.argv[2]):
    c = int(r["core"])
    assert c == (int(r["worker"]) + int(r["cpu_shift"])) % 8
    assert int(r["ref"])>0 and int(r["sibling_total"])>0
    runs[key(r)][c] = dict(
        mhz=float(r["ref_mhz"])*int(r["delivered"])/int(r["ref"]),
        sibling=100*int(r["sibling_busy"])/int(r["sibling_total"]),
        off=100*(1-int(r["cpu_ns"])/int(r["wall_ns"])), switches=int(r["invol"]),
        won=0, won_switched=0, sampled_switch=0, durations=[], oncpu=[], starts=[])

assert len(runs)==384 and all(len(v)==8 for v in runs.values())
counts=Counter()
for r in rows(sys.argv[1]):
    k=key(r)
    counts[k]+=1
    assert int(r["pass"])==counts[k]*32 and r['checksum']=="18222223107798614418"
    end=[int(r[f"finish{w}_ns"]) for w in range(8)]
    ties=end.count(max(end))
    for w,f in enumerate(end):
        d=runs[k][(w+k[3])%8]
        start=int(r[f"start{w}_ns"])
        switched=int(r[f"invol{w}"])+int(r[f"vol{w}"])>0
        d['durations'].append((f-start)/1000)
        d['oncpu'].append(int(r[f"cpu{w}_ns"])/1000)
        d['starts'].append(start/1000)
        d['sampled_switch']+=switched
        if f==max(end):
            d['won']+=1/ties
            d['won_switched']+=switched/ties
assert len(counts)==384 and set(counts.values())=={181}
for group in runs.values():
    for d in group.values():
        for k in ('durations','oncpu','starts'):
            d[k]=median(d[k])
        d['last']=100*d['won']/181
for cc in ('clang++','g++'):
    cases=[(k,v) for k,v in runs.items() if k[0]==cc]
    print(cc, 'per-core: last%, median MHz, sibling busy%, off-CPU%, kernel us, CPU us (incl probe), involuntary switches/run median, fraction of wins with a switch %')
    for c in range(8):
        a=[v[c] for k,v in cases]
        print(c, *(round(x,3) for x in [mean(d['last'] for d in a),*[median(d[m] for d in a) for m in ('mhz','sibling','off','durations','oncpu','switches')],100*sum(d['won_switched'] for d in a)/sum(d['won'] for d in a)]))
    for b in range(3):
        a=[v[3] for k,v in cases if k[1]==b]
        print('core 3 block',b+1,*(round(x,2) for x in [mean(d['last'] for d in a),*[median(d[m] for d in a) for m in ('mhz','sibling','off','durations')]]))
    print('core 3 range and run-level correlation with last share (absolute / relative to median of other cores)')
    for m in ('mhz','sibling','off','durations','oncpu','starts'):
        x=[v[3][m] for _,v in cases]
        rel=[v[3][m]-median(v[c][m] for c in range(8) if c!=3) for _,v in cases]
        last=[v[3]['last'] for _,v in cases]
        print(m, round(min(x),3),round(max(x),3),*[round(float(spearmanr(z,last).statistic),2) if len(set(z))>1 else None for z in [x,rel]])
    for label, test in [('zero ticks', lambda x: x==0), ('some, below 50%', lambda x: 0<x<50), ('at least 50%', lambda x: x>=50)]:
        group=[v[3] for _,v in cases if test(v[3]['sibling'])]
        if group:
            print('CPU19',label,'runs',len(group),'core 3 last %',round(mean(d['last'] for d in group),1),
                  'median kernel us',round(median(d['durations'] for d in group),2),
                  'median MHz',round(median(d['mhz'] for d in group)))
