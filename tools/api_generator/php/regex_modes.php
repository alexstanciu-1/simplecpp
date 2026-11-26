<?php
declare(strict_types=1);
namespace Scpp\Api;

/** The same row publication supplies configure-time headers, compiler signatures and archive instantiations. */
function regexCallbackRows(object $catalog, object $request, object $callback): array {
    if (!isset($callback->capture_rows)) return [];
    $rows = ['mixed' => ['flags' => -1, 'cell' => 'mixed', 'named' => true]];
    $cells = ['string' => 0];
    foreach (names($request->capture_modes ?? []) as $mode) {
        $definition = at($catalog->capture_modes, $mode);
        $cells[$definition->cell] = ($definition->offset ? $catalog->php_constants->PREG_OFFSET_CAPTURE : 0)
            | ($definition->nullable ? $catalog->php_constants->PREG_UNMATCHED_AS_NULL : 0);
    }
    foreach (names($callback->capture_rows) as $kind) {
        requireThat(in_array($kind, ['numbered', 'named'], true), 'Unknown regex callback row');
        $named = $kind === 'named';
        foreach ($cells as $cell => $flags) {
            $row = $named ? "hash_{$cell}_string" : "vector_$cell";
            $rows[$row] = compact('cell', 'flags', 'named');
        }
    }
    return $rows;
}

/** Expand only selected operation/mode pairs. The base signature owns arity and the writable selector. */
function regexModeVariants(object $catalog, object $request): array {
    $variants = [];
    foreach (names($request->capture_modes ?? []) as $mode) {
        requireThat(recordIdentifier($mode), 'Invalid regex mode name');
        $definition = at(at($catalog, 'capture_modes'), $mode);
        fields($definition, ['cell', 'offset', 'nullable']);
        foreach (names($request->functions) as $base) {
            $layout = $catalog->capture_operations->$base ?? null;
            if ($layout === null) {
                // A return-valued operation already has a complete signature in the catalog.
                if (($catalog->functions->$base->result ?? null) === 'result_or_false_vector_' . $definition->cell)
                    $variants[$base] = [$base, $mode, $definition, null];
                continue;
            }
            $variants[$base . '_' . $mode] = [$base, $mode, $definition, $layout];
        }
    }
    // Callback publications also consume cells without requiring a preg_match overload.
    foreach ($request->callbacks ?? [] as $callback) {
        foreach ($catalog->callbacks->$callback->capture_rows ?? [] as $keys) {
            foreach ($request->capture_modes ?? [] as $mode) {
                $variants["callback_type_{$keys}_$mode"] = [null, $mode, $catalog->capture_modes->$mode, ['single', $keys]];
            }
        }
    }
    return $variants;
}

/** Instantiate a capture vector using the string vector's validated storage and traversal binding. */
function regexCaptureVector(array $prototype, string $type, string $element, string $source): array {
    $v = $prototype;
    $v['name'] = $type;
    $v['element'] = $element;
    $v['source_type'] = $source;
    $v['native_value'] = '';
    $v['operations'] = $v['casts_from'] = [];
    $v['initialization'] = ['bulk' => null];
    $v['methods'] = [];
    foreach ($prototype['methods'] as $method) {
        if (in_array('const char*', $method['arguments'], true)) continue;
        $method['arguments'] = str_replace('string', $element, $method['arguments']);
        if ($method['result'] === 'string') $method['result'] = $element;
        $v['methods'][] = $method;
    }
    return $v;
}

