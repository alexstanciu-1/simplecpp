<?php
declare(strict_types=1);
namespace Scpp\Api;

/** Emit concrete identities and bounded member handlers from validated runtime specializations. */
function compilerVectorTypes(mixed $metadata, ?array $runtime, array $definitions): array {
    $code = "<?php\ndeclare(strict_types=1);\nnamespace scpp\\compiler;\n\n// Generated from validated runtime publication. Do not edit.\n";
    $cases = [];
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
        foreach ($vector['methods'] as $method) {
            if ($method['name'] === 'append' && $method['arguments'] === [$element]) $append = $method;
            if ($method['name'] === 'at' && $method['arguments'] === [$vector['count_type']]) $index = $method;
        }
        requireThat($append !== null && $append['receiver'] === 'mutable_reference' && $append['result'] === 'void', 'Compiler vector requires canonical append binding');
        requireThat($index !== null && $index['receiver'] === 'const_reference' && $index['result'] === $element
            && $index['result_category'] === 'value', 'Compiler vector requires value-copy index binding');
        requireThat(isset($definitions[$vector['count_type']]), 'Compiler vector count type is unavailable');
        $countClass = 'type_' . $definitions[$vector['count_type']]['class'];
        $code .= strtr(readFile(__DIR__ . '/../templates/vector_type.php.tpl'), [
            '@class@' => $class, '@name@' => var_export($vector['source_type'], true),
            '@element@' => $elementClass, '@cpp@' => var_export($export['symbol'], true),
            '@header@' => var_export($header, true), '@count@' => $countClass,
            '@append@' => $append['name'], '@index@' => $index['name'],
        ]);
        $cases[] = "\t\t\t$elementClass::instance() => $class::instance(),";
    }
    return [$code, implode("\n", $cases), $family];
}
