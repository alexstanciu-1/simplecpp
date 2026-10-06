<?php

/* Lookup ownership and visited-pool dependencies, without invoking declaration preparation. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function lookup_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

function lookup_path(preparation_owner $owner, array $scopes, string $name, preparation_lookup_kind $kind): void
{
	$found = [];
	foreach ($owner->lookups as $observation) {
		if ($observation->name === $name && $observation->kind === $kind) {
			$found[] = $observation->lookup_scope;
			lookup_check(isset($observation->dependents[$owner]), 'Missing reverse observation link');
		}
	}
	lookup_check($found === $scopes, 'Observed path differs from nearest-pool traversal');
}

Compiler_Lifecycle::reset();
$source = new file();
$source->path = 'observed_lookup.phs';
$source->content = 'function target(): int { return 1; } function target(): int { return 2; }';
$parsed = (new Parser((new Tokenizer($source))->tokenize()))->parse();
$first = object_cast($parsed->root->declarations[0], function_node::class)->occurrence();
$second = object_cast($parsed->root->declarations[1], function_node::class)->occurrence();
$integer = Model::$type_catalog->definition('int');
$floating = Model::$type_catalog->definition('float');
$constant = new integer_constant_definition();
$constant->name = 'target';
$constant->type = Language_Types::integer(Model::$language_scope);
$constant->decimal = '7';
$alternative = new integer_constant_definition();
$alternative->name = 'target';
$alternative->type = $constant->type;
$alternative->decimal = '8';

$outer = new scope();
$proxy = new scope();
$proxy->set_publication($outer);
$middle = new scope();
$middle->set_parent($proxy);
$local = new scope();
$local->set_parent($middle);
$outer->register($first);
$outer->register_type_as('target', $integer);
$outer->register_constant($constant);
$owner = new file_body_work($parsed->collection, $parsed->root->body);
$context = new preparation_context();
$context->owner = $owner;
$context->worker = new Preparation_Worker(Model::$language_scope);

// Plain lookup has no analysis input, subscriptions or declaration completion.
foreach (['functions', 'types', 'constants'] as $method) {
	lookup_check((new \ReflectionMethod(Scope_Lookup::class, $method))->getNumberOfParameters() === 2, 'Plain lookup retained an analysis context');
}
lookup_check(Scope_Lookup::functions($local, 'target') === [$first], 'Plain function selection changed');
lookup_check(Scope_Lookup::types($local, 'target') === [$integer], 'Plain type selection changed');
lookup_check(Scope_Lookup::constants($local, 'target') === [$constant], 'Plain constant selection changed');
foreach ([$local, $middle, $proxy, $outer] as $pool) {
	lookup_check($pool->preparation_lookups_named('target') === [], 'Plain lookup created subscriptions');
}

// Independent namespaces observe empty intermediate pools and the selected published pool.
lookup_check(Observed_Lookup::functions($local, 'target', $context) === [$first], 'Observed function selection differs');
lookup_check(Observed_Lookup::types($local, 'target', $context) === [$integer], 'Observed type selection differs');
lookup_check(Observed_Lookup::constants($local, 'target', $context) === [$constant], 'Observed constant selection differs');
foreach (preparation_lookup_kind::cases() as $kind) {
	lookup_path($owner, [$local, $middle, $outer], 'target', $kind);
}
lookup_check(count($owner->lookups) === 9, 'Namespaces collapsed their observations');
lookup_check($proxy->preparation_lookups_named('target') === [], 'Observed hidden publication proxy');
Observed_Lookup::functions($local, 'target', $context);
lookup_check(count($owner->lookups) === 9, 'Repeated lookup duplicated observations');
lookup_check($first->preparation_work_owner() === null && $second->preparation_work_owner() === null, 'Lookup completed or selected declaration work');

// Missing names are exact-case queries and observe the complete path too.
lookup_check(Observed_Lookup::functions($local, 'Target', $context) === [], 'Function lookup lost exact spelling');
lookup_check(Observed_Lookup::types($local, 'Target', $context) === [], 'Type lookup lost exact spelling');
lookup_check(Observed_Lookup::constants($local, 'Target', $context) === [], 'Constant lookup lost exact spelling');
foreach (preparation_lookup_kind::cases() as $kind) {
	lookup_path($owner, [$local, $middle, $outer], 'Target', $kind);
}

// A nearer ambiguous pool is returned intact; lookup must not fall back to an outer match.
$middle->register($second);
$middle->register($first);
$middle->register_type_as('target', $floating);
$middle->register_type_as('target', $integer);
$middle->register_constant($alternative);
$middle->register_constant($constant);
$near_owner = new file_body_work($parsed->collection, $parsed->root->body);
$context->owner = $near_owner;
lookup_check(Observed_Lookup::functions($local, 'target', $context) === [$second, $first], 'Function ambiguity was hidden');
lookup_check(Observed_Lookup::types($local, 'target', $context) === [$floating, $integer], 'Type ambiguity was hidden');
lookup_check(Observed_Lookup::constants($local, 'target', $context) === [$alternative, $constant], 'Constant ambiguity was hidden');
lookup_check(Scope_Lookup::functions($local, 'target') === [$second, $first], 'Plain nearest function pool differs');
lookup_check(Scope_Lookup::types($local, 'target') === [$floating, $integer], 'Plain nearest type pool differs');
lookup_check(Scope_Lookup::constants($local, 'target') === [$alternative, $constant], 'Plain nearest constant pool differs');
foreach (preparation_lookup_kind::cases() as $kind) {
	lookup_path($near_owner, [$local, $middle], 'target', $kind);
}
lookup_check(count($near_owner->lookups) === 6, 'Lookup visited beyond the nearest pool');
foreach ($local->preparation_lookups_named('target') as $observation) {
	lookup_check(isset($observation->dependents[$owner]) && isset($observation->dependents[$near_owner]), 'Shared observation lost a consumer');
}
echo "Observed lookup: plain purity, three namespaces, published paths, missing and ambiguous pools, shared identity passed\n";
