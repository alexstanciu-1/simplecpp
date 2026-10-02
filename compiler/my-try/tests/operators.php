<?php
namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';

/** Prepare one isolated operator source through shared semantics and C++ lowering. */
function operator_test_source(string $text): parsed_file
{
	Compiler_Lifecycle::reset();
	$input = new file();
	$input->path = 'operator.phs';
	$input->content = $text;
	$syntax = (new Parser((new Tokenizer($input))->tokenize()))->parse();
	$module = new module('.', Source_Registry::normalize('.'), '.');
	$modules /** Keyed_Storage<module> */ = Model::$modules;
	$modules->add($module->name, $module);
	$record = Source_Registry::add($module, $input);
	Source_Publication::publish_parsed($record, $syntax);
	$compiler = new Compiler();
	$compiler->prepare();
	$compiler->cpp();
	return $syntax;
}

$syntax = operator_test_source('$a = 1; $b = $a + 2; return $b;');
$statements /** Storage<statement_node> */ = $syntax->root->body->statements;
$assignment = object_cast(object_cast($statements[1], expression_statement_node::class)->expression,
assignment_expression_node::class);
$binary = object_cast($assignment->value, binary_expression_node::class);
$decision = $binary->require_binary_preparation()->decision;
$integer = Language_Types::integer(Model::$language_scope);
if (($decision->source_operator !== operator_kind::addition)
|| ($decision->operation !== operator_operation::integer_addition)
|| (q_count($decision->operands) !== 2) || (!$decision->result_type->matches($integer))) {
	throw new \LogicException('Integer addition did not retain its selected operator shape');
}
foreach ($decision->operands as $operand) {
	if (($operand->context !== conversion_context::operator_operand)
	|| ($operand->operation !== conversion_operation::identity)
	|| (!$operand->target_type->matches($integer))) {
		throw new \LogicException('Integer addition lost an ordered identity operand conversion');
	}
}
$text = Model::$cpp_files[0]->text;
if (!str_contains($text, 'auto local_b = (local_a + static_cast<scpp::int_t<>>(2LL));')
|| !str_contains($text, '#include "scpp/generated/operators.hpp"')) {
	throw new \LogicException('C++ addition did not consume the prepared operator decision');
}

$syntax = operator_test_source('$a = 1 + 2 + 3; return $a;');
$statements = $syntax->root->body->statements;
$assignment = object_cast(object_cast($statements[0], expression_statement_node::class)->expression,
assignment_expression_node::class);
$outer = object_cast($assignment->value, binary_expression_node::class);
$inner = object_cast($outer->left, binary_expression_node::class);
if (($inner->require_binary_preparation()->decision->operation !== operator_operation::integer_addition)
|| ($outer->require_binary_preparation()->decision->operation !== operator_operation::integer_addition)
|| !str_contains(Model::$cpp_files[0]->text,
'auto local_a = ((static_cast<scpp::int_t<>>(1LL) + static_cast<scpp::int_t<>>(2LL)) + static_cast<scpp::int_t<>>(3LL));')) {
	throw new \LogicException('Left-associative addition chain lost recursive operator decisions');
}

$syntax = operator_test_source('$a = 10; $b = $a - 3; return $b;');
$statements = $syntax->root->body->statements;
$assignment = object_cast(object_cast($statements[1], expression_statement_node::class)->expression,
assignment_expression_node::class);
$binary = object_cast($assignment->value, binary_expression_node::class);
$decision = $binary->require_binary_preparation()->decision;
$integer = Language_Types::integer(Model::$language_scope);
if (($decision->source_operator !== operator_kind::subtraction)
|| ($decision->operation !== operator_operation::integer_subtraction)
|| (q_count($decision->operands) !== 2) || (!$decision->result_type->matches($integer))) {
	throw new \LogicException('Subtraction lost its canonical operation or result type');
}
foreach ($decision->operands as $operand) {
	if (($operand->context !== conversion_context::operator_operand)
	|| ($operand->operation !== conversion_operation::identity)
	|| (!$operand->source_type->matches($integer)) || (!$operand->target_type->matches($integer))) {
		throw new \LogicException('Subtraction lost its ordered identity operand conversions');
	}
}
if (!str_contains(Model::$cpp_files[0]->text, 'auto local_b = (local_a - static_cast<scpp::int_t<>>(3LL));')) {
	throw new \LogicException('Subtraction did not lower its selected operation');
}

