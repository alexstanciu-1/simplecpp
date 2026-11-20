"""Current Simple C++ lowering vs handwritten C++. prepare DIR locally, bench DIR on pinned dev002."""
import hashlib
import itertools
import json
import os
from pathlib import Path
import shutil
import statistics
import subprocess
import sys
import tempfile
import time

HERE = Path(__file__).resolve().parent
FLAVORS = ('cpp', 'php', 'js')
MODES = ('handwritten', 'ordinary', 'selected')
ORDERS = list(itertools.permutations(MODES))
PRIVATE = ['src/sequence', 'src/numeric']


def fingerprint(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def prepare_package(dest):
    root = HERE.parents[3]
    sys.path.insert(0, str(root/'compiler/tests'))
    from tokenizer_scanner_native import prepare
    dest.mkdir()
    rt = root/'runtime'
    deps = subprocess.check_output(['clang++', '-std=c++23', '-MM', '-Igenerated/public', *['-I'+p for p in PRIVATE],
                                    'generated/src/runtime.cpp'], cwd=rt, text=True)
    for name in deps.replace('\\\n', ' ').split(':', 1)[1].split():
        target = dest/name
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy(rt/name, target)
    for name in ('driver.cpp','baseline.cpp','baseline.hpp','current_native.py'):
        shutil.copy(HERE/name, dest)
    with tempfile.TemporaryDirectory() as temp:
        work = Path(temp)
        prepare(work)
        for f in FLAVORS:
            app = work/f
            folder = dest/f
            folder.mkdir()
            for src in [*app.glob('input[0-9]*'), *app.glob('expected[0-9]*')]:
                shutil.copy(src, folder)
            for mode in MODES:
                out = work/(f+'-'+mode)
                sub = folder/mode
                sub.mkdir()
                if mode == 'handwritten':
                    adapter = ('#include "baseline.hpp"\nusing Result=Scan;\n'
                               'auto make_input(const std::string& s){return s;}\n'
                               'auto run(const std::string& s,size_t hint){return scan(s,hint);}\n')
                else:
                    command = ['php', root/'compiler/main.php', 'project', app, root/'build/clang/native-layout/facts.json', out]
                    if mode == 'selected':
                        command.append('--optimize-function=scan')
                    subprocess.run(command, check=True, stdout=subprocess.DEVNULL)
                    unit = out/json.loads((out/'manifest.json').read_text())['sources'][f'{f}/scanner.{f}']
                    assert ('destination_' in unit.read_text()) == (mode=='selected')
                    shutil.copy(unit, sub/'scanner.cpp')
                    for h in unit.parent.glob('*.hpp'):
                        shutil.copy(h, sub)
                    header, = [h for h in sub.glob('*.hpp') if 'Scan scan_(' in h.read_text()]
                    adapter = (f'#include "{header.name}"\n#include <string>\nusing Result=App::scpp_records::Scan;\n'
                               'auto make_input(const std::string& s){return scpp::string{s.data(),long(s.size())};}\n'
                               'auto run(const scpp::string& s,size_t hint){return App::scan_(s,hint);}\n')
                (sub/'adapter.hpp').write_text(adapter)
    metadata = {'commit': subprocess.check_output(['git','rev-parse','HEAD'],cwd=root,text=True).strip(),
                'inputs_sha256': {str(p.relative_to(dest)): fingerprint(p) for p in sorted(dest.rglob('*')) if p.is_file()}}
    (dest/'package.json').write_text(json.dumps(metadata, indent=2)+'\n')


def run(*args):
    return subprocess.check_output(args)


def elapsed(*args):
    start=time.monotonic_ns()
    run(*args)
    return (time.monotonic_ns()-start)/1e6


def bench(dest):
    os.chdir(dest)
    affinity=sorted(os.sched_getaffinity(0))
    assert len(affinity)==1
    package=json.loads(Path('package.json').read_text())
    assert all(fingerprint(Path(k))==v for k,v in package['inputs_sha256'].items())
    flags=['clang++','-std=c++23','-O2','-g0','-I.','-Igenerated/public']
    result={'commit':package['commit'], 'package_sha256': fingerprint(Path('package.json')), 'cpu':affinity,
            'clang':run('clang++','--version').decode().splitlines()[0], 'flags':flags,
            'build_ms':{}, 'trace_ms':{}, 'bytes':{}, 'seconds':{}}
    run(*flags, *['-I'+p for p in PRIVATE], '-ffunction-sections','-fdata-sections','-c','generated/src/runtime.cpp','-o','runtime.o')
    for flavor in FLAVORS:
        folder=Path(flavor)
        commands={}
        for mode in MODES:
            sub=folder/mode
            opts=[*flags,'-I'+str(sub)]
            src=sub/'scanner.cpp'
            libs=['runtime.o']
            if mode=='handwritten':
                src='baseline.cpp'
                libs=[]
                if flavor!='php':
                    opts.append('-DEXTRA_WORD="'+('auto' if flavor=='cpp' else 'let')+'"')
            run(*opts,'-c','driver.cpp','-o',sub/'driver.o')
            commands[mode]=([*opts,'-c',src,'-o',sub/'scanner.o'],
                            ['clang++','-Wl,--gc-sections',sub/'scanner.o',sub/'driver.o',*libs,'-o',sub/'scan'])
        for i in range(7):
            for mode in ORDERS[i%6]:
                compile_cmd,link_cmd=commands[mode]
                times=[elapsed(*compile_cmd),elapsed(*link_cmd)]
                if i: result['build_ms'].setdefault(flavor+'/'+mode,[]).append(times)
        for mode in MODES:
            sub=folder/mode
            trace=sub/'trace.json'
            run(*commands[mode][0][:-2],'-ftime-trace='+str(trace),'-o',sub/'trace.o')
            result['trace_ms'][flavor+'/'+mode]={e['name']:e['dur']/1000 for e in json.loads(trace.read_text())['traceEvents']
                                                 if e['name'] in ('Total Frontend','Total Backend')}
            exe=sub/'scan'
            for i in range(11):
                for hint in ([],[folder/'input10']):
                    assert run(exe,'dump',folder/f'input{i}',*hint)==(folder/f'expected{i}').read_bytes()
            assert run(exe,'edits',folder/'input3',*[folder/f'input{i}' for i in range(4,8)])==b''.join(
                (folder/f'expected{i}').read_bytes()+b'|\n' for i in range(4,8))
        for i,role in enumerate(('identifiers','operators','mixed'),8):
            inp=folder/f'input{i}'
            key=flavor+'/'+role
            result['bytes'][key]=inp.stat().st_size
            rows=[]
            for r in range(13):
                row={}
                for mode in ORDERS[r%6]:
                    samples=list(map(float,run(folder/mode/'scan','time',inp,inp).split()))
                    assert len(samples)==9 and min(samples)>0
                    row[mode]=statistics.median(samples)
                if r: rows.append(row)
            result['seconds'][key]=rows
    return result


if __name__=='__main__':
    action,path=sys.argv[1:]
    if action=='prepare':
        prepare_package(Path(path).resolve())
    elif action=='bench':
        print(json.dumps(bench(path), indent=2))
