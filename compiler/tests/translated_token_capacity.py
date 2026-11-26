"""Compare the 24-bit span boundary, failed replacement and repair against production."""
from pathlib import Path
import subprocess
import tempfile

from translated_punctuation import ORACLE_PREAMBLE, scanner_project
from translated_preparation_recovery import ROOT, fresh_build, measure


ORACLE = r"""
$source = 'x ' . str_repeat('a', 0xFFFFFF);
foreach (flavor::cases() as $mode) {
    echo $mode->name, "\n";
    foreach ([$source, $source . 'a', $source] as $text) {
        try {
            $tokens = (new Tokenizer(new source_file('input', $text, $mode)))->tokenize()->tokens;
            foreach ($tokens as $t) echo $t->kind->value, ',', $t->offset, ',', $t->length, '|';
        } catch (diagnostic $e) {
            echo $e->offset, ':', substr(strrchr($e->getMessage(), ':'), 2);
        }
        echo "\n";
    }
}
"""


PROBE = r"""
function report(Translation_Source $source): void {
    foreach ($source->tokens as $t) {
        if ($t->kind !== Token_Kind::End) echo enum_value($t->kind), ",", $t->offset, ",", $t->length, "|";
    }
    echo "\n";
}

function exercise(string $text, Scan_Flavor $mode): void {
    $current = new Translation_Source("input", $text, $mode);
    $original = $current;
    report($current);
    try {
        $current = new Translation_Source("input", $text . "a", $mode);
        throw Exception::make("Accepted oversized token");
    } catch (Translation_Diagnostic $error) {
        echo $error->offset, ":", $error->detail, "\n";
    }
    if ($current !== $original) throw Exception::make("Replaced source after overflow");
    $current = new Translation_Source("input", $text, $mode);
    report($current);
}
"""


def verify(work):
    oracle = subprocess.check_output(['php', '-r', ORACLE_PREAMBLE + ORACLE, ROOT/'compiler/boot.php'], text=True)
    rows = oracle.splitlines()
    main = [PROBE, '$text = "x " . str_repeat("a", 0xFFFFFF);']
    modes = []
    for index in range(0, len(rows), 4):
        mode, valid, error, repaired = rows[index:index + 4]
        assert valid.startswith('1,0,1|1,2,16777215|'), (mode, valid)
        assert error == '2:Token length exceeds 24-bit capacity', (mode, error)
        assert repaired == valid, (mode, repaired)
        modes.append(mode)
        main.append(f'echo "{mode}\\n"; exercise($text, Scan_Flavor::{mode});')
    assert set(modes) == {'cpp', 'php', 'js', 'python'}
    app = scanner_project(work)
    (app/'main.php').write_text('\n'.join([*main, 'return 8;']))
    generated = work/'generated'
    result = fresh_build(app, generated)
    assert 'error' not in result, result
    measure(generated, work/'native', oracle.encode())


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-token-capacity-') as tmp:
        verify(Path(tmp))
