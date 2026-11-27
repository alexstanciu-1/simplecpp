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
function compilerPeerEquality(mixed $carriers, array $definitions, array $promotions, array $binary): string {
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
    $writes = "\treturn null;";
    if (isset($carriers->payload_writes)) {
        $policy = $carriers->payload_writes;
        fields($policy, ['flavor', 'operands', 'payloads', 'alternate', 'write_back']);
        literals($policy, ['flavor' => 'php', 'operands' => 'operand_participation',
            'alternate' => 'checked_extraction', 'write_back' => 'wrap_after_scalar_operation']);
        $types = array_intersect(names(at($policy, 'payloads')), array_keys($definitions));
        $names = implode(', ', array_map(static fn($name) => var_export($name, true), $types));
        $writes = "\tif (\$flavor !== flavor::php) return null;\n\t\$payload = carrier_operand_payload(\$type);\n"
            . "\treturn \$payload !== null && in_array(\$payload->name, [$names], true) ? \$payload : null;";
    }
    $template = strtr($template, ['@families@' => $familyArm, '@writes@' => $writes, '@shared_states@' => implode("\n", $shared)]);
    if ($rule === null) return str_replace('@dispatch@', "\treturn null;", $template);
    requireThat($published !== [], 'Equality requires published carrier operands');
    fields($rule, ['flavor', 'operands', 'payloads', 'evaluation', 'states', 'operators', 'result_observations']);
    literals($rule, ['operands' => 'operand_participation',
        'evaluation' => 'snapshot_left_then_right', 'states' => 'match_alternate_state']);
    $flavor = textValue(at($rule, 'flavor'));
    requireThat(in_array($flavor, ['php', 'cpp', 'js', 'python'], true), 'Unknown peer equality flavor');
    $arms = [];
    $operators = fields(at($rule, 'operators'), ['equal', 'identical']);
    foreach ($operators as $positive => $inverse) {
        requireThat($inverse === 'not_' . $positive, 'Invalid inverse equality operator');
        $arms[$positive] = "\t\tbinary_operator::$positive, binary_operator::$inverse => binary_operator::$positive,";
    }
    requireThat($arms !== [], 'Empty peer equality rule');
    ksort($arms, SORT_STRING);
    $mixed = compilerMixedPeerBindings(at($rule, 'payloads'), $operators, $definitions, $promotions, $binary);
    $observations = '';
    if (isset($rule->result_observations)) {
        $obs = $rule->result_observations;
        fields($obs, ['families', 'boolean', 'strict_null']);
        literals($obs, ['boolean' => 'success', 'strict_null' => 'present_null_payload']);
        $resultFamilies = names(at($obs, 'families'));
        requireThat(array_diff($resultFamilies, ['result', 'result_or_false', 'result_or_bool']) === [], 'Success comparison requires result families');
        $families = array_intersect($published, array_map(static fn($f) => var_export($f, true), $resultFamilies));
        $list = implode(', ', $families);
        $observations = "\t\$carrier = \$left instanceof carrier_type_definition ? \$left : \$right;\n"
            . "\tif (\$carrier instanceof carrier_type_definition && in_array(\$carrier->family, [$list], true)) {\n"
            . "\t\t\$peer = \$carrier === \$left ? \$right : \$left;\n"
            . "\t\tif (\$peer === type_bool::instance()) return new prepared_peer_equality(\$comparator, type_bool::instance(), observation: result_observation::success);\n"
            . "\t\tif (\$peer === type_null::instance()) return new prepared_peer_equality(\$comparator, type_bool::instance(), observation: result_observation::null_payload);\n\t}\n";
    }
    $dispatch = "\tif (\$flavor !== flavor::$flavor) return null;\n"
        . "\t\$comparator = match (\$operator) {\n" . implode("\n", $arms) . "\n\t\tdefault => null,\n\t};\n"
        . "\tif (\$comparator === null) return null;\n"
        . $observations
        . "\t\$left = carrier_operand_payload(\$left);\n\t\$right = carrier_operand_payload(\$right);\n"
        . "\tif (\$left !== null && \$left === \$right) return new prepared_peer_equality(\$comparator, \$left);\n"
        . implode("\n", $mixed) . "\n\treturn null;";
    return str_replace('@dispatch@', $dispatch, $template);
}

/** Emit carrier payload recipes from the same normalized scalar plans used by plain comparisons. */
function compilerMixedPeerBindings(mixed $payloads, array $operators, array $definitions, array $promotions, array $binary): array {
    fields($payloads, ['matching_types', 'scalar_pairs']);
    literals($payloads, ['matching_types' => true]);
    literals($payloads, ['scalar_pairs' => 'admitted_scalar_comparisons']);
    $mixed = [];
    if (isset($operators['identical'])) {
        $peers = [];
        foreach ($binary as $name => $bindings) {
            if (!isset($bindings['identical']['identity_peers'])) continue;
            requireThat($bindings['identical']['different_result'] === false, 'Unsupported scalar identity policy');
            $peers[] = var_export($name, true);
        }
        if ($peers !== []) {
            $mixed[] = "\tif (\$comparator === binary_operator::identical && \$left !== null && \$right !== null\n"
                . "\t\t&& in_array(\$left->name, [" . implode(', ', $peers) . "], true)\n"
                . "\t\t&& \$left->binary_result(\$operator, \$right) === type_bool::instance()) return new prepared_peer_equality(\$comparator, null);";
        }
    }
    $selected = array_merge(array_keys($operators), array_values($operators));
    foreach ($promotions as $left => $rows) {
        $arms = [];
        foreach ($rows as $row) {
            foreach (array_intersect($row['operators'], $selected) as $operator) {
                requireThat($row['result'] === 'bool_t', 'Peer comparison must return bool');
                $exec = 'type_' . $definitions[$row['execution']]['class'] . '::instance()';
                $arms[$row['right']][] = "\t\t\tbinary_operator::$operator => new prepared_peer_equality(\$comparator, $exec, "
                    . "value_conversion::{$row['left_conversion']}, value_conversion::{$row['right_conversion']}),";
            }
        }
        foreach ($arms as $right => $bindings) {
            $mixed[] = "\tif (\$left === type_{$definitions[$left]['class']}::instance() && \$right === type_{$definitions[$right]['class']}::instance()) {\n"
                . "\t\treturn match (\$operator) {\n" . implode("\n", $bindings) . "\n\t\t\tdefault => null,\n\t\t};\n\t}";
        }
    }
    return $mixed;
}
