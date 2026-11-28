"""Run on the build host with the staged filesystem publication and matching archives."""
import argparse
import json
import os
from pathlib import Path
import platform
import random
import shutil
import subprocess
import sys
import tempfile

from generate import BASE, HERE, DOMAINS, materialize
from verify_workload import fixture
sys.path.insert(0, str(BASE))
from build_levels import build, measured


def make_drivers(root, out, opt, mold):
    manifest = json.loads((out/'manifest.json').read_text())
    unit = out/manifest['sources']['app/main.phs']
    cmd = ['clang++','-std=c++23','-stdlib=libc++',opt,'-g0','--ld-path='+mold,
           '-I'+str(root/'runtime/generated/public'), BASE/'runtime.cpp', unit.with_suffix('.o'),
           *manifest['link_inputs']]
    for mode,extra in [('timing',[]),('memory',['-DCOUNT_ALLOCATIONS','-Wl,--wrap=_Znwm'])]:
        measured([*cmd,*extra,'-o',out/mode])
    return unit.stat().st_size


def benchmark(size, root, tmp, php, mold, cpu):
    rng = random.Random(455)
    jobs = [(name,opt) for name in DOMAINS for opt in ['-O0','-O2']]
    builds, runs = [], []
    cases, sources, drivers = {}, {}, {}
    for name in DOMAINS:
        cases[name] = {}
        source = HERE/f'{size}k'/name
        text, plans = materialize(name,size*1000)
        assert (source/'main.phs').read_text() == text
        sources[name] = dict(bytes=len(text.encode()),functions=text.count('function '),reports=len(plans))
        for mode in ['shared','full']:
            path = tmp/name/mode
            records = fixture(name,size,path,mode=='full')
            cases[name][mode] = (path,records)
    for i in range(-1,5):
        rng.shuffle(jobs)
        for name,opt in jobs:
            app = tmp/name/opt/str(i)/'app'
            out = app.parent/'out'
            app.mkdir(parents=True)
            for f in ['main.phs','module.json']:
                shutil.copyfile(HERE/f'{size}k'/name/f,app/f)
            times = build(root,app,out,php,mold,opt)
            for path in [BASE/name, *(p for p,_ in cases[name].values())]:
                process,_ = measured([out/'program'],cwd=path)
                if process.stdout != (path/'expected.txt').read_bytes():
                    raise ValueError(f'{name} {size} {opt} output mismatch for {path}')
            if i >= 0:
                builds.append(dict(name=name,level=opt,round=i,**times))
            if i == 0:
                make_drivers(root,out,opt,mold)
                drivers[name,opt] = out
    for mode,repeats,passes in [('timing',5,8),('memory',3,1)]:
        run_jobs = [(n,o,c) for n,o in jobs for c in cases[n]]
        for i in range(repeats):
            rng.shuffle(run_jobs)
            for name,opt,case in run_jobs:
                path,records = cases[name][case]
                run,_ = measured(['taskset','-c',str(cpu),drivers[name,opt]/mode,path/'input.txt',str(passes)])
                ns,rss,allocs,allocated = map(int,run.stderr.split())
                runs.append(dict(name=name,level=opt,case=case,round=i,mode=mode,passes=passes,
                                 rows=records,cpu_ns=ns,peak_rss_kib=rss,new_calls=allocs,new_bytes=allocated))
    return dict(size_kb=size,sources=sources,builds=builds,runs=runs)


def main():
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument('v02',type=Path)
    p.add_argument('output',type=Path)
    p.add_argument('--php',default='php')
    p.add_argument('--cpu',type=int,default=8)
    p.add_argument('--size',type=int,choices=[50,100],required=True)
    args = p.parse_args()
    os.environ['XDEBUG_MODE']='off'
    mold = shutil.which('ld.mold')
    if not mold:
        p.error('mold required')
    with tempfile.TemporaryDirectory() as t:
        result = benchmark(args.size,args.v02.resolve(),Path(t),args.php,mold,args.cpu)
    result.update(host=platform.node(),cpu=args.cpu,php=subprocess.check_output([args.php,'-v'],text=True).splitlines()[0],
                  clang=subprocess.check_output(['clang++','--version'],text=True).splitlines()[0],
                  mold=subprocess.check_output([mold,'--version'],text=True).splitlines()[0])
    args.output.write_text(json.dumps(result,indent=2)+'\n')


if __name__ == '__main__':
    main()
