<?php
declare(strict_types=1);
namespace Scpp\Api;

/** Compute exact decimal boundaries during generation without host overflow or a big-integer dependency. */
function decimalPowerOfTwo(int $exponent): string {
    $value = '1';
    for ($power = 0; $power < $exponent; ++$power) {
        $carry = 0;
        $next = '';
        for ($i = strlen($value) - 1; $i >= 0; --$i) {
            $digit = ((int)$value[$i]) * 2 + $carry;
            $next = (string)($digit % 10) . $next;
            $carry = intdiv($digit, 10);
        }
        $value = ($carry === 0 ? '' : (string)$carry) . $next;
    }
    return $value;
}

/** Subtract one from a positive decimal magnitude; used for the signed maximum. */
function decimalPredecessor(string $value): string {
    for ($i = strlen($value) - 1; $i >= 0; --$i) {
        if ($value[$i] !== '0') {
            $value[$i] = (string)((int)$value[$i] - 1);
            return ltrim($value, '0') ?: '0';
        }
        $value[$i] = '9';
    }
    throw new \LogicException('Expected positive decimal magnitude');
}

/** Resolve a public storage/wrapper type; executable construction remains a lowering strategy. */
function compilerTypeBinding(mixed $compiler, string $name, string $identity, bool $extract): array {
    $row = at(at($compiler, 'runtime_bindings'), $name);
    fields($row, ['kind', 'identity', 'symbol', 'header', 'extract']);
    literals($row, ['kind' => 'type', 'identity' => $identity]);
    $symbol = textValue(at($row, 'symbol'));
    $header = textValue(at($row, 'header'));
    requireThat(preg_match('/^scpp::[A-Za-z_][A-Za-z0-9_]*$/D', $symbol) === 1, 'Invalid type binding symbol');
    requireThat(preg_match('~^scpp/[a-zA-Z0-9_]+(?:/[a-zA-Z0-9_]+)*\.hpp$~D', $header) === 1, 'Invalid public type binding header');
    $member = $extract && at($row, 'extract') !== null ? textValue($row->extract) : null;
    requireThat($member === null || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $member) === 1, 'Invalid type binding extraction member');
    return ['symbol' => $symbol, 'header' => $header, 'extract' => $member];
}

