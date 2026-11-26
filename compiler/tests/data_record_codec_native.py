"""Compile the actual published record header and exercise its native codec adapters."""
import argparse
import json
from pathlib import Path
import subprocess
from tempfile import TemporaryDirectory

from app_namespace_native import ROOT, invoke, service


def run(work, runtime, quick):
    app, out = work / 'app', work / 'out'
    app.mkdir()
    (app / 'module.json').write_text(json.dumps({'entry': 'main.php'}))
    (app / 'main.php').write_text('$p Point = {x:2};$v Packet = {data:[1,2]};$f Flags = {data:[true]};return $p->x;')
    (app / 'records.php').write_text('data struct Point {public readonly int $x;public int $y=7;}'
                                      'data struct Packet {public vector<int> $data; public int $version=1;}'
                                      'data struct Flags {public vector<bool> $data;}')
    compiler = service(app, out)
    try:
        result = invoke(compiler)
        assert 'error' not in result, result
    finally:
        compiler.stdin.close()
        compiler.wait()
    includes = []
    for name in ('Point', 'Packet', 'Flags'):
        header, = [p for p in out.rglob('*.hpp') if f'struct {name} {{' in p.read_text()]
        includes.extend(['-include', header])
    exe = work / 'codec'
    subprocess.run(['clang++', '-std=c++20', '-stdlib=libc++', '-O0' if quick else '-O2', '-g0', '-I', ROOT / 'runtime/generated/public',
                    '-I', ROOT / 'runtime/src', *includes,
                    Path(__file__).with_suffix('.cpp'), runtime,
                    '-Wl,--wrap=_ZN4scpp12vector_int64C1ERKS0_,--wrap=_ZN4scpp12vector_int64aSERKS0_',
                    '-o', exe], check=True)
    subprocess.run([exe, '--quick'] if quick else [exe], check=True)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--runtime', type=Path, default=ROOT / 'build/clang/libscpp_runtime.a')
    # The optimized fixture took four seconds alone. CTest runs the same semantic
    # and copy checks without optimization; direct invocation also measures throughput.
    parser.add_argument('--quick', action='store_true')
    args = parser.parse_args()
    with TemporaryDirectory() as tmp:
        run(Path(tmp), args.runtime, args.quick)
