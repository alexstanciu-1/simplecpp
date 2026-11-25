<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/bootstrap.php';

use tokenize\Tokenizer;
use read_sources\Source_Buffer;

class Tokenization_Test
{
    public static function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    public static function scan(string $text): \tokenize\Token_Buffer
    {
        return \tokenize\File_Tokenizer::tokenize(new Source_Buffer(7, 'lexical-test.phs', 0, $text));
    }

    /** Check exact token kinds and source spans without copied lexemes. */
    public static function expect_tokens(string $text, array $expected): \tokenize\Token_Buffer
    {
        $buffer = self::scan($text);
        self::check(count($buffer->rows) === count($expected), 'One flat row per token plus EOF');
        $cursor = 0;
        foreach ($expected as $index => [$kind, $spelling])
        {
            $token = $buffer->rows[$index];
            $start = $spelling === '' ? strlen($text) : strpos($text, $spelling, $cursor);
            self::check(($token->kind->name === $kind) && ($token->start === $start)
                && ($token->length === strlen($spelling)), 'Exact kind and byte span: ' . $spelling);
            self::check(array_keys(get_object_vars($token)) === ['start', 'length', 'kind'], 'Do not copy lexemes into token records');
            $cursor = $start + strlen($spelling);
        }
        return $buffer;
    }

    /** Require lexical failures to retain the expected source anchor and explanation. */
    public static function expect_error(string $text, int $offset, string $reason): void
    {
        try {
            self::scan($text);
        }
        catch (\diagnostics\Source_Error $error) {
            self::check(($error->source_file_id === 7) && ($error->path === 'lexical-test.phs')
                && ($error->start === $offset) && ($error->length > 0)
                && str_contains($error->getMessage(), $reason), 'Lexical errors need the correct source anchor');
            return;
        }
        throw new Exception('Expected lexical rejection');
    }
}

$text = "/* heading */\r\nfunction worker_9(): Custom { // call\r\n return other(10, 22);\n}\n";
$expected = [
    ['function_keyword', 'function'], ['identifier', 'worker_9'], ['left_parenthesis', '('],
    ['right_parenthesis', ')'], ['colon', ':'], ['identifier', 'Custom'], ['left_brace', '{'],
    ['return_keyword', 'return'], ['identifier', 'other'], ['left_parenthesis', '('],
    ['integer_literal', '10'], ['comma', ','], ['integer_literal', '22'],
    ['right_parenthesis', ')'], ['semicolon', ';'], ['right_brace', '}'], ['end_of_file', ''],
];
$buffer = Tokenization_Test::expect_tokens($text, $expected);
$copy = serialize($buffer);
$export = json_decode($buffer->to_json(), true, 512, JSON_THROW_ON_ERROR);
Tokenization_Test::check(($export['tokens'][7]['text'] === 'return') && (serialize($buffer) === $copy),
    'Debug text is derived from source spans without altering token storage');

// Strict typed-local spelling comes from the configured Simple C++ quick-learn.
$locals = '$count int=42; { $_next_2 int = $count; $count=value(); } return $count;';
$local_tokens = Tokenization_Test::expect_tokens($locals, [
        ['variable_name', '$count'], ['identifier', 'int'], ['assignment', '='], ['integer_literal', '42'], ['semicolon', ';'],
        ['left_brace', '{'], ['variable_name', '$_next_2'], ['identifier', 'int'], ['assignment', '='],
        ['variable_name', '$count'], ['semicolon', ';'], ['variable_name', '$count'], ['assignment', '='],
        ['identifier', 'value'], ['left_parenthesis', '('], ['right_parenthesis', ')'], ['semicolon', ';'],
        ['right_brace', '}'], ['return_keyword', 'return'], ['variable_name', '$count'], ['semicolon', ';'], ['end_of_file', ''],
    ]);
$before = serialize($local_tokens);
$export = json_decode($local_tokens->to_json(), true, 512, JSON_THROW_ON_ERROR);
Tokenization_Test::check(($export['tokens'][0]['text'] === '$count') && ($export['tokens'][2]['text'] === '=')
    && (serialize($local_tokens) === $before), 'Variable and assignment exports retain exact spelling without mutating storage');
