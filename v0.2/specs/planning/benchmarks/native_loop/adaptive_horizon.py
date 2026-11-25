"""Pair both policies at each remaining-work horizon using the saved dev002 onsets."""
import csv
import itertools
import os
from pathlib import Path
import random
import subprocess
import sys
import tempfile

root = Path(__file__).resolve().parent
sources = list(csv.DictReader((root / "dev002-adaptive.csv").open()))
onsets = {(r["compiler"], int(r["block"]), int(r["slice"])): int(r["onset"])
          for r in sources if r["mode"] == "fixed_load"}
team = list(range(8))
for c in range(16):
    siblings = Path(f"/sys/devices/system/cpu/cpu{c}/topology/thread_siblings_list")
    assert set(map(int, siblings.read_text().split(","))) == {c, c+16}
assert set(range(32)) <= os.sched_getaffinity(0)
env = {k:v for k,v in os.environ.items() if not k.startswith("LOOP_")}
extended = "--extended" in sys.argv
rng = random.Random(519022 if extended else 519021)
horizons = [12288, 65536] if extended else [1024, 2048, 4096, 8192, 16384, 32768]
out = csv.writer(sys.stdout)
out.writerow(("compiler block slice after_onset mode position onset wall_s process_cpu_s checksum load_cpu_s "
              "load_result polls move_pass target poll_us load_start_us decision_us migrated_us pin_us observed_busy").split())
with tempfile.TemporaryDirectory() as tmp:
    obj = Path(tmp) / "load.o"
    flags = ["-O3","-std=c++20","-pthread"]
    subprocess.run(["g++",*flags,"-c",root/"smt_load.cpp","-o",obj],check=True)
    compilers = ["clang++","g++"]
    for cc in compilers:
        subprocess.run([cc,*flags,"-DSMT_LOAD","-DROTATION_TRACE","-DADAPT_PLACEMENT",
                        root/"dispatch.cpp",obj,"-o",Path(tmp)/cc],check=True)
    for block in (range(4,6) if extended else range(4)):
        jobs = list(itertools.product(compilers, range(8), horizons))
        rng.shuffle(jobs)
        for cc,shift,horizon in jobs:
            onset = onsets[cc,block,shift]
            total = horizon + onset
            policies = ("fixed","adaptive") if (block+shift)%2==0 else ("adaptive","fixed")
            checksum = None
            for pos,mode in enumerate(policies):
                e = dict(env, LOOP_ACTIVE="1", LOOP_SLICE_SHIFT=str(shift), LOOP_ONSET=str(onset), LOOP_LOAD_CPU="19")
                if mode == "adaptive":
                    e["LOOP_ADAPT"] = "1"
                data = subprocess.check_output([Path(tmp)/cc,str(total),*map(str,team)],env=e,text=True).splitlines()
                result = data[0].split(",")
                if checksum is None: checksum = result[2]
                assert result[2]==checksum and len(data)==1+(total-1)//32
                out.writerow([cc,block,shift,horizon,mode,pos,onset,*result])
