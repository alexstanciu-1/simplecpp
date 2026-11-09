<?php
declare(strict_types=1);
namespace Scpp\Api;

/**
 * Contracts of the implemented declaration groups, adjacent to their assembly owner.
 * These describe template requirements and exported binding roles, not C++ ABI signatures.
 * Provider placement remains metadata-owned; scalar membership comes from canonical metadata.
 */
function declarationGroups(mixed $metadata): array {
    $core = array_values(array_intersect(names(at($metadata, 'core_integers')), array_keys(object(at($metadata, 'types')))));
    fields(at($metadata, 'scalar_providers'), ['core', 'numeric']);
    $contracts = [
        'string' => ['requires' => ['bool_t'], 'types' => ['string'], 'bindings' => []],
        'output' => ['requires' => [], 'types' => [], 'bindings' => [
            ['operand' => 'int64', 'strategy' => 'integer'], ['operand' => 'string', 'strategy' => 'bytes']]],
    ];
    $groups = [
        'scalar_core' => ['requires' => [],
            'types' => array_merge($core, array_keys(object(at($metadata, 'values'))), [textValue(at($metadata->floating, 'type'))]),
            'bindings' => [], 'provider' => textValue(at($metadata->scalar_providers, 'core'))],
        'scalar_numeric' => ['requires' => [],
            'types' => array_values(array_diff(array_keys(object(at($metadata, 'types'))), $core)),
            'bindings' => [], 'provider' => textValue(at($metadata->scalar_providers, 'numeric'))],
    ];
    foreach (object($metadata->boxed->providers ?? (object)[]) as $family => $provider) {
        requireThat(in_array($family, ['mixed', 'dynamic'], true), 'Unknown boxed provider family');
        $groups[$family] = ['requires' => [], 'types' => [], 'bindings' => [], 'provider' => textValue($provider)];
    }
    if (isset($metadata->carriers)) {
        $groups['carriers'] = ['requires' => [], 'types' => [], 'bindings' => [], 'provider' => textValue(at($metadata->carriers, 'provider'))];
    }
    if (isset($metadata->hash)) {
        $groups['hashes'] = ['requires' => [], 'types' => [], 'bindings' => [], 'provider' => textValue(at($metadata->hash, 'provider'))];
    }
    if (isset($metadata->vector)) {
        $groups['vectors'] = ['requires' => [], 'types' => [], 'bindings' => [], 'provider' => textValue(at($metadata->vector, 'provider'))];
    }
    if (isset($metadata->checked_conversions)) {
        $groups['conversions'] = ['requires' => [], 'types' => [], 'bindings' => [], 'provider' => textValue(at($metadata->checked_conversions, 'provider'))];
    }
    foreach ($contracts as $name => $contract) {
        // Scalar-only preparation may omit the string family entirely.
        if ($name === 'string' && !isset($metadata->string)) continue;
        $contract['provider'] = textValue(at(at($metadata, $name), 'provider'));
        $groups[$name] = $contract;
    }
    return $groups;
}

/** Resolve canonical runtime identities and existing public spellings, without inspecting representations.
 * Declaration groups own placement; exports do not choose construction or extraction.
 */
function typeExports(mixed $metadata, array $nodes, array $groups): array {
    $owners = [];
    foreach ($groups as $group => $contract) {
        foreach ($contract['types'] as $identity) {
            requireThat(!array_key_exists($identity, $owners), 'Ambiguous type declaration owner');
            $owners[$identity] = $group;
        }
    }
    $aliases = object(at($metadata, 'integer_aliases'));
    $exports = [];
    foreach (listValue(at($metadata, 'type_exports')) as $row) {
        fields($row, ['identity', 'name', 'declaration_group']);
        $identity = textValue(at($row, 'identity'));
        requireThat(array_key_exists($identity, $owners), 'Unknown type export: ' . $identity);
        requireThat(!isset($exports[$identity]), 'Duplicate type export: ' . $identity);
        $name = textValue(at($row, 'name'));
        requireThat(preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $name) === 1, 'Invalid public type name');
        requireThat(($aliases[$name] ?? $name) === $identity, 'Public type name is not declared for export: ' . $identity);
        $group = textValue(at($row, 'declaration_group'));
        requireThat($owners[$identity] === $group, 'Incompatible type export declaration group');
        $provider = $groups[$group]['provider'];
        requireThat(isset($nodes[$provider]), 'Unknown type export provider: ' . $provider);
        $exports[$identity] = ['symbol' => 'scpp::' . $name, 'provider' => $provider];
    }
    ksort($exports, SORT_STRING);
    return $exports;
}

