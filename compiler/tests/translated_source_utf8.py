"""Compare source UTF-8 error offsets with production tokenization in all four flavors."""
import json
from pathlib import Path
import shutil
import subprocess
import tempfile

from translated_preparation_recovery import ROOT, fresh_build, measure

# Every valid payload occurs inside a quoted token accepted by all four tokenizers.
PAYLOADS = ('', '00', '7f', 'c280', 'dfbf', 'e0a080', 'ed9fbf', 'ee8080', 'efbfbf',
            'f0908080', 'f48fbfbf', '80', 'bf', 'c080', 'c1bf', 'ff', 'f5808080',
            'c2', 'e0a0', 'f09080', 'df20', 'e18020', 'f1808020', 'e09fbf',
            'eda080', 'f08fbfbf', 'f4908080')
ORACLE = r"""
require $argv[1];
use scpp\compiler\{Tokenizer, source_file, flavor, diagnostic};
foreach (json_decode(stream_get_contents(STDIN)) as $hex) {
    foreach (flavor::cases() as $mode) {
        $file = new source_file('input', hex2bin($hex), $mode);
        try { (new Tokenizer($file))->tokenize(); echo "-1\n"; }
        catch (diagnostic $e) {
            if (!str_ends_with($e->getMessage(), 'Invalid UTF-8 source')) throw $e;
            echo $e->offset, "\n";
        }
    }
}
"""


def verify(work):
    inputs = [b'"' + bytes.fromhex(value) + b'"' for value in PAYLOADS]
    inputs += [bytes.fromhex(value) for value in ('c2', 'e0a0', 'f09080')]
    # Include an earlier lexical error, two invalid sequences, and a repaired source.
    inputs += [b'\x01"\xc3\xa9"\r\n\x80\xff', b'"\xc3\xa9"\r\n', b'']
    offsets = []
    for data in inputs:
        try:
            data.decode('utf-8')
            offsets.append(-1)
        except UnicodeDecodeError as error:
            offsets.append(error.start)
    observed = subprocess.check_output(['php', '-r', ORACLE, ROOT/'compiler/boot.php'],
                                       input=json.dumps([data.hex() for data in inputs]), text=True)
    assert list(map(int, observed.split())) == [n for n in offsets for _ in range(4)]
    app = work/'app'
    app.mkdir()
    (app/'module.json').write_text('{"general_api":true}')
    shutil.copyfile(ROOT/'compiler/examples/tokenizer/utf8.php.scpp', app/'utf8.php')
    main = []
    for data in inputs:
        value = ' . '.join(f'string_byte_from_int({byte})' for byte in data) or '""'
        main.append(f'echo source_utf8_error({value}), "\\n";')
    (app/'main.php').write_text('\n'.join([*main, 'return 8;']))
    generated = work/'generated'
    result = fresh_build(app, generated)
    assert 'error' not in result, result
    measure(generated, work/'native', ''.join(f'{offset}\n' for offset in offsets).encode())


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-source-utf8-') as temp:
        verify(Path(temp))
