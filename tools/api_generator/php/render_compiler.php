<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/submodules.php';

require_once __DIR__ . '/compiler_families.php';
require_once __DIR__ . '/compiler_plans.php';
require_once __DIR__ . '/render_compiler_vectors.php';
require_once __DIR__ . '/render_compiler_carriers.php';
require_once __DIR__ . '/render_compiler_hashes.php';
require_once __DIR__ . '/boxed_dispatch.php';

/** Bind the two source observations to checked, nullary runtime member signatures. */
function compilerCardinalityMethods(array $methods, string $count, array $definitions): string {
    $bindings = [];
    foreach ($methods as $method) {
        $name = $method['name'];
        if (!in_array($name, ['size', 'empty'], true) || $method['arguments'] !== []) continue;
        $result = $name === 'size' ? $count : 'bool_t';
        $readOnly = isset($method['receiver'])
            ? $method['receiver'] === 'const_reference' && $method['result_category'] === 'value'
            : $method['mutates'] === false;
        requireThat($readOnly && $method['result'] === $result && isset($definitions[$result]),
            'Invalid collection cardinality binding: ' . $name);
        requireThat(!isset($bindings['@' . $name . '@']), 'Duplicate collection cardinality binding');
        $bindings['@' . $name . '@'] = $name;
        $bindings[$name === 'size' ? '@count_type@' : '@empty_type@'] = 'type_' . $definitions[$result]['class'];
    }
    requireThat(isset($bindings['@size@'], $bindings['@empty@']), 'Missing collection cardinality binding');
    return strtr(readFile(__DIR__ . '/../templates/collections/collection_cardinality.php.tpl'), $bindings);
}

/** Compile PHP source admission independently of native argument representation. */
function compilerPhpAdmission(array $policies, string $name, int $arity, array $references = [], bool $allowPrefix = false): string {
    if (!isset($policies[$name])) return '';
    $slots = listValue($policies[$name]);
    if ($allowPrefix) $slots = array_slice($slots, 0, $arity);
    requireThat(count($slots) === $arity, "Wrong PHP admission arity for $name");
    $labels = [];
    $exact = 0;
    foreach ($slots as $i => $slot) {
        requireThat(is_array($slot) && count($slot) === 2, "Invalid PHP admission slot for $name");
        [$label, $mode] = $slot;
        requireThat(is_string($label) && preg_match('/^[a-z_][a-z0-9_]*$/D', $label)
            && !isset($labels[$label]), "Invalid PHP argument label for $name");
        requireThat(in_array($mode, ['typed', 'exact', 'writable'], true)
            && ($mode === 'writable') === in_array($i, $references, true), "Invalid PHP argument mode for $name");
        $labels[$label] = var_export($label, true);
        if ($mode === 'exact') $exact |= 1 << $i;
    }
    return ', php_argument_labels: [' . implode(', ', $labels) . "], php_exact_inputs: $exact";
}

