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
    $member = $extract ? textValue(at($row, 'extract')) : null;
    requireThat($member === null || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $member) === 1, 'Invalid type binding extraction member');
    return ['symbol' => $symbol, 'header' => $header, 'extract' => $member];
}

/** Resolve active membership, aliases and representations once for class and registry generation. */
function compilerTypePlan(mixed $metadata, array $facts, array $types): array {
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
    foreach (array_merge(array_values($sources), ['byte', textValue(at(at($metadata, 'string'), 'type'))]) as $required) {
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
        fields($row, ['class', 'source_names', 'cpp_type', 'literals', 'storage_binding', 'copy_semantics', 'declarations', 'literal_family']);
        $family = at($row, 'literal_family');
        requireThat($family === null || in_array($family, ['integer', 'floating', 'string', 'keyword'], true), 'Unsupported literal family');
        $declarations = fields(at($row, 'declarations'), ['inferred_local', 'global_storage']);
        foreach (['inferred_local', 'global_storage'] as $capability) {
            $declarations[$capability] = boolean(at(at($row, 'declarations'), $capability));
        }
        $copy = textValue(at($row, 'copy_semantics'));
        requireThat(in_array($copy, ['trivial', 'nontrivial'], true), 'Unsupported copy semantics');
        $class = textValue(at($row, 'class'));
        requireThat(preg_match('/^[a-z][a-z0-9_]*$/D', $class) === 1 && !isset($classes[$class]), 'Invalid or duplicate generated type class');
        $classes[$class] = true;
        $aliases = names(at($row, 'source_names'), 'Duplicate source type name');
        foreach ($sources as $alias => $target) if ($target === $identity) $aliases[] = $alias;
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
            'integer', 'byte', 'floating', 'string' => $identity,
            'boolean' => 'bool', 'null' => 'null',
            default => throw new \RuntimeException('Unsupported compiler type strategy'),
        };
        requireThat($class === $expectedClass, 'Unsupported generated class ABI');
        if ($kind === 'integer') {
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
    return $definitions;
}

/** Generate the supported core scalar definitions; module membership determines availability. */
function renderCompiler(mixed $metadata, array $facts): array {
    $types = registry($metadata, $facts);
    $compiler = at($metadata, 'compiler');
    fields($compiler, ['modules', 'integer_literals', 'value_types', 'conversions', 'floating_literals', 'conditions', 'comparisons', 'arithmetic', 'updates', 'unary', 'promotions', 'runtime_bindings', 'lowering_strategies', 'output']);
    $safety = compilerLoweringSafety($compiler);
    $definitions = compilerTypePlan($metadata, $facts, $types);
    $families = compilerLiteralFamilies($definitions);
    $files = [];
    $conversions = compilerConversionPlan($metadata, $definitions, $types);
    foreach ($definitions as $identity => $definition) {
        $methods = compilerDeclarations($definition['declarations']) . compilerOutput($compiler, $definitions, $identity) . compilerConversions($conversions, $definitions, $identity) . compilerConditions($compiler, $identity) . compilerBinary($compiler, $definitions, $identity, $safety) . compilerUpdates($compiler, $identity) . compilerUnary($compiler, $identity, $safety) . compilerPromotions($compiler, $conversions, $definitions, $identity);
        if ($definition['copy_semantics'] === 'trivial') {
            $methods .= "
\t/** Native scalar storage has no observable copy operation. */
\tpublic function cpp_trivial_value(): bool
\t{
\t\treturn true;
\t}
";
        }
        if ($definition['strategy'] === 'integer') {
            $accept = implode(' || ', array_map(static fn(string $alias): string => '($name === ' . var_export($alias, true) . ')', $definition['aliases']));
            $files['types/' . $definition['class'] . '.php'] = strtr(readFile(__DIR__ . '/../templates/signed_integer.php.tpl'), [
                '@name@' => $identity, '@maximum@' => $definition['maximum'], '@magnitude@' => $definition['magnitude'],
                '@accept_names@' => $accept, '@representation@' => var_export($definition['cpp'], true),
                '@cpp_minimum@' => var_export($definition['minimum_cpp'], true), '@cpp_suffix@' => var_export($definition['suffix'], true),
                '@conversion_methods@' => $methods,
            ]);
            continue;
        }
        $limits = $identity === 'byte' ? "\t/** CONSTANTS */\n\tpublic const int MINIMUM /** uint8 */ = 0;\n\tpublic const int MAXIMUM /** uint8 */ = " . decimalPredecessor(decimalPowerOfTwo($types[$identity]['bits'])) . ";\n\n" : '';
        $accept = implode(' || ', array_map(static fn(string $alias): string => '($name === ' . var_export($alias, true) . ')', $definition['aliases']));
        $literalMethods = $definition['literal_family'] === 'floating'
            ? strtr(readFile(__DIR__ . '/../templates/floating_literal_methods.php.tpl'), [
                '@minimum_exponent@' => (string)(2 - $facts['double']['max_exponent']),
                '@exponent_bias@' => (string)($facts['double']['max_exponent'] - 1),
            ]) : compilerLiteralMethods($definition);
        if ($definition['literal_family'] === 'string') $literalMethods = strtr(readFile(__DIR__ . '/../templates/string_literal_methods.php.tpl'), ['@storage_header@' => var_export($definition['storage']['header'], true)]);
        $hostChecks = $identity === 'float64' ? "\t\tif ((PHP_FLOAT_DIG !== 15) || (PHP_FLOAT_EPSILON !== 2.0 ** (1 - " . $facts['double']['digits'] . "))\n"
            . "\t\t\t|| (PHP_FLOAT_MIN !== 2.0 ** (2 - " . $facts['double']['max_exponent'] . "))) {\n"
            . "\t\t\tthrow new \\RuntimeException('Compiler host requires IEEE binary64');\n\t\t}\n" : '';
        $files['types/' . $definition['class'] . '.php'] = strtr(readFile(__DIR__ . '/../templates/scalar_type.php.tpl'), [
            '@name@' => $identity, '@class@' => $definition['class'], '@limits@' => $limits,
            '@accept_names@' => $accept, '@representation@' => var_export($definition['cpp'], true),
            '@constructor_checks@' => $hostChecks, '@literal_methods@' => $literalMethods, '@conversion_methods@' => $methods,
        ]);
    }
    $cases = [];
    $literalCases = [];
    foreach ($definitions as $definition) {
        $aliases = implode(', ', array_map(static fn(string $alias): string => var_export($alias, true), $definition['aliases']));
        $cases[] = "\t\t\t" . $aliases . ' => type_' . $definition['class'] . '::instance(),';
        foreach ($definition['literals'] ?? [] as $word => $value) {
            $literalCases[] = "\t\t\t" . var_export($word, true) . ' => type_' . $definition['class'] . '::instance(),';
        }
    }
    $files['types/registry.php'] = strtr(readFile(__DIR__ . '/../templates/type_registry.php.tpl'), [
        '@family_cases@' => $families['cases'], '@family_accessors@' => $families['accessors'],
        '@cases@' => implode("\n", $cases), '@literal_cases@' => implode("\n", $literalCases),
    ]);
    $files['inventory.json'] = encode(['generator' => 'scpp-compiler-lowering', 'version' => 16,
        'files' => array_keys($files)]) . "\n";
    return $files;
}

/** Generate literal handling from a closed, validated scalar vocabulary. */
function compilerLiteralMethods(array $definition): string {
    if ($definition['literals'] === []) return '';
    $cases = [];
    foreach ($definition['literals'] as $word => $value) {
        $cases[] = "\t\t\t" . var_export($word, true) . ' => ' . var_export($value, true) . ',';
    }
    $cases = implode("\n", $cases);
    $lower = $definition['class'] === 'bool' ? "\$syntax->constant() ? 'true' : 'false'" : "'nullptr'";
    return <<<PHP_CODE
	/** Validate only this type's literal spelling, then attach occurrence facts. */
	public function prepare_literal(ast_expression \$syntax): void
	{
		if (!(\$syntax instanceof ast_keyword_expression)) {
			parent::prepare_literal(\$syntax);
			return;
		}
		\$value = match (\$syntax->word) {
$cases
			default => throw new diagnostic(\$syntax->source(), \$syntax->byte_start(), 'Invalid scalar literal'),
		};
		\$syntax->settle_scalar(\$this, \$value);
	}

	public function lower_cpp_literal(ast_expression \$syntax): string
	{
		if (!(\$syntax instanceof ast_keyword_expression) || (\$syntax->type_def() !== \$this) || !\$syntax->constant_known()) {
			throw new \LogicException('Expected a prepared scalar literal');
		}
		return $lower;
	}
PHP_CODE;
}

/** Resolve structural-family ownership and keyword spellings before emitting direct registry dispatch. */
function compilerLiteralFamilies(array $definitions): array {
    $owners = [];
    $words = [];
    foreach ($definitions as $identity => $definition) {
        $family = $definition['literal_family'];
        if ($family === 'keyword') {
            requireThat(in_array($definition['strategy'], ['boolean', 'null'], true), 'Incompatible keyword literal strategy');
            requireThat($definition['literals'] !== [], 'Keyword family needs literal spellings');
            foreach ($definition['literals'] as $word => $_) {
                requireThat(!isset($words[$word]), 'Duplicate literal spelling: ' . $word);
                $words[$word] = $identity;
            }
        } elseif ($family !== null) {
            requireThat(!isset($owners[$family]), 'Duplicate literal family owner: ' . $family);
            requireThat($definition['strategy'] === $family, 'Incompatible literal preparation strategy');
            $owners[$family] = $definition['class'];
        } else {
            requireThat($definition['literals'] === [], 'Literal spellings require keyword family');
        }
    }
    $cases = [];
    $accessors = [];
    // AST vocabulary is compiler-owned; metadata chooses each family's canonical type.
    foreach (['integer' => 'ast_integer_expression', 'floating' => 'ast_float_expression', 'string' => 'ast_string_expression'] as $family => $node) {
        requireThat(isset($owners[$family]), 'Missing literal family owner: ' . $family);
        $class = $owners[$family];
        $cases[] = "\t\t\t\$value instanceof $node => self::$family(),";
        $accessors[] = "\t/** Canonical $family literal identity selected by metadata. */
\tpublic static function $family(): type_$class
\t{
\t\treturn type_$class::instance();
\t}";
    }
    if ($words !== []) $cases[] = "\t\t\t" . '$value instanceof ast_keyword_expression => self::literal($value->word),';
    return ['cases' => implode("\n", $cases), 'accessors' => implode("\n\n", $accessors)];
}

/** Emit declaration-policy queries, separate from representation and conversion permissions. */
function compilerDeclarations(array $declarations): string {
    $methods = '';
    foreach ($declarations as $capability => $supported) {
        $result = $supported ? 'true' : 'false';
        $methods .= "
\t/** Current compiler declaration capability; does not grant conversions. */
\tpublic function supports_$capability(): bool
\t{
\t\treturn $result;
\t}
";
    }
    return $methods;
}

/** Generate type-owned output admission and dispatch; formatting/lifetime algorithms stay in the backend. */
function compilerOutput(mixed $compiler, array $definitions, string $identity): string {
    $selected = null;
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
        if ($name === $identity) $selected = ['strategy' => $strategy, 'symbol' => $symbol, 'header' => $header];
    }
    if ($selected === null) return '';
    $header = var_export($selected['header'], true);
    $symbol = var_export($selected['symbol'], true);
    $expression = $selected['strategy'] === 'bytes' ? 'Cpp_Output::bytes($value, $operand, ' . $symbol . ')'
        : $symbol . " . '(' . \$operand . ');'";
    return "
\t/** Generated output admission; does not authorize string conversion. */
\tpublic function supports_output(): bool
\t{
\t\treturn true;
\t}

\t/** @return string[] */
\tpublic function output_headers(): array /** list<string> */
\t{
\t\treturn [$header];
\t}

\t/** Consume an approved output operand using the selected binding. */
\tpublic function lower_cpp_output(ast_expression \$value, ?string \$operand): string
\t{
\t\treturn $expression;
\t}
";
}

