"""Pinned Clang A/Bs for the outer signed cursor check in the transfer scanner."""
import argparse
import hashlib
import json
import os
import re
from pathlib import Path
from measure import HERE, once, run, verify

MODES = ('original','nonnegative','same_layout')


def first_load(disasm):
    body = disasm.split('<App::scan_(scpp::string, long)>:',1)[1].split('\n\n',1)[0]
    match = re.search(r'^\s*([0-9a-f]+):\s+([0-9a-f ]+)\s+test\s+(%\w+),\3\n'
                      r'\s*[0-9a-f]+:\s+([0-9a-f ]+)\s+js\s+[^\n]+\n'
                      r'\s*[0-9a-f]+:\s+[0-9a-f ]+\s+movzb[lq]\s+[^\n]+',body,re.M)
    return match


def build(root,slot,rt,work,f):
    family=slot/('clang++-'+f)
    source=(family/'transfer/scanner.cpp').read_text()
    outer='while (var_i_5 < var_size_4) {\n\t\t[[maybe_unused]]'
    variants={'original':source,'nonnegative':once(source,outer,outer.replace('{\n','{\n\t\t__builtin_assume(var_i_5 >= 0);\n'))}
    opts=['clang++','-std=c++23','-O2','-g0','-I'+str(family/'slot'),'-I'+str(HERE/'byte_access'),
          '-I'+str(rt/'generated/public')]
    evidence={}
    for mode in MODES:
        d=work/f/mode
        d.mkdir(parents=True,exist_ok=True)
        obj=d/'scanner.o'
        if mode in variants:
            src=d/'scanner.cpp'
            src.write_text(variants[mode])
            run([*opts,'-c',src,'-o',obj])
        else:
            # One 9-byte x86-64 NOP in place of testq + near js. Keep all addresses.
            original=work/f/'original/scanner.o'
            m=first_load(run(['objdump','-dC',original]).decode())
            offset=int(m[1],16)
            old=bytes.fromhex(m[2]+m[4])
            nop=bytes.fromhex('66 0f 1f 84 00 00 00 00 00')
            sections=run(['objdump','-h',original]).decode()
            header=next(line.split() for line in sections.splitlines() if line.split()[1:2]==['.text'])
            position=int(header[5],16)+offset
            image=original.read_bytes()
            assert len(old)==len(nop) and image[position:position+len(old)]==old
            obj.write_bytes(image[:position]+nop+image[position+len(old):])
        disasm=run(['objdump','-dC',obj]).decode()
        guard=first_load(disasm)
        assert bool(guard)==(mode=='original')
        (d/'scanner.asm').write_text(disasm)
        exe=d/'clean'
        run([*opts,HERE/'driver.cpp',obj,slot/'clang++.o','-o',exe])
        if mode=='same_layout':
            images=[]
            for linked in (work/f/'original/clean',exe):
                section=linked.with_name('linked.text')
                run(['objcopy','-O','binary','--only-section=.text',linked,section])
                images.append(section.read_bytes())
            a,b=images
            assert len(a)==len(b)
            first=next(i for i in range(len(a)) if a[i]!=b[i])
            assert a[first:first+9]==old and b==a[:first]+nop+a[first+9:]
        verify(exe,root/f)
        evidence[mode]={'sha256':hashlib.sha256(exe.read_bytes()).hexdigest(),
                        'outer_test_js':bool(guard)}
        if mode=='original': evidence[mode]['outer_sequence']=guard[0].strip()
    return evidence


def measure(root,slot,rt,work,cpu):
    flavors=('cpp','php','js')
    evidence={f:build(root,slot,rt,work,f) for f in flavors}
    os.sched_setaffinity(0,{cpu})
    rows=[]
    # Six permutations balance time position and which variant immediately precedes another.
    from itertools import permutations
    orders=list(permutations(MODES))
    for f in flavors:
        source=root/f/'input9'
        hint=work/f/'prior'
        hint.write_bytes(source.read_bytes()+b' previous')
        for m in MODES:
            run([work/f/m/'clean','time',source,hint])
        for i in range(48):
            for m in orders[i%6]:
                samples=list(map(float,run([work/f/m/'clean','time',source,hint]).split()))
                assert len(samples)==9 and min(samples)>0
                rows.append(dict(flavor=f,round=i,mode=m,seconds=samples))
    return dict(cpu=cpu,compiler=run(['clang++','--version']).decode().splitlines()[0],
                bytes=source.stat().st_size,tokens=len((root/f/'expected9').read_bytes().splitlines()),
                evidence=evidence,rows=rows)


if __name__=='__main__':
    p=argparse.ArgumentParser(description=__doc__)
    for s in ('root','slot','runtime','work'): p.add_argument(s,type=Path)
    p.add_argument('--cpu',type=int,required=True)
    a=p.parse_args()
    print(json.dumps(measure(*(v.resolve() for v in (a.root,a.slot,a.runtime,a.work)),a.cpu),indent=2))
