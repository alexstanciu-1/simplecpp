"""Matched, presized native scanner comparisons. Uses private copies of emitted units."""
import argparse
import json
import platform
import random
import re
import shutil
import subprocess
import time
from pathlib import Path

HERE = Path(__file__).resolve().parent.parent
MODES = ('runtime', 'inline_checked', 'direct')
VARIANTS = [f'{mode}_{publication}' for mode in MODES for publication in ('copy', 'move')] + ['handwritten']


def run(args):
    return subprocess.check_output(list(map(str, args)))


def once(text, old, new):
    if text.count(old) != 1:
        raise ValueError(old)
    return text.replace(old, new)


def mark(name):
    return f'\n#ifdef TRACE\nphase="{name}";\n#endif\n'


def scanner(src, mode, move):
    text = src.read_text()
    name = re.search(r'(var_text_\d+)\)', text)[1]
    decl = re.search(r'(scpp_vector_\w+) (var_items_\d+) = [^;]+;', text)
    vector, items = decl.groups()
    result = re.search(r'return (var_out_\d+);', text)[1]
    assignment = f'({result}).tokens = {items};'
    # Accept fixtures from before and after compiler-owned terminal transfer.
    text = text.replace(f'({result}).tokens = std::move({items});', assignment)
    text = once(text, assignment+'\n\treturn '+result+';', mark('publish tokens') +
                (f'({result}).tokens = static_cast<{vector}&&>({items});' if move else assignment) +
                '\n\treturn '+result+';')
    text = once(text, f'({result}).source =', mark('result source') + f'({result}).source =')
    reserve = re.search(r'\('+items+r'\)\.reserve\([^;]+;', text)[0]
    text = once(text, reserve, mark('reserve tokens')+reserve+mark('recognition'))
    if mode != 'runtime':
        # One immutable byte span per scan. The inline variants differ only in the range guard.
        size = re.search(r'(var_size_\d+) = scpp::str::byte_length\([^;]+;', text)
        guard = (f'if (i < 0 || i >= {size[1]}) throw std::out_of_range("byte index"); '
                 if mode == 'inline_checked' else '')
        text = once(text,size[0],size[0]+f'\nconst char* bytes = {name}.data();\n'
                    f'auto read_byte = [&](scpp::int64 i) -> scpp::uint8 {{ {guard}return static_cast<scpp::uint8>(bytes[i]); }};')
        needle = f'({name}).byte_at('
        assert needle in text
        text = text.replace(needle, 'read_byte(')
    return '#ifdef TRACE\nextern const char* phase;\n#endif\n'+text


def verify(exe, corpus):
    for i in range(11):
        expected=(corpus/f'expected{i}').read_bytes()
        assert run([exe,'dump',corpus/f'input{i}',corpus/'input10']) == expected
    edits=range(4,8)
    assert run([exe,'edits',corpus/'input3',*[corpus/f'input{i}' for i in edits]]) == b''.join(
        (corpus/f'expected{i}').read_bytes()+b'|\n' for i in edits)


def view_scanner_text(text, split):
    pattern = r'(scpp::int64 var_size_\d+ = )scpp::str::byte_length\((var_text_\d+)\);'
    match = re.search(pattern, text)
    size, source = match.groups()
    pos = text.rindex('[[maybe_unused]]', 0, match.start())
    if split:
        acquire = f'const scpp::string owner(std::move({source}));\n\tconst tokenizer_bench::split_view view(owner);'
    else:
        acquire = f'tokenizer_bench::checked_byte_view view(std::move({source}));'
    text = text[:pos] + acquire + '\n\t' + text[pos:]
    text = once(text, match[0], size + 'view.size();')
    before, after = text.split(size + 'view.size();')
    after = after.replace('(' + source + ').byte_at(', 'view.byte_at(')
    assert source not in after and 'view.byte_at(' in after
    header = 'split_view.hpp' if split else 'checked_view.hpp'
    return f'#include "{header}"\n' + before + size + 'view.size();' + after


