<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function callable_instance_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}
function callable_instance_owner(string $name): collected_record
{
	foreach (Model::collected_files() as $source) {
		foreach ($source->defined_elements as $index) {
			$entry = $source->entries[$index];
			if ($entry instanceof collected_record && $entry->qualified_name() === $name) { return $entry; }
		}
	}
	throw new \LogicException('Missing record ' . $name);
}
$directory = sys_get_temp_dir() . '/callable-instances-' . bin2hex(random_bytes(6));
mkdir($directory);
$base_path = $directory . '/base.phs';
$child_path = $directory . '/child.phs';
$config_path = $directory . '/config.phs';
$base_text = 'namespace types; struct Item { int $number; } namespace origin; use types\\Item as Input; class Base { private const OFFSET = 3; public static function read(Input $item, int $offset = 2): int { $local = $offset; return self::OFFSET + $local + \\external\\BONUS; } }';
$child_text = 'namespace app; struct Input { string $wrong; } class Child extends \\origin\\Base {} class Other extends \\origin\\Base {}';
try {
	file_put_contents($base_path, $base_text);
	file_put_contents($child_path, $child_text);
	file_put_contents($config_path, 'namespace external; const BONUS = 1;');
	Compiler_Lifecycle::reset();
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->select_entry('main', 'child.phs');
	$compiler->exec_cpp();
	$baseline = CPP_Output::encode(Model::$cpp_files);
	$base = callable_instance_owner('origin\\Base');
	$child = callable_instance_owner('app\\Child');
	$other = callable_instance_owner('app\\Other');
	$origin = object_cast($base->syntax()->methods[0]->occurrence(), collected_method::class);
	$authored = $origin->callable_syntax();
	$original_facts = $authored->require_preparation();
	$first = Callable_Instances::obtain($origin, $child);
	$second = Callable_Instances::obtain($origin, $other);
	callable_instance_check(Callable_Instances::obtain($origin, $child) === $first, 'Repeated request lost instance identity');
	try { Callable_Instances::obtain($first->working, $child); throw new \RuntimeException('Instance-of-instance accepted'); }
	catch (\LogicException $expected) { callable_instance_check(str_contains($expected->getMessage(), 'original method'), 'Wrong origin rejection'); }
	callable_instance_check($first->origin() === $origin && $first->called_type() === $child, 'Instance key lost');
	callable_instance_check($first->working->declaring_type() === $base, 'Called type replaced lexical declaring type');
	callable_instance_check(weakref_get($first->working->instance_context) === $first, 'Working context backlink lost');
	$left = $first->working->callable_syntax(); $right = $second->working->callable_syntax();
	callable_instance_check($left !== $right && $left !== $authored && $left->require_body() !== $right->require_body(), 'Syntax shared');
	callable_instance_check($left->parameters[1] !== $right->parameters[1] && $left->signature_scope() !== $right->signature_scope(), 'Parameters/scopes shared');
	callable_instance_check($child->syntax()->methods->is_empty() && count($base->syntax()->methods) === 1, 'Member inventories changed');
	$compiler->prepare();
	callable_instance_check($left->require_body()->work()->state === preparation_state::ready && $right->require_body()->work()->state === preparation_state::ready, 'Working bodies did not settle');
	callable_instance_check($left->require_preparation() !== $right->require_preparation() && $authored->require_preparation() === $original_facts, 'Preparation aliased authored/other instance');
	callable_instance_check(Type_Queries::source_record($left->require_preparation()->parameters[0]->storage_type()) === callable_instance_owner('types\\Item'), 'Lexical imported parameter type lost');
	callable_instance_check($first->parsed->tokens->tokens[$left->start_token()] !== $origin->token_snapshot()->tokens[$authored->start_token()], 'Token rows shared');
	$compiler->update_cpp([]);
	callable_instance_check(Callable_Instances::obtain($origin, $child) === $first && CPP_Output::encode(Model::$cpp_files) === $baseline, 'No-op changed instances or published private implementations');
	$first_work = $left->require_body()->work();
	$first_version = $first_work->version;
	$compiler->prepare();
	callable_instance_check($first_work->version === $first_version, 'No-op preparation rebuilt private body');
	$collections = Callable_Inventory::source_collections(Model::collected_files());
	$private_count = 0;
	foreach ($collections as $collection) {
		if ($collection === $first->parsed->collection || $collection === $second->parsed->collection) { $private_count++; }
	}
	callable_instance_check($private_count === 2 && count(Model::$prepared_files) === 3, 'Private implementations were lost or published as program files');
	// An external dependency edit must reprepare private bodies without rematerializing them.
	file_put_contents($config_path, 'namespace external; const BONUS = 7;');
	$compiler->update_cpp([$config_path]);
	callable_instance_check(Callable_Instances::obtain($origin, $child) === $first && $first_work->version > $first_version, 'External dependency missed private work');
	file_put_contents($config_path, 'namespace external; const BONUS = "invalid";');
	try { $compiler->update_cpp([$config_path]); throw new \LogicException('Invalid external dependency accepted'); }
	catch (\RuntimeException $expected) {}
	callable_instance_check($first_work->failed && $right->require_body()->work()->failed, 'Private failures were not retained');
	try { $compiler->update_cpp([]); throw new \LogicException('Retry hid failed private work'); }
	catch (\RuntimeException $expected) {}
	file_put_contents($config_path, 'namespace external; const BONUS = 1;');
	$compiler->update_cpp([$config_path]);
	callable_instance_check(!$first_work->failed && $first_work->state === preparation_state::ready && Callable_Instances::obtain($origin, $child) === $first, 'Private dependency repair lost identity or completion');
	Compiler_Lifecycle::reset_preparation();
	callable_instance_check($left->preparation() === null && $first_work->state === preparation_state::pending, 'Preparation reset missed instance');
	$compiler->prepare();
	callable_instance_check($left->require_body()->work() === $first_work && $first_work->state === preparation_state::ready, 'Reset lost work identity');
	// A failed parse cannot retire otherwise usable private source graphs.
	file_put_contents($child_path, 'namespace app; class Child {');
	try { $compiler->sync([$child_path]); throw new \LogicException('Malformed source accepted'); }
	catch (\RuntimeException $expected) {}
	callable_instance_check(count($origin->called_instances) === 2 && $first_work->change_status !== change_state::deleted, 'Failed parse retired instance');
	file_put_contents($child_path, $child_text . ' class Added {}');
	$compiler->sync([$child_path]);
	callable_instance_check($origin->called_instances->is_empty() && $first_work->change_status === change_state::deleted, 'Called-source edit did not retire instances');
	callable_instance_check($first->parsed->collection->entries->is_empty(), 'Retired occurrences remain');
	$child = callable_instance_owner('app\\Child');
	$replacement = Callable_Instances::obtain($origin, $child);
	$compiler->prepare();
	$replacement_work = $replacement->working->callable_syntax()->require_body()->work();
	file_put_contents($base_path, str_replace('OFFSET = 3', 'OFFSET = 4', $base_text));
	$compiler->sync([$base_path]);
	callable_instance_check($replacement_work->change_status === change_state::deleted && $origin->called_instances->is_empty(), 'Origin edit did not retire instances');
	$origin = object_cast(callable_instance_owner('origin\\Base')->syntax()->methods[0]->occurrence(), collected_method::class);
	$last = Callable_Instances::obtain($origin, callable_instance_owner('app\\Child'));
	$compiler->prepare();
	$last_work = $last->working->callable_syntax()->require_body()->work();
	file_put_contents($child_path, 'namespace app; class Other extends \\origin\\Base {}');
	$compiler->sync([$child_path]);
	callable_instance_check($last_work->change_status === change_state::deleted && $origin->called_instances->is_empty(), 'Called-type removal retained instance');
	file_put_contents($child_path, $child_text);
	$compiler->sync([$child_path]);
	$last = Callable_Instances::obtain($origin, callable_instance_owner('app\\Child'));
	$compiler->prepare();
	$last_work = $last->working->callable_syntax()->require_body()->work();
	Compiler_Lifecycle::reset();
	callable_instance_check($last_work->change_status === change_state::deleted && $origin->called_instances->is_empty(), 'Full reset retained private work');
}
finally {
	Compiler_Lifecycle::reset();
	foreach ([$base_path, $child_path, $config_path] as $path) { if (is_file($path)) { unlink($path); } }
	rmdir($directory);
}
echo "Callable instances: identity, isolation, lexical preparation, edit retirement and reset passed\n";
