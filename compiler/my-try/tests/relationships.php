<?php

/* Relationship preparation is an analysis checkpoint, not inherited execution. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function relationship_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

function relationship_type(string $name): collected_member_owner
{
	return object_cast(Model::$global_scope->source_types_named($name)[0], collected_member_owner::class);
}

function relationship_prepare(): Preparation_Worker
{
	$sources = Model::collected_files();
	Compiler_Lifecycle::retire_deleted_sources($sources, Model::$language_scope);
	$worker = new Preparation_Worker(Model::$language_scope);
	$worker->prepare_relationships($sources);
	return $worker;
}

function relationship_failure(string $expected): void
{
	try { relationship_prepare(); }
	catch (\RuntimeException $error) {
		relationship_check(str_contains($error->getMessage(), $expected), 'Wrong relationship diagnostic: ' . $error->getMessage());
		return;
	}
	throw new \LogicException('Expected relationship rejection: ' . $expected);
}

/** Compare semantic identities by qualified names across independent fresh/incremental models. */
function relationship_snapshot(): array
{
	$result = [];
	foreach (Model::collected_files() as $source) {
		foreach ($source->entries as $entry) {
			if (!($entry instanceof collected_member_owner)) { continue; }
			if ($entry->change_status === change_state::deleted) { continue; }
			$facts = $entry->relationship_work->require_facts();
			$base = weakref_get($facts->base);
			$interfaces = [];
			foreach ($facts->interfaces as $target) { $interfaces[] = $target->qualified_name(); }
			$result[$entry->qualified_name()] = [$base?->qualified_name(), $interfaces, $facts->final_type];
		}
	}
	ksort($result);
	return $result;
}

