<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
Compiler_Lifecycle::reset();

function capability_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Apply arbitrary registered recipes without teaching the query their identities. */
function capability_apply(abstract_template_type_definition $definition, array $arguments): canonical_type_use
{
	$registry = Model::$type_catalog->registry();
	return $registry->use($registry->intern_application($definition, $arguments)->type_id());
}

$registry = Model::$type_catalog->registry();
$integer = $registry->use(Model::$type_catalog->canonical('int')->type_id());
$unique = capability_apply(object_cast(Model::$type_catalog->definition('unique'), abstract_template_type_definition::class), [$integer]);
$storage = [generic_contract::value_storable];
$capabilities = [generic_contract::value_storable, generic_contract::copyable_value, generic_contract::result_payload];
$copy = [generic_contract::copyable_value];
$recipe = $registry->define_template('unfamiliar_pair', type_definition_origin::runtime, nominal_type_kind::structure,
	[new template_type_parameter('Ignored', $storage), new template_type_parameter('Owned', $storage, $integer, $copy)], $capabilities);
$allowed = capability_apply($recipe, [$unique]);
$denied = capability_apply($recipe, [$integer, $unique]);
capability_check($registry->has_capability($allowed, generic_contract::copyable_value), 'Nondependent first argument affected copying');
capability_check(!$registry->has_capability($denied, generic_contract::copyable_value), 'Dependent second argument did not gate copying');
capability_check($registry->has_capability($denied, generic_contract::value_storable), 'Conditional copyability became a formation/storage constraint');
$nested = capability_apply($recipe, [$integer, $denied]);
capability_check(!$registry->has_capability($nested, generic_contract::copyable_value), 'Nested declared dependency was ignored');
foreach (['nullable', 'result', 'result_or_false', 'result_or_bool', 'vector'] as $name)
{
	$definition = object_cast(Model::$type_catalog->definition($name), abstract_template_type_definition::class);
	capability_check(!$registry->has_capability(capability_apply($definition, [$denied]), generic_contract::copyable_value), 'Copy rule lost for ' . $name);
}
$shared = capability_apply(object_cast(Model::$type_catalog->definition('shared'), abstract_template_type_definition::class), [$denied]);
capability_check($registry->has_capability($shared, generic_contract::copyable_value), 'Handle copy incorrectly depends on its pointee');
$wrapper = capability_apply(object_cast(Model::$type_catalog->definition('result'), abstract_template_type_definition::class), [$allowed]);
$role = Result_Conversions::role($wrapper);
capability_check(Result_Conversions::require_payload($wrapper, $role)->matches($allowed), 'Registered payload required a named Result special case');
foreach ([[$copy, []], [[generic_contract::copyable_value, generic_contract::copyable_value], $capabilities],
	[[generic_contract::result_payload], $capabilities]] as [$dependencies, $declared])
{
	$failed = false;
	try {
		$registry->define_template('invalid_rule', type_definition_origin::runtime, nominal_type_kind::structure,
			[new template_type_parameter('T', $storage, null, $dependencies)], $declared);
	}
	catch (\InvalidArgumentException $error) { $failed = true; }
	capability_check($failed, 'Invalid capability rule accepted');
}
$unregistered = $registry->define_nominal('opaque_value', type_definition_origin::runtime, nominal_type_kind::structure, $storage);
$opaque = $registry->use($registry->canonical($unregistered)->type_id());
$opaque_result = capability_apply(object_cast(Model::$type_catalog->definition('result'), abstract_template_type_definition::class), [$opaque]);
$failed = false;
try { Result_Conversions::require_payload($opaque_result, Result_Conversions::role($opaque_result)); }
catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'result_payload'); }
capability_check($failed, 'Storable alone granted Result transport');
echo "Type capability rules passed\n";
