<?php
declare(strict_types=1);
namespace Scpp\Api;

require_once __DIR__ . '/regex_captures.php';

const FUNCTION_MODULES = ['filesystem', 'regex'];

/** Resolve the published module signatures once for both native and compiler rendering. */
function prepareModuleFunctions(string $family, mixed $metadata, mixed $publication, array &$plan): void {
    $catalog = familyMetadata($metadata, $publication, $family, $family, $family);
    if ($catalog === null || !property_exists($publication, $family)) return;

    fields($catalog, ['provider', 'namespace', 'functions', 'surface', 'php_exposures', 'php_constants', 'callbacks', 'php_overloads', 'capture_modes', 'capture_operations']);
    $requestFields = ['functions', 'php_exposures', 'callbacks'];
    if ($family === 'regex') $requestFields[] = 'capture_modes';
    if ($family === 'filesystem') $requestFields[] = 'resources';
    fields($publication->{$family}, $requestFields);
    $surface = $catalog->surface ?? null;
    requireThat($surface === null || $surface === 'general', 'Unknown module function surface');
    $provider = textValue(at($catalog, 'provider'));
    $header = providerHeader($plan['submodules'], $provider);
    $module = $plan['submodules']['nodes'][$provider]['module'];
    requireThat($module === 'runtime-' . $family, "Invalid module for $family declaration provider");
    $namespace = textValue(at($catalog, 'namespace'));
    requireThat(preg_match('/^scpp(?:::[A-Za-z_][A-Za-z_0-9]*)+$/D', $namespace) === 1, 'Invalid module namespace');

    if ($family === 'regex') prepareRegexCaptureTypes($publication->regex->functions, $catalog->functions, $header, $plan);
    $published = names(at($publication->{$family}, 'functions'), 'Duplicate module function');
    $signatures = object($catalog->functions);
    if ($family === 'regex' && isset($publication->regex->capture_modes)) {
        $variants = prepareRegexModes($catalog, $publication->regex, $header, $plan);
        $published = array_merge($published, array_keys($variants));
        $signatures += $variants;
    }
    $functions = [];
    $requires = [];
    $nativeNames = [];
    foreach ($published as $name) {
        $pattern = $family === 'filesystem' ? '/^fs_[a-z][a-z_0-9]*$/D' : '/^[a-z][a-z_0-9]*$/D';
        requireThat(preg_match($pattern, $name) === 1, 'Invalid module function name');
        $function = $signatures[$name] ?? throw new \RuntimeException('Unknown module function: ' . $name);
        fields($function, ['native_name', 'parameters', 'result', 'flavor', 'required_parameters', 'overload']);
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
        if (isset($function->overload)) {
            $group = textValue($function->overload);
            requireThat($flavor === 'php', 'Invalid PHP overload group');
            applyModuleOverload($catalog, $group, $functions[$name]);
        }
        if ($surface === 'general') {
            if ($flavor === null) $functions[$name]['surface'] = 'general';
            $functions[$name]['header'] = moduleOperationHeader($header, $name);
            $declarationProviders = $signatureProviders;
            foreach ($parameters as $i => $parameter) {
                if ($i < $required || $parameter['type'] !== 'mixed' || !($reference_mask & (1 << $i))) continue;
                // The short status call only needs the incomplete type for its longer overload declarations.
                unset($declarationProviders[$i]);
                $functions[$name]['forward'] = 'namespace scpp { class mixed; }';
            }
            $functions[$name]['includes'] = array_map(fn($id) => providerHeader($plan['submodules'], $id),
                array_unique($declarationProviders));
            checkHeader($plan, $functions[$name]['header']);
        }
    }
    requireThat($functions !== [], 'Empty module function publication');
    $requires = array_values(array_unique($requires));
    $plan[$family] = compact('provider', 'header', 'module', 'namespace', 'requires', 'functions');
    foreach (names($publication->{$family}->callbacks ?? [], 'Duplicate regex callback') as $name) {
        $item = at(at($catalog, 'callbacks'), $name);
        fields($item, ['input', 'surface', 'table', 'optional_output', 'capture_rows', 'subjects', 'pattern_inputs']);
        $input = textValue(at($item, 'input'));
        $callbackSurface = textValue(at($item, 'surface'));
        requireThat($family === 'regex' && in_array($input, ['mixed', 'vector_string'], true)
            && in_array($callbackSurface, ['general', 'php'], true), 'Unsupported regex callback signature');
        $countOutput = isset($item->optional_output);
        requireThat(!$countOutput || ($callbackSurface === 'php' && $input === 'mixed' && $item->optional_output === 'int64'),
            'Unsupported callback count output');
        $table = boolean(at($item, 'table'));
        $callbackHeader = moduleOperationHeader($header, 'callback_' . $input);
        $includes = [];
        foreach ([$input, 'string', 'result_or_false_string', 'int64'] as $type) {
            $export = moduleFunctionTypeExport($plan, $type);
            $includes[] = providerHeader($plan['submodules'], $export['provider']);
        }
        $plan[$family]['callbacks'][$name] = compact('input', 'table', 'callbackSurface', 'callbackHeader', 'includes', 'countOutput');
        $patterns = names($item->pattern_inputs ?? []);
        if ($patterns !== []) {
            requireThat(!$table && isset($item->capture_rows), 'Pattern collections require a single PHP capture callback');
            foreach ($patterns as $type) {
                requireThat(in_array($type, ['vector_string', 'hash_string_int64', 'hash_string_string'], true), 'Invalid callback pattern collection');
                moduleFunctionTypeExport($plan, $type);
            }
            $plan[$family]['callbacks'][$name]['pattern_inputs'] = $patterns;
        }
        if (isset($item->capture_rows)) {
            requireThat($countOutput, 'PHP capture flags follow the count output');
            foreach (regexCallbackRows($catalog, $publication->regex, $item) as $row => $shape) {
                moduleFunctionTypeExport($plan, $row);
                $rowHeader = moduleOperationHeader($header, 'callback_row_' . $row);
                $plan[$family]['callback_rows'][$row] ??= $shape + ['header' => $rowHeader];
                foreach (names(at($item, 'subjects')) as $subject) {
                    $result = 'result_or_false_' . $subject;
                    moduleFunctionTypeExport($plan, $result);
                    $subjectHeader = $subject === 'string' ? $rowHeader : moduleOperationHeader($header, "callback_row_{$row}_$subject");
                    $plan[$family]['callback_rows'][$row]['subjects'][$subject] = $subjectHeader;
                    foreach ($patterns as $type) $plan[$family]['callback_rows'][$row]['pattern_inputs'][$subject][$type] = true;
                    $plan[$family]['callbacks'][$name]['subjects'][$subject] = $result;
                }
                $plan[$family]['callbacks'][$name]['rows'][$row] = $rowHeader;
            }
        }
    }
    foreach (object($catalog->php_constants ?? (object)[]) as $constant => $value) {
        requireThat(preg_match('/^[A-Z][A-Z_0-9]*$/D', $constant) === 1 && is_int($value), 'Invalid module integer constant');
        $plan[$family]['php_constants'][$constant] = $value;
    }
    foreach (names($publication->{$family}->php_exposures ?? [], 'Duplicate PHP exposure') as $name) {
        $alias = at(at($catalog, 'php_exposures'), $name);
        fields($alias, ['target', 'native_name', 'false_result', 'optional_output', 'arguments', 'overload']);
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
        requireThat((!isset($alias->arguments) || isset($alias->optional_output))
            && (!isset($alias->optional_output) || isset($alias->arguments) !== isset($alias->overload)), 'An optional output requires PHP argument admission');
        if (isset($alias->optional_output)) {
            requireThat(!isset($alias->false_result) && !isset($alias->native_name), 'An optional output requires a direct native target');
            $out = moduleFunctionTypeExport($plan, textValue($alias->optional_output));
            $slot = count($binding['parameters']);
            requireThat($slot < 32, 'Too many module function parameters');
            $binding['reference_mask'] |= 1 << $slot;
            $binding['parameters'][] = ['type' => $out['identity'], 'native' => $out['symbol'] . '&'];
            if (isset($alias->arguments)) $binding['arguments'] = listValue($alias->arguments);
            $binding['output_overload'] = true;
            $binding['includes'] = [$binding['header'], providerHeader($plan['submodules'], $out['provider'])];
            $binding['header'] = "scpp/php/$name.hpp";
            checkHeader($plan, $binding['header']);
        }
        if (isset($alias->overload)) applyModuleOverload($catalog, textValue($alias->overload), $binding);
        $binding['symbol'] = $symbol;
        $plan[$family]['php_exposures'][$name] = $binding;
    }
}