$directory = sys_get_temp_dir() . '/scpp_relationships_' . bin2hex(random_bytes(6));
mkdir($directory);
$base_path = $directory . '/base.phs';
$child_path = $directory . '/child.phs';
$probe_path = $directory . '/probe.phs';
$base_text = 'interface Root { public function read(): int; const code = 7; }
interface Left extends Root {} interface Right extends Root {}
interface Diamond extends Left, Right {}
class Base { public int $same; public function same(): int { return 1; } const same = 3; }
struct ValueBase { int $x; }';
$child_text = 'class Child extends Base implements Diamond {} struct ValueChild extends ValueBase {}
class Probe {} function observe(): int { return 0; }';
try {
	file_put_contents($base_path, $base_text);
	file_put_contents($child_path, $child_text);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->sync([]);
	$worker = relationship_prepare();
	$base = relationship_type('Base');
	$child = relationship_type('Child');
	$probe = relationship_type('Probe');
	$work = $child->relationship_work;
	relationship_check($work !== null && Preparation_Status::complete($work), 'Relationship work not complete');
	relationship_check($child->preparation === null && $child->syntax()->preparation() === null, 'Relationships manufactured layout');
	relationship_check(weakref_get($work->require_facts()->base) === $base, 'Wrong base identity');
	relationship_check($work->require_facts()->interfaces[0] === relationship_type('Diamond'), 'Interface edge lost');
	relationship_check($child->syntax()->methods->is_empty(), 'Inherited methods copied into child');
	$context = new preparation_context();
	$context->worker = $worker;
	$context->owner = $probe->relationship_work;
	foreach ([preparation_lookup_kind::property_name, preparation_lookup_kind::method_name, preparation_lookup_kind::constant] as $kind) {
		$found = Inherited_Lookup::members($child, 'same', $kind, $context);
		relationship_check(count($found) === 1 && $found[0]->declaring_type === $base, 'Member category/declaring owner lost');
	}
	$found = Inherited_Lookup::members(relationship_type('Diamond'), 'read', preparation_lookup_kind::method_name, $context);
	relationship_check(count($found) === 1 && $found[0]->member === relationship_type('Root')->syntax()->methods[0]->occurrence(), 'Diamond duplicated original declaration');
	relationship_check(Inherited_Lookup::members($child, 'read', preparation_lookup_kind::method_name, $context) === [], 'Implements manufactured an implementation');
	$version = $work->version;
	$worker = relationship_prepare();
	relationship_check($work->version === $version, 'No-op rebuilt relationship facts');
	try { $compiler->prepare(); throw new \LogicException('Inherited execution enabled'); }
	catch (\RuntimeException $error) { relationship_check(str_contains($error->getMessage(), 'Interface preparation'), 'Unexpected execution gate'); }

	// Cross-file edge changes, failure/retry and recovery retain the same work owner.
	file_put_contents($base_path, str_replace('class Base', 'final class Base', $base_text));
	$compiler->sync([$base_path]);
	relationship_failure('Cannot extend a final type');
	relationship_failure('Cannot extend a final type');
	relationship_check($work->failed && !Preparation_Status::complete($work), 'Failed relationship was reusable');
	file_put_contents($base_path, $base_text);
	$compiler->sync([$base_path]);
	relationship_prepare();
	relationship_check($child->relationship_work === $work && !$work->failed, 'Recovery replaced identity or stayed failed');
	file_put_contents($base_path, str_replace('class Base {', 'class Base extends Child {', $base_text));
	$compiler->sync([$base_path]);
	relationship_failure('Cyclic type relationship');
	file_put_contents($base_path, $base_text);
	$compiler->sync([$base_path]);
	relationship_prepare();
	$incremental_snapshot = relationship_snapshot();
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->sync([]);
	relationship_prepare();
	relationship_check(relationship_snapshot() === $incremental_snapshot, 'Fresh/incremental relationship facts disagree');
	foreach (['class Child extends ValueBase {}' => 'same-kind',
		'class Child extends Root {}' => 'base must',
		'class Child implements Base {}' => 'interface target',
		'class Child extends Missing {}' => 'one resolved type',
		'class Child implements Root, Root {}' => 'Duplicate direct',
		'interface Child extends Child {}' => 'Cyclic type relationship'] as $text => $error) {
		file_put_contents($child_path, $text);
		$compiler->sync([$child_path]);
		relationship_failure($error);
	}
	file_put_contents($child_path, $child_text);
	$compiler->sync([$child_path]);
	relationship_prepare();
	file_put_contents($child_path, 'class Child extends Future {}');
	$compiler->sync([$child_path]);
	relationship_failure('one resolved type');
	file_put_contents($base_path, $base_text . ' class Future {}');
	$compiler->sync([$base_path]);
	relationship_prepare();
	relationship_check(weakref_get(relationship_type('Child')->relationship_work->require_facts()->base) === relationship_type('Future'), 'Added target did not repair an unchanged child');
	file_put_contents($base_path, $base_text);
	$compiler->sync([$base_path]);
	relationship_failure('one resolved type');
	file_put_contents($child_path, $child_text);
	$compiler->sync([$child_path]);
	relationship_prepare();
	$child = relationship_type('Child');
	$old_work = $child->relationship_work;
	file_put_contents($child_path, 'class Child {}');
	$compiler->sync([$child_path]);
	relationship_prepare();
	relationship_check($child->relationship_work === $old_work && weakref_get($old_work->require_facts()->base) === null, 'Base removal not rebuilt');
	file_put_contents($child_path, '');
	$compiler->sync([$child_path]);
	relationship_prepare();
	relationship_check($old_work->change_status === change_state::deleted && $old_work->facts === null, 'Retirement kept ancestry facts');
	relationship_check(count($old_work->dependencies) === 0 && count($old_work->dependents) === 0, 'Retirement kept dependency edges');

	// The ordinary scheduler tracks missing/present member pools through the shared query.
	file_put_contents($base_path, 'class Base {}');
	file_put_contents($child_path, 'function observe(): int { return 0; }');
	$compiler->sync([$base_path, $child_path]);
	$compiler->prepare();
	$observer = Model::$global_scope->functions_named('observe')[0];
	$body_work = $observer->callable_syntax()->require_body()->work();
	$context->owner = $body_work;
	$context->worker = new Preparation_Worker(Model::$language_scope);
	$base = relationship_type('Base');
	relationship_check(Inherited_Lookup::members($base, 'added', preparation_lookup_kind::method_name, $context) === [], 'Expected miss');
	$before = $body_work->version;
	file_put_contents($base_path, 'class Base { public function added(): int { return 1; } }');
	$compiler->sync([$base_path]);
	$compiler->prepare();
	relationship_check($body_work->version > $before, 'Missing member observation did not invalidate caller');
	$found = Inherited_Lookup::members($base, 'added', preparation_lookup_kind::method_name, $context);
	relationship_check(count($found) === 1, 'Added member not visible');
	$before = $body_work->version;
	file_put_contents($base_path, 'class Base {}');
	$compiler->sync([$base_path]);
	$compiler->prepare();
	relationship_check($body_work->version > $before, 'Removed member observation did not invalidate caller');
	Compiler_Lifecycle::reset_preparation();
	relationship_check($base->relationship_work->facts === null, 'Reset retained relationship facts');
	$compiler->prepare();
	relationship_check(Preparation_Status::complete($base->relationship_work), 'Reset cannot rebuild relationships');
}
finally {
	Compiler_Lifecycle::reset();
	foreach ([$base_path, $child_path, $probe_path] as $path) { if (is_file($path)) { unlink($path); } }
	rmdir($directory);
}
echo "Relationship preparation, inherited identity, execution gates and incremental lifecycle passed\n";