def publication_scanner(src, publication):
    text = scanner(src, 'runtime', publication == 'move')
    if publication == 'direct':
        decl = re.search(r'(scpp_vector_\w+) (var_items_\d+) = \1\{\};', text)
        vector, items = decl.groups()
        result = re.search(r'return (var_out_\d+);', text)[1]
        field = f'({result}).tokens'
        text = once(text, decl[0], f'auto& {items} = {field};')
        text = once(text, f'{field} = {items};', '')
        error = re.search(r'\(' + result + r'\)\.reason = [^;]+;', text)[0]
        text = once(text, error, error + f'\n\t\t\t{field} = {vector}{{}};')
    return view_scanner_text(text, split=True)


def measure(root, runtime, work, publication_test=False, append_test=False, slot_test=False):
    variants = ['copy', 'move', 'direct', 'handwritten'] if publication_test else VARIANTS
    append_test = append_test or slot_test
    if append_test:
        from append_probe import scanner_control
        variants = ['named', 'aggregate', 'named_fast', 'aggregate_fast', 'handwritten']
    if slot_test:
        variants = ['named', 'named_fast', 'slot', 'handwritten']
    work.mkdir(parents=True,exist_ok=True)
    driver=(HERE/'driver.cpp').read_text()
    # Fixed-size trace records avoid counting observation machinery.
    trace_driver=once(driver,'calls = requested = 0;',
                      'extern bool tracing; extern void print_events(); calls=requested=0; tracing=true;')
    trace_driver=once(trace_driver,'auto c=calls, b=requested;', 'tracing=false; print_events(); auto c=calls, b=requested;')
    (work/'trace-driver.cpp').write_text(trace_driver)
    hook=(HERE/'allocations.cpp').read_text().replace('#include <new>', '''#include <new>
#include <cstdio>
bool tracing=false;
const char* phase="input argument";
static struct {const char* site; std::size_t bytes;} events[32];
static unsigned used=0;
void print_events(){for(unsigned i=0;i<used;++i) std::printf("%s:%zu\\n",events[i].site,events[i].bytes);}''')
    hook=once(hook,'++calls;', 'if(tracing){if(calls==0) phase="input argument"; if(used>=32) std::abort(); events[used++]={phase,size};} ++calls;')
    (work/'trace.cpp').write_text(hook)
    rows=[]
    rng=random.Random(8791)
    for cc in ('clang++','g++'):
        flags=[cc,'-std=c++23','-g0','-O2',*['-I'+str(runtime/p) for p in ('generated/public','generated/private','src/numeric','src/sequence','src/hash')]]
        runtime_obj=work/(cc+'.o')
        run([*flags,'-c',runtime/'generated/src/runtime.cpp','-o',runtime_obj])
        for flavor in ('cpp','php','js'):
            corpus=root/flavor
            out=root/(flavor+'-out')
            manifest=json.loads((out/'manifest.json').read_text())
            src=out/manifest['sources'][f'{flavor}/scanner.{"phs" if flavor == "php" else flavor}']
            dest=work/(cc+'-'+flavor)
            dest.mkdir(exist_ok=True)
            results={}
            for label in variants:
                native=label=='handwritten'
                folder=dest/label
                folder.mkdir(exist_ok=True)
                if native:
                    text=(HERE/'baseline.cpp').read_text()
                    text=once(text,'out.tokens.reserve',mark('reserve tokens')+'out.tokens.reserve')
                    text='#ifdef TRACE\nextern const char* phase;\n#endif\n'+text
                    adapter='#include "baseline.hpp"\nusing Result=Scan;\nauto make_input(const std::string& s){return s;}\nauto run(const std::string& s,size_t n){return scan(s,n);}'
                else:
                    if append_test:
                        text=scanner_control(src,label,folder,publication_scanner(src,'move'),once)
                    elif publication_test:
                        text=publication_scanner(src,label)
                    else:
                        mode,publication=label.rsplit('_',1)
                        text=scanner(src,mode,publication=='move')
                    adapter=f'#include "{src.with_suffix(".hpp").name}"\n#include <string>\nusing Result=App::scpp_records::Scan;\nauto make_input(const std::string& s){{return scpp::string(s.data(),long(s.size()));}}\nauto run(const scpp::string& s,size_t n){{return App::scan_(s,n);}}'
                (folder/'scanner.cpp').write_text(text)
                (folder/'adapter.hpp').write_text(adapter)
                opts=[*flags,'-I'+str(folder),'-I'+str(HERE),'-I'+str(HERE/'byte_access'),'-I'+str(src.parent)]
                if native and flavor!='php': opts+=['-DEXTRA_WORD="'+('let' if flavor=='js' else 'auto')+'"']
                # Separate translation units and no LTO, as in the production scanner benchmark.
                deps=[] if native else [runtime_obj]
                exe=folder/'clean'
                compile_ms=[]
                obj=folder/'scanner.o'
                for _ in range(3 if publication_test or append_test else 1):
                    start=time.perf_counter()
                    run([*opts,'-c',folder/'scanner.cpp','-o',obj])
                    compile_ms.append(1000*(time.perf_counter()-start))
                if append_test:
                    if cc=='clang++':
                        run([*opts,'-S','-emit-llvm',folder/'scanner.cpp','-o',folder/'scanner.ll'])
                    else:
                        run([*opts,'-fdump-tree-optimized='+str(folder/'scanner.gimple'),'-S',folder/'scanner.cpp','-o',folder/'scanner.s'])
                run([*opts,obj,HERE/'driver.cpp',*deps,'-o',exe])
                run([*opts,'-DTRACE','-DCOUNT',folder/'scanner.cpp',work/'trace-driver.cpp',work/'trace.cpp',*deps,'-o',folder/'trace'])
                verify(exe,corpus)
                if slot_test:
                    for hint_bytes in (b'', b'a'):
                        hint = folder/'growth_hint'
                        hint.write_bytes(hint_bytes)
                        assert run([exe,'dump',corpus/'input9',hint]) == (corpus/'expected9').read_bytes()
                results[label]={}
                if publication_test or append_test:
                    results[label]['compile_ms']=compile_ms
                    late_error=folder/'late_error'
                    prefix=b'if a == while '
                    late_error.write_bytes(prefix+(corpus/'input6').read_bytes())
                    expected=(corpus/'expected6').read_bytes()
                    offset=int(expected.split()[1])
                    diagnostic=once(expected,b'error '+str(offset).encode()+b' ',
                                    b'error '+str(len(prefix)+offset).encode()+b' ')
                    assert run([exe,'edits',corpus/'input3',corpus/'input4',late_error,corpus/'input7']) == (
                        (corpus/'expected4').read_bytes()+b'|\n'+diagnostic+b'|\n'+
                        (corpus/'expected7').read_bytes()+b'|\n')
            for i,corpus_name in enumerate(('identifiers','operators','mixed'),8):
                path=corpus/f'input{i}'
                prior=dest/'prior'
                prior.write_bytes(path.read_bytes()+b' previous')
                for label in variants:
                    lines=run([dest/label/'trace','count',path,prior]).decode().splitlines()
                    events=[(s,int(b)) for s,b in [line.split(':') for line in lines[:-1]]]
                    assert tuple(map(int,lines[-1].split()))==(len(events),sum(b for _,b in events))
                    results[label][corpus_name]=dict(seconds=[],bytes=path.stat().st_size,events=events)
                for _ in range(3):
                    order=variants.copy()
                    rng.shuffle(order)
                    for label in order:
                        samples=run([dest/label/'clean','time',path,prior]).split()
                        results[label][corpus_name]['seconds'].extend(map(float,samples))
            rows.append(dict(cc=cc,flavor=flavor,version=run([cc,'--version']).decode().splitlines()[0],cases=results))
            print(cc,flavor,'passed',flush=True)
            (work/'results.json').write_text(json.dumps(dict(host=platform.node(),rows=rows),indent=2)+'\n')


if __name__=='__main__':
    p=argparse.ArgumentParser(description=__doc__)
    for name in ('fixtures','runtime','work'): p.add_argument(name,type=Path)
    p.add_argument('--publication', action='store_true')
    p.add_argument('--append', action='store_true')
    p.add_argument('--slot', action='store_true')
    args=p.parse_args()
    measure(args.fixtures.resolve(),args.runtime.resolve(),args.work.resolve(),args.publication,args.append,args.slot)
