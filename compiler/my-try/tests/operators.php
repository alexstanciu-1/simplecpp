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

$rejections = [
	'boolean operand' => ['$value = 1 + true;', 'integer addition requires canonical int operands'],
	'narrow operand' => ['$left uint8 = 1; $value = $left + 2;', 'integer addition requires canonical int operands'],
	'effectful operand' => ['function value(): int { return 1; } $result = value() + 2;', 'integer addition requires order-independent operands'],
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

echo "operator preparation tests passed\n";