// Mixed additive chains share precedence; grouping changes the existing binary tree.
$syntax = operator_test_source('return 10 - 3 + 2;');
$outer = $syntax->root->body->statements[0]->expression;
if (($outer->require_binary_preparation()->decision->operation !== operator_operation::integer_addition)
|| ($outer->left->require_binary_preparation()->decision->operation !== operator_operation::integer_subtraction)) {
	throw new \LogicException('Addition and subtraction do not share left associativity');
}
$syntax = operator_test_source('return 10 - (3 - 2);');
$outer = $syntax->root->body->statements[0]->expression;
if (($outer->left instanceof binary_expression_node)
|| ($outer->right->require_binary_preparation()->decision->operation !== operator_operation::integer_subtraction)) {
	throw new \LogicException('Subtraction lost right grouping');
}

// Multiplication retains the same operand facts and forms a tighter binary subtree.
$syntax = operator_test_source('return 2 + 3 * 4 - 5;');
$outer = $syntax->root->body->statements[0]->expression;
$sum = $outer->left;
$product = $sum->right;
$decision = $product->require_binary_preparation()->decision;
$integer = Language_Types::integer(Model::$language_scope);
if (($outer->require_binary_preparation()->decision->operation !== operator_operation::integer_subtraction)
|| ($sum->require_binary_preparation()->decision->operation !== operator_operation::integer_addition)
|| ($decision->source_operator !== operator_kind::multiplication)
|| ($decision->operation !== operator_operation::integer_multiplication)
|| (!$decision->result_type->matches($integer)) || (count($decision->operands) !== 2)) {
	throw new \LogicException('Multiplication lost precedence or its selected canonical operation');
}
foreach ($decision->operands as $operand) {
	if (($operand->context !== conversion_context::operator_operand)
	|| ($operand->operation !== conversion_operation::identity)
	|| (!$operand->source_type->matches($integer)) || (!$operand->target_type->matches($integer))) {
		throw new \LogicException('Multiplication lost its identity operand conversions');
	}
}
$syntax = operator_test_source('return 2 * 3 * 4;');
$outer = $syntax->root->body->statements[0]->expression;
if (!($outer->left instanceof binary_expression_node) || ($outer->right instanceof binary_expression_node)) {
	throw new \LogicException('Multiplication chain lost left associativity');
}
$syntax = operator_test_source('return (2 + 3) * 4;');
$product = $syntax->root->body->statements[0]->expression;
if (($product->require_binary_preparation()->decision->operation !== operator_operation::integer_multiplication)
|| ($product->left->require_binary_preparation()->decision->operation !== operator_operation::integer_addition)) {
	throw new \LogicException('Grouping did not override multiplication precedence');
}

// All admitted integer pairs share conversion preparation; comparisons retain bool results.
$binary_cases = [
	['&', operator_kind::bitwise_and, operator_operation::integer_bitwise_and, false],
	['|', operator_kind::bitwise_or, operator_operation::integer_bitwise_or, false],
	['^', operator_kind::bitwise_xor, operator_operation::integer_bitwise_xor, false],
	['<<', operator_kind::shift_left, operator_operation::integer_shift_left, false],
	['>>', operator_kind::shift_right, operator_operation::integer_shift_right, false],
	['<=>', operator_kind::three_way, operator_operation::integer_three_way, false],
	['/', operator_kind::division, operator_operation::integer_division, false],
	['%', operator_kind::remainder, operator_operation::integer_remainder, false],
	['==', operator_kind::equal, operator_operation::integer_equal, true],
	['!=', operator_kind::not_equal, operator_operation::integer_not_equal, true],
	['===', operator_kind::identical, operator_operation::integer_identical, true],
	['!==', operator_kind::not_identical, operator_operation::integer_not_identical, true],
	['<', operator_kind::less, operator_operation::integer_less, true],
	['<=', operator_kind::less_equal, operator_operation::integer_less_equal, true],
	['>', operator_kind::greater, operator_operation::integer_greater, true],
	['>=', operator_kind::greater_equal, operator_operation::integer_greater_equal, true],
];
foreach ($binary_cases as [$symbol, $kind, $operation, $comparison])
{
	$syntax = operator_test_source('return 7 ' . $symbol . ' 3;');
	$decision = $syntax->root->body->statements[0]->expression->require_binary_preparation()->decision;
	$integer = Language_Types::integer(Model::$language_scope);
	$result = $comparison ? Language_Types::boolean(Model::$language_scope) : $integer;
	if (($decision->source_operator !== $kind) || ($decision->operation !== $operation)
	|| (!$decision->result_type->matches($result)) || (count($decision->operands) !== 2)) {
		throw new \LogicException('Binary decision or result type lost for ' . $symbol);
	}
	foreach ($decision->operands as $operand) {
		if (($operand->operation !== conversion_operation::identity)
		|| ($operand->context !== conversion_context::operator_operand)
		|| (!$operand->source_type->matches($integer)) || (!$operand->target_type->matches($integer))) {
			throw new \LogicException('Operand conversion lost for ' . $symbol);
		}
	}
	if ($kind === operator_kind::identical) {
		if (!str_contains(Model::$cpp_files[0]->text, 'scpp::php::identical(')) {
			throw new \LogicException('Strict identity bypassed its runtime helper');
		}
	}
}
$syntax = operator_test_source('return 1 + 2 * 3 == 7;');
$comparison = $syntax->root->body->statements[0]->expression;
if (($comparison->require_binary_preparation()->decision->operation !== operator_operation::integer_equal)
|| ($comparison->left->require_binary_preparation()->decision->operation !== operator_operation::integer_addition)) {
	throw new \LogicException('Comparison did not bind below arithmetic');
}
$syntax = operator_test_source('return PHP_INT_MAX < 1;');
if (!($syntax->root->body->statements[0]->expression->left instanceof constant_reference_node)) {
	throw new \LogicException('Constant followed by relational operator became a template call');
}

