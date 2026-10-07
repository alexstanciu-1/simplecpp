<?php

/* Shared declaration ownership and existing-path proof; not new member execution. */
namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';

function declaration_model_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Compare neutral maintenance with inspection, including optional and category edges. */
final class Declaration_Model_Maintenance implements node_maintenance_worker_i
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

/** Inspection is a view, not a persistent combined member collection. */
function declaration_model_inspect(ast_node $node): array
{
	$visited = [$node];
	foreach ($node->children() as $child) {
		foreach (declaration_model_inspect($child) as $nested) {
			$visited[] = $nested;
		}
	}
	return $visited;
}

Compiler_Lifecycle::reset();
$type = new type_declaration_node();
$type->name = 'Example';
$type->base_class = new named_type_node();
$type->extended_interfaces->append(new named_type_node());
$type->implemented_interfaces->append(new named_type_node());
$property = new property_node();
$property->name = 'same';
$property->type_syntax = new named_type_node();
$property->initializer = new integer_literal_node();
$type->properties->append($property);
$inferred_property = new property_node();
$inferred_property->name = 'inferred';
$inferred_property->initializer = new integer_literal_node();
$type->properties->append($inferred_property);
$method = new method_node();
$method->name = 'same';
$method->modifiers = [declaration_modifier::public_access, declaration_modifier::abstract_declaration];
declaration_model_check(!$method->has_parsed_definition(), 'Unparsed method is already complete');
$method->set_bodyless_declaration();
declaration_model_check($method->has_parsed_definition() && !$method->has_parsed_body(), 'Bodyless completion was lost');
$type->methods->append($method);
$constructor = new method_node();
$constructor->name = '__construct';
$local_scope = new scope();
$constructor->set_parsed_body(new function_body_node($local_scope));
$type->methods->append($constructor);
$constant = new member_constant_node();
$constant->name = 'same';
$constant->initializer = new integer_literal_node();
$type->constants->append($constant);
$use = new trait_use_node();
$use->traits->append(new named_type_node());
$use->traits->append(new named_type_node());
$type->use_traits->append($use);
$second_use = new trait_use_node();
$second_use->traits->append(new named_type_node());
$type->use_traits->append($second_use);

$children = iterator_to_array($type->children());
declaration_model_check($children === [$type->base_class, $type->extended_interfaces[0],
	$type->implemented_interfaces[0], $property, $inferred_property, $method, $constructor,
	$constant, $use, $second_use], 'Typed member inventories lost their independent order');
declaration_model_check(iterator_to_array($type->children()) === $children, 'Inspection cursor is shared');
declaration_model_check(iterator_to_array($constructor->children()) === [$constructor->body], 'Missing return syntax hid a constructor body');
declaration_model_check(iterator_to_array($inferred_property->children()) === [$inferred_property->initializer], 'Missing property type hid its initializer');
declaration_model_check(!property_exists($type, 'members'), 'A competing combined member owner was retained');
$maintenance = new Declaration_Model_Maintenance();
$type->maintain($maintenance);
declaration_model_check($maintenance->visited === declaration_model_inspect($type), 'Inspection and maintenance disagree');
declaration_model_check(count($maintenance->visited) === count(array_unique(array_map('spl_object_id', $maintenance->visited))), 'An owning edge was traversed twice');
foreach ([type_declaration_kind::class_type, type_declaration_kind::struct_type,
	type_declaration_kind::interface_type, type_declaration_kind::trait_type] as $kind) {
	$type->declaration_kind = $kind;
	declaration_model_check($type->kind() === node_kind::type_declaration, 'Declaration form changed the concrete AST kind');
	declaration_model_check($type->preparation() === null, 'Syntax manufactured prepared storage');
}

$function = new function_node();
declaration_model_check(($function instanceof callable_declaration_node) && ($method instanceof callable_declaration_node), 'Callables lost their common syntax');
declaration_model_check($function->signature_scope() !== $method->signature_scope(), 'Callables share a mutable signature scope');
$namespace_constant = new namespace_constant_node();
$namespace_constant->name = 'same';
$namespace_constant->initializer = new integer_literal_node();
declaration_model_check(($constant instanceof constant_declaration_node) && ($namespace_constant instanceof constant_declaration_node), 'Constants lost their common syntax');
$value = new integer_constant_value();
$value->decimal = '7';
$constant->set_preparation(new prepared_source_constant($value, member_visibility::public_access));
$namespace_constant->set_preparation(new prepared_source_constant($value, member_visibility::public_access));
$constant->clear_preparation();
declaration_model_check($namespace_constant->require_preparation()->value === $value, 'Constant cleanup mutated shared value facts');

// Exercise the migrated syntax through real collection, preparation and incremental emission.
$module = new module('/declaration-model', '/declaration-model', '/declaration-model');
$file = new file();
$file->path = 'main.phs';
$file->content = 'struct Box { public int $value; } const LIMIT = -(-7); function read(Box $box): int { return $box->value; } $b Box; $b->value = LIMIT; return read($b);';
$source = Source_Registry::add($module, $file);
Model::$modules->add($module->name, $module);
$compiler = new Compiler();
$compiler->exec_cpp();
$before = CPP_Output::encode(Model::$cpp_files);
$record = object_cast(Model::$global_scope->source_types_named('Box')[0], collected_record::class)->syntax();
$free = Model::$global_scope->functions_named('read')[0]->syntax();
$ordinary_constant = object_cast(Model::$global_scope->constants_named('LIMIT')[0], collected_constant::class)->syntax();
declaration_model_check($record instanceof type_declaration_node, 'Parser still builds a struct-only AST');
declaration_model_check($record->properties[0] instanceof property_node, 'Parser bypassed property syntax');
declaration_model_check($record->properties[0]->modifiers === [declaration_modifier::public_access], 'Explicit public modifier was discarded');
declaration_model_check($ordinary_constant instanceof namespace_constant_node, 'Namespace constant specialization was lost');
declaration_model_check($free->has_parsed_body() && $free->has_parsed_definition(), 'Ordinary function lost its required body');
$body = $free->require_body();
$file->content = "// shifted source\n" . $file->content;
$file->changes = SYNC_CHANGED;
$source->changes = change_state::changed;
$compiler->exec_cpp();
declaration_model_check(CPP_Output::encode(Model::$cpp_files) === $before, 'Source movement changed emitted artifacts');
declaration_model_check($free->require_body() === $body, 'Migration lost retained body identity');
declaration_model_check(object_cast(Model::$global_scope->source_types_named('Box')[0], collected_record::class)->syntax() === $record, 'Migration lost type identity');

// New modeled members must not disappear silently through the old struct preparation path.
$member_collection = $record->occurrence()->collection;
$collector = new Symbol_Collector($member_collection, $member_collection->token_snapshot(), $source->parsed->root_scope(), null, $member_collection->revision);
$collector->collect_method($method, $record->occurrence(), $record->start_token());
$method->set_parsed_body(new function_body_node(new scope()));
$record->methods->append($method);
$record->occurrence()->change_status = change_state::changed;
$rejected = false;
try {
	$compiler->prepare();
}
catch (\RuntimeException $error) {
	$rejected = str_contains($error->getMessage(), 'supported modifiers');
}
declaration_model_check($rejected, 'Unsupported modeled methods were silently ignored');
echo "Declaration model ownership, shared syntax, migration and admission gates passed\n";
