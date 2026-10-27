"""Check focused includes and reject unsupported scalar/helper conversions."""
import json
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[1]


def main():
    receipt = json.loads((ROOT / 'dependencies/middleware.json').read_text())
    include = ROOT / 'dependencies' / receipt['package'] / 'include'
    cases = [
        ('types_only', '#include "scpp_compiler/types.hpp"\n',
         'uint64 value = 0; ++value; return value == 1 ? 0 : 1;', True),
        ('no_native_escape', '#include "scpp_compiler/types.hpp"\n',
         'uint64 value = 1; unsigned long long native = value; return native;', False),
        ('no_mixed_signedness', '#include "scpp_compiler/types.hpp"\n',
         'auto value = int64(1) + uint64(1); return 0;', False),
        ('no_integer_truthiness', '#include "scpp_compiler/types.hpp"\n',
         'if (uint64(1)) return 1; return 0;', False),
        ('no_scalar_empty', '#include "scpp_compiler/string.hpp"\n',
         'return empty(int32(0)) ? 1 : 0;', False),
        ('no_string_count', '#include "scpp_compiler/string.hpp"\n',
         'auto size = count(string("abc")); return 0;', False),
        ('no_vector_in_basic_types', '#include "scpp_compiler/types.hpp"\n',
         'vector_string values; return 0;', False),
        ('no_hash_in_basic_types', '#include "scpp_compiler/types.hpp"\n',
         'hash_string values; return 0;', False),
    ]
    with tempfile.TemporaryDirectory(prefix='scalar-boundary-') as folder:
        source = Path(folder) / 'probe.cpp'
        for name, header, body, success in cases:
            source.write_text(header + 'namespace scpp::compiler { int probe() {' + body + '} }\n')
            result = subprocess.run([shutil.which('clang++'), '-std=c++23', '-fsyntax-only',
                                     '-I', str(include), str(source)], capture_output=True, text=True)
            assert (result.returncode == 0) == success, name + ': ' + result.stderr
        for header in ['types', 'string', 'textbuilder', 'filesystem', 'datetime', 'io', 'host']:
            source.write_text(f'#include "scpp_compiler/{header}.hpp"\nint main() {{ return 0; }}\n')
            result = subprocess.run([sys.executable, str(ROOT / 'tools/checked_compile.py'),
                                     shutil.which('clang++'), '-std=c++23', '-I', str(include),
                                     '-c', str(source), '-o', str(Path(folder) / 'probe.o')],
                                    capture_output=True, text=True)
            assert result.returncode == 0, header + ': ' + result.stderr
    print('Scalar admission and standalone template-free headers passed.')


if __name__ == '__main__':
    main()
