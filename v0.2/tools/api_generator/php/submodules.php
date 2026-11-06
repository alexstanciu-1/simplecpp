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
        'string' => ['requires' => ['types', 'values'], 'exports' => [['kind' => 'type', 'identity' => 'string']]],
        'output' => ['requires' => [], 'exports' => [
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

/** Resolve public providers once; existing scalar renderers retain their publication-owned paths. */
function submodulePlan(mixed $metadata, ?array $existingHeaders = null): array {
    $nodes = [];
    $paths = [];
    $existing = [];
    foreach (object(at($metadata, 'submodules')) as $id => $row) {
        requireThat(preg_match('/^[a-z][a-z0-9_.]*$/D', $id) === 1, 'Invalid submodule identity');
        fields($row, ['module', 'header', 'requires', 'existing']);
        // Only core providers are integrated in this slice; header reachability grants no language admission.
        requireThat(textValue(at($row, 'module')) === 'core', 'Unavailable submodule module');
        $header = textValue(at($row, 'header'));
        if (isset($row->existing)) {
            $slot = textValue($row->existing);
            requireThat(in_array($slot, ['types', 'values'], true), 'Unknown existing submodule renderer');
            requireThat(!isset($existing[$slot]), 'Duplicate existing submodule renderer');
            $existing[$slot] = $id;
            requireThat(names(at($row, 'requires')) === [], 'Existing renderer dependencies are not configurable');
            $header = $existingHeaders[$slot] ?? $header;
        }
        requireThat(preg_match('~^scpp/[a-zA-Z0-9_]+(?:/[a-zA-Z0-9_]+)*\.hpp$~D', $header) === 1, 'Invalid submodule header');
        requireThat(!isset($paths[$header]), 'Conflicting submodule headers');
        $paths[$header] = true;
        $nodes[$id] = ['header' => $header, 'requires' => names(at($row, 'requires')), 'existing' => isset($row->existing)];
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
    fields(at($metadata, 'output'), ['provider']);
    $plan = ['nodes' => $nodes, 'groups' => declarationGroups($metadata)];
    foreach ($plan['groups'] as $name => $group) {
        $id = $group['provider'];
        providerHeader($plan, $id);
        requireThat(!$nodes[$id]['existing'], 'Declaration group cannot replace an existing renderer');
        foreach ($group['requires'] as $slot) {
            requireThat(isset($existing[$slot]) && isset($closures[$id][$existing[$slot]]),
                "Declaration group $name requires existing provider $slot");
        }
    }
    return $plan;
}

/** Resolve a provider without teaching consumers about header paths. */
function providerHeader(array $plan, string $provider): string {
    requireThat(isset($plan['nodes'][$provider]), 'Unknown submodule provider: ' . $provider);
    return $plan['nodes'][$provider]['header'];
}

/** Produce a private compiler-generation copy; source metadata remains unchanged. */
function compilerProviderMetadata(mixed $metadata): object {
    $plan = submodulePlan($metadata);
    $copy = clone $metadata;
    $copy->compiler = clone $metadata->compiler;
    $copy->compiler->runtime_bindings = clone $metadata->compiler->runtime_bindings;
    foreach (object($copy->compiler->runtime_bindings) as $name => $binding) {
        $owner = null;
        foreach ($plan['groups'] as $group => $contract) {
            foreach ($contract['exports'] as $export) {
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
