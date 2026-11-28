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
	["return 8 %= 2", 9, 'Unsupported punctuation'],
	["return \"\u{03c0}\" /= 2", 12, 'Unsupported punctuation'],
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


$text = "while False:\n    return 1\nelse:\n    return 2";
$entry = $frontend->prepare(new source_file('while_else.py', $text, flavor::python), new entry_target(31));
$loop = $entry->body()->statements[0];
check($loop instanceof scpp\compiler\ast_while_else_statement && $loop->source_text() === $text
	&& $loop->otherwise->parent === $loop && $entry->body()->statement_visits === 3
	&& $entry->body()->expression_visits === 3, 'While else owns its suite and prepares each occurrence once');

foreach ([flavor::cpp, flavor::php, flavor::js] as $flavor) {
	$text = 'while (false) { break; } else { return 1; }';
	try {
		$frontend->parse(new source_file('peer', $text, $flavor));
		throw new RuntimeException('Accepted while else in ' . $flavor->name);
	} catch (diagnostic $failure) {
		check($failure->offset >= strpos($text, 'else'), $failure->getMessage());
	}
}

$chain = $frontend->parse(new source_file('chain.py', 'return not 1 & 3 < 2 <= 4 or False', flavor::python))
	->main()->body()->statements[0]->value->left->operand;
[$first, $second] = $chain->links;
check($chain->source_text() === '1 & 3 < 2 <= 4' && $first->left->operator === binary_operator::bit_and
	&& $first->operator === binary_operator::less && $second->operator === binary_operator::less_equal
	&& $first->right === $second->left && $second->left->parent === $first, 'Comparison chain precedence, span and ownership');

$grouped = '(1 + 2) < 4 <= (5 + 6)';
$chain = $frontend->parse(new source_file('grouped.py', 'return ' . $grouped, flavor::python))
	->main()->body()->statements[0]->value;
check($chain->source_text() === $grouped, 'Chain span includes grouping at both ends');

$bitwise = $frontend->parse(new source_file('flags.py',
	'return not 1 | 2 ^ 3 & ~4 + 5 == 6 or False', flavor::python));
$logic = $bitwise->main()->body()->statements[0]->value;
$comparison = $logic->left->operand;
$bits = $comparison->left;
check($logic->operator === binary_operator::logical_or && $logic->left instanceof ast_not_expression
	&& $comparison->operator === binary_operator::equal && $bits->operator === binary_operator::bit_or
	&& $bits->right->operator === binary_operator::bit_xor && $bits->right->right->operator === binary_operator::bit_and
	&& $bits->right->right->right->operator === binary_operator::add
	&& $bits->right->right->right->left->source_text() === '~4', 'Python bitwise precedence on the shared tree');

$membership = $frontend->parse(new source_file('membership.py',
	'return not a | b not in mapping and ok', flavor::python))->main()->body()->statements[0]->value;
$test = $membership->left->operand;
check($membership->operator === binary_operator::logical_and && $membership->left instanceof ast_not_expression
	&& $test->operator === binary_operator::key_not_in && $test->left->operator === binary_operator::bit_or
	&& $test->source_text() === 'a | b not in mapping', 'Membership shares comparison precedence and preserves the source span');

$chain = $frontend->parse(new source_file('links.py', 'return a not in b in c', flavor::python))
	->main()->body()->statements[0]->value;
[$first, $second] = $chain->links;
check($first->operator === binary_operator::key_not_in && $second->operator === binary_operator::key_in
	&& $first->right === $second->left, 'Membership can start and continue a comparison chain');

$chain = $frontend->parse(new source_file('identity_chain.py', 'return a is not b is None', flavor::python))
	->main()->body()->statements[0]->value;
[$first, $second] = $chain->links;
check($first->operator === binary_operator::not_identical && $second->operator === binary_operator::identical
	&& $first->right === $second->left && $chain->source_text() === 'a is not b is None', 'Chained identity keeps its middle operand');

