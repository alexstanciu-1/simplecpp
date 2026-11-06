<?php
declare(strict_types=1);
namespace Scpp\Api;

/** Expand mixed numeric families before conversions derive their operator permissions. */
function compilerMixedNumericOperations(object $compiler, array $definitions): object {
    if (!isset($compiler->mixed_numeric_operators)) return $compiler;
    requireThat(!isset($compiler->promotions), 'Duplicate promotion policy owner');
    $group = $compiler->mixed_numeric_operators;
    fields($group, ['promotion', 'arithmetic', 'comparisons', 'compound_operators']);
    literals($group, ['promotion' => 'floating_operand']);
    $arithmetic = names(at($group, 'arithmetic'));
    $comparisons = names(at($group, 'comparisons'));
    $compounds = names(at($group, 'compound_operators'));
    requireThat(array_diff($arithmetic, ['add', 'subtract', 'multiply', 'divide']) === [], 'Unsupported mixed arithmetic operator');
    requireThat(array_diff($comparisons, COMPARISONS) === [], 'Unsupported mixed comparison operator');
    requireThat(array_diff($compounds, $arithmetic) === [], 'Compound requires an admitted mixed arithmetic operator');
    $compiler->promotions = [];
    foreach ($definitions as $integer => $i) foreach ($definitions as $floating => $f) {
        if ($i['strategy'] !== 'integer' || $f['strategy'] !== 'floating') continue;
        foreach ([$arithmetic, $comparisons] as $index => $operators) {
            if ($operators === []) continue;
            $result = $index === 0 ? $floating : at(at($compiler->comparisons, $floating), 'result');
            foreach ([[$integer, $floating], [$floating, $integer]] as [$left, $right]) {
                $row = (object)['left' => $left, 'right' => $right, 'operators' => $operators,
                    'execution_type' => $floating, 'result' => $result];
                if ($index === 0 && $left === $floating) $row->compound_operators = $compounds;
                $compiler->promotions[] = $row;
            }
        }
    }
    unset($compiler->mixed_numeric_operators);
    return $compiler;
}

/** Expand family permission into concrete generation-time rows; no pair table is retained by the compiler. */
function compilerConversionFamilies(object $compiler, array $definitions, array $types): object {
    if (!isset($compiler->conversion_families)) return $compiler;
    requireThat(!isset($compiler->conversions), 'Duplicate conversion policy owner');
    $rules = object($compiler->conversion_families);
    foreach ($rules as $pair => $rule) {
        $families = explode(':', $pair);
        requireThat(count($families) === 2 && array_diff($families, ['integer', 'floating', 'boolean']) === [], 'Unsupported conversion family');
        fields($rule, ['operation', 'contexts']);
        textValue(at($rule, 'operation'));
        names(at($rule, 'contexts'));
    }
    $compiler->conversions = [];
    foreach ($definitions as $from => $source) foreach ($definitions as $to => $target) {
        if ($from === $to) continue;
        $rule = $rules[$source['strategy'] . ':' . $target['strategy']] ?? null;
        if ($rule === null) continue;
        $operation = textValue(at($rule, 'operation'));
        if ($operation === 'integer_range') {
            requireThat(isset($types[$from], $types[$to]), 'Integer range conversion needs integer representations');
            $operation = fits($types[$to], $types[$from]['signed'], digits($types[$from])) ? 'integer_widen' : 'checked_integer';
        }
        $row = (object)['from' => $from, 'to' => $to, 'contexts' => names(at($rule, 'contexts')),
            'operation' => $operation, 'promotion_operators' => []];
        if (in_array($operation, ['checked_integer', 'floating_integer'], true)) {
            $row->target_binding = $target['conversion_binding'];
            if ($operation === 'floating_integer') $row->source_binding = $source['conversion_binding'];
        }
        $compiler->conversions[] = $row;
    }
    unset($compiler->conversion_families);
    return $compiler;
}

