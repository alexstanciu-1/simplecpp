<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/boxed_dispatch.php';

function prepareBoxed(mixed $metadata, mixed $publication, array $facts, array &$plan): void {
    $m = familyMetadata($metadata, $publication, 'boxed', 'boxed', 'Boxed');
    if ($m === null) return;
    fields($m, ['access_contracts', 'mixed_predicates', 'binding', 'families', 'providers', 'scalar_kinds', 'integer_keys', 'string_keys', 'read', 'conversion', 'source_collection', 'transfer_assignment', 'initialization']);
    $literalInput = $m->initialization ?? null;
    requireThat($literalInput === null || $literalInput === 'ordered_family_literals', 'Unsupported boxed initialization');
    $transfer = privateValueTransfer($m);
    literals($m, ['binding' => 'private_inline_boxed', 'integer_keys' => 'nonnegative_int31',
        'string_keys' => 'classified_bytes', 'read' => 'same_family_null_on_miss', 'conversion' => 'explicit_scalar_only']);
    requireThat(names(at($m, 'families'), 'Duplicate boxed policy') === ['dynamic', 'mixed'], 'Unsupported boxed families');
    $source = at($m, 'source_collection');
    fields($source, ['count_method', 'empty_method', 'append_method', 'instance_field_default', 'key_types', 'methods']);
    literals($source, ['count_method' => 'container_size', 'empty_method' => 'empty', 'append_method' => 'append', 'instance_field_default' => 'null']);
    requireThat(names(at($source, 'key_types')) === ['int64', 'string'], 'Unsupported boxed source keys');
    fields($source->methods, ['has', 'remove', 'clear']);
    literals($source->methods, ['has' => 'source_has', 'remove' => 'source_remove', 'clear' => 'clear']);
    $sourceCollection = ['count_method' => $source->count_method, 'empty_method' => $source->empty_method, 'append_method' => $source->append_method, 'instance_field_default' => $source->instance_field_default, 'key_types' => names($source->key_types),
        'methods' => (array) $source->methods];
    if (!property_exists($publication, 'boxed')) return;
    $p = $publication->boxed;
    fields($p, ['header', 'families', 'scalar_conversions', 'scalar_dispatch', 'mixed_predicates']);
    $header = headerPath(at($p, 'header'));
    checkHeader($plan, $header);
    $requested = names(at($p, 'families'), 'Duplicate boxed family');
    requireThat($requested !== [], 'Empty boxed publication');
    foreach (names($p->mixed_predicates ?? [], 'Duplicate mixed predicate') as $name) {
        requireThat(in_array('mixed', $requested, true), 'Mixed predicates require mixed');
        requireThat(preg_match('/^is_[a-z]+$/D', $name) === 1, 'Invalid mixed predicate name');
        $kind = at(at($m, 'mixed_predicates'), $name);
        requireThat(in_array($kind, ['boolean', 'integer', 'floating', 'string', 'container', 'null'], true), 'Unsupported mixed predicate kind');
        $plan['mixed_predicates'][$name] = $kind;
    }
    $convert = boolean(at($p, 'scalar_conversions'));
    requireThat(!$convert || $requested === ['dynamic', 'mixed'], 'Boxed scalar conversion requires both families');
    requireThat(hasType($plan, 'int64') && hasValue($plan, 'bool_t') && hasValue($plan, 'float64') && isset($plan['string']), 'Boxed requires int64, bool_t, float64 and string');
    requireThat(hasCast($plan, 'int64', 'long long', true) && hasCast($plan, 'float64', 'double', true), 'Boxed requires native scalar transport');
    foreach (array_merge($requested, ['boxed_kind']) as $name) {
        requireThat(!property_exists($plan['aliases'], $name), 'Conflicting boxed name');
    }
    $families = [];
    foreach ($requested as $family) {
        requireThat(in_array($family, ['mixed', 'dynamic'], true), 'Unsupported boxed family');
        requireThat(isset($facts['boxed'][$family]), 'Boxed requires measured native layout');
        $families[] = ['name' => $family, 'native' => 'detail::'.$family.'_value',
            'copy' => $family === 'mixed' ? 'cow' : 'shared_container_identity',
            'traversal' => collectionTraversalPlan(), 'source_collection' => $sourceCollection,
            'null_write' => $family === 'mixed' ? 'bootstrap' : 'reject'] + $facts['boxed'][$family];
    }
    if (boolean($p->scalar_dispatch ?? false)) {
        foreach ($requested as $family) publishTypeExports($plan['submodules'], $family, [$family]);
        $plan['boxed_dispatch'] = boxedScalarPlan($metadata, $facts, $plan['submodules'], $plan['explicit_formatters'] ?? []);
    }
    $plan['boxed'] = ['initialization' => $literalInput, 'transfer_assignment' => $transfer, 'header' => $header, 'families' => $families,
        'scalar_conversions' => $convert, 'integer_keys' => [0, 2147483647],
        'string_keys' => 'classified_bytes', 'read' => 'same_family_null_on_miss',
        'operations' => ['from_boolean', 'from_integer', 'from_floating', 'from_string', 'container', 'kind',
            'get_boolean', 'get_integer', 'get_floating', 'get_string', 'read', 'has', 'set', 'remove', 'append', 'container_size', 'empty', 'is_packed_container', 'clean', 'clear', 'iteration_limit', 'iteration_next'],
        'build' => ['generated_sources' => ['src/boxed.cpp'], 'requires' => 'scpp_boxed_storage']];
}
