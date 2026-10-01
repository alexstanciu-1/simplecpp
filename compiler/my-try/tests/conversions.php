<?php
namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';

/** Prepare one isolated source through the shared semantic and C++ paths. */
function conversion_test_source(string $text): parsed_file
{
	Compiler_Lifecycle::reset();
	$input = new file();
	$input->path = 'conversion.phs';
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

$syntax = conversion_test_source('$a int = 1; $b uint8 = 2; $a = $a; $b = $a; return $b;');
$statements /** Storage<statement_node> */ = $syntax->root->body->statements;
$identity_declaration = object_cast($statements[0], variable_declaration_node::class)->require_preparation()->conversion;
$integer_declaration = object_cast($statements[1], variable_declaration_node::class)->require_preparation()->conversion;
$identity_assignment = object_cast(object_cast($statements[2], expression_statement_node::class)->expression,
	assignment_expression_node::class)->require_assignment_preparation()->binding->conversion;
$integer_assignment = object_cast(object_cast($statements[3], expression_statement_node::class)->expression,
	assignment_expression_node::class)->require_assignment_preparation()->binding->conversion;
if (($identity_declaration === null) || ($identity_declaration->operation !== conversion_operation::identity)
	|| ($integer_declaration === null) || ($integer_declaration->operation !== conversion_operation::integer_value_cast)
	|| ($identity_assignment === null) || ($identity_assignment->operation !== conversion_operation::identity)
	|| ($integer_assignment === null) || ($integer_assignment->operation !== conversion_operation::integer_value_cast)
	|| (!$integer_assignment->result_type->matches($integer_assignment->target_type))) {
	throw new \LogicException('Assignment conversion decisions were not attached to their prepared consumers');
}
$text = Model::$cpp_files[0]->text;
if (!str_contains($text, 'scpp::int_t<std::uint8_t> local_b = static_cast<scpp::int_t<std::uint8_t>>')
	|| !str_contains($text, "\tlocal_a = local_a;\n")
	|| !str_contains($text, 'local_b = static_cast<scpp::int_t<std::uint8_t>>((local_a).native_value());')) {
	throw new \LogicException('C++ storage emission did not consume prepared conversion decisions');
}

$syntax = conversion_test_source('$a uint8 = 1; $b byte = $a; return $b;');
$statements = $syntax->root->body->statements;
$alias = object_cast($statements[1], variable_declaration_node::class)->require_preparation()->conversion;
if (($alias === null) || ($alias->operation !== conversion_operation::identity)
	|| !$alias->source_type->matches($alias->target_type)) {
	throw new \LogicException('Canonical byte/uint8 identity selected an unnecessary cast');
}

$failed = false;
try {
	conversion_test_source('$a int = false;');
}
catch (\RuntimeException $error) {
	$failed = str_contains($error->getMessage(), 'value boundary requires matching types or an integer conversion');
}
if (!$failed) {
	throw new \LogicException('Unsupported assignment conversion was not rejected during preparation');
}

$syntax = conversion_test_source(
	'function narrow(int32 $value): uint8 { return $value; } $source int64 = 7; return narrow($source);');
$declarations /** Storage<declaration_node> */ = $syntax->root->declarations;
$function = object_cast($declarations[0], function_node::class);
$function_statements /** Storage<statement_node> */ = $function->body->statements;
$function_return = object_cast($function_statements[0], return_node::class);
$return_decision = $function_return->require_preparation()->require_conversion();
$entry_statements /** Storage<statement_node> */ = $syntax->root->body->statements;
$entry_return = object_cast($entry_statements[1], return_node::class);
$call = object_cast($entry_return->expression, call_node::class);
$call_arguments /** Storage<prepared_call_argument> */ = $call->require_call_preparation()->arguments;
$argument_decision = $call_arguments[0]->require_conversion();
if (($return_decision->context !== conversion_context::return_value)
	|| ($return_decision->operation !== conversion_operation::integer_value_cast)
	|| ($argument_decision->context !== conversion_context::argument)
	|| ($argument_decision->operation !== conversion_operation::integer_value_cast)) {
	throw new \LogicException('Call or return conversion did not retain its prepared context and operation');
}
$text = Model::$cpp_files[0]->text;
if (!str_contains($text, 'return static_cast<scpp::int_t<std::uint8_t>>((local_value).native_value());')
	|| !str_contains($text, 'scpp::int_t<std::int32_t> argument_0 = static_cast<scpp::int_t<std::int32_t>>((local_source).native_value());')) {
	throw new \LogicException('Call or return lowering did not consume its prepared conversion decision');
}

$syntax = conversion_test_source('function observe(int &$value): void {} $value = 1; observe($value);');
$entry_statements = $syntax->root->body->statements;
$call_statement = object_cast($entry_statements[1], expression_statement_node::class);
$reference_call = object_cast($call_statement->expression, call_node::class);
$reference_arguments /** Storage<prepared_call_argument> */ = $reference_call->require_call_preparation()->arguments;
if ($reference_arguments[0]->conversion !== null) {
	throw new \LogicException('Reference argument incorrectly acquired a value conversion');
}

$scalar_sources = [
	'bool' => 'true',
	'int' => '7',
	'float' => '7.5',
	'string' => '"7"',
];
$cast_source = '';
foreach ($scalar_sources as $name => $literal) {
	$cast_source .= '$source_' . $name . ' = ' . $literal . '; ';
}
foreach ($scalar_sources as $target => $unused) {
	foreach ($scalar_sources as $source => $literal) {
		$cast_source .= '$cast_' . $target . '_from_' . $source
			. ' = (' . $target . ')$source_' . $source . '; ';
	}
}
$cast_source .= '$fixed = (uint8)$source_int; return 0;';
$syntax = conversion_test_source($cast_source);
$statements = $syntax->root->body->statements;
$position = q_count($scalar_sources);
$runtime_casts = 0;
foreach ($scalar_sources as $target => $unused)
{
	foreach ($scalar_sources as $source => $literal)
	{
		$assignment = object_cast(object_cast($statements[$position], expression_statement_node::class)->expression,
			assignment_expression_node::class);
		$cast = object_cast($assignment->value, cast_expression_node::class);
		$decision = $cast->require_cast_preparation()->conversion;
		$expected = $target === $source ? conversion_operation::identity
			: conversion_operation::explicit_runtime_cast;
		if (($decision->context !== conversion_context::explicit_cast)
			|| ($decision->operation !== $expected)
			|| (!$cast->require_preparation()->type->matches($decision->target_type))) {
			throw new \LogicException('Scalar cast matrix lost its target-family decision');
		}
		if ($decision->requires_cast()) {
			$runtime_casts++;
		}
		$position++;
	}
}
$fixed_assignment = object_cast(object_cast($statements[$position], expression_statement_node::class)->expression,
	assignment_expression_node::class);
$fixed_cast = object_cast($fixed_assignment->value, cast_expression_node::class);
if ($fixed_cast->require_cast_preparation()->conversion->operation
	!== conversion_operation::explicit_runtime_cast) {
	throw new \LogicException('Registered fixed-width integer cast target was not resolved generically');
}
$runtime_casts++;
$text = Model::$cpp_files[0]->text;
if (($runtime_casts !== 13) || (substr_count($text, 'scpp::cast<') !== $runtime_casts)
	|| (substr_count($text, '#include "scpp/cast.hpp"') !== 1)) {
	throw new \LogicException('Scalar cast emission did not match prepared identity/runtime decisions');
}

$syntax = conversion_test_source(
	'function source(): float { return 2.5; } $value = (int)source(); $nested = (string)(float)$value; '
	. '$sum = (int)2.5 + 1; return $value;');
$statements = $syntax->root->body->statements;
$value_assignment = object_cast(object_cast($statements[0], expression_statement_node::class)->expression,
	assignment_expression_node::class);
$value_cast = object_cast($value_assignment->value, cast_expression_node::class);
$nested_assignment = object_cast(object_cast($statements[1], expression_statement_node::class)->expression,
	assignment_expression_node::class);
$outer_cast = object_cast($nested_assignment->value, cast_expression_node::class);
$inner_cast = object_cast($outer_cast->operand, cast_expression_node::class);
$sum_assignment = object_cast(object_cast($statements[2], expression_statement_node::class)->expression,
	assignment_expression_node::class);
$sum = object_cast($sum_assignment->value, binary_expression_node::class);
if (!($value_cast->operand instanceof call_node) || !($sum->left instanceof cast_expression_node)
	|| ($outer_cast->require_cast_preparation()->conversion->operation !== conversion_operation::explicit_runtime_cast)
	|| ($inner_cast->require_cast_preparation()->conversion->operation !== conversion_operation::explicit_runtime_cast)) {
	throw new \LogicException('Explicit cast operand, nesting or binary precedence changed');
}
$text = Model::$cpp_files[0]->text;
if ((substr_count($text, 'return function_source();') !== 1)
	|| !str_contains($text, 'scpp::cast<scpp::string_t>(scpp::cast<scpp::float_t>(local_value))')
	|| !str_contains($text, '(scpp::cast<scpp::int_t<>>(static_cast<scpp::float_t>(2.5)) + static_cast<scpp::int_t<>>(1LL))')) {
	throw new \LogicException('Explicit cast lowering lost single evaluation, nesting or precedence');
}

$failed = false;
try {
	conversion_test_source('struct Box {} $box Box; $value = (int)$box;');
}
catch (\RuntimeException $error) {
	$failed = str_contains($error->getMessage(), 'explicit integer cast is not supported');
}
if (!$failed) {
	throw new \LogicException('Unsupported nominal-to-scalar cast was not rejected during preparation');
}

$failed = false;
try {
	conversion_test_source('struct Box {} $box Box; $value = (Box)$box;');
}
catch (\RuntimeException $error) {
	$failed = str_contains($error->getMessage(), 'explicit cast target is not supported');
}
if (!$failed) {
	throw new \LogicException('Unsupported nominal cast target passed through identity handling');
}

echo "conversion preparation tests passed\n";