/** Expand active integer families at generation time; runtime and compiler share the domain resolver. */
function compilerIntegerOperations(mixed $metadata, object $compiler, array $definitions, array $types, array &$requirements): object {
    if (!isset($compiler->integer_operators)) return $compiler;
    $group = $compiler->integer_operators;
    fields($group, ['promotion', 'arithmetic', 'comparisons', 'compounds', 'bindings', 'unary', 'updates', 'condition', 'bitwise', 'shifts']);
    literals($group, ['promotion' => 'integer_domain_maximum']);
    $policy = integerPromotionPolicy($metadata, $types);
    $bitwise = names(at($group, 'bitwise'));
    requireThat(array_diff($bitwise, ['bit_and', 'bit_or', 'bit_xor']) === [], 'Unsupported integer bitwise group');
    $arithmetic = array_merge(names(at($group, 'arithmetic')), $bitwise);
    $shift = at($group, 'shifts');
    fields($shift, ['operators', 'count_type', 'bindings']);
    $shiftOperators = names(at($shift, 'operators'));
    requireThat(array_diff($shiftOperators, ['shift_left', 'shift_right']) === [], 'Unsupported integer shift group');
    $countType = textValue(at($shift, 'count_type'));
    requireThat(isset($types[$countType], $definitions[$countType]), 'Inactive shift count representation');
    requireThat(!isset($compiler->shifts), 'Duplicate shift policy owner');
    $compiler->shifts = (object)[];
    $comparisons = names(at($group, 'comparisons'));
    $compoundPolicy = at($group, 'compounds');
    fields($compoundPolicy, ['types', 'write_back']);
    literals($compoundPolicy, ['write_back' => 'checked']);
    $compound = names(at($compoundPolicy, 'types'));
    $bindings = object(at($group, 'bindings'));
    $runtime = object(at($metadata, 'operations'));
    $unary = names(at($group, 'unary'));
    $updates = names(at($group, 'updates'));
    requireThat(array_diff($unary, ['positive', 'negative', 'bit_not']) === [], 'Unsupported integer unary operator');
    requireThat(array_diff($updates, ['increment', 'decrement']) === [], 'Unsupported integer update operator');
    requireThat(at($group, 'condition') === 'nonzero_integer', 'Unsupported integer condition policy');
    foreach ($unary as $operator) {
        requireThat(at($runtime[$operator === 'negative' ? 'negate' : 'positive'] ?? null, 'promotion') === 'preserve_operand', 'Integer unary must preserve operand');
    }
    foreach ($updates as $operator) foreach (['prefix', 'postfix'] as $form) {
        requireThat(at($runtime[$form . '_' . $operator] ?? null, 'promotion') === 'preserve_operand', 'Integer update must preserve operand');
    }
    $integers = array_intersect_key($types, $definitions);
    foreach ($compound as $identity) requireThat(isset($integers[$identity]), 'Inactive integer compound identity');
    foreach ($integers as $identity => $type) {
        requireThat(!isset($compiler->arithmetic->$identity) && !isset($compiler->comparisons->$identity), 'Duplicate integer operation owner');
        requireThat(!isset($compiler->unary->$identity) && !isset($compiler->updates->$identity) && !isset($compiler->conditions->$identity), 'Duplicate integer unary/update/condition owner');
        $compiler->unary->$identity = (object)['operators' => $unary, 'result' => $identity, 'overflow' => 'native', 'lowering' => 'native'];
        $compiler->updates->$identity = (object)['operators' => $updates, 'overflow' => 'native', 'lowering' => 'native'];
        $compiler->conditions->$identity = $group->condition;
        requireThat(fits($types[$countType], $type['signed'], digits($type)), 'Shift count transport must preserve the complete source domain');
        foreach ($shiftOperators as $op) requireThat(($runtime[$op]->promotion ?? null) === 'left_with_checked_count', 'Shift policy mismatch');
        $compiler->shifts->$identity = (object)['operators' => $shiftOperators, 'counts' => array_keys($integers),
            'count_type' => $countType, 'bindings' => at(at($shift, 'bindings'), $identity)];
        $operators = [];
        foreach ($arithmetic as $op) {
            requireThat(isset($runtime[$op]) && in_array($op, ['add', 'subtract', 'multiply', 'divide', 'remainder', 'bit_and', 'bit_or', 'bit_xor'], true), 'Unsupported integer arithmetic group');
            requireThat($runtime[$op]->promotion === $group->promotion, 'Integer arithmetic policy mismatch');
            $operators[$op] = match ($op) { 'divide' => 'zero_checked_divide', 'remainder' => 'zero_checked_remainder', default => 'native' };
        }
        foreach ($comparisons as $op) requireThat(isset($runtime[$op]) && in_array($op, COMPARISONS, true) && $runtime[$op]->promotion === 'exact_integer_comparison', 'Unsupported integer comparison group');
        if ($arithmetic !== []) $compiler->arithmetic->$identity = (object)['operand' => 'same_type', 'result' => $identity, 'overflow' => 'native', 'compound' => in_array($identity, $compound, true), 'operators' => (object)$operators, 'bindings' => $bindings[$identity] ?? (object)[]];
        if ($comparisons !== []) $compiler->comparisons->$identity = (object)['operand' => 'same_type', 'result' => 'bool_t', 'lowering' => 'native', 'operators' => $comparisons];
    }
    foreach ($integers as $left => $l) foreach ($integers as $right => $r) {
        if ($left === $right) continue;
        $execution = integerExecution($l, $r, $policy);
        // All currently active pairs have a lossless common domain. Wider exact comparisons need their own lowering.
        requireThat($execution !== null || $comparisons === [], 'Active integer comparison pair needs exact mixed-domain lowering');
        if ($execution === null) continue;
        requireThat(isset($integers[$execution]) && fits($types[$execution], $l['signed'], digits($l)) && fits($types[$execution], $r['signed'], digits($r)), 'Integer execution domain is unavailable or lossy');
        foreach ([$arithmetic, $comparisons] as $index => $operators) {
            if ($operators === []) continue;
            $row = (object)['left' => $left, 'right' => $right, 'operators' => $operators, 'execution_type' => $execution, 'result' => $index === 0 ? $execution : 'bool_t'];
            if ($index === 0 && in_array($left, $compound, true)) $row->compound_operators = $operators;
            $compiler->promotions[] = $row;
            $requirements[] = ['promotion' => $row, 'policy' => 'integer'];
        }
    }
    unset($compiler->integer_operators);
    return $compiler;
}

