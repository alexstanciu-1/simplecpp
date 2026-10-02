<?php
namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';

/** Prepare one isolated operator source through shared semantics and C++ lowering. */
function mutation_test_source(string $text): parsed_file
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


foreach ([['++$x', operator_operation::integer_pre_increment, false],
	['$x++', operator_operation::integer_post_increment, true],
	['--$x', operator_operation::integer_pre_decrement, false],
	['$x--', operator_operation::integer_post_decrement, true]] as [$source, $operation, $postfix])
{
	$syntax = mutation_test_source('$x = 3; $a = ' . $source . '; return $a;');
	$mutation = $syntax->root->body->statements[1]->expression->value;
	$decision = $mutation->require_mutation_preparation()->decision;
	$integer = Language_Types::integer(Model::$language_scope);
	if (($mutation->kind() !== node_kind::mutation_expression) || ($mutation->postfix !== $postfix)
		|| ($decision->operation !== $operation) || !$decision->result_type->matches($integer)
		|| (count($decision->operands) !== 1) || $mutation->require_preparation()->addressable
		|| ($decision->operands[0]->operation !== conversion_operation::identity)
		|| ($decision->operands[0]->context !== conversion_context::operator_operand)) {
		throw new \LogicException('Mutation lost its operation, conversion or snapshot contract');
	}
	if (!$mutation->target->require_preparation()->addressable
		|| (iterator_to_array($mutation->children()) !== [$mutation->target])) {
		throw new \LogicException('Mutation lost its single resolved storage target');
	}
	$tokens = $syntax->tokens;
	$span = '';
	for ($index = $mutation->start_token(); $index < $mutation->end_token(); $index++) {
		$span .= $tokens->text_at($index);
	}
	if (($span !== $source) || ($tokens->text_at($mutation->operator_token_index) !== substr($source, $postfix ? -2 : 0, 2))) {
		throw new \LogicException('Mutation lost its source span or operator index');
	}
	Preparation_Cleanup::tree($syntax->root);
	if (($mutation->preparation() !== null) || ($mutation->target->preparation() !== null)) {
		throw new \LogicException('Mutation cleanup left stale target or result facts');
	}
}

$rejections = [
	['++$missing;', 'established local declaration'],
	['$missing++;', 'established local declaration'],
	['$x = true; $x++;', 'canonical int storage'],
	['$x = 1.5; --$x;', 'canonical int storage'],
	['$x = "x"; ++$x;', 'canonical int storage'],
	['$x uint8 = 3; $x--;', 'canonical int storage'],
	['++1;', 'existing local or parameter'],
	['PHP_INT_MAX++;', 'existing local or parameter'],
	['function value(): int { return 1; } value()++;', 'existing local or parameter'],
	['$x = 1; ((int)$x)++;', 'existing local or parameter'],
	['$x = 1; ($x + 1)++;', 'existing local or parameter'],
	['$x = 1; ++$x++;', 'existing local or parameter'],
	['$x = 1; $x++++;', 'existing local or parameter'],
	['struct Box { int $value; } $box Box; ++$box->value;', 'existing local or parameter'],
	['$x = 1; ++$x[0];', 'operator[] with 1 argument(s) overload resolution is deferred'],
	['$x = 1; return $x++ + $x;', 'order-independent operands'],
	['$x = 1; return 1 + (int)$x++;', 'order-independent operands'],
	['$x = 1; return -$x++;', 'order-independent operands'],
	['function take(int &$a): int { return $a; } $x = 1; return take(++$x);', 'reference arguments require stable storage'],
];
foreach ($rejections as [$source, $diagnostic])
{
	$failed = false;
	try {
		mutation_test_source($source);
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $diagnostic);
	}
	if (!$failed || !Model::$cpp_files->is_empty() || !Model::$prepared_files->is_empty()) {
		throw new \LogicException('Invalid mutation was accepted or published: ' . $source);
	}
}

$directory = sys_get_temp_dir() . '/scpp_mutation_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
$initial = 'function value(): int { $x = 3; return ++$x; } return value();';
try
{
	file_put_contents($path, $initial);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$function = Model::$global_scope->functions_named('value')[0]->syntax();
	$signature = $function->require_preparation();
	$changed = str_replace('++$x', '$x++', $initial);
	file_put_contents($path, $changed);
	$compiler->update_cpp([$path]);
	$body = $function->body;
	$mutation = $body->statements[1]->expression;
	if (($mutation->require_mutation_preparation()->decision->operation !== operator_operation::integer_post_increment)
		|| ($function->require_preparation() !== $signature)) {
		throw new \LogicException('Operator edit lost its new decision or invalidated an unchanged signature');
	}
	$changed = str_replace('$x++', '$x--', $changed);
	file_put_contents($path, $changed);
	$compiler->update_cpp([$path]);
	$body = $function->body;
	$mutation = $body->statements[1]->expression;
	if (($mutation->require_mutation_preparation()->decision->operation !== operator_operation::integer_post_decrement)
		|| ($function->require_preparation() !== $signature)) {
		throw new \LogicException('Mutation edit retained a stale decision or changed the signature');
	}
	$changed = 'function before(): int { return 0; } ' . $changed;
	file_put_contents($path, $changed);
	$compiler->update_cpp([$path]);
	$compiler->cleanup_tokens();
	$tokens = Model::tokens()[0];
	if (($function->body !== $body) || ($tokens->text_at($mutation->operator_token_index) !== '--')) {
		throw new \LogicException('Token cleanup lost the retained mutation operator');
	}
	$incremental = Model::$cpp_files[0]->text;
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	if (Model::$cpp_files[0]->text !== $incremental) {
		throw new \LogicException('Incremental mutation output differs from a fresh build');
	}
}
finally {
	unlink($path);
	rmdir($directory);
}

echo "Mutation: storage targets, snapshots, rejections, cleanup and incremental equivalence passed\n";
