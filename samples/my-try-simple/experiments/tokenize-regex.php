<?php
/** Experimental PHP-flavor lexer comparison; never loaded by the compiler. */
declare(strict_types=1);
require_once __DIR__ . '/../../../compiler/boot.php';
use scpp\compiler\{source_file, flavor, Tokenizer, token, token_kind, token_list};

/** Corpus prototype: bulk lexical matching followed by ordinary token allocation/validation. */
final class Regex_Probe
{
    private string $pattern;
    private string $match_pattern;
    private string $utf8_pattern;
    private array $punctuation;

    public function __construct()
    {
        // Reuse current longest-match order for this experiment only; no production reflection.
        $kinds = (new ReflectionClass(Tokenizer::class))->getConstant('PUNCTUATION');
        $parts = [];
        foreach ($kinds as $kind) {
            $this->punctuation[$kind->label()] = $kind;
            $parts[] = preg_quote($kind->label(), '~');
        }
        $lexemes = <<<'REGEX'
[ \t\r\n]+|//[^\r\n]*|\#(?!\[)[^\r\n]*|/\*[\s\S]*?\*/|"(?:[^"\\]|\\[\s\S])*"|'(?:[^'\\]|\\[\s\S])*'|\$[A-Za-z_][A-Za-z_0-9]*|[A-Za-z_][A-Za-z_0-9]*|(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)(?:[eE][+-]?[0-9]+)?
REGEX;
        $this->pattern = '~(' . $lexemes . '|' . implode('|', $parts) . ')~';
        $this->match_pattern = '~(?:' . $lexemes . '|' . implode('|', $parts) . ')~';
        // UTF mode validates source encoding; ASCII identifier and whitespace rules stay explicit.
        $this->utf8_pattern = $this->match_pattern . 'u';
    }

    public function scan(source_file $source, string $method): token_list
    {
        if ($source->flavor !== flavor::php) { throw new LogicException('PHP-flavor prototype only'); }
        if ($method === 'match_all' || $method === 'match_all_utf8') {
            $pattern = $method === 'match_all_utf8' ? $this->utf8_pattern : $this->match_pattern;
            $ok = preg_match_all($pattern, $source->content, $matches, PREG_OFFSET_CAPTURE);
            if ($ok === false) { throw new RuntimeException(preg_last_error_msg(), preg_last_error()); }
            $pieces = $matches[0];
            unset($matches);
        } elseif ($method === 'split') {
            $pieces = preg_split($this->pattern, $source->content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_NO_EMPTY);
            if ($pieces === false) { throw new RuntimeException(preg_last_error_msg()); }
        } else {
            throw new InvalidArgumentException('Unknown probe method');
        }
        $tokens = [];
        $end = 0;
        foreach ($pieces as [$word, $offset]) {
            if ($offset !== $end) { throw new RuntimeException('Unmatched source bytes'); }
            $length = strlen($word);
            $end = $offset + $length;
            $first = $word[0];
            if (str_contains(" \t\r\n", $first) || str_starts_with($word, '//') || str_starts_with($word, '/*')
                || ($first === '#' && !str_starts_with($word, '#['))) { continue; }
            if (isset($this->punctuation[$word])) { $kind = $this->punctuation[$word]; }
            elseif ($first === '$') { $kind = token_kind::variable; }
            elseif ($first === '"') { $kind = token_kind::double_string; }
            elseif ($first === "'") { $kind = token_kind::single_string; }
            elseif (($first >= '0' && $first <= '9') || $first === '.') {
                $kind = strpbrk($word, '.eE') === false ? token_kind::integer : token_kind::floating;
            } else { $kind = $source->flavor->word_kind($word); }
            if ($length > 16777215) { throw new RuntimeException('Token too long'); }
            $tokens[] = new token($offset, $length, $kind);
        }
        if ($end !== strlen($source->content)) { throw new RuntimeException('Unmatched trailing bytes'); }
        return new token_list($source, $tokens);
    }
}