/** Generate the supported core scalar definitions; module membership determines availability. */
function renderCompiler(mixed $metadata, array $facts, ?array $runtime = null): array {
    // A null plan is the scalar-only generator test surface; production always supplies a validated runtime plan.
    $providers = $runtime['submodules'] ?? submodulePlan($metadata);
    $metadata = compilerProviderMetadata($metadata, $providers);
    $types = registry($metadata, $facts);
    $compiler = at($metadata, 'compiler');
    fields($compiler, ['positional_subset_family', 'php_builtin_admission', 'published_transport_types', 'generic_syntax', 'interfaces', 'modules', 'integer_literals', 'value_types', 'conversions', 'floating_literals', 'conditions', 'comparisons', 'arithmetic', 'updates', 'unary', 'promotions', 'runtime_bindings', 'lowering_strategies', 'output', 'php_scalar_output', 'entry', 'integer_operators', 'conversion_families', 'mixed_numeric_operators', 'shifts', 'concatenation', 'strict_identity', 'three_way', 'power', 'exception', 'division']);
    $phpAdmission = object($compiler->php_builtin_admission ?? new \stdClass());
    if (isset($metadata->string->functions, $metadata->boxed->mixed_predicates)) {
        foreach ($phpAdmission as $name => $_) {
            requireThat(isset($metadata->string->functions->$name) || isset($metadata->boxed->mixed_predicates->$name),
                "Unknown PHP admission target: $name");
        }
    }
    $extentFamilies = [];
    foreach (object($compiler->generic_syntax ?? new \stdClass()) as $family => $arguments) {
        requireThat(preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $family) && $arguments === ['type', 'integer_extent'], 'Unsupported generic syntax: ' . $family);
        $extentFamilies[] = var_export($family, true);
    }
    $safety = compilerLoweringSafety($compiler);
    $definitions = compilerTypePlan($metadata, $facts, $types, $providers);
    if ($runtime !== null) {
        $nullIdentity = $metadata->source_types->null;
        requireThat(hasValue($runtime, $nullIdentity), "Compiler null requires published $nullIdentity");
    }
    $metadata = clone $metadata;
    $compiler = compilerFamilyPlan($metadata, $definitions, $types);
    $metadata->compiler = $compiler;
    $families = compilerLiteralFamilies($definitions);
    $files = [];
    $interface = $metadata->compiler->interfaces->iterator ?? null;
    if ($interface !== null) {
        fields($metadata->compiler->interfaces, ['iterator']);
        fields($interface, ['name', 'type_parameters', 'method', 'result', 'parameter', 'optional_key_parameter', 'visibility', 'receiver']);
        fields($interface->parameter, ['type_argument', 'mode']);
        fields($interface->optional_key_parameter, ['type_argument', 'mode']);
        requireThat($interface->type_parameters === 1 && $interface->result === 'bool_t'
            && $interface->parameter->type_argument === 0 && $interface->parameter->mode === 'reference'
            && $interface->optional_key_parameter->type_argument === 1 && $interface->optional_key_parameter->mode === 'reference'
            && $interface->visibility === 'public' && $interface->receiver === 'mutable_reference', 'Unsupported Iterator contract');
        requireThat(preg_match('/^[A-Za-z_][A-Za-z_0-9]*$/D', $interface->name) === 1
            && preg_match('/^[A-Za-z_][A-Za-z_0-9]*$/D', $interface->method) === 1, 'Invalid interface identifier');
    }
    $files['types/interfaces.php'] = strtr(readFile(__DIR__ . '/../templates/collections/iterator_contract.php.tpl'), [
        '@name@' => var_export($interface?->name, true), '@method@' => var_export($interface?->method, true),
    ]);
    $contract = $runtime['to_string'] ?? null;
    $files['types/interfaces.php'] .= strtr(readFile(__DIR__ . '/../templates/core/stringable_contract.php.tpl'), [
        '@name@' => var_export($contract['interface'] ?? null, true),
        '@method@' => var_export($contract['method'] ?? null, true),
        '@source@' => var_export($contract === null ? null : "interface {$contract['interface']} { {$contract['result']} {$contract['method']}(); };", true),
    ]);
    $conversions = compilerConversionPlan($metadata, $definitions, $types, $facts, $runtime['explicit_formatters'] ?? []);
    $conditions = compilerConditionPlan($compiler, $definitions);
    $comparisons = compilerComparisonPlan($compiler, $definitions, names(at(at($metadata, 'string'), 'operations')));
    $binary = compilerBinaryPlan($compiler, $definitions, $comparisons, $types, names(at(at($metadata, 'string'), 'operations')));
    $output = compilerOutputPlan($compiler, $definitions);
    $updates = compilerUpdatePlan($compiler, $definitions);
    $unary = compilerUnaryPlan($compiler, $definitions);
    $promotions = compilerPromotionPlan($compiler, $conversions, $definitions, $binary);
    // Transport types participate in declarations and identity passing only. In particular
    // the PHP host never needs to represent UINT64_MAX or fold a uint64 operation.
    $optionalRequires = '';
    foreach (object($compiler->published_transport_types ?? new \stdClass()) as $identity => $module) {
        requireThat(is_string($module) && isset($types[$identity], $compiler->modules->$module) && !isset($definitions[$identity]),
            'Invalid published transport type: ' . $identity);
        if ($runtime === null || !hasType($runtime, $identity)) continue;
        $export = typeExport($providers, $identity);
        $definitions[$identity] = ['class' => $identity, 'cpp' => $export['symbol'],
            'cpp_header' => providerHeader($providers, $export['provider']), 'aliases' => [$identity],
            'strategy' => 'transport', 'literal_family' => null, 'literals' => [], 'copy_semantics' => 'trivial',
            'declarations' => ['inferred_local' => true, 'global_storage' => true, 'instance_field' => true],
            'required_module' => $module];
        $optionalRequires .= "require_once __DIR__ . '/$identity.php';\n";
    }
    $moduleTypes = ($runtime['regex_records'] ?? []) + ($runtime['json_handles'] ?? []) + ($runtime['resource_types'] ?? []);
    foreach (($runtime['text_builders'] ?? []) + $moduleTypes as $identity => $builder) {
        $definitions[$identity] = ['class' => $identity, 'cpp' => "::scpp::$identity",
            'cpp_header' => $builder['header'], 'aliases' => [$identity], 'strategy' => $builder['strategy'] ?? 'builder',
            'literal_family' => null, 'literals' => [], 'copy_semantics' => 'owning', 'required_module' => $builder['module'] ?? null,
            'declarations' => ['inferred_local' => true, 'global_storage' => true, 'instance_field' => true]];
        $optionalRequires .= "require_once __DIR__ . '/$identity.php';\n";
    }
    foreach ($definitions as $identity => $definition) {
        $keyContract = $runtime['hashes']['key_contracts'][$identity] ?? null;
        $typeTraits = $keyContract === null ? '' : "\tuse hash_key_specializations;\n\tuse concrete_value_storage;\n\n";
        $phpOutput = $compiler->php_scalar_output->$identity ?? null;
        if ($phpOutput !== null && isset($output['int64'])) {
            requireThat(in_array($phpOutput, ['echo_int', 'echo_uint', 'echo_float', 'echo_php_bool', 'discard'], true), 'Unknown PHP scalar output');
            $output[$identity] = ['header' => $output['int64']['header'], 'php' => true,
                'symbol' => $phpOutput === 'discard' ? 'static_cast<void>' : "scpp::$phpOutput"];
        }
        $methods = compilerDeclarations($definition['declarations']) . compilerOutput($output[$identity] ?? null) . compilerConversions($conversions, $definitions, $identity) . compilerConditions($conditions, $identity) . compilerBinary($compiler, $definitions, $identity, $safety, $binary, $types) . compilerUpdates($updates[$identity] ?? []) . compilerUnary($unary[$identity] ?? [], $identity, $safety, $definitions, $types) . compilerPromotions($promotions[$identity] ?? [], $definitions);
        if ($contract !== null && (isset($runtime['explicit_formatters'][$identity]) || $identity === 'string'
            || isset($contract['integers'][$identity]) || isset($contract['builders'][$identity]))) {
            $builder = isset($contract['builders'][$identity]);
            $header = $builder ? $contract['builders'][$identity] : 'scpp/to_string.hpp';
            if (isset($contract['integers'][$identity])) $header = 'scpp/numeric/to_string.hpp';
            $symbol = $builder ? $contract['method'] : '::scpp::to_string';
            $mode = $builder ? 'receiver_method: true' : 'free_receiver: true';
            $borrow = $builder || $identity === 'string' ? 1 : 0;
            // Existing scalar conversions retain their constant facts and direct lowering.
            $existing = isset($runtime['explicit_formatters'][$identity]) || $identity === 'string';
            $methods .= "\n\tpublic function native_string_formatter(bool \$fallback = false): ?runtime_function\n\t{\n"
                . ($existing ? "\t\tif (\$fallback) return null;\n" : '')
                . "\t\tstatic \$binding = null;\n\t\treturn \$binding ??= new runtime_function('{$contract['method']}', [\$this], type_string::instance(), '$symbol', '$header', "
                . "const_reference_mask: $borrow, $mode, retains_receiver: false, exact_arguments: true);\n\t}\n";
        }
        $kind = isset($types[$identity]) ? 'integer' : $definition['strategy'];
        if (in_array($kind, ['integer', 'boolean', 'floating', 'string', 'null'], true)) {
            $methods .= "\n\tpublic function predicate_kind(): string { return '$kind'; }\n";
        }
        if (isset($runtime['structural_decoder'][$identity])) {
            $reader = $runtime['structural_decoder'][$identity];
            $methods .= "\n\tpublic function structural_reader(): ?string { return '$reader'; }\n";
        }
        if (isset($definition['constant_dispatch'])) {
            $methods .= "\n\tpublic function constant_dispatch(): constant_dispatch { return constant_dispatch::" . $definition['constant_dispatch'] . "; }\n";
        }
        if (isset($definition['cpp_header'])) {
            $header = var_export($definition['cpp_header'], true);
            $methods .= "\n\t/** @return string[] */\n\tpublic function cpp_headers(): array /** list<string> */\n\t{\n\t\treturn [$header];\n\t}\n";
        }
        if ($keyContract !== null) {
            foreach (['hash' => ['lower_cpp_hash_key', ['key']], 'equal' => ['lower_cpp_key_equal', ['left', 'right']]] as $operation => [$method, $arguments]) {
                $expression = var_export($keyContract[$operation], true);
                $parameters = [];
                foreach ($arguments as $argument) {
                    $parameters[] = 'string $' . $argument;
                    $expression = str_replace('@' . $argument . '@', "' . \$" . $argument . " . '", $expression);
                }
                $methods .= "\n\tpublic function $method(" . implode(', ', $parameters) . "): ?string\n\t{\n\t\treturn $expression;\n\t}\n";
            }
        }
        if ($definition['copy_semantics'] === 'trivial') {
            $methods .= "
\t/** Native scalar storage has no observable copy operation. */
\tpublic function cpp_trivial_value(): bool
\t{
\t\treturn true;
\t}
";
        }
        if (isset($compiler->value_types->$identity) && $definition['copy_semantics'] === 'trivial') {
            $methods .= "\n\tpublic function copy_has_no_observable_effects(): bool { return true; }\n";
        }
        if ($definition['literal_family'] === 'integer') {
            $accept = implode(' || ', array_map(static fn(string $alias): string => '($name === ' . var_export($alias, true) . ')', $definition['aliases']));
            $nativeSuffix = match ($types[$identity]['native_name']) {
                'int' => '', 'long' => 'L', 'long long' => 'LL',
                default => throw new \RuntimeException('Unsupported native signed integer literal type'),
            };
            $files['types/' . $definition['class'] . '.php'] = strtr(readFile(__DIR__ . '/../templates/scalars/signed_integer.php.tpl'), [
                '@radix_cases@' => implode(', ', array_map(static fn(string $prefix, int $radix): string => var_export($prefix, true) . ' => ' . $radix, array_keys(object($compiler->integer_literals->prefixes)), array_values(object($compiler->integer_literals->prefixes)))),
                '@native_suffix@' => var_export($nativeSuffix, true),
                '@native_minimum@' => var_export('(-' . $definition['maximum'] . $nativeSuffix . ' - 1' . $nativeSuffix . ')', true),
                '@name@' => $identity, '@maximum@' => $definition['maximum'], '@magnitude@' => $definition['magnitude'],
                '@accept_names@' => $accept, '@representation@' => var_export($definition['cpp'], true),
                '@cpp_minimum@' => var_export($definition['minimum_cpp'], true), '@cpp_suffix@' => var_export($definition['suffix'], true),
                '@type_traits@' => $typeTraits, '@conversion_methods@' => $methods,
            ]);
            continue;
        }
        $limits = '';
        if ($definition['strategy'] === 'integer') {
            $type = $types[$identity];
            requireThat($type['signed'] || $type['bits'] < 64, 'Unsigned 64-bit compiler facts remain deferred');
            $maximum = decimalPredecessor(decimalPowerOfTwo(digits($type)));
            $minimum = $type['signed'] ? '(-' . $maximum . ' - 1)' : '0';
            $limits = "\t/** CONSTANTS */\n\tpublic const int MINIMUM /** $identity */ = $minimum;\n\tpublic const int MAXIMUM /** $identity */ = $maximum;\n\n";
        }
        $accept = implode(' || ', array_map(static fn(string $alias): string => '($name === ' . var_export($alias, true) . ')', $definition['aliases']));
        $literalMethods = $definition['literal_family'] === 'floating'
            ? strtr(readFile(__DIR__ . '/../templates/scalars/floating_literal_methods.php.tpl'), [
                '@minimum_exponent@' => (string)(2 - $facts['double']['max_exponent']),
                '@exponent_bias@' => (string)($facts['double']['max_exponent'] - 1),
            ]) : compilerLiteralMethods($definition);
        if ($definition['literal_family'] === 'string') $literalMethods = strtr(readFile(__DIR__ . '/../templates/strings/string_literal_methods.php.tpl'), ['@storage_header@' => var_export($definition['storage']['header'], true)]);
        $indexInterface = $definition['strategy'] === 'builder' ? ' implements text_builder_type' : '';
        $handle = isset($runtime['resource_types'][$identity]) && !isset($moduleTypes[$identity]['fields']);
        if ($handle) $indexInterface = ' implements runtime_handle_type';
        if (isset($moduleTypes[$identity]['fields']) || isset($moduleTypes[$identity]['properties'])) {
            $indexInterface = $handle ? ' implements runtime_handle_type, runtime_field_type' : ' implements runtime_field_type';
            if (isset($runtime['regex_records'][$identity])) $indexInterface = ' implements runtime_field_getters';
            $fields = [];
            foreach (($moduleTypes[$identity]['fields'] ?? $moduleTypes[$identity]['properties']) as $field => $type) {
                $class = $definitions[$type]['class'] ?? $type;
                $fields[] = "'$field' => type_{$class}::instance()";
            }
            $methods .= "\n\tpublic function runtime_field(string \$name): ?type_definition { return match (\$name) { "
                . implode(', ', $fields) . ", default => null }; }\n";
        }
        if ($identity === 'string' && isset($runtime['string_index_read'])) {
            $read = $runtime['string_index_read'];
            requireThat(isset($definitions[$read['key']], $definitions[$read['result']]), 'Compiler string index requires key and result types');
            $indexInterface = ' implements index_read_type';
            foreach ($runtime['string_functions'] ?? [] as $binding) {
                if ($binding['implementation'] !== 'byte_length') continue;
                $indexInterface = ' implements scoped_byte_read_type';
                $viewHeader = var_export($runtime['string_view']['header'], true);
                $methods .= "\n\tpublic function byte_view_header(): string { return $viewHeader; }\n";
                $methods .= "\n\tpublic function is_byte_length(runtime_function \$function): bool\n\t{\n"
                    . "\t\treturn \$function->symbol === " . var_export($binding['symbol'], true) . ";\n\t}\n";
                break;
            }
            $methods .= "\n" . compilerIndexRead('String', 'type_' . $definitions[$read['key']]['class'],
                'type_' . $definitions[$read['result']]['class'], $read['name']);
        }
        if ($identity === 'string' && isset($runtime['string_index_write'])) {
            $write = $runtime['string_index_write'];
            requireThat(isset($definitions[$write['key']], $definitions[$write['value']]), 'Compiler string store requires key and byte types');
            $indexInterface .= $indexInterface === '' ? ' implements index_write_type' : ', index_write_type';
            $methods .= "\n" . strtr(readFile(__DIR__ . '/../templates/strings/string_index_write.php.tpl'), [
                '@key@' => 'type_' . $definitions[$write['key']]['class'], '@value@' => 'type_' . $definitions[$write['value']]['class'],
                '@method@' => $write['name'],
            ]);
        }
        if ($identity === 'string' && isset($runtime['string'])) {
            $indexInterface .= $indexInterface === '' ? ' implements counted_collection_type' : ', counted_collection_type';
            $indexInterface .= compilerAccessInterfaces($metadata->string, isset($runtime['string_index_read']));
            $methods .= "\n" . readFile(__DIR__ . '/../templates/strings/string_cardinality.php.tpl');
        }
        if ($identity === 'string' && ($runtime['string']['transfer_assignment'] ?? false)) {
            $indexInterface .= $indexInterface === '' ? ' implements private_value_transfer_type' : ', private_value_transfer_type';
        }
        $hostChecks = $identity === 'float64' ? "\t\tif ((PHP_FLOAT_DIG !== 15) || (PHP_FLOAT_EPSILON !== 2.0 ** (1 - " . $facts['double']['digits'] . "))\n"
            . "\t\t\t|| (PHP_FLOAT_MIN !== 2.0 ** (2 - " . $facts['double']['max_exponent'] . "))) {\n"
            . "\t\t\tthrow new \\RuntimeException('Compiler host requires IEEE binary64');\n\t\t}\n" : '';
        $files['types/' . $definition['class'] . '.php'] = strtr(readFile(__DIR__ . '/../templates/scalars/scalar_type.php.tpl'), [
            '@name@' => $identity, '@class@' => $definition['class'], '@limits@' => $limits, '@index_interface@' => $indexInterface,
            '@accept_names@' => $accept, '@representation@' => var_export($definition['cpp'], true),
            '@constructor_checks@' => $hostChecks, '@literal_methods@' => $literalMethods, '@type_traits@' => $typeTraits, '@conversion_methods@' => $methods,
        ]);
    }
    $cases = [];
    $literalCases = [];
    foreach ($definitions as $definition) {
        $aliases = implode(', ', array_map(static fn(string $alias): string => var_export($alias, true), $definition['aliases']));
        $cases[] = "\t\t\t" . $aliases . ' => type_' . $definition['class'] . '::instance(),';
        foreach ($definition['literals'] ?? [] as $word => $value) {
            $literalCases[] = "\t\t\t" . var_export($word, true) . ' => type_' . $definition['class'] . '::instance(),';
        }
    }
    [$vectorCode, $vectorCases, $vectorFamily, $applicationVectors] = compilerVectorTypes($metadata, $runtime, $definitions);
    $files['types/vectors.php'] = $vectorCode;
    [$carrierCode, $carrierCases, $carrierLiterals, $carrierTypes] = compilerCarrierTypes($runtime, $definitions);
    $files['types/carriers.php'] = $carrierCode . compilerPeerEquality($metadata->carriers ?? null, $definitions, $promotions, $binary);
    $literalCases = array_merge($literalCases, $carrierLiterals);
    $genericCases = [];
    if ($vectorFamily !== null) $genericCases[$vectorFamily] = $vectorCases;
    foreach ($runtime['carrier_families'] ?? [] as $family => $policy) {
        $genericCases[$family] = implode("\n", $carrierCases[$family] ?? []);
    }
    $arity = [];
    $specializations = '';
    foreach ($genericCases as $family => $matches) {
        $name = var_export($family, true);
        $matches = str_replace("\t\t\t", "\t\t\t\t", $matches);
        $arity[] = "\t\t\t$name => 1,";
        $specializations .= "\t\tif (\$family === $name) {\n\t\t\treturn match (\$element) {\n$matches\n\t\t\t\tdefault => null,\n\t\t\t};\n\t\t}\n";
    }
    [$hashCode, $hashCases, $hashFamily, $applicationHashes] = compilerHashTypes($metadata, $runtime, $definitions);
    $files['types/hashes.php'] = $hashCode;
    if ($hashFamily !== null) {
        $name = var_export($hashFamily, true);
        $arity[] = "\t\t\t$name => 2,";
        $specializations .= "\t\tif (\$family === $name) {\n$hashCases\n\t\t\treturn null;\n\t\t}\n";
    }
    if (isset($runtime['boxed_dispatch'])) {
        $files['types/boxed.php'] = compilerBoxedDefinitions($runtime, $cases, compilerAccessInterfaces($metadata->boxed));
    }
    $runtimeTypes = [];
    foreach ($definitions as $identity => $definition) $runtimeTypes[$identity] = $definition['class'];
    $vectorFactory = false;
    foreach ($runtime['vectors'] ?? [] as $vector) {
        if (isset($definitions[$vector['element']])) {
            $vectorFactory = $vectorFactory || !empty($vector['capacity_helpers']);
            $runtimeTypes[$vector['name']] = $runtimeTypes[$vector['source_type']] = $vector['name'];
        }
    }
    foreach ($runtime['regex_vectors'] ?? [] as $vector) $runtimeTypes[$vector['name']] = $vector['name'];
    foreach (array_keys($runtime['regex_shapes'] ?? []) as $shape) $runtimeTypes[$shape] = $shape;
    $runtimeTypes += $carrierTypes;
    foreach ($runtime['hashes']['specializations'] ?? [] as $hash) {
        if (isset($runtimeTypes[$hash['key']], $runtimeTypes[$hash['value']])) $runtimeTypes[$hash['name']] = $hash['name'];
    }
    if (isset($runtime['boxed_dispatch'])) {
        foreach ($runtime['boxed']['families'] as $box) $runtimeTypes[$box['name']] = $box['name'];
    }
    $runtimeCases = array_merge(compilerJsonCases($runtime, $runtimeTypes), compilerResourceCases($runtime, $runtimeTypes));
    foreach ($runtime['regex']['callbacks'] ?? [] as $name => $binding) {
        $key = $binding['callbackSurface'] . ':func:' . $name;
        $input = 'type_' . $runtimeTypes[$binding['input']] . '::instance()';
        $table = $binding['table'] ? 'true' : 'false';
        $countOutput = $binding['countOutput'] ? 'true' : 'false';
        if (isset($binding['rows'])) {
            $allowed = $runtime['regex']['php_constants']['PREG_OFFSET_CAPTURE'] | $runtime['regex']['php_constants']['PREG_UNMATCHED_AS_NULL'];
            $rows = [];
            foreach ($binding['rows'] as $row => $header) {
                $type = $runtimeTypes[$row];
                $flags = $runtime['regex']['callback_rows'][$row]['flags'];
                $headers = [];
                foreach ($binding['subjects'] as $subject => $_) {
                    $provider = $runtime['regex']['callback_rows'][$row]['subjects'][$subject];
                    $headers[] = "type_{$runtimeTypes[$subject]}::class => '$provider'";
                }
                $rows[] = "type_$type::instance()->name => [type_$type::instance(), [" . implode(', ', $headers) . "], $flags]";
            }
            $subjects = [];
            foreach ($binding['subjects'] as $subject => $result) {
                $type = $runtimeTypes[$subject];
                $subjects[] = "type_$type::class => type_{$runtimeTypes[$result]}::instance()";
            }
            $patterns = [];
            foreach ($binding['pattern_inputs'] ?? [] as $pattern) {
                $type = $runtimeTypes[$pattern];
                $patterns[] = "type_$type::class => type_$type::instance()";
            }
            $runtimeCases[] = "\t\t\t'$key' => \$bindings[\$key] ??= new regex_callback_overloads('$name', $table, $allowed, [" . implode(', ', $rows) . "], [" . implode(', ', $subjects) . "], [" . implode(', ', $patterns) . "]),";
            continue;
        }
        $runtimeCases[] = "\t\t\t'$key' => \$bindings[\$key] ??= new regex_callback_function('$name', $input, $table, '{$binding['callbackHeader']}', $countOutput),";
    }
    foreach ($runtime['collection_transforms'] ?? [] as $name) {
        $surface = in_array($name, ['array_map', 'array_filter', 'in_array'], true) ? 'php' : 'general';
        $runtimeCases[] = "\t\t\t'$surface:func:$name' => runtime_factory::$name,";
    }
    if ($vectorFactory) {
        $runtimeCases[] = "\t\t\t'general:func:vector_filled' => runtime_factory::vector_filled,";
    }
    // Published aliases delegate to the same canonical type-owned signatures as application concrete types.
    foreach (array_merge($runtime['vectors'] ?? [], $runtime['hashes']['specializations'] ?? []) as $collection) {
        $elements = isset($collection['element']) ? [$collection['element']] : [$collection['value'], $collection['key']];
        foreach ($elements as $element) if (!isset($runtimeTypes[$element])) continue 2;
        $source = $collection['source_type'] ?? ($collection['source_family'] . '<' . implode(',', $elements) . '>');
        foreach ($collection['methods'] as $method) {
            $name = $method['name'];
            if (!sourceCollectionMember($name)) continue;
            $keys = array_map(static fn($flavor) => var_export("$flavor:$source:$name", true), ['php', 'cpp', 'js']);
            $runtimeCases[] = "\t\t\t" . implode(', ', $keys) . " => type_{$collection['name']}::instance()->runtime_method('$name'),";
        }
    }

    foreach ($runtime['boxed']['families'] ?? [] as $family) {
        if (!isset($runtime['boxed_dispatch'])) continue;
        $source = $family['name'];
        $header = providerHeader($runtime['submodules'], typeExport($runtime['submodules'], $source)['provider']);
        if ($source === 'mixed') {
            foreach ($runtime['mixed_predicates'] ?? [] as $name => $kind) {
                $runtimeCases[] = "\t\t\t'php:func:$name' => \$bindings[\$key] ??= new php_kind_predicate('$kind'),";
                $function = "new runtime_function('$name', [type_mixed::instance()], type_bool::instance(), '::scpp::$name', '$header', const_reference_mask: 1";
                $runtimeCases[] = "\t\t\t'general:func:$name' => \$bindings['mixed:$name'] ??= new runtime_overloads([1 => [type_mixed::class => $function, php_exact_inputs: 1)]]),";
            }
        }
        foreach ($family['source_collection']['methods'] as $name => $binding) {
            if ($name === 'clear') {
                $keys = array_map(static fn($flavor) => var_export("$flavor:$source:$name", true), ['php', 'cpp', 'js']);
                $runtimeCases[] = "\t\t\t" . implode(', ', $keys) . " => \$bindings['$source:$name'] ??= new runtime_function('$name', [type_$source::instance()], type_void::instance(), '$binding', '$header', reference_mask: 1, receiver_method: true),";
                continue;
            }
            $variants = [];
            foreach ($family['source_collection']['key_types'] as $keyType) {
                $keyClass = $definitions[$keyType]['class'];
                $referenceMask = $name === 'remove' ? 1 : 0;
                $constMask = $name === 'has' ? 1 : 0;
                if ($keyType === 'string') $constMask |= 2;
                $variants[] = "type_$keyClass::class => new runtime_function('$name', [type_$source::instance(), type_$keyClass::instance()], type_bool::instance(), '$binding', '$header', reference_mask: $referenceMask, const_reference_mask: $constMask, receiver_method: true, php_exact_inputs: 2)";
            }
            $keys = array_map(static fn($flavor) => var_export("$flavor:$source:$name", true), ['php', 'cpp', 'js']);
            $runtimeCases[] = "\t\t\t" . implode(', ', $keys) . " => \$bindings['$source:$name'] ??= new runtime_overloads([2 => [" . implode(', ', $variants) . "]], argument_index: 1, receiver_method: true),";
        }
    }

    foreach ($runtime['string_functions'] ?? [] as $name => $binding) {
        if (!isset($runtimeTypes[$binding['result']])) continue;
        $parameters = [];
        foreach ($binding['parameters'] as $type) {
            if (!isset($runtimeTypes[$type])) continue 2;
            $parameters[] = 'type_' . $runtimeTypes[$type] . '::instance()';
        }
        $result = $runtimeTypes[$binding['result']];
        if (isset($binding['adapters'])) {
            foreach ($binding['adapters'] as $flavor => $adapter) {
                $scope = $flavor === 'php' || isset($adapter['free']) ? 'func' : $binding['parameters'][0];
                $key = "$flavor:$scope:" . $adapter['name'];
                if (isset($binding['overloads'])) {
                    $rows = [];
                    $project = $binding['project_selector'] ?? false;
                    if ($project) requireThat(count($phpAdmission[$name] ?? []) === max(array_keys($binding['overloads'])),
                        "Incomplete PHP union argument policy for $name");
                    foreach ($binding['overloads'] as $argumentCount => $variants) {
                        $byType = [];
                        $boxes = $project ? array_column($runtime['boxed']['families'] ?? [], 'name') : [];
                        foreach ($boxes as $box) {
                            $types = reset($variants);
                            $types[0] = $box;
                            $variants[$box] = $types;
                        }
                        foreach ($variants as $input => $types) {
                            if (!isset($runtimeTypes[$input])) continue;
                            $params = array_map(static fn($type) => 'type_' . $runtimeTypes[$type] . '::instance()', $types);
                            $constRefs = stringAdapterConstReferenceMask($types);
                            $boxed = in_array($input, $boxes, true);
                            $header = $boxed ? "scpp/php/{$name}_$input.hpp" : $adapter['header'];
                            $symbol = $boxed ? $adapter['symbol'] . '_' . $input : $adapter['symbol'];
                            if ($boxed) $constRefs |= 1;
                            $overload = $boxed ? 'false' : 'true';
                            $policy = $project ? compilerPhpAdmission($phpAdmission, $name, $argumentCount, allowPrefix: true) : '';
                            $byType[] = 'type_' . $runtimeTypes[$input] . "::class => new runtime_function('$name', ["
                                . implode(', ', $params) . "], type_$result::instance(), '$symbol', '$header', '$flavor', overloaded_first: $overload, const_reference_mask: $constRefs$policy)";
                        }
                        $rows[] = "$argumentCount => [" . implode(', ', $byType) . ']';
                    }
                    $runtimeCases[] = "\t\t\t'$key' => \$bindings[\$key] ??= new runtime_overloads([" . implode(', ', $rows) . ']' . ($project ? ', project_selector: true' : '') . '),';
                    continue;
                }
                $types = stringAdapterOrder($adapter['parameters'] ?? $binding['parameters'], $adapter);
                $arguments = array_map(static fn($type) => 'type_' . $runtimeTypes[$type] . '::instance()', $types);
                $adapterResult = $adapter['result'] ?? $result;
                $requiredCount = $adapter['required_parameters'] ?? $binding['required_parameters'] ?? null;
                $required = $requiredCount === null ? '' : ', required_parameters: ' . $requiredCount;
                if (isset($adapter['reference_parameters'])) {
                    $required .= ', reference_mask: ' . array_sum(array_map(static fn($i) => 1 << $i, $adapter['reference_parameters']));
                }
                $constRefs = stringAdapterConstReferenceMask($types);
                if ($constRefs) $required .= ', const_reference_mask: ' . $constRefs;
                if ($flavor === 'php') $required .= compilerPhpAdmission($phpAdmission, $name, count($types), $adapter['reference_parameters'] ?? []);
                $symbol = $adapter['symbol'];
                $header = $adapter['header'];
                $adapterFlavor = ", '$flavor'";
                if (isset($binding['direct_header'])) {
                    if (isset($adapter['search_offset']) && !isset($adapter['body'])) {
                        $policy = '::scpp::str::search_policy::' . $adapter['search_offset'];
                        $required .= ", full_arity_argument: '$policy'";
                    }
                    $symbol = '::' . (isset($adapter['body']) ? $adapter['symbol'] : $binding['symbol']);
                    $header = isset($adapter['body']) ? $adapter['header'] : $binding['direct_header'];
                    $adapterFlavor = '';
                }
                if (isset($adapter['parameter_names'])) {
                    $names = array_map(static fn(string $label): string => var_export($label, true), $adapter['parameter_names']);
                    $required .= ', parameter_names: [' . implode(', ', $names) . ']';
                }
                $call = "new runtime_function('$name', [" . implode(', ', $arguments)
                    . "], type_$adapterResult::instance(), '$symbol', '$header'$adapterFlavor$required)";
                if ($key === 'php:func:implode' && ($hashes = stringJoinHashes($runtime)) !== []) {
                    $vector = $runtimeTypes[$types[1]];
                    $variants = ["type_$vector::class => $call"];
                    foreach ($hashes as $hash) {
                        $type = 'type_' . $hash['name'];
                        $variants[] = "$type::class => new runtime_function('implode', [type_string::instance(), $type::instance()], type_string::instance(), '::scpp::php::implode', 'scpp/php/implode_hash.hpp', const_reference_mask: 3)";
                    }
                    $call = 'new runtime_overloads([2 => [' . implode(', ', $variants)
                        . "]], argument_index: 1, list_context: type_$vector::instance())";
                }
                $keys = [$key];
                if ($flavor === 'php') foreach ($binding['php_aliases'] ?? [] as $alias) $keys[] = "php:func:$alias";
                $bindingKeys = implode(', ', array_map(static fn($k) => var_export($k, true), $keys));
                $runtimeCases[] = "\t\t\t$bindingKeys => \$bindings[\$key] ??= $call,";
            }
            continue;
        }
        $keys = [$binding['members'] ? "php:func:$name" : "func:$name"];
        foreach ($binding['members'] as $flavor => $member) $keys[] = "$flavor:string:$member";
        $policy = compilerPhpAdmission($phpAdmission, $name, count($parameters));
        $labels = implode(', ', array_map(static fn($key) => var_export($key, true), $keys));
        $runtimeCases[] = "\t\t\t$labels => \$bindings['$name'] ??= new runtime_function('$name', ["
            . implode(', ', $parameters) . "], type_$result::instance(), '{$binding['symbol']}', '{$binding['header']}'$policy),";
    }
    foreach (compilerBuilderCases($runtime['text_builders'] ?? [], $runtimeTypes) as $case) $runtimeCases[] = $case;
    foreach (compilerCliCases($runtime ?? [], $runtimeTypes) as $case) $runtimeCases[] = $case;
    foreach ($runtime['text_helpers'] ?? [] as $name => $binding) {
        $resultModule = $definitions[$binding['result']]['required_module'] ?? null;
        $call = compilerTextBinding($name, $binding, $runtimeTypes, $resultModule);
        $runtimeCases[] = "\t\t\t'general:func:$name' => \$bindings[\$key] ??= $call,";
    }
    foreach ($runtime['datetime']['operations'] ?? [] as $op) {
        $name = $op['source_name'];
        if ($name === null) continue;
        foreach ([...$op['parameters'], $op['result']] as $type) {
            requireThat($type === 'void' || isset($runtimeTypes[$type]), 'Datetime binding requires a compiler type: ' . $type);
        }
        $arguments = array_map(static fn(string $type): string => 'type_' . $runtimeTypes[$type] . '::instance()', $op['parameters']);
        $result = $op['result'] === 'void' ? 'void' : $runtimeTypes[$op['result']];
        $symbol = '::' . $runtime['datetime']['namespace'] . '::' . $op['name'];
        $mask = (1 << count($arguments)) - 1;
        $runtimeCases[] = "\t\t\t'general:func:$name' => \$bindings[\$key] ??= new runtime_function('$name', ["
            . implode(', ', $arguments) . "], type_$result::instance(), '$symbol', '{$op['header']}', const_reference_mask: $mask, required_module: 'runtime-datetime', exact_arguments: true),";
    }
    array_push($runtimeCases, ...compilerModuleFunctionCases($runtime, $runtimeTypes));
    if ($runtime['mt_segments'] ?? false) {
        $cases[] = "\t\t\t'segment_range' => type_segment_range::instance(),";
        $runtimeCases[] = "\t\t\t'func:balanced_range' => \$bindings[\$key] ??= new runtime_function('balanced_range', "
            . "[type_int64::instance(), type_int64::instance(), type_int64::instance()], type_segment_range::instance(), "
            . "'::scpp::mt::balanced_range', 'scpp/mt_segment.hpp', parameter_names: ['length', 'parts', 'part']),";
    }
    $exportedNames = array_column(array_merge($runtime['types'] ?? [], $runtime['values'] ?? []), 'name');
    if (isset($runtime['string'])) $exportedNames[] = $runtime['string']['name'];
    $exportedNames = array_merge($exportedNames, array_keys((array) ($runtime['aliases'] ?? [])));
    $shortTypes = implode(', ', array_map(static fn($name) => var_export($name, true), $exportedNames));
    $constantCases = [];
    foreach ($runtime['resource_modules'] ?? [] as $module => $catalog) {
        foreach ($catalog['constants'] as $name => $value) {
            $constantCases[] = "\t\t\t'general:const:$name' => \$constants[\$key] ??= new runtime_integer_constant(\$key, $value, 'runtime-$module'),";
            $constantCases[] = "\t\t\t'const:$name' => \$constants[\$key] ??= new runtime_integer_constant(\$key, $value, 'runtime-$module'),";
        }
    }
    foreach (FUNCTION_MODULES as $family) {
        foreach ($runtime[$family]['php_constants'] ?? [] as $name => $value) {
            $module = $runtime[$family]['module'];
            $constantCases[] = "\t\t\t'const:$name' => \$constants[\$key] ??= new runtime_integer_constant(\$key, $value, '$module'),";
        }
    }
    foreach ($runtime['php_integer_constants'] ?? [] as $name => $value) {
        $constantCases[] = "\t\t\t'const:$name' => \$constants[\$key] ??= new runtime_integer_constant(\$key, $value),";
    }
    $moduleCases = [];
    foreach ($definitions as $definition) {
        if ($definition['required_module'] === null) continue;
        $aliases = implode(', ', array_map(static fn(string $name): string => var_export($name, true), $definition['aliases']));
        $moduleCases[] = "\t\t\t$aliases => " . var_export($definition['required_module'], true) . ',';
    }
    $exception = $runtime === null ? coreExceptionBinding($metadata) : ($runtime['exception'] ?? null);
    $asyncTypes = array_map(static fn(string $name): string => 'type_' . $name . '::class', array_unique($runtimeTypes));
    $numericScalars = [];
    foreach ($definitions as $definition) {
        if ($definition['strategy'] === 'integer' || $definition['strategy'] === 'floating') {
            $numericScalars[] = 'type_' . $definition['class'] . '::instance()';
        }
    }
    $subsetFamily = $compiler->positional_subset_family ?? null;
    requireThat($subsetFamily === null || (is_string($subsetFamily) && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $subsetFamily)), 'Invalid positional subset family');
    $files['types/registry.php'] = strtr(readFile(__DIR__ . '/../templates/core/type_registry.php.tpl'), [
        '@record_result_header@' => isset($runtime['structural_decoder'])
            ? 'public const ?string RECORD_RESULT_HEADER = ' . var_export($runtime['carriers_header'] ?? null, true) . ";\n\t" : '',
        '@mt_available@' => ($runtime['mt_segments'] ?? false) ? 'true' : 'false',
        '@async_available@' => ($runtime['async_frames'] ?? false) ? 'true' : 'false',
        '@async_value_types@' => implode(', ', [...$asyncTypes, 'type_void::class']),
        '@exception@' => $exception === null ? 'null' : "type_exception::instance("
            . implode(', ', array_map(static fn($value) => var_export($value, true), $exception)) . ')',
        '@numeric_scalar_cases@' => $numericScalars === [] ? '' : "\t\t\t" . implode(', ', $numericScalars) . ' => true,',
        '@module_cases@' => implode("\n", $moduleCases),
        '@cpp_type_aliases@' => $shortTypes === '' ? '' : "\t\t\t$shortTypes => true,",
        '@integer_constants@' => implode("\n", $constantCases), '@runtime_cases@' => implode("\n", $runtimeCases), '@family_cases@' => $families['cases'], '@family_accessors@' => $families['accessors'],
        '@optional_requires@' => $optionalRequires . (isset($files['types/boxed.php']) ? "require_once __DIR__ . '/boxed.php';\n" : ''),
        '@extent_families@' => implode(', ', $extentFamilies),
        '@mapped_sequence@' => $vectorFamily === null ? 'null' : 'Type_Resolution::collection(' . var_export($vectorFamily, true) . ', $element, null)',
        '@mapped_keyed@' => $hashFamily === null ? 'null' : 'Type_Resolution::collection(' . var_export($hashFamily, true) . ', $element, $key)',
        '@positional_subset@' => $subsetFamily === null ? 'null' : 'Type_Resolution::collection(' . var_export($subsetFamily, true) . ', $element, self::integer())',
        '@generic_arity@' => implode("\n", $arity), '@specializations@' => $specializations, '@application_specializations@' => $applicationVectors . $applicationHashes,
        '@cases@' => implode("\n", $cases), '@literal_cases@' => implode("\n", $literalCases),
    ]);
    $files['bindings/entry.php'] = compilerEntryBinding($compiler, cliStartup($runtime ?? []));
    $files['inventory.json'] = encode(['generator' => 'scpp-compiler-lowering', 'version' => 58,
        'files' => array_keys($files)]) . "\n";
    return $files;
}

