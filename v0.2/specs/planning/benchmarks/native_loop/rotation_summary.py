"""Summarize rotation.py data; split exact ties evenly between the final workers."""
import csv
import gzip
import sys
from collections import Counter, defaultdict
from statistics import median

runs = Counter()
last = Counter()
clear = Counter()
starts = defaultdict(list)
durations = defaultdict(list)
last_start = Counter()
last_duration = Counter()
for r in csv.DictReader(gzip.open(sys.argv[1], "rt")):
    cc, rep = r["compiler"], int(r["repeat"])
    sl, co = int(r["slice_shift"]), int(r["cpu_shift"])
    key = (cc, rep, sl, co)
    runs[key] += 1
    assert int(r["pass"]) == runs[key]*32 and r["checksum"]=="18222223107798614418"
    start = [int(r[f"start{w}_ns"]) for w in range(8)]
    finish = [int(r[f"finish{w}_ns"]) for w in range(8)]
    duration = [f-s for f,s in zip(finish,start)]
    assert min(start)>=0 and min(duration)>0
    latest = max(finish)
    winners = [w for w in range(8) if finish[w]==latest]
    margin = latest-sorted(finish)[-2]
    for w in range(8):
        for kind,i in (("worker",w),("core",(w+co)%8),("slice",(w+sl)%8)):
            k = (cc,kind,i)
            starts[k].append(start[w])
            durations[k].append(duration[w])
            if w in winners:
                last[(*k, rep)] += 1/len(winners)
                if margin>=1000: clear[k]+=1
        if w in winners:
            last_start[cc] += (start[w]==max(start))/len(winners)
            last_duration[cc] += (duration[w]==max(duration))/len(winners)
assert len(runs)==384 and set(runs.values())=={181}
for cc in ["clang++","g++"]:
    total=sum(n for k,n in runs.items() if k[0]==cc)
    print(cc, total,"latest starter / longest duration also last (%)",*[round(100*x[cc]/total,1) for x in (last_start,last_duration)])
    for kind in ("worker","core","slice"):
        for i in range(8):
            k=(cc,kind,i)
            by_rep=[last[(*k,rep)] for rep in range(3)]
            print(kind,i,"last %", round(100*sum(by_rep)/total,1),"by repeat",*[round(300*x/total,1) for x in by_rep],
                  "clear % of all passes",round(100*clear[k]/total,1),
                  "start/kernel us",round(median(starts[k])/1000,2),round(median(durations[k])/1000,2))
