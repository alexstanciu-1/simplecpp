<?php
namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';

/** Prepare one isolated operator source through shared semantics and C++ lowering. */
function power_test_source(string $text): parsed_file
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


$syntax = power_test_source('return -2 ** 3 ** 2;');
$unary = $syntax->root->body->statements[0]->expression;
if (!($unary instanceof unary_expression_node) || !($unary->operand instanceof binary_expression_node)
	|| !($unary->operand->right instanceof binary_expression_node)) {
	throw new \LogicException('Power precedence or right associativity lost its AST shape');
}
$facts = $unary->operand->require_binary_preparation();
if (($facts->decision->source_operator !== operator_kind::power)
	|| ($facts->decision->operation !== operator_operation::integer_power)
	|| (count($facts->decision->operands) !== 2) || $facts->addressable
	|| !$facts->type->matches(Language_Types::integer(Model::$language_scope))) {
	throw new \LogicException('Power lost its canonical decision or value facts');
}
Preparation_Cleanup::tree($syntax->root);
if (($unary->preparation() !== null) || ($unary->operand->right->preparation() !== null)) {
	throw new \LogicException('Power cleanup left attached child facts');
}
$syntax = power_test_source('return 2 ** -2 ** 2;');
$power = $syntax->root->body->statements[0]->expression;
if (!($power->right instanceof unary_expression_node)
	|| !($power->right->operand instanceof binary_expression_node)) {
	throw new \LogicException('Signed exponent did not retain its own nested power');
}

$directory = sys_get_temp_dir() . '/scpp_power_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
try
{
	$source = 'function value(): int { return 2 * 3; } return value();';
	file_put_contents($path, $source);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$function = Model::$global_scope->functions_named('value')[0]->syntax();
	$signature = $function->require_preparation();
	$source = str_replace('2 * 3', '2 ** 3', $source);
	file_put_contents($path, $source);
	$compiler->update_cpp([$path]);
	$expression = $function->body->statements[0]->expression;
	if (($expression->require_binary_preparation()->decision->operation !== operator_operation::integer_power)
		|| ($function->require_preparation() !== $signature)
		|| !str_contains(Model::$cpp_files[0]->text, '#include "operators/arithmetic/power.hpp"')) {
		throw new \LogicException('Power edit lost operation, header or unchanged signature');
	}
	$body = $function->body;
	$source = 'function before(): int { return 0; } ' . $source;
	file_put_contents($path, $source);
	$compiler->update_cpp([$path]);
	$compiler->cleanup_tokens();
	if (($function->body !== $body)
		|| (Model::tokens()[0]->text_at($expression->operator_token_index) !== '**')) {
		throw new \LogicException('Compaction lost retained power syntax');
	}
	$incremental = Model::$cpp_files[0]->text;
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	if (Model::$cpp_files[0]->text !== $incremental) {
		throw new \LogicException('Incremental power differs from fresh output');
	}
	file_put_contents($path, str_replace('**', '* *', $source));
	$failed = false;
	try {
		$compiler->update_cpp([$path]);
	}
	catch (\RuntimeException $error) {
		$failed = true;
	}
	if (!$failed || !Model::$cpp_files->is_empty()) {
		throw new \LogicException('Spaced power punctuation retained a valid program');
	}
	file_put_contents($path, $source);
	$compiler->update_cpp([$path]);
	if (Model::$cpp_files[0]->text !== $incremental) {
		throw new \LogicException('Power recovery did not restore fresh output');
	}
	file_put_contents($path, str_replace('**', '*', $source));
	$compiler->update_cpp([$path]);
	if (str_contains(Model::$cpp_files[0]->text, 'operators/arithmetic/power.hpp')) {
		throw new \LogicException('Removed power retained a stale runtime include');
	}
}
finally {
	unlink($path);
	rmdir($directory);
}
echo "Power: precedence, canonical facts, cleanup, incremental headers and recovery passed\n";
