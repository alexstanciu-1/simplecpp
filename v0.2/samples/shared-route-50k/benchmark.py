"""Compare two native lowerings of the same PHP-flavor route workload."""
import hashlib
import json
from pathlib import Path
import re
import shutil
import subprocess
import sys
import tempfile
import time

SAMPLE = Path(__file__).resolve().parent
sys.path.insert(0, str(SAMPLE.parents[1] / 'compiler/tests'))
from app_namespace_native import invoke, service, write_build


def direct_access(text):
    # Only rewrite the generated shared Route receiver. Inline Coordinate fields use a dot.
    member = re.compile(r'(\(var_(?:route|observer)_\d+\))->(?=position|distance|toll|stops)')
    method = ('inline scpp_records::Route* owner_Route::operator->() const {\n'
              '\tif (!ptr) ::scpp::throw_empty_owner();\n'
              '\treturn &ptr->value;\n}\n')
    # Preserve checked access on the direct path too. This fixture has only simple local receivers.
    def checked(match):
        ptr = match[1] + '.ptr'
        return f'({ptr} ? &{ptr}->value : (::scpp::throw_empty_owner(), nullptr))->'
    direct, sites = member.subn(checked, text.replace(method, '').replace('\tscpp_records::Route* operator->() const;\n', ''))
    if 'operator->()' in direct or member.search(direct):
        raise ValueError('Unexpected shared-access spelling; update the benchmark for this emitter')
    return direct, sites


def native_build(work, units, expected):
    work.mkdir()
    command = write_build(work, units, optimization='O2')
    command[2] = '8'
    graph = work / 'build.ninja'
    graph.write_text(graph.read_text().replace(' -MD ', ' -ftime-trace -MD '))
    start = time.perf_counter()
    subprocess.run(command, cwd=work, check=True, stdout=subprocess.DEVNULL)
    wall = (time.perf_counter() - start) * 1000
    exe = work / 'program'
    run = subprocess.run([exe], capture_output=True)
    if (run.returncode, run.stdout, run.stderr) != (expected['exit'], expected['stdout'].encode(), b''):
        raise RuntimeError(f'Unexpected result: {run}')
    section = work / 'text.bin'
    subprocess.run(['objcopy', '--dump-section', f'.text={section}', exe], check=True)
    phases = {'Total Frontend': 0, 'Total Backend': 0}
    traces = list(work.glob('unit-*.json'))
    assert len(traces) == len(units)
    for trace in traces:
        for event in json.loads(trace.read_text())['traceEvents']:
            if event['name'] in phases:
                phases[event['name']] += event['dur']
    link = next(line.split('\t') for line in (work / '.ninja_log').read_text().splitlines()[1:]
                if line.split('\t')[3] == 'program')
    return dict(wall_ms=wall, frontend_ms=phases['Total Frontend']/1000, llvm_ms=phases['Total Backend']/1000,
                link_ms=int(link[1])-int(link[0]), text_sha256=hashlib.sha256(section.read_bytes()).hexdigest())


def benchmark(root):
    app = root / 'app'
    app.mkdir()
    sources = list(SAMPLE.glob('*.phs'))
    for path in [SAMPLE / 'module.json', *sources]:
        shutil.copyfile(path, app / path.name)
    output = root / 'generated'
    with service(app, output) as compiler:
        emitted = invoke(compiler)
        compiler.stdin.close()
    if 'error' in emitted:
        raise RuntimeError(emitted['error'])
    units = [output / name for name in json.loads((output / 'manifest.json').read_text())['translation_units']]
    overload = {u: u.read_text() for u in units}
    direct = {}
    sites = 0
    for u, text in overload.items():
        direct[u], count = direct_access(text)
        sites += count
    assert sites > 0
    expected = json.loads((SAMPLE / 'expected.json').read_text())
    variants = {'direct': direct, 'overload': overload}
    rows = {key: [] for key in variants}
    for pair in range(11):
        hashes = set()
        for key in (list(variants) if pair % 2 == 0 else reversed(variants)):
            for path, text in variants[key].items():
                path.write_text(text)
            result = native_build(root / f'{pair}-{key}', units, expected)
            hashes.add(result['text_sha256'])
            if pair:
                rows[key].append(result)
        if len(hashes) != 1:
            raise RuntimeError(f'Machine code differs in pair {pair}')
    return dict(source_bytes=sum(p.stat().st_size for p in sources), source_files=len(sources),
                translation_units=len(units), shared_accesses=sites, php_ms=emitted['php_ms'],
                cpp_bytes={key: sum(len(s.encode()) for s in files.values()) for key, files in variants.items()},
                header_bytes=sum(p.stat().st_size for p in output.rglob('*.hpp')), samples=rows)


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-shared-route-') as directory:
        data = benchmark(Path(directory))
    print(json.dumps(data, indent=2))
