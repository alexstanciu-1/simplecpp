<?php
declare(strict_types=1);
namespace Scpp\Api;

// Source signatures are shared by native declaration and compiler binding emission.
const JSON_DOCUMENT_OPERATIONS = [
    'document_parse' => ['result_json_document','string','nullable_json_parse_error','int64'],
    'document_root' => ['result_json_node','json_document'],
    'node_kind' => ['result_string','json_node'], 'node_size' => ['result_int64','json_node'],
    'node_at' => ['result_json_node','json_node','int64'], 'node_key' => ['result_string','json_node','int64'],
    'node_has' => ['result_bool_t','json_node','string'], 'node_member' => ['result_json_node','json_node','string'],
    'node_string' => ['result_string','json_node'], 'node_number' => ['result_string','json_node'],
    'node_int' => ['result_int64','json_node'], 'node_boolean' => ['result_bool_t','json_node'],
];

function prepareJsonHandles(mixed $publication, string $header, array &$plan): void {
    if (!boolean($publication->document ?? false)) return;
    $header = moduleOperationHeader($header, 'document');
    foreach (['json_document','json_node','json_parse_error'] as $type) {
        $plan['json_handles'][$type] = ['header' => $header, 'module' => 'runtime-json', 'strategy' => 'json_handle'];
    }
    $plan['json_handles']['json_parse_error']['fields'] = ['category' => 'string', 'message' => 'string', 'byte_offset' => 'int64'];
    $carriers = array_column($plan['carriers'] ?? [], null, 'name');
    foreach (['result_bool_t','result_int64','result_string','nullable_string'] as $type) {
        requireThat(isset($carriers[$type]), 'JSON documents require ' . $type);
    }
    foreach (['json_document','json_node','json_parse_error'] as $payload) {
        $family = $payload === 'json_parse_error' ? 'nullable' : 'result';
        $name = $family . '_' . $payload;
        $plan['json_carriers'][] = compact('name', 'payload', 'header') + $carriers[$family . '_string'];
    }
}

function compilerJsonDocumentCases(?array $runtime, array $types): array {
    $cases = [];
    if (!isset($runtime['json_handles']['json_document'])) return $cases;
    $header = $runtime['json_handles']['json_document']['header'];
    foreach (JSON_DOCUMENT_OPERATIONS as $method => $signature) {
        $result = $types[array_shift($signature)];
        $arguments = array_map(static fn($t) => "type_{$types[$t]}::instance()", $signature);
        $extra = $method === 'document_parse' ? ', required_parameters: 2, reference_mask: 2' : '';
        $borrows = 0;
        foreach ($signature as $i => $t) if ($t !== 'int64' && $t !== 'nullable_json_parse_error') $borrows |= 1 << $i;
        $name = "json_$method";
        $cases[] = "\t\t\t'general:func:$name' => \$bindings[\$key] ??= new runtime_function('$name', ["
            . implode(', ', $arguments) . "], type_$result::instance(), '::scpp::json::$method', '$header', const_reference_mask: $borrows$extra, required_module: 'runtime-json', exact_arguments: true),";
    }
    return $cases;
}

function renderJsonHandles(array $plan, array &$files): void {
    if (!isset($plan['json_handles']['json_document'])) return;
    $header = $plan['json_handles']['json_document']['header'];
    $h = BANNER . "#pragma once\n" . includeHeader($plan['carriers_header']) . template('json/document_types') . "namespace scpp {\n";
    $d = includeHeader($header) . includeHeader('../sequence/carrier_semantics.hpp') . "namespace scpp {\n";
    foreach ($plan['json_carriers'] as $carrier) {
        [$decl, $impl] = renderCarrier($plan, $carrier);
        $h .= $decl;
        $d .= $impl;
    }
    $h .= "}\nnamespace scpp::json {\n";
    foreach (JSON_DOCUMENT_OPERATIONS as $method => $signature) {
        $result = array_shift($signature);
        $args = array_map(static fn($t) => match ($t) {
            'int64' => 'int64', 'nullable_json_parse_error' => "$t&", default => "const $t&"
        }, $signature);
        if ($method === 'document_parse') $args[2] .= ' = 128';
        $h .= "$result $method(" . implode(', ', $args) . ");\n";
    }
    $files['public/' . $header] = $h . "}\n";
    $files['src/json.cpp'] .= $d . "}\n" . includeHeader('document_impl.hpp');
}
