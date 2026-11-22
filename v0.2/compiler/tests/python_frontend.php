<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{ast_binary_expression, ast_not_expression, binary_operator, diagnostic, entry_target,
	flavor, source_file, Source_Frontend, token_kind};

function check(bool $ok, string $message): void
{
	if (!$ok) throw new RuntimeException($message);
}

$frontend = new Source_Frontend();
$lines = ['if True:', '    # ignored', '    while False:', '        run(', '            3)', '', 'run(4)',
	'if False:', '    run(5)', 'else:', '    run(6)'];
foreach (["\n", "\r\n"] as $ending) {
	foreach (['', $ending] as $tail) {
		$text = implode($ending, $lines) . $tail;
		$source = new source_file('layout.py', $text, flavor::python);
		$list = $frontend->scan($source);
		$layout = [];
		foreach ($list->tokens as $token) {
			if ($token->kind === token_kind::newline) {
				$physical = $token->offset < strlen($text);
				check($token->length === ($physical ? strlen($ending) : 0)
					&& substr($text, $token->offset, $token->length) === ($physical ? $ending : ''), 'NEWLINE span');
			}
			elseif (!in_array($token->kind, [token_kind::indent, token_kind::dedent], true)) continue;
			else check($token->length === 0, 'Indentation tokens are zero-width');
			$layout[] = [$token->kind->label(), $token->offset];
		}
		$offsets = [0];
		foreach ($lines as $line) $offsets[] = end($offsets) + strlen($line) + strlen($ending);
		$expected = [
			['NEWLINE', strlen($lines[0])], ['INDENT', $offsets[2] + 4], ['NEWLINE', $offsets[2] + strlen($lines[2])],
			['INDENT', $offsets[3] + 8], ['NEWLINE', $offsets[4] + strlen($lines[4])],
			['DEDENT', $offsets[6]], ['DEDENT', $offsets[6]], ['NEWLINE', $offsets[6] + strlen($lines[6])],
			['NEWLINE', $offsets[7] + strlen($lines[7])], ['INDENT', $offsets[8] + 4],
			['NEWLINE', $offsets[8] + strlen($lines[8])], ['DEDENT', $offsets[9]],
			['NEWLINE', $offsets[9] + strlen($lines[9])], ['INDENT', $offsets[10] + 4],
			['NEWLINE', strlen($text) - strlen($tail)], ['DEDENT', strlen($text)],
		];
		check($list->source === $source && $layout === $expected, json_encode($layout));
		$frontend->parse($source);
	}
}

foreach ([
	["  return 1\n", 2, 'Unexpected Python indentation'],
	["if True:\n \treturn 1\n", 10, 'Tab in Python indentation'],
	["if True:\n    return 1\n  return 2", 22, 'Unmatched Python dedent'],
	["if True\n    return 1", 7, ': before Python suite'],
	["if True:\nreturn 1", 9, 'INDENT for Python suite'],
	["if True: return 1", 9, 'NEWLINE before Python suite'],
	["return 8 // 2", 9, 'Unsupported punctuation'],
	["return \"\u{03c0}\" / 2", 12, 'Unsupported punctuation'],
	["return 1 == 2 < 3", 14, 'chained comparisons'],
] as [$text, $offset, $message]) {
	try {
		$source = new source_file('invalid.py', $text, flavor::python);
		$frontend->parse($source);
		throw new RuntimeException('Expected syntax failure: ' . $text);
	} catch (diagnostic $failure) {
		check($failure->source === $source && $failure->offset === $offset
			&& str_contains($failure->getMessage(), $message), $failure->getMessage() . ' offset ' . $failure->offset);
		if ($message === 'Unsupported punctuation') {
			check(str_starts_with($failure->getMessage(), 'invalid.py:1:' . ($offset + 1) . ':'), 'Division diagnostic byte column');
		}
	}
}

$shapes = [
	[flavor::python, "x: int = 0\nif True:\n    while x < 2:\n        x = x + 1\nreturn x"],
	[flavor::cpp, 'int x = 0; if (true) { while (x < 2) { x = x + 1; } } return x;'],
	[flavor::php, '$x int = 0; if (true) { while ($x < 2) { $x = $x + 1; } } return $x;'],
	[flavor::js, 'let x:int = 0; if (true) { while (x < 2) { x = x + 1; } } return x;'],
];
$expected = null;
foreach ($shapes as [$flavor, $text]) {
	$statements = $frontend->parse(new source_file('shape', $text, $flavor))->main()->body()->statements;
	$loop = $statements[1]->arms[0]->body->statements[0];
	$shape = [get_class($statements[0]), get_class($statements[1]), get_class($loop),
		get_class($loop->body->statements[0]), get_class($statements[2])];
	$expected ??= $shape;
	check($shape === $expected, 'Common statement shapes in ' . $flavor->value);
}

