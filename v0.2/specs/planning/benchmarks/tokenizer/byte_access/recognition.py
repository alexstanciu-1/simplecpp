"""Pinned Clang operator comparison with crossed recognition and output controls."""
import argparse
import itertools
import json
import os
import random
from pathlib import Path

from measure import HERE, once, run, verify

HASH = '''#include <cstdint>
std::uint64_t last_digest;
static std::uint64_t mix_token(std::uint64_t d, std::uint64_t pos, std::uint64_t len, std::uint64_t kind) {
    return (d * 16777619u) ^ (pos * 65537u + len * 257u + kind);
}
'''


def build(root, slot, runtime, dest, flavor, recognizer, output):
    family = slot / ('clang++-' + flavor)
    native = recognizer == 'native'
    headers = family / ('handwritten' if native else 'slot')
    baseline = (HERE/'baseline.cpp').read_text()
    loop = baseline[baseline.index('    for (std::size_t i'):baseline.rindex('    return out;')]
    append = 'out.tokens.push_back({static_cast<std::int64_t>(start), static_cast<std::int64_t>(i-start), kind});'
    if native:
        source = baseline
        success = '    return out;\n}'
        reserve = '    out.tokens.reserve(prior_count ? prior_count : (text.empty() ? 0 : 4));'
        fields = 'start, i-start, kind'
    else:
        source = (family/'transfer/scanner.cpp').read_text()
        success = '(var_out_2).tokens ='
        reserve = '(var_items_3).reserve(var_previous_count_1);'
        fields = 'var_start_7, (var_i_5 - var_start_7), var_kind_8'
        if recognizer == 'hand_loop':
            begin = source.index('const tokenizer_bench::split_view view(')
            end = source.index('#ifdef TRACE\nphase="publish tokens";',begin)
            adapted = once(loop,'out.tokens.clear();','')
            adapted = adapted.replace('out.error','var_out_2.error').replace('out.reason = "Unsupported token";','var_out_2.reason = scpp::string{"Unsupported token",17};').replace('return out;', 'return var_out_2;')
            fields = 'start, static_cast<scpp::int64>(i-start), kind'
            adapted = once(adapted,append,'(var_items_3).append_fields('+fields+');')
            source = (source[:begin] + 'std::string_view text(var_out_2.source.data(), var_out_2.source.size());\n'
                      + '(var_items_3).reserve(std::min(var_previous_count_1,static_cast<scpp::int64>(text.size())));\n'
                      + adapted + source[end:])
            source = '#include <algorithm>\n#include <string_view>\n'+baseline[baseline.index('static bool letter'):baseline.index('Scan scan(')]+source
            reserve = '(var_items_3).reserve(std::min(var_previous_count_1,static_cast<scpp::int64>(text.size())));'
        append = '(var_items_3).append_fields('+fields+');'
    driver = (HERE/'driver.cpp').read_text()
    if output == 'digest':
        source = HASH + once(source, reserve, 'std::uint64_t digest = 0;')
        source = once(source,append,'digest = mix_token(digest, '+fields+');')
        source = once(source,success,'last_digest = digest;\n'+success)
        driver = '#include <cstdint>\nextern std::uint64_t last_digest;\n'+once(driver,'    return out.str();','    if (r.error < 0) out << last_digest << "\\n";\n    return out.str();')
    flags=['clang++','-std=c++23','-O2','-g0','-I'+str(headers),'-I'+str(HERE),'-I'+str(HERE/'byte_access'),'-I'+str(runtime/'generated/public')]
    if flavor != 'php':
        flags += ['-DEXTRA_WORD="'+('auto' if flavor=='cpp' else 'let')+'"']
    dest.mkdir(parents=True,exist_ok=True)
    (dest/'scanner.cpp').write_text(source)
    (dest/'driver.cpp').write_text(driver)
    run([*flags,'-c',dest/'scanner.cpp','-o',dest/'scanner.o'])
    run([*flags,dest/'driver.cpp',dest/'scanner.o',*([] if native else [slot/'clang++.o']),'-o',dest/'clean'])
    exe = dest/'clean'
    corpus = root/flavor
    if output == 'vector':
        verify(exe,corpus)
    else:
        for i in range(11):
            expected=(corpus/f'expected{i}').read_bytes()
            if not expected.startswith(b'error'):
                value=0
                for line in expected.splitlines():
                    pos,length,kind=map(int,line.split()[:3])
                    value=((value*16777619) ^ (pos*65537+length*257+kind)) % (1<<64)
                expected=f'{value}\n'.encode()
            assert run([exe,'dump',corpus/f'input{i}'])==expected
    return exe


def measure(root, slot, runtime, work, cpu):
    modes=list(itertools.product(('generated','hand_loop','native'),('digest','vector')))
    exes={(f,m):build(root,slot,runtime,work/f/'_'.join(m),f,*m)
          for f in ('cpp','php','js') for m in modes}
    os.sched_setaffinity(0,{cpu})
    rng=random.Random(6173)
    rows=[]
    for f in ('cpp','php','js'):
        source=root/f/'input9'
        hint=work/f/'prior'
        hint.write_bytes(source.read_bytes()+b' previous')
        for m in modes:
            run([exes[f,m],'time',source,hint])
        # Rotate randomized orders so each configuration occupies each position equally.
        for block in range(4):
            order=modes.copy()
            rng.shuffle(order)
            for shift in range(len(order)):
                for m in order[shift:]+order[:shift]:
                    samples=list(map(float,run([exes[f,m],'time',source,hint]).split()))
                    assert len(samples)==9
                    rows.append(dict(flavor=f,round=block*6+shift,recognizer=m[0],output=m[1],seconds=samples))
    return dict(cpu=cpu,compiler=run(['clang++','--version']).decode().splitlines()[0],bytes=source.stat().st_size,
                tokens=len((root/f/'expected9').read_bytes().splitlines()),rows=rows)


if __name__=='__main__':
    p=argparse.ArgumentParser(description=__doc__)
    for name in ('root','slot','runtime','work'):
        p.add_argument(name,type=Path)
    p.add_argument('--cpu',type=int,required=True)
    a=p.parse_args()
    print(json.dumps(measure(*(x.resolve() for x in (a.root,a.slot,a.runtime,a.work)),a.cpu),indent=2))
