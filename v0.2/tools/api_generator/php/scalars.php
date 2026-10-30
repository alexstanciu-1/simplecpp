<?php
declare(strict_types=1);
namespace Scpp\Api;

const METADATA_FIELDS = ['version', 'types', 'representation', 'construction', 'promotion_policies', 'operations', 'values', 'source_types', 'aliases', 'floating', 'scalar_casts', 'string', 'vector', 'carriers', 'ownership', 'hash', 'datetime', 'boxed', 'json'];
const SCALAR_FIELDS = ['version', 'namespace', 'types', 'operators', 'headers', 'values', 'short_aliases', 'modules'];
const SYMBOLS = ['add' => '+', 'subtract' => '-', 'multiply' => '*', 'divide' => '/', 'remainder' => '%', 'bit_and' => '&', 'bit_or' => '|', 'bit_xor' => '^', 'equal' => '==', 'not_equal' => '!=', 'less' => '<', 'less_equal' => '<=', 'greater' => '>', 'greater_equal' => '>=', 'positive' => '+', 'negate' => '-', 'bit_not' => '~', 'shift_left' => '<<', 'shift_right' => '>>', 'increment' => '++', 'decrement' => '--'];
const COMPARISONS = ['equal', 'not_equal', 'less', 'less_equal', 'greater', 'greater_equal'];
function digits(array $type): int {
    return $type['bits'] - (int)$type['signed'];
}

function fits(array $target, bool $signed, int $digits): bool {
    return (!$signed || $target['signed']) && digits($target) >= $digits;
}

function registry(mixed $metadata, array $facts): array {
    $r = at($metadata, 'representation');
    fields($r, ['binding', 'default_initialization']);
    literals($r, ['binding' => 'fixed_width_integer', 'default_initialization' => 'zero']);
    $c = at($metadata, 'construction');
    fields($c, ['public_integer_inputs', 'native_integer_inputs', 'native_conversion', 'explicit']);
    literals($c, ['public_integer_inputs' => 'checked', 'native_conversion' => 'checked']);
    requireThat(boolean(at($c, 'explicit')), 'Integer constructors must be explicit');
    $types = [];
    foreach (object(at($metadata, 'types')) as $name => $row) {
        fields($row, ['signed', 'bits']);
        $signed = boolean(at($row, 'signed'));
        $bits = unsigned(at($row, 'bits'));
        requireThat(in_array($bits, [8, 16, 32, 64], true), 'Unsupported integer width');
        requireThat($name === ($signed ? 'int' : 'uint') . $bits, 'Integer identity disagrees with width/signedness');
        $native = $facts['fixed_integers'][$name] ?? null;
        requireThat($native !== null && $native['signed'] === $signed && $native['digits'] === $bits - (int)$signed, 'Native fixed-width mismatch');
        $types[$name] = ['name' => $name, 'representation' => '__' . ($signed ? 'INT':'UINT') . $bits . '_TYPE__', 'signed' => $signed, 'bits' => $bits];
    }
    requireThat($types !== [], 'Expected integer type definitions');
    $native = $facts['fixed_integers']['uint8'];
    requireThat(!$native['signed'] && $native['digits'] === 8, 'Byte requires unsigned eight-bit storage');
    $types['byte'] = ['name' => 'byte', 'representation' => '__UINT8_TYPE__', 'signed' => false, 'bits' => 8];
    return $types;
}

function nativeInputs(mixed $metadata, array $facts): array {
    $out = [];
    foreach (names(at(at($metadata, 'construction'), 'native_integer_inputs'), 'Duplicate native integer input') as $cpp) {
        requireThat(isset($facts['native_integers'][$cpp]), 'Unsupported native integer input');
        $n = $facts['native_integers'][$cpp];
        $out[] = ['cpp' => $cpp, 'signed' => $n['signed'], 'digits' => $n['digits']];
    }
    requireThat($out !== [], 'Empty native integer input set');
    return $out;
}

function conversion(string $target, string $source, bool $native, bool $lossless, string $implementation, bool $canFail): array {
    return ['target' => $target, 'source' => $source, 'native_source' => $native, 'lossless' => $lossless, 'implementation' => $implementation, 'can_fail' => $canFail, 'source_category' => in_array($implementation, ['parse_decimal', 'parse_boolean_text'], true) ? 'const_reference' : 'value'];
}

