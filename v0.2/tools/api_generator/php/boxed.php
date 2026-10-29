<?php
declare(strict_types=1);
namespace Scpp\Api;

function prepareBoxed(mixed $metadata, mixed $publication, array $facts, array &$plan): void {
    $m = familyMetadata($metadata, $publication, 'boxed', 'boxed', 'Boxed');
    if ($m === null) return;
    fields($m, ['binding', 'families', 'integer_keys', 'string_keys', 'read', 'conversion']);
    literals($m, ['binding' => 'private_inline_boxed', 'integer_keys' => 'nonnegative_int31',
        'string_keys' => 'classified_bytes', 'read' => 'same_family_null_on_miss', 'conversion' => 'explicit_scalar_only']);
    requireThat(names(at($m, 'families'), 'Duplicate boxed policy') === ['dynamic', 'mixed'], 'Unsupported boxed families');
    if (!property_exists($publication, 'boxed')) return;
    $p = $publication->boxed;
    fields($p, ['header', 'families', 'scalar_conversions']);
    $header = headerPath(at($p, 'header'));
    checkHeader($plan, $header);
    $requested = names(at($p, 'families'), 'Duplicate boxed family');
    requireThat($requested !== [], 'Empty boxed publication');
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
            'null_write' => $family === 'mixed' ? 'bootstrap' : 'reject'] + $facts['boxed'][$family];
    }
    $plan['boxed'] = ['header' => $header, 'families' => $families,
        'scalar_conversions' => $convert, 'integer_keys' => [0, 2147483647],
        'string_keys' => 'classified_bytes', 'read' => 'same_family_null_on_miss',
        'operations' => ['from_boolean', 'from_integer', 'from_floating', 'from_string', 'container', 'kind',
            'get_boolean', 'get_integer', 'get_floating', 'get_string', 'read', 'has', 'set', 'remove', 'append', 'container_size', 'is_packed_container', 'clean'],
        'build' => ['generated_sources' => ['src/boxed.cpp'], 'requires' => 'scpp_boxed_storage']];
}