/** Resolve the native entry ABI without treating native int as a source-language type. */
function compilerEntryBinding(mixed $compiler, bool $arguments): string {
    $entry = at($compiler, 'entry');
    fields($entry, ['checked_binding']);
    $binding = at(at($compiler, 'runtime_bindings'), textValue(at($entry, 'checked_binding')));
    fields($binding, ['operand', 'result', 'symbol', 'header']);
    literals($binding, ['operand' => 'int64', 'result' => 'native_int']);
    $symbol = textValue(at($binding, 'symbol'));
    $header = textValue(at($binding, 'header'));
    requireThat(preg_match('/^scpp::[A-Za-z_][A-Za-z0-9_]*$/D', $symbol) === 1, 'Invalid entry binding symbol');
    requireThat(preg_match('~^scpp/[a-zA-Z0-9_]+(?:/[a-zA-Z0-9_]+)*\.hpp$~D', $header) === 1, 'Invalid entry binding header');
    return strtr(readFile(__DIR__ . '/../templates/core/entry_binding.php.tpl'), [
        '@arguments@' => $arguments ? 'true' : 'false', '@symbol@' => var_export($symbol, true), '@header@' => var_export($header, true),
    ]);
}

/** Generate literal handling from a closed, validated scalar vocabulary. */
function compilerLiteralMethods(array $definition): string {
    if ($definition['literals'] === []) return '';
    $invalid = "throw new diagnostic(\$syntax->source(), \$syntax->byte_start(), 'Invalid scalar literal')";
    if ($definition['strategy'] === 'boolean') {
        $cases = [];
        foreach ($definition['literals'] as $word => $value) {
            $cases[] = "\t\t\t" . var_export($word, true) . ' => ' . var_export($value, true) . ',';
        }
        $prepare = "\$syntax->settle_boolean(\$this, match (\$syntax->word) {\n" . implode("\n", $cases)
            . "\n\t\t\tdefault => $invalid,\n\t\t});";
        $lower = "\$syntax->constant() ? 'true' : 'false'";
    } else {
        $word = var_export(array_key_first($definition['literals']), true);
        $prepare = "if (\$syntax->word !== $word) $invalid;\n\t\t\$syntax->settle_keyword(\$this);";
        $lower = "'nullptr'";
    }
    return <<<PHP_CODE
	/** Validate only this type's literal spelling, then attach occurrence facts. */
	public function prepare_literal(ast_expression \$syntax): void
	{
		if (!(\$syntax instanceof ast_keyword_expression)) {
			parent::prepare_literal(\$syntax);
			return;
		}
		$prepare
	}

	public function lower_cpp_literal(ast_expression \$syntax): string
	{
		if (!(\$syntax instanceof ast_keyword_expression) || (\$syntax->type_def() !== \$this) || !\$syntax->constant_known()) {
			throw new \LogicException('Expected a prepared scalar literal');
		}
		return $lower;
	}
PHP_CODE;
}