function integerConversions(array $types, array $natives): array {
    $out = [];
    foreach ($types as $target) {
        foreach ($natives as $source) {
            $lossless = fits($target, $source['signed'], $source['digits']);
            $out[] = conversion($target['name'], $source['cpp'], true, $lossless, 'checked_integer_cast', !$lossless);
        }
        foreach ($types as $source) {
            if ($source['name'] === $target['name']) {
                continue;
            }
            $lossless = fits($target, $source['signed'], digits($source));
            $out[] = conversion($target['name'], $source['name'], false, $lossless, 'checked_integer_cast', !$lossless);
        }
    }
    sortConversions($out);
    return $out;
}

function sortConversions(array &$rows): void {
    usort($rows, fn(array $a, array $b): int => [$a['target'], $a['source']] <=> [$b['target'], $b['source']]);
}

function hasCast(array $plan, string $target, string $source, bool $native = false): bool {
    foreach ($plan['conversions'] as $row) {
        if ($row['target'] === $target && $row['source'] === $source && $row['native_source'] === $native) {
            return true;
        }
    }
    return false;
}

function hasType(array $plan, string $name): bool {
    return in_array($name, array_column($plan['types'], 'name'), true);
}

function hasValue(array $plan, string $name): bool {
    return in_array($name, array_column($plan['values'], 'name'), true);
}

function hasNative(array $plan, string $name): bool {
    return in_array($name, array_column($plan['native_inputs'], 'cpp'), true);
}

function scalarHeaders(mixed $publication): array {
    $headers = at($publication, 'headers');
    $keys = ['types', 'values', 'operators', 'short_names', 'native_bridge'];
    fields($headers, $keys);
    $out = [];
    foreach ($keys as $key) {
        if ($key === 'native_bridge' && !property_exists($headers, $key)) continue;
        $path = headerPath(at($headers, $key));
        requireThat(!in_array($path, $out, true), 'Conflicting public headers');
        $out[$key] = $path;
    }
    return $out;
}

function bindings(mixed $rows, bool $floating): array {
    $out = [];
    foreach (object($rows) as $name => $row) {
        $base = $name;
        $form = 'binary';
        if (str_ends_with($base, '_assign')) {
            $base = substr($base, 0, - 7);
            $form = 'compound';
        }
        elseif (str_starts_with($base, 'prefix_')) {
            $base = substr($base, 7);
            $form = 'prefix';
        }
        elseif (str_starts_with($base, 'postfix_')) {
            $base = substr($base, 8);
            $form = 'postfix';
        }
        requireThat(isset(SYMBOLS[$base]), 'Unknown integer operation binding');
        $comparison = in_array($base, COMPARISONS, true);
        $unary = in_array($base, ['positive', 'negate', 'bit_not', 'increment', 'decrement'], true);
        $shift = in_array($base, ['shift_left', 'shift_right'], true);
        $promotion = $comparison ? 'exact_integer_comparison' : ($unary ? 'preserve_operand' : ($shift ? 'left_with_checked_count' : 'integer_domain_maximum'));
        $increment = in_array($base, ['increment', 'decrement'], true);
        requireThat(match($form) {
            'prefix', 'postfix' => $increment, 'compound' => !$comparison && !$unary, default => !$increment
        }, 'Unknown integer operation binding');
        if ($unary && $form === 'binary') {
            $form = 'unary';
        }
        $symbol = SYMBOLS[$base] . ($form === 'compound' ? '=' : '');
        $result = match($form) {
            'compound', 'prefix' => 'left_reference', 'postfix' => 'old_value', 'unary' => 'operand_type', default => $comparison ? 'boolean':($shift ? 'left_type':'execution_type')
        };
        $native = false;
        fields($row, ['public_operator', 'promotion', 'implementation', 'result', 'native_arguments', 'form']);
        if (property_exists($row, 'native_arguments')) {
            requireThat($promotion === 'integer_domain_maximum', 'Native adapters require arithmetic normalization');
            fields($row->native_arguments, ['policy', 'inputs']);
            literals($row->native_arguments, ['policy' => 'type_range', 'inputs' => 'native_integer_inputs']);
            $native = true;
        }
        if ($floating) {
            requireThat(!$shift && !str_starts_with($base, 'bit_') && $base !== 'remainder', 'Unsupported floating operation');
            $promotion = 'floating_exact_integer';
        }
        $implementation = ($floating ? 'floating_' : 'integer_') . $base;
        literals($row, ['public_operator' => $symbol, 'promotion' => $promotion, 'implementation' => $implementation, 'form' => $form, 'result' => $result]);
        $out[$name] = compact('symbol', 'promotion', 'implementation', 'form', 'comparison', 'native');
    }
    requireThat($floating || $out !== [], 'Expected integer operations');
    return $out;
}

