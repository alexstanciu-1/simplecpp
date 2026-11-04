"""The integer arithmetic ABI is standalone and does not expose implementation headers."""
from pathlib import Path
import re
import subprocess
import tempfile

root = Path(__file__).resolve().parents[1]
with tempfile.TemporaryDirectory(prefix='scpp-arithmetic-api-') as directory:
    source = Path(directory) / 'arithmetic.cpp'
    source.write_text('#include <scpp/arithmetic.hpp>\nint main() { return scpp::int64_divide(17, 2) == 8 && scpp::int64_remainder(-17, 2) == -1 ? 0 : 1; }\n')
    flags = ['clang++', '-std=c++23', '-stdlib=libc++', '-O2', '-g0', '-fuse-ld=mold',
             '-I', str(root / 'runtime/generated/public')]
    def run(*args):
        return subprocess.run([*flags, *map(str, args)], capture_output=True, text=True, timeout=60)
    preprocessed = run('-E', '-P', source)
    assert preprocessed.returncode == 0, preprocessed.stderr
    assert not re.search(r'\b(template|typename)\b', preprocessed.stdout)
    dependencies = run('-M', source)
    assert dependencies.returncode == 0, dependencies.stderr
    for forbidden in ['/stdexcept', '/type_traits', '/scalar_semantics', '/string']:
        assert forbidden not in dependencies.stdout, dependencies.stdout
    binary = Path(directory) / 'arithmetic'
    result = run(source, root / 'build/clang/libscpp_runtime.a', '-o', binary)
    assert result.returncode == 0, result.stderr
    assert subprocess.run([binary], timeout=5).returncode == 0
print('Arithmetic public API: isolated header, minimal include closure and runtime linkage passed')
