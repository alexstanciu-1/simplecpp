"""The integer arithmetic ABI is standalone and does not expose implementation headers."""
from pathlib import Path
import random
import re
import struct
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
    source.write_text("""
#include <scpp/arithmetic.hpp>
#include <bit>
#include <iostream>
#include <stdexcept>
int main() {
    int64_t a, b;
    while (std::cin >> a >> b) {
        std::cout << std::bit_cast<uint64_t>(scpp::int64_true_divide(a, b)) << ' ';
        try {
            std::cout << scpp::int64_floor_divide(a, b);
        } catch (const std::overflow_error& error) {
            std::cout << error.what();
        }
        std::cout << ' ' << scpp::int64_floor_remainder(a, b) << '\\n';
    }
}
""")
    # Instrument the owning runtime implementation so invalid native operations fail the differential test.
    result = run('-Wall', '-Wextra', '-Werror', '-fsanitize=undefined', '-fno-sanitize-recover=all',
                 source, root / 'runtime/src/numeric/arithmetic.cpp', '-o', binary)
    assert result.returncode == 0, result.stderr
    minimum, maximum = -(1 << 63), (1 << 63) - 1
    boundary = [minimum, minimum + 1, -(1 << 53) - 1, -3, -1, 0, 1, 3, (1 << 53) + 1, maximum]
    pairs = [(a, b) for a in boundary for b in boundary if b]
    rng = random.Random(0)
    for _ in range(4096):
        # Vary both magnitudes. Uniform int64 samples exercise ratios mostly near one.
        a, b = (rng.randrange(-(1 << size), 1 << size) for size in (rng.randrange(64), rng.randrange(64)))
        if b:
            pairs.append((a, b))
    result = subprocess.run([binary], input=''.join(f'{a} {b}\n' for a, b in pairs),
                            capture_output=True, text=True, timeout=10)
    assert result.returncode == 0, result.stderr
    rows = result.stdout.splitlines()
    assert len(rows) == len(pairs)
    for (a, b), row in zip(pairs, rows):
        bits, quotient, remainder = row.split()
        expected_quotient = 'division_overflow' if (a, b) == (minimum, -1) else str(a // b)
        assert (int(bits), quotient, int(remainder)) == (
            int.from_bytes(struct.pack('>d', a / b), 'big'), expected_quotient, a % b
        ), (a, b, row)

print('Arithmetic public API: isolated header, minimal include closure and runtime linkage passed')
