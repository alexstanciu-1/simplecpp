<?php
declare(strict_types=1);
namespace Scpp\Api;

/**
 * Contracts of the implemented declaration groups, adjacent to their assembly owner.
 * These describe template requirements and exported binding roles, not C++ ABI signatures.
 * Provider placement remains metadata-owned; existing scalar slots keep their renderer owner.
 */
function declarationGroups(mixed $metadata): array {
    $contracts = [
        'string' => ['requires' => ['bool_t'], 'types' => ['string'], 'bindings' => []],
        'output' => ['requires' => [], 'types' => [], 'bindings' => [
            ['operand' => 'int64', 'strategy' => 'integer'], ['operand' => 'string', 'strategy' => 'bytes']]],
    ];
    $groups = [];
    foreach ($contracts as $name => $contract) {
        // Scalar-only preparation may omit the string family entirely.
        if ($name === 'string' && !isset($metadata->string)) continue;
        $contract['provider'] = textValue(at(at($metadata, $name), 'provider'));
        $groups[$name] = $contract;
    }
    return $groups;
}

/** Resolve canonical runtime identities and existing public spellings, without inspecting representations.
 * The legacy renderer adapter checks placement; it does not choose construction or extraction.
 */
function typeExports(mixed $metadata, array $nodes, array $groups, array $existing): array {
    $owners = [];
    foreach (object(at($metadata, 'types')) as $identity => $_) $owners[$identity] = $existing['types'] ?? null;
    foreach (object(at($metadata, 'values')) as $identity => $_) $owners[$identity] = $existing['values'] ?? null;
    $owners[textValue(at($metadata->floating, 'type'))] = $existing['values'] ?? null;
    $typeGroups = [];
    foreach ($groups as $group => $contract) {
        foreach ($contract['types'] as $identity) {
            requireThat(!array_key_exists($identity, $owners), 'Ambiguous type declaration owner');
            $owners[$identity] = $contract['provider'];
            $typeGroups[$identity] = $group;
        }
    }
    $aliases = object(at($metadata, 'integer_aliases'));
    $exports = [];
    foreach (listValue(at($metadata, 'type_exports')) as $row) {
        fields($row, ['identity', 'name', 'provider', 'declaration_group']);
        $identity = textValue(at($row, 'identity'));
        requireThat(array_key_exists($identity, $owners), 'Unknown type export: ' . $identity);
        requireThat(!isset($exports[$identity]), 'Duplicate type export: ' . $identity);
        $name = textValue(at($row, 'name'));
        requireThat(preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $name) === 1, 'Invalid public type name');
        requireThat(($aliases[$name] ?? $name) === $identity, 'Public type name is not declared for export: ' . $identity);
        if (isset($row->declaration_group)) {
            $group = textValue($row->declaration_group);
            requireThat(!isset($row->provider) && ($typeGroups[$identity] ?? null) === $group, 'Incompatible type export declaration group');
            $provider = $groups[$group]['provider'];
        } else {
            requireThat(!isset($typeGroups[$identity]), 'Type export requires its declaration group');
            $provider = textValue(at($row, 'provider'));
        }
        requireThat(isset($nodes[$provider]), 'Unknown type export provider: ' . $provider);
        requireThat($owners[$identity] === $provider, 'Type export disagrees with declaration placement: ' . $identity);
        $exports[$identity] = ['symbol' => 'scpp::' . $name, 'provider' => $provider];
    }
    ksort($exports, SORT_STRING);
    return $exports;
}

/** A semantic export requirement resolves to the same provider used by compiler name queries. */
function typeExport(array $plan, string $identity): array {
    requireThat(isset($plan['type_exports'][$identity]), 'Missing type export: ' . $identity);
    return $plan['type_exports'][$identity];
}

