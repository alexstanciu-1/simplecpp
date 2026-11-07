"""Native string byte preservation and copy ownership proofs, with Clang stage timings."""
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


def prove(work, fixture_script="strings.php", check_copy=True):
    work.mkdir(parents=True, exist_ok=True)
    start = time.perf_counter()
    success(run(['php', ROOT / 'compiler/tests' / fixture_script, work / 'fixtures.json']))
    results = {'php_fixture_generation_ms': (time.perf_counter() - start) * 1000,
               'configuration': 'O2/g0, libc++, mold, Ninja -j8, no PCH/LTO; prebuilt runtime; fresh instrumented app objects',
               'builds': {}}
    fixtures = json.loads((work / 'fixtures.json').read_text())
    for flavor, fixture in fixtures.items():
        directory = work / flavor
        directory.mkdir(exist_ok=True)
        if 'files' in fixture:
            units = []
            for path, text in fixture['files'].items():
                artifact = directory / path
                artifact.parent.mkdir(parents=True, exist_ok=True)
                artifact.write_text(text)
                if artifact.suffix == '.cpp':
                    units.append(artifact)
        else:
            cpp = directory / 'program.cpp'
            # Only the proof artifact exercises mutation: source-level methods remain deferred.
            checks = ('\tlocal_0.clear();\n'
                      '\tif (local_0.size() != 0 || local_1.size() != 13) return 91;\n'
                      '\tif (local_1.data()[6] != 0 || local_1.data()[12] != \'\\n\') return 92;\n')
            source = fixture['cpp'].replace('\treturn ', checks + '\treturn ') if check_copy else fixture['cpp']
            if fixture.get('catch_out_of_range'):
                # Test-only exception observation; this does not enable source try/catch syntax.
                source = '#include <stdexcept>\n' + source.replace('int main()', 'int tested_entry()')
                source += '\nint main() { try { tested_entry(); } catch (const std::out_of_range&) { return 8; } return 90; }\n'
            if fixture.get('catch_domain_error'):
                source = '#include <stdexcept>\n#include <string_view>\n' + source.replace('int main()', 'int tested_entry()')
                message = json.dumps(fixture['catch_domain_error'])
                source += ('\nint main() { try { tested_entry(); } catch (const std::domain_error& e) { '
                           'return std::string_view(e.what()) == ' + message + ' ? 8 : 91; } return 90; }\n')
            if fixture.get('catch_runtime_error'):
                source = '#include <stdexcept>\n#include <string_view>\n' + source.replace('int main()', 'int tested_entry()')
                message = json.dumps(fixture['catch_runtime_error'])
                source += ('\nint main() { try { tested_entry(); } catch (const std::runtime_error& e) { '
                           'return std::string_view(e.what()) == ' + message + ' ? 8 : 91; } return 90; }\n')
            cpp.write_text(source)
            units = [cpp]
            for index, extra in enumerate(fixture.get('extra_cpp', [])):
                unit = directory / f'extra-{index}.cpp'
                unit.write_text(extra)
                units.append(unit)
        command = write_build(directory, units)
        command[2] = '8'
        graph = directory / 'build.ninja'
        graph.write_text(graph.read_text().replace(' -MD ', ' -ftime-trace -MD '))
        success(run(['ninja', '-f', 'build.ninja', '-t', 'clean'], directory))
        start = time.perf_counter()
        build = success(run(command, directory))
        elapsed = (time.perf_counter() - start) * 1000
        (directory / 'build.log').write_text(build.stdout + build.stderr)
        result = run([directory / 'program'])
        assert result.returncode == 8 and result.stdout.encode().hex() == fixture['stdout_hex'], result
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
            'stdout_hex': result.stdout.encode().hex(), 'exit': result.returncode, 'copy_independence': check_copy}
    (work / 'results.json').write_text(json.dumps(results, indent=2) + '\n')
    print(json.dumps(results, indent=2))


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    parser.add_argument('--fixture', choices=['strings.php', 'strlen.php'], default='strings.php')
    args = parser.parse_args()
    if args.output:
        prove(args.output.resolve(), args.fixture, check_copy=args.fixture == 'strings.php')
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-strings-native-') as directory:
            prove(Path(directory), args.fixture, check_copy=args.fixture == 'strings.php')