Tokenization_Test::expect_tokens('$return $function $int $_ $Name_9 name$return', [
        ['variable_name', '$return'], ['variable_name', '$function'], ['variable_name', '$int'],
        ['variable_name', '$_'], ['variable_name', '$Name_9'], ['identifier', 'name'], ['variable_name', '$return'], ['end_of_file', ''],
    ]);
Tokenization_Test::expect_tokens('$a/* $ignored */=// $ignored' . "\n" . '$b;', [
        ['variable_name', '$a'], ['assignment', '='], ['variable_name', '$b'], ['semicolon', ';'], ['end_of_file', ''],
    ]);
Tokenization_Test::expect_tokens('= =', [['assignment', '='], ['assignment', '='], ['end_of_file', '']]);
foreach (['', " \t\r\n", '//', '// trailing', '// $ignored =', '/* $ignored = */', '/**/', "/*\xFF*/"] as $trivia) {
    $tokens = Tokenization_Test::scan($trivia)->rows;
    Tokenization_Test::check((count($tokens) === 1) && ($tokens[0]->kind === \tokenize\token_kind::end_of_file)
        && ($tokens[0]->start === strlen($trivia)) && ($tokens[0]->length === 0), 'Trivia/empty input still has exact EOF');
}
$tokens = Tokenization_Test::scan('returning function_ Function int')->rows;
foreach (array_slice($tokens, 0, -1) as $token) {
    Tokenization_Test::check($token->kind === \tokenize\token_kind::identifier, 'Keyword boundaries/case and type names remain lexical identifiers');
}
$long = str_repeat('x', 70000);
$huge_integer = str_repeat('9', 70000);
$tokens = Tokenization_Test::scan($long . ' ' . $huge_integer)->rows;
Tokenization_Test::check(($tokens[0]->length === 70000) && ($tokens[1]->length === 70000),
    'Long names/numbers are spans; tokenizer does not convert or narrow integer values');
$tokens = Tokenization_Test::scan('$' . $long)->rows;
Tokenization_Test::check((count($tokens) === 2) && ($tokens[0]->kind === \tokenize\token_kind::variable_name)
    && ($tokens[0]->start === 0) && ($tokens[0]->length === 70001) && ($tokens[1]->start === 70001),
    'Long variable names include the prefix and end exactly at EOF');
foreach (['$', '$9name', '$ name', '$/*comment*/name', '$$name', '${name}', "$\xC3\xA9"] as $malformed) {
    Tokenization_Test::expect_error('return ' . $malformed, 7, 'Expected ASCII variable name');
}
foreach (['==', '===', '=>'] as $operator) {
    Tokenization_Test::expect_error('$a ' . $operator . ' $b;', 3, 'Unsupported operator');
}
Tokenization_Test::expect_error('return /* open', 7, 'Unterminated block comment');
foreach (['0x10', '1e3', '12abc', '1.25', '1_000'] as $number) {
    Tokenization_Test::expect_error($number, 0, 'numeric literal');
}
foreach (["\0", "\xC3\xA9", '-', '/', '#'] as $unsupported) {
    Tokenization_Test::expect_error('return ' . $unsupported, 7, 'Unsupported source byte');
}
$literal_source = <<<'PHS'
echo "a\" // /*", '\n\'x';
PHS;
$literal_tokens = Tokenization_Test::scan($literal_source);
Tokenization_Test::check(array_map(static fn($token) => $token->kind->name, $literal_tokens->rows)
    === ['echo_keyword', 'string_literal', 'comma', 'string_literal', 'semicolon', 'end_of_file'],
    'Quotes contain escaped quotes and comment markers without creating extra tokens');
Tokenization_Test::expect_error('echo "open', 5, 'Unterminated string literal');
Tokenization_Test::expect_error("echo 'open", 5, 'Unterminated string literal');
Tokenization_Test::expect_error('<?php' , 0, 'Unsupported source byte');
echo "tokenization ok: exact spans, variables/assignment, comments, EOF, identifier boundaries, debug export, long tokens and anchored rejection\n";