$identity = $frontend->parse(new source_file('presence.py',
	'return not value is not None or None is None', flavor::python))->main()->body()->statements[0]->value;
check($identity->operator === binary_operator::logical_or && $identity->left instanceof ast_not_expression
	&& $identity->left->operand->operator === binary_operator::not_identical
	&& $identity->left->operand->source_text() === 'value is not None'
	&& $identity->right->operator === binary_operator::identical && $identity->right->right->word === 'null',
	'Python identity uses the shared strict comparison at comparison precedence');

$division = "fraction: float = 7 / 2 # keep the newline\nfloor: int = 1 + 20 // 3 % 2 * 4\nmixed: float = 7 / 2.0\nreturn floor";
$source = new source_file('division.py', $division, flavor::python);
$file = $frontend->parse($source);
[$fraction, $floor] = $file->main()->body()->statements;
$remainder = $floor->initializer->right->left;
check($fraction->initializer->operator === binary_operator::true_divide && $fraction->initializer->source_text() === '7 / 2'
	&& $floor->initializer->operator === binary_operator::add && $floor->initializer->right->operator === binary_operator::multiply
	&& $remainder->operator === binary_operator::floor_remainder && $remainder->left->operator === binary_operator::floor_divide
	&& $remainder->source_text() === '20 // 3 % 2', 'Python division operators preserve precedence, association and spans');
$prepared = $frontend->prepare($source, new entry_target(31));
[$fraction, $floor, $mixed] = $prepared->file->main()->body()->statements;
check($fraction->initializer->type_def() === scpp\compiler\type_float64::instance()
	&& $mixed->initializer->type_def() === scpp\compiler\type_float64::instance()
	&& $floor->initializer->type_def() === scpp\compiler\type_int64::instance(), 'Python division result types');
