"""Compare the wire representation a PHP client sees after one JSON decode and encode."""
from pathlib import Path
import subprocess
import tempfile

from check_apps import build

cases = {
    'empty object': b'{}', 'empty array': b'[]', 'nested empty shape': b'{"a":[{},[]]}',
    'zero key': b'{"0":"a"}', 'list-shaped object': b'{"0":false,"1":null}',
    'noncontiguous numeric keys': b'{"0":"a","2":"c"}',
    'zero-padded key': b'{"01":"same?"}', 'reversed keys': b'{"1":1,"0":0}',
    'boxed key upper range': b'{"2147483648":"id"}', 'wider key': b'{"4294967296":"id"}',
    'quoted integer id': b'"9223372036854775809"', 'unquoted large id': b'9223372036854775809',
    'negative zero': b'-0', 'exponent': b'1e2', 'fraction': b'1.000',
    'unescaped invalid UTF-8': b'"\xff"', 'unpaired surrogate': br'"\uD800"',
    'escaped Unicode': br'"\u00e9"', 'NUL in string': br'"A\u0000B"',
    'duplicate name after decode': br'{"a":1,"\u0061":2}',
}

# File input preserves raw bytes even for cases which are invalid Unicode.
entry = '''
$args=cli_arguments();
$mode=$args[1];
$source string=fs_get($args[2]);
if ($mode === "mixed") {
    $decoded=json_decode_mixed($source);
    if (!isset($decoded)) { echo "reject"; return 0; }
    $m mixed=$decoded;
    $out=json_encode($m);
    if (!isset($out)) { echo "encode error"; return 0; }
    $wire string=$out; echo $wire;
} else if ($mode === "dynamic") {
    $decoded=json_decode($source);
    if (!isset($decoded)) { echo "reject"; return 0; }
    $d dynamic=$decoded;
    $out=json_encode($d);
    if (!isset($out)) { echo "encode error"; return 0; }
    $wire string=$out; echo $wire;
} else {
    $diag ?json_parse_error=null;
    $result=json_document_parse($source,$diag);
    if (!isset($result)) { $e json_parse_error=$diag; echo $e->category,"@", $e->byte_offset; return 0; }
    $doc json_document=$result;
    $node json_node=json_document_root($doc);
    $kind string=json_node_kind($node);
    echo $kind;
    if ($kind === "number") { $token string=json_node_number($node); echo " ",$token; }
    if ($kind === "object") {
        $size int=json_node_size($node);
        for ($i=0;$i<$size;$i+=1) { $name string=json_node_key($node,$i); echo " ",$name; }
    }
}
return 0;
'''

control = '''<?php
try {
    $value = json_decode(file_get_contents($argv[2]), $argv[1] === 'assoc', flags: JSON_THROW_ON_ERROR);
    echo json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
} catch (JsonException $e) { echo "reject"; }
'''


def observe(cmd):
    result = subprocess.run(cmd, check=True, capture_output=True, timeout=5)
    if result.stderr:
        raise AssertionError(result.stderr)
    return result.stdout


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='json-wire-') as directory:
        work = Path(directory)
        app = work/'app'
        app.mkdir()
        (app/'module.json').write_text('{"general_api":true,"dependencies":{"runtime":{"modules":["filesystem","json"]}}}')
        (app/'main.phs').write_text(entry)
        exe = build(app, work)
        php = work/'control.php'
        php.write_text(control)
        data = work/'wire-input'
        print('case\tSimple C++ boxed (mixed and dynamic)\tdocument observation\tPHP object mode\tPHP associative')
        for label, body in cases.items():
            data.write_bytes(body)
            mixed, dynamic, document = [observe([exe, mode, data]) for mode in ('mixed', 'dynamic', 'document')]
            if mixed != dynamic:
                raise AssertionError((label, mixed, dynamic))
            stock = [observe(['php', '-n', php, mode, data]) for mode in ('object', 'assoc')]
            print(label, *(repr(value) for value in [mixed, document, *stock]), sep='\t')
