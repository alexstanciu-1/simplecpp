"""Prepare the bounded scanner comparison locally; run bench on a single pinned CPU."""
import hashlib
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
MODES = ('ordinary', 'match')
PRIVATE = ('src/sequence', 'src/numeric')


def package(dest, cache_pointer=False):
    from fixed_match import prove, ROOT
    dest.mkdir()
    with tempfile.TemporaryDirectory() as temp:
        work = Path(temp)
        prove(work, cache_pointer)
        shutil.copy(work/'byte_match.hpp', dest)
        for flavor in FLAVORS:
            data = work/flavor
            out = work/(flavor+'-out')
            unit = out/json.loads((out/'manifest.json').read_text())['sources'][f'{flavor}/scanner.{"phs" if flavor == "php" else flavor}']
            folder = dest/flavor
            folder.mkdir()
            shutil.copy(unit, folder/'ordinary.cpp')
            for file in [*unit.parent.glob('*.hpp'), data/'match.cpp', data/'adapter.hpp',
                         *data.glob('input[0-9]*'), *data.glob('expected[0-9]*')]:
                shutil.copy(file, folder)
            plain = unit.read_text().splitlines()
            candidate = (folder/'match.cpp').read_text().splitlines()
            if cache_pointer:
                candidate.remove('\tconst char* const match_bytes = var_out_2.source.data();')
            assert candidate[0] == '#include "byte_match.hpp"' and len(candidate) == len(plain)+1
            delta, = [(a,b) for a,b in zip(plain, candidate[1:], strict=True) if a != b]
            assert 'scpp_byte_view.read' in delta[0] and 'bytes_match_at' in delta[1]
    shutil.copy(HERE.parent/'driver.cpp', dest)
    shutil.copy(__file__, dest)
    rt = ROOT/'runtime'
    src = 'generated/src/runtime.cpp'
    deps = subprocess.check_output(['clang++', '-std=c++23', '-MM', '-Igenerated/public',
                                    *['-I'+p for p in PRIVATE], src], cwd=rt, text=True)
    for name in deps.replace('\\\n', ' ').split(':', 1)[1].split():
        target = dest/name
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy(rt/name, target)


def run(*args):
    return subprocess.check_output(args)


def timed(*args):
    start = time.monotonic_ns()
    run(*args)
    return (time.monotonic_ns()-start)/1e6


def bench(folder):
    os.chdir(folder)
    cpus = sorted(os.sched_getaffinity(0))
    assert len(cpus) == 1
    flags = ['clang++', '-std=c++23', '-O2', '-g0', '-I.', '-Igenerated/public']
    results = {'cpu': cpus, 'clang': run('clang++','--version').decode().splitlines()[0], 'flags': flags,
               'pairs_seconds': {}, 'build_ms': {}, 'trace_ms': {}, 'sources_sha256': {}, 'bytes': {}}
    run(*flags, *['-I'+p for p in PRIVATE], '-ffunction-sections', '-fdata-sections', '-c',
        'generated/src/runtime.cpp', '-o', 'runtime.o')
    for flavor in FLAVORS:
        data = Path(flavor)
        opts = [*flags, '-I'+flavor]
        driver = data/'driver.o'
        run(*opts, '-c', 'driver.cpp', '-o', driver)
        for r in range(6):
            for mode in (MODES if r%2==0 else MODES[::-1]):
                exe = data/mode
                obj = exe.with_suffix('.o')
                src = exe.with_suffix('.cpp')
                key = flavor+'/'+mode
                compile_ms = timed(*opts, '-c', src, '-o', obj)
                link_ms = timed('clang++', '-Wl,--gc-sections', obj, driver, 'runtime.o', '-o', exe)
                if r:
                    results['build_ms'].setdefault(key, []).append([compile_ms,link_ms])
        for mode in MODES:
            key = flavor+'/'+mode
            exe = data/mode
            src = exe.with_suffix('.cpp')
            results['sources_sha256'][key] = hashlib.sha256(src.read_bytes()).hexdigest()
            trace = exe.with_suffix('.json')
            run(*opts, '-ftime-trace='+str(trace), '-c', src, '-o', data/'trace.o')
            results['trace_ms'][key] = {e['name']:e['dur']/1000 for e in json.loads(trace.read_text())['traceEvents']
                                        if e['name'] in ('Total Frontend','Total Backend')}
            for i in range(11):
                assert run(exe, 'dump', data/f'input{i}', data/'input10') == (data/f'expected{i}').read_bytes()
            assert run(exe, 'edits', data/'input3', *[data/f'input{i}' for i in range(4,8)]) == b''.join(
                (data/f'expected{i}').read_bytes()+b'|\n' for i in range(4,8))
        for i, role in enumerate(('identifiers', 'operators', 'mixed'), start=8):
            inp = data/f'input{i}'
            key = flavor+'/'+role
            results['bytes'][key] = inp.stat().st_size
            pairs = []
            for r in range(17):
                row = {}
                for mode in (MODES if r%2==0 else MODES[::-1]):
                    samples = list(map(float, run(data/mode, 'time', inp, inp).split()))
                    assert len(samples)==9 and min(samples)>0
                    row[mode] = statistics.median(samples)
                if r: pairs.append(row)
            results['pairs_seconds'][key] = pairs
    return results


if __name__ == '__main__':
    action, folder = sys.argv[1:]
    if action in ('prepare', 'prepare-cached'):
        package(Path(folder).resolve(), action == 'prepare-cached')
    elif action == 'bench':
        print(json.dumps(bench(folder), indent=2))
