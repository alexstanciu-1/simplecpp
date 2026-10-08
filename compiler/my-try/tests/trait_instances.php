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

function instance_rejection(string $trait_source, string $expected): void
{
	$origin_file = new file(); $origin_file->path = 'rejected-trait.phs'; $origin_file->content = 'trait T { ' . $trait_source . ' }';
	$consumer_file = new file(); $consumer_file->path = 'rejected-consumer.phs'; $consumer_file->content = 'class C {}';
	$origin_parse = (new Parser((new Tokenizer($origin_file))->tokenize()))->parse();
	$consumer_parse = (new Parser((new Tokenizer($consumer_file))->tokenize()))->parse();
	$origin_parse->root_scope()->set_parent(Model::$language_scope);
	$consumer_parse->root_scope()->set_parent(Model::$language_scope);
	$origin = object_cast($origin_parse->root->single_body()->declarations[0]->occurrence(), collected_trait::class);
	$consumer = object_cast($consumer_parse->root->single_body()->declarations[0]->occurrence(), collected_record::class);
	$instance = Trait_Materialization::instantiate($origin, $consumer);
	$sources = new Storage(); $sources->append($consumer_parse->collection);
	try { (new Preparation_Worker(Model::$language_scope))->prepare($sources); throw new \LogicException('Invalid composition accepted'); }
	catch (\RuntimeException $error) { instance_check(str_contains($error->getMessage(), $expected), 'Wrong composition error: ' . $error->getMessage()); }
	$sources->append($instance->collection);
	(new Preparation_Worker(Model::$language_scope))->discard_sources($sources);
}
$directory = sys_get_temp_dir() . '/trait-instances-' . bin2hex(random_bytes(6));
mkdir($directory);
$origin_path = $directory . '/origin.phs';
$consumer_path = $directory . '/consumer.phs';
try {
	file_put_contents($origin_path, 'namespace features; use defs\\Payload as Item; trait Reader { private int $extra = 9; public function read(Item $item, int $offset = 2): int { $local = $this->own; $extra = $this->extra; return $local + $extra; } public function relay(Item $item): int { return $this->read($item); } }');
	// Exercise materialization directly, independently of the automatic source-use path.
	file_put_contents($consumer_path, 'namespace defs; struct Payload { int $number; } namespace app; struct Item { string $wrong; } class A { private int $own = 1; public int $read = 4; } struct B { private int $own = 3; } $item \\defs\\Payload; $a = new A(); $answer = $a->relay($item);');
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
	$worker->select_record($a->syntax()); $worker->select_record($b->syntax());
	$empty_sources = new Storage(); $worker->prepare($empty_sources);
	instance_check($left->require_body()->work()->state === preparation_state::ready && $right->require_body()->work()->state === preparation_state::ready, 'Independent bodies did not settle');
	instance_check($left->require_preparation() !== $right->require_preparation(), 'Signature facts shared');
	instance_check($property_left->require_preparation() !== $property_right->require_preparation(), 'Contributed property facts shared');
	instance_check($property_left->require_preparation()->default_value->value->value_key() === '9', 'Property default read consumer tokens instead of trait tokens');
	instance_check(count($a->syntax()->require_preparation()->fields->named('extra')) === 1, 'Contributed field missing from layout');
	instance_check(count($a->syntax()->require_preparation()->methods->named('read')) === 1, 'Contributed method missing from record facts');
	instance_check($property_left->occurrence()->change_status === change_state::unchanged, 'Contributed field did not settle with its consumer');
	instance_check(Type_Queries::source_record($left->require_preparation()->parameters[0]->storage_type()) === instance_owner('defs\\Payload'), 'Parameter type did not use trait-local import');
	instance_check($original->preparation() === null && $original->require_body()->work() === null, 'Authored trait acquired consumer preparation');
	instance_check($left->require_preparation()->receiver->declaration === $left->occurrence(), 'Receiver lost working identity');
	instance_check(Type_Queries::source_record($left->require_preparation()->receiver->type) === $a && Type_Queries::source_record($right->require_preparation()->receiver->type) === $b, 'Consumer receivers mixed');
	$left_return = object_cast($left->require_body()->statements[2], return_node::class);
	$right_return = object_cast($right->require_body()->statements[2], return_node::class);
	instance_check($left_return->expression !== $right_return->expression && $left_return->expression->preparation() !== $right_return->expression->preparation(), 'Body expression/facts shared');
	// Normal source scheduling and backend enumeration use the same local-member view.
	$sources = new Storage(); $sources->append($a->collection);
	$prepared = (new Preparation_Worker(Model::$language_scope))->prepare($sources);
	$artifacts = (new CPP_Generator())->generate_program($prepared, $prepared[0]);
	$implementation = '';
	foreach ($artifacts as $artifact) {
		if ($artifact->file_name === CPP_Artifacts::unit_path($a->collection)) { $implementation = $artifact->text; }
		instance_check(!str_contains($artifact->text, CPP_Generator::source_name('record', 'features\\Reader')), 'Trait became a runtime base/type');
	}
	foreach ([$a, $b] as $consumer) {
		$reference = CPP_Generator::source_name('record', $consumer->qualified_name()) . '::' . CPP_Generator::source_name('method', 'read');
		instance_check(substr_count($implementation, $reference . '(') === 1, 'Working method body not emitted exactly once in consumer unit');
	}
	// Same-category collisions use ordinary record validation, not a trait-specific override rule.
	foreach (['public function read(int $item): int { return 0; }' => 'Duplicate method', 'int $extra;' => 'Duplicate property'] as $member_source => $diagnostic) {
		$input = new file(); $input->path = 'collision.phs';
		$input->content = 'class Collision { int $own; ' . $member_source . ' }';
		$collision_parse = (new Parser((new Tokenizer($input))->tokenize()))->parse();
		$collision_parse->root_scope()->set_parent(Model::$language_scope);
		$collision = object_cast($collision_parse->root->single_body()->declarations[0]->occurrence(), collected_record::class);
		$collision_instance = Trait_Materialization::instantiate($trait, $collision);
		$collision_worker = new Preparation_Worker(Model::$language_scope);
		$collision_worker->select_record($collision->syntax());
		try { $collision_worker->prepare(new Storage()); throw new \LogicException('Collision accepted'); }
		catch (\RuntimeException $error) { instance_check(str_contains($error->getMessage(), $diagnostic), 'Wrong collision diagnostic: ' . $error->getMessage()); }
		$collision_sources = new Storage(); $collision_sources->append($collision_parse->collection); $collision_sources->append($collision_instance->collection);
		(new Preparation_Worker(Model::$language_scope))->discard_sources($collision_sources);
	}
	instance_rejection('static int $number;', 'instance storage');
	instance_rejection('$number = 4;', 'explicit types');
	instance_rejection('public static function count(): int { return 1; }', 'ordinary concrete instance');
	instance_rejection('public function &select(int &$value): int { return $this->select($value); }', 'recursive reference-return cycles');
	// Snapshot owns token rows, so origin compaction cannot rebase working spans.
	$working_tokens = $first->parsed->tokens;
	$origin_tokens = $trait->token_snapshot();
	instance_check($working_tokens !== $origin_tokens && $working_tokens->tokens[$left->start_token()] !== $origin_tokens->tokens[$original->start_token()], 'Snapshot shares mutable token rows');
	$spelling = $working_tokens->text_at($left->start_token());
	$offset = $working_tokens->tokens[$left->start_token()]->offset;
	$left_work = $left->require_body()->work(); $right_work = $right->require_body()->work();
	Trait_Composition::retire($a, Model::$language_scope);
	instance_check($left_work->change_status === change_state::deleted, 'Working body not retired');
	instance_check($right_work->change_status !== change_state::deleted, 'Retirement crossed consumer boundary');
	instance_check(count($first->collection->entries) === 0 && count($second->collection->entries) > 0, 'Private occurrence retirement crossed instances');
	file_put_contents($origin_path, 'namespace features; trait Reader { use Other; public function changed(): int { return 7; } }');
	$compiler->sync([$origin_path]);
	instance_check($working_tokens->text_at($left->start_token()) === $spelling && $working_tokens->tokens[$left->start_token()]->offset === $offset, 'Origin edit changed instance snapshot');
	try { Trait_Materialization::instantiate($trait, $a); throw new \LogicException('Nested use was silently omitted'); }
	catch (\RuntimeException $error) { instance_check(str_contains($error->getMessage(), 'direct properties and methods'), 'Wrong materialization failure'); }
	instance_check(count($a->trait_instances) === 0, 'Failed materialization published a partial instance');
	instance_check($right_work->change_status === change_state::deleted && $b->trait_instances->is_empty(), 'Origin edit did not retire remaining consumer');
}
finally {
	Compiler_Lifecycle::reset();
	foreach ([$origin_path, $consumer_path] as $path) { if (is_file($path)) { unlink($path); } }
	rmdir($directory);
}
echo "Trait instances: isolated syntax, scopes, preparation, receivers, snapshots and retirement passed\n";