/** Resolve structural-family ownership and keyword spellings before emitting direct registry dispatch. */
function compilerLiteralFamilies(array $definitions): array {
    $owners = [];
    $words = [];
    foreach ($definitions as $identity => $definition) {
        $family = $definition['literal_family'];
        if ($family === 'keyword') {
            requireThat(in_array($definition['strategy'], ['boolean', 'null'], true), 'Incompatible keyword literal strategy');
            requireThat($definition['literals'] !== [], 'Keyword family needs literal spellings');
            foreach ($definition['literals'] as $word => $_) {
                requireThat(!isset($words[$word]), 'Duplicate literal spelling: ' . $word);
                $words[$word] = $identity;
            }
        } elseif ($family !== null) {
            requireThat(!isset($owners[$family]), 'Duplicate literal family owner: ' . $family);
            requireThat($definition['strategy'] === $family, 'Incompatible literal preparation strategy');
            $owners[$family] = $definition['class'];
        } else {
            requireThat($definition['literals'] === [], 'Literal spellings require keyword family');
        }
    }
    $cases = [];
    $accessors = [];
    // AST vocabulary is compiler-owned; metadata chooses each family's canonical type.
    foreach (['integer' => 'ast_integer_expression', 'floating' => 'ast_float_expression', 'string' => 'ast_string_expression'] as $family => $node) {
        requireThat(isset($owners[$family]), 'Missing literal family owner: ' . $family);
        $class = $owners[$family];
        $cases[] = "\t\t\t\$value instanceof $node => self::$family(),";
        $accessors[] = "\t/** Canonical $family literal identity selected by metadata. */
\tpublic static function $family(): type_$class
\t{
\t\treturn type_$class::instance();
\t}";
    }
    if ($words !== []) $cases[] = "\t\t\t" . '$value instanceof ast_keyword_expression => self::literal($value->word),';
    return ['cases' => implode("\n", $cases), 'accessors' => implode("\n\n", $accessors)];
}

