"""Feed the published C++ helpers every Unicode break-test row, including slice boundaries."""
import hashlib
from pathlib import Path
import subprocess
import sys

corpus = Path(__file__).with_name('GraphemeBreakTest-14.0.0.txt').read_bytes()
assert hashlib.sha256(corpus).hexdigest() == 'ddc7d4d1f3838573b94fc5d83ff7217e63c47b22ae1cd40c5fe1a54efc15589b'
rows = []
for line in corpus.decode().splitlines():
    rule = line.partition('#')[0].strip()
    if rule:
        clusters = rule.strip('÷ ').split('÷')
        rows.append(' '.join(''.join(chr(int(cp, 16)) for cp in c.split('×')).encode().hex() for c in clusters))
assert len(rows) == 602
# Empty input, embedded NUL and a malformed suffix after valid multi-byte text.
rows.extend(['', '00 61 00', 'c3 a9 ff', '65 cc 81 c0 af', 'f0 9f', 'ed a0 80'])
# GB11 can join arbitrarily many pictographs; PCRE recursion limits must
# not split a long cluster or make slicing it fail.
rows.append(('👩' + '\u200d💻' * 4096).encode().hex() + ' 21')
subprocess.run([sys.argv[1]], input='\n'.join(rows) + '\n', text=True, check=True)
print('602 Unicode 14.0.0 conformance cases and 7 additional cases passed')
