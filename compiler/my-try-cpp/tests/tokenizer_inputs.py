"""Actual scanner failures, binary bytes, longest-match boundaries and 24-bit length limits."""
import json
from pathlib import Path
import subprocess
import sys
import tempfile

with tempfile.TemporaryDirectory(prefix='tokenizer-inputs-') as temporary:
    root = Path(temporary)
    source = root / 'source'
    source.mkdir()
    catalogs = root / 'catalogs'
    catalogs.mkdir()
    (catalogs / 'core.json').write_text(json.dumps(dict(version=1, module='core', dependencies=[], functions=[], cpp=[])))
    command = [sys.argv[1], '--catalogs', str(catalogs), '--module', 'core', '--source', str(source), '--jobs', '8']
    for value in (b'$', b'$0', b'12a', b'1.e+', b'1..', b'0xff', b'0b11', b'1_000', b'\0', b'\xc3\xa9',
                  b"'", b"'abc\\", b'"abc', b'/* unfinished', b'@', b'`'):
        (source / 'bad.phs').write_bytes(b'  ' + value)
        result = subprocess.run(command, capture_output=True, text=True)
        assert result.returncode != 0 and 'bad.phs:' in result.stderr and 'byte 2' in result.stderr, (value, result.stderr)
    valid = b"$a=007; .5;10.;1e-3; #[Override] \"x\0y\";1</*gap*/<2;vector<vector<int>>;"
    (source / 'bad.phs').write_bytes(valid)
    result = subprocess.run(command, capture_output=True, text=True, check=True)
    assert json.loads(result.stdout)['tokens'] > 0
    # Include quote delimiters in the exact length boundary; lexical scanning is exercised.
    for length, success in ((0xffffff, True), (0x1000000, False)):
        (source / 'bad.phs').write_bytes(b'"' + b'x' * (length - 2) + b'"')
        result = subprocess.run(command, capture_output=True, text=True)
        assert (result.returncode == 0) == success, result.stderr
        if success:
            assert json.loads(result.stdout)['tokens'] == 1
        else:
            assert '24-bit capacity' in result.stderr and 'byte 0' in result.stderr, result.stderr
print('Lexical rejection, binary quotes, exact 24-bit limit and overflow passed.')