/** Emit declaration-policy queries, separate from representation and conversion permissions. */
function compilerDeclarations(array $declarations): string {
    $methods = '';
    foreach ($declarations as $capability => $supported) {
        $result = $supported ? 'true' : 'false';
        $methods .= "
\t/** Current compiler declaration capability; does not grant conversions. */
\tpublic function supports_$capability(): bool
\t{
\t\treturn $result;
\t}
";
    }
    return $methods;
}

/** Emit type-owned output dispatch; formatting/lifetime algorithms stay in the backend. */
function compilerOutput(?array $selected): string {
    if ($selected === null) return '';
    $admission = isset($selected['php']) ? '$flavor === flavor::php' : 'true';
    $header = var_export($selected['header'], true);
    $symbol = var_export($selected['symbol'], true);
    $expression = isset($selected['literal_symbol'])
        ? 'Cpp_Output::string($value, $operand, ' . $symbol . ', ' . var_export($selected['literal_symbol'], true) . ')'
        : $symbol . " . '(' . \$operand . ');'";
    return "
\t/** Generated output admission; does not authorize string conversion. */
\tpublic function supports_output(flavor \$flavor): bool
\t{
\t\treturn $admission;
\t}

\t/** @return string[] */
\tpublic function output_headers(): array /** list<string> */
\t{
\t\treturn [$header];
\t}

\t/** Consume an approved output operand using the selected binding. */
\tpublic function lower_cpp_output(ast_expression \$value, ?string \$operand): string
\t{
\t\treturn $expression;
\t}
";
}

