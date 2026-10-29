<?php
declare(strict_types=1);
namespace Scpp\Api;

const JSON_OPERATIONS = [
    'decode' => ['decode', 'dynamic', 'result_dynamic', 'string'],
    'decode_mixed' => ['decode_mixed', 'mixed', 'result_mixed', 'string'],
    'encode_mixed' => ['encode', 'mixed', 'result_string', 'mixed'],
    'encode_dynamic' => ['encode', 'dynamic', 'result_string', 'dynamic'],
    'encode_int64' => ['encode', null, 'result_string', 'int64'],
    'encode_float64' => ['encode', null, 'result_string', 'float64'],
    'encode_bool_t' => ['encode', null, 'result_string', 'bool_t'],
    'encode_string' => ['encode', null, 'result_string', 'string'],
    'encode_nullable_int64' => ['encode', null, 'result_string', 'nullable_int64', 'int64'],
    'encode_nullable_float64' => ['encode', null, 'result_string', 'nullable_float64', 'float64'],
    'encode_nullable_bool_t' => ['encode', null, 'result_string', 'nullable_bool_t', 'bool_t'],
    'encode_nullable_string' => ['encode', null, 'result_string', 'nullable_string', 'string'],
];
function prepareJson(mixed $metadata, mixed $publication, array &$plan): void {
    $m = familyMetadata($metadata, $publication, 'json', 'json', 'JSON');
    if ($m === null) return;
    fields($m, ['binding', 'key_limit', 'operations']);
    literals($m, ['binding' => 'native_json', 'key_limit' => 'checked_decode_error']);
    $registered = names(at($m, 'operations'), 'Duplicate JSON operation policy');
    requireThat($registered !== [], 'Empty JSON policy');
    foreach ($registered as $id) requireThat(isset(JSON_OPERATIONS[$id]), 'Unsupported JSON operation');
    if (!property_exists($publication, 'json')) return;
    $p = $publication->json;
    fields($p, ['header', 'operations']);
    $header = headerPath(at($p, 'header'));
    checkHeader($plan, $header);
    requireThat(!property_exists($plan['aliases'], 'json'), 'Conflicting JSON namespace');
    requireThat(isset($plan['string']), 'JSON requires published string');
    $operations = [];
    foreach (names(at($p, 'operations'), 'Duplicate JSON operation') as $id) {
        requireThat(in_array($id, $registered, true), 'Unregistered JSON operation');
        [$name, $family, $result, $argument] = JSON_OPERATIONS[$id];
        $nullablePayload = JSON_OPERATIONS[$id][4] ?? null;
        if ($family !== null) {
            requireThat(in_array($family, array_column($plan['boxed']['families'] ?? [], 'name'), true), 'JSON requires published boxed family');
        } elseif ($nullablePayload !== null) {
            $matches = array_filter($plan['carriers'], fn(array $carrier): bool =>
                $carrier['name'] === $argument && $carrier['family'] === 'nullable' && $carrier['payload'] === $nullablePayload);
            requireThat($matches !== [], 'JSON requires published nullable argument');
        } else {
            requireThat($argument === 'string' || hasType($plan, $argument) || hasValue($plan, $argument), 'JSON requires published scalar argument');
        }
        $transportType = $nullablePayload ?? $argument;
        $transport = $transportType === 'string' ? 'bytes' : ($family !== null ? 'boxed' : $transportType);
        requireThat(in_array($result, array_column($plan['carriers'], 'name'), true), 'JSON requires published result carrier');
        $operations[] = compact('id', 'name', 'family', 'result', 'argument', 'transport', 'nullablePayload');
    }
    requireThat($operations !== [], 'Empty JSON publication');
    $plan['json'] = ['module' => 'json', 'namespace' => 'scpp::json', 'header' => $header,
        'key_limit' => 'checked_decode_error', 'operations' => $operations,
        'build' => ['target' => 'scpp_json', 'requires' => 'scpp_runtime',
            'generated_sources' => ['src/json.cpp'], 'private_sources' => ['runtime/src/json/json_native.cpp'],
            'private_includes' => ['runtime/src/json', 'runtime/src/boxed', 'runtime/src/hash', 'runtime/src/ownership', 'runtime/src/numeric']]];
}
