"""Report fixed minus adaptive end-to-end time from matched trials."""
import csv
import math
from pathlib import Path
from statistics import median
import sys

rows = [r for path in sys.argv[1:] for r in csv.DictReader(Path(path).open())]
pairs = {}
for r in rows:
    key = r["compiler"], int(r["after_onset"]), int(r["block"]), int(r["slice"])
    pair = pairs.setdefault(key, {})
    assert r["mode"] not in pair
    pair[r["mode"]] = r

def ms(row):
    return 1000*float(row["wall_s"])

print("| Build | Passes after onset | Fixed post-onset ms | Fixed total ms | Adaptive total ms | Paired saving ms [95% interval] | Wins | Moves |")
print("|---|---:|---:|---:|---:|---:|---:|---:|")
for cc in ["clang++","g++"]:
    for horizon in sorted({k[1] for k in pairs}):
        group = [v for k,v in pairs.items() if k[:2]==(cc,horizon)]
        assert len(group)==(16 if horizon in (12288,65536) else 32)
        f, a = zip(*[(p["fixed"],p["adaptive"]) for p in group])
        assert all(x['onset']==y['onset'] and x['checksum']==y['checksum'] for x,y in zip(f,a))
        diff = sorted(ms(x)-ms(y) for x,y in zip(f,a))
        n = len(diff)
        # Two-sided distribution-free interval for the median of paired differences.
        tail = 0
        for i in range(n//2):
            if sum(math.comb(n,j) for j in range(i+1))/2**n > .025:
                break
            tail=i+1
        duration = median(ms(r)-float(r["load_start_us"])/1000 for r in f)
        print(f"| {cc} | {horizon:,} | {duration:.1f} | {median(map(ms,f)):.1f} | "
              f"{median(map(ms,a)):.1f} | {median(diff):+.2f} [{diff[tail]:+.2f}, {diff[-tail-1]:+.2f}] | "
              f"{sum(d>0 for d in diff)}/{n} | {sum(int(r['target'])>=0 for r in a)}/{n} |")
