"""Compile and run the fixed-size vector experiment on dev002."""
import csv
import itertools
import os
from pathlib import Path
import random
from statistics import median
import subprocess
import sys
import tempfile

source = Path(__file__).with_name("two_vectors.cpp")
assert set(range(8)) <= os.sched_getaffinity(0)
cores = [Path(f"/sys/devices/system/cpu/cpu{i}/topology/core_id").read_text() for i in range(8)]
assert len(set(cores)) == 8
writer = csv.writer(sys.stdout)
writer.writerow("compiler round workers workload steps trace wall_s overlap_s envelope_s records_low records_high checksum".split())
rng = random.Random(8192)
checksums = {}
with tempfile.TemporaryDirectory() as tmp:
    compilers = ["clang++","g++"]
    def run(cc,n,steps,trace):
        return subprocess.check_output([Path(tmp)/cc,str(n),str(steps),str(int(trace))], text=True).strip().split(",")
    for cc in compilers:
        subprocess.run([cc,"-std=c++20","-O3","-pthread",source,"-o",Path(tmp)/cc],check=True)
    steps = 862
    for _ in range(2):
        samples = [float(run(cc,1,steps,False)[0]) for _ in range(2) for cc in compilers]
        steps = max(1, round(steps*0.60/median(samples)))
    for repeat in range(8):
        cases = list(itertools.product(compilers,[1,2,4,8],[("light",1),("compute",steps)],[False,True]))
        rng.shuffle(cases)
        for cc,n,(mode,k),trace in cases:
            row = run(cc,n,k,trace)
            assert row[-1] == checksums.setdefault(k,row[-1])
            writer.writerow([cc,repeat,n,mode,k,int(trace),*row])
