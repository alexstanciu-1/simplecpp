"""Run on dev002. CSV has one sampled pass per row and start/finish ns per worker."""
import csv
import itertools
import os
import random
import subprocess
import sys
import tempfile
from pathlib import Path

root = Path(__file__).resolve().parent
cores = list(range(8))
topology = subprocess.check_output(["lscpu", "-p=CPU,CORE,SOCKET"], text=True)
physical = {int(cpu): (core, socket) for cpu,core,socket in csv.reader(
    line for line in topology.splitlines() if not line.startswith("#"))}
assert set(cores) <= os.sched_getaffinity(0) and len({physical[c] for c in cores}) == 8
telemetry = "--telemetry" in sys.argv
fields = ["start", "finish", "cpu", "invol", "vol"] if telemetry else ["start","finish"]
if telemetry:
    counters = csv.writer(open(sys.argv[sys.argv.index("--telemetry")+1], "w"))
    counters.writerow("compiler repeat slice_shift cpu_shift worker core ref delivered sibling_busy sibling_total wall_ns cpu_ns invol vol ref_mhz".split())
    ref_mhz = {}
    for core in cores:
        folder = Path(f"/sys/devices/system/cpu/cpu{core}/acpi_cppc")
        value = lambda key: int((folder/key).read_text())
        ref_mhz[core] = value("nominal_freq") * value("reference_perf") / value("nominal_perf")
        topo = Path(f"/sys/devices/system/cpu/cpu{core}/topology/thread_siblings_list")
        assert set(map(int,topo.read_text().split(","))) == {core,core+16}
rng = random.Random(1701)
writer = csv.writer(sys.stdout)
writer.writerow(["compiler","repeat","slice_shift","cpu_shift","wall_s","checksum","pass",
                 *[f"{name}{w}" + ("_ns" if name in ("start","finish","cpu") else "") for w in cores for name in fields]])
env = {k:v for k,v in os.environ.items() if not k.startswith("LOOP_")}
with tempfile.TemporaryDirectory() as tmp:
    compilers = ["clang++", "g++"]
    for cc in compilers:
        subprocess.run([cc,"-O3","-std=c++20","-pthread","-DROTATION_TRACE",*(["-DROTATION_TELEMETRY"] if telemetry else []),root/"dispatch.cpp",
                        "-o",Path(tmp)/cc], check=True)
    for repeat in range(3):
        cases = list(itertools.product(compilers, cores, cores))
        rng.shuffle(cases)
        for cc,sl,co in cases:
            cpus = [str((w+co)%8) for w in cores]
            out = subprocess.check_output([Path(tmp)/cc,"5801",*cpus], text=True,
                   env=dict(env, LOOP_ACTIVE="1", LOOP_SLICE_SHIFT=str(sl))).splitlines()
            wall, _, checksum = out[0].split(",")
            if telemetry:
                for line in out[1:9]:
                    worker, core, *values = line.split(",")
                    assert int(core) == (int(worker)+co)%8
                    counters.writerow([cc,repeat,sl,co,worker,core,*values,ref_mhz[int(core)]])
                del out[1:9]
            assert checksum == "18222223107798614418" and len(out)==182
            for row in csv.reader(out[1:]):
                writer.writerow([cc, repeat, sl, co, wall, checksum, *row])
