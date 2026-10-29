<?php
declare(strict_types=1);
namespace Scpp\Api;
function renderBoxed(array $plan, array &$files): void {
    if (!isset($plan['boxed'])) return;
    $box = $plan['boxed'];
    $h = BANNER . "#pragma once\n" . includeHeader($plan['headers']['types']) . includeHeader($plan['headers']['values']) . includeHeader($plan['string']['header']);
    $h .= "namespace scpp {\nnamespace detail { struct ownership_access; }\nenum class boxed_kind { null, boolean, integer, floating, string, container };\n";
    foreach ($box['families'] as $family) $h .= "class {$family['name']};\n";
    $d = BANNER . includeHeader($box['header']) . includeHeader('boxed_conversion.hpp') . includeHeader('ownership_semantics.hpp') . includeHeader('scalar_semantics.hpp') . "namespace scpp {\n";
    foreach ($box['families'] as $family) {
        $n = $family['name']; $native = $family['native'];
        $get = fn(string $value): string => "detail::ownership_access::get<$native>($value)";
        $peer = $n === 'mixed' ? 'dynamic' : 'mixed';
        $vars = $family + ['n' => $n, 'conversion' => $box['scalar_conversions'] ? "    static $n from_{$peer}_scalar(const $peer& value);\n" : '',
            'self' => $get('*this'), 'other' => $get('other'), 'result' => $get('result'), 'value' => $get('value')];
        $h .= template('boxed_declaration', $vars);
        $d .= template('boxed_definition', $vars);
        foreach (['int64' => 'key.native_value()', 'string' => 'std::string_view{key.data(), static_cast<std::size_t>(key.size().native_value())}'] as $key => $expression) {
            $self = $get('*this'); $result = $get('result'); $value = $get('value');
            $d .= "$n $n::read(const $key& key) const { $n result; $result = $self.read($expression); return result; }\n";
            $d .= "bool_t $n::has(const $key& key) const { return bool_t{{$self}.contains($expression)}; }\n";
            $parameter = $key === 'int64' ? 'int64' : 'const string&';
            $d .= "void $n::set($parameter key, $n value) { $self.set($expression, std::move($value)); }\n";
            $d .= "bool_t $n::remove(const $key& key) { return bool_t{{$self}.remove($expression)}; }\n";
        }
        if ($box['scalar_conversions']) {
            $d .= "$n $n::from_{$peer}_scalar(const $peer& value) { $n result; {$vars['result']} = detail::{$n}_from_{$peer}_scalar(detail::ownership_access::get<detail::{$peer}_value>(value)); return result; }\n";
        }
    }
    $files['public/'.$box['header']] = $h . "}\n";
    $files['src/boxed.cpp'] = $d . "}\n";
}
