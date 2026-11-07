<?php
declare(strict_types=1);
namespace Scpp\Api;

/** Emit published hash identities and two-argument dispatch; key/value admission remains runtime metadata-owned. */
function compilerHashTypes(mixed $metadata, ?array $runtime, array $definitions): array {
    $code = "<?php\ndeclare(strict_types=1);\nnamespace scpp\\compiler;\n\n// Generated from validated runtime publication. Do not edit.\n";
    $cases = [];
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
        foreach ($hash['methods'] as $method) {
            if ($method['name'] === 'set' && $method['arguments'] === [$hash['key'], $hash['value']]) $set = $method;
            if ($method['name'] === 'at' && $method['arguments'] === ['const ' . $hash['key'] . '&']) $at = $method;
        }
        requireThat($set !== null && $set['result'] === 'void' && $set['mutates'], 'Compiler hash requires canonical set binding');
        requireThat($at !== null && $at['result'] === $hash['value'] && !$at['mutates']
            && $at['missing'] === 'throw_out_of_range', 'Compiler hash requires checked value-copy read binding');
        $export = typeExport($runtime['submodules'], $hash['name']);
        $code .= strtr(readFile(__DIR__ . '/../templates/hash_type.php.tpl'), [
            '@set@' => $set['name'], '@index@' => $at['name'],
            '@cardinality@' => compilerCardinalityMethods($hash['methods'], $runtime['hashes']['count_type'], $definitions),
            '@class@' => $class, '@family@' => var_export($hash['source_family'], true),
            '@value@' => $value, '@key@' => $key, '@cpp@' => var_export($export['symbol'], true),
            '@header@' => var_export(providerHeader($runtime['submodules'], $export['provider']), true),
        ]);
        $cases[] = "\t\t\tif ((\$element === $value::instance()) && (\$second === $key::instance())) return $class::instance();";
    }
    return [$code, implode("\n", $cases), $family];
}
