<?php
declare(strict_types=1);
namespace Scpp\Api;

function textHelperHeader(string $name): string {
    requireThat(preg_match('/^[a-z][a-z_0-9]*$/D', $name) === 1, 'Invalid text helper name');
    return "scpp/str/$name.hpp";
}

function prepareTextHelpers(mixed $metadata, mixed $publication, array &$plan): void {
    foreach (names($publication->text_helpers ?? [], 'Duplicate text helper') as $name) {
        $header = textHelperHeader($name);
        $signature = listValue(at(at($metadata, 'text_helpers'), $name));
        $result = textValue(array_shift($signature));
        $includes = [];
        $parameters = [];
        $mask = 0;
        foreach ([$result, ...$signature] as $type) {
            $export = moduleFunctionTypeExport($plan, $type);
            $includes[] = providerHeader($plan['submodules'], $export['provider']);
        }
        foreach ($signature as $i => $type) {
            if ($type === 'string') $mask |= 1 << $i;
            $parameters[] = ($type === 'string' ? 'const string&' : $type) . " arg$i";
        }
        $plan['text_helpers'][$name] = ['parameters' => $signature, 'result' => $result, 'mask' => $mask,
            'string_header' => $plan['string']['header'], 'native_parameters' => $parameters, 'header' => $header, 'includes' => array_unique($includes)];
    }
}

function compilerTextBinding(string $name, array $binding, array $types, ?string $resultModule): string {
    $args = array_map(static fn($t) => "type_{$types[$t]}::instance()", $binding['parameters']);
    $symbol = "::scpp::str::$name";
    $extra = $name === 'string_byte_find' ? ', required_parameters: 2' : '';
    if ($resultModule !== null) $extra .= ', required_module: ' . var_export($resultModule, true);
    $header = $binding['header'];
    if ($name === 'string_byte_len') {
        $symbol = 'size';
        $header = $binding['string_header'];
        $extra = ', receiver_method: true';
    }
    return "new runtime_function('$name', [" . implode(', ', $args) . "], type_{$types[$binding['result']]}::instance(), '$symbol', '$header', const_reference_mask: {$binding['mask']}$extra)";
}

function renderTextHelpers(array $plan, array &$files): void {
    foreach ($plan['text_helpers'] ?? [] as $name => $binding) {
        if (in_array($name, ['hash_string', 'stable_hash_string_u64'], true)) $files['src/runtime.cpp'] .= includeHeader('stable_string_hash.hpp');
        if (str_starts_with($name, 'string_utf8_')) $files['src/runtime.cpp'] .= includeHeader('utf8_codepoints.hpp');
        $signature = $binding['result'] . " $name(" . implode(', ', $binding['native_parameters']) . ')';
        $declaration = $name === 'string_byte_find' ? substr($signature, 0, -1) . ' = 0)' : $signature;
        $files['public/' . $binding['header']] = moduleHeaderBody($binding['includes'], 'scpp::str', "$declaration;\n");
        $files['src/runtime.cpp'] .= includeHeader($binding['header']) . "namespace scpp::str {\n$signature {\n"
            . template("strings/$name") . "}\n}\n";
    }
}

/** PHP implode accepts the values of every published string-valued hash in traversal order. */
function stringJoinHashes(array $runtime): array {
    if (!isset($runtime['string_functions']['implode']['adapters']['php'])) return [];
    return array_filter($runtime['hashes']['specializations'] ?? [], static fn($h) => $h['value'] === 'string');
}

function renderHashJoin(array $plan, array &$files): void {
    $hashes = stringJoinHashes($plan);
    if ($hashes === []) return;
    $header = 'scpp/php/implode_hash.hpp';
    $declarations = '';
    $definitions = '';
    foreach ($hashes as $hash) {
        $signature = "string implode(const string& separator, const {$hash['name']}& parts)";
        $declarations .= "$signature;\n";
        $definitions .= "$signature {\n" . template('strings/hash_join') . "}\n";
    }
    $files['public/' . $header] = moduleHeaderBody([$plan['hashes']['header']], 'scpp::php', $declarations);
    $files['src/runtime.cpp'] .= includeHeader($header) . "namespace scpp::php {\n$definitions}\n";
}