function applyModuleOverload(object $catalog, string $group, array &$binding): void {
    requireThat(preg_match('/^[a-z][a-z_0-9]*$/D', $group) === 1, 'Invalid PHP overload group');
    $policy = at(at($catalog, 'php_overloads'), $group);
    fields($policy, ['selector', 'list_context', 'arguments', 'rule_inputs', 'keep_rows']);
    if (isset($policy->rule_inputs)) {
        $binding['rule_inputs'] = names($policy->rule_inputs);
        $binding['keep_rows'] = textValue(at($policy, 'keep_rows'));
        requireThat($binding['rule_inputs'] !== [] && in_array($binding['keep_rows'], ['all', 'matched'], true), 'Invalid rewrite rule publication');
        $binding['rule_header'] = "scpp/php/{$group}_rules.hpp";
    }
    $selector = at($policy, 'selector');
    requireThat(is_int($selector) && isset($binding['parameters'][$selector])
        && (isset($policy->list_context) === !(bool)($binding['reference_mask'] & (1 << $selector))), 'Invalid module overload selector');
    $binding['overload'] = $group;
    $binding['selector'] = $selector;
    $binding['list_context'] = isset($policy->list_context) ? textValue($policy->list_context) : null;
    $binding['arguments'] = listValue(at($policy, 'arguments'));
}

