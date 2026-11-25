"""Pinned matched whole-file Rust and C++ tokenizer runs."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import random
from measure import HERE, run, verify
from source_classification import FILES, FLAVORS


def build(corpus, work, rustc, f):
    vocab=json.loads((corpus/f/'vocabulary.json').read_text())
    folder=work/f
    folder.mkdir(parents=True,exist_ok=True)
    groups={}
    for word,k in vocab['punct']:
        if word[0]!='=': groups.setdefault(word[0],[]).append((word,k))
    lines=['const PHP: bool = '+str(f=='php').lower()+';',
           'fn keyword(w:&[u8])->bool { matches!(w,'+' | '.join('b'+json.dumps(w) for w in vocab['keywords'])+') }',
           'fn punctuation(input:&[u8])->Option<(i64,usize)> { match input[0] {']
    for first,items in groups.items():
        lines.append(str(ord(first))+'=> {')
        for word,k in sorted(items,key=lambda item:-len(item[0])):
            lines.append(f'if input.starts_with(b{json.dumps(word)}) {{return Some(({k},{len(word)}));}}')
        lines.append('None },')
    lines.append('_ => None, } }')
    (folder/'vocabulary.rs').write_text('\n'.join(lines))
    os.environ['VOCAB_DIR']=str(folder)
    opts=[rustc,'--edition=2021','-C','opt-level=2','-C','lto=off','-C','codegen-units=1']
    lib=folder/'librust_scanner.rlib'
    run([*opts,'--crate-type=rlib',HERE/'byte_access/rust_scanner.rs','-o',lib])
    exe=folder/'clean'
    run([*opts,HERE/'byte_access/rust_driver.rs','--extern','rust_scanner='+str(lib),'-o',exe])
    return exe


def measure(root,corpus,native,work,rustc,cpu):
    exes={}
    hashes={}
    files={}
    for f in FLAVORS:
        pool={'rust':build(corpus,work,rustc,f)}
        for cc in ('clang++','g++'):
            for mode in ('table','handwritten'): pool[cc+' '+mode]=native/cc/f/mode/'clean'
        for mode,exe in pool.items():
            verify(exe,root/f)
            hashes[f+' '+mode]=hashlib.sha256(exe.read_bytes()).hexdigest()
            for role in FILES:
                path=corpus/f/role
                assert run([exe,'dump',path,path])==(corpus/f/(role+'.oracle')).read_bytes()
                files[f+'/'+role]=hashlib.sha256(path.read_bytes()).hexdigest()
        exes[f]=pool
    os.sched_setaffinity(0,{cpu})
    rng=random.Random(914)
    rows=[]
    for f,pool in exes.items():
        for role in FILES:
            path=corpus/f/role
            order=list(pool)
            for mode in order: run([pool[mode],'time',path,path])
            for block in range(6):
                rng.shuffle(order)
                for shift in range(5):
                    for mode in order[shift:]+order[:shift]:
                        samples=list(map(float,run([pool[mode],'time',path,path]).split()))
                        assert len(samples)==9 and min(samples)>0
                        rows.append(dict(flavor=f,file=role,round=block*5+shift,mode=mode,seconds=samples))
    return dict(cpu=cpu,rust=run([rustc,'-vV']).decode(),binaries=hashes,files=files,rows=rows)


if __name__=='__main__':
    p=argparse.ArgumentParser(description=__doc__)
    for name in ('fixtures','corpus','native','work','rustc'): p.add_argument(name,type=Path)
    p.add_argument('--cpu',type=int,required=True)
    a=p.parse_args()
    print(json.dumps(measure(*(v.resolve() for v in (a.fixtures,a.corpus,a.native,a.work,a.rustc)),a.cpu),indent=2))