$cpp = (new scpp\compiler\Cpp_Emitter())->emit($prepared);
foreach (['#include <scpp/arithmetic.hpp>', 'scpp::int64_true_divide(', 'scpp::float64_divide(',
	'scpp::int64_floor_divide(', 'scpp::int64_floor_remainder('] as $expected) {
	check(str_contains($cpp, $expected), 'Missing generated division binding: ' . $expected);
}
foreach ([flavor::cpp, flavor::php, flavor::js] as $peer) {
	$ordinary = $frontend->parse(new source_file('ordinary', 'return 1 < 2 < 3;', $peer))
		->main()->body()->statements[0]->value;
	check($ordinary instanceof ast_binary_expression && $ordinary->left instanceof ast_binary_expression
		&& $ordinary->operator === binary_operator::less && $ordinary->left->operator === binary_operator::less,
		$peer->value . ' retains left-associated binary comparisons');
	foreach (['in', 'not in'] as $operator) {
		$source = new source_file('ordinary', "return 1 < 2 $operator 3;", $peer);
		try {
			$frontend->parse($source);
			throw new RuntimeException($peer->value . ' accepted Python membership');
		} catch (diagnostic $failure) {
			check($failure->offset === 13, $failure->getMessage());
		}
	}

	$tokens = $frontend->scan(new source_file('ordinary', "return 8 / 2 % 3; // 1 // 2\n", $peer));
	$operators = array_filter($tokens->tokens, static fn($token) => in_array($token->kind,
		[token_kind::slash, token_kind::percent, token_kind::double_slash], true));
	check(array_values(array_column($operators, 'kind')) === [token_kind::slash, token_kind::percent], 'Default slash comments and operators');
}
foreach (['1.0 // 2', '1 % 2.0', 'True / 2', '"fmt" % 2'] as $expression) {
	try {
		$frontend->prepare(new source_file('division.py', "return $expression", flavor::python), new entry_target(31));
		throw new RuntimeException('Expected division rejection: ' . $expression);
	} catch (diagnostic $failure) {
		check(str_contains($failure->getMessage(), 'Unsupported binary operands or operator'), $failure->getMessage());
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
	["while False:\n    pass\nelse:\n    break", 'requires an enclosing loop'],
	["while False:\n    pass\nelse:\n    continue", 'requires an enclosing loop'],
	["def probe(stop: bool) -> int:\n    while stop:\n        break\n    else:\n        return 1\nreturn 1", 'Non-void function requires a value return'],
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


$text = "for key, value in make_mapping().items():\r\n    pass\r\nreturn 8";
$file = $frontend->parse(new source_file('items.py', $text, flavor::python));
$loop = $file->main()->body()->statements[0];
check($loop instanceof scpp\compiler\ast_foreach_statement && $loop->key?->source_text() === 'key'
	&& $loop->value->source_text() === 'value'
	&& $loop->source_value instanceof scpp\compiler\ast_call_expression
	&& $loop->source_value->source_text() === 'make_mapping()'
	&& $loop->source_text() === "for key, value in make_mapping().items():\r\n    pass\r\n",
	'Python items uses existing foreach ownership and retains source spans');
foreach ([
	['for x, x in h.items():', 'different names'],
	['for x, y, z in h.items():', 'Expected in'],
	['for x, y in h.items(1):', 'requires terminal .items()'],
	['for x, y in h:', 'requires terminal .items()'],
	['for x, y in h.items().extra():', 'requires terminal .items()'],
] as [$head, $message]) {
	try {
		$frontend->parse(new source_file('items.py', "$head\n    pass\nreturn 8", flavor::python));
		throw new RuntimeException('Expected Python items rejection');
	} catch (diagnostic $failure) {
		check(str_contains($failure->getMessage(), $message), $failure->getMessage());
	}
}


foreach ([
	["for fresh, value in h.items():\n    pass\nreturn fresh", 'Unknown or not-yet-initialized local/global: fresh'],
	["h.items()\nreturn 8", 'unknown method items'],
] as [$body, $message]) {
	try {
		$frontend->prepare(new source_file('items.py', "h: hash<int,int> = {1: 2}\n$body", flavor::python), new entry_target(31));
		throw new RuntimeException('Expected rejection of fresh key escape or standalone items');
	} catch (diagnostic $failure) {
		check(str_contains($failure->getMessage(), $message), $failure->getMessage());
	}
}

$text = "items: vector<int> = [1, 2,]\r\nmapping: hash<int,string> = {\r\n    \"k\": items[0],\r\n}\r\nitems[1] = mapping[\"k\"]\r\nreturn items[1]";
$file = $frontend->parse(new source_file('collections.py', $text, flavor::python));
[$vector, $hash, $write] = $file->main()->body()->statements;
check($vector->initializer instanceof scpp\compiler\ast_collection_expression
	&& $hash->initializer instanceof scpp\compiler\ast_keyed_collection_expression
	&& $hash->initializer->source_text() === "{\r\n    \"k\": items[0],\r\n}"
	&& $hash->initializer->entries[0]->value instanceof scpp\compiler\ast_index_expression
	&& $write instanceof scpp\compiler\ast_index_assignment
	&& $write->source_text() === "items[1] = mapping[\"k\"]\r\n",
	'Python collections and indexed writes use shared nodes and retain source spans');

foreach ([
	["for item in 1:\n    pass\nreturn 8", 'Python for requires a typed vector'],
	["text: string = \"a\"\nreturn text[0]", 'Provider does not support Python indexed reads'],
	["text: string = \"a\"\ntext[0] = 2\nreturn 8", 'Python indexed write requires a typed vector or hash'],
	["items: vector<int> = []\nitems[] = 1\nreturn 8", 'Python indexed write requires one key and ='],
	["items: vector<int> = {}\nreturn 8", 'Keyed initializer requires an explicitly typed published keyed destination'],
	["def probe(items: vector<int>) -> int:\n    for item in items:\n        pass\n    return item\nreturn 8", 'Unknown or not-yet-initialized local/global: item'],
	["def probe(items: vector<int>) -> int:\n    item: string = \"sentinel\"\n    for item in items:\n        pass\n    return 8\nreturn 8", 'conversion'],
	["missing += 1\nreturn 8", 'local'],
	["text: string = \"a\"\ntext += \"b\"\nreturn 8", 'Python compound destination requires a numeric scalar'],
	["value: int = 1\nvalue += True\nreturn 8", 'Python compound operand requires a numeric scalar'],
	["def fold(x: dynamic) -> int:\n    return x | 3\nreturn 0", 'Unsupported binary'],
	["def fold(x: mixed) -> int:\n    return 1 & x\nreturn 0", 'Unsupported binary'],
	["return 8 if 1 in 42 else 0", 'does not support Python membership'],
	["h: hash<int,string> = {}\nreturn 8 if 1 not in h else 0", 'key type string'],
	["h: hash<int,int> = {}\nreturn 8 if 1 in h is None else 0", 'Python None test'],
	["return 1 is None", 'Python None test'],
	["return None is False", 'literal None'],
	["return None is None + 1", 'Unsupported binary'],
	["return 1 is 1", 'two shared handles'],
	['return "same" is not "same"', 'two shared handles'],
	["v: vector<int> = []\nreturn v is v", 'two shared handles'],
	["def same(a: nullable<int>, b: nullable<int>) -> bool:\n    return a is b\nreturn 0", 'two shared handles'],
	["return 1 == 2 is None", 'Python None test'],
	["class Item:\n    pass\na: Item = Item()\nreturn a is not a is 3", 'two shared handles'],
	["def f(x: mixed) -> bool:\n    return x is None\nreturn 0", 'Python None test'],
	["def f(x: result_or_bool<int>) -> bool:\n    return x is not None\nreturn 0", 'Python None test'],
	["def f(x: result_or_false<int>) -> bool:\n    return x is None\nreturn 0", 'Python None test'],
	["return ~False", 'Unsupported unary'],
	["return 1 ^ 2.0", 'Unsupported binary'],
	["return True | False", 'Unsupported binary'],
	["def bad(&x: int) -> int:\n    return x\nreturn 0", 'identifier'],
	["def bad(x: A & B) -> int:\n    return 0\nreturn 0", 'typed parameters'],
	["def bad(x: A | B) -> int:\n    return 0\nreturn 0", 'typed parameters'],
	["value: int = 1\nvalue /= 2\nreturn 8", 'Unsupported punctuation'],
] as [$text, $message]) {
	try {
		$frontend->prepare(new source_file('traversal.py', $text, flavor::python), new entry_target(31));
		throw new RuntimeException('Expected rejection: ' . $text);
	} catch (diagnostic $failure) {
		check(str_contains(strtolower($failure->getMessage()), strtolower($message)), $failure->getMessage());
	}
}

$text = "def sum(items: vector<int>) -> int:\n    count: int = 0\n    for item in items:\n        count += item\n    return count\nreturn 8";
$file = $frontend->parse(new source_file('traversal.py', $text, flavor::python));
$functions = array_values(array_filter($file->declaration_nodes, static fn($node) => $node->short_name === 'sum'));
$loop = $functions[0]->body()->statements[1];
check($loop instanceof scpp\compiler\ast_foreach_statement && $loop->key === null
	&& $loop->value->short_name === 'item' && $loop->source_text() === "for item in items:\n        count += item\n",
	'Python traversal uses the shared value binding and retains its suite span');

$record = "class Box:\n    value: int = 0\n    def __init__(self, initial: int):\n        self.value = initial\n    def read(self) -> int:\n        return self.value\n";
$text = $record . "box: Box = Box(8)\nreturn box.read()";
$file = $frontend->parse(new source_file('record.py', $text, flavor::python));
$class = array_values(array_filter($file->declaration_nodes, static fn($node) => $node instanceof scpp\compiler\ast_record_declaration))[0];
check($class->source_text() === $record && count($class->methods['__construct']->parameters) === 1
	&& $class->methods['read']->parameters === [], 'Python class preserves its span and excludes self from signatures');
$write = $class->methods['__construct']->body()->statements[0];
check($write instanceof scpp\compiler\ast_field_assignment
	&& $write->target->receiver instanceof scpp\compiler\ast_this_expression, 'Self uses the shared receiver node');

$derived = "class Child(Base):\n    def __init__(self, value: int):\n        super().__init__(value)\n    @override\n    def read(self) -> int:\n        super().mark(2)\n        return super().read()\n";
$file = $frontend->parse(new source_file('derived.py', $derived . 'return 8', flavor::python));
$class = array_values(array_filter($file->declaration_nodes, static fn($node) => $node instanceof scpp\compiler\ast_record_declaration))[0];
$base = $class->methods['__construct']->body()->statements[0];
$read = $class->methods['read'];
check($class->source_text() === $derived && $class->base_reference?->name === 'Base'
	&& $base instanceof scpp\compiler\ast_base_statement && $base->value->explicit
	&& $base->source_text() === "super().__init__(value)\n"
	&& $read->is_override && $file->tokens->text_at($read->modifier_start) === '@',
	'Python inheritance preserves source spans on the shared record, base initializer and method marker');

[$statement, $returned] = $read->body()->statements;
foreach ([[$statement->value, 'super().mark(2)'], [$returned->value, 'super().read()']] as [$call, $spelling]) {
	check($call instanceof scpp\compiler\ast_call_expression && $call->receiver === null
		&& $call->qualifier === scpp\compiler\call_qualifier::lexical_parent && $call->source_text() === $spelling,
		'Python ancestor calls retain their authored spans and shared lexical qualifier');
}

$implicit = $frontend->parse(new source_file('implicit.py',
	str_replace('super().__init__(value)', 'super().mark(value)', $derived), flavor::python));
$child = array_values(array_filter($implicit->declaration_nodes, static fn($node) => $node instanceof scpp\compiler\ast_record_declaration))[0];
[$initializer, $mark] = $child->methods['__construct']->body()->statements;
check(!$initializer->value->explicit && $mark->value->qualifier === scpp\compiler\call_qualifier::lexical_parent,
	'A first ordinary super method call follows implicit base initialization');

foreach ([
	["return super().read()", 'requires a declaring record'],
	["class Box:\n    def read(self) -> int:\n        return super().read()\nreturn 8", 'requires a base type'],
	["return super(1).read()", ') after super'],
	["return super().field", 'Expected ('],
	["class Box:\n    def read() -> int:\n        return 1\nreturn 8", 'Expected self'],
	["class Box(Base):\n    pass\nreturn 8", 'Base must be a class'],
	["class Box:\n    def __init__(self):\n        return 1\nreturn 8", 'Void function cannot return a value'],
	[$record . "def Box(value: int) -> int:\n    return value\nreturn Box(8)", 'both function and class'],
	[$record . "box: Box = Box(8)\nbox.unknown = 4\nreturn 8", 'Unknown field'],
	["raise\nreturn 8", 'enclosing catch'],
	["raise 3\nreturn 8", 'shared descendant'],
	["try:\n    pass\nexcept Exception:\n    pass\nreturn 8", 'Expected as'],
	["try:\n    pass\nexcept:\n    pass\nreturn 8", 'type'],
	["try:\n    return 8\nfinally:\n    return 9\n", 'Return cannot leave a finally block'],
] as [$text, $message]) {
	try {
		$frontend->prepare(new source_file('record-errors.py', $text, flavor::python), new entry_target(31));
		throw new RuntimeException('Expected rejection: ' . $text);
	} catch (diagnostic $failure) {
		check(str_contains(strtolower($failure->getMessage()), strtolower($message)), $failure->getMessage());
	}
}
