"""Compare the existing byte_at body made visible at the call site to both checked controls."""
import argparse
import json
import platform
import random
import re
import subprocess
import time
from pathlib import Path

from measure import HERE, once, run, scanner, verify, view_scanner_text

MODES = ('current', 'inline', 'cached')


def overlay(runtime, work):
    src = (runtime/'generated/src/runtime.cpp').read_text()
    header = (runtime/'generated/public/scpp/string.hpp').read_text()
    private = src[src.index('struct string::storage {'):src.index('string::~string()')]
    accessor = re.search(r'uint8 string::byte_at\(int64 index\) const \{.*?\n\}', src, re.S)[0]
    # All translation units in this variant see one identical inline definition.
    public = work/'include/scpp/string.hpp'
    public.parent.mkdir(parents=True, exist_ok=True)
    public.write_text(header+'\n#include "sequence_semantics.hpp"\nnamespace scpp {\n'+private+'inline '+accessor+'\n}\n')
    rt = work/'inline-runtime.cpp'
    rt.write_text(once(once(src,private,''),accessor,''))
    return rt


def view_scanner(src, split=False):
    return view_scanner_text(src.read_text(), split)


def measure(root, runtime, work, view=False, split=False):
    work.mkdir(parents=True,exist_ok=True)
    modes = ('current', 'view', 'cached') if view else MODES
    if split:
        modes = ('current', 'view', 'split')
    if not view:
        inlined = overlay(runtime,work)
    includes = ['-I'+str(runtime/p) for p in ('generated/public','generated/private','src/numeric','src/sequence','src/hash')]
    rows=[]
    rng=random.Random(4119)
    for cc in ('clang++','g++'):
        flags=[cc,'-std=c++23','-O2','-g0']
        for mode,source in [('current',runtime/'generated/src/runtime.cpp')] + ([] if view else [('inline',inlined)]):
            extra=['-I'+str(work/'include')] if mode=='inline' else []
            run([*flags,*extra,*includes,'-c',source,'-o',work/(cc+mode+'.o')])
        if view:
            lifetime = work/(cc+'-lifetime')
            run([*flags,*includes,HERE/'byte_access/checked_view_test.cpp',work/(cc+'current.o'),'-o',lifetime])
            run([lifetime])
        for flavor in ('cpp','php','js'):
            out=root/(flavor+'-out')
            corpus=root/flavor
            src=out/json.loads((out/'manifest.json').read_text())['sources'][f'{flavor}/scanner.{flavor}']
            folder=work/(cc+flavor)
            folder.mkdir(exist_ok=True)
            (folder/'adapter.hpp').write_text(f'''#include "{src.with_suffix('.hpp').name}"
#include <string>
using Result=App::scpp_records::Scan;
auto make_input(const std::string& s) {{return scpp::string(s.data(),long(s.size()));}}
auto run(const scpp::string& s,size_t n) {{return App::scan_(s,n);}}
''')
            cached=folder/'cached.cpp'
            cached.write_text(scanner(src,'inline_checked',False))
            sources = dict(current=src, inline=src, cached=cached)
            if view:
                sources['view'] = folder/'view.cpp'
                sources['view'].write_text(view_scanner(src))
            if split:
                sources['split'] = folder/'split.cpp'
                sources['split'].write_text(view_scanner(src, split=True))
            obs={}
            for mode in modes:
                opts=[*flags, *(['-I'+str(work/'include')] if mode=='inline' else []), *includes,
                      '-I'+str(HERE/'byte_access'),'-I'+str(src.parent),'-I'+str(folder)]
                obj=folder/(mode+'.o')
                compile_ms=[]
                for _ in range(3):
                    start=time.perf_counter()
                    run([*opts,'-c',sources[mode],'-o',obj])
                    compile_ms.append((time.perf_counter()-start)*1000)
                if mode in ('view', 'split'):
                    if cc == 'clang++':
                        run([*opts,'-S','-emit-llvm',sources[mode],'-o',folder/(mode+'.ll')])
                    else:
                        run([*opts,'-fdump-tree-optimized='+str(folder/(mode+'.gimple')),'-S',sources[mode],'-o',folder/(mode+'.s')])
                dis=run(['objdump','-drC',obj]).decode()
                calls=sum('R_X86_64_' in line and 'scpp::string::byte_at(' in line for line in dis.splitlines())
                assert mode!='current' or calls>0
                exe=folder/mode
                run([*opts,HERE/'driver.cpp',obj,work/(cc+('inline' if mode=='inline' else 'current')+'.o'),'-o',exe])
                verify(exe,corpus)
                obs[mode]=dict(byte_at_relocations=calls,compile_ms=compile_ms,corpora={})
            for i,name in enumerate(('identifiers','operators','mixed'),8):
                path=corpus/f'input{i}'
                prior=folder/'prior'
                prior.write_bytes(path.read_bytes()+b' previous')
                for mode in modes:
                    obs[mode]['corpora'][name]=dict(bytes=path.stat().st_size,seconds=[])
                for _ in range(5):
                    order=list(modes)
                    rng.shuffle(order)
                    for mode in order:
                        batch=run([folder/mode,'time',path,prior]).split()
                        obs[mode]['corpora'][name]['seconds'].extend(map(float,batch))
            rows.append(dict(cc=cc,version=run([cc,'--version']).decode().splitlines()[0],flavor=flavor,modes=obs))
            print(cc,flavor,'passed',flush=True)
    (work/'results.json').write_text(json.dumps(dict(host=platform.node(),rows=rows),indent=2)+'\n')


if __name__=='__main__':
    parser=argparse.ArgumentParser(description=__doc__)
    for name in ('fixtures','runtime','work'):
        parser.add_argument(name,type=Path)
    parser.add_argument('--checked-view', action='store_true')
    parser.add_argument('--split-view', action='store_true')
    args=parser.parse_args()
    measure(args.fixtures.resolve(),args.runtime.resolve(),args.work.resolve(),args.checked_view or args.split_view,args.split_view)