/** Resolve the concrete public export and guard against unpublished scalar identities. */
function moduleFunctionTypeExport(array $plan, string $type): array {
    $identity = $plan['source_types']->{$type} ?? $type;
    requireThat(hasType($plan, $identity) || hasValue($plan, $identity)
        || (isset($plan['string']) && $identity === 'string')
        || (isset($plan['boxed_dispatch']) && in_array($identity, array_column($plan['boxed']['families'], 'name'), true))
        || in_array($identity, array_column($plan['vectors'] ?? [], 'name'), true)
        || in_array($identity, array_column($plan['hashes']['specializations'] ?? [], 'name'), true)
        || isset($plan['regex_shapes'][$identity])
        || in_array($identity, array_column($plan['regex_vectors'] ?? [], 'name'), true)
        || in_array($identity, array_column($plan['regex_carriers'] ?? [], 'name'), true)
        || in_array($identity, array_column($plan['carriers'] ?? [], 'name'), true),
        'Module function requires a published type: ' . $type);
    return typeExport($plan['submodules'], $identity) + ['identity' => $identity];
}

/** Narrow declarations follow their module provider when a build relocates it. */
function moduleOperationHeader(string $header, string $name): string {
    return substr($header, 0, -4) . '/' . $name . '.hpp';
}