function prepareScalars(mixed $metadata, mixed $publication, array $facts, bool $integerOnly = false): array {
    fields($metadata, METADATA_FIELDS);
    fields($publication, SCALAR_FIELDS);
    requireThat(unsigned(at($metadata, 'version')) === 1 && unsigned(at($publication, 'version')) === 1, 'Unsupported metadata version');
    literals($publication, ['namespace' => 'scpp']);
    $registry = registry($metadata, $facts);
    $natives = nativeInputs($metadata, $facts);
    $headers = scalarHeaders($publication);
    $modules = property_exists($publication, 'modules') ? names($publication->modules, 'Duplicate module') : [];
    foreach ($modules as $module) {
        requireThat($module === 'numeric', 'Unknown scalar module');
    }
    $types = [];
    foreach (names(at($publication, 'types'), 'Duplicate published type') as $name) {
        requireThat(isset($registry[$name]), 'Unknown published integer type');
        requireThat(in_array($name, ['int64', 'byte'], true) || in_array('numeric', $modules, true), 'Integer width requires numeric module');
        $types[$name] = $registry[$name];
    }
    $plan = ['kind' => 'prepared_runtime_api', 'types' => array_values($types), 'native_inputs' => $natives, 'values' => [], 'conversions' => integerConversions($types, $natives), 'operations' => [], 'source_types' => new \stdClass(), 'aliases' => new \stdClass(), 'short_aliases' => [], 'headers' => $headers, 'vectors' => [], 'carriers' => []];
    if ($integerOnly) {
        foreach (['operators', 'values', 'short_aliases'] as $key) {
            requireThat(listValue(at($publication, $key)) === [], 'Integer construction batch requires empty operators, values and short_aliases');
        }
        requireThat($types !== [], 'Empty integer construction publication');
        return ['kind' => 'prepared_integer_construction', 'schema' => 1, 'types' => $plan['types'], 'native_inputs' => $natives, 'conversions' => $plan['conversions']];
    }
    $policies = at($metadata, 'promotion_policies');
    fields($policies, ['integer_domain_maximum']);
    $policy = at($policies, 'integer_domain_maximum');
    fields($policy, ['same_type', 'different_signed', 'different_unsigned', 'signed_and_unsigned_below_64_bits', 'signed_and_uint64']);
    literals($policy, ['same_type' => 'preserve', 'signed_and_uint64' => 'reject']);
    foreach (['different_signed' => true, 'different_unsigned' => false, 'signed_and_unsigned_below_64_bits' => true] as $key => $signed) {
        $name = textValue(at($policy, $key));
        requireThat(isset($registry[$name]) && $registry[$name]['bits'] === 64 && $registry[$name]['signed'] === $signed, 'Domain maximum must be a registered 64-bit type with matching signedness');
    }
    $integerOps = bindings(at($metadata, 'operations'), false);
    $valueTypes = [];
    foreach (object(at($metadata, 'values')) as $name => $row) {
        fields($row, ['kind', 'representation', 'construction', 'condition', 'operations']);
        $kind = textValue(at($row, 'kind'));
        requireThat(($name === 'bool_t' && $kind === 'boolean') || ($name === 'null_t' && $kind === 'null'), 'Unsupported simple value identity');
        $representation = $kind === 'boolean' ? 'native_bool' : 'empty';
        literals($row, ['representation' => $representation, 'construction' => $kind === 'boolean' ? 'explicit':'default']);
        if ($kind === 'boolean') {
            literals($row, ['condition' => 'explicit_native_bool']);
        }
        else {
            requireThat(!property_exists($row, 'condition'), 'Null has no condition conversion');
        }
        $operations = names(at($row, 'operations'), 'Duplicate simple value operation');
        foreach ($operations as $op) {
            requireThat(in_array($op, ['equal', 'not_equal'], true) || ($kind === 'boolean' && $op === 'not'), 'Unsupported simple value operation');
        }
        $valueTypes[$name] = compact('name', 'kind', 'representation', 'operations');
    }
    $floating = at($metadata, 'floating');
    fields($floating, ['type', 'representation', 'native_inputs', 'integer_inputs', 'mixed_integer', 'division_by_zero', 'operations']);
    literals($floating, ['type' => 'float64', 'representation' => 'ieee_binary64', 'integer_inputs' => 'native_integer_inputs', 'mixed_integer' => 'exact_type_range', 'division_by_zero' => 'throw']);
    $valueTypes['float64'] = ['name' => 'float64', 'kind' => 'floating', 'representation' => 'double', 'operations' => []];
    $floatingOps = bindings(at($floating, 'operations'), true);
    foreach (names(at($publication, 'values'), 'Duplicate published value type') as $name) {
        requireThat(isset($valueTypes[$name]), 'Unknown published value type');
        $plan['values'][] = $valueTypes[$name];
    }
    $bool = hasValue($plan, 'bool_t');
    $float = hasValue($plan, 'float64');
    foreach ($plan['values'] as $v) {
        requireThat($bool || $v['operations'] === [], 'Value operations require published boolean');
    }
    requireThat($types !== [] || $plan['values'] !== [], 'Empty runtime publication');
    $sources = at($metadata, 'source_types');
    fields($sources, ['int', 'bool', 'null', 'float']);
    foreach (object($sources) as $source => $target) {
        $name = textValue($target);
        $valid = $source === 'int' ? ($registry[$name]['signed'] ?? false) : (($valueTypes[$name]['kind'] ?? '') === match($source) {
            'bool' => 'boolean', 'float' => 'floating', default => 'null'
        });
        requireThat($valid, 'Source identity disagrees with kind');
        if (isset($types[$name]) || hasValue($plan, $name)) {
            $plan['source_types']->{$source} = $name;
        }
    }
    $aliases = at($metadata, 'aliases');
    fields($aliases, ['int_t', 'float_t']);
    foreach (object($aliases) as $alias => $source) {
        requireThat(textValue($source) === ($alias === 'int_t' ? 'int':'float'), 'Alias must follow the configured int/float default');
        at($sources, $source);
        if (property_exists($plan['source_types'], $source)) {
            $plan['aliases']->{$alias} = $plan['source_types']->{$source};
        }
    }
    foreach (names(at($publication, 'short_aliases'), 'Duplicate short alias') as $alias) {
        at($aliases, $alias);
        if (property_exists($plan['aliases'], $alias)) {
            $plan['short_aliases'][] = $alias;
        }
    }
    $floatInputs = names(at($floating, 'native_inputs'), 'Duplicate native floating input');
    foreach ($floatInputs as $input) {
        requireThat(in_array($input, ['float', 'double'], true), 'Unsupported native floating constructor input');
        if ($float) {
            $plan['conversions'][] = conversion('float64', $input, true, true, 'numeric_cast', false);
        }
    }
    requireThat(in_array('double', $floatInputs, true), 'float64 requires its native double constructor');
    if ($float) {
        foreach ($natives as $n) {
            $plan['conversions'][] = conversion('float64', $n['cpp'], true, $n['digits'] <= 53, 'numeric_cast', false);
        }
    }
    foreach (names(at($metadata, 'scalar_casts'), 'Duplicate scalar cast policy') as $cast) {
        requireThat(in_array($cast, ['integer_to_floating_rounding', 'floating_to_integer_truncate_checked', 'numeric_to_boolean_nonzero', 'boolean_to_numeric_01'], true), 'Unsupported scalar cast policy');
        if ($float && in_array($cast, ['integer_to_floating_rounding', 'floating_to_integer_truncate_checked'], true)) {
            foreach ($types as $t) {
                if ($t['name'] === 'byte') continue;
                $plan['conversions'][] = $cast === 'integer_to_floating_rounding' ? conversion('float64', $t['name'], false, digits($t) <= 53, 'numeric_cast', false) : conversion($t['name'], 'float64', false, false, 'floating_integer_cast', true);
            }
        }
        if ($bool && in_array($cast, ['numeric_to_boolean_nonzero', 'boolean_to_numeric_01'], true)) {
            foreach (array_merge(array_keys($types), $float ? ['float64']:[]) as $name) {
                if ($name === 'byte') continue;
                $plan['conversions'][] = $cast === 'numeric_to_boolean_nonzero' ? conversion('bool_t', $name, false, false, 'truth_cast', false) : conversion($name, 'bool_t', false, true, 'numeric_cast', false);
            }
        }
    }
    sortConversions($plan['conversions']);
    $pairs = [];
    foreach (listValue(at($publication, 'operators')) as $request) {
        fields($request, ['operation', 'operands', 'both_orders']);
        $name = textValue(at($request, 'operation'));
        $args = listValue(at($request, 'operands'));
        $binding = (in_array('float64', $args, true) ? $floatingOps : $integerOps)[$name] ?? null;
        requireThat($binding !== null, 'Unregistered numeric operation');
        $binary = in_array($binding['form'], ['binary', 'compound'], true);
        requireThat(count($args) === ($binary ? 2:1), 'Operator operand count disagrees with form');
        $operands = [];
        foreach ($args as $arg) {
            if (is_string($arg)) {
                requireThat(isset($types[$arg]) || ($float && $arg === 'float64'), 'Operator requires published operand types');
                $operands[] = ['cpp' => $arg, 'identity' => $arg, 'native' => false];
            }
            else {
                fields($arg, ['native']);
                requireThat(isset($headers['native_bridge']), 'Native operators require a native_bridge header');
                requireThat($binding['native'], 'Native arithmetic is not enabled');
                $cpp = textValue(at($arg, 'native'));
                $native = null;
                foreach ($natives as $n) {
                    if ($n['cpp'] === $cpp) {
                        $native = $n;
                    }
                }
                requireThat($native !== null, 'Unregistered native arithmetic input');
                $identity = null;
                foreach ($registry as $t) {
                    if ($t['name'] !== 'byte' && $t['signed'] === $native['signed'] && digits($t) === $native['digits']) {
                        $identity = $t['name'];
                        break;
                    }
                }
                requireThat($identity !== null, 'Native range has no exact integer identity');
                $operands[] = ['cpp' => $cpp, 'identity' => $identity, 'native' => true];
            }
        }
        $orders = [$operands];
        if (property_exists($request, 'both_orders') && boolean($request->both_orders)) {
            requireThat($binding['form'] === 'binary', 'Both orders applies only to binary value operators');
            if ($operands[0]['cpp'] !== $operands[1]['cpp']) {
                $orders[] = array_reverse($operands);
            }
        }
        foreach ($orders as $order) {
            $key = encode([$name, $order[0]['cpp'], $order[1]['cpp'] ?? '']);
            requireThat(!isset($pairs[$key]), 'Duplicate operator request');
            $pairs[$key] = true;
            $plan['operations'][] = resolveOperation($name, $binding, $order, $registry, $policy, $plan);
        }
    }
    foreach ($plan['operations'] as $op) {
        if ($op['left']['native'] || ($op['right']['native'] ?? false)) {
            $left = $op['form'] === 'compound' ? $op['left']['identity'] : $op['execution'];
            requireThat(isset($pairs[encode([$op['operation'], $left, $op['execution']])]), 'Native bridge requires published same-type execution operator');
        }
    }
    usort($plan['operations'], fn(array $a, array $b): int => [$a['operation'], $a['left']['cpp'], $a['right']['cpp'] ?? ''] <=> [$b['operation'], $b['left']['cpp'], $b['right']['cpp'] ?? '']);
    return $plan;
}

