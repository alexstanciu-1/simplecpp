<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/compiler_families.php';
require_once __DIR__ . '/compiler_plans.php';
require_once __DIR__ . '/submodules.php';

/** Project the existing scalar plans onto the published box tags and the supported boxed operator groups. */
function boxedScalarPlan(object $metadata, array $facts, array $providers, array $formatters): array {
    $metadata = compilerProviderMetadata($metadata, $providers);
    $types = registry($metadata, $facts);
    $defs = compilerTypePlan($metadata, $facts, $types, $providers);
    $metadata = clone $metadata;
    $compiler = $metadata->compiler = compilerFamilyPlan($metadata, $defs, $types);
    $conversions = compilerConversionPlan($metadata, $defs, $types, $facts, $formatters);
    $strings = names($metadata->string->operations);
    $binary = compilerBinaryPlan($compiler, $defs, compilerComparisonPlan($compiler, $defs, $strings), $types, $strings);
    $promotions = compilerPromotionPlan($compiler, $conversions, $defs, $binary);
    $kinds = [];
    foreach (names($metadata->boxed->scalar_kinds) as $id) {
        requireThat(isset($defs[$id]), 'Inactive boxed scalar kind');
        $tag = $defs[$id]['strategy'];
        requireThat(!in_array($tag, $kinds, true), 'Duplicate boxed scalar kind');
        $kinds[$id] = $tag;
    }
    requireThat(isset($kinds['int64']) && $kinds['int64'] === 'integer', 'Boxed integers require canonical int64');
    $cpp = [];
    foreach ($defs as $id => $def) {
        // The runtime ABI uses the canonical integer name regardless of compiler export spelling.
        $cpp[$id] = $def['strategy'] === 'integer' ? 'scpp::' . $id : $def['cpp'];
    }
    $headers = [];
    $casts = [];
    foreach ($conversions as $row) {
        if (!isset($kinds[$row['from']])) continue;
        $binding = $row['binding'];
        if ($binding !== null) $headers[$binding['header']] = true;
        $casts[$row['to']][$row['from']] = ['contexts' => $row['contexts'], 'function' => $binding['symbol'] ?? null];
    }
    $pairs = [];
    foreach (['add', 'subtract', 'multiply', 'divide', 'remainder', 'power', 'bit_and', 'bit_or', 'bit_xor', 'shift_left', 'shift_right', 'concatenate', 'less', 'less_equal', 'greater', 'greater_equal'] as $op) {
        foreach ($defs as $l => $_) foreach ($defs as $r => $_right) {
            $execution = $l === $r ? $l : null;
            foreach ($promotions[$l] ?? [] as $row) {
                if ($row['right'] === $r && in_array($op, $row['operators'], true)) $execution = $row['execution'];
            }
            if (in_array($r, $binary[$l][$op]['counts'] ?? [], true)) $execution = $l;
            $decision = $binary[$execution][$op] ?? null;
            if ($decision === null || !isset($kinds[$decision['result']])) continue;
            requireThat(in_array($decision['lowering'], ['native', 'runtime_compare', 'runtime_concat',
                'zero_checked_divide', 'zero_checked_remainder', 'checked_shift', 'checked_power'], true), 'Unsupported boxed scalar execution strategy');
            if (isset($decision['binding']['header'])) $headers[$decision['binding']['header']] = true;
            $pairs[$op][$l][$r] = ['execution' => $execution, 'compound' => $decision['compound'] ?? ($compiler->arithmetic->$execution->compound ?? false)] + $decision;
        }
    }
    $updates = compilerUpdatePlan($compiler, $defs);
    return compact('kinds', 'cpp', 'casts', 'pairs', 'updates') + ['headers' => array_keys($headers)];
}


/** Flavor masks are a generated ABI detail; source admission continues to use the normal flavor enum. */
function boxedFlavorMask(array $flavors): int {
    $mask = 0;
    foreach ($flavors as $flavor) $mask |= ['php' => 1, 'cpp' => 2, 'js' => 4][$flavor];
    return $mask;
}

/** Generate a total runtime kind guard; every rejected kind exits before the receiving assignment. */
function boxedSwitch(string $operand, array $branches): string {
    $body = "switch ($operand.kind()) {\n";
    foreach ($branches as $tag => $code) $body .= "case boxed_kind::$tag: $code\n";
    return $body . "default: throw std::runtime_error(\"boxed_scalar_kind\");\n}";
}

function boxedPayload(string $operand, string $tag): string {
    return $tag === 'null' ? 'nullptr' : "$operand.get_$tag()";
}

