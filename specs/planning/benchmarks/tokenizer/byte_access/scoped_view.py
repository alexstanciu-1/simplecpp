"""Compare ordinary field indexing and the production scoped view with the same source copy."""
import argparse
import json
import os
import statistics
import subprocess
import sys
import time
from pathlib import Path

HERE = Path(__file__).resolve().parent
FLAVORS = ('cpp', 'php', 'js')


def prepare(root):
    sys.path.insert(0, str(HERE.parents[4] / 'compiler/tests'))
    from tokenizer_scanner_native import prepare as scanner
    from app_namespace_native import service, invoke
    from extends_native import source_edit
    scanner(root)
    for f in FLAVORS:
        path = root / f / ('scanner.' + ('phs' if f == 'php' else f))
        original = path.read_text()
        # An equivalent length expression bypasses the direct-initializer proof.
        length = next(line for line in original.splitlines() if 'strlen(' in line)
        source_edit(path, original.replace(length, length.replace(');', ') + 0;')))
        proc = service(path.parent, root / (f + '-ordinary'))
        try:
            row = invoke(proc)
            assert 'error' not in row, row
        finally:
            proc.stdin.close()
            proc.wait()
            source_edit(path, original)


def run(root, runtime, rounds, copy_root=None):
    def call(*args):
        return subprocess.check_output(list(map(str, args)))
    rows = []
    cc = 'clang++'
    flags = [cc, '-std=c++23', '-O2', '-g0', *['-I' + str(runtime / p) for p in
             ('generated/public', 'public', 'generated/private', 'src/numeric', 'src/sequence', 'src/hash')]]
    rt = root / (cc + '.o')
    call(*flags, '-c', runtime / 'generated/src/runtime.cpp', '-o', rt)
    for f in FLAVORS:
        variants = [('copy', copy_root / (f + '-out')), ('transfer', root / (f + '-out'))] if copy_root else [
            ('ordinary', root / (f + '-ordinary')), ('view', root / (f + '-out'))]
        for variant, out in variants:
            unit = out / json.loads((out / 'manifest.json').read_text())['sources'][f'{f}/scanner.{"phs" if f == "php" else f}']
            code = unit.read_text()
            assert ('.checked_bytes()' in code) == (variant != 'ordinary')
            assert '.tokens = std::move(' in code
            if copy_root:
                if variant == 'copy':
                    assert '.source = var_text_0;' in code
                    control = code
                else:
                    assert code == control.replace('.source = var_text_0;', '.source = std::move(var_text_0);')
            elif variant == 'ordinary':
                source_moves = '.source = std::move(' in code
            else:
                assert ('.source = std::move(' in code) == source_moves
            header = next(p for p in unit.parent.glob('*.hpp') if 'Scan scan_(' in p.read_text())
            build = root / (cc + f + variant)
            build.mkdir(exist_ok=True)
            (build / 'adapter.hpp').write_text(f'#include "{header.name}"\n#include <string>\n'
                'using Result=App::scpp_records::Scan;\n'
                'auto make_input(const std::string& s){return scpp::string(s.data(),long(s.size()));}\n'
                'auto run(const scpp::string& s,size_t n){return App::scan_(s,n);}')
            local = [*flags, '-I' + str(build), '-I' + str(unit.parent)]
            elapsed = []
            obj = build / 'scan.o'
            for _ in range(3):
                start = time.perf_counter()
                call(*local, '-c', unit, '-o', obj)
                elapsed.append(1000 * (time.perf_counter() - start))
            call(*local, HERE.parent / 'driver.cpp', obj, rt, '-o', build / 'scan')
            if copy_root:
                call(*local, '-DCOUNT', HERE.parent / 'driver.cpp', HERE.parent / 'allocations.cpp', obj, rt, '-o', build / 'count')
            if variant != 'ordinary':
                check = HERE.parents[4] / 'compiler/tests/scoped_byte_view.cpp'
                call(*local, f'-DSCPP_SCANNER_HEADER="{header.name}"', check, obj, rt, '-o', build / 'lifetime')
                call(build / 'lifetime')
            for i in range(11):
                assert call(build / 'scan', 'dump', root/f/f'input{i}') == (root/f/f'expected{i}').read_bytes()
            edits = range(4, 8)
            assert call(build / 'scan', 'edits', root/f/'input3', *[root/f/f'input{i}' for i in edits]) == b''.join(
                (root/f/f'expected{i}').read_bytes() + b'|\n' for i in edits)
            rows.append(dict(compiler=cc, flavor=f, variant=variant, compile_ms=elapsed, samples={}))
        pair = rows[-2:]
        for i, name in enumerate(('identifiers', 'operators', 'mixed'), 8):
            path = root/f/f'input{i}'
            prior = root/f/'previous'
            prior.write_bytes(path.read_bytes() + b' previous')
            if copy_root:
                for row in pair:
                    count = root/(cc+f+row['variant'])/'count'
                    row.setdefault('allocations_and_bytes', {})[name] = list(map(int, call(count, 'count', path, prior).split()))
            for r in range(rounds):
                for row in (pair if r % 2 == 0 else pair[::-1]):
                    exe = root/(cc+f+row['variant'])/'scan'
                    seconds = statistics.median(map(float, call(exe, 'time', path, prior).split()))
                    row['samples'].setdefault(name, []).append(path.stat().st_size / (2**20 * seconds))
    print(json.dumps(dict(cpu=sorted(os.sched_getaffinity(0)), versions=[call(cc, '--version').decode().splitlines()[0]], rows=rows), indent=2))


if __name__ == '__main__':
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument('fixtures', type=Path)
    p.add_argument('--runtime', type=Path)
    p.add_argument('--copy-fixtures', type=Path, help='Compare against previously emitted copy-path scanner units')
    p.add_argument('--rounds', type=int, default=8)
    a = p.parse_args()
    if a.runtime:
        run(a.fixtures.resolve(), a.runtime.resolve(), a.rounds, a.copy_fixtures)
    else:
        prepare(a.fixtures.resolve())