function signature(token_list $list): array
{
    return array_map(static fn(token $t): array => [$t->offset, $t->length, $t->kind->value], $list->tokens);
}
function stats(array $values): array
{
    sort($values);
    return ['median_ms' => $values[intdiv(count($values), 2)], 'min_ms' => min($values), 'max_ms' => max($values)];
}
if (function_exists('xdebug_info') && xdebug_info('mode') !== []) { throw new RuntimeException('Use XDEBUG_MODE=off'); }
$probe = new Regex_Probe();
$manifest = json_decode(file_get_contents(__DIR__ . '/../module.json'), true, flags: JSON_THROW_ON_ERROR);
$small = [];
foreach ($manifest['sources'] as $path => $_) {
    $small[] = new source_file($path, file_get_contents(__DIR__ . '/../' . $path), flavor::php);
}
$large = '';
for ($i = 0; strlen($large) < 65536; $i++) {
    $large .= '$ret_' . $i . ' int = ' . ($i + 8) . '; echo "Result: "; echo $ret_' . $i . '; echo "\n";' . "\n";
}
$fixtures = new source_file('lexical-fixtures', <<<'SOURCE'
// words $ignore 123
# hash comment
/* block 'quoted' */
#[attribute] $value int = 3; 0 12. .5 1e-3 2.3E+4
'quoted \' text' "quoted \" text" 'multiline
text'
... **= <<= >>= === !== <=> == != <= >= => -> :: ?? && || ++ -- ** += -= *= /= %= .= &= |= ^= ; ( ) { } ? : , & [ ] < > + - * / % . ! ~ | ^ \ =
SOURCE, flavor::php);
$unicode = <<<'SOURCE'
// Română 日本語 😀 é
# Ελληνικά العربية
/* 中文 🧪 */
$value int = 8; echo "Bună 日本語 😀"; echo 'é العربية';
echo "escaped \😀 and \" quoted";
SOURCE;
$unicode_large = str_repeat($unicode . "\n", (int) ceil(65536 / strlen($unicode . "\n")));
$cases = ['my_try_simple' => $small, 'integer_echo_64k' => [new source_file('integer-echo', $large, flavor::php)],
    'lexical_fixtures' => [$fixtures], 'utf8_text_64k' => [new source_file('utf8-text', $unicode_large, flavor::php)]];
$output = ['php' => PHP_VERSION, 'pcre' => PCRE_VERSION, 'pcre_jit' => ini_get('pcre.jit'), 'samples' => 15, 'corpora' => []];
// Invalid encoding is rejected even inside comments/strings where the byte lexer permits raw bytes.
$invalid = ["\x80", "\xC0\xAF", "\xE2\x82", "\xED\xA0\x80", "\xF4\x90\x80\x80", "\xFF"];
$rejected = 0;
foreach ($invalid as $bytes) {
    foreach (['echo "' . $bytes . '";', '// ' . $bytes . "\nreturn 8;", '/* ' . $bytes . ' */ return 8;'] as $text) {
        try {
            $probe->scan(new source_file('invalid-utf8', $text, flavor::php), 'match_all_utf8');
            throw new LogicException('Malformed UTF-8 was accepted');
        } catch (RuntimeException $error) {
            if ($error->getCode() !== PREG_BAD_UTF8_ERROR) { throw $error; }
            $rejected++;
        }
    }
}
foreach (['$café int = 8;', 'echo 8;' . "\u{00A0}" . 'return 8;'] as $text) {
    try {
        $probe->scan(new source_file('unchanged-lexical-policy', $text, flavor::php), 'match_all_utf8');
        throw new LogicException('UTF mode silently expanded identifiers or whitespace');
    } catch (RuntimeException $error) {
        if (!str_starts_with($error->getMessage(), 'Unmatched')) { throw $error; }
    }
}
$output['utf8_checks'] = ['invalid_encoding_rejections' => $rejected, 'ascii_lexical_policy_preserved' => true];
foreach ($cases as $name => $sources) {
    $count = 0;
    foreach ($sources as $source) {
        $expected = signature((new Tokenizer($source))->tokenize());
        $count += count($expected);
        foreach (['match_all', 'split', 'match_all_utf8'] as $method) {
            if (signature($probe->scan($source, $method)) !== $expected) { throw new RuntimeException('Token mismatch: ' . $name . '/' . $source->path . '/' . $method); }
        }
    }
    $samples = [];
    for ($i = 0; $i < 15; $i++) {
        $methods = ['current', 'match_all', 'split', 'match_all_utf8'];
        // Rotate order to reduce a fixed-order advantage.
        $methods = array_merge(array_slice($methods, $i % count($methods)), array_slice($methods, 0, $i % count($methods)));
        foreach ($methods as $method) {
            $start = hrtime(true);
            foreach ($sources as $source) {
                $result = $method === 'current' ? (new Tokenizer($source))->tokenize() : $probe->scan($source, $method);
                unset($result);
            }
            $samples[$method][] = (hrtime(true) - $start) / 1e6;
        }
    }
    $output['corpora'][$name] = ['files' => count($sources), 'bytes' => array_sum(array_map(static fn(source_file $s): int => strlen($s->content), $sources)),
        'tokens' => $count, 'equivalent' => true, 'timings' => array_map('stats', $samples), 'raw_ms' => $samples];
}
echo json_encode($output, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