/** Emit direct conversion branches; no conversion matrix is interpreted by the running compiler. */
function compilerConversions(array $conversions, array $definitions, string $target): string {
    $prepare = [];
    $lower = [];
    $checkedLower = [];
    $constants = [];
    $headerCases = [];
    foreach ($conversions as $row) {
        $from = $row['from'];
        $to = $row['to'];
        $contexts = $row['contexts'];
        $operation = $row['operation'];
        if ($to !== $target) continue;
        if ($operation === 'format_text') {
            $condition = '$context === conversion_context::explicit_cast && $value->value_type() === type_' . $definitions[$from]['class'] . '::instance()';
            $code = 'value_conversion::format_' . $from;
            $prepare[] = "\t\tif ($condition) {\n\t\t\treturn $code;\n\t\t}";
            $binding = $row['binding'];
            $lower[] = "\t\t\t$code => '{$binding['symbol']}(static_cast<{$binding['source_cpp']}>(' . \$value . '))',";
            $constants[] = "\t\t\t$code => constant_evaluation::unavailable,";
            $headerCases[] = "\t\t\t$code => [" . var_export($binding['header'], true) . '],';
            continue;
        }
        if ($row['binding'] !== null) {
            $symbol = $row['binding']['symbol'];
            // C++ intermediates (for example LL literals) must match the helper's exact prepared-source ABI.
            $sourceCpp = $row['binding']['source_cpp'];
            $checked = $operation === 'floating_integer' ? 'floating_integer_checked' : 'integer_checked';
            $headerCases[] = "\t\t\tvalue_conversion::$checked => [" . var_export($row['binding']['header'], true) . '],';
        }
        $condition = '$value->value_type() === type_' . $definitions[$from]['class'] . '::instance()';
        $permission = array_map(static fn(string $context): string => '$context === conversion_context::' . $context, $contexts);
        $condition = '(' . ($permission === [] ? 'false' : implode(' || ', $permission)) . ') && (' . $condition . ')';
        $prepare[] = "\t\tif ($condition) {";
        $constantOperation = match ($operation) {
            'boolean_integer' => '$value === null ? null : (int) $value', 'integer_boolean' => '$value === null ? null : $value !== 0',
            'numeric_floating' => '$value === null ? null : (float) $value',
            'floating_boolean' => '$value === null ? null : $value != 0.0',
            'floating_integer' => '$value === null ? null : (int) $value',
            default => '$value',
        };
        $constantCases = match ($operation) {
            'checked_integer' => 'value_conversion::integer_proven, value_conversion::integer_checked',
            'floating_integer' => 'value_conversion::floating_integer_proven, value_conversion::floating_integer_checked',
            default => 'value_conversion::' . $operation,
        };
        $constants[] = "\t\t\t" . $constantCases . ' => ' . $constantOperation . ',';
        if ($operation === 'floating_integer') {
            $bound = $row['floating_bound'] . '.0';
            $lowerBound = $row['integer_signed'] ? '-' . $bound : '0.0';
            $prepare[] = strtr(<<<'CODE'
			if ($value->constant_known()) {
				$constant = $value->constant();
				$truncated = $constant < 0.0 ? ceil($constant) : floor($constant);
				if (!is_finite($constant) || ($truncated < @lower@) || ($truncated >= @upper@)) {
					throw new diagnostic($value->source(), $value->byte_start(), 'Floating conversion is outside ' . $this->name . ' range');
				}
				return value_conversion::floating_integer_proven;
			}
			return value_conversion::floating_integer_checked;
CODE, ['@lower@' => $lowerBound, '@upper@' => $bound]);
            $lower[] = "\t\t\tvalue_conversion::floating_integer_proven => 'static_cast<' . \$this->cpp_type() . '>(' . \$value . ')',";
            $checkedLower['floating_integer_checked'][$from] = "'{$symbol}(' . \$value . ')'";
        } elseif ($operation === 'checked_integer') {
            $prepare[] = <<<'CODE'
			if ($value->constant_known()) {
				$constant = $value->constant();
				if (($constant < self::MINIMUM) || ($constant > self::MAXIMUM)) {
					throw new diagnostic($value->source(), $value->byte_start(), 'Conversion is outside ' . $this->name . ' range');
				}
				return value_conversion::integer_proven;
			}
			return value_conversion::integer_checked;
CODE;
            $lower[] = "\t\t\tvalue_conversion::integer_proven => 'static_cast<' . \$this->cpp_type() . '>(' . \$value . ')',";
            $checkedLower['integer_checked'][$from] = "'{$symbol}(' . self::checked_integer_operand(\$syntax, \$value, '{$sourceCpp}') . ')'";
        } else {
            $prepare[] = "\t\t\treturn value_conversion::$operation;";
            $expression = in_array($operation, ['integer_boolean', 'floating_boolean'], true) ? "'(' . \$value . ' != 0)'" : "'static_cast<' . \$this->cpp_type() . '>(' . \$value . ')'";
            $lower[] = "\t\t\tvalue_conversion::$operation => $expression,";
        }
        $prepare[] = "\t\t}";
    }
    if ($prepare === []) return '';
    foreach ($checkedLower as $operation => $sources) {
        $arms = [];
        foreach ($sources as $source => $expression) {
            $class = $definitions[$source]['class'];
            $arms[] = "\t\t\t\ttype_$class::instance() => $expression,";
        }
        $lower[] = "\t\t\tvalue_conversion::$operation => match (\$source_type ?? \$syntax?->value_type()) {\n"
            . implode("\n", $arms) . "\n\t\t\t\tdefault => throw new \\LogicException('Checked conversion requires its prepared source type'),\n\t\t\t},";
    }
    $prepare = implode("\n", $prepare);
    $lower = implode("\n", array_unique($lower));
    $constants = implode("\n", array_unique($constants));
    $headers = '';
    if ($headerCases !== []) {
        $cases = implode("\n", array_unique($headerCases));
        $headers = <<<PHP_CODE

	/** @return string[] Public runtime includes required by the selected conversion only. */
	public function scalar_conversion_headers(int \$conversion /** uint16 */): array /** list<string> */
	{
		return match (\$conversion) {
$cases
			default => [],
		};
	}
PHP_CODE;
    }
    return <<<PHP_CODE
	/** Select the conversion once; the caller marks written destinations or explicit cast boundaries. */
	public function prepare_scalar_conversion(ast_expression \$value, conversion_context \$context): int /** uint16 */
	{
$prepare
		return parent::prepare_scalar_conversion(\$value, \$context);
	}

	/** Render an approved boundary without repeating source-type resolution or changing facts. */
	public function lower_scalar_conversion(int \$conversion /** uint16 */, string \$value, ?ast_expression \$syntax = null, ?type_definition \$source_type = null): string
	{
		return match (\$conversion) {
$lower
			default => parent::lower_scalar_conversion(\$conversion, \$value, \$syntax, \$source_type),
		};
	}

	/** Preserve unknown values; convert known payloads using the selected operation only. */
	public function convert_scalar_constant(int \$conversion /** uint16 */, int|float|bool|null \$value /** int64|float64|bool|null */): int|float|bool|null|constant_evaluation /** int64|float64|bool|null|constant_evaluation */
	{
		return match (\$conversion) {
$constants
			default => parent::convert_scalar_constant(\$conversion, \$value),
		};
	}
$headers
PHP_CODE;
}

