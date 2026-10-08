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
$side_path = $directory . '/side.phs';
$text = 'function owner(): int { $a = function (int $amount): int { return $amount + 1; }; $b = function (int $amount): int { return $amount + 1; }; return 0; } return 0;';
try {
	file_put_contents($path, $text);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->sync([]);
	$owner = closure_model_function();
	$first = closure_model_local($owner, 0); $second = closure_model_local($owner, 1);
	$first_identity = $first->anonymous_identity(); $second_identity = $second->anonymous_identity();
	$collection = $first_identity->collection();
	closure_model_check($first_identity !== $second_identity && $first_identity->local_index !== $second_identity->local_index
		&& $first_identity->syntax() === $first && $second_identity->collection() === $collection, 'Anonymous identity is not source-local and distinct');
	closure_model_check(count($collection->anonymous_callables) === 2 && !property_exists($first_identity, 'name'), 'Anonymous inventory became named lookup');
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
	closure_model_check($first->anonymous_identity() === $first_identity && !$first_identity->deleted
		&& count($collection->anonymous_callables) === 2, 'No-op replaced or duplicated anonymous identities');
	closure_model_check($first->require_body()->local_scope() === $first->body_scope(), 'Retained nested body lost its scope owner');
	foreach (Model::collected_files()[0]->entries as $entry) {
		if ($entry instanceof collected_source_callable) { closure_model_check($entry->name === 'owner', 'Closure leaked into named callable inventory'); }
	}
	$compiler->prepare();
	closure_model_check(!$first_identity->value_type->matches($second_identity->value_type), 'Distinct closure expressions acquired interchangeable types');
	closure_model_check(Model::$type_catalog->closures()->identity($first_identity->value_type) === $first_identity, 'Concrete type lost its source callable association');
	$signature_work = $first_identity->signature_work;
	$body_work = $first->require_body()->work();
	closure_model_check($signature_work instanceof anonymous_signature_work && $body_work instanceof anonymous_body_work, 'Anonymous callable did not acquire independent work');
	closure_model_check(Preparation_Status::complete($signature_work) && Preparation_Status::complete($body_work), 'Anonymous facts failed to settle before execution gate');
	closure_model_check($signature_work->declaration() === null && $body_work->declaration() === null, 'Anonymous work acquired a fake declaration');
	closure_model_check($body_work->dependencies[$signature_work] === 1, 'Anonymous body does not observe its signature');
	$return_facts = $first->require_body()->statements[0]->preparation();
	try { $compiler->prepare(); } catch (\RuntimeException $expected) { }
	closure_model_check($signature_work->version === 1 && $body_work->version === 1
		&& $first->require_body()->statements[0]->preparation() === $return_facts, 'Enclosing retry rebuilt or erased settled anonymous facts');
	$parameter_facts = $first->signature_syntax->parameters[0]->require_preparation();
	$signature_work->change_status = change_state::changed;
	try { $compiler->prepare(); } catch (\RuntimeException $expected) { }
	closure_model_check($signature_work->version === 1 && $first->signature_syntax->parameters[0]->require_preparation() === $parameter_facts,
		'Equivalent anonymous signature replaced referenced parameter facts');
	// Replacing the enclosing body currently replaces anonymous occurrences: no false stable-ID claim.
	// Rollover must not make an old occurrence's revision look newly collected.
	$collection->revision = 4294967295;
	$first_identity->revision = 1; $second_identity->revision = 1;
	file_put_contents($path, str_replace('$amount + 1', '$amount + 2', $text)); $compiler->sync([$path]);
	closure_model_check(closure_model_function() === $owner && closure_model_local($owner, 0) !== $first, 'Body replacement retained stale closure syntax');
	closure_model_check($parameter->change_status === change_state::deleted, 'Removed closure parameter was not retired');
	closure_model_check($signature_work->change_status === change_state::deleted && $body_work->change_status === change_state::deleted
		&& count($signature_work->dependents) === 0 && count($body_work->dependencies) === 0
		&& $first_identity->signature_work === null && $first->require_body()->work() === null, 'Anonymous retirement retained work or dependency edges');
	closure_model_check($first_identity->deleted && !isset($collection->anonymous_callables[$first_identity->local_index])
		&& closure_model_local($owner, 0)->anonymous_identity()->local_index > $second_identity->local_index, 'Removed anonymous identity was reused or retained');
	$retired_type_rejected = false;
	try { Model::$type_catalog->closures()->identity($first_identity->value_type); }
	catch (\RuntimeException $expected) { $retired_type_rejected = true; }
	closure_model_check($retired_type_rejected, 'Retired closure retained its type-to-source association');
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
	closure_model_check(count($collection->anonymous_callables) === 2 && !$top->anonymous_identity()->deleted
		&& !$inner->anonymous_identity()->deleted, 'Speculative top-level parse retained duplicate identities');
	file_put_contents($side_path, 'function spare(): int { $f = function (): int { return 1; }; return 0; }');
	$compiler->sync([$side_path]);
	$spare = object_cast(Model::$global_scope->functions_named('spare')[0]->syntax(), function_node::class);
	$side_identity = closure_model_local($spare, 0)->anonymous_identity();
	$side_collection = $side_identity->collection();
	closure_model_check($side_collection !== $collection, 'Identity lost source context');
	try { $compiler->prepare(); } catch (\RuntimeException $expected) { }
	$side_work = $side_identity->signature_work;
	closure_model_check($side_work instanceof anonymous_signature_work, 'Side-source anonymous contract was not demanded');
	unlink($side_path); $compiler->sync([$side_path]);
	closure_model_check($side_identity->deleted && $side_collection->anonymous_callables->is_empty(), 'Source deletion retained anonymous membership');
	closure_model_check($side_work->change_status === change_state::deleted && $side_identity->signature_work === null, 'Source deletion retained anonymous semantic work');
	file_put_contents($path, 'function owner(): int { return 7; } return owner();');
	$compiler->update_cpp([$path]);
	closure_model_check(!Model::$cpp_files->is_empty(), 'Removal/repair failed to restore named execution');
	closure_model_check($collection->anonymous_callables->is_empty(), 'Closure removal leaked its identity inventory');
	file_put_contents($path, '$f = function (int $value): int { $local = $value; };');
	$compiler->sync([$path]);
	$missing_return = false;
	try { $compiler->prepare(); }
	catch (\RuntimeException $error) { $missing_return = str_contains($error->getMessage(), 'can reach the end without returning'); }
	closure_model_check($missing_return, 'Anonymous body bypassed shared return-path validation');
	file_put_contents($path, '$f = function (int $value = 1): int { return $value; };');
	$compiler->sync([$path]);
	$unsupported_parameter = false;
	try { $compiler->prepare(); }
	catch (\RuntimeException $error) { $unsupported_parameter = str_contains($error->getMessage(), 'exact-arity by-value'); }
	closure_model_check($unsupported_parameter, 'Deferred anonymous parameter contract was accepted');
	$dependency_source = 'function external(int $n): int { return $n; } $f = function (): int { return external(1); };';
	file_put_contents($path, $dependency_source); $compiler->sync([$path]);
	try { $compiler->prepare(); } catch (\RuntimeException $expected) { }
	$dependency_closure = object_cast(Model::syntax_files()[0]->root->single_body()->body->statements[0]->expression->value, closure_node::class);
	$dependency_work = $dependency_closure->require_body()->work();
	closure_model_check(Preparation_Status::complete($dependency_work), 'Anonymous call dependency did not settle');
	file_put_contents($path, str_replace('int $n)', 'int $n, int $extra)', $dependency_source)); $compiler->sync([$path]);
	try { $compiler->prepare(); } catch (\RuntimeException $expected) { }
	closure_model_check($dependency_closure->require_body()->work() === $dependency_work && $dependency_work->failed,
		'Changed callee contract did not invalidate retained anonymous body');
	file_put_contents($path, $dependency_source); $compiler->sync([$path]);
	try { $compiler->prepare(); } catch (\RuntimeException $expected) { }
	closure_model_check(Preparation_Status::complete($dependency_work) && $dependency_work->version > 1, 'Anonymous dependency failure did not recover');
	echo "Closure syntax model: PASS\n";
}
finally {
	Compiler_Lifecycle::reset();
	if (is_file($path)) { unlink($path); }
	if (is_file($side_path)) { unlink($side_path); }
	rmdir($directory);
}
