<?php

/* Prove built-in registration, separated identities and demand-only template interning. */
namespace scpp\compiler;

require_once dirname(__DIR__, 3) . '/tools/php_portability/runtime/bootstrap.php';
require_once dirname(__DIR__) . '/03_parse/types/model.php';
require_once dirname(__DIR__) . '/03_parse/types/catalog.php';
require_once dirname(__DIR__) . '/03_parse/types/register.php';
require_once dirname(__DIR__) . '/05_backend/cpp/type_bindings_catalog.php';

function type_catalog_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Require an unknown exposure to fail rather than entering the strict source surface. */
function type_catalog_rejects(\Closure $operation): void
{
	try {
		$operation();
	}
	catch (\OutOfBoundsException $error) {
		return;
	}
	throw new \LogicException('Expected unknown type exposure rejection');
}

$registry = new Type_Registry();
$catalog = Type_Definition_Registration::install($registry);
$bindings = CPP_Type_Binding_Registration::install($catalog);

type_catalog_check($registry->definition_count() === 19, 'Unexpected semantic definition count');
type_catalog_check($registry->type_count() === 13, 'Concrete built-in type count changed');
type_catalog_check($registry->application_count() === 0, 'Registration eagerly materialized a template application');
type_catalog_check($catalog->source()->type_count() === 20, 'Source exposure count changed');
type_catalog_check($catalog->source()->modifier_count() === 1, 'Source modifier count changed');
type_catalog_check($catalog->providers()->count() === 7, 'Runtime provider count changed');
type_catalog_check($bindings->definition_count() === 19, 'C++ binding count changed');

$value_modifier = $catalog->source()->modifier('value');
type_catalog_check($value_modifier->kind() === type_use_modifier_kind::by_value,
	'value<T> is not registered as the by-value type-use modifier');
type_catalog_check($bindings->modifier(type_use_modifier_kind::by_value)->name() === 'scpp::value_p',
	'By-value backend binding changed');
$value_provider = $catalog->providers()->modifier_provider(type_use_modifier_kind::by_value)->identity();
type_catalog_check($value_provider->family() === 'value',
	'By-value runtime identity was conflated with its value_p backend spelling');
type_catalog_rejects(fn() => $catalog->definition('value'));
type_catalog_rejects(fn() => $catalog->definition('shared_p'));
type_catalog_rejects(fn() => $catalog->definition('vector_t'));

$shared = $catalog->definition('shared');
$shared_provider = $catalog->providers()->provider_for($shared->definition_id())->identity();
type_catalog_check($shared_provider->provider() === 'simple_cpp.runtime', 'Runtime provider identity changed');
type_catalog_check($shared_provider->family() === 'shared', 'Runtime provider family changed');
type_catalog_check($bindings->definition($shared->definition_id())->name() === 'scpp::shared_p',
	'Source spelling leaked into the shared C++ binding');

$vector = object_cast($catalog->definition('vector'), template_type_definition::class);
$hash = object_cast($catalog->definition('hash'), template_type_definition::class);
$unique = object_cast($catalog->definition('unique'), template_type_definition::class);
type_catalog_check((new template_type_parameter('T'))->contracts() === [generic_contract::copyable_value],
	'Bare source type parameter lost its copyable_value default');
type_catalog_check($vector->parameter(0)->contracts() === [generic_contract::value_storable],
	'Vector value formation contract changed');
type_catalog_check($hash->parameter(0)->contracts() === [generic_contract::value_storable],
	'Hash value formation contract changed');
type_catalog_check($hash->parameter(1)->contracts()
	=== [generic_contract::hashable, generic_contract::comparable], 'Hash key contracts changed');
type_catalog_check($hash->required_arity() === 1 && $hash->arity() === 2,
	'Hash default-key arity changed');
type_catalog_check($unique->parameter(0)->contracts() === [],
	'Unknown pointer-target requirements were mislabeled as copyability');

$integer = $catalog->canonical('int');
$string = $catalog->canonical('string');
type_catalog_check($catalog->canonical('int') === $integer, 'Concrete type identity was not reused');
type_catalog_check($catalog->canonical('byte') === $catalog->canonical('uint8'),
	'byte did not reuse the stable uint8 canonical identity');
type_catalog_check($catalog->definition('byte')->name() === 'uint8',
	'byte source spelling created a second semantic definition');
type_catalog_check($string->definition()->capabilities() === [generic_contract::copyable_value,
	generic_contract::value_storable, generic_contract::hashable, generic_contract::comparable],
	'String declarative capabilities changed');
type_catalog_check($unique->capabilities() === [generic_contract::value_storable],
	'Unique ownership was incorrectly declared copyable');

$vector_int = $registry->intern_application($vector, [$registry->use($integer->type_id())]);
type_catalog_check($registry->intern_application($vector, [$registry->use($integer->type_id())]) === $vector_int,
	'Exact vector application was not reused');
$hash_default = $registry->intern_application($hash, [$registry->use($integer->type_id())]);
$hash_explicit = $registry->intern_application($hash,
	[$registry->use($integer->type_id()), $registry->use($string->type_id())]);
type_catalog_check($hash_default === $hash_explicit, 'Default hash key did not canonicalize to string');
type_catalog_check(q_count($hash_default->arguments()) === 2,
	'Canonical hash application did not retain its completed argument list');

$hash_string_int = $registry->intern_application($hash,
	[$registry->use($string->type_id()), $registry->use($integer->type_id())]);
$inner_vector = $registry->intern_application($vector, [$registry->use($hash_string_int->type_id())]);
$outer_vector = $registry->intern_application($vector, [$registry->use($inner_vector->type_id())]);
type_catalog_check($outer_vector->type_id() !== $inner_vector->type_id(),
	'Nested template applications collapsed distinct identities');
$by_value_vector = $registry->intern_application($vector,
	[$registry->use($integer->type_id(), true)]);
type_catalog_check($by_value_vector !== $vector_int,
	'By-value template argument flag was omitted from canonical identity');
type_catalog_check($registry->application_count() === 6,
	'Only demanded exact template applications should be materialized');

$copy_template = $registry->define_template('copy_box', type_definition_origin::source,
	nominal_type_kind::structure, [new template_type_parameter('T')], []);
$rejected = false;
try {
	$registry->intern_application($copy_template, [$registry->use($registry->intern_application($unique,
		[$registry->use($integer->type_id())])->type_id())]);
}
catch (\InvalidArgumentException $error) {
	$rejected = str_contains($error->getMessage(), 'copyable_value');
}
type_catalog_check($rejected, 'Declared template capability requirement was not enforced');
type_catalog_check($registry->application_count() === 7,
	'Rejected capability validation published the outer canonical application');

echo "Type catalog: definitions, contracts, identity separation and demand-only reuse passed\n";
