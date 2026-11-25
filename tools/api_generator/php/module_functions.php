<?php
declare(strict_types=1);
namespace Scpp\Api;

const FUNCTION_MODULES = ['filesystem', 'regex'];

/** Resolve the published module signatures once for both native and compiler rendering. */
function prepareModuleFunctions(string $family, mixed $metadata, mixed $publication, array &$plan): void {
    $catalog = familyMetadata($metadata, $publication, $family, $family, $family);
    if ($catalog === null || !property_exists($publication, $family)) return;

    fields($catalog, ['provider', 'namespace', 'functions']);
    fields($publication->{$family}, ['functions']);
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
        fields($function, ['native_name', 'parameters', 'result', 'flavor']);
        $flavor = $function->flavor ?? null;
        requireThat($flavor === null || in_array($flavor, ['php', 'cpp', 'js'], true), 'Invalid function flavor');
        $native_name = textValue(at($function, 'native_name'));
        requireThat(preg_match('/^[A-Za-z_][A-Za-z_0-9]*$/D', $native_name) === 1, 'Invalid module function native name');
        requireThat(!isset($nativeNames[$native_name]), 'Duplicate module function native name');
        $nativeNames[$native_name] = true;

        $parameters = [];
        $reference_mask = 0;
        $const_reference_mask = 0;
        $inputs = listValue(at($function, 'parameters'));
        requireThat(count($inputs) <= 32, 'Too many module function parameters');
        foreach ($inputs as $index => $parameter) {
            fields($parameter, ['type', 'native_passing']);
            $export = moduleFunctionTypeExport($plan, textValue(at($parameter, 'type')));
            $type = $export['identity'];
            $requires[] = $export['provider'];
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
        $requires[] = $export['provider'];
        $native_result = $export['symbol'];
        $functions[$name] = compact('native_name', 'flavor', 'parameters', 'result', 'native_result', 'reference_mask', 'const_reference_mask');
    }
    requireThat($functions !== [], 'Empty module function publication');
    $requires = array_values(array_unique($requires));
    $plan[$family] = compact('provider', 'header', 'module', 'namespace', 'requires', 'functions');
}

/** Resolve the concrete public export and guard against unpublished scalar identities. */
function moduleFunctionTypeExport(array $plan, string $type): array {
    $identity = $plan['source_types']->{$type} ?? $type;
    requireThat(hasType($plan, $identity) || hasValue($plan, $identity)
        || (isset($plan['string']) && $identity === 'string')
        || in_array($identity, array_column($plan['carriers'] ?? [], 'name'), true),
        'Module function requires a published type: ' . $type);
    return typeExport($plan['submodules'], $identity) + ['identity' => $identity];
}

/** Render declarations through the provider graph and a private include for the implementation. */
function renderModuleFunctions(string $family, array $plan, array &$files, array &$groups): void {
    if (!isset($plan[$family])) return;
    $module = $plan[$family];
    $declarations = "namespace {$module['namespace']} {\n";
    foreach ($module['functions'] as $function) {
        $parameters = implode(', ', array_column($function['parameters'], 'native'));
        $declarations .= "[[nodiscard]] {$function['native_result']} {$function['native_name']}($parameters);\n";
    }
    $groups[] = ['provider' => $module['provider'], 'declarations' => $declarations . "}\n", 'system' => []];
    $files["private/{$family}_provider.hpp"] = BANNER . "#pragma once\n" . includeHeader($module['header']);
}