/** One signature decision drives runtime declarations/definitions and compiler admission/lowering. */
function boxedBinaryBindings(array $plan, string $family): array {
    $bindings = [];
    $operands = array_keys($plan['cpp']);
    $operands[] = $family;
    foreach ($plan['pairs'] as $op => $rows) {
        foreach ($operands as $left) foreach ($operands as $right) {
            if ($left !== $family && $right !== $family) continue;
            $ls = $left === $family ? $plan['kinds'] : [$left => null];
            $rs = $right === $family ? $plan['kinds'] : [$right => null];
            $outer = [];
            foreach ($ls as $l => $ltag) {
                $inner = [];
                foreach ($rs as $r => $rtag) {
                    if (!isset($rows[$l][$r])) continue;
                    $decision = $rows[$l][$r];
                    $execution = $decision['execution'];
                    $result = $decision['result'];
                    $spelling = $decision['spelling'] ?? '';
                    $a = $ltag === null ? 'a' : boxedPayload('a', $ltag);
                    $b = $rtag === null ? 'b' : boxedPayload('b', $rtag);
                    $cpp = $plan['cpp'][$execution];
                    if ($l !== $execution) $a = "static_cast<$cpp>($a)";
                    if ($r !== $execution && !isset($decision['counts'])) $b = "static_cast<$cpp>($b)";
                    $expr = isset($decision['binding']['symbol'])
                        ? $decision['binding']['symbol'] . "($a, $b)" : "($a $spelling $b)";
                    if (!in_array($op, ['less', 'less_equal', 'greater', 'greater_equal'], true)) {
                        $expr = $family . '::from_' . $plan['kinds'][$result] . "($expr)";
                    }
                    $guard = isset($decision['flavors'])
                        ? 'if (!(flavor_mask & ' . boxedFlavorMask($decision['flavors']) . ')) throw std::runtime_error("boxed_scalar_kind"); ' : '';
                    $inner[$rtag ?? 'plain'] = $guard . "return $expr;";
                }
                if ($inner) $outer[$ltag ?? 'plain'] = $right === $family ? boxedSwitch('b', $inner) : $inner['plain'];
            }
            if (!$outer) continue;
            $bindings[] = ['op' => $op, 'left' => $left, 'right' => $right,
                'name' => "boxed_{$op}_{$left}_$right", 'result' => in_array($op, ['less', 'less_equal', 'greater', 'greater_equal'], true) ? 'bool' : $family,
                'body' => $left === $family ? boxedSwitch('a', $outer) : $outer['plain']];
        }
    }
    return $bindings;
}

function renderBoxedDispatch(array $plan, string $family): array {
    $api = '';
    $impl = '';
    $emit = static function (string $signature, string $body) use (&$api, &$impl): void {
        $api .= str_replace("unsigned flavor_mask)", "unsigned flavor_mask = 0)", $signature) . ";\n";
        $impl .= "$signature {\n$body\n}\n";
    };
    foreach ($plan['cpp'] as $id => $cpp) {
        foreach (['typed_destination', 'explicit_cast', 'assignment'] as $context) {
            $branches = [];
            foreach ($plan['kinds'] as $source => $tag) {
                $row = $plan['casts'][$id][$source] ?? null;
                $expr = boxedPayload('v', $tag);
                if ($id !== $source) {
                    if (!in_array($context, $row['contexts'] ?? [], true)) continue;
                    $expr = ($row['function'] ?? "static_cast<$cpp>") . "($expr)";
                }
                $branches[$tag] = "return $expr;";
            }
            if ($branches) $emit("$cpp boxed_{$family}_{$context}_$id(const $family& v)", boxedSwitch('v', $branches));
        }
    }
    foreach (boxedBinaryBindings($plan, $family) as $row) {
        $param = static function (string $id) use ($family, $plan): string {
            return $id === $family || $id === 'string' ? "const $id&" : $plan['cpp'][$id];
        };
        $emit($row['result'] . ' ' . $row['name'] . '(' . $param($row['left']) . ' a, ' . $param($row['right']) . ' b, unsigned flavor_mask)', "(void)flavor_mask;\n" . $row['body']);
    }
    foreach (['increment' => '++', 'decrement' => '--'] as $op => $spelling) {
        $branches = [];
        foreach ($plan['kinds'] as $id => $tag) {
            if (!in_array($op, $plan['updates'][$id] ?? [], true)) continue;
            $branches[$tag] = "{ auto value = " . boxedPayload('v', $tag) . "; $spelling value; return $family::from_$tag(value); }";
        }
        if ($branches) $emit("$family boxed_{$op}_$family(const $family& v)", boxedSwitch('v', $branches));
    }
    return [$api, $impl];
}

