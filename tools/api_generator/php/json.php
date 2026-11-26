<?php
declare(strict_types=1);
namespace Scpp\Api;

require_once __DIR__ . '/json_handles.php';

const JSON_RECORD_READERS = ['int64' => 'read_integer', 'bool_t' => 'read_boolean', 'string' => 'read_string'];
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
    fields($p, ['header', 'operations', 'document', 'pull_reader']);
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
    prepareJsonHandles($p, $header, $plan);
    requireThat($operations !== [] || isset($plan['json_handles']), 'Empty JSON publication');
    $plan['json'] = ['module' => 'json', 'namespace' => 'scpp::json', 'header' => $header,
        'key_limit' => 'checked_decode_error', 'operations' => $operations,
        'build' => ['target' => 'scpp_json', 'requires' => 'scpp_runtime',
            'generated_sources' => ['src/json.cpp'], 'private_sources' => ['runtime/src/json/json_native.cpp'],
            'private_includes' => ['runtime/src/json', 'runtime/src/boxed', 'runtime/src/hash', 'runtime/src/ownership', 'runtime/src/numeric']]];
}

/** Select source signatures only when the active compiler also publishes their types. */
function compilerJsonCases(?array $runtime, array $types): array {
    $cases = [];
    $encoders = [];
    foreach ($runtime['json']['operations'] ?? [] as $op) {
        if (!isset($types[$op['argument']], $types[$op['result']])) continue;
        $argument = 'type_' . $types[$op['argument']];
        $result = 'type_' . $types[$op['result']];
        $name = 'json_' . $op['name'];
        $header = moduleOperationHeader($runtime['json']['header'], $op['id']);
        $binding = "new runtime_function('$name', [$argument::instance()], $result::instance(), '::scpp::json::{$op['name']}', '$header', const_reference_mask: 1, required_module: 'runtime-json', exact_arguments: true)";
        if ($op['id'] === 'decode') {
            $leaves = [];
            foreach (JSON_RECORD_READERS as $id => $method) {
                if (isset($types[$id])) $leaves[] = 'type_' . $types[$id] . "::class => '$method'";
            }
            $cases[] = "\t\t\t'php:func:json_decode_as', 'general:func:json_decode_as' => \$bindings[\$key] ??= new json_decoder_contract(["
                . implode(', ', $leaves) . "], '" . moduleOperationHeader($runtime['json']['header'], 'decode_record') . "'),";
        }
        if ($name === 'json_encode') {
            $encoders[] = "$argument::class => $binding";
        } else {
            $keys = $name === 'json_decode' ? "'php:func:$name', " : '';
            $cases[] = "\t\t\t$keys'general:func:$name' => \$bindings[\$key] ??= $binding,";
        }
    }
    if ($encoders !== []) {
        $cases[] = "\t\t\t'php:func:json_encode', 'general:func:json_encode' => \$bindings[\$key] ??= new json_encoder_overloads(["
            . implode(', ', $encoders) . "], '" . moduleOperationHeader($runtime['json']['header'], 'encode_record') . "'),";
    }
    return array_merge($cases, compilerJsonDocumentCases($runtime, $types), compilerJsonReaderCases($runtime, $types));
}
