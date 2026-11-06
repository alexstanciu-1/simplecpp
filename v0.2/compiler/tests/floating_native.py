"""Native bit-exact float literal and checked conversion proofs, with Clang stage timings."""
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
    success(run(['php', ROOT / 'compiler/tests/floating.php', work / 'fixtures.json']))
    results = {'php_fixture_generation_ms': (time.perf_counter() - start) * 1000,
               'configuration': 'O2/g0, libc++, mold, Ninja -j8, no PCH/LTO; prebuilt runtime; fresh instrumented app objects',
               'builds': {}}
    fixtures = json.loads((work / 'fixtures.json').read_text())
    for flavor, fixture in fixtures.items():
        directory = work / flavor
        directory.mkdir(exist_ok=True)
        literals = directory / 'literals.cpp'
        checks = ''.join(f'\tif (std::bit_cast<std::uint64_t>(local_{i}) != 0x{bits}ULL) return 91;\n'
                         for i, bits in enumerate(fixture['bits']))
        # Instrument only the proof artifact; the compiler output remains unchanged.
        literals.write_text('#include <bit>\n#include <cstdint>\n' + fixture['literal_cpp'].replace(
            'int main()', 'int check_literals()').replace('\treturn ', checks + '\treturn '))
        cpp = directory / 'program.cpp'
        checks = ('\tif (check_literals() != 8) return 92;\n'
                  '\tif (local_0 != 9007199254740992.0 || local_1 != 1.0 || local_2 || !local_3) return 93;\n')
        cpp.write_text('int check_literals();\n' + fixture['cpp'].replace('\treturn ', checks + '\treturn '))
        command = write_build(directory, [cpp, literals])
        command[2] = '8'
        graph = directory / 'build.ninja'
        graph.write_text(graph.read_text().replace(' -MD ', ' -ftime-trace -MD '))
        success(run(['ninja', '-f', 'build.ninja', '-t', 'clean'], directory))
        start = time.perf_counter()
        build = success(run(command, directory))
        elapsed = (time.perf_counter() - start) * 1000
        (directory / 'build.log').write_text(build.stdout + build.stderr)
        result = run([directory / 'program'])
        assert result.returncode == 8 and result.stdout == '-81', result
        totals = {}
        for path in directory.glob('unit-*.json'):
            for event in json.loads(path.read_text())['traceEvents']:
                if event['name'].startswith('Total '):
                    key = event['name'][6:]
                    totals[key] = totals.get(key, 0) + event['dur'] / 1000
        links = [line.split('\t') for line in (directory / '.ninja_log').read_text().splitlines()[1:]
                 if line.split('\t')[3] == 'program']
        link = links[-1]
        assert '.debug_' not in success(run(['readelf', '-S', directory / 'program'])).stdout
        results['builds'][flavor] = {'native_ms': elapsed, 'frontend_ms': totals['Frontend'],
            'llvm_backend_ms': totals['Backend'], 'optimizer_ms': totals.get('Optimizer', 0),
            'codegen_ms': totals.get('CodeGenPasses', 0), 'link_command_ms': int(link[1]) - int(link[0]),
            'stdout': result.stdout, 'exit': result.returncode, 'literal_bits_verified': len(fixture['bits'])}
    expression = success(run(['php', '-r',
        'require $argv[1]; echo scpp\\compiler\\type_int64::instance()->lower_cpp_conversion('
        'scpp\\compiler\\value_conversion::floating_integer_checked, "next_value(input)");',
        ROOT / 'compiler/boot.php'])).stdout
    boolean = success(run(['php', '-r',
        'require $argv[1]; echo scpp\\compiler\\type_bool::instance()->lower_cpp_conversion('
        'scpp\\compiler\\value_conversion::floating_boolean, "next_value(input)");',
        ROOT / 'compiler/boot.php'])).stdout
    driver = work / 'boundary.cpp'
    driver.write_text('''#include <scpp/conversions.hpp>
#include <stdexcept>
#include <limits>
#include <initializer_list>
static int calls = 0;
static double next_value(double input) { ++calls; return input; }
int main() {
    __INT64_TYPE__ destination = 42;
    for (double input : {0x1p63, -0x1.0000000000001p63,
        std::numeric_limits<double>::infinity(), std::numeric_limits<double>::quiet_NaN()}) {
        calls = 0;
        try { destination = ''' + expression + '''; return 1; }
        catch (const std::out_of_range&) {}
        if (destination != 42 || calls != 1) return 2;
    }
    for (double input : {-0x1p63, 0x1.fffffffffffffp62, -8.75, 8.75, -0.0, 0x0.0000000000001p-1022}) {
        calls = 0;
        destination = ''' + expression + ''';
        if (destination != static_cast<__INT64_TYPE__>(input) || calls != 1) return 3;
    }
    for (double input : {-0.0, 0.0, 0x0.0000000000001p-1022,
        std::numeric_limits<double>::infinity(), std::numeric_limits<double>::quiet_NaN()}) {
        calls = 0;
        bool value = ''' + boolean + ''';
        if (value != (input != 0.0) || calls != 1) return 4;
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
    results['checked_boundary'] = 'nonfinite/range failures preserve destination; operand evaluated once; truncation, signed zero, subnormals and int64 bounds passed'
    (work / 'results.json').write_text(json.dumps(results, indent=2) + '\n')
    print(json.dumps(results, indent=2))


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    if args.output:
        prove(args.output.resolve())
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-floating-native-') as directory:
            prove(Path(directory))