/** Resolve active membership, aliases and representations once for class and registry generation. */
function compilerTypePlan(mixed $metadata, array $facts, array $types, ?array $providers = null): array {
    $compiler = at($metadata, 'compiler');
    $sources = fields(at($metadata, 'source_types'), ['int', 'bool', 'null', 'float']);
    requireThat(($sources['int'] ?? null) === 'int64' && isset($types[$sources['int']]), 'Compiler int requires int64 metadata');
    $active = [];
    $membership = [];
    foreach (object(at($compiler, 'modules')) as $module => $row) {
        fields($row, ['always_available', 'types']);
        $members = names(at($row, 'types'), 'Duplicate compiler module type');
        foreach ($members as $identity) {
            requireThat(!isset($membership[$identity]), 'Duplicate compiler module ownership: ' . $identity);
            $membership[$identity] = $module;
        }
        if (boolean(at($row, 'always_available'))) {
            requireThat($module === 'default-runtime', 'Only the default runtime is supported in this compiler slice');
            foreach ($members as $identity) $active[$identity] = true;
        }
    }
    // Required roles are consumed by existing AST/literal handlers and boot-time generated class references.
    foreach (array_merge(array_values($sources), names(at($metadata, 'core_integers')), [textValue(at(at($metadata, 'string'), 'type'))]) as $required) {
        requireThat(isset($active[$required]), 'Required compiler type missing from default runtime: ' . $required);
    }
    $rows = object(at($compiler, 'value_types'));
    foreach ($active as $identity => $_) requireThat(isset($rows[$identity]), 'Missing active compiler type definition: ' . $identity);
    foreach ($rows as $identity => $_) requireThat(isset($active[$identity]), 'Inactive compiler type definition: ' . $identity);
    // Integer-first output order preserves the existing generated files and registry spelling.
    $rows = [$sources['int'] => $rows[$sources['int']]] + $rows;
    $definitions = [];
    $owners = [];
    $classes = [];
    foreach ($rows as $identity => $row) {
        fields($row, ['class', 'source_names', 'cpp_type', 'literals', 'storage_binding', 'copy_semantics', 'declarations', 'literal_family', 'member_visibility']);
        $family = at($row, 'literal_family');
        requireThat($family === null || in_array($family, ['integer', 'floating', 'string', 'keyword'], true), 'Unsupported literal family');
        $declarations = fields(at($row, 'declarations'), ['inferred_local', 'global_storage']);
        foreach (['inferred_local', 'global_storage'] as $capability) {
            $declarations[$capability] = boolean(at(at($row, 'declarations'), $capability));
        }
        // Descriptive metadata only; source-member enforcement is deferred.
        $visibility = isset($row->member_visibility) ? object($row->member_visibility) : [];
        foreach ($visibility as $member => $access) {
            requireThat(preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $member) === 1, 'Invalid compiler member name');
            requireThat(in_array($access, ['compiler_internal', 'source_visible'], true), 'Invalid compiler member visibility');
        }
        $copy = textValue(at($row, 'copy_semantics'));
        requireThat(in_array($copy, ['trivial', 'nontrivial'], true), 'Unsupported copy semantics');
        $class = textValue(at($row, 'class'));
        requireThat(preg_match('/^[a-z][a-z0-9_]*$/D', $class) === 1 && !isset($classes[$class]), 'Invalid or duplicate generated type class');
        $classes[$class] = true;
        $aliases = names(at($row, 'source_names'), 'Duplicate source type name');
        foreach ($sources as $alias => $target) if ($target === $identity) $aliases[] = $alias;
        foreach (object(at($metadata, 'integer_aliases')) as $alias => $target) if ($target === $identity) $aliases[] = $alias;
        $aliases = array_values(array_unique($aliases));
        sort($aliases, SORT_STRING);
        requireThat($aliases !== [], 'Compiler type has no source name');
        foreach ($aliases as $alias) {
            requireThat(preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $alias) === 1, 'Invalid source type name');
            requireThat(!isset($owners[$alias]) || $owners[$alias] === $identity, 'Source type name collision: ' . $alias);
            $owners[$alias] = $identity;
        }
        $storage = null;
        $literals = [];
        $kind = compilerConversionKind($metadata, $identity);
        if ($identity === at(at($metadata, 'string'), 'type')) $kind = 'string';
        // These are implementation ABI/strategy guards, not a second registration table.
        $expectedClass = match ($kind) {
            'integer', 'floating', 'string' => $identity,
            'boolean' => 'bool', 'null' => 'null',
            default => throw new \RuntimeException('Unsupported compiler type strategy'),
        };
        requireThat($class === $expectedClass, 'Unsupported generated class ABI');
        if ($kind === 'integer' && $family === 'integer') {
            requireThat($identity === $sources['int'], 'Unsupported compiler integer strategy');
            $type = $types[$identity];
            requireThat($type['signed'] && $type['bits'] === 64, 'Compiler slice requires signed int64');
            $policy = at($compiler, 'integer_literals');
            fields($policy, ['radix', 'overflow', 'cpp_suffix']);
            requireThat(unsigned(at($policy, 'radix')) === 10, 'Unsupported compiler integer radix');
            requireThat(textValue(at($policy, 'overflow')) === 'reject', 'Unsupported compiler integer overflow policy');
            $suffix = textValue(at($policy, 'cpp_suffix'));
            requireThat($suffix === 'LL', 'Unsupported signed integer literal suffix');
            requireThat($facts['native_integers']['long long']['signed'] && $facts['native_integers']['long long']['digits'] >= $type['bits'] - 1,
                'Native literal carrier cannot represent compiler integer');
            $magnitude = decimalPowerOfTwo($type['bits'] - 1);
            $maximum = decimalPredecessor($magnitude);
            $minimumCpp = '(-' . $maximum . $suffix . ' - 1' . $suffix . ')';
            $cpp = $type['representation'];
            if (in_array($identity, $aliases, true)) {
                $aliases = array_values(array_unique(array_merge([$identity], $aliases)));
            }
            $definitions[$identity] = ['literal_family' => $family, 'declarations' => $declarations, 'copy_semantics' => $copy, 'class' => $class, 'aliases' => $aliases, 'cpp' => $cpp, 'literals' => [],
                'strategy' => $kind, 'maximum' => $maximum, 'magnitude' => $magnitude, 'minimum_cpp' => $minimumCpp, 'suffix' => $suffix];
            continue;
        }
        if ($kind === 'floating') {
            requireThat(($sources['float'] ?? null) === $identity, 'Source float identity mismatch');
            $floating = at($metadata, 'floating');
            literals($floating, ['type' => 'float64', 'representation' => 'ieee_binary64']);
            $literalPolicy = at($compiler, 'floating_literals');
            fields($literalPolicy, ['radix', 'overflow', 'underflow', 'rounding']);
            requireThat(unsigned(at($literalPolicy, 'radix')) === 10, 'Unsupported floating radix');
            literals($literalPolicy, ['overflow' => 'reject', 'underflow' => 'gradual_signed_zero', 'rounding' => 'nearest_ties_even']);
            $cpp = textValue(at($row, 'cpp_type'));
            requireThat($cpp === 'double', 'Unsupported floating representation');
            requireThat($facts['double']['digits'] === 53 && $facts['double']['max_exponent'] === 1024
                && $facts['double']['iec559'] && $facts['double']['radix'] === 2, 'Compiler requires IEEE binary64');
            $literals = [];
        } elseif ($kind === 'string') {
            literals(at($metadata, 'string'), ['type' => 'string', 'source_type' => 'string',
                'storage' => 'private_std_string', 'count_type' => 'int64', 'construction' => 'explicit']);
            $storage = compilerTypeBinding($compiler, textValue(at($row, 'storage_binding')), $identity, false);
            $cpp = $storage['symbol'];
            $literals = [];
        } elseif (in_array($kind, ['boolean', 'null'], true)) {
            requireThat(($sources[$class] ?? null) === $identity, 'Source type identity mismatch');
            $value = at(at($metadata, 'values'), $identity);
            requireThat(at($value, 'representation') === ($kind === 'boolean' ? 'native_bool' : 'empty'), 'Unsupported scalar representation');
            $cpp = textValue(at($row, 'cpp_type'));
            requireThat($cpp === ($kind === 'boolean' ? 'bool' : 'decltype(nullptr)'), 'Unsupported compiler scalar representation');
            $literals = object(at($row, 'literals'));
            requireThat($literals === ($kind === 'boolean' ? ['false' => false, 'true' => true] : ['null' => null]), 'Unsupported scalar literal policy');
        } else {
            $cpp = $types[$identity]['representation'];
            $literals = [];
        }

        $definitions[$identity] = ['literal_family' => $family, 'declarations' => $declarations, 'copy_semantics' => $copy, 'class' => $class, 'aliases' => $aliases, 'cpp' => $cpp, 'literals' => $literals, 'strategy' => $kind, 'storage' => $storage];
    }
    $providers ??= submodulePlan($metadata);
    foreach ($definitions as $identity => &$definition) {
        if (!in_array($definition['strategy'], ['integer', 'boolean', 'floating'], true)) continue;
        $export = typeExport($providers, $identity);
        $definition['cpp'] = $export['symbol'];
        $definition['cpp_header'] = providerHeader($providers, $export['provider']);
    }
    unset($definition);
    return $definitions;
}