/** Runtime type categories validate strategy mechanics; they do not grant any source conversion. */
function compilerConversionKind(mixed $metadata, string $identity): string {
    if (isset($metadata->types->$identity)) return 'integer';
    if ($identity === 'byte') return 'byte';
    if ($identity === at(at($metadata, 'floating'), 'type')) return 'floating';
    return isset($metadata->values->$identity) ? textValue(at($metadata->values->$identity, 'kind')) : 'other';
}

/** Validate and index declared conversion rows once, shared by boundary and promotion generation. */
function compilerConversionPlan(mixed $metadata, array $definitions, array $types): array {
    $plan = [];
    $scalarPolicies = names(at($metadata, 'scalar_casts'), 'Duplicate scalar cast policy');
    foreach (listValue(at(at($metadata, 'compiler'), 'conversions')) as $row) {
        fields($row, ['from', 'to', 'contexts', 'operation', 'promotion_operators', 'source_binding', 'target_binding']);
        $from = textValue(at($row, 'from'));
        $to = textValue(at($row, 'to'));
        requireThat(isset($definitions[$from], $definitions[$to]), 'Conversion references inactive type');
        $key = $from . ':' . $to;
        requireThat(!isset($plan[$key]), 'Duplicate compiler conversion');
        $contexts = names(at($row, 'contexts'), 'Duplicate conversion context');
        foreach ($contexts as $context) requireThat(in_array($context, ['explicit_cast', 'typed_destination', 'assignment', 'inference'], true), 'Unsupported conversion context');
        $operation = textValue(at($row, 'operation'));
        $sourceKind = compilerConversionKind($metadata, $from);
        $targetKind = compilerConversionKind($metadata, $to);
        // These guards describe the implemented algorithms, not an independent list of admitted type pairs.
        $valid = match ($operation) {
            'boolean_integer' => $sourceKind === 'boolean' && $targetKind === 'integer',
            'integer_boolean' => $sourceKind === 'integer' && $targetKind === 'boolean',
            'checked_integer' => $sourceKind === 'integer' && $targetKind === 'byte',
            'integer_widen' => $sourceKind === 'byte' && $targetKind === 'integer'
                && fits($types[$to], $types[$from]['signed'], digits($types[$from])),
            'numeric_floating' => in_array($sourceKind, ['integer', 'boolean'], true) && $targetKind === 'floating',
            'floating_boolean' => $sourceKind === 'floating' && $targetKind === 'boolean',
            'floating_integer' => $sourceKind === 'floating' && $targetKind === 'integer' && $types[$to]['signed'],
            default => false,
        };
        requireThat($valid, 'Unsupported compiler conversion operation');
        requireThat(($sourceKind !== 'byte' && $targetKind !== 'byte') || array_diff($contexts, ['explicit_cast', 'typed_destination']) === [], 'Byte conversions must be explicit');
        $required = match ($operation) {
            'boolean_integer' => 'boolean_to_numeric_01',
            'integer_boolean', 'floating_boolean' => 'numeric_to_boolean_nonzero',
            'floating_integer' => 'floating_to_integer_truncate_checked',
            'numeric_floating' => $sourceKind === 'boolean' ? 'boolean_to_numeric_01' : 'integer_to_floating_rounding',
            default => null,
        };
        requireThat($required === null || in_array($required, $scalarPolicies, true), 'Compiler conversion lacks runtime scalar policy');
        $promotion = names(at($row, 'promotion_operators'), 'Duplicate conversion promotion operator');
        // Only the currently implemented unchecked rounding conversion can be settled without value checks.
        requireThat($promotion === [] || ($operation === 'numeric_floating' && $sourceKind === 'integer'), 'Conversion strategy does not support promotion');
        foreach ($promotion as $operator) requireThat(in_array($operator, ['less', 'less_equal', 'greater', 'greater_equal'], true), 'Unsupported promotion operator');
        $sourceBinding = null;
        $targetBinding = null;
        if (in_array($operation, ['checked_integer', 'floating_integer'], true)) {
            $targetBinding = compilerTypeBinding(at($metadata, 'compiler'), textValue(at($row, 'target_binding')), $to, true);
            if ($operation === 'floating_integer') {
                $sourceBinding = compilerTypeBinding(at($metadata, 'compiler'), textValue(at($row, 'source_binding')), $from, false);
            }
        }
        requireThat(!isset($row->source_binding) || $sourceBinding !== null, 'Unused source conversion binding');
        requireThat(!isset($row->target_binding) || $targetBinding !== null, 'Unused target conversion binding');
        $plan[$key] = ['source_binding' => $sourceBinding, 'target_binding' => $targetBinding, 'from' => $from, 'to' => $to, 'contexts' => $contexts, 'operation' => $operation, 'promotion_operators' => $promotion];
    }
    return $plan;
}