$syntax = operator_test_source('$a = "a" . "b";');
$assignment = $syntax->root->body->statements[0]->expression;
$decision = $assignment->value->require_binary_preparation()->decision;
$string_type = Language_Types::string_type(Model::$language_scope);
if (($decision->source_operator !== operator_kind::concatenation)
|| ($decision->operation !== operator_operation::string_concatenation)
|| (!$decision->result_type->matches($string_type))) {
	throw new \LogicException('Concatenation lost its string candidate and result type');
}
foreach ($decision->operands as $operand) {
	if (($operand->operation !== conversion_operation::identity)
	|| ($operand->context !== conversion_context::operator_operand)
	|| (!$operand->target_type->matches($string_type))) {
		throw new \LogicException('Concatenation lost its string operand boundary');
	}
}

$syntax = operator_test_source('return true || false && false;');
$outer = $syntax->root->body->statements[0]->expression;
$decision = $outer->require_binary_preparation()->decision;
if (($decision->operation !== operator_operation::boolean_or)
|| ($outer->right->require_binary_preparation()->decision->operation !== operator_operation::boolean_and)
|| (!$decision->result_type->matches(Language_Types::boolean(Model::$language_scope)))) {
	throw new \LogicException('Logical precedence or result type was lost');
}
foreach ($decision->operands as $operand) {
	if (($operand->operation !== conversion_operation::identity)
	|| ($operand->context !== conversion_context::operator_operand)) {
		throw new \LogicException('Logical operands bypassed conversion preparation');
	}
}
if (!str_contains(Model::$cpp_files[0]->text, 'scpp::bool_t(static_cast<bool>(')) {
	throw new \LogicException('Logical lowering did not select the native short-circuit bridge');
}


// Unary syntax retains one owned child and one ordinary conversion decision.
foreach ([['+', '3', operator_operation::integer_positive],
	['-', '3', operator_operation::integer_negative],
	['~', '3', operator_operation::integer_complement],
	['!', 'false', operator_operation::boolean_not]] as [$symbol, $literal, $operation])
{
	$syntax = operator_test_source('return ' . $symbol . $literal . ';');
	$unary = $syntax->root->body->statements[0]->expression;
	$decision = $unary->require_unary_preparation()->decision;
	$expected = $symbol === '!' ? Language_Types::boolean(Model::$language_scope) : Language_Types::integer(Model::$language_scope);
	if (($decision->operation !== $operation) || (count($decision->operands) !== 1)
		|| !$decision->result_type->matches($expected) || $unary->require_preparation()->addressable
		|| ($decision->operands[0]->operation !== conversion_operation::identity)
		|| ($decision->operands[0]->context !== conversion_context::operator_operand)) {
		throw new \LogicException('Unary operation lost its value/conversion contract');
	}
	if (($unary->kind() !== node_kind::unary_expression) || ($unary->start_token() !== 1)
		|| ($unary->end_token() !== 3) || ($syntax->tokens->text_at($unary->operator_token_index) !== $symbol)) {
		throw new \LogicException('Unary syntax lost its kind, operator position or source span');
	}
	$children = iterator_to_array($unary->children());
	if ($children !== [$unary->operand]) {
		throw new \LogicException('Unary inspection lost the owned operand');
	}
	Preparation_Cleanup::tree($syntax->root);
	if ($unary->preparation() !== null) {
		throw new \LogicException('Unary cleanup retained stale facts');
	}
}
$syntax = operator_test_source('return -2 * 3;');
$binary = $syntax->root->body->statements[0]->expression;
if (!($binary instanceof binary_expression_node) || !($binary->left instanceof unary_expression_node)) {
	throw new \LogicException('Unary sign did not bind tighter than multiplication');
}
$syntax = operator_test_source('return -(2 * 3);');
$unary = $syntax->root->body->statements[0]->expression;
if (!($unary instanceof unary_expression_node) || !($unary->operand instanceof binary_expression_node)) {
	throw new \LogicException('Unary operand lost grouping');
}

