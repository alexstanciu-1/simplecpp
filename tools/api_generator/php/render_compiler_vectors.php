<?php
declare(strict_types=1);
namespace Scpp\Api;

/** Publish provider guarantees directly on the generated type. Consumers only inspect the interface. */
function compilerAccessInterfaces(mixed $provider, bool $indexed = true, bool $runtimeKind = false): string {
    $contracts = names($provider->access_contracts ?? []);
    if (in_array('runtime_kind', $contracts, true)) {
        requireThat($runtimeKind && !in_array('container', $contracts, true),
            'Runtime kind observation requires a tag binding and no static container guarantee');
    }
    $readModes = array_intersect($contracts, ['python_index_read', 'python_index_read_snapshot']);
    requireThat(count($readModes) <= 1 && (!$readModes || $indexed), 'Python index access requires one bound read mode');
    $interfaces = '';
    foreach (generalTransforms($provider) as $form) $interfaces .= ', ' . $form . '_source';
    foreach ($contracts as $contract) {
        $interfaces .= match ($contract) {
            'container' => ', container_type',
            'runtime_kind' => ', runtime_kind_type',
            'readonly_slot' => ', readonly_collection_slot',
            'python_index_read' => ', python_index_read_type',
            'python_index_read_snapshot' => ', python_snapshot_index_read_type',
            default => throw new \RuntimeException("Unknown collection access contract: $contract"),
        };
    }
    return $interfaces;
}

/** The checked sequence read has one preparation and member-call shape for strings and vectors. */
function compilerIndexRead(string $receiver, string $count, string $element, string $method): string {
    return strtr(readFile(__DIR__ . '/../templates/collections/index_read.php.tpl'), [
        '@receiver@' => $receiver, '@count@' => $count, '@element@' => $element, '@index@' => $method,
    ]);
}

/** Source-visible members from the validated collection plan. */
function sourceCollectionMember(string $name): bool {
    return in_array($name, ['clear', 'remove', 'has', 'reserve', 'capacity', 'resize', 'compact'], true);
}

/** Generate one method contract for both prepublished and application concrete collection identities. */
function compilerCollectionMethods(array $collection, array $definitions, ?string $keyExpression = null, bool $applicationVector = false): string {
    $bindings = [];
    foreach ($collection['methods'] as $method) {
        $name = $method['name'];
        if (!sourceCollectionMember($name)) continue;
        $key = $collection['count_type'] ?? $collection['key'];
        $argumentTypes = match ($name) {
            'clear', 'capacity' => [],
            'resize' => [$key, $collection['element']],
            default => [$key],
        };
        $writtenTypes = array_map(static fn($type) => str_replace(['const ', '&'], '', $type), $method['arguments']);
        $result = match ($name) {
            'capacity' => $key,
            'has', 'remove' => 'bool_t',
            default => 'void',
        };
        $mutates = ($method['receiver'] ?? null) === 'mutable_reference' || ($method['mutates'] ?? false);
        requireThat($writtenTypes === $argumentTypes && $method['result'] === $result && $mutates === !in_array($name, ['has', 'capacity'], true),
            "Compiler $name requires its canonical method signature");
        if ($name === 'remove' && isset($collection['element'])) {
            requireThat($method['adaptation'] === 'erase_shift_missing_false', 'Vector removal must shift and return false on absence');
        }
        $referenceMask = $mutates ? 1 : 0;
        $constMask = $mutates ? 0 : 1;
        foreach ($method['arguments'] as $index => $argument) {
            if (str_starts_with($argument, 'const ') && str_ends_with($argument, '&')) $constMask |= 1 << ($index + 1);
        }
        $parameters = ['$this'];
        foreach ($argumentTypes as $index => $type) {
            requireThat(isset($definitions[$type]), 'Collection method argument type is unavailable');
            if ($name === 'resize' && $index === 1) {
                $parameters[] = '$this->element_type';
                if ($applicationVector) $constMask |= 1 << ($index + 1);
            } else {
                $parameters[] = $keyExpression ?? 'type_' . $definitions[$type]['class'] . '::instance()';
            }
        }
        $arity = ($method['adaptation'] ?? '') === 'optional_capacity' ? ', required_parameters: 1' : '';
        $class = $result === 'void' ? 'void' : $definitions[$result]['class'];
        $bindings[] = "\t\t\t'$name' => \$this->runtime_methods[\$name] ??= new runtime_function('$name', [" . implode(', ', $parameters)
            . "], type_$class::instance(), '$name', \$this->cpp_headers()[0], reference_mask: $referenceMask, const_reference_mask: $constMask$arity, receiver_method: true, retains_receiver: false),";
    }
    return strtr(readFile(__DIR__ . '/../templates/collections/compiler_methods.php.tpl'), ['@bindings@' => implode("\n", $bindings)]);
}

