"""Pair whole-file table builds with the handwritten scalar-classification baseline."""
import argparse
import hashlib
import json
import os
from pathlib import Path

from measure import HERE, once, run, verify
from source_classification import FILES, FLAVORS, lexical_helpers


def handwritten(vocab, flavor):
    keyword, fallback = lexical_helpers(vocab, flavor)
    fallback = fallback.replace('const tokenizer_bench::split_view& v, scpp::int64& i',
                                'std::string_view v, std::size_t& i')
    # For this baseline all helper reads already have local bounds guards.
    import re
    fallback = re.sub(r'v\.byte_at\(([^()]*)\)', r'static_cast<unsigned char>(v[\1])', fallback)
    assert 'byte_at' not in fallback
    source = (HERE/'baseline.cpp').read_text()
    source = once(source, 'static bool letter', keyword + fallback + 'static bool letter')
    first = source.index('if (word == "if"')
    last = source.index('kind = 62;',first)
    source = source[:first] + 'if (keyword(word)) ' + source[last:]
    return once(source, '} else {\n            out.error',
                '} else if (!(kind = extra(text,i,static_cast<unsigned char>(c)))) {\n            out.error')


def measure(root,slot,corpus,prior,rt,work,cpu):
    rows=[]
    hashes={}
    versions={}
    file_meta={}
    for cc in ('clang++','g++'):
        versions[cc]=run([cc,'--version']).decode().splitlines()[0]
        for f in FLAVORS:
            for mode in ('table','handwritten'):
                dest=work/cc/f/mode
                dest.mkdir(parents=True,exist_ok=True)
                base=slot/(cc+'-'+f)/('slot' if mode=='table' else 'handwritten')
                opts=[cc,'-std=c++23','-O2','-g0','-I'+str(base),'-I'+str(HERE)]
                if mode=='handwritten':
                    text=handwritten(json.loads((corpus/f/'vocabulary.json').read_text()),f)
                    deps=[]
                else:
                    text=(prior/f/'table/scanner.cpp').read_text()
                    if cc=='g++':
                        text=once(text,'__builtin_assume(var_i_5 >= 0);',
                                  'if (var_i_5 < 0) __builtin_unreachable();')
                    opts+=['-I'+str(HERE/'byte_access'),'-I'+str(rt/'generated/public')]
                    deps=[slot/(cc+'.o')]
                src=dest/'scanner.cpp'
                src.write_text(text)
                exe=dest/'clean'
                run([*opts,src,HERE/'driver.cpp',*deps,'-o',exe])
                verify(exe,root/f)
                hashes[cc+' '+f+' '+mode]=hashlib.sha256(exe.read_bytes()).hexdigest()
                for role in FILES:
                    path=corpus/f/role
                    expected=(corpus/f/(role+'.oracle')).read_bytes()
                    assert run([exe,'dump',path,path])==expected
                    file_meta[f+'/'+role]=dict(bytes=path.stat().st_size,tokens=len(expected.splitlines()),
                        sha256=hashlib.sha256(path.read_bytes()).hexdigest())
    os.sched_setaffinity(0,{cpu})
    for f in FLAVORS:
        for role in FILES:
            path=corpus/f/role
            for pair in range(33):
                for cc in (('clang++','g++') if pair%4<2 else ('g++','clang++')):
                    for mode in (('table','handwritten') if pair%2 else ('handwritten','table')):
                        times=list(map(float,run([work/cc/f/mode/'clean','time',path,path]).split()))
                        assert len(times)==9 and min(times)>0
                        if pair: rows.append(dict(flavor=f,file=role,compiler=cc,pair=pair,mode=mode,seconds=times))
    return dict(cpu=cpu,versions=versions,files=file_meta,binaries=hashes,rows=rows)


if __name__=='__main__':
    p=argparse.ArgumentParser(description=__doc__)
    for n in ('root','slot','corpus','prior','runtime','work'): p.add_argument(n,type=Path)
    p.add_argument('--cpu',required=True,type=int)
    a=p.parse_args()
    print(json.dumps(measure(*(v.resolve() for v in (a.root,a.slot,a.corpus,a.prior,a.runtime,a.work)),a.cpu),indent=2))