function prepareRegexModes(object $catalog, object $request, string $moduleHeader, array &$plan): array {
    $variants = regexModeVariants($catalog, $request);
    if (!$variants) return [];
    literals($catalog->php_constants, ['PREG_OFFSET_CAPTURE' => 256, 'PREG_UNMATCHED_AS_NULL' => 512]);
    $provider = 'regex.flag_types';
    $header = moduleOperationHeader($moduleHeader, 'flag_types');
    checkHeader($plan, $header);
    $plan['submodules']['nodes'][$provider] = ['module' => 'core', 'header' => $header, 'requires' => []];
    requireThat(in_array('nullable_string', array_column($plan['carriers'] ?? [], 'name'), true), 'Capture modes require nullable<string>');
    $requires = exportProviders($plan['submodules'], ['string', 'int64', 'bool_t', 'nullable_string']);
    $plan['regex_mode_publication'] = compact('provider', 'header', 'requires');
    $vector = array_column($plan['vectors'] ?? [], null, 'name')['vector_string'] ?? null;
    $hash = array_column($plan['hashes']['specializations'] ?? [], null, 'name')['hash_string_string'] ?? null;
    requireThat($vector && $hash, 'Capture modes require the string vector and string hash');
    $signatures = [];
    foreach ($variants as $name => [$base, $mode, $definition, $layout]) {
        [$order, $keys] = $layout === null ? ['single', 'numbered'] : listValue($layout);
        requireThat(in_array($order, ['single', 'both', 'rows', 'columns', 'fixed_rows', 'fixed_columns'], true)
            && in_array($keys, ['named', 'numbered'], true), 'Unknown regex capture layout');
        $cell = textValue($definition->cell);
        requireThat(recordIdentifier($cell), 'Invalid regex capture cell');
        $offset = boolean($definition->offset);
        $nullable = boolean($definition->nullable);
        requireThat($offset || ($nullable && $cell === 'nullable_string'), 'Invalid regex capture representation');
        $flags = ($offset ? $catalog->php_constants->PREG_OFFSET_CAPTURE : 0) | ($nullable ? $catalog->php_constants->PREG_UNMATCHED_AS_NULL : 0);
        if (!isset($plan['regex_modes'][$mode])) {
            requireThat(!in_array($flags, array_column($plan['regex_modes'] ?? [], 'flags'), true), 'Duplicate regex capture flags');
            requireThat(!$offset || !isset($plan['submodules']['type_exports'][$cell]), 'Regex capture type name already published');
            $plan['regex_modes'][$mode] = compact('cell', 'flags', 'offset', 'nullable');
        }
        if ($offset) {
            $plan['regex_records'][$cell] = compact('header') + ['module' => 'runtime-regex', 'strategy' => 'transport',
                'fields' => ['text' => $nullable ? 'nullable_string' : 'string', 'byte_offset' => 'int64']];
            $plan['submodules']['type_exports'][$cell] = ['symbol' => 'scpp::' . $cell, 'provider' => $provider];
        }
        $column = "vector_$cell";
        $row = $keys === 'numbered' ? $column : "hash_{$cell}_string";
        $outer = match ($order) {
            'single' => $row,
            'columns', 'fixed_columns' => $keys === 'named' ? "hash_{$column}_string" : "vector_$column",
            default => "vector_$row",
        };
        // Publish inner shapes before the enclosing hash/vector.
        foreach (array_unique([$column, $row, $outer]) as $type) {
            if (isset($plan['regex_shapes'][$type])) continue;
            $isVector = str_starts_with($type, 'vector_');
            $element = $isVector ? substr($type, 7) : substr($type, 5, -7);
            $sourceElement = $plan['regex_shapes'][$element] ?? ($element === 'nullable_string' ? 'nullable<string>' : $element);
            $source = $isVector ? "vector<$sourceElement>" : "hash<$sourceElement,string>";
            $plan['regex_shapes'][$type] = $source;
            $plan['submodules']['type_exports'][$type] = ['symbol' => 'scpp::' . $type, 'provider' => $provider];
            if ($isVector) {
                $plan['regex_mode_vectors'][$type] = regexCaptureVector($vector, $type, $element, $source);
            } else {
                $h = $hash;
                $h['name'] = $type;
                $h['value'] = $element;
                $h['methods'] = [];
                foreach ($hash['methods'] as $method) {
                    if ($method['name'] === 'find') continue;
                    if ($method['name'] === 'at') $method['result'] = $element;
                    if ($method['name'] === 'set') $method['arguments'][1] = $element;
                    $h['methods'][] = $method;
                }
                $plan['regex_mode_hashes'][$type] = $h;
            }
        }
        if ($base === null) continue;
        if ($layout === null) {
            $carrierName = $catalog->functions->$base->result;
            if (!isset($plan['submodules']['type_exports'][$carrierName])) {
                $prototype = array_column($plan['carriers'], null, 'name')['result_or_false_vector_string'] ?? null;
                requireThat($prototype !== null, 'Capture results require a falseable vector carrier');
                $plan['regex_carriers'][] = ['name' => $carrierName, 'payload' => $column, 'header' => $header] + $prototype;
                $plan['submodules']['type_exports'][$carrierName] = ['symbol' => 'scpp::' . $carrierName, 'provider' => $provider];
            }
            continue;
        }
        $binding = clone $catalog->functions->$base;
        $binding->native_name = $name;
        $binding->parameters[2] = (object)['type' => $outer, 'native_passing' => 'reference'];
        $signatures[$name] = $binding;
        $plan['regex_mode_bindings'][$name] = compact('order', 'keys', 'flags', 'cell', 'row', 'column');
    }
    return $signatures;
}

