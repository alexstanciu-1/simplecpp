<?php
declare(strict_types=1);
namespace Scpp\Api;
function renderBoxed(array $plan, array &$files, array &$groups): void {
    if (!isset($plan['boxed'])) return;
    $box = $plan['boxed'];
    $h = BANNER . "#pragma once\n" . scalarProviderIncludes($plan) . includeHeader($plan['string']['header']);
    $h .= "namespace scpp {\nnamespace detail { struct ownership_access; }\nenum class boxed_kind { null, boolean, integer, floating, string, container };\n";
    foreach ($box['families'] as $family) $h .= "class {$family['name']};\n";
    $split = isset($plan['boxed_dispatch']);
    if ($split) {
        $files['public/scpp/boxed_kind.hpp'] = BANNER . "#pragma once\nnamespace scpp { enum class boxed_kind { null, boolean, integer, floating, string, container }; }\n";
        $files['public/scpp/boxed_key.hpp'] = BANNER . "#pragma once\n" . includeHeader($plan['string']['header'])
            . scalarProviderIncludes($plan) . "namespace scpp {\nstruct boxed_text_key { int64 index; string text; };\n"
            . "int64 boxed_key(int64 key);\nboxed_text_key boxed_key(string key);\n}\n";
        $h = BANNER . "#pragma once\n";
    }
    $d = BANNER . includeHeader($box['header']) . includeHeader('boxed_conversion.hpp') . includeHeader('ownership_semantics.hpp') . includeHeader('scalar_semantics.hpp') . "namespace scpp {\n";
    if ($split) {
        $d = str_replace("namespace scpp {\n", includeHeader('source_key.hpp') . includeHeader('packed_key.hpp') . implode('', array_map(__NAMESPACE__ . '\\includeHeader', $plan['boxed_dispatch']['headers'])) . "namespace scpp {\n", $d);
        $d .= "int64 boxed_key(int64 key) { return detail::packed_key::integer(key); }\n"
            . "boxed_text_key boxed_key(string key) {\n"
            . "    const auto index = detail::source_key_index({key.data(), static_cast<std::size_t>(key.size())});\n"
            . "    if (index >= 0) return {boxed_key(static_cast<int64>(index)), {}};\n"
            . "    return {-1, std::move(key)};\n}\n";
    }
    foreach ($box['families'] as $family) {
        $n = $family['name']; $native = $family['native'];
        $get = fn(string $value): string => "detail::ownership_access::get<$native>($value)";
        $peer = $n === 'mixed' ? 'dynamic' : 'mixed';
        $vars = $family + ['count' => $plan['string']['count_representation'], 'n' => $n, 'conversion' => $box['scalar_conversions'] ? "    static $n from_{$peer}_scalar(const $peer& value);\n" : '',
            'self' => $get('*this'), 'other' => $get('other'), 'result' => $get('result'), 'value' => $get('value')];
        $fences = '';
        foreach (['from_boolean' => 'bool_t', 'from_integer' => 'int64', 'from_floating' => 'float64'] as $method => $input) {
            $fences .= preg_replace('/^void /m', 'static void ', scalarInputFences($plan, $method, [[$input]]));
        }
        foreach (['read', 'has', 'remove'] as $method) $fences .= scalarInputFences($plan, $method, [['const int64&'], ['const string&']], false, $method === 'remove' ? '' : ' const');
        $fences .= scalarInputFences($plan, 'set', [['int64', $n], ['const string&', $n]]);
        $sourceMembers = $split ? "    $n source_read(int64 key) const { return read(key); }\n"
            . "    $n source_read(const boxed_text_key& key) const;\n"
            . "    void source_set(int64 key, const $n& value) { set(key, value); }\n"
            . "    void source_set(const boxed_text_key& key, const $n& value);\n" : '';
        $decl = template('boxed/boxed_declaration', $vars + ['fences' => $sourceMembers . $fences]);
        if ($split) {
            $provider = typeExport($plan['submodules'], $n)['provider'];
            $h .= includeHeader(providerHeader($plan['submodules'], $provider));
            [$api, $impl] = renderBoxedDispatch($plan['boxed_dispatch'], $n);
            $forward = $box['scalar_conversions'] ? "class $peer; " : '';
            $groups[] = ['provider' => $provider, 'system' => [], 'declarations' =>
                includeHeader('scpp/boxed_key.hpp') . includeHeader('scpp/boxed_kind.hpp') . "namespace scpp { namespace detail { struct ownership_access; } $forward\n" . $decl . $api . "}\n"];
            $d .= $impl . "$n $n::source_read(const boxed_text_key& key) const { return key.index < 0 ? read(key.text) : read(key.index); }\n"
                . "void $n::source_set(const boxed_text_key& key, const $n& value) { if (key.index < 0) set(key.text, value); else set(key.index, value); }\n";
        } else {
            $h .= $decl;
        }
        $d .= template('boxed/boxed_definition', $vars);
        foreach (['int64' => 'key', 'string' => 'std::string_view{key.data(), static_cast<std::size_t>(key.size())}'] as $key => $expression) {
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
    $files['public/'.$box['header']] = $h . ($split ? '' : "}\n");
    $files['src/boxed.cpp'] = $d . "}\n";
}
