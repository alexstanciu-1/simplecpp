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
                         'ordered_call_peers', 'sole_checked_argument', 'conversion_order', 'ordered_final_conversion',
                         'final_owner_release', 'ordered_literal_positions', 'ordered_reference_alias', 'ordered_late_reference',
                         'ref_owner_release', 'static_arguments', 'static_conversion_failure',
                         'inline_constructor_direct', 'inline_constructor_ordered', 'inline_constructor_failure',
                         'shared_constructor_direct', 'shared_constructor_ordered', 'shared_constructor_failure',
                         'inline_method_arguments', 'inline_method_failure', 'shared_replace', 'shared_field_replace',
                         'shared_empty', 'shared_field_empty', 'shared_checked', 'shared_field_checked',
                         'temporary_lifetime', 'temporary_literals', 'temporary_receiver_failure', 'temporary_argument_failure',
                         'local_call_addition', 'local_call_alias', 'local_call_failure',
                         'constant_compounds', 'mutating_compound_cast', 'checked_compound_cast',
                         'runtime_repeat_direct', 'runtime_repeat_mutation', 'runtime_repeat_failure', 'runtime_repeat_operand_failure',
                         'runtime_ordered_first_failure', 'runtime_ordered_final_failure', 'runtime_ordered_owner',
                         'runtime_pad_literal', 'runtime_pad_failure', 'runtime_pad_bytes', 'runtime_pad_first_failure',
                         'runtime_pad_final_failure', 'runtime_pad_nested', 'runtime_window_owner', 'runtime_window', 'runtime_window_scalar',
                         'runtime_window_first_failure', 'runtime_window_last_failure', *(['runtime_format_literals'] if flavor == 'php' else [])):
                fixture = fixtures[f'{flavor}_{case}']
                source = fixture['cpp']
                objects = []
                exception = next((kind for kind in ('domain_error', 'out_of_range', 'runtime_error', 'length_error') if 'catch_' + kind in fixture), None)
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