/** Publish concrete family identities from the validated specialization owner, not a second metadata inventory. */
function publishTypeExports(array &$plan, string $group, array $identities): void {
    $provider = $plan['groups'][$group]['provider'];
    foreach ($identities as $identity) {
        requireThat(!isset($plan['type_exports'][$identity]), 'Duplicate published type export: ' . $identity);
        $plan['type_exports'][$identity] = ['symbol' => 'scpp::' . $identity, 'provider' => $provider];
    }
    $plan['groups'][$group]['types'] = $identities;
    ksort($plan['type_exports'], SORT_STRING);
}

/** A semantic export requirement resolves to the same provider used by compiler name queries. */
function typeExport(array $plan, string $identity): array {
    requireThat(isset($plan['type_exports'][$identity]), 'Missing type export: ' . $identity);
    return $plan['type_exports'][$identity];
}

/** Resolve catalog providers; publication demand never grants source-language admission. */
function submodulePlan(mixed $metadata): array {
    $nodes = [];
    $paths = [];
    foreach (object(at($metadata, 'submodules')) as $id => $row) {
        requireThat(preg_match('/^[a-z][a-z0-9_.]*$/D', $id) === 1, 'Invalid submodule identity');
        fields($row, ['module', 'header', 'requires', 'requires_exports']);
        // Numeric catalog presence does not activate optional publication or compiler types.
        requireThat(in_array(textValue(at($row, 'module')), ['core', 'numeric'], true), 'Unavailable submodule module');
        $header = textValue(at($row, 'header'));
        requireThat(preg_match('~^scpp/[a-zA-Z0-9_]+(?:/[a-zA-Z0-9_]+)*\.hpp$~D', $header) === 1, 'Invalid submodule header');
        requireThat(!isset($paths[$header]), 'Conflicting submodule headers');
        $paths[$header] = true;
        $nodes[$id] = ['header' => $header, 'requires' => names(at($row, 'requires')), 'module' => textValue(at($row, 'module'))];
    }
    $groups = declarationGroups($metadata);
    $exports = typeExports($metadata, $nodes, $groups);
    $plan = ['nodes' => $nodes, 'groups' => $groups, 'type_exports' => $exports];
    foreach (object(at($metadata, 'submodules')) as $id => $row) {
        foreach (names($row->requires_exports ?? [], 'Duplicate export dependency') as $identity) {
            $provider = typeExport($plan, $identity)['provider'];
            if ($provider !== $id) $nodes[$id]['requires'][] = $provider;
        }
        $nodes[$id]['requires'] = array_values(array_unique($nodes[$id]['requires']));
        sort($nodes[$id]['requires'], SORT_STRING);
    }
    ksort($nodes, SORT_STRING);
    $closures = providerClosures($nodes);
    fields(at($metadata, 'output'), ['provider']);
    $plan['nodes'] = $nodes;
    $plan['closures'] = $closures;
    foreach ($plan['groups'] as $name => $group) {
        $id = $group['provider'];
        providerHeader($plan, $id);
        requireThat($nodes[$id]['module'] === ($name === 'scalar_numeric' ? 'numeric' : 'core'), 'Declaration group module mismatch');
        requireExportDependencies($plan, $id, $group['requires']);
    }
    $separate = [$groups['scalar_core']['provider'], $groups['scalar_numeric']['provider']];
    foreach ($groups as $name => $group) {
        if (!in_array($name, ['scalar_core', 'scalar_numeric'], true)) {
            requireThat(!in_array($group['provider'], $separate, true), 'Scalar declaration providers must remain independent');
        }
    }
    if (isset($groups['conversions'])) $separate[] = $groups['conversions']['provider'];
    requireThat(count(array_unique($separate)) === count($separate), 'Scalar and conversion providers must be separate');
    return $plan;
}

/** Validate one provider graph for catalog edges and selected declaration requirements. */
function providerClosures(array $nodes): array {
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
    foreach ($closures as &$closure) ksort($closure, SORT_STRING);
    unset($closure);
    ksort($closures, SORT_STRING);
    return $closures;
}