/** Validate output admission and runtime bindings once, indexed by canonical type. */
function compilerOutputPlan(mixed $compiler, array $definitions): array {
    $plan = [];
    foreach (object(at($compiler, 'output')) as $name => $row) {
        requireThat(isset($definitions[$name]), 'Output references inactive type');
        fields($row, ['strategy', 'binding']);
        $strategy = textValue(at($row, 'strategy'));
        requireThat(($strategy === 'integer' && $name === 'int64') || ($strategy === 'bytes' && $name === 'string'), 'Unsupported output strategy/type');
        $binding = at(at($compiler, 'runtime_bindings'), textValue(at($row, 'binding')));
        fields($binding, ['operand', 'strategy', 'symbol', 'header']);
        literals($binding, ['operand' => $name, 'strategy' => $strategy]);
        $symbol = textValue(at($binding, 'symbol'));
        $header = textValue(at($binding, 'header'));
        requireThat(preg_match('/^scpp::[A-Za-z_][A-Za-z0-9_]*$/D', $symbol) === 1, 'Invalid output binding symbol');
        requireThat(preg_match('~^scpp/[a-zA-Z0-9_]+(?:/[a-zA-Z0-9_]+)*\.hpp$~D', $header) === 1, 'Invalid output binding header');
        $plan[$name] = ['strategy' => $strategy, 'symbol' => $symbol, 'header' => $header];
    }
    return $plan;
}

/** Runtime type categories validate strategy mechanics; they do not grant any source conversion. */
function compilerConversionKind(mixed $metadata, string $identity): string {
    if (isset($metadata->types->$identity)) return 'integer';
    if ($identity === at(at($metadata, 'floating'), 'type')) return 'floating';
    return isset($metadata->values->$identity) ? textValue(at($metadata->values->$identity, 'kind')) : 'other';
}

/** Resolve native bindings from the runtime's canonical conversion recipes, without a publication request. */
function compilerCheckedConversionBindings(mixed $metadata, array $definitions, array $types, array $facts): array {
    $integers = array_intersect_key($types, $definitions);
    $values = [];
    $boolean = false;
    $floating = false;
    foreach ($definitions as $identity => $definition) {
        $kind = $definition['strategy'];
        if (!in_array($kind, ['boolean', 'floating'], true)) continue;
        $values[] = ['name' => $identity, 'kind' => $kind];
        $boolean = $boolean || $kind === 'boolean';
        $floating = $floating || $kind === 'floating';
    }
    $runtime = ['types' => array_values($integers), 'values' => $values, 'headers' => [], 'submodules' => submodulePlan($metadata),
        'conversions' => array_merge(integerConversions($integers, []), scalarValueConversions($metadata, $integers, $boolean, $floating))];
    prepareCheckedConversions($metadata, $facts, $runtime);
    $bindings = [];
    foreach ($runtime['checked_conversions']['functions'] ?? [] as $function) {
        $bindings[$function['source'] . ':' . $function['target']] = [
            'symbol' => 'scpp::' . $function['symbol'], 'header' => $runtime['checked_conversions']['header'],
            'source_cpp' => $definitions[$function['source']]['cpp']];
    }
    return $bindings;
}