function moduleHeaderBody(array $includes, string $namespace, string $declaration, string $forward = ''): string {
    $body = BANNER . "#pragma once\n";
    foreach (array_unique($includes) as $include) $body .= includeHeader($include);
    if ($forward !== '') $body .= $forward . "\n";
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
            $files['public/' . $function['header']] = moduleHeaderBody($function['includes'], $module['namespace'], $declaration, $function['forward'] ?? '');
        }
    }
    foreach ($module['php_exposures'] ?? [] as $name => $binding) {
        if (isset($binding['output_overload'])) {
            $parameters = implode(', ', array_column($binding['parameters'], 'native'));
            $code = "[[nodiscard]] {$binding['native_result']} {$binding['native_name']}($parameters);\n";
            $files['public/' . $binding['header']] = moduleHeaderBody($binding['includes'], $module['namespace'], $code);
            continue;
        }
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
    foreach ($module['php_exposures'] ?? [] as $binding) {
        if (isset($binding['output_overload'])) $bridge .= includeHeader($binding['header']);
    }
    if ($family === 'filesystem') {
        // The archive compiles all implementations even for a reduced source publication.
        $carrier = typeExport($plan['submodules'], 'result_vector_string');
        $bridge .= includeHeader(providerHeader($plan['submodules'], $carrier['provider']));
    }
    $countedInputs = array_column(array_filter($module['callbacks'] ?? [], fn($cb) => $cb['countOutput']), 'input');
    foreach ($module['callbacks'] ?? [] as $callback) {
        $input = $callback['input'];
        $header = $callback['callbackHeader'];
        if (isset($files['public/' . $header])) continue;
        $declaration = "using render_$input = string (*)(const void*, $input);\n"
            . "result_or_false_string replace_callback(const string&, const void*, render_$input, const string&, int64);\n";
        if (in_array($input, $countedInputs, true))
            $declaration .= "result_or_false_string replace_callback(const string&, const void*, render_$input, const string&, int64, int64&);\n";
        $files['public/' . $header] = moduleHeaderBody($callback['includes'], $module['namespace'], $declaration);
        $bridge .= includeHeader($header);
    }
    foreach ($module['callback_rows'] ?? [] as $row => $shape) {
        $export = moduleFunctionTypeExport($plan, $row);
        $includes = [providerHeader($plan['submodules'], $export['provider'])];
        foreach (['string', 'int64', 'result_or_false_string'] as $type)
            $includes[] = providerHeader($plan['submodules'], moduleFunctionTypeExport($plan, $type)['provider']);
        foreach ($shape['subjects'] as $subject => $subjectHeader) {
            $result = 'result_or_false_' . $subject;
            $providers = $subject === 'string' ? $includes : [$shape['header']];
            foreach ([$subject, $result] as $type)
                $providers[] = providerHeader($plan['submodules'], moduleFunctionTypeExport($plan, $type)['provider']);
            $declaration = $subject === 'string' ? "using callback_row_$row = string (*)(const void*, $row);\n" : '';
            $forward = '';
            foreach (['string', ...array_keys($shape['pattern_inputs'][$subject] ?? [])] as $pattern) {
                if ($pattern !== 'string') $forward .= "namespace scpp { class $pattern; }\n";
                $declaration .= "$result replace_capture_callback(const $pattern&, const void*, callback_row_$row, const $subject&, int64, int64*, int64);\n";
            }
            $files['public/' . $subjectHeader] = moduleHeaderBody($providers, $module['namespace'], $declaration, $forward);
            $bridge .= includeHeader($subjectHeader);
        }
    }
    if ($family === 'regex') {
        $definitions = renderRegexRuleInputs($module, $plan, $files, $bridge);
        $files['private/regex_capture_bindings.hpp'] = renderRegexModeBindings($plan) . $definitions;
    }
    $files["private/{$family}_provider.hpp"] = $bridge;
}

/** Publish ordered-rule signatures separately so single-rule callers retain their narrow header. */
function renderRegexRuleInputs(array $module, array $plan, array &$files, string &$bridge): string {
    $groups = [];
    $definitions = '';
    foreach (array_merge($module['functions'], $module['php_exposures'] ?? []) as $binding) {
        if (!isset($binding['rule_inputs'])) continue;
        $header = $binding['rule_header'];
        $subject = $binding['parameters'][2]['type'];
        $result = $binding['result'];
        $name = $binding['native_name'];
        $filter = $binding['keep_rows'] === 'matched' ? 'true' : 'false';
        foreach ([...$binding['rule_inputs'], 'string', 'int64', $subject, $result] as $type) {
            $export = moduleFunctionTypeExport($plan, $type);
            $groups[$header]['includes'][] = providerHeader($plan['submodules'], $export['provider']);
        }
        foreach ($binding['rule_inputs'] as $patterns) {
            foreach (['string', ...$binding['rule_inputs']] as $replacements) {
                $prefix = "$result $name(const $patterns& patterns, const $replacements& replacements, const $subject& subject, int64 limit";
                $groups[$header]['declarations'][] = "[[nodiscard]] $prefix = -1);\n[[nodiscard]] $prefix, int64& total);\n";
                $definitions .= "$prefix, int64& total) {\n"
                    . "    return apply_rules<$filter, $result>(make_rules(patterns, replacements, limit), subject, limit, total);\n}\n"
                    . "$prefix) {\n    int64 total;\n    return $name(patterns, replacements, subject, limit, total);\n}\n";
            }
        }
    }
    foreach ($groups as $header => $group) {
        $files['public/' . $header] = moduleHeaderBody($group['includes'], $module['namespace'], implode('', $group['declarations']));
        $bridge .= includeHeader($header);
    }
    return "namespace {$module['namespace']} {\n$definitions}\n";
}

