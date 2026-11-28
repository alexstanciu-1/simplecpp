"""Isolated storage experiment on copies of compiler-emitted units. See report.md."""
import argparse
import json
import platform
import random
import re
import shutil
import subprocess
from pathlib import Path
from time import perf_counter

HERE = Path(__file__).resolve().parent.parent
VARIANTS = ('copy', 'move', 'reserve_copy', 'reserve_move')


def replace_once(text, old, new):
    if text.count(old) != 1:
        raise ValueError(f'Expected one emitted occurrence of {old!r}')
    return text.replace(old, new)


def instrument(out, dest, flavor):
    units = dest / 'units'
    shutil.copytree(out/'units', units, dirs_exist_ok=True)
    manifest = json.loads((out/'manifest.json').read_text())
    unit = dest / manifest['sources'][f'{flavor}/scanner.{"phs" if flavor == "php" else flavor}']
    api = unit.with_suffix('.hpp')
    s = unit.read_text()
    decl = re.search(r'(scpp_vector_\w+) (var_items_\d+) = [^;]+;', s)
    name, items = decl.groups()
    reserve = re.search(r'\('+items+r'\)\.reserve\([^;]+;', s)[0]
    s = replace_once(s, reserve, '#ifdef PRIOR\n'+reserve+'\n#endif')
    result = re.search(r'return (var_out_\d+);', s)[1]
    assignment = f'({result}).tokens = {items};'
    # Accept fixtures from before and after compiler-owned terminal transfer.
    s = s.replace(f'({result}).tokens = std::move({items});', assignment)
    # The next statement is return; no references to this local escape the scanner.
    s = replace_once(s, assignment+f'\n\treturn {result};',
                     f'#ifdef MOVE\n({result}).tokens = static_cast<{name}&&>({items});\n#else\n'
                     +assignment+f'\n#endif\n\treturn {result};')
    unit.write_text(s)
    return unit, api


def run(args):
    return subprocess.check_output(list(map(str,args)))


def measure(root, runtime, work):
    work.mkdir(parents=True, exist_ok=True)
    rows = []
    rng = random.Random(1987)
    includes = ['-I'+str(runtime/p) for p in ('generated/public','generated/private','src/numeric','src/sequence','src/hash')]
    # All variants see the same previous revision (including the unhinted controls).
    # Propagate the most recent successful count on edits and preserve it on failure.
    driver = (HERE/'driver.cpp').read_text()
    (work/'driver.cpp').write_text(driver)
    for cc in ('clang++','g++'):
        flags = [cc,'-std=c++23','-O2','-g0',*includes]
        rt = work/(cc+'.o')
        run([*flags,'-c',runtime/'generated/src/runtime.cpp','-o',rt])
        for flavor in ('cpp','php','js'):
            corpus = root/flavor
            dest = work/(cc+'-'+flavor)
            src, api = instrument(root/(flavor+'-out'),dest,flavor)
            (dest/'adapter.hpp').write_text(f'''#include "{api.name}"
#include <string>
using Result=App::scpp_records::Scan;
auto make_input(const std::string& s) {{return scpp::string(s.data(),long(s.size()));}}
auto run(const scpp::string& s,size_t hint) {{return App::scan_(s,hint);}}
''')
            local = [*flags,'-I'+str(dest),'-I'+str(src.parent)]
            for obj, extra in [('driver',[]),('counter',['-DCOUNT'])]:
                run([*local,*extra,'-c',work/'driver.cpp','-o',dest/(obj+'.o')])
            run([*flags,'-c',HERE/'allocations.cpp','-o',dest/'alloc.o'])
            cases = {}
            for variant in VARIANTS:
                switches = (['-DMOVE'] if variant.endswith('move') else []) + (['-DPRIOR'] if variant.startswith('reserve') else [])
                obj = dest/(variant+'.o')
                start = perf_counter()
                run([*local,*switches,'-c',src,'-o',obj])
                compile_ms = (perf_counter()-start)*1000
                exe = dest/variant
                run([cc,obj,dest/'driver.o',rt,'-o',exe])
                run([cc,obj,dest/'counter.o',dest/'alloc.o',rt,'-o',str(exe)+'-count'])
                for i in range(11):
                    expected = (corpus/f'expected{i}').read_bytes()
                    for prev in (0,1,3,10) if i==6 else (0,10):
                        assert run([exe,'dump',corpus/f'input{i}',corpus/f'input{prev}']) == expected
                edits = range(4,8)
                assert run([exe,'edits',corpus/'input3',*[corpus/f'input{i}' for i in edits]]) == b''.join(
                    (corpus/f'expected{i}').read_bytes()+b'|\n' for i in edits)
                cases[variant] = dict(compile_ms=compile_ms,corpora={})
            for i, name in enumerate(('identifiers','operators','mixed'),8):
                path = corpus/f'input{i}'
                prev = dest/'previous'
                prev.write_bytes(path.read_bytes()+b' previous')
                for variant in VARIANTS:
                    alloc, size = map(int,run([str(dest/variant)+'-count','count',path,prev]).split())
                    cases[variant]['corpora'][name] = dict(allocations=alloc,allocated_bytes=size,seconds=[],bytes=path.stat().st_size)
                for _ in range(3):
                    order=list(VARIANTS)
                    rng.shuffle(order)
                    for variant in order:
                        batch = map(float,run([dest/variant,'time',path,prev]).split())
                        cases[variant]['corpora'][name]['seconds'].extend(batch)
            rows.append(dict(cc=cc,version=run([cc,'--version']).decode().splitlines()[0],flavor=flavor,cases=cases))
            print(cc,flavor,'pass',flush=True)
    (work/'results.json').write_text(json.dumps(dict(host=platform.node(),rows=rows),indent=2)+'\n')


if __name__=='__main__':
    p=argparse.ArgumentParser(description=__doc__)
    for key in ('fixtures','runtime','work'):
        p.add_argument(key,type=Path)
    a=p.parse_args()
    measure(a.fixtures.resolve(),a.runtime.resolve(),a.work.resolve())
