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
    if (boolean($publication->pull_reader ?? false)) {
        foreach (['result_bool_t','result_int64','result_string'] as $type) moduleFunctionTypeExport($plan, $type);
        $plan['json_handles']['json_pull_reader'] = ['header' => moduleOperationHeader($header, 'pull_reader'),
            'module' => 'runtime-json', 'strategy' => 'json_handle'];
    }
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

const JSON_READER_METHODS = [
    'start' => ['result_bool_t','string','string','int64'], 'kind' => ['result_string'],
    'begin_object' => ['result_bool_t'], 'begin_array' => ['result_bool_t'],
    'next_property' => ['result_bool_t'], 'next_element' => ['result_bool_t'],
    'property_name' => ['result_string'], 'read_string' => ['result_string'], 'read_number' => ['result_string'],
    'read_integer' => ['result_int64'], 'read_boolean' => ['result_bool_t'], 'read_null' => ['result_bool_t'],
    'finish_document' => ['result_bool_t'], 'reject' => ['result_bool_t','string'],
];

function compilerJsonReaderCases(?array $runtime, array $types): array {
    if (!isset($runtime['json_handles']['json_pull_reader'])) return [];
    $header = $runtime['json_handles']['json_pull_reader']['header'];
    $cases = ["\t\t\t'general:func:json_pull_reader_create' => \$bindings[\$key] ??= new runtime_function('json_pull_reader_create', [], type_json_pull_reader::instance(), '::scpp::json_pull_reader', '$header', required_module: 'runtime-json'),"];
    foreach (JSON_READER_METHODS as $name => $signature) {
        $result = $types[array_shift($signature)];
        $args = ['type_json_pull_reader::instance()'];
        foreach ($signature as $type) $args[] = "type_{$types[$type]}::instance()";
        $keys = array_map(static fn($f) => "'$f:json_pull_reader:$name'", ['php','cpp','js','python']);
        $cases[] = "\t\t\t" . implode(', ', $keys) . " => \$bindings[\$key] ??= new runtime_function('$name', ["
            . implode(', ', $args) . "], type_$result::instance(), '$name', '$header', receiver_method: true, required_module: 'runtime-json', exact_arguments: true),";
    }
    return $cases;
}

function renderJsonReader(array $plan, array &$files): void {
    if (!isset($plan['json_handles']['json_pull_reader'])) return;
    $header = $plan['json_handles']['json_pull_reader']['header'];
    $declarations = '';
    $definitions = '';
    foreach (JSON_READER_METHODS as $name => $signature) {
        $result = array_shift($signature);
        $args = [];
        foreach ($signature as $i => $type) $args[] = ($type === 'string' ? 'const string&' : $type) . " arg$i";
        $call = $name . '(' . implode(', ', $args) . ') const';
        $declarations .= "    $result $call;\n";
        if ($name === 'start') continue;
        $work = match (true) {
            $name === 'reject' => 'cursor.reject(std::string(arg0.data(), arg0.size())); return false;',
            in_array($name, ['begin_object','begin_array','read_null','finish_document'], true) => "cursor.$name(); return true;",
            $result === 'result_string' => "const auto text = cursor.$name(); return string{text.data(), detail::checked_integer_cast<int64>(text.size())};",
            default => "return cursor.$name();",
        };
        $capture = $signature === [] ? '[]' : '[&]';
        $definitions .= "$result json_pull_reader::$call {\n    return detail::json::read_checked<$result>(data_ ? data_->cursor.get() : nullptr, $capture(auto& cursor) { $work });\n}\n";
    }
    if (in_array('decode', array_column($plan['json']['operations'], 'id'), true)) {
        $declarations .= "    void with_decoder(void*, void (*)(void*, Decoder&)) const;\n";
        $definitions .= "void json_pull_reader::with_decoder(void* out, void (*consume)(void*, Decoder&)) const {\n"
            . "    if (!data_ || !data_->cursor) throw std::runtime_error(\"JSON reader is not initialized\");\n"
            . "    json::record_reader reader{*data_->cursor};\n    consume(out, reader);\n}\n";
    }
    $files['public/' . $header] = moduleHeaderBody([$plan['carriers_header']], 'scpp',
        "class Decoder;\n" . template('json/pull_reader_type', ['methods' => $declarations]));
    $files['src/json.cpp'] .= includeHeader($header) . includeHeader('pull_reader_impl.hpp') . "namespace scpp {\n$definitions}\n";
}