/** Expand three-way numeric pairs using the existing domain policy and conversion permissions. */
function compilerThreeWayPromotions(mixed $metadata, object $compiler, array $definitions, array $types, array &$requirements): object {
    if (!isset($compiler->three_way)) return $compiler;
    $group = $compiler->three_way;
    fields($group, ['result', 'integer_promotion', 'mixed_promotion', 'types']);
    literals($group, ['result' => 'int64', 'integer_promotion' => 'integer_domain_maximum', 'mixed_promotion' => 'floating_operand']);
    $members = object(at($group, 'types'));
    $policy = integerPromotionPolicy($metadata, $types);
    foreach ($members as $name => $_) requireThat(isset($definitions[$name]), 'Inactive three-way type');
    foreach ($members as $left => $_) foreach ($members as $right => $_) {
        if ($left === $right) continue;
        $lk = $definitions[$left]['strategy'];
        $rk = $definitions[$right]['strategy'];
        if (!in_array($lk, ['integer', 'floating'], true) || !in_array($rk, ['integer', 'floating'], true)) continue;
        $execution = $lk === 'floating' ? $left : ($rk === 'floating' ? $right : integerExecution($types[$left], $types[$right], $policy));
        requireThat($execution !== null && isset($members[$execution]), 'Three-way execution domain unavailable');
        if ($lk === 'integer' && $rk === 'integer') {
            foreach ([$left, $right] as $source) requireThat(fits($types[$execution], $types[$source]['signed'], digits($types[$source])), 'Three-way integer promotion must be lossless');
        }
        $row = (object)['left' => $left, 'right' => $right, 'operators' => ['compare'],
            'execution_type' => $execution, 'result' => textValue(at($group, 'result'))];
        $compiler->promotions[] = $row;
        $requirements[] = ['promotion' => $row, 'policy' => 'three_way'];
    }
    return $compiler;
}

/** Expand families in explicit dependency order on a private metadata copy. */
function compilerFamilyPlan(mixed $metadata, array $definitions, array $types): object {
    $compiler = unserialize(serialize(at($metadata, 'compiler')));
    $familyConversions = isset($compiler->conversion_families);
    $compiler = compilerConversionFamilies($compiler, $definitions, $types);
    $compiler = compilerMixedNumericOperations($compiler, $definitions);
    $requirements = [];
    if ($familyConversions) {
        foreach (listValue(at($compiler, 'promotions')) as $row) {
            $requirements[] = ['promotion' => $row, 'policy' => 'family'];
        }
    }
    $compiler = compilerIntegerOperations($metadata, $compiler, $definitions, $types, $requirements);
    $compiler = compilerThreeWayPromotions($metadata, $compiler, $definitions, $types, $requirements);
    compilerPromotionPermissions($compiler, $requirements);
    return $compiler;
}

/** Finalize only expansion-owned permissions; explicit promotion rows never grant themselves permission. */
function compilerPromotionPermissions(object $compiler, array $requirements): void {
    $conversions = [];
    foreach (listValue(at($compiler, 'conversions')) as $row) {
        $key = textValue(at($row, 'from')) . ':' . textValue(at($row, 'to'));
        requireThat(!isset($conversions[$key]), 'Duplicate compiler conversion');
        $conversions[$key] = $row;
    }
    foreach ($requirements as $requirement) {
        $promotion = $requirement['promotion'];
        $policy = $requirement['policy'];
        foreach ([$promotion->left, $promotion->right] as $source) {
            if ($source === $promotion->execution_type) continue;
            $conversion = $conversions[$source . ':' . $promotion->execution_type] ?? null;
            // Family expansion may omit a conversion; the promotion validator owns that rejection.
            if ($policy === 'family' && $conversion === null) continue;
            requireThat($conversion !== null, $policy === 'integer' ? 'Integer promotion conversion missing' : 'Missing three-way conversion');
            if ($policy === 'integer') {
                requireThat($conversion->operation === 'integer_widen', 'Integer promotion needs a lossless conversion');
            } elseif ($policy === 'three_way') {
                requireThat(in_array($conversion->operation, ['integer_widen', 'numeric_floating'], true), 'Invalid three-way promotion conversion');
            }
            $conversion->promotion_operators = array_values(array_unique(array_merge(
                $conversion->promotion_operators, names($promotion->operators))));
        }
    }
}
