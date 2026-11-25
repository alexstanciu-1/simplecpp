"""Report common-overlap efficiency bounds and uninstrumented end-to-end speedup."""
import csv
from statistics import median
import sys

with open(sys.argv[1]) as f:
    rows = list(csv.DictReader(f))
assert len(rows) == 256
print("| Work | Compiler | Threads | Total ms | Speedup | Overlap M records/s | Overlap efficiency | Common fraction of kernel window |")
print("|---|---|---:|---:|---:|---:|---:|---:|")
for mode in ('compute','light'):
    for cc in ('clang++','g++'):
        for n in (1,2,4,8):
            groups = [[r for r in rows if r['compiler']==cc and r['workload']==mode and
                       int(r['workers'])==n and int(r['trace'])==tr and int(r['round'])>0] for tr in (0,1)]
            plain, trace = groups
            assert len(plain)==len(trace)==7
            ms = median(float(r['wall_s'])*1000 for r in plain)
            rates = [median(int(r['records_'+key])/float(r['overlap_s'])/1e6 for r in trace)
                     for key in ('low','high')]
            coverage = median(float(r['overlap_s'])/float(r['envelope_s']) for r in trace)
            if n==1:
                assert rates[0]==rates[1]
                serial,baseline = rates[0], ms
            print(f'| {mode} | {cc} | {n} | {ms:.3f} | {baseline/ms:.2f}× | '
                  f'{rates[0]:.3f}–{rates[1]:.3f} | {100*rates[0]/(n*serial):.1f}–'
                  f'{100*rates[1]/(n*serial):.1f}% | {100*coverage:.1f}% |')
