<?php
declare(strict_types=1);
namespace Scpp\Api;

/** Resolve the published filesystem signatures once for both native and compiler rendering. */
function prepareFilesystem(mixed $metadata, mixed $publication, array &$plan): void {
    $catalog = familyMetadata($metadata, $publication, 'filesystem', 'filesystem', 'Filesystem');
    if ($catalog === null || !property_exists($publication, 'filesystem')) return;

    fields($catalog, ['provider', 'namespace', 'functions']);
    fields($publication->filesystem, ['functions']);
    $provider = textValue(at($catalog, 'provider'));
    $header = providerHeader($plan['submodules'], $provider);
    $module = $plan['submodules']['nodes'][$provider]['module'];
    requireThat($module === 'runtime-filesystem', 'Filesystem declaration provider must belong to runtime-filesystem');
    $namespace = textValue(at($catalog, 'namespace'));
    requireThat(preg_match('/^scpp(?:::[A-Za-z_][A-Za-z_0-9]*)+$/D', $namespace) === 1, 'Invalid filesystem namespace');

    $functions = [];
    $requires = [];
    $nativeNames = [];
    foreach (names(at($publication->filesystem, 'functions'), 'Duplicate filesystem function') as $name) {
        requireThat(preg_match('/^fs_[a-z][a-z_0-9]*$/D', $name) === 1, 'Invalid filesystem function name');
        $function = at(at($catalog, 'functions'), $name);
        fields($function, ['native_name', 'parameters', 'result']);
        $native_name = textValue(at($function, 'native_name'));
        requireThat(preg_match('/^[A-Za-z_][A-Za-z_0-9]*$/D', $native_name) === 1, 'Invalid filesystem native name');
        requireThat(!isset($nativeNames[$native_name]), 'Duplicate filesystem native name');
        $nativeNames[$native_name] = true;

        $parameters = [];
        $const_reference_mask = 0;
        $inputs = listValue(at($function, 'parameters'));
        requireThat(count($inputs) <= 32, 'Too many filesystem parameters');
        foreach ($inputs as $index => $parameter) {
            fields($parameter, ['type', 'native_passing']);
            $export = filesystemTypeExport($plan, textValue(at($parameter, 'type')));
            $type = $export['identity'];
            $requires[] = $export['provider'];
            $passing = textValue(at($parameter, 'native_passing'));
            $native = match ($passing) {
                'value' => $export['symbol'],
                'const_reference' => 'const ' . $export['symbol'] . '&',
                default => throw new \RuntimeException('Unsupported filesystem native parameter passing'),
            };
            if ($passing === 'const_reference') $const_reference_mask |= 1 << $index;
            $parameters[] = compact('type', 'native');
        }
        $export = filesystemTypeExport($plan, textValue(at($function, 'result')));
        $result = $export['identity'];
        $requires[] = $export['provider'];
        $native_result = $export['symbol'];
        $functions[$name] = compact('native_name', 'parameters', 'result', 'native_result', 'const_reference_mask');
    }
    requireThat($functions !== [], 'Empty filesystem publication');
    $requires = array_values(array_unique($requires));
    $plan['filesystem'] = compact('provider', 'header', 'module', 'namespace', 'requires', 'functions');
}

/** Resolve the concrete public export and guard against unpublished scalar identities. */
function filesystemTypeExport(array $plan, string $type): array {
    $identity = $plan['source_types']->{$type} ?? $type;
    requireThat(hasType($plan, $identity) || hasValue($plan, $identity)
        || (isset($plan['string']) && $identity === 'string')
        || in_array($identity, array_column($plan['carriers'] ?? [], 'name'), true),
        'Filesystem requires a published type: ' . $type);
    return typeExport($plan['submodules'], $identity) + ['identity' => $identity];
}

/** Render declarations through the provider graph and a private include for the implementation. */
function renderFilesystem(array $plan, array &$files, array &$groups): void {
    if (!isset($plan['filesystem'])) return;
    $module = $plan['filesystem'];
    $declarations = "namespace {$module['namespace']} {\n";
    foreach ($module['functions'] as $function) {
        $parameters = implode(', ', array_column($function['parameters'], 'native'));
        $declarations .= "[[nodiscard]] {$function['native_result']} {$function['native_name']}($parameters);\n";
    }
    $groups[] = ['provider' => $module['provider'], 'declarations' => $declarations . "}\n", 'system' => []];
    $files['private/filesystem_provider.hpp'] = BANNER . "#pragma once\n" . includeHeader($module['header']);
}
