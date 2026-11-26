<?php
declare(strict_types=1);
namespace Scpp\Api;

function renderJson(array $plan, array &$files): void {
    if (!isset($plan['json'])) return;
    $module = $plan['json'];
    $count = $plan['string']['count_representation'];
    $boxedHeader = array_filter(array_column($module['operations'], 'family')) !== [] ? includeHeader($plan['boxed']['header']) : '';
    $h = BANNER . "#pragma once\n" . includeHeader($plan['carriers_header']) . $boxedHeader . includeHeader($plan['string']['header']) . "namespace scpp::json {\n";
    $implementationHeaders = BANNER . includeHeader($module['header']) . includeHeader('json_native.hpp') . includeHeader('own_string.hpp') . includeHeader('ownership_semantics.hpp') . includeHeader('scalar_semantics.hpp');
    $d = "";
    $signatures = [];
    $recordIncludes = [];
    $recordDeclarations = '';
    $recordDefinitions = '';
    $encoderDeclarations = $encoderMethods = '';
    foreach ($module['operations'] as $op) {
        ['name' => $name, 'family' => $family, 'result' => $result, 'argument' => $argument] = $op;
        $signature = "$result $name(const $argument& input)";
        $header = moduleOperationHeader($module['header'], $op['id']);
        $includes = [];
        foreach ([$argument, $result] as $identity) {
            if ($identity === $family && !isset($plan['boxed_dispatch'])) {
                $includes[] = $plan['boxed']['header'];
                continue;
            }
            $export = typeExport($plan['submodules'], $identity);
            $includes[] = providerHeader($plan['submodules'], $export['provider']);
        }
        $files['public/' . $header] = moduleHeaderBody(array_unique($includes), 'scpp::json', "$signature;\n");
        $h .= "$signature;\n";
        $signatures[$name][] = ["const $argument&"];
        $decode = $name !== 'encode';
        $source = $op['nullablePayload'] !== null ? 'value' : 'input';
        $input = jsonNativeArgument($op, $source);
        if (!$decode) {
            foreach ($includes as $include) $recordIncludes[$include] = includeHeader($include);
            $encoderInput = $op['nullablePayload'] === null && !in_array($op['transport'], ['bytes', 'boxed'], true)
                ? "$argument input" : "const $argument& input";
            $encoderDeclarations .= "    virtual void write($encoderInput) = 0;\n";
            $encoderMethods .= "    void write($encoderInput) override { next_value(); sink.write(input); }\n";
            $recordDeclarations .= "    void write(const $argument& input) const;\n";
            $recordDefinitions .= "void record_sink::write(const $argument& input) const {\n";
            if ($op['nullablePayload'] !== null) {
                $recordDefinitions .= "    if (!input.has_value()) { output.write(nullptr); return; }\n    const auto value = input.value();\n";
            }
            $recordDefinitions .= "    output.write($input);\n}\n";
        }
        $d .= "$signature {\n";
        if ($op['nullablePayload'] !== null) {
            $d .= "    auto native = [&]() {\n        if (!input.has_value()) return detail::json::encode_null();\n        const auto value = input.value();\n        return detail::json::encode($input);\n    }();\n";
        } else {
            $d .= "    auto native = detail::json::$name($input);\n";
        }
        if ($decode) {
            $d .= "    if (!native.error.empty()) return $result{error_t{string{native.error.data(), detail::checked_integer_cast<$count>(native.error.size())}}};\n";
            $d .= "    $family value;\n    detail::ownership_access::get<detail::{$family}_value>(value) = std::move(native.value);\n    return $result{std::move(value)};\n";
        } else {
            $d .= "    return encoded_result(std::move(native));\n";
        }
        $d .= "}\n";
    }
    if (in_array('decode', array_column($module['operations'], 'id'), true)) {
        $recordHeader = moduleOperationHeader($module['header'], 'decode_record');
        $declarations = $definitions = '';
        foreach (JSON_RECORD_READERS as $type => $method) {
            if ($type !== 'string' && !hasType($plan, $type) && !hasValue($plan, $type)) continue;
            $declarations .= "    virtual $type $method() = 0;\n";
            $body = $type === 'string'
                ? "auto value = cursor.$method(); return string{value.data(), detail::checked_integer_cast<$count>(value.size())};"
                : "return cursor.$method();";
            $definitions .= "    $type $method() override { $body }\n";
        }
        $files['public/scpp/decoder.hpp'] = BANNER . template('json/decoder', ['includes' => includeHeader($plan['string']['header']), 'declarations' => $declarations]);
        $files['public/' . $recordHeader] = BANNER . template('json/record_decoder', ['includes' => includeHeader($plan['carriers_header'])]);
        $implementationHeaders .= includeHeader($recordHeader) . includeHeader('structural_reader.hpp');
        $d .= template('json/record_reader_impl', compact('definitions', 'count'));
    }
    if ($recordIncludes !== []) {
        foreach (['structure/record_fields.hpp', 'json/record_output.hpp'] as $path) {
            $files['public/scpp/detail/' . $path] = BANNER . readFile(__DIR__ . '/../../../runtime/src/' . $path);
        }
        $recordHeader = moduleOperationHeader($module['header'], 'encode_record');
        $files['public/scpp/encoder.hpp'] = BANNER . template('json/encoder', [
            'includes' => implode('', $recordIncludes), 'declarations' => $encoderDeclarations]);
        $fieldsHeader = moduleOperationHeader($module['header'], 'encode_fields');
        $files['public/' . $fieldsHeader] = moduleHeaderBody(['scpp/encoder.hpp'], 'scpp::json',
            "result_string encode_fields(const void*, Encoder::visit);\n");
        $implementationHeaders .= includeHeader($fieldsHeader);
        $files['public/' . $recordHeader] = BANNER . template('json/record_encoder', [
            'includes' => implode('', $recordIncludes), 'declarations' => $recordDeclarations]);
        $implementationHeaders .= includeHeader($recordHeader);
        $d = "static result_string encoded_result(detail::json::outcome<std::string>&& native) {\n"
            . "    if (!native.error.empty()) return result_string{error_t{string{native.error.data(), detail::checked_integer_cast<$count>(native.error.size())}}};\n"
            . "    return result_string{detail::own_string(std::move(native.value))};\n}\n"
            . $d . $recordDefinitions
            . "void record_sink::operator()(std::string_view bytes) const { output.append(bytes); }\n"
            . "result_string encode_record_erased(const void* value, void (*visit)(const void*, detail::json::writer&)) {\n"
            . "    return encoded_result(detail::json::encode_record(value, visit));\n}\n"
            . template('json/record_writer_impl', ['methods' => $encoderMethods]);
    }
    foreach ($signatures as $name => $arguments) $h .= scalarInputFences($plan, $name, $arguments);
    $files['public/' . $module['header']] = $h . "}\n";
    $files['src/json.cpp'] = $implementationHeaders . "namespace scpp::json {\n" . $d . "}\n";
    renderJsonHandles($plan, $files);
    renderJsonReader($plan, $files);
}

function jsonNativeArgument(array $operation, string $source): string {
    return match ($operation['transport']) {
        'bytes' => "std::string_view{{$source}.data(), static_cast<std::size_t>({$source}.size())}",
        'boxed' => "detail::ownership_access::get<detail::{$operation['family']}_value>($source)",
        'int64' => "static_cast<std::int64_t>({$source})",
        'float64', 'bool_t' => "$source",
    };
}