/** Emit direct conversion branches; no conversion matrix is interpreted by the running compiler. */
function compilerConversions(array $conversions, array $definitions, string $target): string {
    $prepare = [];
    $lower = [];
    $constants = [];
    $headerCases = [];
    foreach ($conversions as $row) {
        $from = $row['from'];
        $to = $row['to'];
        $contexts = $row['contexts'];
        $operation = $row['operation'];
        if ($to !== $target) continue;
        if ($row['target_binding'] !== null) {
            $wrapper = $row['target_binding']['symbol'];
            $extract = $row['target_binding']['extract'];
            $headers = [$row['target_binding']['header']];
            if ($row['source_binding'] !== null) $headers[] = $row['source_binding']['header'];
            $checked = $operation === 'floating_integer' ? 'floating_integer_checked' : 'integer_checked';
            $headerCases[] = "\t\t\tvalue_conversion::$checked => [" . implode(', ', array_map(static fn(string $header): string => var_export($header, true), array_unique($headers))) . '],';
        }
        $condition = '$value->type_def() === type_' . $definitions[$from]['class'] . '::instance()';
        $permission = array_map(static fn(string $context): string => '$context === conversion_context::' . $context, $contexts);
        $condition = '(' . ($permission === [] ? 'false' : implode(' || ', $permission)) . ') && (' . $condition . ')';
        $prepare[] = "\t\tif ($condition) {";
        $constantOperation = match ($operation) {
            'boolean_integer' => '$value === null ? null : (int) $value', 'integer_boolean' => '$value === null ? null : $value !== 0',
            'numeric_floating' => '$value === null ? null : (float) $value',
            'floating_boolean' => '$value === null ? null : $value != 0.0',
            'floating_integer' => '$value === null ? null : (int) $value',
            default => '$value',
        };
        $constantCases = match ($operation) {
            'checked_integer' => 'value_conversion::integer_proven, value_conversion::integer_checked',
            'floating_integer' => 'value_conversion::floating_integer_proven, value_conversion::floating_integer_checked',
            default => 'value_conversion::' . $operation,
        };
        $constants[] = "\t\t\t" . $constantCases . ' => ' . $constantOperation . ',';
        if ($operation === 'floating_integer') {
            $prepare[] = <<<'CODE'
			if ($value->constant_known()) {
				$constant = $value->constant();
				$truncated = $constant < 0.0 ? ceil($constant) : floor($constant);
				if (!is_finite($constant) || ($truncated < (float) self::MINIMUM) || ($truncated >= -(float) self::MINIMUM)) {
					throw new diagnostic($value->source(), $value->byte_start(), 'Floating conversion is outside int64 range');
				}
				return value_conversion::floating_integer_proven;
			}
			return value_conversion::floating_integer_checked;
CODE;
            $lower[] = "\t\t\tvalue_conversion::floating_integer_proven => 'static_cast<' . \$this->cpp_type() . '>(' . \$value . ')',";
            $sourceWrapper = $row['source_binding']['symbol'];
            $lower[] = "\t\t\tvalue_conversion::floating_integer_checked => '({$wrapper}{{$sourceWrapper}{' . \$value . '}}).{$extract}()',";
        } elseif ($operation === 'checked_integer') {
            $prepare[] = <<<'CODE'
			if ($value->constant_known()) {
				$constant = $value->constant();
				if (($constant < self::MINIMUM) || ($constant > self::MAXIMUM)) {
					throw new diagnostic($value->source(), $value->byte_start(), 'Conversion is outside byte range');
				}
				return value_conversion::integer_proven;
			}
			return value_conversion::integer_checked;
CODE;
            $lower[] = "\t\t\tvalue_conversion::integer_proven => 'static_cast<' . \$this->cpp_type() . '>(' . \$value . ')',";
            $lower[] = "\t\t\tvalue_conversion::integer_checked => '({$wrapper}{' . \$value . '}).{$extract}()',";
        } else {
            $prepare[] = "\t\t\treturn value_conversion::$operation;";
            $expression = in_array($operation, ['integer_boolean', 'floating_boolean'], true) ? "'(' . \$value . ' != 0)'" : "'static_cast<' . \$this->cpp_type() . '>(' . \$value . ')'";
            $lower[] = "\t\t\tvalue_conversion::$operation => $expression,";
        }
        $prepare[] = "\t\t}";
    }
    if ($prepare === []) return '';
    $prepare = implode("\n", $prepare);
    $lower = implode("\n", array_unique($lower));
    $constants = implode("\n", array_unique($constants));
    $headers = '';
    if ($headerCases !== []) {
        $cases = implode("\n", array_unique($headerCases));
        $headers = <<<PHP_CODE

	/** @return string[] Public runtime includes required by the selected conversion only. */
	public function conversion_headers(value_conversion \$conversion): array /** list<string> */
	{
		return match (\$conversion) {
$cases
			default => [],
		};
	}
PHP_CODE;
    }
    return <<<PHP_CODE
	/** Select the conversion once; the caller marks written destinations or explicit cast boundaries. */
	public function prepare_conversion(ast_expression \$value, conversion_context \$context): value_conversion
	{
$prepare
		return parent::prepare_conversion(\$value, \$context);
	}

	/** Render an approved boundary without repeating source-type resolution or changing facts. */
	public function lower_cpp_conversion(value_conversion \$conversion, string \$value): string
	{
		return match (\$conversion) {
$lower
			default => parent::lower_cpp_conversion(\$conversion, \$value),
		};
	}

	/** Preserve unknown values; convert known payloads using the selected operation only. */
	public function convert_constant(value_conversion \$conversion, int|float|bool|null \$value /** int64|float64|bool|null */): int|float|bool|null /** int64|float64|bool|null */
	{
		return match (\$conversion) {
$constants
			default => parent::convert_constant(\$conversion, \$value),
		};
	}
$headers
PHP_CODE;
}