$rejections = [
	'bitwise float' => ['return 1 & 2.0;', 'canonical int operands'],
	'bitwise string' => ['return "a" | "b";', 'canonical int operands'],
	'bitwise bool' => ['return true ^ false;', 'canonical int operands'],
	'shift width' => ['$x uint8 = 1; return $x << 2;', 'canonical int operands'],
	'shift count type' => ['return 1 >> true;', 'canonical int operands'],
	'bitwise comparison precedence' => ['return 1 & 1 == 1;', 'canonical int operands'],
	'shift spaced punctuation' => ['return 1 < < 2;', 'Expected scalar literal'],
	'shift effect' => ['$x = 1; return $x++ << 2;', 'order-independent operands'],
	'unary positive float' => ['return +1.5;', 'unary operation requires'],
	'unary float' => ['return -1.5;', 'unary operation requires'],
	'unary bool' => ['return +true;', 'unary operation requires'],
	'unary complement string' => ['return ~"x";', 'unary operation requires'],
	'unary not int' => ['return !1;', 'unary operation requires'],
	'unary narrow' => ['$x uint8 = 1; return -$x;', 'unary operation requires'],
	'unary oversized magnitude' => ['return -9223372036854775808;', 'exceeds signed 64-bit'],
	'unary call' => ['function value(): int { return 1; } return -value();', 'order-independent operands'],
	'nested unary call' => ['function value(): int { return 1; } return 2 + -value();', 'order-independent operands'],

	'boolean operand' => ['$value = 1 + true;', 'integer binary operation requires canonical int operands'],
	'narrow operand' => ['$left uint8 = 1; $value = $left + 2;', 'integer binary operation requires canonical int operands'],
	'effectful operand' => ['function value(): int { return 1; } $result = value() + 2;', 'binary operation requires order-independent operands'],
	'subtraction boolean' => ['$value = 1 - true;', 'integer binary operation requires canonical int operands'],
	'subtraction float' => ['$value = 3.0 - 1;', 'integer binary operation requires canonical int operands'],
	'subtraction string' => ['$value = 3 - "1";', 'integer binary operation requires canonical int operands'],
	'subtraction width' => ['$left uint8 = 3; $value = $left - 1;', 'integer binary operation requires canonical int operands'],
	'subtraction call' => ['function value(): int { return 1; } $result = 2 - value();', 'binary operation requires order-independent operands'],
	'nested subtraction call' => ['function value(): int { return 1; } $result = 2 + (3 - value());', 'binary operation requires order-independent operands'],
	'multiplication boolean' => ['$value = true * 2;', 'integer binary operation requires canonical int operands'],
	'multiplication float' => ['$value = 2 * 3.5;', 'integer binary operation requires canonical int operands'],
	'multiplication string' => ['$value = "2" * 3;', 'integer binary operation requires canonical int operands'],
	'multiplication width' => ['$x uint8 = 2; $value = $x * 3;', 'integer binary operation requires canonical int operands'],
	'multiplication call' => ['function value(): int { return 2; } $result = 1 + 3 * value();', 'binary operation requires order-independent operands'],
	'division float' => ['return 7 / 3.0;', 'integer binary operation requires canonical int operands'],
	'remainder width' => ['$x uint8 = 7; return $x % 3;', 'integer binary operation requires canonical int operands'],
	'comparison string' => ['return 3 == "3";', 'integer binary operation requires canonical int operands'],
	'comparison bool' => ['return 1 === true;', 'integer binary operation requires canonical int operands'],
	'comparison float' => ['return 1 < 2.0;', 'integer binary operation requires canonical int operands'],
	'comparison call' => ['function value(): int { return 1; } return value() < 2;', 'binary operation requires order-independent operands'],
	'relational chain' => ['return 1 < 2 < 3;', 'integer binary operation requires canonical int operands'],
	'implicit concatenation' => ['$a = "x" . 1;', 'concatenation requires string operands'],
	'constant concatenation' => ['$a = (PHP_INT_MAX) . "x";', 'concatenation requires string operands'],
	'concatenation call' => ['function value(): string { return "a"; } $a = "x" . value();', 'binary operation requires order-independent operands'],
	'logical non-bool' => ['return false && 1;', 'logical operation requires canonical bool operands'],
	'logical skipped unknown' => ['return false && $missing;', 'established local declaration for missing'],
	'power' => ['$value = 2 ** 3;', 'Expected scalar literal or variable reference'],
];
foreach ($rejections as $name => [$source, $diagnostic])
{
	$failed = false;
	try {
		operator_test_source($source);
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $diagnostic);
	}
	if (!$failed || !Model::$cpp_files->is_empty() || !Model::$prepared_files->is_empty()) {
		throw new \LogicException('Invalid operator ' . $name . ' did not reject before publication');
	}
}

