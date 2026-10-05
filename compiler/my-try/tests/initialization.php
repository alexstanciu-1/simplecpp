<?php

/* Model-only proof: no executable factories or data-construction lowering. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function initialization_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

function initialization_rejects(callable $action, string $message): void
{
	try { $action(); }
	catch (\Exception $error) {
		initialization_check(str_contains($error->getMessage(), $message), 'Wrong diagnostic: ' . $error->getMessage());
		return;
	}
	throw new \LogicException('Expected rejection: ' . $message);
}

function initialization_application(string $name, array $arguments): canonical_type_use
{
	$registry = Model::$type_catalog->registry();
	$definition = object_cast(Model::$type_catalog->definition($name), abstract_template_type_definition::class);
	return $registry->use($registry->intern_application($definition, $arguments)->type_id());
}

Compiler_Lifecycle::reset();
$registry = Model::$type_catalog->registry();
$contracts = Model::$initialization_contracts;
$integer = Language_Types::integer(Model::$language_scope);
$string_type = Language_Types::string_type(Model::$language_scope);
$mixed = $registry->use(Model::$type_catalog->canonical('mixed')->type_id());
$dynamic = $registry->use(Model::$type_catalog->canonical('dynamic')->type_id());
$context = new preparation_context();
$context->worker = new Preparation_Worker(Model::$language_scope);
initialization_check($registry->application_count() === 0, 'Installation eagerly instantiated input types');
initialization_check(Model::$language_scope->types_named('initializer_list') === []
	&& Model::$language_scope->types_named('initializer_entry') === [], 'Unsafe input types became source-visible');

$vector = initialization_application('vector', [$integer]);
$hash_string = initialization_application('hash', [$integer]);
$hash_int = initialization_application('hash', [$integer, $integer]);
$cases = [
	[$vector, false, $integer, null, $integer],
	[$hash_string, true, $integer, $string_type, $integer],
	[$hash_int, false, $integer, null, $integer],
	[$hash_int, true, $integer, $integer, $integer],
	[$mixed, false, $mixed, null, $mixed],
	[$mixed, true, $mixed, $mixed, $mixed],
	[$dynamic, false, $mixed, null, $dynamic],
	[$dynamic, true, $mixed, $mixed, $dynamic],
];
foreach ($cases as [$target, $keyed, $value, $key_type, $nested]) {
	$shape = $keyed ? initialization_shape::keyed : initialization_shape::positional;
	$binding = Initialization_Preparation::binding($target, $shape, $context);
	initialization_check($binding->target()->matches($target) && $binding->value()->matches($value)
		&& $binding->nested_target()->matches($nested) && $binding->is_keyed() === $keyed, 'Incorrect interface substitution');
	initialization_check($key_type === null ? $binding->key() === null : $binding->key()->matches($key_type), 'Wrong key substitution');
	$signature = $binding->factory()->signature();
	Initialization_Preparation::validate($binding, $signature, $context);
	initialization_check($signature->return_type->matches($target) && count($signature->parameters) === 1, 'Factory signature mismatch');
	$input = Type_Queries::canonical($signature->parameters[0]->type);
	initialization_check($contracts->is_call_scoped($input) && $contracts->is_readonly_input($input), 'Input lost lifetime/read-only contract');
	initialization_check(!$registry->has_capability($signature->parameters[0]->type, generic_contract::value_storable)
		&& !$registry->has_capability($signature->parameters[0]->type, generic_contract::copyable_value), 'View gained owning copy/storage permission');
	$element = object_cast($input, applied_template_type::class)->arguments()[0];
	if ($keyed) {
		$entry = object_cast(Type_Queries::canonical($element), applied_template_type::class);
		initialization_check($contracts->is_readonly_input($entry) && !$contracts->is_call_scoped($entry), 'Entry incorrectly inherits view lifetime');
		initialization_check($entry->arguments()[0]->matches($value) && $entry->arguments()[1]->matches($key_type), 'Entry generic order changed');
	}
	else { initialization_check($element->matches($value), 'List introduced entry/null-key wrapping'); }
	initialization_check(Initialization_Preparation::binding($target, $shape, $context) === $binding, 'Repeated query lost binding identity');
	initialization_rejects(fn() => CPP_Callables::reference($binding->factory(), new cpp_generation_context()), 'no C++ binding');
}
initialization_check(!Initialization_Preparation::binding($hash_int, initialization_shape::empty_brackets, $context)->is_keyed(), 'Empty brackets did not prefer list');
initialization_check(Initialization_Preparation::binding($hash_string, initialization_shape::empty_brackets, $context)->is_keyed(), 'Empty brackets did not fall back to keyed');
initialization_rejects(fn() => Initialization_Preparation::binding($vector, initialization_shape::keyed, $context), 'does not support');
initialization_rejects(fn() => Initialization_Preparation::binding($hash_string, initialization_shape::positional, $context), 'does not support');
initialization_rejects(fn() => Initialization_Preparation::binding($integer, initialization_shape::empty_brackets, $context), 'does not support');
$hash_bool = initialization_application('hash', [$integer, Language_Types::boolean(Model::$language_scope)]);
initialization_rejects(fn() => Initialization_Preparation::binding($hash_bool, initialization_shape::keyed, $context), 'does not support');
$nested_vector = initialization_application('vector', [$vector]);
initialization_check(Initialization_Preparation::binding($nested_vector, initialization_shape::positional, $context)->nested_target()->matches($vector), 'Typed nested target was lost');
$value_vector = $registry->use($vector->type_id(), true);
initialization_check(Initialization_Preparation::binding($value_vector, initialization_shape::positional, $context)->target()->matches($value_vector), 'Target modifier was lost');
$value_dynamic = $registry->use($dynamic->type_id(), true);
initialization_check(Initialization_Preparation::binding($value_dynamic, initialization_shape::positional, $context)->nested_target()->matches($dynamic), 'Outer storage modifier leaked into nested family default');
$entry_default = $registry->intern_application($contracts->entry(), [$integer]);
initialization_check($entry_default->arguments()[1]->matches($string_type), 'Entry lost its string key default');
$view_type = Initialization_Preparation::input_type($integer, null, $context);
initialization_rejects(fn() => initialization_application('vector', [$view_type]), 'value_storable');
initialization_rejects(fn() => Initialization_Preparation::input_type($view_type, null, $context), 'copyable_value');
$unique = initialization_application('unique', [$integer]);
$noncopy_vector = initialization_application('vector', [$unique]);
initialization_rejects(fn() => Initialization_Preparation::binding($noncopy_vector, initialization_shape::positional, $context), 'copyable_value');

// A matching method name/signature alone does not provide interface opt-in.
$fake = $registry->define_nominal('vector', type_definition_origin::runtime, nominal_type_kind::class_type,
	[generic_contract::copyable_value, generic_contract::value_storable]);
$fake_type = $registry->use($registry->canonical($fake)->type_id());
initialization_rejects(fn() => Initialization_Preparation::binding($fake_type, initialization_shape::positional, $context), 'does not support');

// One validation path rejects wrong return, parameter type, mode, count and variadics.
$good = Initialization_Preparation::binding($vector, initialization_shape::positional, $context);
foreach (['return', 'input', 'reference', 'count', 'variadic', 'default'] as $bad_kind) {
	$signature = new callable_signature();
	$signature->return_type = $bad_kind === 'return' ? $hash_string : $vector;
	$parameter = new callable_parameter();
	$parameter->type = $bad_kind === 'input' ? $integer : $good->factory()->signature()->parameters[0]->type;
	$parameter->mode = $bad_kind === 'reference' ? passing_mode::reference : passing_mode::value;
	if ($bad_kind === 'variadic') { $parameter->element_type = $integer; }
	if ($bad_kind === 'default') { $parameter->default_value = new prepared_parameter_default(); }
	if ($bad_kind !== 'count') { $signature->parameters->append($parameter); }
	$factory = Model::$runtime_callables->register_static_factory('test.bad.' . $bad_kind, $signature->return_type, $signature);
	$bad = new initialization_binding($vector, $integer, null, $integer, $factory);
	$diagnostic = $bad_kind === 'return' ? 'exact target' : ($bad_kind === 'count' ? 'one input sequence' : 'input contract mismatch');
	initialization_rejects(fn() => Initialization_Preparation::validate($bad, $signature, $context), $diagnostic);
}
initialization_rejects(fn() => $contracts->register($contracts->candidates()[0]), 'require reset');
$old = $contracts;
Compiler_Lifecycle::reset_preparation();
initialization_check(Model::$initialization_contracts === $old, 'Preparation reset replaced installed contracts');
Compiler_Lifecycle::reset_syntax();
initialization_check(Model::$initialization_contracts !== $old, 'Syntax reset retained old contracts');

// Conflicting opt-ins reject without using literal contents as overload selection.
$fresh = Model::$initialization_contracts;
$fresh->register($fresh->candidates()[0]);
$new_integer = Language_Types::integer(Model::$language_scope);
$new_vector = initialization_application('vector', [$new_integer]);
initialization_rejects(fn() => Initialization_Preparation::binding($new_vector, initialization_shape::positional, $context), 'ambiguous initialization');

// An unrelated runtime provider uses exactly the same path, including self substitution.
Compiler_Lifecycle::reset();
$registry = Model::$type_catalog->registry();
$integer = Language_Types::integer(Model::$language_scope);
$provider = $registry->define_nominal('external_bucket', type_definition_origin::runtime,
	nominal_type_kind::class_type, [generic_contract::copyable_value, generic_contract::value_storable]);
$provider_type = $registry->use($registry->canonical($provider)->type_id());
Model::$initialization_contracts->register(new initialization_declaration($provider,
	contract_type_reference::concrete($integer), null, contract_type_reference::target(), [], 'external.bucket.list'));
$provider_binding = Initialization_Preparation::binding($provider_type, initialization_shape::positional, $context);
initialization_check($provider_binding->nested_target()->matches($provider_type)
	&& $provider_binding->value()->matches($integer), 'External declaration needs a new binder branch');

// Cached bindings recheck source-derived input capability and observe the declaration.
$directory = sys_get_temp_dir() . '/scpp_initialization_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
try {
	$source = 'struct Item { int $value; } return 0;';
	file_put_contents($path, $source);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->sync([]);
	$compiler->prepare();
	$registry = Model::$type_catalog->registry();
	$item = Model::$global_scope->source_types_named('Item')[0];
	$item_definition = object_cast(Scope_Lookup::types(Model::$global_scope, 'Item')[0], concrete_type_definition_i::class);
	$item_type = $registry->use($registry->canonical($item_definition)->type_id());
	$parsed = Model::syntax_files()[0];
	$context = new preparation_context();
	$context->worker = new Preparation_Worker(Model::$language_scope);
	$context->owner = $parsed->root->body->work();
	$context->collection = $parsed->collection;
	$vector_definition = object_cast(Model::$type_catalog->definition('vector'), abstract_template_type_definition::class);
	$target = $registry->use($registry->intern_application($vector_definition, [$item_type], new Capability_Preparation($context))->type_id());
	$binding = Initialization_Preparation::binding($target, initialization_shape::positional, $context);
	initialization_check($context->owner->dependencies->contains($item->preparation), 'Input type completion lost its source dependency');
	initialization_rejects(fn() => Initialization_Preparation::binding($item_type, initialization_shape::positional, $context), 'source initialization interfaces are deferred');
	file_put_contents($path, str_replace('int $value', 'unique<int> $value', $source));
	$compiler->sync([$path]);
	$compiler->prepare();
	initialization_rejects(fn() => Initialization_Preparation::binding($target, initialization_shape::positional, $context), 'copyable_value');
	file_put_contents($path, $source);
	$compiler->sync([$path]);
	$compiler->prepare();
	initialization_check(Initialization_Preparation::binding($target, initialization_shape::positional, $context) === $binding,
		'Recovery duplicated a concrete binding');
}
finally {
	unlink($path);
	rmdir($directory);
}
echo "Initialization model: shared bindings, shapes, exact signatures, input roles, nesting and reset passed\n";
