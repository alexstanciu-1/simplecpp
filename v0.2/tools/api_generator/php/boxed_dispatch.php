<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/compiler_families.php';
require_once __DIR__ . '/compiler_plans.php';
require_once __DIR__ . '/submodules.php';

/** Project the existing scalar plans onto the published box tags and the first five boxed operators. */
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
    foreach (['add', 'less', 'less_equal', 'greater', 'greater_equal'] as $op) {
        foreach ($defs as $l => $_) foreach ($defs as $r => $_right) {
            $execution = $l === $r ? $l : null;
            foreach ($promotions[$l] ?? [] as $row) {
                if ($row['right'] === $r && in_array($op, $row['operators'], true)) $execution = $row['execution'];
            }
            $decision = $binary[$execution][$op] ?? null;
            // String concatenation has a separate bounded spelling/contract from numeric addition.
            if ($decision === null || $decision['lowering'] === 'runtime_concat') continue;
            requireThat(in_array($decision['lowering'], ['native', 'runtime_compare'], true), 'Unsupported boxed scalar execution strategy');
            $pairs[$op][$l][$r] = [$execution, $decision['spelling'], $decision['result']];
        }
    }
    return compact('kinds', 'cpp', 'casts', 'pairs') + ['headers' => array_keys($headers)];
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
                    [$execution, $spelling, $result] = $rows[$l][$r];
                    $a = $ltag === null ? 'a' : boxedPayload('a', $ltag);
                    $b = $rtag === null ? 'b' : boxedPayload('b', $rtag);
                    $cpp = $plan['cpp'][$execution];
                    if ($l !== $execution) $a = "static_cast<$cpp>($a)";
                    if ($r !== $execution) $b = "static_cast<$cpp>($b)";
                    $expr = "($a $spelling $b)";
                    if ($op === 'add') $expr = $family . '::from_' . $plan['kinds'][$result] . "($expr)";
                    $inner[$rtag ?? 'plain'] = "return $expr;";
                }
                if ($inner) $outer[$ltag ?? 'plain'] = $right === $family ? boxedSwitch('b', $inner) : $inner['plain'];
            }
            if (!$outer) continue;
            $bindings[] = ['op' => $op, 'left' => $left, 'right' => $right,
                'name' => "boxed_{$op}_{$left}_$right", 'result' => $op === 'add' ? $family : 'bool',
                'body' => $left === $family ? boxedSwitch('a', $outer) : $outer['plain']];
        }
    }
    return $bindings;
}

function renderBoxedDispatch(array $plan, string $family): array {
    $api = '';
    $impl = '';
    $emit = static function (string $signature, string $body) use (&$api, &$impl): void {
        $api .= "$signature;\n";
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
        $emit($row['result'] . ' ' . $row['name'] . '(' . $param($row['left']) . ' a, ' . $param($row['right']) . ' b)', $row['body']);
    }
    return [$api, $impl];
}

function compilerBoxedDefinitions(array $runtime, array &$cases): string {
    $plan = $runtime['boxed_dispatch'];
    $code = "<?php\ndeclare(strict_types=1);\nnamespace scpp\\compiler;\n// Generated from boxed kinds and validated scalar plans.\n";
    foreach ($runtime['boxed']['families'] as ['name' => $name]) {
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
        $directOps = [];
        foreach (boxedBinaryBindings($plan, $name) as $row) {
            if ($name === 'dynamic' && $row['left'] === $name && $row['right'] === 'int64' && in_array($row['op'], ['add', 'less'], true)) {
                $directOps[] = '$expr->operator === binary_operator::' . $row['op'];
            }
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
        $directMethod = $directOps === [] ? '' : str_replace('@direct_ops@', implode(' || ', $directOps), <<<'PHP_CODE'
	/** These helpers borrow the dynamic local; the integer literal cannot change its payload. */
	public function cpp_direct_binary(ast_binary_expression $expr, bool $operands_safe, bool $same_spelling): bool
	{
		return (@direct_ops@)
			&& $expr->left instanceof ast_name_expression && $expr->left->type_def() === $this
			&& $expr->left->declaration() instanceof ast_local_declaration
			&& $expr->right instanceof ast_integer_expression && $expr->right->type_def() === type_int64::instance();
	}
PHP_CODE
        ) . "\n\n";
        $code .= strtr(readFile(__DIR__ . '/../templates/compiler_boxed.php.tpl'), [
            '@name@' => $name, '@header@' => $header, '@ops@' => $ops, '@direct_method@' => $directMethod, '@factories@' => $factories,
            '@targets@' => $destinations, '@sources@' => implode(', ', array_map(fn($s) => "'$s'", array_keys($input))),
        ]);
    }
    return $code;
}