function compilerBoxedDefinitions(array $runtime, array &$cases): string {
    $plan = $runtime['boxed_dispatch'];
    $code = "<?php\ndeclare(strict_types=1);\nnamespace scpp\\compiler;\n// Generated from boxed kinds and validated scalar plans.\n";
    foreach ($runtime['boxed']['families'] as $family) {
        $name = $family['name'];
        $cases[] = "\t\t\t'$name' => type_$name::instance(),";
        $header = providerHeader($runtime['submodules'], typeExport($runtime['submodules'], $name)['provider']);
        $input = $plan['kinds'] + ['uint8' => 'integer'];
        $factories = '';
        foreach ($input as $source => $tag) {
            if ($tag === 'null') {
                $factory = "'((void)(' . \$value . '), scpp::$name{})'";
            } else {
                // The integer factory takes canonical int64, including when boxing a byte.
                $argument = $tag === 'integer' && $source !== 'int64'
                    ? 'type_registry::integer()->lower_cpp_exact_argument($syntax, value_conversion::integer_widen, $value)'
                    : '$source->lower_cpp_exact_argument($syntax, value_conversion::identity, $value)';
                $factory = "'scpp::$name::from_$tag(' . $argument . ')'";
            }
            $factories .= "\t\t\t'$source' => $factory,\n";
        }
        $pairs = [];
        $results = [];
        foreach (boxedBinaryBindings($plan, $name) as $row) {
            $results[$row['op']] = $row['result'] === $name ? '$this' : 'type_bool::instance()';
            if ($row['left'] === $name) $pairs[$row['op']]['left'][] = "'{$row['right']}'";
            if ($row['right'] === $name) $pairs[$row['op']]['right'][] = "'{$row['left']}'";
        }
        $destinations = '';
        foreach (['typed_destination', 'explicit_cast', 'assignment'] as $context) {
            $targets = array_keys($plan['kinds']);
            foreach ($plan['casts'] as $to => $rows) foreach ($rows as $row) {
                if (in_array($context, $row['contexts'], true)) $targets[] = $to;
            }
            $allowed = implode(', ', array_map(fn($id) => "'$id'", array_unique($targets)));
            $destinations .= "\t\t\tconversion_context::$context => match (\$target->name) { $allowed => true, default => false },\n";
        }
        $ops = '';
        foreach ($pairs as $op => $keys) {
            $admission = [];
            foreach (['left', 'right'] as $side) {
                $peers = $keys[$side] ?? [];
                sort($peers, SORT_STRING);
                $admission[$side] = $peers ? "match (\$peer->name) { " . implode(', ', $peers) . " => true, default => false }" : 'false';
            }
            $expression = $admission['left'] === $admission['right'] ? $admission['left']
                : "(\$box_left ? {$admission['left']} : {$admission['right']})";
            $ops .= "\t\t\tbinary_operator::$op => $expression,\n";
        }
        $updateCases = [];
        foreach ($plan['kinds'] as $id => $_) foreach ($plan['updates'][$id] ?? [] as $op) $updateCases[$op] = 'update_operator::' . $op;
        $compounds = [];
        foreach ($plan['pairs'] as $op => $lefts) foreach ($lefts as $l => $rights) foreach ($rights as $r => $decision) {
            if ($decision['compound'] && isset($plan['kinds'][$l], $plan['kinds'][$r])) $compounds[$op] = 'binary_operator::' . $op;
        }
        $resultCode = '';
        foreach ($results as $op => $result) $resultCode .= "\t\t\tbinary_operator::$op => $result,\n";
        $flavorCode = '';
        foreach (['php', 'cpp', 'js'] as $flavor) $flavorCode .= "flavor::$flavor => " . boxedFlavorMask([$flavor]) . ', ';
        $code .= strtr(readFile(__DIR__ . '/../templates/boxed/compiler_boxed.php.tpl'), [
            '@compounds@' => implode(', ', $compounds), '@updates@' => implode(', ', $updateCases), '@results@' => $resultCode, '@flavor_mask@' => $flavorCode,
            '@reference_mutation@' => $family['copy'] === 'shared_container_identity' ? 'true' : 'false',
            '@field_admission@' => $family['source_collection']['instance_field_default'] === 'null' ? 'true' : 'false',
            '@append_method@' => strtr(readFile(__DIR__ . '/../templates/collections/compiler_append.php.tpl'), ['@append@' => $family['source_collection']['append_method']]),
            '@count_method@' => $family['source_collection']['count_method'],
            '@iteration@' => compilerTraversalMethods($family['traversal'], 'self::instance()', isset($runtime['submodules']['type_exports']['mixed']) ? 'type_mixed::instance()' : 'null', true),
            '@name@' => $name, '@header@' => $header, '@ops@' => $ops, '@factories@' => $factories,
            '@targets@' => $destinations, '@sources@' => implode(', ', array_map(fn($s) => "'$s'", array_keys($input))),
        ]);
    }
    return $code;
}
