<?php

/* Isolated model proof; authored enum behavior is covered by enums.php. */
namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';

function enum_model_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Compare both traversal protocols without depending on parser or enum preparation. */
final class Enum_Model_Maintenance implements node_maintenance_worker_i
{
	public array $visited /** vector<ast_node> */ = [];

	public function enter(ast_node $node): void
	{
		$this->visited[] = $node;
	}

	public function edge(ast_node $parent, ast_node $child): void
	{
		$child->maintain($this);
	}

	public function token_index(int $index): int
	{
		return $index;
	}
}

Compiler_Lifecycle::reset();
$registry = Model::$type_catalog->registry();
$source = new file();
$source->path = 'enum-model.phs';
$source->content = 'enum Suit { case Hearts; case Spades; }';
$tokens = (new Tokenizer($source))->tokenize();
$local_scope = new scope();
$local_scope->set_parent(Model::$global_scope);
$root = new file_node($local_scope);
$collection = new collected_file($tokens);
$collector = new Symbol_Collector($collection, $tokens, $local_scope, null, 1);

$unit = new enum_node();
$unit->name = 'Suit';
$unit->set_span(0, $tokens->end_token);
$hearts = new enum_case_node();
$hearts->name = 'Hearts';
$hearts->set_span(3, 6);
$spades = new enum_case_node();
$spades->name = 'Spades';
$spades->set_span(6, 9);
$unit->cases->append($hearts);
$unit->cases->append($spades);
$root->single_body()->declarations->append($unit);
$unit->collect($collector, $local_scope, 1);
$enum_entry = object_cast($unit->occurrence(), collected_enum::class);
$definition = object_cast($local_scope->types_named('Suit')[0], enum_type_definition::class);
$canonical = $registry->canonical($definition);
$use = $registry->use($canonical->type_id());

enum_model_check($enum_entry instanceof collected_type_definition, 'Enum lacks common source-type role');
enum_model_check(($enum_entry->syntax() === $unit) && ($enum_entry->enum_syntax() === $unit), 'Enum syntax identity changed');
enum_model_check($definition->nominal_kind() === nominal_type_kind::enumeration, 'Enum masquerades as a struct');
enum_model_check(($canonical instanceof nominal_type) && ($canonical->family() === type_family::nominal), 'Enum became an integer type');
enum_model_check($registry->canonical($definition) === $canonical, 'Canonical enum identity was not reused');
enum_model_check($registry->is_value_type($canonical->type_id()) && !$registry->use($canonical->type_id(), true)->by_value(),
	'Enum storage normalization depends on integer classification');
enum_model_check(!Conversion_Preparation::is_scalar($use), 'Enum entered scalar conversion without admission');
enum_model_check(!Type_Preparation::same_storage_type($use, $registry->use(Model::$type_catalog->canonical('int')->type_id())),
	'Enum acquired integer reference compatibility');
enum_model_check($definition->capabilities() === [], 'Registry fabricated source-derived capabilities');
$requires_preparation = false;
try {
	$copyable = $registry->has_capability($use, generic_contract::copyable_value);
}
catch (\LogicException $error) {
	$requires_preparation = str_contains($error->getMessage(), 'completed declaration preparation');
}
enum_model_check($requires_preparation, 'Enum bypassed the source capability completion gate');
enum_model_check(Type_Queries::source_declaration($use) === $enum_entry, 'Source association lost enum identity');
enum_model_check(Type_Queries::source_record($use) === null, 'Record-only query returned an enum');
enum_model_check($local_scope->source_types_named('Suit') === [$enum_entry], 'Source type lookup still assumes struct');
enum_model_check(($hearts->optional_occurrence() === null) && ($spades->optional_occurrence() === null), 'Case gained a namespace occurrence');
enum_model_check($local_scope->constants_named('Hearts') === [], 'Case leaked into constant lookup');
enum_model_check(($enum_entry->preparation === null) && ($unit->backing_type === null), 'Collection performed enum preparation');

// Both existing whole-scope and incremental publication paths retain the same identities.
Scope_Publication::publish($local_scope, Model::$global_scope);
enum_model_check(Model::$global_scope->types_named('Suit') === [$definition], 'Enum scope publication copied its identity');
$collector->select_source_scope($local_scope, Model::$global_scope);
$second = new enum_node();
$second->name = 'Suit';
$second->set_span(0, $tokens->end_token);
task_run_publish_unordered([$second], 1, function (enum_node $node) use ($collector, $local_scope): int {
	$node->collect($collector, $local_scope, 1);
	return 0;
}, function (int $result): void {
});
$root->single_body()->declarations->append($second);
$second_definition = object_cast($local_scope->types_named('Suit')[1], enum_type_definition::class);
enum_model_check($second_definition->definition_id() !== $definition->definition_id(), 'Duplicate source declarations merged');
enum_model_check($registry->canonical($second_definition)->type_id() !== $canonical->type_id(), 'Distinct enum types merged');
enum_model_check(count(Model::$global_scope->types_named('Suit')) === 2, 'Incremental enum publication was skipped');

