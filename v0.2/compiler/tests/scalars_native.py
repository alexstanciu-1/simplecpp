"""Native proof for generated core scalar initializers and checked conversion boundaries."""
import argparse
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import time

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'tools/compiler_panel'))
from native_build import write_build


def run(args, cwd=None):
    return subprocess.run(list(map(str, args)), cwd=cwd, capture_output=True, text=True,
                          env={**os.environ, 'XDEBUG_MODE': 'off'})


def success(result):
    assert result.returncode == 0, result.stdout + result.stderr
    return result


def prove(work):
    work.mkdir(parents=True, exist_ok=True)
    start = time.perf_counter()
    success(run(['php', ROOT / 'compiler/tests/scalars.php', work / 'fixtures.json']))
    php_ms = (time.perf_counter() - start) * 1000
    fixtures = json.loads((work / 'fixtures.json').read_text())
    results = {'php_fixture_generation_ms': php_ms, 'builds': {},
               'configuration': 'O2/g0, libc++, mold, Ninja -j8, no PCH/LTO; prebuilt runtime; instrumented fresh app objects'}
    for flavor, fixture in fixtures.items():
        directory = work / flavor
        directory.mkdir(exist_ok=True)
        source = directory / 'program.cpp'
        source.write_text(fixture['cpp'])
        command = write_build(directory, [source])
        command[2] = '8'
        graph = directory / 'build.ninja'
        graph.write_text(graph.read_text().replace(' -MD ', ' -ftime-trace -MD '))
        success(run(['ninja', '-f', 'build.ninja', '-t', 'clean'], directory))
        start = time.perf_counter()
        build = success(run(command, directory))
        elapsed = (time.perf_counter() - start) * 1000
        (directory / 'build.log').write_text(build.stdout + build.stderr)
        result = run([directory / 'program'])
        assert result.returncode == 8 and result.stdout == '0125581', result
        traces = json.loads((directory / 'unit-0.json').read_text())['traceEvents']
        totals = {e['name'][6:]: e['dur'] / 1000 for e in traces if e['name'].startswith('Total ')}
        log = (directory / '.ninja_log').read_text().splitlines()[1:]
        link = [line.split('\t') for line in log if line.split('\t')[3] == 'program'][-1]
        assert '.debug_' not in success(run(['readelf', '-S', directory / 'program'])).stdout
        results['builds'][flavor] = {'native_ms': elapsed, 'frontend_ms': totals['Frontend'],
                                    'llvm_backend_ms': totals['Backend'], 'optimizer_ms': totals.get('Optimizer', 0),
                                    'codegen_ms': totals.get('CodeGenPasses', 0),
                                    'link_command_ms': int(link[1]) - int(link[0]),
                                    'exit': result.returncode, 'stdout': result.stdout}
    # Exercise the exact generated checked expression with unknown native inputs and side effects.
    expression = success(run(['php', '-r',
        'require $argv[1]; echo scpp\\compiler\\type_byte::instance()->lower_cpp_conversion('
        'scpp\\compiler\\value_conversion::integer_checked, "next_value(input)");',
        ROOT / 'compiler/boot.php'])).stdout
    driver = work / 'boundary.cpp'
    driver.write_text('''#include <scpp/integers.hpp>
#include <stdexcept>
#include <initializer_list>
static int calls = 0;
static __INT64_TYPE__ next_value(__INT64_TYPE__ input) { ++calls; return input; }
int main() {
    __UINT8_TYPE__ destination = 42;
    for (__INT64_TYPE__ input : {-1LL, 256LL}) {
        calls = 0;
        try { destination = ''' + expression + '''; return 1; }
        catch (const std::out_of_range&) {}
        if (destination != 42 || calls != 1) return 2;
    }
    for (__INT64_TYPE__ input : {0LL, 255LL}) {
        calls = 0;
        destination = ''' + expression + ''';
        if (destination != input || calls != 1) return 3;
    }
    return 0;
}
''')
    directory = work / 'boundary'
    directory.mkdir(exist_ok=True)
    command = write_build(directory, [driver])
    command[2] = '8'
    success(run(command, directory))
    success(run([directory / 'program']))
    results['checked_boundary'] = 'range failure preserves destination; operand evaluated once; 0 and 255 pass'
    (work / 'results.json').write_text(json.dumps(results, indent=2) + '\n')
    print(json.dumps(results, indent=2))


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    if args.output:
        prove(args.output.resolve())
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-scalars-native-') as directory:
            prove(Path(directory))
