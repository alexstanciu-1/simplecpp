"""Run the production string scanner through PHP and the v0.2 native compiler."""
import json
from pathlib import Path
import subprocess
import tempfile

from collection_goal_native import revisions

COMPILER = Path(__file__).resolve().parents[1]
PREFIX = 'before π\n  '

# The diagnostic expectations are offsets in the spelling, measured in bytes.
CASES = [
    ('marked_bytes', r'"A\nB\tC\0D"', 'php', False, b'A\nB\tC\0D'),
    ('adjacent_escapes', r'"\n\t\\Z"', 'php', False, b'\n\t\\Z'),
    ('last_byte', r'"\n"', 'php', False, b'\n'),
    ('last_part_byte', r'\0', 'js', True, b'\0'),
    ('single_quote', r"'a\nb\'c'", 'php', False, br"a\nb'c"),
    ('js_part', r'X\`Y\`Z', 'js', True, b'X`Y`Z'),
    ('cpp_part', r'{{A\nB}}', 'cpp', True, b'{A\nB}'),
    ('incomplete_part', 'é\\nA\\', 'js', True, (5, 'Incomplete string escape')),
    ('unsupported_quoted', r'"é\nA\qZ"', 'php', False, (6, 'Unsupported string escape')),
    ('unsupported_octal', r'"A\n\07Z"', 'php', False, (4, 'Unsupported string escape')),
]

# The native fixture supplies the source/span and exception fields used by the scanner.
# Its AST classes carry no parser or preparation behavior.
NATIVE_BOUNDARY = '''
use Exception as CoreException;

enum flavor: string { case php = "php"; case js = "js"; case cpp = "cpp"; }

final class source_file {
    public flavor $flavor = flavor::php;
    public function __construct(flavor $mode) { $this->flavor = $mode; }
}

class ast_string_expression {
    private source_file $origin;
    private string $spelling;
    private int $offset;

    public function __construct(source_file $source, string $text, int $start) {
        $this->origin = $source;
        $this->spelling = $text;
        $this->offset = $start;
    }

    public function source(): source_file { return $this->origin; }
    public virtual function source_text(): string { return $this->spelling; }
    public function byte_start(): int { return $this->offset; }
}

final class ast_interpolation_text extends ast_string_expression {
    public function __construct(source_file $source, string $text, int $start) {
        parent::__construct($source, $text, $start);
    }
}

final class diagnostic extends CoreException {
    public source_file $source;
    public int $offset;
    public string $reason;

    public function __construct(source_file $source, int $offset, string $message) {
        $this->source = $source;
        $this->offset = $offset;
        $this->reason = $message;
    }
}

function report(string $label, ast_string_expression $syntax): void {
    try {
        $decoded = String_Literals::decode($syntax);
        echo $label, "|ok|", bin2hex($decoded), "\\n";
    } catch (diagnostic $failure) {
        if ($failure->source !== $syntax->source()) {
            throw $failure;
        }
        echo $label, "|error|", $failure->offset, "|", $failure->reason, "\\n";
    }
}
'''


def expected_output():
    rows = []
    for name, spelling, _, _, expected in CASES:
        if isinstance(expected, bytes):
            rows.append(f'{name}|ok|{expected.hex()}\n')
        else:
            relative, reason = expected
            assert spelling.encode()[relative:relative + 1] == b'\\', (name, relative)
            rows.append(f'{name}|error|{len(PREFIX.encode()) + relative}|{reason}\n')
    return ''.join(rows).encode()


def php_output():
    # Construct real AST spans directly so a truncated interpolation part reaches
    # the scanner's defensive check even if tokenization would reject the full input.
    fixture = [(name, spelling, flavor, part) for name, spelling, flavor, part, _ in CASES]
    return subprocess.check_output(['php', '-r', r'''
require $argv[1];
use scpp\compiler\{ast_file_node, ast_interpolation_text, ast_string_expression, diagnostic,
    flavor, source_file, String_Literals, token, token_kind, token_list};
[$prefix, $cases] = json_decode($argv[2], true, flags: JSON_THROW_ON_ERROR);
foreach ($cases as [$label, $text, $mode, $part]) {
    $source = new source_file('scanner.php', $prefix . $text, flavor::from($mode));
    $start = strlen($prefix);
    $length = strlen($text);
    $part_offset = $part ? 2 : 0;
    $token = new token($start - $part_offset, $length + $part_offset, token_kind::double_string);
    $root = new ast_file_node(new token_list($source, [$token]));
    $syntax = $part ? new ast_interpolation_text(0, $part_offset, $length) : new ast_string_expression(0, 1);
    $syntax->attach($root);
    try {
        $result = String_Literals::decode($syntax);
        echo $label, '|ok|', bin2hex($result), "\n";
    } catch (diagnostic $failure) {
        if ($failure->source !== $source) throw $failure;
        $message = $failure->getMessage();
        $reason = substr($message, strpos($message, ': ') + 2);
        echo $label, '|error|', $failure->offset, '|', $reason, "\n";
    }
}
''', COMPILER / 'boot.php', json.dumps([PREFIX, fixture])])


def native_scanner():
    source = (COMPILER / '04_analyze/prepare/strings.php').read_text()
    source = source.split('namespace scpp\\compiler;', 1)[1]

    # PHP [] reads one-byte strings. This PHP++ runtime exposes numeric indexed
    # bytes, so spell the same byte-string access via its byte-slice binding.
    # Keep the scanner's control flow and postfix expression from production.
    lookahead = "$text[$index + 1] ?? ''"
    assert source.count(lookahead) == 1
    source = source.replace(lookahead, 'substr($text, $index + 1, 1)')
    for index, count in [('0', 2), ('$index', 2)]:
        access = f'$text[{index}]'
        assert source.count(access) == count
        source = source.replace(access, f'substr($text, {index}, 1)')
    # Bind the present payload to the type expected by the native compound.
    append = '$bytes .= $decoded;'
    assert source.count(append) == 1
    return source.replace(append, '$decoded_bytes string = $decoded;\n\t\t\t$bytes .= $decoded_bytes;')


def php_string(text):
    return "'" + text.replace('\\', '\\\\').replace("'", "\\'") + "'"


def native_entry():
    start = len(PREFIX.encode())
    lines = []
    for name, spelling, flavor, part, _ in CASES:
        syntax_type = 'ast_interpolation_text' if part else 'ast_string_expression'
        lines.append(f'report("{name}", new {syntax_type}(new source_file(flavor::{flavor}), '
                     f'{php_string(spelling)}, {start}));')
    return '\n'.join(lines) + '\nreturn 8;\n'


if __name__ == '__main__':
    expected = expected_output()
    actual = php_output()
    assert actual == expected, (actual, expected)
    with tempfile.TemporaryDirectory(prefix='scpp-string-scanner-') as directory:
        revisions(Path(directory), 'php',
                  {'scanner': NATIVE_BOUNDARY + native_scanner(), 'main': native_entry()},
                  [(None, None, expected)])