/** Generate one direct condition operation per admitted type; absent metadata retains base rejection. */
function compilerConditions(mixed $compiler, string $identity): string {
    $conditions = object(at($compiler, 'conditions'));
    foreach ($conditions as $name => $rule) {
        requireThat(in_array($name, ['int64', 'bool_t', 'float64', 'string'], true), 'Unsupported compiler condition type');
        requireThat($rule === ['int64' => 'nonzero_integer', 'bool_t' => 'identity', 'float64' => 'nonzero_float', 'string' => 'nonempty_except_zero'][$name], 'Unsupported compiler condition policy');
    }
    if (!isset($conditions[$identity])) return '';
    $expression = match ($conditions[$identity]) {
        'identity' => '$value',
        'nonzero_integer' => "'(' . \$value . ' != 0)'",
        'nonzero_float' => "'(' . \$value . ' != 0.0)'",
        'nonempty_except_zero' => var_export("([](const scpp::string& condition_value) -> bool { return condition_value.size() != 0 && (condition_value.size() != 1 || condition_value.data()[0] != '0'); })(", true) . " . \$value . ')'",
    };
    $headers = $identity === 'string' ? "['scpp/string.hpp']" : '[]';
    return "
\t/** Generated condition admission; never an explicit bool conversion. */
\tpublic function prepare_condition(ast_expression \$value): void
\t{
\t\tif (\$value->type_def() !== \$this) {
\t\t\tthrow new \\LogicException('Condition type does not match its prepared operand');
\t\t}
\t}

\tpublic function lower_cpp_condition(string \$value): string
\t{
\t\treturn $expression;
\t}

\t/** @return string[] */
\tpublic function condition_headers(): array /** list<string> */
\t{
\t\treturn $headers;
\t}
";
}