/** Emit direct condition handlers; missing rows retain base rejection without per-expression metadata lookup. */
function compilerConditions(array $conditions, string $identity): string {
    if (!isset($conditions[$identity])) return '';
    $condition = $conditions[$identity];
    $storage = $condition['storage'];
    $expression = match ($condition['strategy']) {
        'identity' => '$value',
        'nonzero_integer' => "\$negated ? '(' . \$value . ' != 0)' : \$value . ' != 0'",
        'nonzero_float' => "\$negated ? '(' . \$value . ' != 0.0)' : \$value . ' != 0.0'",
        'nonempty_except_zero' => var_export("([](const " . $storage['symbol'] . "& condition_value) -> bool { return condition_value.size() != 0 && (condition_value.size() != 1 || condition_value.data()[0] != '0'); })(", true) . " . \$value . ')'",
    };
    $headers = $storage === null ? '[]' : '[' . var_export($storage['header'], true) . ']';
    return "
\tpublic function supports_scalar_condition(): bool { return true; }

\t/** Generated condition admission; never an explicit bool conversion. */
\tpublic function prepare_condition(ast_expression \$value): void
\t{
\t\tif (\$value->value_type() !== \$this) {
\t\t\tthrow new \\LogicException('Condition type does not match its prepared operand');
\t\t}
\t}

\tpublic function lower_cpp_condition(string \$value, bool \$negated = false): string
\t{
\t\treturn $expression;
\t}

\t/** @return string[] */
\tpublic function condition_headers(): array /** list<string> */
\t{
\t\treturn $headers;
\t}
";
}

/** Generate direct binary handlers from the validated execution plan. */
function compilerBinary(mixed $compiler, array $definitions, string $identity, array $safety, array $binary, array $types): string {
    $operations = $binary[$identity] ?? [];
    if ($operations === []) return '';
    $results = '';
    $rightCases = '';
    $flavorCases = '';
    $compoundEnabled = isset($compiler->arithmetic->$identity) && $compiler->arithmetic->$identity->compound;
    $compoundOperators = [];
    $nativeCompoundCases = [];
    $exactResults = [];
    $cases = '';
    $headers = [];
    $reorderable = [];
    $directCases = [];
    $valueHelpers = [];
    $stringValueOps = [];
    foreach ($operations as $op => $operation) {
        $result = 'type_' . $definitions[$operation['result']]['class'];
        if (($operation['compound'] ?? $compoundEnabled) && $operation['result'] === $identity) {
            $compoundOperators[] = "binary_operator::$op";
            if ($operation['lowering'] === 'native' && $definitions[$identity]['strategy'] === 'integer'
                && $types[$identity]['signed'] && $types[$identity]['bits'] === 64) {
                $nativeCompoundCases[] = "binary_operator::$op => " . var_export($operation['spelling'] . '=', true);
            }
        }
        if (isset($operation['flavors'])) {
            $allowed = implode(', ', array_map(static fn(string $flavor): string => 'flavor::' . $flavor, $operation['flavors']));
            $flavorCases .= "\t\t\tbinary_operator::$op => match (\$site->source()->flavor) { $allowed => true, default => false },\n";
        }
        $rightTypes = $operation['counts'] ?? $operation['identity_peers'] ?? null;
        if ($rightTypes !== null) {
            $admission = implode(' || ', array_map(static fn(string $name): string => '$right === type_' . $definitions[$name]['class'] . '::instance()', $rightTypes));
            $rightCases .= "\t\tif (\$operator === binary_operator::$op) {\n\t\t\treturn (" . ($admission === '' ? 'false' : $admission) . ") ? $result::instance() : null;\n\t\t}\n";
        } else {
            $results .= "\t\t\tbinary_operator::$op => $result::instance(),\n";
        }
        if (compilerStrategyReorderable($safety, isset($operation['identity_peers']) ? 'canonical_identity' : $operation['lowering'])) {
            // Native self-comparisons and literal XOR such as 2LL ^ 2LL can trigger Clang warnings.
            $guard = in_array($operation['spelling'], ['==', '!=', '<', '<=', '>', '>=', '^'], true) ? '!$same_spelling' : 'true';
            $reorderable[$guard][] = "binary_operator::$op";
        }
        if (in_array($operation['lowering'], ['zero_checked_divide', 'zero_checked_remainder', 'checked_shift', 'checked_power', 'checked_floor_divide', 'checked_floor_remainder'], true)) {
            // These validated scalar strategies call a runtime helper with two value parameters.
            $valueHelpers[] = "binary_operator::$op";
        } elseif ($definitions[$identity]['strategy'] === 'integer') {
            if ($op === 'add' && $operation['lowering'] === 'native' && compilerStrategyReorderable($safety, 'native')) {
                $directCases[] = "binary_operator::$op => self::independent_local_call(\$expr->left, \$expr->right)"
                    . "\n\t\t\t\t|| self::independent_local_call(\$expr->right, \$expr->left),";
            } elseif ($operation['lowering'] === 'native_three_way') {
                // This formula reads both operands twice. Only repeat plain, distinct storage reads.
                $directCases[] = "binary_operator::$op => \$operands_safe && !\$same_spelling && \$expr->right->type_def() === \$this"
                    . "\n\t\t\t\t&& \$expr->left instanceof ast_name_expression && \$expr->left->declaration() instanceof ast_local_binding"
                    . "\n\t\t\t\t&& \$expr->right instanceof ast_name_expression && \$expr->right->declaration() instanceof ast_local_binding,";
            } elseif ($op === 'not_identical' && isset($operation['identity_peers'])) {
                $directCases[] = "binary_operator::$op => \$operands_safe && \$expr->right->value_type() === \$this && !\$same_spelling,";
            }
        } elseif ($definitions[$identity]['strategy'] === 'string'
            && in_array($operation['lowering'], ['runtime_concat', 'runtime_compare'], true)) {
            $stringValueOps[] = "binary_operator::$op";
        }
        if ($operation['lowering'] === 'native') {
            $expression = "'(' . \$left . ' " . $operation['spelling'] . " ' . \$right . ')'";
            // Narrow unsigned multiplication must not overflow C++'s promoted signed int.
            if ($op === 'multiply' && $definitions[$identity]['strategy'] === 'integer'
                && !$types[$identity]['signed'] && $types[$identity]['bits'] < 32) {
                $expression = "'(static_cast<__UINT64_TYPE__>(' . \$left . ') * ' . \$right . ')'";
            }
            if ($definitions[$identity]['strategy'] === 'integer' && $types[$identity]['bits'] < 64 && $operation['result'] === $identity) {
                $expression = "'static_cast<" . $definitions[$identity]['cpp'] . ">(' . " . $expression . " . ')'";
            }
        } elseif (in_array($operation['lowering'], ['native_three_way', 'string_three_way'], true)) {
            $resultCpp = $definitions[$operation['result']]['cpp'];
            $exactResults[] = "binary_operator::$op => $result::instance()";
            $extract = '';
            if ($operation['lowering'] === 'string_three_way') {
                $extract = $operation['result_binding']['extract'] === null ? '' : '.' . $operation['result_binding']['extract'] . '()';
                $headers[$operation['storage']['header']][] = "binary_operator::$op";
                $headers[$operation['result_binding']['header']][] = "binary_operator::$op";
            }
            // The native integer comparison difference is int and can only be -1, 0 or 1.
            $cast = $operation['lowering'] === 'native_three_way'
                ? "(\$typed_initializer ? '' : 'static_cast<$resultCpp>')" : "'static_cast<$resultCpp>'";
            $expression = "$cast . '(((' . \$left . ' > ' . \$right . ')$extract) - ((' . \$left . ' < ' . \$right . ')$extract))'";
        } elseif ($operation['lowering'] === 'runtime_concat') {
            $expression = "'(' . \$left . ' + ' . \$right . ')'";
            $headers[$operation['storage']['header']][] = "binary_operator::$op";
        } elseif ($operation['lowering'] === 'runtime_compare') {
            $extract = $operation['result_binding']['extract'] === null ? '' : '.' . $operation['result_binding']['extract'] . '()';
            $expression = "'((' . \$left . ' " . $operation['spelling'] . " ' . \$right . ')$extract)'";
            $headers[$operation['storage']['header']][] = "binary_operator::$op";
            $headers[$operation['result_binding']['header']][] = "binary_operator::$op";
        } else {
            $symbol = $operation['binding']['symbol'];
            $expression = "'{$symbol}(' . \$left . ', ' . \$right . ')'";
            $headers[$operation['binding']['header']][] = "binary_operator::$op";
            // compilerArithmeticBinding validates the helper's declared result against the operation.
            $exactResults[] = "binary_operator::$op => $result::instance()";
        }
        if (isset($operation['identity_peers'])) {
            $different = $operation['different_result'] ? 'true' : 'false';
            $expression = "(\$right_type === \$this) ? $expression : "
                . "'(static_cast<void>(' . \$left . '), static_cast<void>(' . \$right . '), $different)'";
        }
        $cases .= "\t\t\tbinary_operator::$op => $expression,\n";
    }
    $compound = $compoundOperators === [] ? 'false' : "match (\$operator) { " . implode(', ', $compoundOperators) . " => true, default => false }";
    $syntaxMethod = $flavorCases === '' ? '' : "\n\t/** Flavor spelling admission generated from operation metadata. */\n\tpublic function binary_syntax_allowed(binary_operator \$operator, ast_node \$site): bool\n\t{\n\t\treturn match (\$operator) {\n$flavorCases\t\t\tdefault => true,\n\t\t};\n\t}\n";
    $safeCases = '';
    foreach ($reorderable as $guard => $operators) {
        $safeCases .= "\t\t\t" . implode(', ', $operators) . " => $guard,\n";
    }
    $directMethod = '';
    if ($stringValueOps !== []) {
        $operators = implode(', ', $stringValueOps);
        $directCases[] = "{$operators} => \$expr->left->type_def() === \$this && \$expr->right->type_def() === \$this"
            . "\n\t\t\t\t&& self::stable_cpp_operand(\$expr->left) && self::stable_cpp_operand(\$expr->right),";
    }
    if ($directCases !== []) {
        $casesText = implode("\n\t\t\t", $directCases);
        $directMethod .= "
\tpublic function cpp_direct_binary(ast_binary_expression \$expr, bool \$operands_safe, bool \$same_spelling): bool
\t{
\t\treturn match (\$expr->operator) {
\t\t\t$casesText
\t\t\tdefault => false,
\t\t};
\t}
";
    }
    if ($valueHelpers !== []) {
        $operators = implode(', ', $valueHelpers);
        $directMethod .= "
\tpublic function cpp_value_helper(binary_operator \$operator): bool
\t{
\t\treturn match (\$operator) { $operators => true, default => false };
\t}
";
    }
    $headerMethods = '';
    if ($headers !== []) {
        $headerCases = '';
        $perOperator = [];
        foreach ($headers as $header => $operators) {
            foreach ($operators as $operator) $perOperator[$operator][$header] = true;
        }
        $headerGroups = [];
        foreach ($perOperator as $operator => $required) {
            $list = implode(', ', array_map(static fn(string $header): string => var_export($header, true), array_keys($required)));
            $headerGroups[$list][] = $operator;
        }
        foreach ($headerGroups as $list => $operators) {
            $headerCases .= "\t\t\t" . implode(', ', $operators) . " => [$list],\n";
        }
        $headerMethods = "
\t/** @return string[] */
\tpublic function binary_headers(binary_operator \$operator): array /** list<string> */
\t{
\t\treturn match (\$operator) {
$headerCases\t\t\tdefault => [],
\t\t};
\t}
";
    }
    $nativeCompoundMethod = $nativeCompoundCases === [] ? '' : "\n\tpublic function cpp_compound_operator(binary_operator \$operator): ?string\n\t{\n\t\treturn match (\$operator) { " . implode(', ', $nativeCompoundCases) . ", default => null };\n\t}\n";
    $exactMethod = '';
    if ($exactResults !== []) {
        $arms = implode(",\n\t\t\t", $exactResults);
        $exactMethod = "
\tpublic function cpp_exact_binary_result(binary_operator \$operator): ?type_definition
\t{
\t\treturn match (\$operator) {
\t\t\t$arms,
\t\t\tdefault => null,
\t\t};
\t}
";
    }
    return "$syntaxMethod$nativeCompoundMethod$exactMethod
\t/** Compound syntax admission is separate from ordinary arithmetic capability. */
\tpublic function supports_compound(binary_operator \$operator): bool
\t{
\t\treturn $compound;
\t}

\t/** Generated same-type admission; result identity follows the selected metadata operation. */
\tpublic function binary_result(binary_operator \$operator, type_definition \$right): ?type_definition
\t{
$rightCases\t\tif (\$right !== \$this) {
\t\t\treturn null;
\t\t}
\t\treturn match (\$operator) {
$results\t\t\tdefault => null,
\t\t};
\t}

\t/** Lower an approved operator on already sequenced operand snapshots. */
\tpublic function lower_cpp_binary(binary_operator \$operator, string \$left, string \$right, type_definition \$right_type, bool \$typed_initializer = false): string
\t{
\t\treturn match (\$operator) {
$cases\t\t\tdefault => throw new \\LogicException('Binary operation was not approved'),
\t\t};
\t}

\t/** Only admitted primitive native operations permit reordering of independently safe operands. */
\tpublic function cpp_binary_reorderable(binary_operator \$operator, bool \$same_spelling = false): bool
\t{
\t\treturn match (\$operator) {
$safeCases\t\t\tdefault => false,
\t\t};
\t}
$directMethod$headerMethods";
}

