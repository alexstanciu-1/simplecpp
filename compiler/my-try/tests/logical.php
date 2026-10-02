<?php
namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';

/** Isolate source publication, preparation and C++ emission for logical expressions. */
function logical_test_source(string $text): parsed_file
{
	Compiler_Lifecycle::reset();
	$input = new file();
	$input->path = 'logical.phs';
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

foreach (['and' => operator_kind::logical_and, 'or' => operator_kind::logical_or,
	'xor' => operator_kind::logical_xor] as $word => $kind)
{
	$syntax = logical_test_source('$a = false; $a = true ' . $word . ' false;');
	$expression = $syntax->root->body->statements[1]->expression;
	if (!($expression instanceof binary_expression_node)
		|| !($expression->left instanceof assignment_expression_node)
		|| ($expression->require_binary_preparation()->decision->source_operator !== $kind)
		|| $expression->left->require_assignment_preparation()->addressable) {
		throw new \LogicException('Keyword precedence or assignment value facts were lost');
	}
}
$syntax = logical_test_source('$a = false; $b = false; return $a = $b = true or false xor true and false || true;');
$outer = $syntax->root->body->statements[2]->expression;
if (!($outer->left instanceof assignment_expression_node)
	|| !($outer->left->value instanceof assignment_expression_node)
	|| ($outer->require_binary_preparation()->decision->source_operator !== operator_kind::logical_or)
	|| ($outer->right->require_binary_preparation()->decision->source_operator !== operator_kind::logical_xor)
	|| ($outer->right->right->require_binary_preparation()->decision->source_operator !== operator_kind::logical_and)
	|| ($outer->right->right->right->require_binary_preparation()->decision->source_operator !== operator_kind::logical_or)) {
	throw new \LogicException('Combined precedence ladder or right-associative assignment changed');
}
$syntax = logical_test_source('$a = 1; return (($a)) = 2;');
$assignment = $syntax->root->body->statements[1]->expression;
if (!($assignment->target->occurrence() instanceof collected_variable_write)) {
	throw new \LogicException('Normalized grouped target lost its write occurrence');
}

Preparation_Cleanup::tree($syntax->root);
if (($assignment->preparation() !== null) || ($assignment->value->preparation() !== null)) {
	throw new \LogicException('Assignment cleanup left retained facts');
}

$rejections = [
	['$a = true and false;', 'established local declaration for a'],
	['false and ($a = true);', 'established local declaration for a'],
	['true or ($a = true);', 'established local declaration for a'],
	['return $a = 1;', 'established local declaration for a'],
	['$a int = ($b = 1);', 'established local declaration for b'],
	['$a = false; return $a = ($b = true);', 'established local declaration for b'],
	['return false and 1;', 'canonical bool operands'],
	['return true or 1;', 'canonical bool operands'],
	['return true xor 1;', 'canonical bool operands'],
	['return true or $missing;', 'established local declaration for missing'],
	['$a = 0; return ($a = 1) + 2;', 'order-independent operands'],
	['$a = false; return (int)(true and ($a = true)) + 1;', 'order-independent operands'],
	['$a = false; $b = 0; $b += (int)(true and ($a = true));', 'order-independent operands'],
	['$a = 0; return (bool)($a++) and true;', 'order-independent operands'],
	['$a = 0; return (bool)($a += 1) and true;', 'order-independent operands'],
	['function f(): bool { return true; } return true and f();', 'order-independent operands'],
	['function f(int &$x): int { return $x; } $a = 0; return f($a = 1);', 'reference arguments require stable storage'],
	['struct Box { int $x; } $b Box; return $b->x = 1;', 'assignment target is not supported'],
	['return 1 = 2;', 'Assignment requires an assignable target'],
];
foreach ($rejections as [$source, $diagnostic])
{
	$failed = false;
	try {
		logical_test_source($source);
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $diagnostic);
	}
	if (!$failed || !Model::$cpp_files->is_empty()) {
		throw new \LogicException('Missing logical diagnostic ' . $diagnostic . ': ' . $source);
	}
}

$directory = sys_get_temp_dir() . '/scpp_logic_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
try
{
	$source = 'function value(): bool { $a = false; return ($a = true) and ($a = false); } return value();';
	file_put_contents($path, $source);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$function = Model::$global_scope->functions_named('value')[0]->syntax();
	$signature = $function->require_preparation();
	$source = str_replace(' and ', ' xor ', $source);
	file_put_contents($path, $source);
	$compiler->update_cpp([$path]);
	$expression = $function->body->statements[1]->expression;
	if (($expression->require_binary_preparation()->decision->operation !== operator_operation::boolean_xor)
		|| ($function->require_preparation() !== $signature)) {
		throw new \LogicException('Logical edit lost its operation or unchanged signature');
	}
	$body = $function->body;
	$source = 'function before(): int { return 0; } ' . $source;
	file_put_contents($path, $source);
	$compiler->update_cpp([$path]);
	$compiler->cleanup_tokens();
	if (($function->body !== $body)
		|| (Model::tokens()[0]->text_at($expression->operator_token_index) !== 'xor')) {
		throw new \LogicException('Compaction lost retained logical syntax');
	}
	$incremental = Model::$cpp_files[0]->text;
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	if (Model::$cpp_files[0]->text !== $incremental) {
		throw new \LogicException('Incremental logical output differs from fresh output');
	}
	file_put_contents($path, str_replace('($a = false)', '($missing = false)', $source));
	$failed = false;
	try {
		$compiler->update_cpp([$path]);
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), 'established local declaration');
	}
	if (!$failed || !Model::$cpp_files->is_empty()) {
		throw new \LogicException('Failed logical edit retained a published program');
	}
	file_put_contents($path, $source);
	$compiler->update_cpp([$path]);
	if (Model::$cpp_files[0]->text !== $incremental) {
		throw new \LogicException('Logical recovery did not restore fresh output');
	}
}
finally {
	unlink($path);
	rmdir($directory);
}
echo "Logical: precedence, assignment facts, restrictions, incremental cleanup and recovery passed\n";
