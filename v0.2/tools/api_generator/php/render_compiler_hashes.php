<?php
declare(strict_types=1);
namespace Scpp\Api;

/** Emit published hash identities and two-argument dispatch; key/value admission remains runtime metadata-owned. */
function compilerHashTypes(mixed $metadata, ?array $runtime, array $definitions): array {
    $code = "<?php\ndeclare(strict_types=1);\nnamespace scpp\\compiler;\n\n// Generated from validated runtime publication. Do not edit.\n";
    $cases = [];
    $applicationCases = [];
    $family = isset($metadata->hash) ? textValue(at($metadata->hash, 'source_type')) : null;
    foreach ($runtime['hashes']['specializations'] ?? [] as $hash) {
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
            . ", \$this->cpp_headers()[0], const_reference_mask: 3, receiver_method: true);\n";
        $carrier = null;
        foreach ($runtime['carriers'] ?? [] as $candidate) {
            if ($candidate['name'] === ($find['result'] ?? null) && $candidate['family'] === 'nullable'
                && $candidate['payload'] === $hash['value']) $carrier = $candidate;
        }
        requireThat($find !== null && !$find['mutates'] && $find['missing'] === 'empty_nullable' && $carrier !== null,
            'Compiler hash requires copied lookup with a published nullable payload');
        $lookup = "\t\t\$this->lookup_binding = new runtime_function('hash_find', [\$this, $key::instance()], type_"
            . $carrier['name'] . "::instance(), " . var_export($find['name'], true)
            . ", \$this->cpp_headers()[0], const_reference_mask: 3, receiver_method: true);\n";
        $export = typeExport($runtime['submodules'], $hash['name']);
        $bindings = [
            '@append_method@' => compilerHashAppend($hash),
            '@set@' => $set['name'], '@index@' => $at['name'],
            '@iteration@' => compilerTraversalMethods($hash['traversal'], $value . '::instance()', $key . '::instance()'),
            '@methods@' => compilerCollectionMethods($hash, $definitions),
            '@cardinality@' => compilerCardinalityMethods($hash['methods'], $runtime['hashes']['count_type'], $definitions),
            '@class@' => $class,
            '@lookup_property@' => "\tprivate readonly runtime_function \$lookup_binding;",
            '@lookup@' => "\tpublic function lookup_function(): runtime_function\n\t{\n\t\treturn \$this->lookup_binding;\n\t}\n",

            '@key@' => $key . '::instance()', '@cpp@' => var_export($export['symbol'], true),
            '@header@' => var_export(providerHeader($runtime['submodules'], $export['provider']), true),
            '@interfaces@' => ' implements iterable_type, key_presence_type, key_lookup_type' . ($hash['key'] === 'int64' ? ', appendable_collection_type' : ''),
            '@identity@' => "\tprivate static ?self \$instance = null;\n\n\tprivate function __construct()\n\t{\n\t\tparent::__construct(" . var_export($hash['source_family'], true) . " . '<' . $value::instance()->name . ',' . $key::instance()->name . '>', $value::instance(), $key::instance());\n" . $presence . $lookup . "\t}\n",
            '@factory@' => "\tpublic static function instance(): self { return self::\$instance ??= new self(); }",
        ];
        $template = readFile(__DIR__ . '/../templates/collections/hash_type.php.tpl');
        $code .= strtr($template, $bindings);
        if (!isset($applicationCases[$key])) {
            $app = 'application_hash_' . $definitions[$hash['key']]['class'] . '_type';
            $applicationCases[$key] = "\t\tif (\$family === " . var_export($family, true) . " && \$second === $key::instance()) return new $app(\$element);\n";
            $recipe = hashConcreteArguments($hash, $runtime['hashes']['count_type']) + $hash['traversal'];
            $code .= strtr($template, array_replace($bindings, [
                '@lookup_property@' => '', '@lookup@' => '',
                '@class@' => $app, '@interfaces@' => ' implements application_collection_type, key_presence_type, iterable_type' . ($hash['key'] === 'int64' ? ', appendable_collection_type' : ''),
                '@iteration@' => compilerTraversalMethods($hash['traversal'], '$this->value_type', '$this->key_type', constructed: true),
                '@identity@' => "\tprivate readonly string \$native_name;\n\n\tpublic function __construct(type_definition \$value)\n\t{\n\t\tparent::__construct(" . var_export($family, true) . " . '<' . \$value->name . ',' . $key::instance()->name . '>', \$value, $key::instance());\n" . $presence . "\t\t\$this->native_name = 'scpp_hash_' . hash('sha256', \$value::class . ':' . \$value->name . ':' . \$this->key_type->name);\n\t}\n",
                '@cpp@' => '$this->native_name',
                '@header@' => var_export(providerHeader($runtime['submodules'], typeExport($runtime['submodules'], $runtime['hashes']['count_type'])['provider']), true),
                '@factory@' => "\tpublic static function concrete_recipe(): string { return " . var_export(hashConcreteRecipe($hash), true) . "; }\n\t/** @return type_definition[] */\n\tpublic function concrete_types(): array { return ['k' => \$this->key_type, 'v' => \$this->value_type]; }\n\t/** @return string[] */\n\tpublic function recipe_arguments(): array { return " . var_export($recipe, true) . "; }",
            ]));
        }
        $cases[] = "\t\t\tif ((\$element === $value::instance()) && (\$second === $key::instance())) return $class::instance();";
    }
    return [$code, implode("\n", $cases), $family, implode('', $applicationCases)];
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
