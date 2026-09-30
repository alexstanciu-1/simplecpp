<?php

/* Prove collection roles and syntax links without allocating a second symbol identity. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function role_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

Compiler_Lifecycle::reset();
$source = new file();
$source->path = 'collected_roles.phs';
$source->content = 'struct Box { int32 $value; } function put(Box &$box, int $n): int { $box->value = $n; $x = $n; $x = 7; return $x; } $box Box; return put($box, 9);';
$parsed = (new Parser((new Tokenizer($source))->tokenize()))->parse();
$parsed->root_scope()->set_parent(Model::$language_scope);
$expected = [
	collected_function::class => function_node::class,
	collected_struct::class => struct_node::class,
	collected_field::class => field_node::class,
	collected_parameter::class => parameter_node::class,
	collected_variable::class => variable_declaration_node::class,
	collected_function_reference::class => call_node::class,
	collected_field_reference::class => field_access_node::class,
	collected_variable_reference::class => variable_reference_node::class,
	collected_type_reference::class => named_type_node::class,
	collected_variable_write::class => variable_reference_node::class,
];
$seen = [];
$writes = [];
foreach ($parsed->collection->entries as $entry)
{
	$class = get_class($entry);
	role_check(isset($expected[$class]), 'Unknown collected role');
	$syntax_class = $expected[$class];
	role_check($entry->syntax() instanceof $syntax_class, 'Role has the wrong syntax specialization');
	role_check($entry->syntax()->occurrence() === $entry, 'Syntax does not observe its canonical collected identity');
	role_check($parsed->collection->entries[$entry->local_index] === $entry, 'Occurrence index changed identity');
	role_check(!property_exists($entry, 'kind') && !property_exists($entry, 'node'), 'Mutable tag or untyped syntax storage survived');
	role_check(property_exists($entry, 'preparation') === ($entry instanceof collected_definition), 'Preparation ownership leaked onto members or references');
	role_check(property_exists($entry, 'exported') === ($entry instanceof collected_declaration), 'Declaration state leaked onto unresolved uses');
	$seen[$class] = true;
	if ($entry instanceof collected_variable_write) {
		$writes[] = $entry;
	}
}
role_check(count($seen) === count($expected), 'Fixture did not exercise every collected role');
role_check(count($writes) === 2, 'Fixture must contain first and repeated writes');

// Refresh must publish every live occurrence exactly once in its role's existing list.
$lists = [
	'defined_elements' => collected_declaration::class,
	'variable_references' => collected_variable_reference::class,
	'function_references' => collected_function_reference::class,
	'type_references' => collected_type_reference::class,
	'field_references' => collected_field_reference::class,
	'pending_bindings' => collected_variable_write::class,
];
$indexed = [];
foreach ($lists as $property => $role) {
	foreach ($parsed->collection->$property as $index) {
		role_check(!isset($indexed[$index]), 'Occurrence indexed more than once');
		role_check($parsed->collection->entries[$index] instanceof $role, 'Occurrence indexed in the wrong role list');
		$indexed[$index] = true;
	}
}
role_check(count($indexed) === count($parsed->collection->entries), 'Inventory refresh lost a collected occurrence');

$function = object_cast($parsed->root->declarations[1], function_node::class);
$first = object_cast($function->body->statements[1], expression_statement_node::class)->expression;
$second = object_cast($function->body->statements[2], expression_statement_node::class)->expression;
(new File_Preparation($parsed->collection, Model::$language_scope))->prepare();
role_check($first->require_assignment_preparation()->binding->resolved_kind === binding_kind::declaration, 'First write did not declare');
role_check($second->require_assignment_preparation()->binding->resolved_kind === binding_kind::assignment, 'Repeated write did not assign');
role_check($first->require_assignment_preparation()->binding->declaration === $writes[0], 'Inferred storage allocated another collected identity');
role_check($second->require_assignment_preparation()->binding->declaration === $writes[0], 'Assignment lost the first-write identity');
foreach ($writes as $entry) {
	role_check($entry->syntax()->occurrence() === $entry, 'Resolution replaced the write occurrence');
	role_check($entry->kind() === collected_name_kind::binding && $entry->preparation_work_owner() === null, 'Resolution changed the collection role or added declaration work');
}
echo "Collected roles: typed syntax, role-specific storage and stable first/repeated-write identity passed\n";