/** Validate and index declared conversion rows once, shared by boundary and promotion generation. */
function compilerConversionPlan(mixed $metadata, array $definitions, array $types, array $facts): array {
    $bindings = compilerCheckedConversionBindings($metadata, $definitions, $types, $facts);
    $plan = [];
    $scalarPolicies = names(at($metadata, 'scalar_casts'), 'Duplicate scalar cast policy');
    foreach (listValue(at(at($metadata, 'compiler'), 'conversions')) as $row) {
        fields($row, ['from', 'to', 'contexts', 'operation', 'promotion_operators']);
        $from = textValue(at($row, 'from'));
        $to = textValue(at($row, 'to'));
        requireThat(isset($definitions[$from], $definitions[$to]), 'Conversion references inactive type');
        $key = $from . ':' . $to;
        requireThat(!isset($plan[$key]), 'Duplicate compiler conversion');
        $contexts = names(at($row, 'contexts'), 'Duplicate conversion context');
        foreach ($contexts as $context) requireThat(in_array($context, ['explicit_cast', 'typed_destination', 'assignment', 'inference', 'compound_write_back'], true), 'Unsupported conversion context');
        $operation = textValue(at($row, 'operation'));
        $sourceKind = compilerConversionKind($metadata, $from);
        $targetKind = compilerConversionKind($metadata, $to);
        // These guards describe the implemented algorithms, not an independent list of admitted type pairs.
        $valid = match ($operation) {
            'boolean_integer' => $sourceKind === 'boolean' && $targetKind === 'integer',
            'integer_boolean' => $sourceKind === 'integer' && $targetKind === 'boolean',
            'checked_integer' => $sourceKind === 'integer' && $targetKind === 'integer'
                && !$types[$to]['signed'] && $types[$to]['bits'] < 64,
            'integer_widen' => $sourceKind === 'integer' && $targetKind === 'integer'
                && fits($types[$to], $types[$from]['signed'], digits($types[$from])),
            'numeric_floating' => in_array($sourceKind, ['integer', 'boolean'], true) && $targetKind === 'floating',
            'floating_boolean' => $sourceKind === 'floating' && $targetKind === 'boolean',
            'floating_integer' => $sourceKind === 'floating' && $targetKind === 'integer' && ($types[$to]['signed'] || $types[$to]['bits'] < 64),
            default => false,
        };
        requireThat($valid, 'Unsupported compiler conversion operation');
        requireThat(array_diff($contexts, ['explicit_cast', 'typed_destination', 'compound_write_back']) === [], 'Cross-type conversions require explicit intent');
        requireThat(!in_array('compound_write_back', $contexts, true) || in_array($operation, ['checked_integer', 'integer_widen'], true), 'Compound write-back requires an integer conversion');
        $required = match ($operation) {
            'boolean_integer' => 'boolean_to_numeric_01',
            'integer_boolean', 'floating_boolean' => 'numeric_to_boolean_nonzero',
            'floating_integer' => 'floating_to_integer_truncate_checked',
            'numeric_floating' => $sourceKind === 'boolean' ? 'boolean_to_numeric_01' : 'integer_to_floating_rounding',
            default => null,
        };
        requireThat($required === null || in_array($required, $scalarPolicies, true), 'Compiler conversion lacks runtime scalar policy');
        $promotion = names(at($row, 'promotion_operators'), 'Duplicate conversion promotion operator');
        // Lossless integer widening and integer-to-float rounding need no occurrence-level checks.
        requireThat($promotion === [] || (in_array($operation, ['numeric_floating', 'integer_widen'], true) && $sourceKind === 'integer'), 'Conversion strategy does not support promotion');
        foreach ($promotion as $operator) requireThat(in_array($operator, ['compare', 'equal', 'not_equal', 'less', 'less_equal', 'greater', 'greater_equal', 'add', 'subtract', 'multiply', 'divide', 'remainder', 'bit_and', 'bit_or', 'bit_xor'], true) && (!in_array($operator, ['remainder', 'bit_and', 'bit_or', 'bit_xor'], true) || $operation === 'integer_widen'), 'Unsupported promotion operator');
        $binding = null;
        if (in_array($operation, ['checked_integer', 'floating_integer'], true)) {
            $binding = $bindings[$key] ?? null;
            requireThat($binding !== null, 'Compiler checked conversion lacks named runtime binding');
        }
        $plan[$key] = ['binding' => $binding, 'from' => $from, 'to' => $to, 'contexts' => $contexts, 'operation' => $operation, 'promotion_operators' => $promotion,
            'floating_bound' => $operation === 'floating_integer' ? decimalPowerOfTwo(digits($types[$to])) : null,
            'integer_signed' => $operation === 'floating_integer' ? $types[$to]['signed'] : null];
    }
    return $plan;
}

