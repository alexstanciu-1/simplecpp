"""Check the extracted decimal predicate against Python integers, never host PHP parsing."""
import json
from pathlib import Path
import random
import subprocess

ROOT = Path(__file__).resolve().parents[2]
cases = [(str(value), bits) for bits in range(12) for value in range(1, 2049)]
for bits in [16, 31, 32, 63, 64, 127, 128, 255, 256, 512]:
    cases.extend((str((1 << bits) + delta), bits) for delta in [-1, 0, 1])
rng = random.Random(1701)
for _ in range(120):
    bits = rng.randrange(1, 257)
    cases.append((str(max(1, rng.getrandbits(bits + 1))), bits))
cases += [('9' * 1000, 64), ('1', -1)]
program = r'''
require $argv[1];
while (($line = fgets(STDIN)) !== false) {
    [$digits, $bits] = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
    echo check_bodies\Decimal_Range::fits_positive($digits, $bits) ? "1\n" : "0\n";
}
'''
proc = subprocess.run(['php', '-r', program, str(ROOT / 'compiler/bootstrap.php')],
                      input=''.join(json.dumps(case) + '\n' for case in cases),
                      capture_output=True, text=True, timeout=120)
assert proc.returncode == 0, proc.stderr
observed = proc.stdout.splitlines()
assert len(observed) == len(cases)
for (digits, bits), actual in zip(cases, observed):
    expected = bits >= 0 and int(digits) < (1 << bits)
    assert actual == str(int(expected)), (digits, bits, actual, expected)
print(f'{len(cases)} decimal range cases matched the independent integer oracle.')
