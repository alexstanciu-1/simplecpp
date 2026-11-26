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
    if (isset($runtime['boxed_dispatch'])) {
        foreach ($runtime['boxed']['families'] as $box) $payloads[$box['name']] = ['class' => 'type_' . $box['name']];
    }
    foreach ($runtime['regex_carriers'] ?? [] as $carrier) {
        $payloads[$carrier['payload']] = ['class' => 'type_' . $carrier['payload']];
    }
    $cases = [];
    $literals = [];
    $typeNames = [];
    foreach ($runtime['carrier_sentinels'] ?? [] as $state => $sentinel) {
        $class = 'type_' . $sentinel['literal'];
        $name = var_export($sentinel['literal'], true);
        $stateValue = var_export($state, true);
        $code .= "\n/** Explicit carrier alternate-state token; never an ordinary boolean payload. */\nfinal class $class extends carrier_sentinel_type_definition\n{\n"
            . "\tprivate static ?self \$instance = null;\n\tprivate function __construct() { parent::__construct($name, $stateValue); }\n"
            . "\tpublic static function instance(): self { return self::\$instance ??= new self(); }\n}\n";
        $literals[] = "\t\t\t$name => $class::instance(),";
    }
    foreach ([...($runtime['carriers'] ?? []), ...($runtime['json_carriers'] ?? []), ...($runtime['regex_carriers'] ?? []), ...($runtime['resource_carriers'] ?? [])] as $carrier) {
        $payload = $payloads[$carrier['payload']] ?? null;
        if ($payload === null) continue;
        $export = isset($carrier['header']) ? ['symbol' => 'scpp::' . $carrier['name']] : typeExport($runtime['submodules'], $carrier['name']);
        $class = 'type_' . $carrier['name'];
        $typeNames[$carrier['family'] . '<' . $carrier['payload'] . '>'] = $typeNames[$carrier['name']] = $carrier['name'];
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
        $peerEquality = '';
        if ($hasTrueState) {
            $peerEquality = strtr(readFile(__DIR__ . '/../templates/carriers/boolean_sentinel_equality.php.tpl'), [
                '@payload@' => $valuePresence, '@true@' => $carrier['presence']['true'],
            ]);
        } elseif (isset($carrier['states']['error'])) {
            $peerEquality = readFile(__DIR__ . '/../templates/carriers/error_equality.php.tpl');
        }
        $code .= strtr(readFile(__DIR__ . '/../templates/carriers/carrier_type.php.tpl'), [
            '@instance_field@' => $carrier['instance_field'] ? 'true' : 'false',
            '@sentinel_cases@' => implode("\n", $sentinelCases),
            '@presence_expression@' => $presenceExpression,
            '@peer_equality@' => $peerEquality,
            '@presence@' => $valuePresence,
            '@true_presence@' => $truePresence,
            '@dispatch_label_type@' => in_array($carrier['family'], ['nullable', 'result'], true) ? '$this->payload_type' : '$this',
            '@coalesce@' => $carrier['family'] === 'nullable' && count($carrier['states']) === 2 && array_key_exists('value', $carrier['states']) && array_key_exists('empty', $carrier['states']) ? 'true' : 'false',
            '@empty@' => array_key_exists('empty', $carrier['states']) ? 'true' : 'false',
            '@implicit@' => $carrier['extraction'] === 'implicit_checked_one_layer_value_copy' ? 'true' : 'false',
            '@access@' => $carrier['payload_access'],
            '@class@' => $class, '@family@' => var_export($carrier['family'], true),
            '@payload@' => $payload['class'], '@cpp@' => var_export($export['symbol'], true),
            '@header@' => var_export($carrier['header'] ?? providerHeader($runtime['submodules'], $export['provider']), true),
        ]);
        $cases[$carrier['family']][] = "\t\t\t" . $payload['class'] . "::instance() => $class::instance(),";
    }
    return [$code, $cases, $literals, $typeNames];
}

/** Publish shared operand participation and the equality group that consumes it. */
function compilerPeerEquality(mixed $carriers): string {
    $template = readFile(__DIR__ . '/../templates/carriers/peer_equality.php.tpl');
    $rule = $carriers->peer_equality ?? null;
    $participation = $carriers->operand_participation ?? null;
    $published = [];
    $stateCounts = [];
    if ($participation !== null) {
        fields($participation, ['families', 'carrier', 'plain']);
        literals($participation, ['carrier' => 'payload_type', 'plain' => 'own_type']);
        foreach (names(at($participation, 'families')) as $name) {
            $states = object(at(at($carriers->families, $name), 'states'));
            foreach (array_keys($states) as $state) {
                if ($state !== 'value') $stateCounts[$state] = ($stateCounts[$state] ?? 0) + 1;
            }
            $published[] = var_export($name, true);
        }
    }
    $familyArm = $published === [] ? '' : "\t\t" . implode(', ', $published) . ' => $type->payload_type,';
    $shared = [];
    foreach ($stateCounts as $state => $count) {
        if ($count < 2) continue;
        requireThat(in_array($state, ['false', 'true'], true), 'Unsupported shared carrier sentinel');
        at($carriers->sentinels, $state);
        $shared[] = "\tif (\$left_type->sentinel_factory('$state') !== null && \$right_type->sentinel_factory('$state') !== null) {\n"
            . "\t\t\$matches[] = \"((\$left).is_$state() && (\$right).is_$state())\";\n\t}";
    }
    $template = strtr($template, ['@families@' => $familyArm, '@shared_states@' => implode("\n", $shared)]);
    if ($rule === null) return str_replace('@dispatch@', "\treturn null;", $template);
    requireThat($published !== [], 'Equality requires published carrier operands');
    fields($rule, ['flavor', 'operands', 'payloads', 'evaluation', 'states', 'operators']);
    literals($rule, ['operands' => 'operand_participation', 'payloads' => 'matching_types',
        'evaluation' => 'snapshot_left_then_right', 'states' => 'match_alternate_state']);
    $flavor = textValue(at($rule, 'flavor'));
    requireThat(in_array($flavor, ['php', 'cpp', 'js', 'python'], true), 'Unknown peer equality flavor');
    $arms = [];
    foreach (fields(at($rule, 'operators'), ['equal', 'identical']) as $positive => $inverse) {
        requireThat($inverse === 'not_' . $positive, 'Invalid inverse equality operator');
        $arms[$positive] = "\t\tbinary_operator::$positive, binary_operator::$inverse => binary_operator::$positive,";
    }
    requireThat($arms !== [], 'Empty peer equality rule');
    ksort($arms, SORT_STRING);
    $dispatch = "\tif (\$flavor !== flavor::$flavor) return null;\n"
        . "\t\$comparator = match (\$operator) {\n" . implode("\n", $arms) . "\n\t\tdefault => null,\n\t};\n"
        . "\tif (\$comparator === null) return null;\n"
        . "\t\$payload = carrier_operand_payload(\$left);\n"
        . "\treturn \$payload !== null && \$payload === carrier_operand_payload(\$right) ? \$comparator : null;";
    return str_replace('@dispatch@', $dispatch, $template);
}
