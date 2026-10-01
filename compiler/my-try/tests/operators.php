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

$rejections = [
	'boolean operand' => ['$value = 1 + true;', 'integer arithmetic operation requires canonical int operands'],
	'narrow operand' => ['$left uint8 = 1; $value = $left + 2;', 'integer arithmetic operation requires canonical int operands'],
	'effectful operand' => ['function value(): int { return 1; } $result = value() + 2;', 'integer arithmetic operation requires order-independent operands'],
	'subtraction boolean' => ['$value = 1 - true;', 'integer arithmetic operation requires canonical int operands'],
	'subtraction float' => ['$value = 3.0 - 1;', 'integer arithmetic operation requires canonical int operands'],
	'subtraction string' => ['$value = 3 - "1";', 'integer arithmetic operation requires canonical int operands'],
	'subtraction width' => ['$left uint8 = 3; $value = $left - 1;', 'integer arithmetic operation requires canonical int operands'],
	'subtraction call' => ['function value(): int { return 1; } $result = 2 - value();', 'integer arithmetic operation requires order-independent operands'],
	'nested subtraction call' => ['function value(): int { return 1; } $result = 2 + (3 - value());', 'integer arithmetic operation requires order-independent operands'],
	'unary minus' => ['$value = -1;', 'Expected scalar literal or variable reference'],
	'decrement' => ['$value = 1; $value--;', 'Expected type name or = after variable name'],
	'compound subtraction' => ['$value = 1; $value -= 1;', 'Expected type name or = after variable name'],
	'multiplication boolean' => ['$value = true * 2;', 'integer arithmetic operation requires canonical int operands'],
	'multiplication float' => ['$value = 2 * 3.5;', 'integer arithmetic operation requires canonical int operands'],
	'multiplication string' => ['$value = "2" * 3;', 'integer arithmetic operation requires canonical int operands'],
	'multiplication width' => ['$x uint8 = 2; $value = $x * 3;', 'integer arithmetic operation requires canonical int operands'],
	'multiplication call' => ['function value(): int { return 2; } $result = 1 + 3 * value();', 'integer arithmetic operation requires order-independent operands'],
	'compound multiplication' => ['$value = 1; $value *= 2;', 'Expected type name or = after variable name'],
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

echo "operator preparation tests passed\n";