/** Validate condition strategies against resolved type capabilities once; rows alone grant admission. */
function compilerConditionPlan(mixed $compiler, array $definitions): array {
    $plan = [];
    foreach (object(at($compiler, 'conditions')) as $name => $rule) {
        requireThat(isset($definitions[$name]), 'Unsupported compiler condition type');
        $definition = $definitions[$name];
        $valid = match ($rule) {
            'identity' => $definition['strategy'] === 'boolean',
            'nonzero_integer' => $definition['strategy'] === 'integer',
            'nonzero_float' => $definition['strategy'] === 'floating',
            'nonempty_except_zero' => $definition['strategy'] === 'string' && $definition['storage'] !== null,
            default => false,
        };
        requireThat($valid, 'Unsupported compiler condition policy');
        $plan[$name] = ['strategy' => $rule, 'storage' => $definition['storage'] ?? null];
    }
    return $plan;
}

/** Resolve a named binary helper; metadata owns its public symbol/header and declared signature. */
function compilerArithmeticBinding(mixed $compiler, string $name, string $operand, string $result, string $strategy, ?string $count = null): array {
    $binding = at(at($compiler, 'runtime_bindings'), $name);
    fields($binding, ['operand', 'result', 'strategy', 'symbol', 'header', 'count']);
    requireThat(($binding->count ?? null) === $count, 'Invalid arithmetic binding count representation');
    literals($binding, ['operand' => $operand, 'result' => $result, 'strategy' => $strategy]);
    $symbol = textValue(at($binding, 'symbol'));
    $header = textValue(at($binding, 'header'));
    requireThat(preg_match('/^scpp::[A-Za-z_][A-Za-z0-9_]*$/D', $symbol) === 1, 'Invalid arithmetic binding symbol');
    requireThat(preg_match('~^scpp/[a-zA-Z0-9_]+(?:/[a-zA-Z0-9_]+)*\.hpp$~D', $header) === 1, 'Invalid public arithmetic binding header');
    return ['symbol' => $symbol, 'header' => $header];
}

/** Validate reviewed operation-local safety; operand effects remain the emitter's responsibility. */
function compilerLoweringSafety(mixed $compiler): array {
    $safety = [];
    foreach (object(at($compiler, 'lowering_strategies')) as $strategy => $row) {
        requireThat(in_array($strategy, ['native', 'runtime_compare', 'zero_checked_divide', 'zero_checked_remainder', 'checked_shift', 'runtime_concat', 'canonical_identity', 'native_three_way', 'checked_three_way', 'string_three_way', 'checked_power'], true), 'Unsupported lowering safety strategy');
        fields($row, ['reorderable']);
        $safe = boolean(at($row, 'reorderable'));
        requireThat(!$safe || $strategy === 'native', 'Checked lowering cannot be reorderable');
        $safety[$strategy] = $safe;
    }
    return $safety;
}

/** Every admitted strategy needs an explicit safety declaration, including conservative ones. */
function compilerStrategyReorderable(array $safety, string $strategy): bool {
    requireThat(array_key_exists($strategy, $safety), 'Missing lowering safety strategy: ' . $strategy);
    return $safety[$strategy];
}

/** Resolve value-comparison mechanics for independently admitted ordinary or identity operators. */
function compilerComparisonStrategy(mixed $compiler, array $definitions, string $name, array $operators, ?string $binding, array $stringOperations): array {
    requireThat(isset($definitions[$name]) && in_array($name, ['int64', 'float64', 'bool_t', 'uint8', 'string', 'null_t'], true), 'Unsupported compiler comparison type');
    $resultBinding = $name === 'string' ? compilerTypeBinding($compiler, $binding, 'bool_t', true) : null;
    $storage = $name === 'string' ? $definitions[$name]['storage'] : null;
    requireThat(isset($definitions['bool_t']), 'Comparison result must be active');
    $spelling = ['equal' => '==', 'not_equal' => '!=', 'less' => '<', 'less_equal' => '<=', 'greater' => '>', 'greater_equal' => '>='];
    $resolved = [];
    foreach ($operators as $operator) {
        requireThat(isset($spelling[$operator]), 'Unsupported comparison operator');
        requireThat(!in_array($name, ['bool_t', 'null_t'], true) || in_array($operator, ['equal', 'not_equal'], true), 'Ordering unavailable for bool/current narrow integer slice');
        if ($name === 'string') {
            requireThat(in_array($operator, $stringOperations, true), 'Missing runtime string comparison');
        }
        $resolved[$operator] = $spelling[$operator];
    }
    return ['operand' => 'same_type', 'result' => 'bool_t',
        'lowering' => $name === 'string' ? 'runtime_compare' : 'native', 'operators' => $resolved,
        'result_binding' => $resultBinding, 'storage' => $storage];
}

