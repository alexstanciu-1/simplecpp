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
COMPARE_TOKENS = r"""
foreach (array_unique($cases) as $text) {
    foreach (flavor::cases() as $mode) {
        $file = new source_file('input', $text, $mode);
        try {
            $row = '';
            foreach ((new Tokenizer($file))->tokenize()->tokens as $t) {
                if (in_array($t->kind, [token_kind::newline, token_kind::indent, token_kind::dedent], true)) continue;
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
    oracle = subprocess.check_output(['php', '-r', ORACLE_PREAMBLE + PUNCTUATION_CASES + RADIX_CASES + COMPARE_TOKENS,
                                      ROOT/'compiler/boot.php'], text=True)
    rows = [json.loads(line) for line in oracle.splitlines()]
    expected = {(mode, text): row for mode, text, row in rows}
    assert expected['php', 'x ==== == ='] == '1,0,1|11,2,3|61,5,1|15,7,2|61,10,1|'
    assert expected['python', 'x ==== == ='] == '2:Unsupported punctuation in Python flavor'
    assert expected['python', 'x ++'] == '1,0,1|50,2,1|50,3,1|'
    main = [DRIVER]
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
