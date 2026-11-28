<?php
declare(strict_types=1);
namespace Scpp\Api;

/** Project the shared text capability onto the exact scalar and builder providers in this publication. */
function prepareStringable(mixed $metadata, array &$plan): void {
    if (!isset($metadata->capabilities)) return;
    fields($metadata->capabilities, ['to_string']);
    if (isset($metadata->capabilities->to_string, $plan['string'])) {
        $contract = $metadata->capabilities->to_string;
        fields($contract, ['interface', 'method', 'result', 'scalars', 'integers', 'identity', 'builders']);
        literals($contract, ['interface' => 'Stringable', 'method' => 'to_string', 'result' => 'string',
            'scalars' => 'explicit_formatters', 'integers' => 'decimal', 'identity' => 'string']);
        $members = [];
        foreach (names($contract->builders) as $builder) {
            requireThat(isset($metadata->text_builders->$builder) && $metadata->text_builders->$builder->{$contract->method} === ['read', 'string'],
                "Invalid to_string builder: $builder");
            if (isset($plan['text_builders'][$builder])) $members[$builder] = $plan['text_builders'][$builder]['header'];
        }
        $integers = [];
        foreach ($plan['types'] as $type) {
            $name = $type['name'];
            if (!isset($plan['explicit_formatters'][$name])) $integers[$name] = providerHeader($plan['submodules'], typeExport($plan['submodules'], $name)['provider']);
        }
        $plan['to_string'] = ['interface' => $contract->interface, 'method' => $contract->method,
            'result' => $contract->result, 'builders' => $members, 'integers' => $integers];
    }
}

/** Separate concrete declarations keep formatter implementation dependencies private. */
function renderStringable(array $plan, array &$files): void {
    if (!isset($plan['to_string'])) return;
    $declarations = "template<class T> string to_string(const T&) = delete;\ninline string to_string(const string& value) { return value; }\n";
    $definitions = '';
    foreach ($plan['explicit_formatters'] ?? [] as $source => $formatter) {
        $signature = "string to_string($source value)";
        $declarations .= "$signature;\n";
        $definitions .= "$signature { return {$formatter['symbol']}(value); }\n";
    }
    $header = 'scpp/to_string.hpp';
    $files['public/' . $header] = moduleHeaderBody([$plan['string']['header']], 'scpp', $declarations);
    $files['src/runtime.cpp'] .= includeHeader($header) . "namespace scpp {\n$definitions}\n";
    $integers = $plan['to_string']['integers'];
    if ($integers === []) return;
    $declarations = $definitions = '';
    foreach ($integers as $name => $_) {
        $signature = "string to_string($name value)";
        $declarations .= "$signature;\n";
        $definitions .= "$signature { char buffer[128]; auto n = detail::format_decimal_into(value, buffer); return string{buffer, static_cast<int64>(n)}; }\n";
    }
    $files['public/scpp/numeric/to_string.hpp'] = moduleHeaderBody([$header, ...array_values($integers)], 'scpp', $declarations);
    $files['src/runtime.cpp'] .= includeHeader('scpp/numeric/to_string.hpp') . "namespace scpp {\n$definitions}\n";
}