/** Resolve actual signature/declaration identities to provider dependencies. */
function exportProviders(array $plan, array $identities): array {
    $providers = [];
    foreach ($identities as $identity) $providers[typeExport($plan, $identity)['provider']] = true;
    $out = array_keys($providers);
    sort($out, SORT_STRING);
    return $out;
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

const STRING_VECTOR_IMPLEMENTATIONS = ['split_bytes', 'join_bytes'];

function stringAdapterHeader(string $flavor, string $implementation): string {
    $file = in_array($implementation, STRING_VECTOR_IMPLEMENTATIONS, true) ? 'string_vectors' : 'string';
    return "scpp/$flavor/$file.hpp";
}

const STRING_VALUE_ERROR_IMPLEMENTATIONS = ['compare_prefix', 'pad_bytes', 'count_matches', 'split_bytes', 'find', 'rfind'];

/** Configure-time inventory for managed headers; full semantic preparation still validates publication. */
function publicationProviderHeaders(mixed $metadata, mixed $publication): array {
    $plan = submodulePlan($metadata);
    $aliases = object(at($metadata, 'integer_aliases'));
    $identities = array_map(fn(string $name): string => $aliases[$name] ?? $name, names(at($publication, 'types')));
    $identities = array_merge($identities, names(at($publication, 'values')));
    $providers = exportProviders($plan, $identities);
    foreach (['output', 'conversions', 'string', 'vectors', 'carriers', 'hashes'] as $group) {
        if (!isset($plan['groups'][$group]) || (in_array($group, ['string', 'vectors', 'carriers', 'hashes'], true) && !isset($publication->{$group}))) continue;
        $providers[] = $plan['groups'][$group]['provider'];
    }
    if ($publication->boxed->scalar_dispatch ?? false) {
        foreach ($publication->boxed->families as $family) $providers[] = $plan['groups'][$family]['provider'];
    }
    $headers = array_map(fn(string $id): string => providerHeader($plan, $id), array_unique($providers));
    foreach ($publication->string->functions ?? [] as $name) {
        $function = at($metadata->string->functions, $name);
        if (in_array($function->implementation, STRING_VALUE_ERROR_IMPLEMENTATIONS, true)) $headers[] = 'scpp/value_error.hpp';
        if (!($function->flavor_adapters ?? false)) continue;
        $flavors = $function->flavors ?? ['php', ...array_keys((array)($function->members ?? []) + (array)($function->free_names ?? []))];
        foreach ($flavors as $flavor) $headers[] = stringAdapterHeader($flavor, $function->implementation);
    }
    $headers = array_values(array_unique($headers));
    sort($headers, SORT_STRING);
    return $headers;
}

/** Resolve the effective graph once all published declarations and signatures are prepared. */
function finalizePublicationProviders(array &$plan): void {
    $providers = &$plan['submodules'];
    $published = exportProviders($providers, array_column(array_merge($plan['types'], $plan['values']), 'name'));
    $published[] = $providers['groups']['output']['provider'];
    foreach (['string', 'checked_conversions', 'vector_publication', 'carrier_publication', 'hash_publication'] as $family) {
        if (!isset($plan[$family])) continue;
        $declaration = $plan[$family];
        $id = $declaration['provider'];
        $published[] = $id;
        foreach ($declaration['requires'] as $dependency) {
            if ($dependency !== $id && !isset($providers['closures'][$id][$dependency])) {
                $providers['nodes'][$id]['requires'][] = $dependency;
            }
        }
    }
    if (isset($plan['boxed_dispatch'])) {
        foreach ($plan['boxed']['families'] as $family) {
            $id = typeExport($providers, $family['name'])['provider'];
            $published[] = $id;
            foreach (exportProviders($providers, ['int64', 'bool_t', 'float64', 'string']) as $dependency) {
                if ($dependency !== $id) $providers['nodes'][$id]['requires'][] = $dependency;
            }
        }
    }
    foreach ($providers['nodes'] as &$node) {
        $node['requires'] = array_values(array_unique($node['requires']));
        sort($node['requires'], SORT_STRING);
    }
    unset($node);
    $providers['closures'] = providerClosures($providers['nodes']);
    $providers['published'] = array_values(array_unique($published));
    sort($providers['published'], SORT_STRING);
    foreach ($providers['published'] as $id) {
        foreach ($providers['nodes'][$id]['requires'] as $dependency) {
            requireThat(in_array($dependency, $providers['published'], true), 'Submodule dependency is not published: ' . $dependency);
        }
    }
}

/** Render the finalized publication graph; implementation-only includes stay in source files. */
function assembleSubmodules(array $plan, array $groups, array &$files): void {
    $content = [];
    $system = [];
    foreach ($groups as $group) {
        $id = $group['provider'];
        $content[$id][] = $group['declarations'];
        foreach ($group['system'] as $header) $system[$id][$header] = true;
    }
    $published = array_keys($content);
    sort($published, SORT_STRING);
    requireThat($published === $plan['published'], 'Rendered declaration providers disagree with prepared publication');
    foreach ($content as $id => $declarations) {
        $node = $plan['nodes'][$id];
        $path = 'public/' . $node['header'];
        requireThat(!isset($files[$path]), 'Submodule conflicts with an existing public header');
        $includes = [];
        foreach ($node['requires'] as $dependency) {
            $header = providerHeader($plan, $dependency);
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
