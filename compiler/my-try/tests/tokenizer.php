<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function token_scan(string $text): token_list
{
	$file = new file();
	$file->path = 'bytes.phs';
	$file->content = $text;
	$scanner = new Tokenizer($file);
	return $scanner->tokenize();
}
function token_check(bool $ok): void
{
	if (!$ok) {
		throw new \LogicException('Tokenizer byte contract failed');
	}
}
$single = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_0123456789;(){}:,&[]<>=';
for ($value = 0; $value < 256; $value++)
{
	$byte = chr($value);
	if (str_contains(" \t\r\n", $byte)) {
		token_check(token_scan($byte)->tokens->is_empty());
	}
	elseif (str_contains($single, $byte)) {
		$tokens = token_scan($byte)->tokens;
		token_check(count($tokens) === 1 && $tokens[0]->offset === 0 && $tokens[0]->length === 1 && $tokens[0]->text() === $byte);
	}
	else
	{
		$failed = false;
		try {
			token_scan($byte);
		}
		catch (\RuntimeException $error) {
			$failed = str_contains($error->getMessage(), 'bytes.phs: byte 0');
		}
		token_check($failed);
	}
}
$input = " \t\r\n\$abc_09=007;\$x->field";
$expected = [[4, 7, '$abc_09'], [11, 1, '='], [12, 3, '007'], [15, 1, ';'], [16, 2, '$x'], [18, 2, '->'], [20, 5, 'field']];
$tokens = token_scan($input)->tokens;
token_check(count($tokens) === count($expected));
foreach ($tokens as $index => $token) {
	token_check([$token->offset, $token->length, $token->text()] === $expected[$index]);
}
foreach (['$', '$0', '12a', '1.', '==', '=>', '-', "\0", "\xc3\xa9"] as $invalid)
{
	$failed = false;
	try {
		token_scan('  ' . $invalid);
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), 'bytes.phs: byte 2');
	}
	token_check($failed);
}
token_check(token_scan('')->tokens->is_empty());
for ($byte = 0; $byte < 256; $byte++) {
	$text = chr($byte);
	token_check(Source_Text::identifier($text) === (preg_match('/^[A-Za-z_]$/D', $text) === 1));
	token_check(Source_Text::identifier('a' . $text) === (preg_match('/^[A-Za-z_][A-Za-z_0-9]*$/D', 'a' . $text) === 1));
	token_check(Source_Text::digits($text) === ($byte >= 48 && $byte <= 57));
}
token_check(!Source_Text::identifier('') && !Source_Text::digits(''));
token_check(Source_Text::digits('00012') && !Source_Text::digits('12a'));
echo "Tokenizer: every byte, exact spans, whitespace, EOF and rejection offsets passed\n";
