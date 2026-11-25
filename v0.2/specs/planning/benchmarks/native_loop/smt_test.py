"""Fixed eight-core team; compare three load placements in counterbalanced triplets."""
import csv
import itertools
import os
from pathlib import Path
import random
import statistics
import subprocess
import sys
import tempfile
import time

root = Path(__file__).resolve().parent
team = list(range(8))
def cache_group(cpu):
    return (Path(f"/sys/devices/system/cpu/cpu{cpu}/cache/index3/shared_cpu_list").read_text().strip())
def siblings(cpu):
    return set(map(int, Path(f"/sys/devices/system/cpu/cpu{cpu}/topology/thread_siblings_list").read_text().split(",")))
assert siblings(3) == {3,19} and siblings(8).isdisjoint(team)
assert cache_group(8) != cache_group(3)
assert set([*team,8,19]) <= os.sched_getaffinity(0)
def cpu_ticks():
    ticks = {}
    for line in Path("/proc/stat").read_text().splitlines():
        name, *values = line.split()
        if name.startswith("cpu") and name[3:].isdigit():
            fields = list(map(int, values[:8]))
            ticks[int(name[3:])] = (sum(fields)-fields[3]-fields[4], sum(fields))
    return ticks

def utilization(before, after, cpu):
    busy, total = (y-x for x,y in zip(before[cpu], after[cpu]))
    return busy/total if total else 0.0

relocate = "--relocate" in sys.argv
if relocate:
    before = cpu_ticks()
    time.sleep(2)
    after = cpu_ticks()
    spare = min(range(8,16),key=lambda c: (max(utilization(before,after,k) for k in siblings(c)), c))
    sibling, = siblings(spare) - {spare}
    assert set([spare,sibling]) <= os.sched_getaffinity(0) and cache_group(spare)!=cache_group(3)
    print("Selected CPU and sibling, prescreen busy fractions:",spare,sibling,
          utilization(before,after,spare),utilization(before,after,sibling),file=sys.stderr)
rng = random.Random(1938)
orders = list(itertools.permutations(["none","cpu19","cpu8"]))
cells = list(itertools.product(["clang++","g++"],range(8)))
schedule = {cell:rng.sample(orders,len(orders)) for cell in cells}
if relocate:
    # Balanced four-condition sequences: each condition follows each other once per cycle.
    modes = ["home_none","home_cpu19","spare_none","spare_cpu19"]
    orders = [tuple(modes[(j+i)%4] for j in (0,1,3,2)) for i in range(4)]
    schedule = {cell:rng.sample(orders,4)+rng.sample(orders,4) for cell in cells}
env = {k:v for k,v in os.environ.items() if not k.startswith("LOOP_")}
writer = csv.writer(sys.stdout)
if relocate:
    writer.writerow("compiler block slice_shift position condition wall_s load_cpu_s samples worker3_wins clear_wins worker3_kernel_us worker3_start_us checksum spare sibling spare_busy sibling_busy".split())
else:
    writer.writerow("compiler block slice_shift position load wall_s load_cpu_s samples core3_wins clear_wins core3_kernel_us core3_start_us checksum".split())
with tempfile.TemporaryDirectory() as tmp:
    obj = Path(tmp)/"load.o"
    flags = ["-std=c++20","-O3","-pthread"]
    subprocess.run(["g++",*flags,"-c",root/"smt_load.cpp","-o",obj],check=True)
    for cc in ("clang++","g++"):
        subprocess.run([cc,*flags,"-DROTATION_TRACE","-DSMT_LOAD",root/"dispatch.cpp",obj,"-o",Path(tmp)/cc],check=True)
    for block in range(8 if relocate else 6):
        rng.shuffle(cells)
        for cc,shift in cells:
            for pos,mode in enumerate(schedule[cc,shift][block]):
                settings=dict(env, LOOP_ACTIVE="1", LOOP_SLICE_SHIFT=str(shift))
                placement = team.copy()
                if relocate:
                    if mode.startswith("spare"): placement[3] = spare
                    if mode.endswith("cpu19"): settings["LOOP_LOAD_CPU"] = "19"
                    before = cpu_ticks()
                elif mode!="none":
                    settings["LOOP_LOAD_CPU"] = mode[3:]
                lines=subprocess.check_output([Path(tmp)/cc,"5801",*map(str,placement)],env=settings,text=True).splitlines()
                extra = []
                if relocate:
                    after = cpu_ticks()
                    extra = [spare,sibling,utilization(before,after,spare),utilization(before,after,sibling)]
                wall,_,checksum,load_cpu,_=lines[0].split(",")
                assert checksum=="18222223107798614418" and len(lines)==182
                wins=clear=0
                starts,durations=[],[]
                for j,line in enumerate(lines[1:],1):
                    step,*pairs=map(int,line.split(","))
                    assert step==j*32 and len(pairs)==16
                    finish=pairs[1::2]
                    starts.append(pairs[6])
                    durations.append(pairs[7]-pairs[6])
                    if finish[3]==max(finish):
                        wins+=1/finish.count(finish[3])
                        clear+=finish[3]-sorted(finish)[-2]>=1000
                writer.writerow([cc,block,shift,pos,mode,wall,load_cpu,len(starts),wins,clear,
                                 statistics.median(durations)/1000,statistics.median(starts)/1000,checksum,*extra])
