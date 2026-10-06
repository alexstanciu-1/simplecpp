<?php

/* Prove distinct dynamic roles without admitting boxing, extraction or operators. */
namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function dynamic_model_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

Compiler_Lifecycle::reset();
$catalog = Model::$type_catalog;
$registry = $catalog->registry();
$initial_applications = $registry->application_count();
$mixed_definition = $catalog->definition('mixed');
$dynamic_definition = $catalog->definition('dynamic');
dynamic_model_check(($mixed_definition instanceof mixed_semantics)
	&& !($mixed_definition instanceof dynamic_semantics), 'Mixed semantic role changed');
dynamic_model_check(($dynamic_definition instanceof dynamic_semantics)
	&& !($dynamic_definition instanceof mixed_semantics), 'Dynamic semantic role changed');
$mixed_type = $catalog->canonical('mixed');
$dynamic_type = $catalog->canonical('dynamic');
dynamic_model_check($mixed_type->type_id() !== $dynamic_type->type_id(), 'Distinct carriers became aliases');
dynamic_model_check(($catalog->canonical('mixed') === $mixed_type)
	&& ($catalog->canonical('dynamic') === $dynamic_type), 'Concrete canonical identity was not reused');

foreach (['mixed' => 'scpp::mixed_t', 'dynamic' => 'scpp::dynamic_t<>'] as $name => $spelling)
{
	$type = $catalog->canonical($name);
	$definition = $type->definition();
	$type_use = $registry->use($type->type_id());
	dynamic_model_check(($type->family() === type_family::nominal)
		&& ($definition->kind() === type_definition_kind::nominal), 'Role replaced nominal classification');
	dynamic_model_check(!($definition instanceof abstract_template_type_definition), 'Runtime template spelling leaked into source model');
	dynamic_model_check($registry->has_capability($type_use, generic_contract::copyable_value)
		&& $registry->has_capability($type_use, generic_contract::value_storable), 'Carrier lost storage/copy contracts');
	dynamic_model_check(!$registry->has_capability($type_use, generic_contract::hashable)
		&& !$registry->has_capability($type_use, generic_contract::comparable), 'Model-only registration promised extra operations');
	$representation = CPP_Types::representation($type_use);
	dynamic_model_check($representation->spelling === $spelling, 'Wrong C++ carrier binding');
	dynamic_model_check(isset($representation->headers['scpp/' . $name . '_t.hpp']), 'Missing carrier header');
	dynamic_model_check(!Conversion_Preparation::is_scalar($type_use), 'Dynamic carrier entered native scalar conversion path');
}
dynamic_model_check($registry->application_count() === $initial_applications, 'Concrete carriers created template applications');
dynamic_model_check($registry->is_value_type($mixed_type->type_id()), 'Mixed box lost value storage');
dynamic_model_check($registry->is_value_type($dynamic_type->type_id()), 'Dynamic tagged binding lost value storage');
dynamic_model_check(!$registry->use($dynamic_type->type_id(), true)->by_value(), 'Redundant dynamic value modifier was retained');
dynamic_model_check(!$registry->use($mixed_type->type_id(), true)->by_value(), 'Redundant mixed value modifier was retained');

$custom = new Type_Registry();
$no_capabilities = [];
$renamed_mixed = $custom->define_mixed('BoxedValue', type_definition_origin::runtime, $no_capabilities);
$renamed_dynamic = $custom->define_dynamic('PropertyBag', type_definition_origin::runtime, $no_capabilities);
dynamic_model_check(($renamed_mixed instanceof mixed_semantics)
	&& ($renamed_dynamic instanceof dynamic_semantics), 'Semantic roles depend on spelling');
foreach (['mixed', 'dynamic'] as $name)
{
	$ordinary = $custom->define_nominal($name, type_definition_origin::source,
		nominal_type_kind::structure, $no_capabilities);
	dynamic_model_check(!($ordinary instanceof mixed_semantics)
		&& !($ordinary instanceof dynamic_semantics), 'Ordinary nominal name acquired dynamic semantics');
}

// Formation is shared; this does not enable operations on these constructed values.
$vector = object_cast($catalog->definition('vector'), abstract_template_type_definition::class);
$arguments = [$registry->use($dynamic_type->type_id())];
$application = $registry->intern_application($vector, $arguments);
dynamic_model_check($registry->intern_application($vector, $arguments) === $application,
	'Dynamic payload bypassed ordinary template interning');
dynamic_model_check(CPP_Types::representation($registry->use($application->type_id()))->spelling
	=== 'scpp::vector_t<scpp::dynamic_t<>>', 'Nested dynamic binding changed');

// Resolve source declarations through the ordinary language scope and preparation.
Compiler_Lifecycle::reset();
$input_module = new module('/dynamic-model', '/dynamic-model', '/dynamic-model');
$source = new file();
$source->path = 'main.phs';
$source->content = '$boxed mixed; $object_table dynamic; return 0;';
Source_Registry::add($input_module, $source);
$modules /** Keyed_Storage<module> */ = Model::$modules;
$modules->add($input_module->name, $input_module);
$compiler = new Compiler();
$compiler->exec_cpp();
$outputs /** Storage<cpp_artifact> */ = Model::$cpp_files;
$text = CPP_Probe::text($outputs);
dynamic_model_check(str_contains($text, 'scpp::mixed_t local_boxed;')
	&& str_contains($text, 'scpp::dynamic_t<> local_objectU_table;'), 'Source declarations lost carrier bindings');
if (isset($argv[1])) {
	file_put_contents($argv[1], $text);
}

echo "Dynamic model: separate roles, canonical identities, storage and bindings passed\n";