/** Emit the static factory's exact native signature on its canonical result type. */
function compilerFillBinding(object $factory, string $countClass, bool $borrow): string {
    return strtr(readFile(__DIR__ . '/../templates/collections/compiler_fill_factory.php.tpl'), [
        '@source@' => var_export($factory->source, true), '@member@' => $factory->member,
        '@count@' => $countClass, '@mask@' => $borrow ? '2' : '0',
    ]);
}

/** Emit concrete identities and bounded member handlers from validated runtime specializations. */
function compilerVectorTypes(mixed $metadata, ?array $runtime, array $definitions): array {
    $code = "<?php\ndeclare(strict_types=1);\nnamespace scpp\\compiler;\n\n// Generated from validated runtime publication. Do not edit.\n";
    $cases = [];
    $traits = [];
    $application = false;
    $fillFactories = [];
    $access = compilerAccessInterfaces($metadata->vector ?? null);
    $family = isset($metadata->vector) ? textValue(at($metadata->vector, 'source_type')) : null;
    foreach (array_keys($runtime['regex_shapes'] ?? []) as $type) $definitions[$type] = ['class' => $type];
    foreach ($runtime['regex_modes'] ?? [] as $mode) $definitions[$mode['cell']] = ['class' => $mode['cell']];
    foreach ([...($runtime['vectors'] ?? []), ...($runtime['regex_vectors'] ?? []), ...($runtime['regex_mode_vectors'] ?? [])] as $vector) {
        $element = $vector['element'];
        if (!isset($definitions[$element]) && isset($runtime['regex_capture_publication'])
            && in_array($element, ['vector_string', 'hash_string_string'], true)) $definitions[$element] = ['class' => $element];
        if (!isset($definitions[$element])) continue;
        $export = typeExport($runtime['submodules'], $vector['name']);
        $header = providerHeader($runtime['submodules'], $export['provider']);
        $class = 'type_' . $vector['name'];
        $elementClass = 'type_' . $definitions[$element]['class'];
        $append = null;
        $index = null;
        $set = null;
        foreach ($vector['methods'] as $method) {
            if ($method['name'] === 'append' && $method['arguments'] === [$element]) $append = $method;
            if ($method['name'] === 'set' && $method['arguments'] === [$vector['count_type'], $element]) $set = $method;
            if ($method['name'] === 'at' && $method['arguments'] === [$vector['count_type']]) $index = $method;
        }
        requireThat($append !== null && $append['receiver'] === 'mutable_reference' && $append['result'] === 'void', 'Compiler vector requires canonical append binding');
        requireThat($set !== null && $set['receiver'] === 'mutable_reference' && $set['result'] === 'void'
            && $set['adaptation'] === 'checked_index_move_value', 'Compiler vector requires canonical set binding');
        requireThat($index !== null && $index['receiver'] === 'const_reference' && $index['result'] === $element
            && $index['result_category'] === 'value', 'Compiler vector requires value-copy index binding');
        requireThat(isset($definitions[$vector['count_type']]), 'Compiler vector count type is unavailable');
        $countClass = 'type_' . $definitions[$vector['count_type']]['class'];
        $initialization = "\tpublic function list_initialization(): ?initialization_transport { return null; }\n";
        $initializationSetup = '';
        $transfer = $vector['transfer_assignment'] ? ', private_value_transfer_type' : '';
        $sequence = $vector['initialization'] !== null ? ', sequence_initializer_type' : '';
        if (($vector['initialization']['bulk'] ?? null) !== null) {
            $init = $vector['initialization']['bulk'];
            $input = var_export($export['symbol'] . '::' . $init['input'], true);
            $factory = var_export($export['symbol'] . '::' . ($init['consume'] ?? $init['read_only']), true);
            $initialization = "\tprivate readonly initialization_transport \$initialization;\n"
                . "\tpublic function list_initialization(): ?initialization_transport\n\t{\n"
                . "\t\treturn \$this->initialization;\n\t}\n";
            $initializationSetup = "\t\t\$this->initialization = new initialization_transport($elementClass::instance()->cpp_type(), $input, $factory, {$init['inline_limit']});\n";
        }
        $writable = compilerWritableIteration(vectorConcreteRecipe($vector), $vector['traversal'], $traits, $code);
        $writableInterface = $writable === '' ? '' : ', writable_iteration_type';
        $fillFactory = empty($vector['capacity_helpers']) ? null : concreteRecipe(vectorConcreteRecipe($vector))->fill_factory;
        if ($fillFactory !== null) $fillFactories[$fillFactory->source] = $family;
        $fillInterface = $fillFactory === null ? '' : ', filled_collection_type';
        $provider = concreteRecipe(vectorConcreteRecipe($vector));
        $indexedTypes = textValue(at($provider, 'indexed_entry_types'));
        $bindings = compilerCollectionBuilders($provider) + [
            '@declarations@' => compilerCollectionDeclarations($provider),
            '@fill_binding@' => $fillFactory === null ? '' : compilerFillBinding($fillFactory, $countClass, collectionFillBorrows($vector)),
            '@writable_iteration@' => $writable === '' ? '' : "\tuse $writable;\n",
            '@entry_presence@' => compilerEntryPresence(vectorConcreteRecipe($vector), null, $traits, $code),
            '@checked_slot_binding@' => compilerCheckedSlot(vectorConcreteRecipe($vector)),
            '@initialization@' => $initialization,
            '@iteration@' => compilerTraversalMethods($vector['traversal'], $elementClass . '::instance()', $countClass . '::instance()', indexedTypes: $indexedTypes),
            '@methods@' => compilerCollectionMethods($vector, $definitions),
            '@cardinality@' => compilerCardinalityMethods($vector['methods'], $vector['count_type'], $definitions),
            '@class@' => $class, '@name@' => var_export($vector['source_type'], true),
            '@element@' => $elementClass . '::instance()', '@cpp@' => var_export($export['symbol'], true),
            '@header@' => var_export($header, true), '@count@' => $countClass,
            '@append@' => $append['name'], '@index@' => $index['name'], '@set@' => $set['name'],
            '@interfaces@' => ' implements nested_index_storage_type, conditional_index_store_type, iterable_type' . $fillInterface . $writableInterface . $access . $sequence . $transfer,
            '@identity@' => "\tprivate static ?self \$instance = null;\n\n\tprivate function __construct()\n\t{\n\t\tparent::__construct(" . var_export($vector['source_type'], true) . ", $elementClass::instance());\n$initializationSetup\t}\n",
            '@factory@' => "\tpublic static function instance(): self\n\t{\n\t\treturn self::\$instance ??= new self();\n\t}",
        ];
        $template = readFile(__DIR__ . '/../templates/collections/vector_type.php.tpl');
        $code .= strtr($template, $bindings);
        if (!$application) {
            $application = true;
            $identity = "\tuse concrete_async_task;\n\n\tprivate readonly string \$native_name;\n\n\tpublic function __construct(type_definition \$element)\n\t{\n\t\tparent::__construct('vector<' . \$element->name . '>', \$element);\n\t\t\$this->native_name = 'scpp_vector_' . hash('sha256', \$element->concrete_identity());\n\t}\n";
            $destination = concreteRecipe(vectorConcreteRecipe($vector))->destination_append ?? null;
            $destinationInterface = $destination === null ? '' : ', destination_append_type';
            $destinationMethod = '';
            if ($destination !== null) {
                $slots = ['helper', 'vector', 'element', 'parameters', 'inputs'];
                $placeholders = array_combine($slots, array_map(static fn(string $s): string => '@' . $s . '@', $slots));
                $body = template(textValue($destination), $placeholders);
                $destinationMethod = "\n\tpublic function destination_append_definition(array \$values): string\n\t{\n"
                    . "\t\treturn strtr(" . var_export($body, true) . ", \$values);\n\t}\n";
            }
            $recipe = ['mt_segments' => !empty($runtime['mt_segments']) ? '1' : '', 'capacity_helpers' => !empty($vector['capacity_helpers']) ? '1' : '', 'qual' => 'const ', 'ref' => '&', 'enable_reserve' => $vector['enable_reserve'], 'count' => 'scpp::' . $vector['count_type'], 'limit_method' => $vector['traversal']['limit_method'], 'next_method' => $vector['traversal']['next_method']];
            $applicationMethods = compilerCollectionMethods($vector, $definitions, applicationVector: true);
            $code .= strtr($template, array_replace($bindings, [
                '@fill_binding@' => $fillFactory === null ? '' : compilerFillBinding($fillFactory, $countClass, true),
                '@methods@' => $applicationMethods,
                '@initialization@' => "\tpublic function list_initialization(): ?initialization_transport { return null; }\n", '@class@' => 'application_vector_type', '@interfaces@' => ' implements nested_index_storage_type, conditional_index_store_type, application_collection_type, iterable_type' . $fillInterface . $writableInterface . $access . $sequence . $transfer . $destinationInterface, '@identity@' => $identity,
                '@iteration@' => compilerTraversalMethods($vector['traversal'], '$this->element_type', $countClass . '::instance()', constructed: true, indexedTypes: $indexedTypes), '@element@' => '$this->element_type', '@cpp@' => '$this->native_name',
                '@header@' => var_export(providerHeader($runtime['submodules'], typeExport($runtime['submodules'], $vector['count_type'])['provider']), true)
                    . ($vector['enable_reserve'] === '1' ? ", 'scpp/value_error.hpp'" : ''),
                '@factory@' => $destinationMethod . "\tpublic static function concrete_recipe(): string\n\t{\n\t\treturn " . var_export(vectorConcreteRecipe($vector), true) . ";\n\t}\n\n\t/** Recipe inputs from the same validated vector plan as runtime specializations. @return string[] */\n\tpublic function recipe_arguments(): array\n\t{\n\t\treturn " . var_export($recipe, true) . " + ['buffer' => '::scpp::detail', 'access_name' => 'access_' . \$this->native_name, 'access' => 'detail::access_' . \$this->native_name];\n\t}\n\t/** @return type_definition[] */\n\tpublic function concrete_types(): array { return ['e' => \$this->element_type]; }",
            ]));
        }
        $cases[] = "\t\t\t$elementClass::instance() => $class::instance(),";
    }
    $applicationCase = $application ? "\t\tif (\$family === " . var_export($family, true) . " && \$second === null) return new application_vector_type(\$element);\n" : '';
    return [$code, implode("\n", $cases), $family, $applicationCase, $fillFactories];
}

