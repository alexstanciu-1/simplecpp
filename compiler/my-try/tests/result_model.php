<?php

/* Result roles share implementation without sharing definition/application identity. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function result_model_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

function result_model_rejects(\Closure $operation): void
{
	$failed = false;
	try { $operation(); }
	catch (\InvalidArgumentException $error) { $failed = true; }
	result_model_check($failed, 'Invalid result model operation was accepted');
}

Compiler_Lifecycle::reset();
$catalog = Model::$type_catalog;
$registry = $catalog->registry();
$int = $registry->canonical(object_cast($catalog->definition('int'), concrete_type_definition_i::class));
$payload = $registry->use($int->type_id());
$void = $registry->canonical(object_cast($catalog->definition('void'), concrete_type_definition_i::class));
$families = ['result' => result_kind::structured_error, 'result_or_false' => result_kind::false_sentinel,
	'result_or_bool' => result_kind::boolean_sentinels];
$identities = [];
$definitions = [];
$applications = [];
foreach ($families as $name => $kind) {
	$definition = object_cast($catalog->definition($name), result_type_definition::class);
	$role = object_cast($definition, result_semantics::class);
	result_model_check($role->result_family() === $kind, 'Wrong result semantic kind');
	result_model_check(!($definition instanceof nullable_semantics), 'Result acquired nullable semantics');
	result_model_check($definition->kind() === type_definition_kind::template, 'Result lost template classification');
	$before = $registry->application_count();
	result_model_rejects(fn() => $registry->intern_application($definition, []));
	result_model_rejects(fn() => $registry->intern_application($definition, [$payload, $payload]));
	result_model_rejects(fn() => $registry->intern_application($definition, [$registry->use($void->type_id())]));
	result_model_check($registry->application_count() === $before, 'Invalid result application was published');
	$application = $registry->intern_application($definition, [$payload]);
	result_model_check($registry->intern_application($definition, [$payload]) === $application, 'Result application was not interned');
	result_model_check($role->payload_type($application)->matches($payload), 'Result payload identity changed');
	$representation = CPP_Types::representation($registry->use($application->type_id()));
	result_model_check($representation->spelling === 'scpp::' . $name . '<scpp::int_t<>>', 'Wrong result C++ spelling');
	result_model_check(isset($representation->headers['scpp/' . $name . '.hpp']), 'Missing result header');
	result_model_check(isset($representation->headers['scpp/int_t.hpp']), 'Missing result payload header');
	$identities[] = $application->type_id();
	$definitions[] = $definition;
	$applications[] = $application;
}
result_model_check(count(array_unique($identities)) === 3, 'Result families collapsed into one canonical type');
result_model_rejects(fn() => $definitions[0]->payload_type($applications[1]));
$nullable = object_cast($catalog->definition('nullable'), nullable_type_definition::class);
$optional = $registry->intern_application($nullable, [$payload]);
result_model_rejects(fn() => $definitions[0]->payload_type($optional));
$parameters = [new template_type_parameter('Value', [generic_contract::value_storable])];
$renamed = $registry->define_result('DifferentSpelling', type_definition_origin::runtime,
	nominal_type_kind::class_type, $parameters, [generic_contract::value_storable], result_kind::structured_error);
$renamed_application = $registry->intern_application($renamed, [$payload]);
result_model_check($renamed->result_family() === result_kind::structured_error, 'Result role depends on spelling');
result_model_rejects(fn() => $definitions[0]->payload_type($renamed_application));
result_model_rejects(fn() => $registry->define_result('BadArity', type_definition_origin::runtime,
	nominal_type_kind::class_type, [], [], result_kind::structured_error));
$defaulted = [new template_type_parameter('Value', [], $payload)];
result_model_rejects(fn() => $registry->define_result('BadDefault', type_definition_origin::runtime,
	nominal_type_kind::class_type, $defaulted, [], result_kind::structured_error));
$boolean = $registry->canonical(object_cast($catalog->definition('bool'), concrete_type_definition_i::class));
foreach ($definitions as $definition) {
	$bool_use = $registry->use($boolean->type_id());
	$application = $registry->intern_application($definition, [$bool_use]);
	result_model_check($definition->payload_type($application)->matches($bool_use), 'Boolean payload was replaced by sentinel metadata');
}
echo "Result model: roles, identity, payload validation and C++ bindings passed\n";
