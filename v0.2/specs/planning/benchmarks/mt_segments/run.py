"""Fresh compiler + optimized native build. Write raw paired samples to stdout."""
import json
import os
import re
import subprocess
import sys
import tempfile
from pathlib import Path
from time import perf_counter

HERE = Path(__file__).resolve().parent
ROOT = HERE.parents[3]
BUILD = ROOT / 'build/clang'


def run(args, **kwargs):
    return subprocess.run(args, text=True, check=True, **kwargs)


def measure(work):
    sources = ['entry.php', 'stock.php', 'price.php', 'work.php', 'launch.php']
    # A reachable source entry drives publication just like the application; the timed
    # harness supplies populated vectors to the same published functions.
    (work / 'entry.php').write_text('$s vector<Stock> = []; $a vector<int> = []; '
                                    '$r = reconcile_batch($s,$a,0,0,0); return $r[0];')
    for name in sources[1:]:
        (work / name).write_bytes((HERE / name).read_bytes())
    out = work / 'out'
    cmd = ['php', ROOT / 'compiler/main.php', 'program', work, 'entry.php',
           BUILD / 'native-layout/facts.json', out]
    for name in sources:
        cmd.extend(['php', name])
    start = perf_counter()
    run(cmd, stdout=subprocess.DEVNULL)
    php_ms = (perf_counter() - start) * 1000
    manifest = json.loads((out / 'manifest.json').read_text())
    headers = [out / path for path in manifest['headers']]
    record = next(p for p in headers if 'struct Stock {' in p.read_text())
    vector = next(p for p in headers if 'mt_data()' in p.read_text())
    native_name = re.search(r'class (scpp_vector_\w+) final', vector.read_text())[1]
    driver = work / 'driver.cpp'
    driver.write_text(f'#include "{record}"\n#include "{vector}"\nusing Rows = App::{native_name};\n'
                      + (HERE / 'measure.cpp').read_text())
    compiler = ['clang++', '-O3', '-std=c++23', '-stdlib=libc++', '-pthread',
                '-I' + str(ROOT / 'runtime/generated/public')]
    # Compile the committed runtime in the temporary build. Insert the benchmark
    # switch after its entire admission loop. The fresh branch still uses its normal threads.
    fresh = subprocess.check_output(
    ['git', 'show', '462113d00:v0.2/runtime/src/mt/segments.cpp'], cwd=ROOT, text=True)
    point = '    std::vector<std::exception_ptr> errors(count);'
    runtime = (ROOT / 'runtime/src/mt/segments.cpp').read_text().split(point)[0] + point + fresh.split(point)[1].replace(
        "jobs[i].result = jobs[i].run(jobs[i].inputs[0], jobs[i].inputs[1])", "jobs[i].execute()")
    assert runtime.count(point) == 1
    measured_runtime = work / 'segments.cpp'
    measured_runtime.write_text('#include "reuse.hpp"\n' + runtime.replace(point,
        '    if (bench::selected) { bench::selected->run(jobs, count); return; }\n' + point))
    compiler.extend(['-I', str(HERE)])
    objects = []
    totals = {'Total Frontend': 0, 'Total Backend': 0, 'Total Optimizer': 0, 'Total CodeGenPasses': 0}
    for unit in [driver, measured_runtime, *[out / p for p in manifest['translation_units'] if p != 'entry.cpp']]:
        obj = work / (unit.stem + '.o')
        run([*compiler, '-ftime-trace', '-c', unit, '-o', obj])
        objects.append(obj)
        for row in json.loads(obj.with_suffix('.json').read_text())['traceEvents']:
            if row['name'] in totals:
                totals[row['name']] += row['dur'] / 1000
    link = perf_counter()
    exe = work / 'measure'
    run([*compiler, *objects, BUILD / 'libscpp_runtime.a', '-o', exe])
    built = perf_counter()
    print(f'PHP {php_ms:.1f} ms, native {(built-start)*1000-php_ms:.1f} ms, link {(built-link)*1000:.1f} ms, '
          f'overall {(built-start)*1000:.1f} ms, cumulative Clang trace ms {totals}', file=sys.stderr)
    print('records,round,mode,us', flush=True)
    for n in [0, 1024, 16384, 65536, 524288]:
        run([exe, str(n)])
    helper = work / 'price.php'
    helper.write_text('global $rounding int = 1;\n' + helper.read_text().replace(' * $unit_cost;', ' * $unit_cost + $rounding;'))
    invalid = cmd.copy()
    invalid[6] = work / 'invalid'
    failure = subprocess.run(invalid, text=True, capture_output=True)
    assert failure.returncode and 'MT safety:' in failure.stderr, failure
    print(failure.stderr.replace(str(work) + '/', '').strip(), file=sys.stderr)


if __name__ == '__main__':
    os.environ['XDEBUG_MODE'] = 'off'
    with tempfile.TemporaryDirectory(prefix='mt-stock-') as directory:
        measure(Path(directory))