$signs = new source_file('signs.py', "return 9--2++1", flavor::python);
$tokens = $frontend->scan($signs);
check(array_map(static fn($token) => $token->kind, $tokens->tokens) === [
	token_kind::keyword, token_kind::integer, token_kind::minus, token_kind::minus,
	token_kind::integer, token_kind::plus, token_kind::plus, token_kind::integer, token_kind::newline,
], 'Adjacent signs remain distinct Python operators');
$frontend->parse($signs);

$text = "def choose() -> int:\n    if True:\n        return 1\n    # Before sibling\n    return 2\n# Before entry\nreturn choose()";
$file = $frontend->parse(new source_file('span.py', $text, flavor::python));
$functions = array_values(array_filter($file->declaration_nodes, static fn($node) => $node->short_name === 'choose'));
check(count($functions) === 1, 'Find declared function for span checks');
$function = $functions[0];
$body = $function->body();
$arm = $body->statements[0]->arms[0];
check($arm->body->source_text() === ":\n        return 1\n"
	&& $arm->byte_end() === strpos($text, '    # Before sibling'), 'Suite excludes next sibling trivia');
check($function->source_text() === "def choose() -> int:\n    if True:\n        return 1\n    # Before sibling\n    return 2\n"
	&& $body->byte_end() === strpos($text, '# Before entry'), 'Function excludes next declaration trivia');

$logic = new source_file('logic.py', "flag: bool = not 1 < 2 and False or True\nreturn 8", flavor::python);
$value = $frontend->parse($logic)->main()->body()->statements[0]->initializer;
check($value instanceof ast_binary_expression && $value->operator === binary_operator::logical_or
	&& $value->left instanceof ast_binary_expression && $value->left->operator === binary_operator::logical_and
	&& $value->left->left instanceof ast_not_expression && $value->left->left->operand instanceof ast_binary_expression
	&& $value->left->left->operand->operator === binary_operator::less, 'Python logical precedence');
$grouped = new source_file('grouped.py', "flag: bool = not (True or False) and True\nreturn 8", flavor::python);
$value = $frontend->parse($grouped)->main()->body()->statements[0]->initializer;
check($value instanceof ast_binary_expression && $value->operator === binary_operator::logical_and
	&& $value->left instanceof ast_not_expression && $value->left->operand instanceof ast_binary_expression
	&& $value->left->operand->operator === binary_operator::logical_or, 'Grouping overrides Python logical precedence');

foreach ([
	['not 1', '1'],
	['0 and False', '0'],
	['False and 1', '1'],
	['"text" or False', '"text"'],
	['True or "fallback"', '"fallback"'],
] as [$expression, $operand]) {
	$text = 'flag: bool = ' . $expression . "\nreturn 8";
	$source = new source_file('logic-type.py', $text, flavor::python);
	try {
		$frontend->prepare($source, new entry_target(31));
		throw new RuntimeException('Expected non-bool rejection: ' . $expression);
	} catch (diagnostic $failure) {
		check($failure->source === $source && $failure->offset === strpos($text, $operand)
			&& str_contains($failure->getMessage(), 'Python logical operand requires bool'), $failure->getMessage());
	}
}

$text = "value: int = (1 + 2) if not False and True or False else 4 if True else (5 + 6)\nreturn value";
$file = $frontend->parse(new source_file('selection.py', $text, flavor::python));
$selection = $file->main()->body()->statements[0]->initializer;
check($selection instanceof scpp\compiler\ast_ternary_expression
	&& $selection->condition instanceof ast_binary_expression && $selection->condition->operator === binary_operator::logical_or
	&& $selection->when_false instanceof scpp\compiler\ast_ternary_expression
	&& $selection->when_false->source_text() === '4 if True else (5 + 6)'
	&& $selection->source_text() === '(1 + 2) if not False and True or False else 4 if True else (5 + 6)',
	'Python conditional precedence, right association and complete source span');

$text = "if True:\r\n    pass\r\nreturn 8";
$file = $frontend->parse(new source_file('pass.py', $text, flavor::python));
$pass = $file->main()->body()->statements[0]->arms[0]->body->statements[0];
check($pass instanceof scpp\compiler\ast_block_statement && $pass->body->statements === []
	&& $pass->byte_start() === strpos($text, 'pass') && $pass->source_text() === "pass\r\n",
	'Pass-only suite retains its original statement span');

foreach ([
	["return 8 if True else False", 'Ternary result types must be identical'],
	["break\nreturn 8", 'requires an enclosing loop'],
	["continue\nreturn 8", 'requires an enclosing loop'],
	["while True:\n    break 2\nreturn 8", 'Expected NEWLINE'],
	["while True:\n    continue 2\nreturn 8", 'Expected NEWLINE'],
	["def missing() -> int:\n    return\nreturn missing()", 'Non-void function requires a value return'],
	["return", 'Entry requires a value return'],
] as [$text, $message]) {
	try {
		$frontend->prepare(new source_file('body.py', $text, flavor::python), new entry_target(31));
		throw new RuntimeException('Expected rejection: ' . $text);
	} catch (diagnostic $failure) {
		check(str_contains($failure->getMessage(), $message), $failure->getMessage());
	}
}
