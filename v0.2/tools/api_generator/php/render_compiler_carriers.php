<?php
declare(strict_types=1);
namespace Scpp\Api;

/** Generate all carrier families through one payload/export path; inactive or unpublished payloads stay unavailable. */
function compilerCarrierTypes(?array $runtime, array $definitions): array {
    $code = "<?php\ndeclare(strict_types=1);\nnamespace scpp\\compiler;\n\n// Generated from validated runtime publication. Do not edit.\n";
    $payloads = [];
    foreach ($definitions as $identity => $definition) {
        $payloads[$identity] = ['class' => 'type_' . $definition['class']];
    }
    foreach ($runtime['vectors'] ?? [] as $vector) {
        if (isset($definitions[$vector['element']])) $payloads[$vector['name']] = ['class' => 'type_' . $vector['name']];
    }
    $cases = [];
    $literals = [];
    foreach ($runtime['carrier_sentinels'] ?? [] as $state => $sentinel) {
        $class = 'type_' . $sentinel['literal'];
        $name = var_export($sentinel['literal'], true);
        $stateValue = var_export($state, true);
        $code .= "\n/** Explicit carrier alternate-state token; never an ordinary boolean payload. */\nfinal class $class extends carrier_sentinel_type_definition\n{\n"
            . "\tprivate static ?self \$instance = null;\n\tprivate function __construct() { parent::__construct($name, $stateValue); }\n"
            . "\tpublic static function instance(): self { return self::\$instance ??= new self(); }\n}\n";
        $literals[] = "\t\t\t$name => $class::instance(),";
    }
    foreach ($runtime['carriers'] ?? [] as $carrier) {
        $payload = $payloads[$carrier['payload']] ?? null;
        if ($payload === null) continue;
        $export = typeExport($runtime['submodules'], $carrier['name']);
        $class = 'type_' . $carrier['name'];
        $sentinelCases = [];
        foreach ($runtime['carrier_sentinels'] ?? [] as $state => $sentinel) {
            if (array_key_exists($state, $carrier['states'])) {
                $sentinelCases[] = "\t\t\t" . var_export($state, true) . ' => ' . var_export($sentinel['factory'], true) . ',';
            }
        }
        $valuePresence = $carrier['presence']['value'];
        $hasTrueState = array_key_exists('true', $carrier['states']);
        $truePresence = $hasTrueState ? 'carrier.' . $carrier['presence']['true'] . '()' : 'false';
        $presenceExpression = $hasTrueState
            ? "'([&]() { const auto &carrier = ' . \$value . '; return carrier.{$valuePresence}() || {$truePresence}; }())'"
            : "'(' . \$value . ').{$valuePresence}()'";
        $code .= strtr(readFile(__DIR__ . '/../templates/carrier_type.php.tpl'), [
            '@sentinel_cases@' => implode("\n", $sentinelCases),
            '@presence_expression@' => $presenceExpression,
            '@presence@' => $valuePresence,
            '@true_presence@' => $truePresence,
            '@empty@' => array_key_exists('empty', $carrier['states']) ? 'true' : 'false',
            '@implicit@' => $carrier['extraction'] === 'implicit_checked_one_layer_value_copy' ? 'true' : 'false',
            '@access@' => $carrier['payload_access'],
            '@class@' => $class, '@family@' => var_export($carrier['family'], true),
            '@payload@' => $payload['class'], '@cpp@' => var_export($export['symbol'], true),
            '@header@' => var_export(providerHeader($runtime['submodules'], $export['provider']), true),
        ]);
        $cases[$carrier['family']][] = "\t\t\t" . $payload['class'] . "::instance() => $class::instance(),";
    }
    return [$code, $cases, $literals];
}