/** Normalize ordinary comparison admission without consulting strict-identity policy. */
function compilerComparisonPlan(mixed $compiler, array $definitions, array $stringOperations): array {
    $plan = [];
    foreach (object(at($compiler, 'comparisons')) as $name => $row) {
        requireThat(isset($definitions[$name]) && in_array($name, ['int64', 'float64', 'bool_t', 'uint8', 'string', 'null_t'], true), 'Unsupported compiler comparison type');
        fields($row, ['operand', 'result', 'lowering', 'operators', 'result_binding']);
        literals($row, ['operand' => 'same_type', 'result' => 'bool_t', 'lowering' => $name === 'string' ? 'runtime_compare' : 'native']);
        $binding = $name === 'string' ? textValue(at($row, 'result_binding')) : null;
        $plan[$name] = compilerComparisonStrategy($compiler, $definitions, $name,
            names(at($row, 'operators'), 'Duplicate comparison operator'), $binding, $stringOperations);
    }
    return $plan;
}

/** Validate execution operations once; direct lowering and promotion admission share this plan. */
function compilerBinaryPlan(mixed $compiler, array $definitions, array $comparisons, array $types, array $stringOperations): array {
    $plan = [];
    foreach ($comparisons as $identity => $comparison) {
        foreach ($comparison['operators'] as $op => $spelling) {
            $plan[$identity][$op] = ['result' => $comparison['result'], 'lowering' => $comparison['lowering'], 'spelling' => $spelling, 'result_binding' => $comparison['result_binding'], 'storage' => $comparison['storage']];
        }
    }
    if (isset($compiler->concatenation)) {
        $row = $compiler->concatenation;
        fields($row, ['operand', 'result', 'lowering', 'compound', 'operators']);
        literals($row, ['operand' => 'string', 'result' => 'string', 'lowering' => 'runtime_concat']);
        requireThat(isset($definitions['string']) && in_array('add', $stringOperations, true), 'Missing runtime string concatenation');
        $compound = boolean(at($row, 'compound'));
        foreach (object(at($row, 'operators')) as $op => $flavors) {
            requireThat(in_array($op, ['add', 'concatenate'], true), 'Unsupported concatenation operator');
            $flavors = names($flavors);
            requireThat($flavors !== [] && array_diff($flavors, $op === 'add' ? ['cpp', 'js'] : ['php']) === [], 'Invalid concatenation flavors');
            $plan['string'][$op] = ['result' => 'string', 'lowering' => 'runtime_concat', 'spelling' => '+',
                'storage' => $definitions['string']['storage'], 'flavors' => $flavors, 'compound' => $compound];
        }
    }
    $arithmeticSpelling = ['add' => '+', 'subtract' => '-', 'multiply' => '*', 'divide' => '/', 'remainder' => '%', 'bit_and' => '&', 'bit_or' => '|', 'bit_xor' => '^'];
    foreach (object(at($compiler, 'arithmetic')) as $name => $row) {
        requireThat(isset($definitions[$name]) && in_array($definitions[$name]['strategy'], ['integer', 'floating'], true), 'Unsupported compiler arithmetic type');
        fields($row, ['operand', 'result', 'overflow', 'compound', 'operators', 'bindings']);
        literals($row, ['operand' => 'same_type', 'result' => $name, 'overflow' => 'native']);
        requireThat(is_bool(at($row, 'compound')), 'Compound policy must be boolean');
        foreach (object(at($row, 'operators')) as $op => $lowering) {
            requireThat(isset($arithmeticSpelling[$op]) && ($definitions[$name]['strategy'] !== 'floating' || in_array($op, ['add', 'subtract', 'multiply', 'divide'], true)), 'Unsupported arithmetic operator');
            $expected = match ($op) { 'divide' => 'zero_checked_divide', 'remainder' => 'zero_checked_remainder', default => 'native' };
            requireThat($lowering === $expected, 'Unsupported arithmetic lowering policy');
            $binding = null;
            if ($lowering !== 'native') {
                $binding = compilerArithmeticBinding($compiler, textValue(at(at($row, 'bindings'), $op)), $name, textValue(at($row, 'result')), $lowering);
            }
            $plan[$name][$op] = ['result' => textValue(at($row, 'result')), 'lowering' => $lowering, 'spelling' => $arithmeticSpelling[$op], 'binding' => $binding];
        }
    }
    foreach (object($compiler->shifts ?? (object)[]) as $name => $row) {
        fields($row, ['operators', 'counts', 'count_type', 'bindings']);
        requireThat(($definitions[$name]['strategy'] ?? null) === 'integer', 'Shift left must be integer');
        $counts = names(at($row, 'counts'));
        $transport = textValue(at($row, 'count_type'));
        requireThat(isset($types[$transport], $definitions[$transport]), 'Invalid shift count transport');
        foreach ($counts as $count) {
            requireThat(($definitions[$count]['strategy'] ?? null) === 'integer', 'Shift count must be integer');
            requireThat(fits($types[$transport], $types[$count]['signed'], digits($types[$count])), 'Shift count transport would narrow');
        }
        foreach (names(at($row, 'operators')) as $op) {
            requireThat(in_array($op, ['shift_left', 'shift_right'], true), 'Unsupported shift operator');
            $binding = compilerArithmeticBinding($compiler, textValue(at(at($row, 'bindings'), $op)), $name, $name, 'checked_shift', textValue(at($row, 'count_type')));
            $plan[$name][$op] = ['result' => $name, 'lowering' => 'checked_shift', 'counts' => $counts, 'binding' => $binding];
        }
    }
    if (isset($compiler->power)) {
        $row = $compiler->power;
        fields($row, ['operand', 'result', 'compound', 'lowering', 'binding']);
        literals($row, ['operand' => 'int64', 'result' => 'int64', 'lowering' => 'checked_power']);
        requireThat(isset($definitions['int64']), 'Inactive power operand');
        $plan['int64']['power'] = ['result' => 'int64', 'lowering' => 'checked_power',
            'compound' => boolean(at($row, 'compound')),
            'binding' => compilerArithmeticBinding($compiler, textValue(at($row, 'binding')), 'int64', 'int64', 'checked_power')];
    }
    if (isset($compiler->three_way)) {
        $result = textValue(at($compiler->three_way, 'result'));
        requireThat(isset($definitions[$result]) && $definitions[$result]['strategy'] === 'integer', 'Invalid three-way result');
        foreach (object(at($compiler->three_way, 'types')) as $name => $row) {
            fields($row, ['lowering', 'binding', 'result_binding']);
            $strategy = textValue(at($row, 'lowering'));
            $expected = match ($definitions[$name]['strategy']) {
                'integer' => 'native_three_way', 'floating' => 'checked_three_way', 'string' => 'string_three_way',
                default => throw new \RuntimeException('Unsupported three-way operand'),
            };
            requireThat($strategy === $expected, 'Invalid three-way lowering');
            $operation = ['result' => $result, 'lowering' => $strategy, 'compound' => false];
            if ($strategy === 'checked_three_way') $operation['binding'] = compilerArithmeticBinding($compiler, textValue(at($row, 'binding')), $name, $result, $strategy);
            if ($strategy === 'string_three_way') {
                requireThat(in_array('less', $stringOperations, true) && in_array('greater', $stringOperations, true), 'Missing string three-way comparison');
                $operation['storage'] = $definitions[$name]['storage'];
                $operation['result_binding'] = compilerTypeBinding($compiler, textValue(at($row, 'result_binding')), 'bool_t', true);
            }
            $plan[$name]['compare'] = $operation;
        }
    }
    if (isset($compiler->strict_identity)) {
        $row = $compiler->strict_identity;
        fields($row, ['types', 'same_type', 'different_type', 'result_binding']);
        literals($row, ['same_type' => 'value_equality', 'different_type' => 'unequal']);
        $peers = names(at($row, 'types'));
        foreach ($peers as $name) {
            requireThat(isset($definitions[$name]), 'Inactive strict identity type');
            $binding = $definitions[$name]['strategy'] === 'string' ? textValue(at($row, 'result_binding')) : null;
            $comparison = compilerComparisonStrategy($compiler, $definitions, $name,
                ['equal', 'not_equal'], $binding, $stringOperations);
            foreach (['identical' => 'equal', 'not_identical' => 'not_equal'] as $op => $ordinary) {
                $plan[$name][$op] = ['result' => $comparison['result'], 'lowering' => $comparison['lowering'],
                    'spelling' => $comparison['operators'][$ordinary], 'result_binding' => $comparison['result_binding'],
                    'storage' => $comparison['storage'], 'identity_peers' => $peers, 'different_result' => $op === 'not_identical'];
            }
        }
    }
    return $plan;
}