function resolveOperation(string $name, array $binding, array $args, array $registry, object $policy, array $plan): array {
    validateByteOperation($name, $args);
    $left = $args[0];
    $right = $args[1] ?? null;
    $form = $binding['form'];
    $promotion = $binding['promotion'];
    requireThat(!($left['native'] && ($right['native'] ?? false)), 'Native-only operators are not runtime overloads');
    requireThat($form !== 'compound' || !$left['native'], 'Compound assignment requires a public left operand');
    if ($promotion === 'floating_exact_integer') {
        requireThat(!$binding['comparison'] || hasValue($plan, 'bool_t'), 'Floating comparisons require published boolean');
        foreach ($args as $arg) {
            if ($arg['identity'] !== 'float64') {
                requireThat(isset($registry[$arg['identity']]) && digits($registry[$arg['identity']]) <= 53, 'Wide integer/floating operations require an explicit cast');
                requireThat(hasCast($plan, 'float64', $arg['cpp'], $arg['native']), 'Floating normalization requires an admitted cast');
            }
        }
        if ($form === 'compound' && $left['identity'] !== 'float64') {
            requireThat(hasCast($plan, $left['identity'], 'float64'), 'Floating write-back requires an admitted cast');
        }
        $execution = 'float64';
        $result = $binding['comparison'] ? 'bool_t':($form === 'compound' ? $left['identity']:'float64');
    }
    elseif (in_array($promotion, ['preserve_operand', 'left_with_checked_count'], true)) {
        requireThat(!$left['native'] && !($right['native'] ?? false), 'Unary and shift operations require runtime operands');
        $execution = $result = $left['identity'];
    }
    elseif ($promotion === 'exact_integer_comparison') {
        requireThat(hasValue($plan, 'bool_t'), 'Integer comparisons require published boolean');
        $execution = 'direct_values';
        $result = 'bool_t';
    }
    else {
        $l = $registry[$left['identity']];
        $r = $registry[$right['identity']];
        if ($l['name'] === $r['name']) {
            $execution = $l['name'];
        }
        elseif ($l['signed'] === $r['signed']) {
            $execution = $l['signed'] ? $policy->different_signed:$policy->different_unsigned;
        }
        else {
            requireThat(($l['signed'] ? $r['bits']:$l['bits']) < 64, 'Signed and uint64 arithmetic is not admitted');
            $execution = $policy->signed_and_unsigned_below_64_bits;
        }
        requireThat(hasType($plan, $execution), 'Operator requires published execution type');
        requireThat(fits($registry[$execution], $l['signed'], digits($l)) && fits($registry[$execution], $r['signed'], digits($r)), 'Lossy implicit promotion');
        $result = $form === 'compound' ? $left['identity'] : $execution;
    }
    $op = ['operation' => $name, 'symbol' => $binding['symbol'], 'implementation' => $binding['implementation'], 'form' => $form, 'promotion' => $promotion, 'execution' => $execution, 'result' => $result, 'write_back' => $form === 'compound' ? 'checked_after_calculation':'none', 'result_category' => match($form) {
        'compound', 'prefix' => 'left_reference', 'postfix' => 'old_value', default => 'value'
    }];
    foreach ($args as $i => $arg) {
        $side = $i === 0 ? 'left':'right';
        $op[$side] = $arg;
        $op[$side . '_conversion'] = $promotion === 'floating_exact_integer' && $arg['identity'] !== $execution ? 'exact_integer_to_floating' : (in_array($promotion, ['exact_integer_comparison', 'preserve_operand', 'left_with_checked_count'], true) ? 'identity' : ($arg['native'] ? 'lossless_native_integer':($arg['identity'] === $execution ? 'identity':'lossless_integer')));
    }
    return $op;
}

/** Byte has no implicit numeric participation; consumers must explicitly convert first. */
function validateByteOperation(string $name, array $args): void {
    if (!in_array('byte', array_column($args, 'identity'), true)) return;
    requireThat(!in_array(true, array_column($args, 'native'), true), 'Byte operators require explicit runtime operands');
    $left = $args[0]['identity'];
    $right = $args[1]['identity'] ?? null;
    $allowed = match ($name) {
        'bit_not' => $left === 'byte' && $right === null,
        'bit_and', 'bit_or', 'bit_xor', 'bit_and_assign', 'bit_or_assign', 'bit_xor_assign', 'equal', 'not_equal' => $left === 'byte' && $right === 'byte',
        'shift_left', 'shift_right', 'shift_left_assign', 'shift_right_assign' => $left === 'byte' && $right === 'int64',
        default => false,
    };
    requireThat($allowed, 'Byte supports only bitwise operations and same-type equality; convert explicitly for arithmetic');
}
