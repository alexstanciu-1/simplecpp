"""Compare the native-port spelling experiment with PHP and compile every resulting finite literal."""
from pathlib import Path
import subprocess
import sys
import tempfile

HERE = Path(__file__).resolve().parent
ROOT = HERE.parents[1]
ORACLE = r'''
require $argv[1];
$type = scpp\compiler\type_float64::instance();
while (($line = fgets(STDIN)) !== false) {
    $value = unpack('E', hex2bin(trim($line)))[1];
    try {
        echo $type->cpp_constant($value), "\n";
    } catch (LogicException $error) {
        if (is_finite($value)) throw $error;
        echo "nonfinite\n";
    }
}
'''


def verify(work, compiler, php, flags):
    fractions = [0, 1, 0x123456789abcd, (1 << 52) - 1]
    patterns = [(sign << 63) | (exponent << 52) | fraction
                for sign in (0, 1) for exponent in range(2048) for fraction in fractions]
    data = ''.join(f'{bits:016x}\n' for bits in patterns)
    expected = subprocess.check_output([php, '-r', ORACLE, ROOT/'compiler/boot.php'], input=data, text=True).splitlines()
    options = [compiler, '-std=c++23', '-Wall', '-Wextra', '-Werror', *flags]
    formatter = work/'formatter'
    subprocess.run([*options, HERE/'binary64_spelling.cpp', ROOT/'compiler/06_native/binary64_literal.cpp', '-o', formatter], check=True)
    subprocess.run([sys.executable, '-B', ROOT/'compiler/tests/native_decimal_literal.py', formatter, php], check=True)
    actual = subprocess.check_output([formatter], input=data, text=True).splitlines()
    rows = []
    for bits, reference, native in zip(patterns, expected, actual, strict=True):
        assert native == reference, (hex(bits), native, reference)
        if (bits >> 52) & 2047 == 2047:
            assert native == 'nonfinite', hex(bits)
        else:
            rows.append(f'    {{{native}, 0x{bits:016x}ULL}},\n')

    source = work/'roundtrip.cpp'
    source.write_text('#include <bit>\n#include <cstdint>\n'
                      'struct Row { double value; std::uint64_t bits; };\nconstexpr Row rows[] = {\n'
                      + ''.join(rows) + '};\nint main() {\n'
                      '    for (auto row : rows) if (std::bit_cast<std::uint64_t>(row.value) != row.bits) return 1;\n}\n')
    program = work/'roundtrip'
    subprocess.run([*options, source, '-o', program], check=True)
    subprocess.run([program], check=True)
    print(f'{Path(compiler).name}: {len(rows)} exact spellings and compiled bit round trips, '
          f'{len(patterns) - len(rows)} nonfinite rejections')


if __name__ == '__main__':
    with tempfile.TemporaryDirectory() as directory:
        verify(Path(directory), sys.argv[1], sys.argv[2], sys.argv[3:])
