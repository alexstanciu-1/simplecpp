<?php
declare(strict_types=1);
namespace Scpp\Api;

const FUNCTION_MODULES = ['filesystem', 'regex'];

/** Resolve the published module signatures once for both native and compiler rendering. */
function prepareModuleFunctions(string $family, mixed $metadata, mixed $publication, array &$plan): void {
    $catalog = familyMetadata($metadata, $publication, $family, $family, $family);
    if ($catalog === null || !property_exists($publication, $family)) return;

    fields($catalog, ['provider', 'namespace', 'functions', 'surface', 'php_exposures', 'php_constants']);
    fields($publication->{$family}, ['functions', 'php_exposures']);
    $surface = $catalog->surface ?? null;
    requireThat($surface === null || $surface === 'general', 'Unknown module function surface');
    $provider = textValue(at($catalog, 'provider'));
    $header = providerHeader($plan['submodules'], $provider);
    $module = $plan['submodules']['nodes'][$provider]['module'];
    requireThat($module === 'runtime-' . $family, "Invalid module for $family declaration provider");
    $namespace = textValue(at($catalog, 'namespace'));
    requireThat(preg_match('/^scpp(?:::[A-Za-z_][A-Za-z_0-9]*)+$/D', $namespace) === 1, 'Invalid module namespace');

    $functions = [];
    $requires = [];
    $nativeNames = [];
    foreach (names(at($publication->{$family}, 'functions'), 'Duplicate module function') as $name) {
        $pattern = $family === 'filesystem' ? '/^fs_[a-z][a-z_0-9]*$/D' : '/^[a-z][a-z_0-9]*$/D';
        requireThat(preg_match($pattern, $name) === 1, 'Invalid module function name');
        $function = at(at($catalog, 'functions'), $name);
        fields($function, ['native_name', 'parameters', 'result', 'flavor', 'required_parameters']);
        $flavor = $function->flavor ?? null;
        requireThat($flavor === null || in_array($flavor, ['php', 'cpp', 'js'], true), 'Invalid function flavor');
        $native_name = textValue(at($function, 'native_name'));
        requireThat(preg_match('/^[A-Za-z_][A-Za-z_0-9]*$/D', $native_name) === 1, 'Invalid module function native name');
        requireThat(!isset($nativeNames[$native_name]), 'Duplicate module function native name');
        $nativeNames[$native_name] = true;

        $parameters = [];
        $signatureProviders = [];
        $reference_mask = 0;
        $const_reference_mask = 0;
        $inputs = listValue(at($function, 'parameters'));
        requireThat(count($inputs) <= 32, 'Too many module function parameters');
        foreach ($inputs as $index => $parameter) {
            fields($parameter, ['type', 'native_passing']);
            $export = moduleFunctionTypeExport($plan, textValue(at($parameter, 'type')));
            $type = $export['identity'];
            $signatureProviders[] = $export['provider'];
            $passing = textValue(at($parameter, 'native_passing'));
            $native = match ($passing) {
                'value' => $export['symbol'],
                'reference' => $export['symbol'] . '&',
                'const_reference' => 'const ' . $export['symbol'] . '&',
                default => throw new \RuntimeException('Unsupported module function native parameter passing'),
            };
            if ($passing === 'reference') $reference_mask |= 1 << $index;
            if ($passing === 'const_reference') $const_reference_mask |= 1 << $index;
            $parameters[] = compact('type', 'native');
        }
        $export = moduleFunctionTypeExport($plan, textValue(at($function, 'result')));
        $result = $export['identity'];
        $signatureProviders[] = $export['provider'];
        $native_result = $export['symbol'];
        $requires = array_merge($requires, $signatureProviders);
        $functions[$name] = compact('native_name', 'flavor', 'parameters', 'result', 'native_result', 'reference_mask', 'const_reference_mask');
        $required = $function->required_parameters ?? count($inputs);
        requireThat(is_int($required) && $required >= 0 && $required <= count($inputs), 'Invalid required module parameter count');
        $functions[$name]['required_parameters'] = $required;
        if ($surface === 'general') {
            if ($flavor === null) $functions[$name]['surface'] = 'general';
            $functions[$name]['header'] = moduleOperationHeader($header, $name);
            $functions[$name]['includes'] = array_map(fn($id) => providerHeader($plan['submodules'], $id),
                array_unique($signatureProviders));
            checkHeader($plan, $functions[$name]['header']);
        }
    }
    requireThat($functions !== [], 'Empty module function publication');
    $requires = array_values(array_unique($requires));
    $plan[$family] = compact('provider', 'header', 'module', 'namespace', 'requires', 'functions');
    foreach (object($catalog->php_constants ?? (object)[]) as $constant => $value) {
        requireThat(preg_match('/^[A-Z][A-Z_0-9]*$/D', $constant) === 1 && is_int($value), 'Invalid module integer constant');
        $plan[$family]['php_constants'][$constant] = $value;
    }
    foreach (names($publication->{$family}->php_exposures ?? [], 'Duplicate PHP exposure') as $name) {
        $alias = at(at($catalog, 'php_exposures'), $name);
        fields($alias, ['target', 'native_name', 'false_result']);
        requireThat(preg_match('/^[a-z][a-z_0-9]*$/D', $name) === 1, 'Invalid PHP function name');
        requireThat($surface === 'general' && !isset($functions[$name]), 'Expected a distinct PHP exposure of a general function');
        $target = textValue(at($alias, 'target'));
        requireThat(isset($functions[$target]), 'PHP exposure requires its published target: ' . $target);
        $binding = $functions[$target];
        $binding['flavor'] = 'php';
        unset($binding['surface']);
        $symbol = $namespace . '::' . $binding['native_name'];
        if (isset($alias->false_result) || isset($alias->native_name)) {
            $binding['header'] = "scpp/php/$name.hpp";
            checkHeader($plan, $binding['header']);
            if (isset($alias->false_result)) {
                $result = moduleFunctionTypeExport($plan, textValue($alias->false_result));
                requireThat(!isset($alias->native_name) && str_starts_with($binding['result'], 'result_')
                    && $result['identity'] === 'result_or_false_' . substr($binding['result'], 7),
                    'Expected matching result and falseable payloads');
                $binding['result'] = $result['identity'];
                $binding['native_result'] = $result['symbol'];
                $binding['includes'] = [$functions[$target]['header'], providerHeader($plan['submodules'], $result['provider'])];
                $binding['adapt_target'] = $symbol;
            } else {
                requireThat($alias->native_name === $name, 'Expected matching PHP native function name');
            }
            $symbol = "scpp::php::$name";
        }
        $binding['symbol'] = $symbol;
        $plan[$family]['php_exposures'][$name] = $binding;
    }
}

