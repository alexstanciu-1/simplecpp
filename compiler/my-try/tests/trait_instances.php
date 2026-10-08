<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function instance_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}
function instance_owner(string $name): collected_member_owner
{
	foreach (Model::collected_files() as $source) {
		foreach ($source->defined_elements as $index) {
			$entry = $source->entries[$index];
			if ($entry instanceof collected_member_owner) {
				if ($entry->qualified_name() === $name) { return $entry; }
			}
		}
	}
	throw new \LogicException('Missing ' . $name);
}
$directory = sys_get_temp_dir() . '/trait-instances-' . bin2hex(random_bytes(6));
mkdir($directory);
$origin_path = $directory . '/origin.phs';
$consumer_path = $directory . '/consumer.phs';
try {
	file_put_contents($origin_path, 'namespace defs; struct Payload { int $number; } namespace features; use defs\\Payload as Item; trait Reader { private int $extra = 9; public function read(Item $item, int $offset = 2): int { $local = $this->own; return $local + $offset; } }');
	// Uses stay gated. Exercise the approved materialization boundary directly until integration.
	file_put_contents($consumer_path, 'namespace app; struct Item { string $wrong; } class A { private int $own = 1; } struct B { private int $own = 3; }');
	Compiler_Lifecycle::reset();
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->sync([]);
	$trait = object_cast(instance_owner('features\\Reader'), collected_trait::class);
	$a = object_cast(instance_owner('app\\A'), collected_record::class);
	$b = object_cast(instance_owner('app\\B'), collected_record::class);
	$first = Trait_Materialization::instantiate($trait, $a);
	$second = Trait_Materialization::instantiate($trait, $b);
	instance_check($a->trait_instances[0] === $first && $b->trait_instances[0] === $second, 'Consumer does not own its instances');
	instance_check($first->consumer() === $a && $second->consumer() === $b && $first->origin === $trait, 'Instance provenance lost');
	instance_check($first->collection !== $second->collection && $first->collection !== $trait->collection, 'Working collections are shared');
	$original = $trait->syntax()->methods[0];
	$left = object_cast($first->members[1]->working->syntax(), method_node::class);
	$right = object_cast($second->members[1]->working->syntax(), method_node::class);
	instance_check($first->members[1]->origin === $original->occurrence(), 'Member origin lost');
	instance_check($left !== $original && $left !== $right && $left->require_body() !== $right->require_body(), 'Working syntax is shared');
	instance_check($left->parameters[1] !== $right->parameters[1] && $left->parameters[1] !== $original->parameters[1], 'Parameters shared');
	instance_check($left->signature_scope() !== $right->signature_scope(), 'Signature scopes shared');
	instance_check($left->occurrence()->declaring_type() === $a && $right->occurrence()->declaring_type() === $b, 'Declaring owner is not consumer');
	instance_check($left->occurrence()->qualified_name() === 'app\\A::read', 'Member identity used trait namespace');
	instance_check($left->signature_scope()->source_namespace()->path() === 'features', 'Lexical context lost trait namespace');
	instance_check($a->syntax()->methods->is_empty() && count($a->syntax()->member_scope()->methods_named('read')) === 0, 'Authored consumer membership changed');
	$property_left = object_cast($first->members[0]->working->syntax(), property_node::class);
	$property_right = object_cast($second->members[0]->working->syntax(), property_node::class);
	instance_check($property_left->initializer !== $property_right->initializer && $property_left->initializer !== $trait->syntax()->properties[0]->initializer, 'Property initializer shared');
	// The ordinary method worker can settle both signatures and bodies independently.
	$worker = new Preparation_Worker(Model::$language_scope);
	$worker->select_callable($left); $worker->select_callable($right);
	$empty_sources = new Storage(); $worker->prepare($empty_sources);
	instance_check($left->require_body()->work()->state === preparation_state::ready && $right->require_body()->work()->state === preparation_state::ready, 'Independent bodies did not settle');
	instance_check($left->require_preparation() !== $right->require_preparation(), 'Signature facts shared');
	instance_check(Type_Queries::source_record($left->require_preparation()->parameters[0]->storage_type()) === instance_owner('defs\\Payload'), 'Parameter type did not use trait-local import');
	instance_check($original->preparation() === null && $original->require_body()->work() === null, 'Authored trait acquired consumer preparation');
	instance_check($left->require_preparation()->receiver->declaration === $left->occurrence(), 'Receiver lost working identity');
	instance_check(Type_Queries::source_record($left->require_preparation()->receiver->type) === $a && Type_Queries::source_record($right->require_preparation()->receiver->type) === $b, 'Consumer receivers mixed');
	$left_return = object_cast($left->require_body()->statements[1], return_node::class);
	$right_return = object_cast($right->require_body()->statements[1], return_node::class);
	instance_check($left_return->expression !== $right_return->expression && $left_return->expression->preparation() !== $right_return->expression->preparation(), 'Body expression/facts shared');
	// Snapshot owns token rows, so origin compaction cannot rebase working spans.
	$working_tokens = $first->parsed->tokens;
	$origin_tokens = $trait->token_snapshot();
	instance_check($working_tokens !== $origin_tokens && $working_tokens->tokens[$left->start_token()] !== $origin_tokens->tokens[$original->start_token()], 'Snapshot shares mutable token rows');
	$spelling = $working_tokens->text_at($left->start_token());
	$offset = $working_tokens->tokens[$left->start_token()]->offset;
	file_put_contents($origin_path, 'namespace features; trait Reader { use Other; public function changed(): int { return 7; } }');
	$compiler->sync([$origin_path]);
	instance_check($working_tokens->text_at($left->start_token()) === $spelling && $working_tokens->tokens[$left->start_token()]->offset === $offset, 'Origin edit changed instance snapshot');
	try { Trait_Materialization::instantiate($trait, $a); throw new \LogicException('Nested use was silently omitted'); }
	catch (\RuntimeException $error) { instance_check(str_contains($error->getMessage(), 'direct properties and methods'), 'Wrong materialization failure'); }
	instance_check(count($a->trait_instances) === 1, 'Failed materialization published a partial instance');
	// Exercise the existing ordered retirement protocol explicitly, ahead of automatic use reconciliation.
	$private_sources = new Storage(); $private_sources->append($first->collection);
	(new Preparation_Worker(Model::$language_scope))->discard_sources($private_sources);
	instance_check($left->require_body()->work()->change_status === change_state::deleted, 'Working body not retired');
	instance_check($right->require_body()->work()->change_status !== change_state::deleted, 'Retirement crossed consumer boundary');
	$first->collection->deleted = true;
	Collection_Retirement::remove_deleted_sources($private_sources);
	instance_check(count($first->collection->entries) === 0 && count($second->collection->entries) > 0, 'Private occurrence retirement crossed instances');
	$remaining_sources = new Storage(); $remaining_sources->append($second->collection);
	(new Preparation_Worker(Model::$language_scope))->discard_sources($remaining_sources);
	$second->collection->deleted = true;
	Collection_Retirement::remove_deleted_sources($remaining_sources);
}
finally {
	Compiler_Lifecycle::reset();
	foreach ([$origin_path, $consumer_path] as $path) { if (is_file($path)) { unlink($path); } }
	rmdir($directory);
}
echo "Trait instances: isolated syntax, scopes, preparation, receivers, snapshots and retirement passed\n";
