"""Qualify generated scalar helper calls with installed C++ compilers."""
import argparse
import json
import os
from pathlib import Path
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[2]


def qualify(directory, compilers, flags):
    fixtures = {}
    for suite in ('arithmetic', 'direct_lowering', 'compounds'):
        inventory = directory / (suite + '.json')
        subprocess.run(['php', ROOT / 'compiler/tests' / (suite + '.php'), inventory],
                       env={**os.environ, 'XDEBUG_MODE': 'off'}, check=True)
        fixtures.update(json.loads(inventory.read_text()))

    for compiler in compilers:
        version = subprocess.check_output([compiler, '--version'], text=True).splitlines()[0]
        traces = set()
        for flavor in ('cpp', 'php', 'js'):
            for case in ('call_divide', 'call_remainder', 'call_shift_left', 'call_shift_right', 'call_power',
                         'call_float_divide', 'call_argument_failure', 'division_by_zero', 'modulo_by_zero',
                         'local_xor', 'checked_literal', 'forward_reference', 'int64_divide', 'literal_peers',
                         'ordered_call_peers', 'sole_checked_argument', 'conversion_order'):
                fixture = fixtures[f'{flavor}_{case}']
                source = fixture['cpp']
                objects = []
                exception = next((kind for kind in ('domain_error', 'out_of_range') if 'catch_' + kind in fixture), None)
                if exception:
                    source = source.replace('int main()', 'int tested_entry()')
                    error = json.dumps(fixture['catch_' + exception])
                    # Catch runtime exceptions with the archive's own C++ standard library.
                    observer = directory / 'observer.cpp'
                    observer.write_text('#include <stdexcept>\n#include <string_view>\nint tested_entry();\n'
                                        f'int main() {{ try {{ tested_entry(); }} catch (const std::{exception}& e) {{'
                                        f' return std::string_view(e.what()) == {error} ? 8 : 91; }} return 92; }}\n')
                    objects.append(directory / 'observer.o')
                    subprocess.run(['clang++', '-std=c++23', '-stdlib=libc++', '-c', observer, '-o', objects[0]], check=True)
                unit = directory / 'program.cpp'
                unit.write_text(source)
                binary = directory / 'program'
                obj = directory / 'program.o'
                subprocess.run([compiler, *flags, '-std=c++23', '-Wall', '-Wextra', '-Werror',
                                '-I', ROOT / 'runtime/generated/public', '-c', unit, '-o', obj], check=True)
                subprocess.run(['clang++', '-stdlib=libc++', obj, *objects,
                                ROOT / 'build/clang/libscpp_runtime.a', '-o', binary], check=True)
                result = subprocess.run([binary], capture_output=True)
                expected = fixture['stdout_hex']
                allowed = expected if isinstance(expected, list) else [expected]
                if result.returncode != 8 or result.stdout.hex() not in allowed:
                    raise RuntimeError((version, flavor, case, result))
                traces.add((case, result.stdout.decode()))
        print(version, flags, sorted(traces))


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--cxx', action='append')
    parser.add_argument('--flag', action='append')
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-argument-order-') as work:
        qualify(Path(work), args.cxx or ['clang++', 'g++'], args.flag or ['-O2'])