/** Resolve the concrete public export and guard against unpublished scalar identities. */
function moduleFunctionTypeExport(array $plan, string $type): array {
    $identity = $plan['source_types']->{$type} ?? $type;
    requireThat(hasType($plan, $identity) || hasValue($plan, $identity)
        || (isset($plan['string']) && $identity === 'string')
        || (isset($plan['boxed_dispatch']) && in_array($identity, array_column($plan['boxed']['families'], 'name'), true))
        || in_array($identity, array_column($plan['vectors'] ?? [], 'name'), true)
        || in_array($identity, array_column($plan['carriers'] ?? [], 'name'), true),
        'Module function requires a published type: ' . $type);
    return typeExport($plan['submodules'], $identity) + ['identity' => $identity];
}

/** Narrow declarations follow their module provider when a build relocates it. */
function moduleOperationHeader(string $header, string $name): string {
    return substr($header, 0, -4) . '/' . $name . '.hpp';
}

function moduleHeaderBody(array $includes, string $namespace, string $declaration): string {
    $body = BANNER . "#pragma once\n";
    foreach (array_unique($includes) as $include) $body .= includeHeader($include);
    return $body . "namespace $namespace {\n$declaration}\n";
}

/** Render declarations through the provider graph and a private include for the implementation. */
function renderModuleFunctions(string $family, array $plan, array &$files, array &$groups): void {
    if (!isset($plan[$family])) return;
    $module = $plan[$family];
    $declarations = "namespace {$module['namespace']} {\n";
    foreach ($module['functions'] as $function) {
        $types = array_column($function['parameters'], 'native');
        $declaration = '';
        for ($arity = $function['required_parameters']; $arity <= count($types); ++$arity) {
            $parameters = implode(', ', array_slice($types, 0, $arity));
            $declaration .= "[[nodiscard]] {$function['native_result']} {$function['native_name']}($parameters);\n";
        }
        $declarations .= $declaration;
        if (isset($function['header'])) {
            $files['public/' . $function['header']] = moduleHeaderBody($function['includes'], $module['namespace'], $declaration);
        }
    }
    foreach ($module['php_exposures'] ?? [] as $name => $binding) {
        if (!str_starts_with($binding['symbol'], 'scpp::php::')) continue;
        $parameters = [];
        $arguments = [];
        foreach ($binding['parameters'] as $index => $parameter) {
            $arguments[] = $argument = "arg$index";
            $parameters[] = $parameter['native'] . ' ' . $argument;
        }
        $result = $binding['native_result'];
        $signature = "$result $name(" . implode(', ', $parameters) . ')';
        $code = "[[nodiscard]] $signature;\n";
        if (isset($binding['adapt_target'])) {
            $call = '::' . $binding['adapt_target'] . '(' . implode(', ', $arguments) . ')';
            $code = "[[nodiscard]] inline $signature {\n    auto result = $call;\n"
                . "    if (!result.has_value()) return $result::from_false();\n    return $result{result.value()};\n}\n";
        }
        $files['public/' . $binding['header']] = moduleHeaderBody($binding['includes'], 'scpp::php', $code);
    }
    $groups[] = ['provider' => $module['provider'], 'declarations' => $declarations . "}\n", 'system' => []];
    $bridge = BANNER . "#pragma once\n" . includeHeader($module['header']);
    if ($family === 'filesystem') {
        // The archive compiles all implementations even for a reduced source publication.
        $carrier = typeExport($plan['submodules'], 'result_vector_string');
        $bridge .= includeHeader(providerHeader($plan['submodules'], $carrier['provider']));
    }
    $files["private/{$family}_provider.hpp"] = $bridge;
}