/** Validate explicitly admitted scalar updates once and index operators by canonical type. */
function compilerUpdatePlan(mixed $compiler, array $definitions): array {
    $plan = [];
    foreach (object(at($compiler, 'updates')) as $name => $row) {
        requireThat(isset($definitions[$name]) && in_array($definitions[$name]['strategy'], ['integer', 'floating'], true), 'Unsupported compiler update type');
        fields($row, ['operators', 'overflow', 'lowering']);
        requireThat(at($row, 'overflow') === 'native' && at($row, 'lowering') === 'native', 'Unsupported compiler update policy');
        $operators = names(at($row, 'operators'), 'Duplicate compiler update operator');
        foreach ($operators as $operator) {
            requireThat(in_array($operator, ['increment', 'decrement'], true), 'Unsupported compiler update operator');
        }
        $plan[$name] = $operators;
    }
    return $plan;
}

/** Validate numeric prefix policies once and index admitted operators by canonical type. */
function compilerUnaryPlan(mixed $compiler, array $definitions): array {
    $plan = [];
    foreach (object(at($compiler, 'unary')) as $name => $row) {
        requireThat(isset($definitions[$name]) && in_array($definitions[$name]['strategy'], ['integer', 'floating'], true), 'Unsupported compiler unary type');
        fields($row, ['result', 'overflow', 'lowering', 'operators']);
        literals($row, ['result' => $name, 'overflow' => 'native', 'lowering' => 'native']);
        $operators = names(at($row, 'operators'), 'Duplicate unary operator');
        foreach ($operators as $operator) {
            requireThat((in_array($operator, ['positive', 'negative'], true) || ($operator === 'bit_not' && $definitions[$name]['strategy'] === 'integer')), 'Unsupported unary operator');
        }
        $plan[$name] = $operators;
    }
    return $plan;
}

