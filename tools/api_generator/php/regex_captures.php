<?php
declare(strict_types=1);
namespace Scpp\Api;

/** These capture inputs and results cross a precompiled boundary. Reuse the core collection and carrier recipes. */
function prepareRegexCaptureTypes(array $names, object $functions, string $moduleHeader, array &$plan): void {
    $payloads = [];
    foreach ($names as $name) {
        $function = at($functions, $name);
        foreach ($function->parameters as $parameter) {
            if (in_array($parameter->type, ['vector_vector_string', 'vector_hash_string_string'], true)) $payloads[$parameter->type] ??= false;
        }
        $result = textValue(at($function, 'result'));
        if (!str_starts_with($result, 'result_or_false_')) continue;
        $payload = substr($result, strlen('result_or_false_'));
        if (in_array($payload, ['hash_string_int64', 'hash_string_string', 'vector_vector_string', 'vector_hash_string_string'], true)) {
            $payloads[$payload] = true;
        }
    }
    if (!$payloads) return;
    $provider = 'regex.capture_types';
    $header = moduleOperationHeader($moduleHeader, 'capture_types');
    checkHeader($plan, $header);
    $plan['submodules']['nodes'][$provider] = ['module' => 'core', 'header' => $header, 'requires' => []];
    $shape = array_column($plan['vectors'] ?? [], null, 'name')['vector_string'] ?? null;
    $prototype = array_column($plan['carriers'] ?? [], null, 'name')['result_or_false_vector_string'] ?? null;
    requireThat($shape !== null, 'Regex captures require the published string vector');
    $needsCarriers = in_array(true, $payloads, true);
    requireThat(!$needsCarriers || $prototype !== null, 'Regex capture results require the falseable string vector carrier');
    $requires = $needsCarriers ? [$plan['carrier_publication']['provider']] : [];
    foreach ($payloads as $payload => $needsCarrier) {
        $element = str_starts_with($payload, 'vector_') ? substr($payload, 7) : $payload;
        $requires[] = typeExport($plan['submodules'], $element)['provider'];
        if ($element !== $payload) {
            $row = $shape;
            $row['name'] = $payload;
            $row['element'] = $element;
            $row['source_type'] = $element === 'vector_string' ? 'vector<vector<string>>' : 'vector<hash<string,string>>';
            $row['native_value'] = '';
            $row['operations'] = [];
            $row['initialization'] = ['bulk' => null];
            $row['methods'] = [];
            foreach ($shape['methods'] as $method) {
                if (in_array('const char*', $method['arguments'], true)) continue;
                $method['arguments'] = array_map(static fn($t) => str_replace('string', $element, $t), $method['arguments']);
                if ($method['result'] === 'string') $method['result'] = $element;
                $row['methods'][] = $method;
            }
            $plan['regex_vectors'][] = $row;
            $plan['submodules']['type_exports'][$payload] = ['symbol' => 'scpp::' . $payload, 'provider' => $provider];
        }
        if (!$needsCarrier) continue;
        $name = 'result_or_false_' . $payload;
        $plan['regex_carriers'][] = compact('name', 'payload', 'header') + $prototype;
        $plan['submodules']['type_exports'][$name] = ['symbol' => 'scpp::' . $name, 'provider' => $provider];
    }
    $plan['regex_capture_publication'] = compact('provider', 'requires', 'header');
}

function renderRegexCaptureTypes(array $plan, array &$files, array &$groups): void {
    if (!isset($plan['regex_capture_publication'])) return;
    $pub = $plan['regex_capture_publication'];
    $vectors = $plan['regex_vectors'] ?? [];
    $decl = $body = '';
    if ($vectors) {
        $copy = $plan;
        $copy['vectors'] = $vectors;
        $copy['string_functions'] = [];
        [$decl, $body] = renderVectors($copy, 'regex_vector_access');
    }
    $decl .= "namespace scpp {\n";
    foreach ($plan['regex_carriers'] ?? [] as $carrier) {
        [$h, $d] = renderCarrier($plan, $carrier);
        $decl .= $h;
        $body .= $d;
    }
    $groups[] = ['provider' => $pub['provider'], 'system' => [], 'declarations' => $decl . "}\n"];
    $files['src/runtime.cpp'] .= includeHeader($pub['header']) . "namespace scpp {\n$body}\n";
}
