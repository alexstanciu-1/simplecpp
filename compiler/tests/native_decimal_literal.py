"""Compare decimal preparation with the PHP compiler and independent halfway expectations."""
from pathlib import Path
import json
import struct
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[2]
ORACLE = r'''
require $argv[1];
use scpp\compiler\{Source_Frontend, source_file, flavor, entry_target, type_float64, diagnostic};
foreach (json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR) as $text) {
    $source = new source_file('decimal.php', '$x float = ' . $text . '; return 0;', flavor::php);
    try {
        $entry = (new Source_Frontend())->prepare($source, new entry_target(31));
        echo type_float64::instance()->cpp_constant(array_values($entry->body()->locals)[0]->constant()), "\n";
    } catch (diagnostic $error) {
        if (!str_contains($error->getMessage(), 'Floating literal is outside float64 finite range')) throw $error;
        echo "overflow\n";
    }
}
'''


def cases():
    # Exactly halfway between zero and the least subnormal, then one last-place decimal unit either side.
    half = 5**1075
    overflow = 2**1024 - 2**970
    positive = [
        ('0.0', 0), ('1e-400', 0), ('5e-324', 1), ('.5', 0x3fe0000000000000),
        ('10.', 0x4024000000000000), ('08E+0', 0x4020000000000000),
        ('1.00000000000000011102230246251565404236316680908203125', 0x3ff0000000000000),
        ('1.00000000000000033306690738754696212708950042724609375', 0x3ff0000000000002),
        ('2.2250738585072014e-308', 0x0010000000000000),
        ('2.225073858507201e-308', 0x000fffffffffffff),
        (f'{half-1}e-1075', 0), (f'{half}e-1075', 0), (f'{half+1}e-1075', 1),
        (f'{overflow-1}.0', 0x7fefffffffffffff), (f'{overflow}.0', None), (f'{overflow+1}.0', None),
        ('0e9999999', 0), ('1e9999999', None), ('1e-9999999', 0),
    ]
    for text, bits in positive:
        yield text, bits
        yield '-' + text, bits | (1 << 63) if bits is not None else None
    yield '+.5', 0x3fe0000000000000


def verify(exe, php):
    samples = list(cases())
    expected = subprocess.check_output([php, '-r', ORACLE, ROOT/'compiler/boot.php'],
                                       input=json.dumps([text for text, _ in samples]), text=True).splitlines()
    for (text, bits), reference in zip(samples, expected, strict=True):
        run = subprocess.run([exe, 'decimal-literal', text], text=True, capture_output=True, timeout=5)
        if bits is None:
            assert reference == 'overflow' and run.returncode and not run.stdout, (text, run, reference)
            assert 'Floating literal is outside float64 finite range' in run.stderr, run
        else:
            assert run.returncode == 0 and run.stdout == reference+'\n', (text, run, reference)
            assert struct.pack('>d', float.fromhex(reference)) == bits.to_bytes(8, 'big'), (text, reference)
    for invalid in ['', '1', ' 1.0', '1.0 ', '1e+', '.', '+.', '1.2x', 'nan', 'inf', '0x1p2', '1,5', '1_0.0']:
        run = subprocess.run([exe, 'decimal-literal', invalid], text=True, capture_output=True, timeout=5)
        assert run.returncode and not run.stdout and 'Expected decimal floating literal' in run.stderr, run
    print(f'{len(samples)} decimal spellings matched PHP and the expected binary64 boundary results.')


if __name__ == '__main__':
    verify(*sys.argv[1:])
