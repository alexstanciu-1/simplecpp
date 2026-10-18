<?php

/* Ownership identity and immediate composition rules, independent of syntax/backend spelling. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function ownership_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Invalid model operations must report their semantic owner before publication. */
function ownership_rejects(\Closure $operation, string $diagnostic): void
{
	$failed = false;
	try {
		$operation();
	}
	catch (\InvalidArgumentException $error) {
		$failed = str_contains($error->getMessage(), $diagnostic);
	}
	ownership_check($failed, 'Expected ownership model rejection: ' . $diagnostic);
}

Compiler_Lifecycle::reset();
$catalog = Model::$type_catalog;
$registry = $catalog->registry();
$integer = $registry->use($catalog->canonical('int')->type_id());
$families = ['shared' => ownership_kind::shared, 'unique' => ownership_kind::unique, 'weak' => ownership_kind::weak];
$applications = [];
foreach ($families as $name => $kind)
{
	$definition = object_cast($catalog->definition($name), ownership_type_definition::class);
	ownership_check($definition instanceof ownership_semantics, 'Ownership role is absent');
	ownership_check($definition->ownership_family() === $kind, 'Wrong ownership family');
	ownership_check(!($definition instanceof nullable_semantics) && !($definition instanceof result_semantics), 'Ownership inherited optionality');
	ownership_check($definition->kind() === type_definition_kind::template, 'Ownership lost template identity');
	$application = $registry->intern_application($definition, [$integer]);
	$applications[$name] = $application;
	$use = $registry->use($application->type_id());
	ownership_check($definition->target_type($application)->matches($integer), 'Wrong ownership target');
	ownership_check($registry->intern_application($definition, [$integer]) === $application, 'Ownership interning changed');
	ownership_check($registry->has_capability($use, generic_contract::value_storable), 'Handle is not storable');
	ownership_check($registry->has_capability($use, generic_contract::copyable_value) === ($kind !== ownership_kind::unique), 'Wrong handle copy capability');
	$cpp = CPP_Types::representation($use);
	ownership_check($cpp->spelling === 'scpp::' . $name . '_p<scpp::int_t<>>', 'Ownership changed backend spelling');
	ownership_check(isset($cpp->headers['scpp/' . $name . '_p.hpp']), 'Ownership header missing');
	ownership_rejects(fn() => $registry->use($application->type_id(), true), 'Value modifier');
	$before = $registry->application_count();
	ownership_rejects(fn() => $registry->intern_application($definition, []), 'arity');
	ownership_rejects(fn() => $registry->intern_application($definition, [$integer, $integer]), 'arity');
	ownership_check($registry->application_count() === $before, 'Invalid arity published an application');
}
foreach ($families as $outer => $kind) {
	$definition = object_cast($catalog->definition($outer), ownership_type_definition::class);
	foreach ($applications as $inner) {
		$before = $registry->application_count();
		$target = $registry->use($inner->type_id());
		ownership_rejects(fn() => $registry->intern_application($definition, [$target]), 'Direct ownership wrapper nesting');
		ownership_check($registry->application_count() === $before, 'Rejected nested ownership was interned');
	}
}
$shared = object_cast($catalog->definition('shared'), ownership_type_definition::class);
ownership_rejects(fn() => $shared->target_type($applications['weak']), 'this definition');
$parameters = [new template_type_parameter('Target', [])];
$renamed = $registry->define_ownership('NotAnOwnershipSpelling', type_definition_origin::runtime, $parameters, ownership_kind::shared);
$renamed_use = $registry->use($registry->intern_application($renamed, [$integer])->type_id());
ownership_rejects(fn() => $registry->intern_application($shared, [$renamed_use]), 'Direct ownership wrapper nesting');
ownership_rejects(fn() => $registry->intern_application($renamed, [$registry->use($applications['unique']->type_id())]), 'Direct ownership wrapper nesting');
$catalog->source()->expose_type('OwnerAlias', $shared);
ownership_check($catalog->definition('OwnerAlias') === $shared, 'Exposure alias lost ownership identity');
$alias_definition = object_cast($catalog->definition('OwnerAlias'), abstract_template_type_definition::class);
ownership_rejects(fn() => $registry->intern_application($alias_definition, [$renamed_use]), 'Direct ownership wrapper nesting');

// An unrelated ordinary template with the same spelling gains no ownership semantics.
$ordinary = $registry->define_template('shared', type_definition_origin::runtime, nominal_type_kind::structure,
	$parameters, [generic_contract::value_storable]);
$ordinary_use = $registry->use($registry->intern_application($ordinary, [$integer])->type_id());
ownership_check(!($ordinary instanceof ownership_semantics), 'Spelling conferred ownership');
$registry->intern_application($shared, [$ordinary_use]);

// Admission follows resolved families, including renamed definitions, before interning.
$no_value = $registry->define_no_value('NoValueAlias', type_definition_origin::runtime);
$void_targets = [$registry->use($catalog->canonical('void')->type_id()),
	$registry->use($registry->canonical($no_value)->type_id())];
foreach ([$shared, $catalog->definition('unique'), $renamed, $alias_definition] as $owner_definition)
{
	foreach ($void_targets as $target)
	{
		$before = $registry->application_count();
		ownership_rejects(fn() => $registry->intern_application($owner_definition, [$target]), 'non-void target');
		ownership_rejects(fn() => $registry->intern_application($owner_definition, [$target]), 'non-void target');
		ownership_check($registry->application_count() === $before, 'Rejected void ownership was interned');
	}
}
$weak = object_cast($catalog->definition('weak'), ownership_type_definition::class);
$weak_void = $registry->intern_application($weak, [$void_targets[0]]);
ownership_check($registry->intern_application($weak, [$void_targets[0]]) === $weak_void, 'Weak void behavior changed');
$registry->intern_application($ordinary, [$void_targets[0]]);

// The restriction is immediate, not a recursive ban on useful compositions.
$vector = object_cast($catalog->definition('vector'), abstract_template_type_definition::class);
$vector_handles = $registry->intern_application($vector, [$registry->use($applications['shared']->type_id())]);
$registry->intern_application($shared, [$registry->use($vector_handles->type_id())]);
$noncopyable = $registry->define_nominal('Noncopyable', type_definition_origin::runtime,
	nominal_type_kind::structure, [generic_contract::value_storable]);
$noncopyable_use = $registry->use($registry->canonical($noncopyable)->type_id());
foreach (['shared', 'weak'] as $name) {
	$definition = object_cast($catalog->definition($name), ownership_type_definition::class);
	$handle = $registry->use($registry->intern_application($definition, [$noncopyable_use])->type_id());
	ownership_check($registry->has_capability($handle, generic_contract::copyable_value), 'Pointee noncopyability leaked to handle');
}
ownership_rejects(fn() => $registry->define_ownership('NoTarget', type_definition_origin::runtime, [], ownership_kind::shared), 'at least one');
ownership_rejects(fn() => $registry->define_ownership('TwoTargets', type_definition_origin::runtime,
	[new template_type_parameter('A', []), new template_type_parameter('B', [])], ownership_kind::shared), 'exactly one');
ownership_rejects(fn() => $registry->define_ownership('DefaultTarget', type_definition_origin::runtime,
	[new template_type_parameter('A', [], $integer)], ownership_kind::shared), 'exactly one');
echo "Ownership model: roles, all nine direct-nesting rejections, modifiers, identities, capabilities and bindings passed\n";