/** Build PHP overload tables alongside ordinary module bindings from the same native signatures. */
function compilerModuleFunctionCases(?array $runtime, array $runtimeTypes): array {
    $runtimeCases = [];
    foreach (FUNCTION_MODULES as $family) {
        $overloads = [];
        foreach (array_merge($runtime[$family]['functions'] ?? [], $runtime[$family]['php_exposures'] ?? []) as $name => $binding) {
            $types = array_column($binding['parameters'], 'type');
            foreach ([...$types, $binding['result']] as $type) {
                requireThat(isset($runtimeTypes[$type]), 'Module function signature requires a compiler type: ' . $type);
            }
            $arguments = array_map(static fn(string $type): string => 'type_' . $runtimeTypes[$type] . '::instance()', $types);
            $result = $runtimeTypes[$binding['result']];
            $symbol = var_export('::' . ($binding['symbol'] ?? ($runtime[$family]['namespace'] . '::' . $binding['native_name'])), true);
            $header = var_export($binding['header'] ?? $runtime[$family]['header'], true);
            $module = var_export($runtime[$family]['module'], true);
            $refs = $binding['reference_mask'] ? ', reference_mask: ' . $binding['reference_mask'] : '';
            $mask = $binding['const_reference_mask'];
            if ($binding['required_parameters'] < count($types)) $refs .= ', required_parameters: ' . $binding['required_parameters'];
            $surface = $binding['flavor'] ?? $binding['surface'] ?? null;
            $name = $binding['overload'] ?? $name;
            $key = ($surface === null ? '' : $surface . ':') . 'func:' . $name;
            $policy = '';
            if (isset($binding['arguments'])) {
                $references = [];
                foreach (array_keys($types) as $slot) {
                    if ($binding['reference_mask'] & (1 << $slot)) $references[] = $slot;
                }
                $policy = compilerPhpAdmission([$name => $binding['arguments']], $name, count($types), $references);
            }
            $call = "new runtime_function('$name', [" . implode(', ', $arguments)
                . "], type_$result::instance(), $symbol, $header$refs, const_reference_mask: $mask, required_module: $module$policy)";
            if (isset($binding['overload'])) {
                $index = $binding['selector'];
                $type = $types[$index];
                for ($arity = $binding['required_parameters']; $arity <= count($types); ++$arity) {
                    if ($index >= $arity) {
                        requireThat(!isset($overloads[$key]['fixed'][$arity]), "Ambiguous overload arity for $key");
                        $overloads[$key]['fixed'][$arity] = "$arity => $call";
                        continue;
                    }
                    requireThat(!isset($overloads[$key]['variants'][$arity][$type]), "Duplicate overload selector for $key");
                    $overloads[$key]['variants'][$arity][$type] = 'type_' . $runtimeTypes[$type] . "::class => $call";
                }
                if (isset($binding['rule_inputs'])) {
                    $overloads[$key]['rule_inputs'] = $binding['rule_inputs'];
                    $overloads[$key]['rule_header'] = $binding['rule_header'];
                }
                $overloads[$key]['index'] = $index;
                $overloads[$key]['context'] = $binding['list_context'];
            } else $runtimeCases[] = "\t\t\t'$key' => \$bindings[\$key] ??= $call,";
        }
        foreach ($overloads as $key => $group) {
            $rows = $group['fixed'] ?? [];
            $context = $group['context'];
            foreach ($group['variants'] as $arity => $variants) {
                requireThat($context === null || isset($variants[$context]), "List context has no matching overload for $key");
                $rows[] = "$arity => [" . implode(', ', $variants) . ']';
            }
            $selection = $context === null ? '' : ", list_context: type_{$runtimeTypes[$context]}::instance(), project_selector: true";
            $class = 'runtime_overloads';
            if (isset($group['rule_inputs'])) {
                $class = 'rewrite_rule_overloads';
                $inputs = array_map(fn($t) => "type_{$runtimeTypes[$t]}::class => type_{$runtimeTypes[$t]}::instance()", $group['rule_inputs']);
                $selection .= ", rule_types: [" . implode(', ', $inputs) . "], rule_header: '{$group['rule_header']}'";
            }
            $runtimeCases[] = "\t\t\t'$key' => \$bindings[\$key] ??= new $class([" . implode(', ', $rows)
                . "], argument_index: {$group['index']}$selection, required_module: $module),";
        }
    }
    return $runtimeCases;
}