/** Resolve public providers once; metadata owns paths; legacy publication fields must agree. */
function submodulePlan(mixed $metadata, ?array $existingHeaders = null): array {
    $nodes = [];
    $paths = [];
    $existing = [];
    foreach (object(at($metadata, 'submodules')) as $id => $row) {
        requireThat(preg_match('/^[a-z][a-z0-9_.]*$/D', $id) === 1, 'Invalid submodule identity');
        fields($row, ['module', 'header', 'requires', 'requires_exports', 'existing']);
        // Only core providers are integrated in this slice; header reachability grants no language admission.
        requireThat(textValue(at($row, 'module')) === 'core', 'Unavailable submodule module');
        $header = textValue(at($row, 'header'));
        if (isset($row->existing)) {
            $slot = textValue($row->existing);
            requireThat(in_array($slot, ['types', 'values'], true), 'Unknown existing submodule renderer');
            requireThat(!isset($existing[$slot]), 'Duplicate existing submodule renderer');
            $existing[$slot] = $id;
            requireThat(names(at($row, 'requires')) === [] && names($row->requires_exports ?? []) === [], 'Existing renderer dependencies are not configurable');
            requireThat(!isset($existingHeaders[$slot]) || $existingHeaders[$slot] === $header,
                'Scalar publication header conflicts with provider: ' . $id);
        }
        requireThat(preg_match('~^scpp/[a-zA-Z0-9_]+(?:/[a-zA-Z0-9_]+)*\.hpp$~D', $header) === 1, 'Invalid submodule header');
        requireThat(!isset($paths[$header]), 'Conflicting submodule headers');
        $paths[$header] = true;
        $nodes[$id] = ['header' => $header, 'requires' => names(at($row, 'requires')), 'existing' => isset($row->existing)];
    }
    $groups = declarationGroups($metadata);
    $exports = typeExports($metadata, $nodes, $groups, $existing);
    $plan = ['nodes' => $nodes, 'groups' => $groups, 'type_exports' => $exports];
    foreach (object(at($metadata, 'submodules')) as $id => $row) {
        foreach (names($row->requires_exports ?? [], 'Duplicate export dependency') as $identity) {
            $provider = typeExport($plan, $identity)['provider'];
            if ($provider !== $id) $nodes[$id]['requires'][] = $provider;
        }
        $nodes[$id]['requires'] = array_values(array_unique($nodes[$id]['requires']));
        sort($nodes[$id]['requires'], SORT_STRING);
    }
    $states = [];
    $closures = [];
    $visit = function (string $id) use (&$visit, &$states, &$closures, $nodes): void {
        requireThat(isset($nodes[$id]), 'Unknown submodule dependency: ' . $id);
        requireThat(($states[$id] ?? 0) !== 1, 'Cyclic submodule dependency: ' . $id);
        if (($states[$id] ?? 0) === 2) return;
        $states[$id] = 1;
        $closures[$id] = [];
        foreach ($nodes[$id]['requires'] as $dependency) {
            $visit($dependency);
            $closures[$id][$dependency] = true;
            $closures[$id] += $closures[$dependency];
        }
        $states[$id] = 2;
    };
    foreach (array_keys($nodes) as $id) $visit($id);
    ksort($nodes, SORT_STRING);
    foreach ($closures as &$closure) ksort($closure, SORT_STRING);
    unset($closure);
    ksort($closures, SORT_STRING);
    fields(at($metadata, 'output'), ['provider']);
    $plan['nodes'] = $nodes;
    $plan['closures'] = $closures;
    foreach ($plan['groups'] as $name => $group) {
        $id = $group['provider'];
        providerHeader($plan, $id);
        requireThat(!$nodes[$id]['existing'], 'Declaration group cannot replace an existing renderer');
        requireExportDependencies($plan, $id, $group['requires']);
    }
    return $plan;
}

/** Validate intrinsic declaration requirements against the resolved metadata dependency graph. */
function requireExportDependencies(array $plan, string $provider, array $identities): void {
    foreach ($identities as $identity) {
        $dependency = typeExport($plan, $identity)['provider'];
        requireThat($dependency === $provider || isset($plan['closures'][$provider][$dependency]),
            "Provider $provider requires type export $identity");
    }
}

