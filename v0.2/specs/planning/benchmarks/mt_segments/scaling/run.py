"""Run balanced scaling and SMT controls using the permitted Linux CPU topology."""
import csv
import os
import platform
import subprocess
import sys

binary = os.path.abspath(sys.argv[1])
topology = subprocess.check_output(["lscpu", "-p=CPU,CORE,SOCKET,NODE"], text=True)
cores = {}
for line in topology.splitlines():
    if line.startswith("#"):
        continue
    cpu, core, socket, node = line.split(",")
    if int(cpu) in os.sched_getaffinity(0):
        cores.setdefault((socket, node, core), []).append(int(cpu))
groups = sorted(cores.values(), key=min)
spread = [group[0] for group in groups] + [cpu for group in groups for cpu in group[1:]]
cases = [("spread", n, spread[:n]) for n in (1, 2, 4, 8)]
cases.append(("single-cpu", 2, spread[:1]))
siblings = next((g for g in groups if len(g) >= 2), None)
if siblings:
    cases.append(("siblings", 2, siblings[:2]))
if len(groups) >= 4:
    cases.append(("wide", 2, spread[:4]))
if len(groups) >= 2:
    cases.append(("pinned-pool", 2, spread[:2]))
print(platform.uname(), "allowed core groups", groups, file=sys.stderr)
with open("/proc/cpuinfo") as info:
    print(next(line.strip() for line in info if line.startswith("model name")), file=sys.stderr)
writer = csv.writer(sys.stdout)
writer.writerow("placement cpus run workers round mode profile us pre span tail cpu spread".split())
for run, order in enumerate([cases, list(reversed(cases))], start=1):
    for placement, workers, cpus in order:
        mask = ",".join(map(str, cpus))
        env = os.environ.copy()
        env.pop("MT_BENCH_PIN_POOL", None)
        if placement == "pinned-pool":
            env["MT_BENCH_PIN_POOL"] = "1"
        out = subprocess.check_output(["taskset", "-c", mask, binary, str(workers)], text=True, env=env)
        for row in csv.reader(out.splitlines()):
            writer.writerow([placement, mask, run, *row])
