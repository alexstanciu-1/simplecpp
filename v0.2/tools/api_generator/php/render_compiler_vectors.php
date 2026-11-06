<?php
declare(strict_types=1);
namespace Scpp\Api;

/** Emit resolution-only identities from validated runtime specializations; compiler activation remains independent. */
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
        $code .= strtr(readFile(__DIR__ . '/../templates/vector_type.php.tpl'), [
            '@class@' => $class, '@name@' => var_export($vector['source_type'], true),
            '@element@' => $elementClass, '@cpp@' => var_export($export['symbol'], true),
            '@header@' => var_export($header, true),
        ]);
        $cases[] = "\t\t\t$elementClass::instance() => $class::instance(),";
    }
    return [$code, implode("\n", $cases), $family];
}
