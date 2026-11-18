<?php
declare(strict_types=1);
namespace Scpp\Api;

/** The checked sequence read has one preparation and member-call shape for strings and vectors. */
function compilerIndexRead(string $receiver, string $count, string $element, string $method): string {
    return strtr(readFile(__DIR__ . '/../templates/collections/index_read.php.tpl'), [
        '@receiver@' => $receiver, '@count@' => $count, '@element@' => $element, '@index@' => $method,
    ]);
}

/** Generate one method contract for both prepublished and application concrete collection identities. */
function compilerCollectionMethods(array $collection, array $definitions): string {
    $bindings = [];
    foreach ($collection['methods'] as $method) {
        $name = $method['name'];
        if (!in_array($name, ['clear', 'remove', 'has'], true)) continue;
        $argumentTypes = $name === 'clear' ? [] : [$collection['count_type'] ?? $collection['key']];
        $writtenTypes = array_map(static fn($type) => str_replace(['const ', '&'], '', $type), $method['arguments']);
        $result = $name === 'clear' ? 'void' : 'bool_t';
        $mutates = ($method['receiver'] ?? null) === 'mutable_reference' || ($method['mutates'] ?? false);
        requireThat($writtenTypes === $argumentTypes && $method['result'] === $result && $mutates === ($name !== 'has'),
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
        foreach ($argumentTypes as $type) {
            requireThat(isset($definitions[$type]), 'Collection method argument type is unavailable');
            $parameters[] = 'type_' . $definitions[$type]['class'] . '::instance()';
        }
        $class = $result === 'void' ? 'void' : $definitions[$result]['class'];
        $bindings[] = "\t\t\t'$name' => \$this->runtime_methods[\$name] ??= new runtime_function('$name', [" . implode(', ', $parameters)
            . "], type_$class::instance(), '$name', \$this->cpp_headers()[0], reference_mask: $referenceMask, const_reference_mask: $constMask, receiver_method: true),";
    }
    return strtr(readFile(__DIR__ . '/../templates/collections/compiler_methods.php.tpl'), ['@bindings@' => implode("\n", $bindings)]);
}

/** Emit concrete identities and bounded member handlers from validated runtime specializations. */
function compilerVectorTypes(mixed $metadata, ?array $runtime, array $definitions): array {
    $code = "<?php\ndeclare(strict_types=1);\nnamespace scpp\\compiler;\n\n// Generated from validated runtime publication. Do not edit.\n";
    $cases = [];
    $application = false;
    $family = isset($metadata->vector) ? textValue(at($metadata->vector, 'source_type')) : null;
    foreach ($runtime['vectors'] ?? [] as $vector) {
        $element = $vector['element'];
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
        $bindings = [
            '@iteration@' => compilerTraversalMethods($vector['traversal'], $elementClass . '::instance()', $countClass . '::instance()'),
            '@methods@' => compilerCollectionMethods($vector, $definitions),
            '@cardinality@' => compilerCardinalityMethods($vector['methods'], $vector['count_type'], $definitions),
            '@class@' => $class, '@name@' => var_export($vector['source_type'], true),
            '@element@' => $elementClass . '::instance()', '@cpp@' => var_export($export['symbol'], true),
            '@header@' => var_export($header, true), '@count@' => $countClass,
            '@append@' => $append['name'], '@index@' => $index['name'], '@set@' => $set['name'],
            '@interfaces@' => ' implements iterable_type',
            '@identity@' => "\tprivate static ?self \$instance = null;\n\n\tprivate function __construct()\n\t{\n\t\tparent::__construct(" . var_export($vector['source_type'], true) . ", $elementClass::instance());\n\t}\n",
            '@factory@' => "\tpublic static function instance(): self\n\t{\n\t\treturn self::\$instance ??= new self();\n\t}",
        ];
        $template = readFile(__DIR__ . '/../templates/collections/vector_type.php.tpl');
        $code .= strtr($template, $bindings);
        if (!$application) {
            $application = true;
            $identity = "\tprivate readonly string \$native_name;\n\n\tpublic function __construct(type_definition \$element)\n\t{\n\t\tparent::__construct('vector<' . \$element->name . '>', \$element);\n\t\t\$this->native_name = 'scpp_vector_' . hash('sha256', \$element::class . ':' . \$element->name);\n\t}\n";
            $recipe = ['count' => 'scpp::' . $vector['count_type'], 'limit_method' => $vector['traversal']['limit_method'], 'next_method' => $vector['traversal']['next_method']];
            $code .= strtr($template, array_replace($bindings, [
                '@class@' => 'application_vector_type', '@interfaces@' => ' implements application_collection_type, iterable_type', '@identity@' => $identity,
                '@iteration@' => compilerTraversalMethods($vector['traversal'], '$this->element_type', $countClass . '::instance()', constructed: true), '@element@' => '$this->element_type', '@cpp@' => '$this->native_name',
                '@header@' => var_export(providerHeader($runtime['submodules'], typeExport($runtime['submodules'], $vector['count_type'])['provider']), true),
                '@factory@' => "\tpublic static function concrete_recipe(): string\n\t{\n\t\treturn " . var_export(vectorConcreteRecipe($vector), true) . ";\n\t}\n\n\t/** Recipe inputs from the same validated vector plan as runtime specializations. @return string[] */\n\tpublic function recipe_arguments(): array\n\t{\n\t\treturn " . var_export($recipe, true) . " + ['buffer' => '::scpp::detail', 'access_name' => 'access_' . \$this->native_name, 'access' => 'detail::access_' . \$this->native_name];\n\t}\n\t/** @return type_definition[] */\n\tpublic function concrete_types(): array { return ['e' => \$this->element_type]; }",
            ]));
        }
        $cases[] = "\t\t\t$elementClass::instance() => $class::instance(),";
    }
    $applicationCase = $application ? "\t\tif (\$family === " . var_export($family, true) . " && \$second === null) return new application_vector_type(\$element);\n" : '';
    return [$code, implode("\n", $cases), $family, $applicationCase];
}

/** Bind the common traversal protocol from a validated runtime publication row. */
function compilerTraversalMethods(array $plan, string $value, string $key, bool $boxed = false, bool $constructed = false): string {
    requireThat($plan['mode'] === 'borrow_live_wrapper_physical' && $plan['visibility'] === 'compiler_internal', 'Unsupported compiler traversal protocol');
    $state = $boxed ? 'scpp::int64 iteration_key_index{}; scpp::string iteration_key_text{};' : '';
    $arguments = $boxed ? "\$prefix . 'iteration_key_index, ' . \$prefix . 'iteration_key_text, ' . \$value" : "\$key . ', ' . \$value";
    $publish = $boxed ? " . ' && ((' . \$key . ' = ' . \$prefix . 'iteration_key_index < 0 ? scpp::mixed::from_string(' . \$prefix . 'iteration_key_text) : scpp::mixed::from_integer(' . \$prefix . 'iteration_key_index)), true)'" : '';
    $output = $constructed ? "\n\tpublic function iteration_output_type(): ?string { return \$this->cpp_type() . '::iteration_output'; }\n" : '';
    return $output . strtr(readFile(__DIR__ . '/../templates/collections/compiler_iteration.php.tpl'), [
        '@value@' => $value, '@key@' => $key, '@state@' => var_export($state, true),
        '@limit@' => $plan['limit_method'], '@next@' => $plan['next_method'],
        '@arguments@' => $arguments, '@publish@' => $publish,
    ]);
}
