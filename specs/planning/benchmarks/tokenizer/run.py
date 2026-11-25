"""Build and measure pre-emitted scanner fixtures. See report.md for reproduction."""
import argparse
import json
import platform
import os
import subprocess
import time
from pathlib import Path

HERE = Path(__file__).resolve().parent

def call(args):
    return subprocess.check_output(list(map(str,args)))

def timed(args):
    start=time.perf_counter()
    call(args)
    return (time.perf_counter()-start)*1000

def measure(root, runtime):
    rows=[]
    root=root.resolve()
    inc=['-I'+str(runtime.resolve()/p) for p in ('generated/public','public','generated/private','src/numeric','src/sequence','src/hash')]
    phases={}
    for flavor in ('cpp','php','js'):
        phases[flavor]=[json.loads(call([os.getenv('PHP','php'), HERE/'phases.php',
                         runtime.parent/'compiler',root/flavor,flavor])) for _ in range(5)]
    cc = 'clang++'
    flags=[cc,'-std=c++23','-O2','-g0',*inc]
    rt=root/(cc+'.o')
    call([*flags,'-c',runtime/'generated/src/runtime.cpp','-o',rt])
    for flavor in ('cpp','php','js'):
        corpus=root/flavor
        out=root/(flavor+'-out')
        units=out/'units'
        manifest=json.loads((out/'manifest.json').read_text())
        generated=out/manifest['sources'][f'{flavor}/scanner.{flavor}']
        header=next(p for p in units.glob('*.hpp') if 'Scan scan_(' in p.read_text())
        for native in (True,False):
            label='handwritten' if native else 'generated'
            build=root/(flavor+'-'+cc+'-'+label)
            build.mkdir(exist_ok=True)
            local=[*flags,'-I'+str(build),'-I'+str(HERE),'-I'+str(units)]
            if native and flavor!='php':
                local.append('-DEXTRA_WORD="'+('let' if flavor=='js' else 'auto')+'"')
            adapter=('#include "baseline.hpp"\nusing Result=Scan;\nauto make_input(const std::string& s){return s;}\nauto run(const std::string& s,size_t hint){return scan(s,hint);}' if native else
                     f'#include "{header.name}"\n#include <string>\nusing Result=App::scpp_records::Scan;\nauto make_input(const std::string& s){{return scpp::string(s.data(),long(s.size()));}}\nauto run(const scpp::string& s,size_t hint){{return App::scan_(s,hint);}}')
            (build/'adapter.hpp').write_text(adapter)
            obj=build/'scan.o'; driver=build/'driver.o'; exe=build/'scan'
            src=HERE/'baseline.cpp' if native else generated
            call([*local,'-c',HERE/'driver.cpp','-o',driver])
            link=[cc,obj,driver,*([] if native else [rt]),'-o',exe]
            compile_ms=[]; link_ms=[]
            for _ in range(3):
                compile_ms.append(timed([*local,'-c',src,'-o',obj]))
                link_ms.append(timed(link))
            trace=build/'trace.json'
            call([*local,'-ftime-trace='+str(trace),'-c',src,'-o',build/'profile.o'])
            profile={e['name']:e['dur']/1000 for e in json.loads(trace.read_text())['traceEvents']
                     if e['name'] in ('Total Frontend','Total Backend','Total Optimizer','Total CodeGenPasses')}
            count=build/'count'
            call([*local,'-DCOUNT',HERE/'driver.cpp',HERE/'allocations.cpp',obj,*([] if native else [rt]),'-o',count])
            if not native:
                safety = build/'reserve-safety'
                call([*local, HERE.parents[3]/'compiler/tests/vector_reserve.cpp', obj, rt, '-o', safety])
                call([safety])
            for i in range(11):
                for previous in (0,3,10):
                    assert call([exe,'dump',corpus/f'input{i}',corpus/f'input{previous}'])==(corpus/f'expected{i}').read_bytes(),(flavor,cc,label,i)
            edits=range(4,8)
            assert call([exe,'edits',corpus/'input3',*[corpus/f'input{i}' for i in edits]])==b''.join((corpus/f'expected{i}').read_bytes()+b'|\n' for i in edits)
            cases={}
            for i,name in enumerate(('identifiers','operators','mixed'),8):
                path=corpus/f'input{i}'
                tokens=len((corpus/f'expected{i}').read_bytes().splitlines())
                for mode in ('cold','prior'):
                    # Prior revision differs by one trailing identifier token.
                    previous=build/'prior-input'
                    previous.write_bytes(path.read_bytes()+b' previous')
                    hint=[previous] if mode=='prior' else []
                    seconds=list(map(float,call([exe,'time',path,*hint]).split()))
                    allocations,allocated=map(int,call([count,'count',path,*hint]).split())
                    cases[name+'-'+mode]=dict(bytes=path.stat().st_size,tokens=tokens,seconds=seconds,allocations=allocations,allocated_bytes=allocated)
            rows.append(dict(cc=call([cc,'--version']).decode().splitlines()[0],flavor=flavor,implementation=label,compile_ms=compile_ms,link_ms=link_ms,profile_ms=profile,source_bytes=src.stat().st_size,cases=cases))
            print(cc,flavor,label,'PASS',flush=True)
    (root/'results.json').write_text(json.dumps(dict(host=platform.node(),php_prepare_emit_ms=phases,rows=rows),indent=2)+'\n')

if __name__=='__main__':
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('fixtures',type=Path)
    parser.add_argument('runtime',type=Path)
    args=parser.parse_args()
    measure(args.fixtures,args.runtime)
