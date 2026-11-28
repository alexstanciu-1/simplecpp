"""Prepare full source oracles locally, then build and time complete-file probes on dev002."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import re
from classification import rewrite
from measure import HERE, once, run, verify

FLAVORS = ('cpp','php','js')
FILES = ('billing','scanner','support')
ORACLE = r'''
require $argv[1].'/compiler/boot.php';
use scpp\compiler\{Tokenizer,source_file,flavor,token_kind};
$f=flavor::from($argv[2]);
$s=file_get_contents($argv[3]);
$tokens=(new Tokenizer(new source_file('input',$s,$f)))->tokenize()->tokens;
foreach ($tokens as $t) echo "$t->offset $t->length ",$t->kind->value,' ',substr($s,$t->offset,$t->length),"\n";
'''
PUNCT = r'''
require $argv[1].'/compiler/boot.php';
foreach (scpp\compiler\token_kind::cases() as $k)
 if (ctype_punct($k->label())) echo json_encode([$k->label(),$k->value]),"\n";
'''


def prepare(repo, corpus):
    medium=repo/'samples/capability-benchmarks/medium'
    words=(repo/'compiler/compiler/flavor.php').read_text()
    punct=[json.loads(line) for line in run(['php','-r',PUNCT,repo]).splitlines()]
    for f,b,s in zip(FLAVORS,['001.scpp','000.phs','002.jss'],['support/core.scpp','main.phs','support/labels.jss']):
        d=corpus/f
        d.mkdir(parents=True,exist_ok=True)
        keywords=re.search("self::"+f+r" => '([^']+)'",words)[1].split()
        (d/'vocabulary.json').write_text(json.dumps(dict(keywords=keywords,punct=punct)))
        for role,src in zip(FILES,[medium/('billing/batch_'+b),HERE/('scanner.'+('phs' if f == 'php' else f)),medium/s]):
            data=src.read_bytes()
            (d/role).write_bytes(data)
            (d/(role+'.path')).write_text(str(src.relative_to(repo)))
            (d/(role+'.oracle')).write_bytes(run(['php','-r',ORACLE,repo,f,src]))


def lexical_helpers(vocab, f):
    # Complement the bounded common scanner for this source corpus. No input offsets,
    # oracle token streams or file-specific spelling tables enter the scanner.
    groups={}
    for word in vocab['keywords']:
        groups.setdefault(len(word),[]).append(word)
    keyword='static bool keyword(std::string_view word) { switch(word.size()) {\n'
    for length,words in groups.items():
        keyword+=f'case {length}: return '+' || '.join('word == '+json.dumps(w) for w in words)+';\n'
    keyword+='default: return false; } }\n'
    fallback='''
static int extra(const tokenizer_bench::split_view& v, scpp::int64& i, unsigned b) {
    const auto n=v.size();
    auto digit=[](unsigned x){return x>='0' && x<='9';};
    if (digit(b)) {
        while (i<n && digit(v.byte_at(i))) ++i;
        if (i+1<n && v.byte_at(i)=='.' && digit(v.byte_at(i+1))) {
            ++i; while(i<n && digit(v.byte_at(i))) ++i; return 4;
        }
        return 3;
    }
    if (b==34 || b==39) {
        while (i<n) {
            auto x=v.byte_at(i++);
            if (x==b) return b==34 ? 6 : 5;
            if (x==92 && i<n) ++i;
        }
        return 0;
    }
'''
    if f=='php':
        fallback+='''
    if (b=='$') {
        const auto start=i;
        while(i<n) {
            auto x=v.byte_at(i);
            if (!(x=='_' || (x>='a' && x<='z') || (x>='A' && x<='Z') || (i>start && digit(x)))) break;
            ++i;
        }
        return i>start ? 2 : 0;
    }
'''
    fallback+='switch(b) {\n'
    punct={}
    for spelling,k in vocab['punct']:
        if spelling[0]!='=':
            punct.setdefault(spelling[0],[]).append((spelling,k))
    for first,items in punct.items():
        fallback+=f'case {ord(first)}:\n'
        for spelling,k in sorted(items,key=lambda x:-len(x[0])):
            tail=spelling[1:]
            if tail:
                cond=' && '.join(f'v.byte_at(i+{j})=={ord(c)}' for j,c in enumerate(tail))
                fallback+=f'if(n-i>={len(tail)} && {cond}) {{ i+={len(tail)}; return {k}; }}\n'
            else: fallback+=f'return {k};\n'
        fallback+='break;\n'
    fallback+='} return 0; }\n'
    return keyword, fallback


def full_file(text, vocab, f):
    keyword,fallback=lexical_helpers(vocab,f)
    text='#include <string_view>\n'+text
    text=once(text,'namespace App {\nscpp_records::Scan','namespace App {\n'+keyword+fallback+'scpp_records::Scan')
    text=once(text,'const tokenizer_bench::split_view view((var_out_2).source);',
              'const tokenizer_bench::split_view view((var_out_2).source);\n    const auto bytes=var_out_2.source.data();')
    old=re.search(r'if \(\(\(var_i_5 - var_start_7\).*?\{\n\s*var_kind_8 = 62LL;',text)[0]
    text=once(text,old,'if (keyword(std::string_view(bytes+var_start_7,var_i_5-var_start_7))) {\n                var_kind_8 = 62LL;')
    return once(text,'else {\n\t\t\t(var_out_2).error', 'else if (!(var_kind_8 = extra(view,var_i_5,var_b_6))) {\n\t\t\t(var_out_2).error')


def bench(corpus,root,slot,signed,rt,work,cpu):
    meta={}
    for f in FLAVORS:
        src=(signed/f/'nonnegative/scanner.cpp').read_text()
        common=full_file(src,json.loads((corpus/f/'vocabulary.json').read_text()),f)
        # Apply the exact same table rewrite as in the synthetic experiment.
        # Its declaration belongs before the first scan_ definition.
        for mode in ('control','table'):
            text=common if mode=='control' else rewrite(src,'table')
            if mode=='table':
                # Move declaration so full_file can insert its shared handlers.
                a=text.index('static constexpr unsigned char byte_class')
                b=text.index('\n',a)+1
                table=text[a:b]
                text=table+full_file(text[:a]+text[b:],json.loads((corpus/f/'vocabulary.json').read_text()),f)
            dest=work/f/mode
            dest.mkdir(parents=True,exist_ok=True)
            (dest/'scanner.cpp').write_text(text)
            opts=['clang++','-std=c++23','-O2','-g0','-I'+str(slot/('clang++-'+f)/'slot'),'-I'+str(HERE/'byte_access'),'-I'+str(rt/'generated/public')]
            run([*opts,dest/'scanner.cpp',HERE/'driver.cpp',slot/'clang++.o','-o',dest/'clean'])
            verify(dest/'clean',root/f)
            for role in FILES:
                path=corpus/f/role
                expected=(corpus/f/(role+'.oracle')).read_bytes()
                got=run([dest/'clean','dump',path,path])
                assert got==expected,(f,role,mode,got[:150],expected[:150])
                meta[f+'/'+role]=dict(path=(corpus/f/(role+'.path')).read_text(),bytes=path.stat().st_size,
                    sha256=hashlib.sha256(path.read_bytes()).hexdigest(),tokens=len(expected.splitlines()))
    os.sched_setaffinity(0,{cpu})
    rows=[]
    for f in FLAVORS:
        for role in FILES:
            path=corpus/f/role
            for i in range(33):
                for mode in (('control','table') if i%2 else ('table','control')):
                    samples=list(map(float,run([work/f/mode/'clean','time',path,path]).split()))
                    assert len(samples)==9 and min(samples)>0
                    if i: rows.append(dict(flavor=f,file=role,mode=mode,pair=i,seconds=samples))
    return dict(cpu=cpu,compiler=run(['clang++','--version']).decode().splitlines()[0],files=meta,rows=rows)


if __name__=='__main__':
    p=argparse.ArgumentParser(description=__doc__)
    sub=p.add_subparsers(dest='cmd',required=True)
    prep=sub.add_parser('prepare')
    for key in ('repo','corpus'): prep.add_argument(key,type=Path)
    timing=sub.add_parser('bench')
    for key in ('corpus','root','slot','signed','runtime','work'): timing.add_argument(key,type=Path)
    timing.add_argument('--cpu',type=int,required=True)
    a=p.parse_args()
    if a.cmd=='prepare': prepare(a.repo.resolve(),a.corpus.resolve())
    else: print(json.dumps(bench(*(x.resolve() for x in (a.corpus,a.root,a.slot,a.signed,a.runtime,a.work)),a.cpu),indent=2))
