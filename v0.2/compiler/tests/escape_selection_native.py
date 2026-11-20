"""Compile the production escape selection as PHP++ and check both flavor modes."""
import json
from pathlib import Path
import subprocess
import tempfile

from collection_goal_native import revisions

HERE = Path(__file__).resolve().parent
BOOT = HERE.parent / 'boot.php'
MAIN = '''
function print_escape_table(bool $js): void {
    for ($code = 0; $code < 256; ++$code) {
        $decoded = Escape_Selection::decode(string_byte_from_int($code), $js);
        if ($decoded === null) {
            echo "A";
        } else {
            $byte string = $decoded;
            echo "P", $byte;
        }
    }
}
print_escape_table(false);
print_escape_table(true);
return 8;
'''


def source():
    # Read the real PHP method with its token range. Only the fixture's visibility and name differ.
    oracle = json.loads(subprocess.check_output(['php', '-r', r'''
require $argv[1];
$method = new ReflectionMethod(\scpp\compiler\String_Literals::class, 'decode_escape');
$lines = file($method->getFileName());
$source = implode('', array_slice($lines, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1));
$table = '';
foreach ([false, true] as $js) {
    for ($code = 0; $code < 256; ++$code) {
        $decoded = $method->invoke(null, chr($code), $js);
        $table .= $decoded === null ? 'A' : 'P' . $decoded;
    }
}
echo json_encode(['source' => $source, 'table' => bin2hex($table)], JSON_THROW_ON_ERROR);
''', BOOT]))
    assert bytes.fromhex(oracle['table']) == expected_table()
    result = oracle['source']
    prefix = 'private static function decode_escape('
    assert result.count(prefix) == 1
    return 'final class Escape_Selection {\n' + result.replace(prefix, 'public static function decode(') + '}\n'


def expected_table():
    by_byte = {
        ord('\\'): b'\\',
        ord('"'): b'"',
        ord('$'): b'$',
        ord('n'): b'\n',
        ord('r'): b'\r',
        ord('t'): b'\t',
        ord('0'): b'\0',
    }
    ordinary = b''.join(b'P' + by_byte[i] if i in by_byte else b'A' for i in range(256))
    by_byte[96] = bytes([96])
    js = b''.join(b'P' + by_byte[i] if i in by_byte else b'A' for i in range(256))
    return ordinary + js


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-escape-selection-') as directory:
        revisions(Path(directory), 'php', {'selection': source(), 'main': MAIN},
                  [(None, None, expected_table())])
