<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function closure_model_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

function closure_model_function(): function_node
{
	return object_cast(Model::$global_scope->functions_named('owner')[0]->syntax(), function_node::class);
}

function closure_model_local(function_node $owner, int $index): closure_node
{
	return object_cast($owner->require_body()->statements[$index]->expression->value, closure_node::class);
}

/** Maintenance is structural only, and follows each owned edge exactly once. */
final class Closure_Model_Trace implements node_maintenance_worker_i
{
	public array $nodes = [];
	public function enter(ast_node $node): void { $this->nodes[] = $node; }
	public function edge(ast_node $parent, ast_node $child): void { $child->maintain($this); }
	public function token_index(int $index): int { return $index; }
}

$directory = sys_get_temp_dir() . '/closure-model-' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
$text = 'function owner(): int { $a = function (int $amount): int { return $amount + 1; }; $b = function (int $amount): int { return $amount + 1; }; return 0; } return 0;';
try {
	file_put_contents($path, $text);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->sync([]);
	$owner = closure_model_function();
	$first = closure_model_local($owner, 0); $second = closure_model_local($owner, 1);
	closure_model_check($first instanceof expression_node && !($first instanceof declaration_node), 'Closure is not an expression');
	closure_model_check($first->kind() === node_kind::closure_expression && $first->optional_occurrence() === null, 'Closure acquired named occurrence');
	closure_model_check(!property_exists($first, 'name') && !property_exists($first, 'template_parameters'), 'Anonymous syntax acquired declaration fields');
	closure_model_check($first !== $second && $first->signature_syntax !== $second->signature_syntax
		&& $first->signature_scope() !== $second->signature_scope() && $first->body_scope() !== $second->body_scope(), 'Distinct occurrences share owned state');
	closure_model_check($first->signature_syntax->parameters[0]->name === 'amount', 'Parameter name was lost');
	closure_model_check($first->signature_scope()->parent_scope() === $owner->require_body()->local_scope()
		&& $first->body_scope()->parent_scope() === $first->signature_scope()
		&& $first->require_body()->local_scope() === $first->body_scope(), 'Closure scope ownership is wrong');
	closure_model_check($first->signature_scope()->is_function() && $first->body_scope()->is_function(), 'Closure lacks function boundary');
	$children = iterator_to_array($first->children());
	closure_model_check($children === [$first->signature_syntax->parameters[0], $first->require_return_type(), $first->require_body()], 'Shared child order is wrong');
	$trace = new Closure_Model_Trace(); $first->maintain($trace);
	closure_model_check($trace->nodes[0] === $first && count($trace->nodes) === count(array_unique(array_map('spl_object_id', $trace->nodes))), 'Maintenance duplicated children');
	$parameter = $first->signature_syntax->parameters[0]->occurrence();
	$compiler->sync([$path]);
	closure_model_check(closure_model_local($owner, 0) === $first && $first->signature_syntax->parameters[0]->occurrence() === $parameter, 'No-op replaced anonymous syntax');
	closure_model_check($first->require_body()->local_scope() === $first->body_scope(), 'Retained nested body lost its scope owner');
	foreach (Model::collected_files()[0]->entries as $entry) {
		if ($entry instanceof collected_source_callable) { closure_model_check($entry->name === 'owner', 'Closure leaked into named callable inventory'); }
	}
	$rejected = false;
	try { $compiler->prepare(); }
	catch (\RuntimeException $error) { $rejected = str_contains($error->getMessage(), 'Closure execution awaits'); }
	closure_model_check($rejected && Model::$prepared_files->is_empty() && Model::$cpp_files->is_empty(), 'Model-only closure reached output');
	// Replacing the enclosing body currently replaces anonymous occurrences: no false stable-ID claim.
	file_put_contents($path, str_replace('$amount + 1', '$amount + 2', $text)); $compiler->sync([$path]);
	closure_model_check(closure_model_function() === $owner && closure_model_local($owner, 0) !== $first, 'Body replacement retained stale closure syntax');
	closure_model_check($parameter->change_status === change_state::deleted, 'Removed closure parameter was not retired');
	// Partial anonymous syntax must recover through the same parser and retirement protocol.
	file_put_contents($path, 'function owner(): int { $a = function (int $broken): int { return ;');
	$failed = false;
	try { $compiler->sync([$path]); } catch (\RuntimeException $error) { $failed = true; }
	closure_model_check($failed, 'Malformed closure was accepted');
	// Nested/top-level expressions use the same path, and restore the enclosing parser scope.
	file_put_contents($path, '$top = function (): int { $inner = function (int $named): int { return $named; }; return 0; }; $after = 1;');
	$compiler->sync([$path]);
	$root = Model::syntax_files()[0]->root->single_body()->body;
	$top = object_cast($root->statements[0]->expression->value, closure_node::class);
	$inner = object_cast($top->require_body()->statements[0]->expression->value, closure_node::class);
	closure_model_check($inner->signature_scope()->parent_scope() === $top->body_scope(), 'Nested closure lost its lexical context');
	closure_model_check(weakref_get($root->statements[1]->expression->target->occurrence()->scope) === $root->local_scope(), 'Closure parsing leaked its scope');
	$compiler->sync([$path]);
	closure_model_check(Model::syntax_files()[0]->root->single_body()->body === $root
		&& $top->require_body()->local_scope() === $top->body_scope()
		&& $inner->require_body()->local_scope() === $inner->body_scope(), 'Top-level reuse lost nested scope ownership');
	file_put_contents($path, 'function owner(): int { return 7; } return owner();');
	$compiler->update_cpp([$path]);
	closure_model_check(!Model::$cpp_files->is_empty(), 'Removal/repair failed to restore named execution');
	echo "Closure syntax model: PASS\n";
}
finally {
	Compiler_Lifecycle::reset();
	if (is_file($path)) { unlink($path); }
	rmdir($directory);
}
