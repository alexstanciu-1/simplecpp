<?php
namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';

/** Prepare one isolated operator source through shared semantics and C++ lowering. */
function compound_test_source(string $text): parsed_file
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



foreach ([['&=', '7', '3', operator_operation::integer_bitwise_and],
	['|=', '7', '3', operator_operation::integer_bitwise_or],
	['^=', '7', '3', operator_operation::integer_bitwise_xor],
	['<<=', '7', '3', operator_operation::integer_shift_left],
	['>>=', '7', '3', operator_operation::integer_shift_right],
	['+=', '3', '2', operator_operation::integer_addition],
	['-=', '3', '2', operator_operation::integer_subtraction],
	['*=', '3', '2', operator_operation::integer_multiplication],
	['/=', '3', '2', operator_operation::integer_division],
	['%=', '3', '2', operator_operation::integer_remainder],
	['.=', '"a"', '"b"', operator_operation::string_concatenation]] as [$symbol, $initial, $right, $operation])
{
	$syntax = compound_test_source('$x = ' . $initial . '; $a = ($x ' . $symbol . ' ' . $right . '); return 0;');
	$compound = $syntax->root->body->statements[1]->expression->value;
	$facts = $compound->require_compound_assignment_preparation();
	$expected = $symbol === '.=' ? Language_Types::string_type(Model::$language_scope) : Language_Types::integer(Model::$language_scope);
	if (($compound->kind() !== node_kind::compound_assignment_expression)
		|| ($facts->decision->operation !== $operation) || !$facts->type->matches($expected)
		|| $facts->addressable || (count($facts->decision->operands) !== 2)
		|| ($facts->write_back->context !== conversion_context::assignment)
		|| ($facts->write_back->operation !== conversion_operation::identity)
		|| !$facts->write_back->target_type->matches($expected)) {
		throw new \LogicException('Compound update lost computation, write-back or value-result facts');
	}
	foreach ($facts->decision->operands as $operand) {
		if (($operand->context !== conversion_context::operator_operand)
			|| ($operand->operation !== conversion_operation::identity)) {
			throw new \LogicException('Compound update bypassed operand conversion preparation');
		}
	}
	if (iterator_to_array($compound->children()) !== [$compound->target, $compound->value]) {
		throw new \LogicException('Compound inspection lost target/RHS order');
	}
	if ($syntax->tokens->text_at($compound->operator_token_index) !== $symbol) {
		throw new \LogicException('Compound update lost its operator token');
	}
	Preparation_Cleanup::tree($syntax->root);
	if (($compound->preparation() !== null) || ($compound->target->preparation() !== null)
		|| ($compound->value->preparation() !== null)) {
		throw new \LogicException('Compound cleanup left stale facts');
	}
}
$rejections = [
	['$x += 1;', 'established local declaration'],
	['$x = true; $x += 1;', 'canonical int operands'],
	['$x uint8 = 3; $x += 1;', 'canonical int operands'],
	['$x = 3; $x /= 1.5;', 'canonical int operands'],
	['$x = "x"; $x .= 1;', 'concatenation requires string'],
	['$x = 3; $x .= "x";', 'concatenation requires string'],
	['1 += 2;', 'existing local or parameter'],
	['PHP_INT_MAX += 1;', 'existing local or parameter'],
	['$x = 1; ((int)$x) += 1;', 'existing local or parameter'],
	['struct Box { int $value; } $box Box; ($box->value) += 1;', 'existing local or parameter'],
	['$x = 1; ($x[0]) += 1;', 'existing local or parameter'],
	['function value(): int { return 1; } $x = 1; $x += value();', 'order-independent operands'],
	['$x = 1; $x += $x++;', 'order-independent operands'],
	['$x = 1; $x += 1 + $x++;', 'order-independent operands'],
	['$x = 1; $x += ($x += 1);', 'order-independent operands'],
	['$x = 1; return ($x += 1) + $x;', 'order-independent operands'],
	['function take(int &$x): int { return $x; } $x = 1; return take($x += 1);', 'reference arguments require stable storage'],
];
foreach ($rejections as [$source, $diagnostic])
{
	$failed = false;
	try {
		compound_test_source($source);
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $diagnostic);
	}
	if (!$failed || !Model::$cpp_files->is_empty() || !Model::$prepared_files->is_empty()) {
		throw new \LogicException('Invalid compound assignment was accepted or published: ' . $source);
	}
}

$directory = sys_get_temp_dir() . '/scpp_compound_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
$initial = 'function value(): int { $x = 3; return $x += 2; } return value();';
try
{
	file_put_contents($path, $initial);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$function = Model::$global_scope->functions_named('value')[0]->syntax();
	$signature = $function->require_preparation();
	$changed = str_replace('+=', '-=', $initial);
	file_put_contents($path, $changed);
	$compiler->update_cpp([$path]);
	$body = $function->body;
	$compound = $body->statements[1]->expression;
	if (($compound->require_compound_assignment_preparation()->decision->operation !== operator_operation::integer_subtraction)
		|| ($function->require_preparation() !== $signature)) {
		throw new \LogicException('Operator edit lost its new decision or invalidated an unchanged signature');
	}
	$changed = str_replace('-=', '*=', $changed);
	file_put_contents($path, $changed);
	$compiler->update_cpp([$path]);
	$body = $function->body;
	$compound = $body->statements[1]->expression;
	if (($compound->require_compound_assignment_preparation()->decision->operation !== operator_operation::integer_multiplication)
		|| ($function->require_preparation() !== $signature)) {
		throw new \LogicException('Compound edit retained a stale decision or changed the signature');
	}
	$changed = 'function before(): int { return 0; } ' . $changed;
	file_put_contents($path, $changed);
	$compiler->update_cpp([$path]);
	$compiler->cleanup_tokens();
	$tokens = Model::tokens()[0];
	if (($function->body !== $body) || ($tokens->text_at($compound->operator_token_index) !== '*=')) {
		throw new \LogicException('Token cleanup lost the retained compound operator');
	}
	$incremental = Model::$cpp_files[0]->text;
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	if (Model::$cpp_files[0]->text !== $incremental) {
		throw new \LogicException('Incremental compound output differs from a fresh build');
	}
}
finally {
	unlink($path);
	rmdir($directory);
}

echo "Compound assignment: decisions, write-back, targets, cleanup and incremental equivalence passed\n";