/** Validate promotions once, including conversion permissions and compound write-back; index by left-hand type. */
function compilerPromotionPlan(mixed $compiler, array $conversions, array $definitions, array $binary): array {
    $plan = [];
    $seen = [];
    foreach (listValue(at($compiler, 'promotions')) as $row) {
        fields($row, ['left', 'right', 'operators', 'execution_type', 'result', 'compound_operators']);
        $left = textValue(at($row, 'left'));
        $right = textValue(at($row, 'right'));
        $execution = textValue(at($row, 'execution_type'));
        $result = textValue(at($row, 'result'));
        foreach ([$left, $right, $execution, $result] as $name) requireThat(isset($definitions[$name]), 'Promotion references inactive type');
        requireThat($left !== $right, 'Promotion requires different operand types');
        $lc = compilerPromotionConversion($conversions, $left, $execution);
        $rc = compilerPromotionConversion($conversions, $right, $execution);
        $operators = names(at($row, 'operators'), 'Duplicate promotion operator');
        foreach ($operators as $operator) {
            foreach ([$lc, $rc] as $conversion) {
                requireThat($conversion === null || in_array($operator, $conversion['promotion_operators'], true), 'Unsupported promotion operator');
            }
            $operation = $binary[$execution][$operator] ?? null;
            requireThat($operation !== null, 'Promotion execution operation unavailable');
            requireThat($operation['result'] === $result, 'Promotion execution/result mismatch');
            $key = $left . ':' . $right . ':' . $operator;
            requireThat(!isset($seen[$key]), 'Duplicate promotion');
            $seen[$key] = true;
        }
        $compoundOperators = names($row->compound_operators ?? [], 'Duplicate compound promotion operator');
        $writeBack = 'identity';
        if ($compoundOperators !== [] && $left !== $execution) {
            $conversion = $conversions[$execution . ':' . $left] ?? null;
            requireThat($conversion !== null && in_array('compound_write_back', $conversion['contexts'], true), 'Compound write-back conversion is not authorized');
            $writeBack = match ($conversion['operation']) {
                'checked_integer' => 'integer_checked', 'integer_widen' => 'integer_widen',
                default => throw new \RuntimeException('Unsupported compound write-back strategy'),
            };
        }
        foreach ($compoundOperators as $operator) {
            requireThat(in_array($operator, $operators, true), 'Compound requires an admitted promotion operator');
            requireThat($result === $execution, 'Compound result must match arithmetic execution');
            requireThat(isset($compiler->arithmetic->$execution->operators->$operator), 'Compound requires arithmetic execution');
        }
        if ($operators === []) continue;
        $plan[$left][] = ['right' => $right, 'execution' => $execution, 'result' => $result,
            'operators' => $operators, 'compound_operators' => $compoundOperators,
            'left_conversion' => $lc['operation'] ?? 'identity', 'right_conversion' => $rc['operation'] ?? 'identity',
            'write_back' => $writeBack];
    }
    return $plan;
}

/** Resolve a direct operand-to-execution conversion; checked promotion preparation remains unsupported. */
function compilerPromotionConversion(array $conversions, string $source, string $execution): ?array {
    if ($source === $execution) return null;
    $conversion = $conversions[$source . ':' . $execution] ?? null;
    requireThat($conversion !== null, 'Promotion requires a defined conversion');
    requireThat($conversion['promotion_operators'] !== [], 'Conversion does not authorize promotion');
    return $conversion;
}