/** Bind the common traversal protocol from a validated runtime publication row. */
function compilerTraversalMethods(array $plan, string $value, string $key, bool $boxed = false, bool $constructed = false, ?string $indexedTypes = null): string {
    requireThat($plan['mode'] === 'borrow_live_wrapper_physical' && $plan['visibility'] === 'compiler_internal', 'Unsupported compiler traversal protocol');
    $state = $boxed ? 'scpp::int64 iteration_key_index{}; scpp::string iteration_key_text{};' : '';
    $arguments = $boxed ? "\$prefix . 'iteration_key_index, ' . \$prefix . 'iteration_key_text, ' . \$value" : "\$key . ', ' . \$value";
    $publish = $boxed ? " . ' && ((' . \$key . ' = ' . \$prefix . 'iteration_key_index < 0 ? scpp::mixed::from_string(' . \$prefix . 'iteration_key_text) : scpp::mixed::from_integer(' . \$prefix . 'iteration_key_index)), true)'" : '';
    $output = $constructed ? "\n\tpublic function iteration_output_type(): ?string { return \$this->cpp_type() . '::iteration_output'; }\n" : '';
    $keys = isset($plan['next_key_method']) ? strtr(readFile(__DIR__ . '/../templates/collections/compiler_key_iteration.php.tpl'), [
        '@next_key@' => $plan['next_key_method'], '@current_value@' => $plan['current_value_method'],
    ]) : '';
    // A transform forwards the classified native key to the result without boxing it for a source variable.
    if ($boxed) $output .= str_replace('@next@', $plan['next_method'], <<<'PHP'

	public function lower_cpp_iteration_next_classified(string $receiver, string $value, string $key): string
	{
		return '(' . $receiver . ').@next@(iteration_position, iteration_limit, ' . $key . '.index, ' . $key . '.text, ' . $value . ')';
	}

PHP);
    if ($indexedTypes !== null) {
        requireThat($indexedTypes === 'traversal_value_exact_key', 'Unknown indexed entry type binding');
        $output .= strtr(readFile(__DIR__ . '/../templates/collections/compiler_indexed_entry_types.php.tpl'), [
            '@value@' => $value, '@key@' => $key,
        ]);
    }
    return $output . $keys . strtr(readFile(__DIR__ . '/../templates/collections/compiler_iteration.php.tpl'), [
        '@entry_key@' => $boxed ? 'key.index, key.text' : 'key', '@value@' => $value, '@key@' => $key, '@state@' => var_export($state, true),
        '@limit@' => $plan['limit_method'], '@next@' => $plan['next_method'],
        '@arguments@' => $arguments, '@publish@' => $publish,
    ]);
}
