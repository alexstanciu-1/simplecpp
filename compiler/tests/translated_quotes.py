"""Compare raw quoted tokens and retry a failed source-snapshot replacement in each flavor."""
import json
from pathlib import Path
import subprocess
import sys
import tempfile

from translated_punctuation import COMPARE_TOKENS, DRIVER, ORACLE_PREAMBLE, scanner_project
from translated_vector_recipe import ROOT, build, fresh


REPAIR = '''
function repair(Scan_Flavor $mode): void {
    $current = new Translation_Source("input", "x 'old'", $mode);
    $old = $current;
    try {
        $current = new Translation_Source("input", "x 'broken", $mode);
    } catch (Translation_Diagnostic $error) {
        if ($error->offset !== 2 || $error->detail !== "Unterminated quoted string") throw $error;
    }
    if ($current !== $old || $current->text !== "x 'old'") throw Exception::make("Lost published source");
    $text = "x 'café'";
    $current = new Translation_Source("input", $text, $mode);
    $fresh = new Translation_Source("input", $text, $mode);
    if (count($current->tokens) !== count($fresh->tokens)) throw Exception::make("Different token count");
    foreach ($current->tokens as $i => $token) {
        $expected = $fresh->tokens[$i];
        if ($token->kind !== $expected->kind || $token->offset !== $expected->offset || $token->length !== $expected->length)
            throw Exception::make("Different repaired token");
    }
    if ($old->tokens[1]->length !== 5 || $current->tokens[1]->length !== 7) throw Exception::make("Stale token span");
}
'''


def verify(work, cxx, linker):
    cases = ['x "café" + z', "x '' \"\"", 'x "a // b # c /*" + \'other\'',
             """x '"é"' "'ø'" """, 'x "a\nb"', "x 'end", 'x "end', 'x "end\\',
             'x "a\x00b"', 'x "é" "tail', 'x "🌍" + z', 'x f"q"', 'x f"q',
             'x `q`', 'x `q', 'x "good" @']
    cases.extend(['x "a' + ch + 'b"' for ch in ('\r', '\\\n', '\r\n')])
    cases.append('x "a\nb')
    for quote in ("'", '"'):
        for n in range(1, 5):
            prefix = 'x ' + quote + 'a' + '\\' * n + quote
            cases.extend([prefix, prefix + 'b' + quote])
    oracle = ORACLE_PREAMBLE + '$cases = json_decode(stream_get_contents(STDIN));' + COMPARE_TOKENS
    rows = [json.loads(line) for line in subprocess.check_output(
        ['php', '-r', oracle, ROOT/'compiler/boot.php'], input=json.dumps(cases), text=True).splitlines()]
    app = scanner_project(work)
    main = [DRIVER, REPAIR]
    for mode, source, expected in rows:
        if source == 'x "café" + z':
            assert '6,2,7|' in expected, expected
        if source == "x 'end":
            assert expected == '2:Unterminated quoted string', expected
        main.append(f'show(hex2bin("{source.encode().hex()}"), Scan_Flavor::{mode});')
    for mode in ('php', 'cpp', 'js', 'python'):
        main.append(f'repair(Scan_Flavor::{mode});')
    (app/'main.phs').write_text('\n'.join([*main, 'return 8;']))
    generated = work/'generated'
    manifest = fresh(app, generated)
    exe = work/'probe'
    build(generated, exe, cxx, linker, manifest['link_inputs'])
    result = subprocess.run([exe], capture_output=True, text=True)
    assert result.returncode == 8, result.stderr
    for (mode, source, expected), actual in zip(rows, result.stdout.splitlines(), strict=True):
        assert actual == expected, (mode, source, expected, actual)
    print(f'{len(rows)} production comparisons and four repaired snapshots passed')


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-quoted-tokens-') as tmp:
        verify(Path(tmp), *sys.argv[1:])