// Equal-length operator edits must refresh decisions; token movement must preserve them.
$directory = sys_get_temp_dir() . '/scpp_operators_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
$initial = 'function value(): int { return 10 + 3; } return value();';
try
{
	file_put_contents($path, $initial);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$function = Model::$global_scope->functions_named('value')[0]->syntax();
	$signature = $function->require_preparation();
	$changed = str_replace('10 + 3', '10 - 3', $initial);
	file_put_contents($path, $changed);
	$compiler->update_cpp([$path]);
	$body = $function->body;
	$binary = $body->statements[0]->expression;
	if (($binary->require_binary_preparation()->decision->operation !== operator_operation::integer_subtraction)
	|| ($function->require_preparation() !== $signature)) {
		throw new \LogicException('Operator edit lost its new decision or invalidated an unchanged signature');
	}
	$changed = str_replace('10 - 3', '10 * 3', $changed);
	file_put_contents($path, $changed);
	$compiler->update_cpp([$path]);
	$body = $function->body;
	$binary = $body->statements[0]->expression;
	if (($binary->require_binary_preparation()->decision->operation !== operator_operation::integer_multiplication)
	|| ($function->require_preparation() !== $signature)) {
		throw new \LogicException('Multiplication edit retained a stale decision or changed the signature');
	}
	$changed = 'function before(): int { return 0; } ' . $changed;
	file_put_contents($path, $changed);
	$compiler->update_cpp([$path]);
	$compiler->cleanup_tokens();
	$tokens = Model::tokens()[0];
	if (($function->body !== $body) || ($tokens->text_at($binary->operator_token_index) !== '*')) {
		throw new \LogicException('Token cleanup lost the retained multiplication operator');
	}
	$incremental = Model::$cpp_files[0]->text;
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	if (Model::$cpp_files[0]->text !== $incremental) {
		throw new \LogicException('Incremental arithmetic output differs from a fresh build');
	}
}
finally {
	unlink($path);
	rmdir($directory);
}

// Equal-length operator edits must refresh decisions; token movement must preserve them.
$directory = sys_get_temp_dir() . '/scpp_unary_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
$initial = 'function value(): int { return +3; } return value();';
try
{
	file_put_contents($path, $initial);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$function = Model::$global_scope->functions_named('value')[0]->syntax();
	$signature = $function->require_preparation();
	$changed = str_replace('+3', '-3', $initial);
	file_put_contents($path, $changed);
	$compiler->update_cpp([$path]);
	$body = $function->body;
	$unary = $body->statements[0]->expression;
	if (($unary->require_unary_preparation()->decision->operation !== operator_operation::integer_negative)
		|| ($function->require_preparation() !== $signature)) {
		throw new \LogicException('Operator edit lost its new decision or invalidated an unchanged signature');
	}
	$changed = str_replace('-3', '~3', $changed);
	file_put_contents($path, $changed);
	$compiler->update_cpp([$path]);
	$body = $function->body;
	$unary = $body->statements[0]->expression;
	if (($unary->require_unary_preparation()->decision->operation !== operator_operation::integer_complement)
		|| ($function->require_preparation() !== $signature)) {
		throw new \LogicException('Unary complement edit retained a stale decision or changed the signature');
	}
	$changed = 'function before(): int { return 0; } ' . $changed;
	file_put_contents($path, $changed);
	$compiler->update_cpp([$path]);
	$compiler->cleanup_tokens();
	$tokens = Model::tokens()[0];
	if (($function->body !== $body) || ($tokens->text_at($unary->operator_token_index) !== '~')) {
		throw new \LogicException('Token cleanup lost the retained unary operator');
	}
	$incremental = Model::$cpp_files[0]->text;
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	if (Model::$cpp_files[0]->text !== $incremental) {
		throw new \LogicException('Incremental unary output differs from a fresh build');
	}
}
finally {
	unlink($path);
	rmdir($directory);
}

echo "operator preparation tests passed\n";