/** Emit direct update handlers from the validated operator list for one type. */
function compilerUpdates(array $selected): string {
    if ($selected === []) return '';
    $admission = [];
    $lowering = '';
    foreach ($selected as $operator) {
        $admission[] = 'update_operator::' . $operator;
        $spelling = $operator === 'increment' ? '++' : '--';
        $lowering .= "\t\t\tupdate_operator::$operator => '$spelling',\n";
    }
    $cases = implode(', ', $admission);
    return "
\t/** Metadata-admitted discarded-value updates; no source result is exposed by this slice. */
\tpublic function supports_update(update_operator \$operator): bool
\t{
\t\treturn match (\$operator) {
\t\t\t$cases => true,
\t\t\tdefault => false,
\t\t};
\t}

\tpublic function lower_cpp_update(update_operator \$operator, string \$target, bool \$postfix): string
\t{
\t\t\$spelling = match (\$operator) {
$lowering\t\t\tdefault => throw new \\LogicException('Update operation was not approved'),
\t\t};
\t\treturn \$postfix ? \$target . \$spelling : \$spelling . \$target;
\t}
";
}

/** Emit direct unary handlers from the validated operator list for one type. */
function compilerUnary(array $selected, string $identity, array $safety, array $definitions, array $types): string {
    if ($selected === []) return '';
    $admission = [];
    $lowering = '';
    foreach ($selected as $operator) {
        $admission[] = 'unary_operator::' . $operator;
        $sign = match ($operator) { 'positive' => '+', 'negative' => '-', 'bit_not' => '~' };
        // C++ promotes narrow operands; restore their identity before any enclosing expression uses the result.
        $restore = isset($types[$identity]) && $types[$identity]['bits'] < 64;
        $expression = $restore ? 'static_cast<' . $definitions[$identity]['cpp'] . '>(' . $sign . '(' : '(' . $sign . '(';
        $lowering .= "\t\t\tunary_operator::$operator => '$expression' . \$operand . '))',\n";
    }
    $cases = implode(', ', $admission);
    $safeCases = compilerStrategyReorderable($safety, 'native') ? "\t\t\t$cases => true,\n" : '';
    return "
\t/** Generated prefix admission; operand preparation is owned by the single source traversal. */
\tpublic function prepare_unary(ast_unary_expression \$value): void
\t{
\t\tif ((\$value->operand->value_type() !== \$this) || !match (\$value->operator) {
\t\t\t$cases => true,
\t\t\tdefault => false,
\t\t}) {
\t\t\tparent::prepare_unary(\$value);
\t\t\treturn;
\t\t}
\t\t\$value->settle_unary(\$this);
\t}

\tpublic function lower_cpp_unary(unary_operator \$operator, string \$operand): string
\t{
\t\treturn match (\$operator) {
$lowering\t\t\tdefault => throw new \\LogicException('Unary operation was not approved'),
\t\t};
\t}

\t/** Admitted native prefix operations add no effects; operand safety remains separate. */
\tpublic function cpp_unary_reorderable(unary_operator \$operator): bool
\t{
\t\treturn match (\$operator) {
$safeCases\t\t\tdefault => false,
\t\t};
\t}
";
}

/** Emit promotion handlers from the validated rows belonging to one left-hand type. */
function compilerPromotions(array $rows, array $definitions): string {
    $cases = '';
    $compounds = '';
    foreach ($rows as $row) {
        $rightClass = 'type_' . $definitions[$row['right']]['class'];
        $executionClass = 'type_' . $definitions[$row['execution']]['class'];
        $resultClass = 'type_' . $definitions[$row['result']]['class'];
        $operatorCases = implode(', ', array_map(static fn(string $op): string => 'binary_operator::' . $op, $row['operators']));
        $compoundOperators = $row['compound_operators'];
        $lc = $row['left_conversion'];
        $rc = $row['right_conversion'];
        $writeBack = $row['write_back'];
        if ($compoundOperators !== []) {
            $compoundCases = implode(', ', array_map(static fn(string $op): string => 'binary_operator::' . $op, $compoundOperators));
            $compounds .= "\t\tif (\$this->supports_compound(\$value->operator) && (\$right === $rightClass::instance()) && match (\$value->operator) {\n"
                . "\t\t\t$compoundCases => true,\n\t\t\tdefault => false,\n\t\t}) {\n\t\t\t\$value->settle_compound($executionClass::instance(), value_conversion::$lc, value_conversion::$rc, value_conversion::$writeBack);\n\t\t\treturn;\n\t\t}\n";
        }
        $cases .= "\t\tif ((\$value->left->value_type() === \$this) && (\$value->right->value_type() === $rightClass::instance()) && match (\$value->operator) {\n"
            . "\t\t\t$operatorCases => true,\n\t\t\tdefault => false,\n\t\t}) {\n"
            . "\t\t\t\$value->settle_binary($resultClass::instance(), $executionClass::instance(), value_conversion::$lc, value_conversion::$rc);\n\t\t\treturn;\n\t\t}\n";
    }
    $compoundMethod = $compounds === '' ? '' : "\n\t/** Attach generated operand conversions, execution identity and checked result write-back. */\n"
        . "\tpublic function prepare_compound(ast_assignment_statement \$value, type_definition \$right): void\n\t{\n$compounds\t\tparent::prepare_compound(\$value, \$right);\n\t}\n";
    if ($cases === '') return $compoundMethod;
    return $compoundMethod . "\n\t/** Metadata-authorized promotion; operands are already prepared and conversion behavior is reused. */\n"
        . "\tpublic function prepare_binary(ast_binary_expression \$value): void\n\t{\n$cases\t\tparent::prepare_binary(\$value);\n\t}\n";
}