function renderRegexModeTypes(array $plan, array &$files, array &$groups): void {
    if (!isset($plan['regex_mode_publication'])) return;
    $publication = $plan['regex_mode_publication'];
    $decl = $body = $hashBody = $isset = '';
    foreach ($plan['regex_records'] ?? [] as $name => $record) {
        $nullable = $record['fields']['text'] === 'nullable_string';
        $result = $nullable ? 'nullable_string' : 'string';
        $read = $nullable ? 'return start_ < 0 ? nullable_string{} : nullable_string{value_};' : 'return value_;';
        $decl .= "namespace scpp {\nclass $name final {\n    string value_;\n    int64 start_ = -1;\npublic:\n"
            . "    $name() noexcept = default;\n    $name(string value, int64 start) noexcept : value_(static_cast<string&&>(value)), start_(start) {}\n"
            . "    $result text() const { $read }\n    int64 byte_offset() const noexcept { return start_; }\n};\n}\n";
    }
    foreach (array_keys($plan['regex_shapes'] ?? []) as $name) {
        if (isset($plan['regex_mode_vectors'][$name])) {
            $copy = $plan;
            $copy['vectors'] = [$plan['regex_mode_vectors'][$name]];
            $copy['string_functions'] = [];
            [$h, $d] = renderVectors($copy, 'access_' . $name);
            $decl .= $h;
            $body .= $d;
        } else {
            [$h, $d] = renderHashTypes($plan, [$plan['regex_mode_hashes'][$name]], false);
            $decl .= $h;
            $hashBody .= $d;
            $value = $plan['regex_mode_hashes'][$name]['value'];
            $test = $value === 'nullable_string' ? 'hash.has(key) && hash.at(key).has_value()' : 'hash.has(key)';
            $isset .= "inline bool isset(const ::scpp::$name& hash, const ::scpp::string& key) { return $test; }\n";
        }
    }
    $header = $publication['header'];
    foreach ($plan['regex_carriers'] ?? [] as $carrier) {
        if ($carrier['header'] !== $header) continue;
        [$h, $d] = renderCarrier($plan, $carrier);
        $decl .= "namespace scpp {\n$h}\n";
        $body .= $d;
    }
    $files['src/hash.cpp'] .= includeHeader($header) . $hashBody;
    $files['src/runtime.cpp'] .= includeHeader($header) . "namespace scpp {\n$body}\n";
    $files['public/scpp/php/regex_hash_isset.hpp'] = BANNER . "#pragma once\n" . includeHeader($header)
        . "namespace scpp::php {\n$isset}\n";
    $groups[] = ['provider' => $publication['provider'], 'declarations' => $decl, 'system' => []];
}

/** The archive instantiates the shared collector for precisely the published signatures. */
function renderRegexModeBindings(array $plan): string {
    $code = BANNER . "namespace scpp::regex {\n";
    foreach ($plan['regex_mode_bindings'] ?? [] as $name => $binding) {
        ['order' => $order, 'keys' => $keys, 'flags' => $flags, 'cell' => $cell,
            'column' => $column, 'row' => $row] = $binding;
        $function = $plan['regex']['functions'][$name];
        $parameters = [];
        $fixed = str_starts_with($order, 'fixed_');
        $layout = $fixed ? substr($order, 6) : $order;
        $named = $keys === 'named' ? 'true' : 'false';
        $labels = $fixed ? ['pattern', 'subject', 'matches', 'offset'] : ['pattern', 'subject', 'matches', 'flags', 'offset'];
        foreach ($function['parameters'] as $i => $parameter) {
            $parameters[] = $parameter['native'] . ' ' . $labels[$i];
            if ($i + 1 < $function['required_parameters']) continue;
            $offset = $labels[$i] === 'offset' ? 'offset' : '0';
            $actual = $fixed ? (string)($flags | ($layout === 'rows' ? 2 : 0)) : ($i >= 3 ? 'flags' : '0');
            $code .= 'result_or_false_int64 ' . $name . '(' . implode(', ', $parameters) . ") {\n"
                . "    return collect_flagged<capture_order::$layout, $named, $flags, $cell, $row, $column>(pattern, subject, matches, $actual, $offset);\n}\n";
        }
    }
    foreach ($plan['regex']['callback_rows'] ?? [] as $row => $shape) {
        $named = $shape['named'] ? 'true' : 'false';
        foreach ($shape['subjects'] as $subject => $_) {
            $result = 'result_or_false_' . $subject;
            $code .= "$result replace_capture_callback(const string& pattern, const void* ctx, callback_row_$row invoke, "
                . "const $subject& subject, int64 limit, int64* count, int64 flags) {\n";
            if ($row === 'mixed') {
                $code .= "    return mixed_callback<$result>(pattern, ctx, invoke, subject, limit, count, flags);\n";
            } else {
                $code .= "    if (flags != {$shape['flags']}) throw ValueError(\"capture flags do not match callback row\");\n"
                    . "    return capture_callback<{$shape['flags']}, $named, {$shape['cell']}, $row, $result>(pattern, ctx, invoke, subject, limit, count);\n";
            }
            $code .= "}\n";
        }
    }
    return $code . "}\n";
}