/** Resolve a named binary helper; metadata owns its public symbol/header and declared signature. */
function compilerArithmeticBinding(mixed $compiler, string $name, string $operand, string $result, string $strategy): array {
    $binding = at(at($compiler, 'runtime_bindings'), $name);
    fields($binding, ['operand', 'result', 'strategy', 'symbol', 'header']);
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
        requireThat(in_array($strategy, ['native', 'zero_checked_divide', 'zero_checked_remainder'], true), 'Unsupported lowering safety strategy');
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

/** Generate direct same-type arithmetic/comparison admission and lowering; no per-expression metadata interpretation. */
function compilerBinary(mixed $compiler, array $definitions, string $identity, array $safety): string {
    $rows = object(at($compiler, 'comparisons'));
    $spelling = ['equal' => '==', 'not_equal' => '!=', 'less' => '<', 'less_equal' => '<=', 'greater' => '>', 'greater_equal' => '>='];
    foreach ($rows as $name => $row) {
        requireThat(isset($definitions[$name]) && in_array($name, ['int64', 'float64', 'bool_t', 'byte'], true), 'Unsupported compiler comparison type');
        fields($row, ['operand', 'result', 'lowering', 'operators']);
        literals($row, ['operand' => 'same_type', 'result' => 'bool_t', 'lowering' => 'native']);
        requireThat(isset($definitions['bool_t']), 'Comparison result must be active');
        foreach (names(at($row, 'operators'), 'Duplicate comparison operator') as $operator) {
            requireThat(isset($spelling[$operator]), 'Unsupported comparison operator');
            requireThat(!in_array($name, ['bool_t', 'byte'], true) || in_array($operator, ['equal', 'not_equal'], true), 'Ordering unavailable for bool/byte');
        }
    }
    $operations = [];
    if (isset($rows[$identity])) {
        foreach (names(at($rows[$identity], 'operators'), 'Duplicate comparison operator') as $op) {
            $operations[$op] = ['result' => 'bool_t', 'lowering' => 'native', 'spelling' => $spelling[$op]];
        }
    }
    $arithmeticSpelling = ['add' => '+', 'subtract' => '-', 'multiply' => '*', 'divide' => '/', 'remainder' => '%'];
    foreach (object(at($compiler, 'arithmetic')) as $name => $row) {
        requireThat(in_array($name, ['int64', 'float64'], true) && isset($definitions[$name]), 'Unsupported compiler arithmetic type');
        fields($row, ['operand', 'result', 'overflow', 'compound', 'operators', 'bindings']);
        literals($row, ['operand' => 'same_type', 'result' => $name, 'overflow' => 'native']);
        requireThat(is_bool(at($row, 'compound')), 'Compound policy must be boolean');
        foreach (object(at($row, 'operators')) as $op => $lowering) {
            requireThat(isset($arithmeticSpelling[$op]) && ($name !== 'float64' || $op !== 'remainder'), 'Unsupported arithmetic operator');
            $expected = match ($op) { 'divide' => 'zero_checked_divide', 'remainder' => 'zero_checked_remainder', default => 'native' };
            requireThat($lowering === $expected, 'Unsupported arithmetic lowering policy');
            $binding = null;
            if ($lowering !== 'native') {
                $binding = compilerArithmeticBinding($compiler, textValue(at(at($row, 'bindings'), $op)), $name, textValue(at($row, 'result')), $lowering);
            }
            if ($identity === $name) $operations[$op] = ['result' => textValue(at($row, 'result')), 'lowering' => $lowering, 'spelling' => $arithmeticSpelling[$op], 'binding' => $binding];
        }
    }
    if ($operations === []) return '';
    $results = '';
    $cases = '';
    $headers = [];
    $reorderable = [];
    foreach ($operations as $op => $operation) {
        $result = 'type_' . $definitions[$operation['result']]['class'];
        $results .= "\t\t\tbinary_operator::$op => $result::instance(),\n";
        if (compilerStrategyReorderable($safety, $operation['lowering'])) $reorderable[] = "binary_operator::$op";
        if ($operation['lowering'] === 'native') {
            $cases .= "\t\t\tbinary_operator::$op => '(' . \$left . ' " . $operation['spelling'] . " ' . \$right . ')',\n";
        } else {
            $symbol = $operation['binding']['symbol'];
            $cases .= "\t\t\tbinary_operator::$op => '{$symbol}(' . \$left . ', ' . \$right . ')',\n";
            $headers[$operation['binding']['header']][] = "binary_operator::$op";
        }
    }
    $compound = isset($compiler->arithmetic->$identity) && $compiler->arithmetic->$identity->compound ? 'true' : 'false';
    $safeCases = $reorderable === [] ? '' : "\t\t\t" . implode(', ', $reorderable) . " => true,\n";
    $headerMethods = '';
    if ($headers !== []) {
        $headerCases = '';
        foreach ($headers as $header => $operators) {
            $headerCases .= "\t\t\t" . implode(', ', $operators) . ' => [' . var_export($header, true) . "],\n";
        }
        $headerMethods = "
\t/** @return string[] */
\tpublic function binary_headers(binary_operator \$operator): array /** list<string> */
\t{
\t\treturn match (\$operator) {
$headerCases\t\t\tdefault => [],
\t\t};
\t}
";
    }
    return "
\t/** Compound syntax admission is separate from ordinary arithmetic capability. */
\tpublic function supports_compound(): bool
\t{
\t\treturn $compound;
\t}

\t/** Generated same-type admission; result identity follows the selected metadata operation. */
\tpublic function binary_result(binary_operator \$operator, type_definition \$right): ?type_definition
\t{
\t\tif (\$right !== \$this) {
\t\t\treturn null;
\t\t}
\t\treturn match (\$operator) {
$results\t\t\tdefault => null,
\t\t};
\t}

\t/** Lower an approved operator on already sequenced operand snapshots. */
\tpublic function lower_cpp_binary(binary_operator \$operator, string \$left, string \$right): string
\t{
\t\treturn match (\$operator) {
$cases\t\t\tdefault => throw new \\LogicException('Binary operation was not approved'),
\t\t};
\t}

\t/** Only admitted primitive native operations permit reordering of independently safe operands. */
\tpublic function cpp_binary_reorderable(binary_operator \$operator): bool
\t{
\t\treturn match (\$operator) {
$safeCases\t\t\tdefault => false,
\t\t};
\t}
$headerMethods";
}

/** Generate only explicitly admitted scalar updates; no expression result or implicit arithmetic-based admission. */
function compilerUpdates(mixed $compiler, string $identity): string {
    $selected = [];
    foreach (object(at($compiler, 'updates')) as $name => $row) {
        requireThat(in_array($name, ['int64', 'float64'], true), 'Unsupported compiler update type');
        fields($row, ['operators', 'overflow', 'lowering']);
        requireThat(at($row, 'overflow') === 'native' && at($row, 'lowering') === 'native', 'Unsupported compiler update policy');
        $operators = names(at($row, 'operators'), 'Duplicate compiler update operator');
        foreach ($operators as $operator) {
            requireThat(in_array($operator, ['increment', 'decrement'], true), 'Unsupported compiler update operator');
        }
        if ($name === $identity) $selected = $operators;
    }
    if ($selected === []) return '';
    $admission = [];
    $lowering = '';
    foreach ($selected as $operator) {
        $admission[] = 'update_operator::' . $operator;
        $spelling = $operator === 'increment' ? '++' : '--';
        $lowering .= "\t\t\tupdate_operator::$operator => '$spelling',\n";
    }
    $cases = implode(', ', $admission);
    return "
\t/** Metadata-admitted discarded-value updates; no source result is exposed by this slice. */
\tpublic function supports_update(update_operator \$operator): bool
\t{
\t\treturn match (\$operator) {
\t\t\t$cases => true,
\t\t\tdefault => false,
\t\t};
\t}

\tpublic function lower_cpp_update(update_operator \$operator, string \$target, bool \$postfix): string
\t{
\t\t\$spelling = match (\$operator) {
$lowering\t\t\tdefault => throw new \\LogicException('Update operation was not approved'),
\t\t};
\t\treturn \$postfix ? \$target . \$spelling : \$spelling . \$target;
\t}
";
}

/** Emit bounded numeric prefix admission and direct lowering from metadata, without per-expression lookup tables. */
function compilerUnary(mixed $compiler, string $identity, array $safety): string {
    $selected = [];
    foreach (object(at($compiler, 'unary')) as $name => $row) {
        requireThat(in_array($name, ['int64', 'float64'], true), 'Unsupported compiler unary type');
        fields($row, ['result', 'overflow', 'lowering', 'operators']);
        literals($row, ['result' => $name, 'overflow' => 'native', 'lowering' => 'native']);
        $operators = names(at($row, 'operators'), 'Duplicate unary operator');
        foreach ($operators as $operator) {
            requireThat(in_array($operator, ['positive', 'negative'], true), 'Unsupported unary operator');
        }
        if ($identity === $name) $selected = $operators;
    }
    if ($selected === []) return '';
    $admission = [];
    $lowering = '';
    foreach ($selected as $operator) {
        $admission[] = 'unary_operator::' . $operator;
        $sign = $operator === 'positive' ? '+' : '-';
        $lowering .= "\t\t\tunary_operator::$operator => '($sign(' . \$operand . '))',\n";
    }
    $cases = implode(', ', $admission);
    $safeCases = compilerStrategyReorderable($safety, 'native') ? "\t\t\t$cases => true,\n" : '';
    return "
\t/** Generated prefix admission; operand preparation is owned by the single source traversal. */
\tpublic function prepare_unary(ast_unary_expression \$value): void
\t{
\t\tif ((\$value->operand->type_def() !== \$this) || !match (\$value->operator) {
\t\t\t$cases => true,
\t\t\tdefault => false,
\t\t}) {
\t\t\tparent::prepare_unary(\$value);
\t\t\treturn;
\t\t}
\t\t\$value->settle_unary(\$this);
\t}

\tpublic function lower_cpp_unary(unary_operator \$operator, string \$operand): string
\t{
\t\treturn match (\$operator) {
$lowering\t\t\tdefault => throw new \\LogicException('Unary operation was not approved'),
\t\t};
\t}

\t/** Admitted native prefix operations add no effects; operand safety remains separate. */
\tpublic function cpp_unary_reorderable(unary_operator \$operator): bool
\t{
\t\treturn match (\$operator) {
$safeCases\t\t\tdefault => false,
\t\t};
\t}
";
}

/** Generate bounded mixed ordering from explicit promotion rows; reuse conversion lowering, never compound admission. */
function compilerPromotions(mixed $compiler, array $conversions, array $definitions, string $identity): string {
    $cases = '';
    $seen = [];
    foreach (listValue(at($compiler, 'promotions')) as $row) {
        fields($row, ['left', 'right', 'operators', 'execution_type', 'result']);
        $left = textValue(at($row, 'left'));
        $right = textValue(at($row, 'right'));
        $execution = textValue(at($row, 'execution_type'));
        $result = textValue(at($row, 'result'));
        foreach ([$left, $right, $execution, $result] as $name) requireThat(isset($definitions[$name]), 'Promotion references inactive type');
        requireThat($left !== $right, 'Promotion requires different operand types');
        $lc = compilerPromotionConversion($conversions, $left, $execution);
        $rc = compilerPromotionConversion($conversions, $right, $execution);
        $operators = names(at($row, 'operators'), 'Duplicate promotion operator');
        $comparison = object(at($compiler, 'comparisons'))[$execution] ?? null;
        requireThat($comparison !== null && at($comparison, 'result') === $result, 'Promotion execution/result mismatch');
        foreach ($operators as $operator) {
            foreach ([$lc, $rc] as $conversion) {
                requireThat($conversion === null || in_array($operator, $conversion['promotion_operators'], true), 'Unsupported promotion operator');
            }
            requireThat(in_array($operator, names(at($comparison, 'operators')), true), 'Promotion execution operation unavailable');
            $key = $left . ':' . $right . ':' . $operator;
            requireThat(!isset($seen[$key]), 'Duplicate promotion');
            $seen[$key] = true;
        }
        if ($left !== $identity || $operators === []) continue;
        $rightClass = 'type_' . $definitions[$right]['class'];
        $executionClass = 'type_' . $definitions[$execution]['class'];
        $resultClass = 'type_' . $definitions[$result]['class'];
        $operatorCases = implode(', ', array_map(static fn(string $op): string => 'binary_operator::' . $op, $operators));
        $lc = $lc['operation'] ?? 'identity';
        $rc = $rc['operation'] ?? 'identity';
        $cases .= "\t\tif ((\$value->left->type_def() === \$this) && (\$value->right->type_def() === $rightClass::instance()) && match (\$value->operator) {\n"
            . "\t\t\t$operatorCases => true,\n\t\t\tdefault => false,\n\t\t}) {\n"
            . "\t\t\t\$value->settle_binary($resultClass::instance(), $executionClass::instance(), value_conversion::$lc, value_conversion::$rc);\n\t\t\treturn;\n\t\t}\n";
    }
    if ($cases === '') return '';
    return "\n\t/** Metadata-authorized promotion; operands are already prepared and conversion behavior is reused. */\n"
        . "\tpublic function prepare_binary(ast_binary_expression \$value): void\n\t{\n$cases\t\tparent::prepare_binary(\$value);\n\t}\n";
}

/** Resolve a direct operand-to-execution conversion; checked promotion preparation remains unsupported. */
function compilerPromotionConversion(array $conversions, string $source, string $execution): ?array {
    if ($source === $execution) return null;
    $conversion = $conversions[$source . ':' . $execution] ?? null;
    requireThat($conversion !== null, 'Promotion requires a defined conversion');
    requireThat($conversion['promotion_operators'] !== [], 'Conversion does not authorize promotion');
    return $conversion;
}