/** Resolve a provider without teaching consumers about header paths. */
function providerHeader(array $plan, string $provider): string {
    requireThat(isset($plan['nodes'][$provider]), 'Unknown submodule provider: ' . $provider);
    return $plan['nodes'][$provider]['header'];
}

/** Produce a private compiler-generation copy; source metadata remains unchanged. */
function compilerProviderMetadata(mixed $metadata, ?array $plan = null): object {
    $plan ??= submodulePlan($metadata);
    $copy = clone $metadata;
    $copy->compiler = clone $metadata->compiler;
    $copy->compiler->runtime_bindings = clone $metadata->compiler->runtime_bindings;
    foreach (object($copy->compiler->runtime_bindings) as $name => $binding) {
        if (($binding->kind ?? null) === 'type' || isset($binding->type_export)) {
            requireThat(($binding->kind ?? null) === 'type', 'Type export binding must have type kind');
            requireThat(!isset($binding->symbol) && !isset($binding->header) && !isset($binding->declaration_group),
                'Type binding has competing export ownership');
            $identity = textValue(at($binding, 'type_export'));
            requireThat(textValue(at($binding, 'identity')) === $identity, 'Incompatible type binding export');
            $export = typeExport($plan, $identity);
            $resolved = clone $binding;
            $resolved->symbol = $export['symbol'];
            $resolved->header = providerHeader($plan, $export['provider']);
            unset($resolved->type_export);
            $copy->compiler->runtime_bindings->{$name} = $resolved;
            continue;
        }
        $owner = null;
        foreach ($plan['groups'] as $group => $contract) {
            foreach ($contract['bindings'] as $export) {
                $matches = true;
                foreach ($export as $field => $value) {
                    if (($binding->{$field} ?? null) !== $value) $matches = false;
                }
                if (!$matches) continue;
                requireThat($owner === null || $owner === $group, 'Ambiguous declaration group export');
                $owner = $group;
            }
        }
        if ($owner === null) {
            requireThat(!isset($binding->declaration_group), 'Binding is not exported by a declaration group');
            continue; // Unmigrated bindings retain their existing header contracts.
        }
        requireThat(!isset($binding->header), 'Binding has both provider and header owners');
        requireThat(textValue(at($binding, 'declaration_group')) === $owner, 'Incompatible binding declaration group');
        $resolved = clone $binding;
        $resolved->header = providerHeader($plan, $plan['groups'][$owner]['provider']);
        unset($resolved->declaration_group);
        $copy->compiler->runtime_bindings->{$name} = $resolved;
    }
    return $copy;
}

/** Assemble movable declaration groups; implementation-only includes stay in their source files. */
function assembleSubmodules(array $plan, array $groups, array &$files): void {
    $content = [];
    $system = [];
    foreach ($groups as $group) {
        $id = $group['provider'];
        $content[$id][] = $group['declarations'];
        foreach ($group['system'] as $header) $system[$id][$header] = true;
    }
    foreach ($content as $id => $declarations) {
        $node = $plan['nodes'][$id];
        $path = 'public/' . $node['header'];
        requireThat(!isset($files[$path]), 'Submodule conflicts with an existing public header');
        $includes = [];
        foreach ($node['requires'] as $dependency) {
            $header = providerHeader($plan, $dependency);
            requireThat(isset($files['public/' . $header]) || isset($content[$dependency]), 'Submodule dependency is not published: ' . $dependency);
            $includes[$header] = true;
        }
        ksort($includes, SORT_STRING);
        $text = BANNER . "#pragma once\n";
        $native = array_keys($system[$id] ?? []);
        sort($native, SORT_STRING);
        foreach ($native as $header) $text .= "#include <$header>\n";
        foreach (array_keys($includes) as $header) $text .= includeHeader($header);
        $files[$path] = $text . implode("\n", $declarations);
    }
}
