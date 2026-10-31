<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{diagnostic, flavor, source_file, Source_Frontend, token, token_kind, token_list};

function check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

$frontend = new Source_Frontend();
function scan(string $text, flavor $flavor = flavor::php): token_list
{
	global $frontend;
	return $frontend->scan(new source_file('memory', $text, $flavor));
}

foreach ([['int count = 3;', flavor::cpp, ['identifier', 'identifier', '=', 'integer', ';']],
	['$count int = 3;', flavor::php, ['variable', 'identifier', '=', 'integer', ';']],
	['let count: int = 3;', flavor::js, ['keyword', 'identifier', ':', 'identifier', '=', 'integer', ';']]] as [$text, $flavor, $expected]) {
	$list = scan($text, $flavor);
	check(array_map(fn(token $token): string => $token->kind->label(), $list->tokens) === $expected, 'Equivalent declaration tokens');
	check($list->source->flavor === $flavor, 'Flavor retained per source');
}
foreach (flavor::cases() as $flavor) {
	$list = scan('echo auto let int true', $flavor);
	$expected = match ($flavor) {
		flavor::php => ['keyword', 'identifier', 'identifier', 'identifier', 'keyword'],
		flavor::cpp => ['keyword', 'keyword', 'identifier', 'identifier', 'keyword'],
		flavor::js => ['keyword', 'identifier', 'keyword', 'identifier', 'keyword'],
	};
	check(array_map(fn(token $token): string => $token->kind->label(), $list->tokens) === $expected, 'Flavor keyword boundary');
	check(scan(" \t\r\n/* bytes \xff */ // tail", $flavor)->tokens === [], 'Trivia and empty generation');
}
check(scan('')->tokens === [], 'Empty source');
check(scan('$a$b', flavor::js)->text_at(0) === '$a$b', 'JS dollar identifier');
check(count(scan('$a$b')->tokens) === 2, 'PHP variable boundary');
check(scan("# comment\n#[A]")->tokens[0]->kind === token_kind::attribute, 'Attribute is not a comment');
$list = scan('<< < < >> >/* gap */> <<= >>=');
check($list->operator_text_at(0) === '<<', 'Adjacent left angles');
check($list->operator_text_at(2) === '<', 'Whitespace prevents shift');
check($list->operator_text_at(4) === '>>', 'Adjacent right angles');
check($list->operator_text_at(6) === '>', 'Comment prevents shift');
check($list->text_at(8) === '<<=' && $list->text_at(9) === '>>=', 'Compound shifts');
check(array_map(fn(token $token): string => $token->kind->label(), scan('0 .5 12. 1e-3 -2 +3')->tokens) ===
	['integer', 'floating', 'floating', 'floating', '-', 'integer', '+', 'integer'], 'Decimal boundaries');
$text = "'a\\'b' \"\xff\0\"";
$list = scan($text);
check($list->text_at(0) === "'a\\'b'" && $list->text_at(1) === "\"\xff\0\"", 'Lossless quoted bytes');
foreach ([['$', flavor::php, 0], ['$1', flavor::php, 0], ['$a', flavor::cpp, 0],
	['#[A]', flavor::js, 0], ['# x', flavor::cpp, 0], ['1e+', flavor::php, 0],
	['12abc', flavor::php, 0], ['0x12', flavor::cpp, 0], ['1.2.3', flavor::js, 0],
	["ok;\n/*", flavor::php, 4], ["'unfinished", flavor::php, 0], ["\0", flavor::js, 0],
	["\xff", flavor::php, 0]] as [$text, $flavor, $offset]) {
	try {
		scan($text, $flavor);
		throw new RuntimeException('Expected lexical failure: ' . bin2hex($text));
	} catch (diagnostic $error) {
		check($error->offset === $offset, 'Error points to opening byte');
	}
}
$source = new source_file('lines', "a\r\nb\n", flavor::cpp);
check($source->location(3) === ['line' => 2, 'column' => 1], 'LF lines');
check($source->location(5) === ['line' => 3, 'column' => 1], 'EOF location');
$old = scan('old');
scan('new', flavor::js);
check($old->text_at(0) === 'old' && $old->source->flavor === flavor::php, 'Old generation stays intact');
foreach ([[new token(0, 2, token_kind::identifier), new token(1, 1, token_kind::identifier)], [new token(5, 1, token_kind::identifier)]] as $tokens) {
	try {
		new token_list($source, $tokens);
		throw new RuntimeException('Expected invalid publication');
	} catch (InvalidArgumentException) {}
}
try {
	$old->tokens = [];
	throw new RuntimeException('Expected immutable publication');
} catch (Error) {}
check(count(scan(str_repeat('x;', 10000))->tokens) === 20000, 'Long flat source');
// The public token boundary rejects arbitrary strings, even valid serialized labels.
try {
	new token(0, 1, 'identifier');
	throw new RuntimeException('Expected enum-only kind');
} catch (TypeError) {}
check(token_kind::tryFrom(0) === null, 'Unknown lexical kind rejected');
check(token_kind::identifier->value === 1, 'Explicit identifier ID');
check(token_kind::variable->value === 2, 'Explicit variable ID');
foreach (token_kind::cases() as $kind) {
	check(is_int($kind->value) && ($kind->value > 0) && ($kind->value <= 127), 'Kind ID fits seven bits');
	check(token_kind::from($kind->value) === $kind, 'Integer kind round trip');
	if (!in_array($kind, [token_kind::identifier, token_kind::variable, token_kind::integer,
		token_kind::floating, token_kind::single_string, token_kind::double_string, token_kind::keyword], true)) {
		$list = scan($kind->label());
		check(count($list->tokens) === 1 && $list->tokens[0]->kind === $kind, 'Punctuation enum classification');
	}
}
// Consumers distinguish keywords by retained spelling, not by a separate token tag.
foreach (flavor::cases() as $flavor) {
	$list = scan('if else return', $flavor);
	foreach (['if', 'else', 'return'] as $index => $word) {
		check($list->tokens[$index]->kind === token_kind::keyword, 'Shared keyword kind');
		check($list->text_at($index) === $word, 'Consumer reads exact keyword spelling');
	}
	$list = scan('iffy Else returning', $flavor);
	foreach ($list->tokens as $token) {
		check($token->kind === token_kind::identifier, 'Keyword matching is exact and case-sensitive');
	}
}
echo "Tokenizer checks passed\n";
