"""Cross byte classification and equals lookahead in the proven-cursor Clang scanner."""
import argparse
import hashlib
from itertools import permutations
import json
import os
from pathlib import Path
import random
import re

from measure import HERE, once, run, verify

MODES = ('control', 'table', 'lookahead', 'both')


def rewrite(text, mode):
    if mode in ('table', 'both'):
        # Index by the existing uint8. Values distinguish space, identifier start,
        # identifier continuation only, equals, and all remaining byte values.
        classes = [1 if b in b' \t\n\r' else 2 if b == 95 or 65 <= b <= 90 or 97 <= b <= 122
                   else 3 if 48 <= b <= 57 else 4 if b == 61 else 0 for b in range(256)]
        table = 'static constexpr unsigned char byte_class[256] = {' + ','.join(map(str,classes)) + '};\n'
        text = once(text, 'namespace App {\nscpp_records::Scan', 'namespace App {\n'+table+'scpp_records::Scan')
        patterns = [(r'if \(static_cast<scpp::int64>\(var_b_6\) == 32LL[^\n]+', 'const auto cls = byte_class[var_b_6];\n\t\tif (cls == 1) {'),
                    (r'if \(\(static_cast<scpp::int64>\(var_b_6\) >= 65LL[^\n]+', 'if (cls == 2) {'),
                    (r'if \(!\(\(static_cast<scpp::int64>\(var_b_6\) >= 65LL[^\n]+', 'const auto next = byte_class[var_b_6];\n\t\t\t\tif (next != 2 && next != 3) {'),
                    (r'else if \(static_cast<scpp::int64>\(var_b_6\) == 61LL\) \{', 'else if (cls == 4) {')]
        for pattern, replacement in patterns:
            matches = re.findall(pattern, text)
            assert len(matches) == 1
            text = once(text, matches[0], replacement)
    if mode in ('lookahead','both'):
        begin = text.index('var_kind_8 = 61LL;')
        end = text.index('\n\t\t}', begin)
        # Both reads are valid on the two-byte path, even if the first is not '='.
        text = text[:begin]+'''var_kind_8 = 61LL;
            const auto remaining = var_size_4 - var_i_5;
            if (remaining >= 2) {
                const bool first = view.byte_at(var_i_5) == '=';
                const bool second = view.byte_at(var_i_5 + 1) == '=';
                var_i_5 += first + (first && second);
                var_kind_8 = first ? (second ? 11 : 15) : 61;
            } else if (remaining == 1 && view.byte_at(var_i_5) == '=') {
                ++var_i_5;
                var_kind_8 = 15;
            }'''+text[end:]
    return text


def verify_edges(exe, dest):
    for n in range(1,10):
        data=b'='*n
        for suffix in (b'',b' ',b'a'):
            path=dest/'edge'
            path.write_bytes(data+suffix)
            lengths=[3]*(n//3)+([n%3] if n%3 else [])
            expected=[]
            offset=0
            for length in lengths:
                expected.append(f'{offset} {length} { {1:61,2:15,3:11}[length]} '+('='*length))
                offset+=length
            if suffix==b'a':
                expected.append(f'{n} 1 1 a')
            assert run([exe,'dump',path])==('\n'.join(expected)+'\n').encode()


def experiment(root,slot,signed,rt,work,cpu):
    evidence={}
    for flavor in ('cpp','php','js'):
        baseline=(signed/flavor/'nonnegative/scanner.cpp').read_text()
        for mode in MODES:
            dest=work/flavor/mode
            dest.mkdir(parents=True,exist_ok=True)
            source=dest/'scanner.cpp'
            source.write_text(rewrite(baseline,mode))
            flags=['clang++','-std=c++23','-O2','-g0','-I'+str(slot/('clang++-'+flavor)/'slot'),
                   '-I'+str(HERE/'byte_access'),'-I'+str(rt/'generated/public')]
            obj=dest/'scanner.o'
            run([*flags,'-c',source,'-o',obj])
            exe=dest/'clean'
            run([*flags,HERE/'driver.cpp',obj,slot/'clang++.o','-o',exe])
            verify(exe,root/flavor)
            verify_edges(exe,dest)
            if mode=='control':
                assert obj.read_bytes()==(signed/flavor/'nonnegative/scanner.o').read_bytes()
            evidence[flavor+' '+mode]=hashlib.sha256(exe.read_bytes()).hexdigest()
    os.sched_setaffinity(0,{cpu})
    rows=[]
    for flavor in ('cpp','php','js'):
        for corpus,index in [('operators',9),('mixed',10),('identifiers',8)]:
            source=root/flavor/f'input{index}'
            hint=work/flavor/'prior'
            hint.write_bytes(source.read_bytes()+b' previous')
            for mode in MODES:
                run([work/flavor/mode/'clean','time',source,hint])
            orders=list(permutations(MODES))
            random.Random(741).shuffle(orders)
            for i,order in enumerate(orders):
                for mode in order:
                    samples=list(map(float,run([work/flavor/mode/'clean','time',source,hint]).split()))
                    assert len(samples)==9 and min(samples)>0
                    rows.append(dict(flavor=flavor,corpus=corpus,round=i,mode=mode,seconds=samples))
    return dict(cpu=cpu,compiler=run(['clang++','--version']).decode().splitlines()[0],hashes=evidence,rows=rows)


if __name__=='__main__':
    p=argparse.ArgumentParser(description=__doc__)
    for name in ('root','slot','signed','runtime','work'): p.add_argument(name,type=Path)
    p.add_argument('--cpu',type=int,required=True)
    a=p.parse_args()
    print(json.dumps(experiment(*(v.resolve() for v in (a.root,a.slot,a.signed,a.runtime,a.work)),a.cpu),indent=2))
