<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{Source_Frontend, source_file, flavor, entry_target, type_definition, type_registry};

/** Produce one independent nominal generation; the caller controls its lifetime. */
function definition(string $name, bool $owner = false): type_definition
{
	$text = 'struct ' . $name . ' { public int $x; } function accept(' . ($owner ? 'shared<' . $name . '>' : $name) . ' $value): void {} return 8;';
	$prepared = (new Source_Frontend())->prepare(new source_file('key-contract', $text, flavor::php), new entry_target(31));
	return $prepared->file->functions()[0]->parameters[0]->type_def();
}

/** Retired values and specializations must not be kept alive by a retained key or a runtime singleton. */
function retirement(type_definition $key): void
{
	$value = definition('Payload');
	$first = $key->keyed_collection_type('hash', $value);
	if ($first === null || $first !== $key->keyed_collection_type('hash', $value)) throw new RuntimeException('Canonical pair lost');
	$next = definition('Payload');
	$replacement = $key->keyed_collection_type('hash', $next);
	if ($first === $replacement || $replacement->value_type !== $next) throw new RuntimeException('Reused retired value generation');
	$old_value = WeakReference::create($value);
	$old_hash = WeakReference::create($first);
	unset($value, $first);
	gc_collect_cycles();
	if ($old_hash->get() !== null || $old_value->get() !== null) throw new RuntimeException('Key cache pins retired generation');
	if ($replacement !== $key->keyed_collection_type('hash', $next)) throw new RuntimeException('Live replacement lost');
}

$key = definition('Point', true);
retirement($key);
retirement(type_registry::integer());
if (!$key->hash_key_supported() || !str_contains($key->lower_cpp_hash_key('owner'), '.ptr')
	|| str_contains($key->lower_cpp_hash_key('owner'), '.view')) throw new RuntimeException('Wrong shared identity');
if (type_registry::resolve('float')->hash_key_supported()) throw new RuntimeException('Unrequested key family activated');
echo "Common key contract and weak canonical cache retirement passed\n";
