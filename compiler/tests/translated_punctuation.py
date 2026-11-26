"""Compare punctuation in the translated scanner with the production lexer in each flavor."""
import json
from pathlib import Path
import shutil
import subprocess
import tempfile

from translated_preparation_recovery import ROOT, fresh_build, measure

ORACLE_PREAMBLE = r"""
require $argv[1];
use scpp\compiler\{Tokenizer, source_file, flavor, diagnostic, token_kind};
"""
PUNCTUATION_CASES = r"""
$words = [];
foreach (flavor::cases() as $mode) {
    foreach (Tokenizer::punctuation_kinds($mode) as $kind) $words[$kind->label()] = true;
}
// Pair delimiters so layout errors do not obscure punctuation recognition.
$pairs = ['('=>'()', ')'=>'()', '['=>'[]', ']'=>'[]', '{'=>'{}', '}'=>'{}'];
$cases = [];
foreach (array_keys($words) as $word) $cases[] = 'x ' . ($pairs[$word] ?? $word);
array_push($cases, 'x ==== == =', 'x +++ ---', 'x ... .. .', 'x **= ** *', 'x ??= ?? ?',
    'x #[x]', "x //==\ny", "x #==\ny", 'x /*===*/ ==', 'x /*unfinished', 'x /*/',
    'x / //', 'x @', 'x === @', 'x ++', 'x /*é*/ ===', "x #é\n=");
"""
RADIX_CASES = r"""
array_push($cases, 'x "é" 0x1e + 0O17', 'x 0X1E+2', 'x 0x000fF..<0o7', 'x 0o000755',
    'x 0x', 'x 0O', 'x 0xG', 'x 0o8', 'x 0o78', 'x 0x1g', 'x 0x1_2', 'x 0o7.0', 'x 0b10');
"""
LAYOUT_CASES = r"""
array_push($cases, "", "\t# ignored\r\n", "a\r\n  b", "a\n  b\n    c\n  d\ne",
    "a\n  b\n    c", "a\n  b\n    c\nd", "a\n  b\n    c\n   d", "a\n  b\n    c\n  d",
    "a\n  # ignored\n\t\n  b # tail", "f(\n\t[\n {1:2}\n ]\n)\n", "a\r", "a\n \tb",
    "a([)]", "a(\n", "a]", "a\n  'é'\r\n  b\n", "a\n  b\n   # trailing comment");
"""
COMPARE_TOKENS = r"""
foreach (array_unique($cases) as $text) {
    foreach (flavor::cases() as $mode) {
        $file = new source_file('input', $text, $mode);
        try {
            $row = '';
            foreach ((new Tokenizer($file))->tokenize()->tokens as $t) {
                $row .= $t->kind->value . ',' . $t->offset . ',' . $t->length . '|';
            }
        } catch (diagnostic $e) {
            $row = $e->offset . ':' . substr(strrchr($e->getMessage(), ':'), 2);
        }
        echo json_encode([$mode->name, $text, $row], JSON_THROW_ON_ERROR), "\n";
    }
}
"""

DRIVER = '''
function show(string $text, Scan_Flavor $mode): void {
    try {
        $tokens = tokenize_source("input", $text, $mode);
        foreach ($tokens as $token) {
            if ($token->kind !== Token_Kind::End) {
                echo enum_value($token->kind), ",", $token->offset, ",", $token->length, "|";
            }
        }
    } catch (Translation_Diagnostic $error) {
        echo $error->offset, ":", $error->detail;
    }
    echo "\\n";
}
'''


LAYOUT_REPAIR = r"""
function repair_layout(): void {
    $mode = Scan_Flavor::python;
    $current = new Translation_Source("input", "a\n  b\n    c", $mode);
    $old = $current;
    try {
        $current = new Translation_Source("input", "a\n  b\n    c\n   d", $mode);
        throw Exception::make("Accepted unmatched dedent");
    } catch (Translation_Diagnostic $error) {
        if ($error->offset !== 12 || $error->detail !== "Unmatched Python dedent") throw $error;
    }
    if ($current !== $old) throw Exception::make("Replaced source on layout failure");
    $repair = "a\n  b\n    c\n  d";
    $current = new Translation_Source("input", $repair, $mode);
    $fresh = new Translation_Source("input", $repair, $mode);
    if (count($current->tokens) !== count($fresh->tokens)) throw Exception::make("Different repaired token count");
    foreach ($current->tokens as $index => $token) {
        $expected = $fresh->tokens[$index];
        if ($token->kind !== $expected->kind || $token->offset !== $expected->offset || $token->length !== $expected->length)
            throw Exception::make("Different repaired token span");
    }
}
"""


def scanner_project(work):
    app = work/'app'
    app.mkdir()
    (app/'module.json').write_text('{"general_api":true}')
    examples = ROOT/'compiler/examples'
    for name in ('frontend', 'function_ast'):
        shutil.copyfile(examples/f'translated_path/{name}.php.scpp', app/f'{name}.php')
    shutil.copyfile(examples/'tokenizer/utf8.php.scpp', app/'utf8.php')
    with (app/'punctuation.php').open('wb') as output:
        subprocess.run(['php', examples/'tokenizer/emit_punctuation.php'], stdout=output, check=True)
    return app


def verify(work):
    app = scanner_project(work)
    oracle = subprocess.check_output(['php', '-r', ORACLE_PREAMBLE + PUNCTUATION_CASES + RADIX_CASES + LAYOUT_CASES + COMPARE_TOKENS,
                                      ROOT/'compiler/boot.php'], text=True)
    rows = [json.loads(line) for line in oracle.splitlines()]
    expected = {(mode, text): row for mode, text, row in rows}
    assert expected['php', 'x ==== == ='] == '1,0,1|11,2,3|61,5,1|15,7,2|61,10,1|'
    assert expected['python', 'x ==== == ='] == '2:Unsupported punctuation in Python flavor'
    assert expected['python', 'x ++'] == '1,0,1|50,2,1|50,3,1|65,4,0|'
    assert expected['python', 'a\r\n  b'] == '1,0,1|65,1,2|66,5,0|1,5,1|65,6,0|67,6,0|'
    assert expected['python', 'a\n  b\n    c\n   d'] == '12:Unmatched Python dedent'
    main = [DRIVER, LAYOUT_REPAIR, 'repair_layout();']
    for mode, source, _ in rows:
        main.append(f'show({json.dumps(source, ensure_ascii=False)}, Scan_Flavor::{mode});')
    (app/'main.php').write_text('\n'.join([*main, 'return 8;']))
    generated = work/'generated'
    result = fresh_build(app, generated)
    assert 'error' not in result, result
    measure(generated, work/'native', ''.join(row + '\n' for _, _, row in rows).encode())
    rejected = sum(':' in row for _, _, row in rows)
    print(f'{len(rows)} comparisons passed, including {rejected} diagnostics')


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-punctuation-') as tmp:
        verify(Path(tmp))
