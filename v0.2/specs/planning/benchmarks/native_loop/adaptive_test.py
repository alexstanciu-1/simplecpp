"""Counterbalanced matched fixed/adaptive trials with and without delayed load."""
import csv
import itertools
import os
from pathlib import Path
import random
import subprocess
import sys
import tempfile

root=Path(__file__).resolve().parent
team=list(range(8))
for c in range(16):
    path=Path(f"/sys/devices/system/cpu/cpu{c}/topology/thread_siblings_list")
    assert set(map(int,path.read_text().split(",")))=={c,c+16}
assert set(range(32)) <= os.sched_getaffinity(0)
rng=random.Random(19035)
modes=['fixed_quiet','fixed_load','adapt_quiet','adapt_load']
orders=[[modes[(i+j)%4] for j in (0,1,3,2)] for i in range(4)]
cells=list(itertools.product(('clang++','g++'),range(8)))
schedule={cell:rng.sample(orders,4)+rng.sample(orders,4) for cell in cells}
env={k:v for k,v in os.environ.items() if not k.startswith('LOOP_')}
out=csv.writer(sys.stdout)
out.writerow('compiler block slice position mode onset wall_s process_cpu_s checksum load_cpu_s load_result polls move_pass target poll_us load_start_us decision_us migrated_us pin_us observed_busy'.split())
with tempfile.TemporaryDirectory() as tmp:
    obj=Path(tmp)/'load.o'
    flags=['-O3','-std=c++20','-pthread']
    subprocess.run(['g++',*flags,'-c',root/'smt_load.cpp','-o',obj],check=True)
    for cc in ('clang++','g++'):
        subprocess.run([cc,*flags,'-DSMT_LOAD','-DROTATION_TRACE','-DADAPT_PLACEMENT',root/'dispatch.cpp',obj,'-o',Path(tmp)/cc],check=True)
    for block in range(8):
        rng.shuffle(cells)
        for cc,shift in cells:
            onset=rng.randint(1934,2900)
            for pos,mode in enumerate(schedule[cc,shift][block]):
                e=dict(env,LOOP_ACTIVE='1',LOOP_SLICE_SHIFT=str(shift),LOOP_ONSET=str(onset))
                if mode.startswith('adapt'): e['LOOP_ADAPT']='1'
                if mode.endswith('load'): e['LOOP_LOAD_CPU']='19'
                data=subprocess.check_output([Path(tmp)/cc,'5801',*map(str,team)],env=e,text=True).splitlines()
                result=data[0].split(',')
                assert result[2]=='18222223107798614418' and len(data)==182
                out.writerow([cc,block,shift,pos,mode,onset,*result])
