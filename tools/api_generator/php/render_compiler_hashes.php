<?php
declare(strict_types=1);
namespace Scpp\Api;

/** Emit published hash identities and two-argument dispatch; key/value admission remains runtime metadata-owned. */
function compilerHashTypes(mixed $metadata, ?array $runtime, array $definitions): array {
    $code = "<?php\ndeclare(strict_types=1);\nnamespace scpp\\compiler;\n\n// Generated from validated runtime publication. Do not edit.\n";
    $cases = [];
    $writableTraits = [];
    $applicationCases = [];
    $access = compilerAccessInterfaces($metadata->hash ?? null);
    $family = isset($metadata->hash) ? textValue(at($metadata->hash, 'source_type')) : null;
    $transfer = ($runtime['hashes']['transfer_assignment'] ?? false) ? ', private_value_transfer_type' : '';
    foreach ($runtime['vectors'] ?? [] as $vector) $definitions[$vector['name']] = ['class' => $vector['name']];
    foreach (array_keys($runtime['regex_shapes'] ?? []) as $type) $definitions[$type] = ['class' => $type];
    foreach ($runtime['regex_modes'] ?? [] as $mode) $definitions[$mode['cell']] = ['class' => $mode['cell']];
    foreach ([...($runtime['hashes']['specializations'] ?? []), ...($runtime['regex_mode_hashes'] ?? [])] as $hash) {
        if (!isset($definitions[$hash['value']], $definitions[$hash['key']])) continue;
        $value = 'type_' . $definitions[$hash['value']]['class'];
        $key = 'type_' . $definitions[$hash['key']]['class'];
        $class = 'type_' . $hash['name'];
        requireThat($hash['construction'] === 'empty' && $hash['key_policy'] === 'strict'
            && $hash['read'] === 'value_copy', 'Compiler hash requires empty construction, strict keys and copied reads');
        $set = null;
        $at = null;
        $has = null;
        $find = null;
        foreach ($hash['methods'] as $method) {
            if ($method['name'] === 'find' && $method['arguments'] === ['const ' . $hash['key'] . '&']) $find = $method;
            if ($method['name'] === 'has' && $method['arguments'] === ['const ' . $hash['key'] . '&']) $has = $method;
            if ($method['name'] === 'set' && $method['arguments'] === [$hash['key'], $hash['value']]) $set = $method;
            if ($method['name'] === 'at' && $method['arguments'] === ['const ' . $hash['key'] . '&']) $at = $method;
        }
        requireThat($set !== null && $set['result'] === 'void' && $set['mutates'], 'Compiler hash requires canonical set binding');
        requireThat($at !== null && $at['result'] === $hash['value'] && !$at['mutates']
            && $at['missing'] === 'throw_out_of_range', 'Compiler hash requires checked value-copy read binding');
        requireThat($has !== null && $has['result'] === 'bool_t' && !$has['mutates'],
            'Compiler hash requires canonical const key-presence binding');
        $presence = "\t\t\$this->presence_binding = new runtime_function('hash_contains', [\$this, $key::instance()], type_"
            . $definitions[$has['result']]['class'] . "::instance(), " . var_export($has['name'], true)
            . ", \$this->cpp_headers()[0], const_reference_mask: 3, receiver_method: true, php_exact_inputs: 2);\n";
        $carrier = null;
        foreach ($runtime['carriers'] ?? [] as $candidate) {
            if ($candidate['name'] === ($find['result'] ?? null) && $candidate['family'] === 'nullable'
                && $candidate['payload'] === $hash['value']) $carrier = $candidate;
        }
        $lookup = '';
        if (!isset($runtime['regex_shapes'][$hash['name']])) {
            requireThat($find !== null && !$find['mutates'] && $find['missing'] === 'empty_nullable' && $carrier !== null,
                'Compiler hash requires copied lookup with a published nullable payload');
            $lookup = "\t\t\$this->lookup_binding = new runtime_function('hash_find', [\$this, $key::instance()], type_"
                . $carrier['name'] . "::instance(), " . var_export($find['name'], true)
                . ", \$this->cpp_headers()[0], const_reference_mask: 3, receiver_method: true, php_exact_inputs: 2);\n";
        }
        $export = typeExport($runtime['submodules'], $hash['name']);
        $initialization = '';
        $initializationSetup = '';
        if ($hash['initialization'] !== null) {
            $init = (array)$hash['initialization'];
            $prefix = $export['symbol'] . '::';
            $entry = var_export($prefix . $init['entry'], true);
            $input = var_export($prefix . $init['input'], true);
            $factory = var_export($prefix . ($init['consume'] ?? $init['read_only']), true);
            $initialization = "\tprivate readonly initialization_transport \$initialization;\n\tpublic function keyed_initialization(): initialization_transport { return \$this->initialization; }\n";
            $initializationSetup = "\t\t\$this->initialization = new initialization_transport($entry, $input, $factory, {$init['inline_entries']});\n";
        }
        $writable = compilerWritableIteration(hashConcreteRecipe($hash), $hash['traversal'], $writableTraits, $code);
        $writableInterface = $writable === '' ? '' : ', writable_iteration_type';
        $bindings = [
            '@writable_iteration@' => $writable === '' ? '' : "\tuse $writable;\n",
            '@checked_slot_binding@' => compilerCheckedSlot(hashConcreteRecipe($hash)),
            '@initialization@' => $initialization,
            '@append_method@' => compilerHashAppend($hash),
            '@set@' => $set['name'], '@index@' => $at['name'],
            '@iteration@' => compilerTraversalMethods($hash['traversal'], $value . '::instance()', $key . '::instance()'),
            '@methods@' => compilerCollectionMethods($hash, $definitions),
            '@cardinality@' => compilerCardinalityMethods($hash['methods'], $runtime['hashes']['count_type'], $definitions),
            '@class@' => $class,
            '@lookup_property@' => $lookup === '' ? '' : "\tprivate readonly runtime_function \$lookup_binding;",
            '@lookup@' => $lookup === '' ? '' : "\tpublic function lookup_function(): runtime_function\n\t{\n\t\treturn \$this->lookup_binding;\n\t}\n",

            '@key@' => $key . '::instance()', '@cpp@' => var_export($export['symbol'], true),
            '@header@' => var_export(providerHeader($runtime['submodules'], $export['provider']), true),
            '@interfaces@' => ' implements conditional_index_store_type, key_iterable_type, key_presence_type' . $writableInterface . $access . ($lookup === '' ? '' : ', key_lookup_type') . $transfer . ($initialization !== '' ? ', keyed_initializer_type' : '') . ($hash['key'] === 'int64' ? ', appendable_collection_type' : ''),
            '@identity@' => "\tprivate static ?self \$instance = null;\n\n\tprivate function __construct()\n\t{\n\t\tparent::__construct(" . var_export($hash['source_family'], true) . " . '<' . $value::instance()->name . ',' . $key::instance()->name . '>', $value::instance(), $key::instance());\n" . $presence . $lookup . $initializationSetup . "\t}\n",
            '@factory@' => "\tpublic static function instance(): self { return self::\$instance ??= new self(); }",
        ];
        if (isset($runtime['regex_shapes'][$hash['name']])) {
            $bindings['@methods@'] .= "\n\tpublic function publishes_entry_isset(): bool { return true; }\n\tpublic function php_isset_header(): string { return 'scpp/php/regex_hash_isset.hpp'; }\n";
        }
        $template = readFile(__DIR__ . '/../templates/collections/hash_type.php.tpl');
        $code .= strtr($template, $bindings);
        foreach (['generic' => false] + ($hash['key'] === 'int64' ? ['append' => true] : []) as $variant => $append) {
            if (isset($applicationCases[$variant])) continue;
            $app = $append ? 'application_hash_int64_type' : 'application_hash_type';
            $applicationCases[$variant] = $append
                ? "\t\tif (\$family === " . var_export($family, true) . " && \$second === $key::instance() && \$element->concrete_storage_support('copy_constructible') === true) return new $app(\$element, \$second);\n"
                : "\t\tif (\$family === " . var_export($family, true) . " && \$second?->hash_key_supported() && \$element->concrete_storage_support('copy_constructible') === true) return new $app(\$element, \$second);\n";
            $recipe = ['mt_segments' => !empty($runtime['mt_segments']) ? '1' : ''] + hashConcreteArguments($hash, $runtime['hashes']['count_type']) + $hash['traversal'];
            unset($recipe['enable_shared_lookup'], $recipe['enable_owner_presence']); // The settled application value supplies this condition.
            $appInitialization = '';
            if ($hash['initialization'] !== null) {
                $mode = isset($init['consume']) ? 'consume' : 'read_only';
                $recipe = array_replace($recipe, [
                    'enable_keyed_input' => '1', 'e' => $init['entry'], 'input' => $init['input'],
                ], hashInputFactoryArguments($init, $mode, 'storage::key_type', 'storage::value_type'));
                // Application layouts have no fixed entry-size bound at PHP preparation time.
                $appInitialization = "\tpublic function keyed_initialization(): initialization_transport { return new initialization_transport("
                    . "\$this->native_name . " . var_export('::' . $init['entry'], true) . ", \$this->native_name . "
                    . var_export('::' . $init['input'], true) . ", \$this->native_name . " . var_export('::' . $init[$mode], true) . ", 0); }\n";
            }
            if (!$append) $recipe = array_replace($recipe, ['enable_append' => '', 'append' => '']);
            $code .= strtr($template, array_replace($bindings, [
                '@lookup_property@' => "\tprivate readonly ?runtime_function \$shared_lookup;",
                '@lookup@' => "\tpublic function shared_lookup_function(): ?runtime_function { return \$this->shared_lookup; }\n", '@initialization@' => $appInitialization,
                '@append_method@' => $append ? compilerHashAppend($hash) : '',
                '@key@' => '$this->key_type',
                '@methods@' => compilerCollectionMethods($hash, $definitions, '$this->key_type'),
                '@class@' => $app, '@interfaces@' => ' implements conditional_index_store_type, application_collection_type, key_presence_type, shared_value_lookup_type, key_iterable_type' . $writableInterface . $access . ($appInitialization !== '' ? ', keyed_initializer_type' : '') . $transfer . ($append ? ', appendable_collection_type' : ''),
                '@iteration@' => compilerTraversalMethods($hash['traversal'], '$this->value_type', '$this->key_type', constructed: true),
                '@identity@' => strtr(readFile(__DIR__ . '/../templates/collections/application_hash_identity.php.tpl'), [
                    '@family@' => var_export($family, true),
                    '@presence@' => rtrim(str_replace("$key::instance()", '$key', $presence)),
                ]),
                '@cpp@' => '$this->native_name',
                '@header@' => var_export(providerHeader($runtime['submodules'], typeExport($runtime['submodules'], $runtime['hashes']['count_type'])['provider']), true),
                '@factory@' => "\tpublic static function concrete_recipe(): string { return " . var_export(hashConcreteRecipe($hash), true) . "; }\n\t/** @return type_definition[] */\n\tpublic function concrete_types(): array { return ['k' => \$this->key_type, 'v' => \$this->value_type]; }\n\t/** @return string[] */\n\tpublic function recipe_arguments(): array { return ['enable_shared_lookup' => \$this->shared_lookup !== null ? '1' : '',\n\t\t'enable_owner_presence' => \$this->probes_stored_owner() ? '1' : ''] + " . var_export($recipe, true) . "; }",
            ]));
        }
        $cases[] = "\t\t\tif ((\$element === $value::instance()) && (\$second === $key::instance())) return $class::instance();";
    }
    return [$code, implode("\n", $cases), $family, ($applicationCases['append'] ?? '') . ($applicationCases['generic'] ?? '')];
}

/** Generate append only when the published method plan provides the integer-key operation. */
function compilerHashAppend(array $hash): string {
    foreach ($hash['methods'] as $method) {
        if ($method['name'] !== 'append') continue;
        requireThat($hash['key'] === 'int64' && $method['arguments'] === [$hash['value']] && $method['result'] === 'int64' && $method['mutates'], 'Invalid hash append contract');
        return strtr(readFile(__DIR__ . '/../templates/collections/compiler_append.php.tpl'), ['@append@' => $method['name']]);
    }
    requireThat($hash['key'] !== 'int64', 'Missing integer hash append');
    return '';
}
