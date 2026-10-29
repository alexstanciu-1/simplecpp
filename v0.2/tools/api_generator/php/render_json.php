<?php
declare(strict_types=1);
namespace Scpp\Api;

function renderJson(array $plan, array &$files): void {
    if (!isset($plan['json'])) return;
    $module = $plan['json'];
    $boxedHeader = array_filter(array_column($module['operations'], 'family')) !== [] ? includeHeader($plan['boxed']['header']) : '';
    $h = BANNER . "#pragma once\n" . includeHeader($plan['carriers_header']) . $boxedHeader . includeHeader($plan['string']['header']) . "namespace scpp::json {\n";
    $d = BANNER . includeHeader($module['header']) . includeHeader('json_native.hpp') . includeHeader('ownership_semantics.hpp') . includeHeader('scalar_semantics.hpp') . "namespace scpp::json {\n";
    foreach ($module['operations'] as $op) {
        ['name' => $name, 'family' => $family, 'result' => $result, 'argument' => $argument] = $op;
        $signature = "$result $name(const $argument& input)";
        $h .= "$signature;\n";
        $decode = $name !== 'encode';
        $input = match ($op['transport']) {
            'bytes' => 'std::string_view{input.data(), static_cast<std::size_t>(input.size().native_value())}',
            'boxed' => "detail::ownership_access::get<detail::{$family}_value>(input)",
            'int64' => 'static_cast<std::int64_t>(input.native_value())',
            'float64', 'bool_t' => 'input.native_value()',
        };
        $d .= "$signature {\n    auto native = detail::json::$name($input);\n";
        $d .= "    if (!native.error.empty()) return $result{error_t{string{native.error.data(), int64{detail::checked_integer_cast<long long>(native.error.size())}}}};\n";
        if ($decode) {
            $d .= "    $family value;\n    detail::ownership_access::get<detail::{$family}_value>(value) = std::move(native.value);\n    return $result{std::move(value)};\n";
        } else {
            $d .= "    return $result{string{native.value.data(), int64{detail::checked_integer_cast<long long>(native.value.size())}}};\n";
        }
        $d .= "}\n";
    }
    $files['public/' . $module['header']] = $h . "}\n";
    $files['src/json.cpp'] = $d . "}\n";
}