$structure = new type_declaration_node();
$structure->name = 'Box';
$structure->set_span(0, 1);
task_run_publish_unordered([$structure], 1, function (type_declaration_node $node) use ($collector, $local_scope): int {
	$node->collect($collector, $local_scope, 0);
	return 0;
}, function (int $result): void {
});
$root->single_body()->declarations->append($structure);
$record_definition = object_cast($local_scope->types_named('Box')[0], nominal_type_definition::class);
$record_use = $registry->use($registry->canonical($record_definition)->type_id());
enum_model_check(Type_Queries::source_declaration($record_use) === $structure->occurrence(), 'General index lost struct association');
enum_model_check(Type_Queries::source_record($record_use) === $structure->occurrence(), 'Record query lost struct identity');
$collector->finish($root);
$next_collector = new Symbol_Collector($collection, $tokens, $local_scope, Model::$global_scope, 2);
enum_model_check($next_collector->previous_record($local_scope, 'Suit', type_declaration_kind::struct_type) === null, 'Struct reconciliation selected enum syntax');
enum_model_check($next_collector->previous_record($local_scope, 'Box', type_declaration_kind::struct_type) === $structure, 'Struct reconciliation no longer reuses its syntax');
$unit->collect($next_collector, $local_scope, 1);
enum_model_check(($unit->occurrence() === $enum_entry) && (count($local_scope->types_named('Suit')) === 2), 'Reuse duplicated enum registration');

// One syntax shape represents unit and backed declarations, preserving ordered owning edges.
enum_model_check(iterator_to_array($unit->children()) === [$hearts, $spades], 'Unit case inspection order changed');
$backing = new named_type_node();
$backing->name = 'uint16';
$backing->set_span(0, 1);
$value = new integer_literal_node();
$value->set_span(0, 1);
$unit->backing_type = $backing;
$hearts->value = $value;
enum_model_check(iterator_to_array($unit->children()) === [$backing, $hearts, $spades], 'Backing syntax not visited before cases');
enum_model_check(iterator_to_array($hearts->children()) === [$value], 'Case lost initializer ownership');
enum_model_check(iterator_to_array($spades->children()) === [], 'Unit case manufactured an initializer');
$left_cursor = $unit->children();
$right_cursor = $unit->children();
$left_cursor->rewind();
$left_cursor->next();
enum_model_check(($left_cursor->current() === $hearts) && ($right_cursor->current() === $backing), 'Inspection cursors share mutable position');
$maintenance = new Enum_Model_Maintenance();
$unit->maintain($maintenance);
enum_model_check($maintenance->visited === [$unit, $backing, $hearts, $value, $spades], 'Maintenance missed or duplicated owning edges');

$rejected_emission = false;
try {
	CPP_Types::representation($use);
}
catch (\RuntimeException $error) {
	$rejected_emission = str_contains($error->getMessage(), 'requires completed preparation');
}
enum_model_check($rejected_emission, 'Unprepared enum silently emitted a record representation');

// Frontend retirement removes only the exact declaration, not same-name peers or structs.
$enum_entry->change_status = change_state::deleted;
$sources /** Storage<collected_file> */ = new Storage();
$sources->append($collection);
Collection_Retirement::remove_deleted_sources($sources);
enum_model_check(Model::$global_scope->types_named('Suit') === [$second_definition], 'Retirement removed a same-name peer');
enum_model_check($local_scope->source_types_named('Suit') === [$second->occurrence()], 'Retirement left a stale local index');
enum_model_check(Model::$type_catalog->source_declarations()->count() === 2, 'Retirement left stale source associations');
enum_model_check(Type_Queries::source_record($record_use) === $structure->occurrence(), 'Enum retirement damaged struct association');
$association_removed = false;
try {
	$old = Model::$type_catalog->source_declarations()->declaration($definition->definition_id());
}
catch (\OutOfBoundsException $error) {
	$association_removed = true;
}
enum_model_check($association_removed, 'Retired enum is still discoverable by definition ID');
Compiler_Lifecycle::reset();
enum_model_check(Model::$type_catalog->source_declarations()->count() === 0, 'Reset retained enum associations');

echo "Enum model: nominal identity, syntax ownership, shared publication and retirement passed\n";
