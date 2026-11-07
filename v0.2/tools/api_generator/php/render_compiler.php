<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/submodules.php';

require_once __DIR__ . '/compiler_families.php';
require_once __DIR__ . '/compiler_plans.php';
require_once __DIR__ . '/render_compiler_vectors.php';
require_once __DIR__ . '/render_compiler_carriers.php';
require_once __DIR__ . '/render_compiler_hashes.php';
require_once __DIR__ . '/boxed_dispatch.php';

/** Generate the supported core scalar definitions; module membership determines availability. */
function renderCompiler(mixed $metadata, array $facts, ?array $runtime = null): array {
    // A null plan is the scalar-only generator test surface; production always supplies a validated runtime plan.
    $providers = $runtime['submodules'] ?? submodulePlan($metadata);
    $metadata = compilerProviderMetadata($metadata, $providers);
    $types = registry($metadata, $facts);
    $compiler = at($metadata, 'compiler');
    fields($compiler, ['modules', 'integer_literals', 'value_types', 'conversions', 'floating_literals', 'conditions', 'comparisons', 'arithmetic', 'updates', 'unary', 'promotions', 'runtime_bindings', 'lowering_strategies', 'output', 'entry', 'integer_operators', 'conversion_families', 'mixed_numeric_operators', 'shifts', 'concatenation', 'strict_identity', 'three_way', 'power']);
    $safety = compilerLoweringSafety($compiler);
    $definitions = compilerTypePlan($metadata, $facts, $types, $providers);
    $metadata = clone $metadata;
    $compiler = compilerFamilyPlan($metadata, $definitions, $types);
    $metadata->compiler = $compiler;
    $families = compilerLiteralFamilies($definitions);
    $files = [];
    $conversions = compilerConversionPlan($metadata, $definitions, $types, $facts, $runtime['explicit_formatters'] ?? []);
    $conditions = compilerConditionPlan($compiler, $definitions);
    $comparisons = compilerComparisonPlan($compiler, $definitions, names(at(at($metadata, 'string'), 'operations')));
    $binary = compilerBinaryPlan($compiler, $definitions, $comparisons, $types, names(at(at($metadata, 'string'), 'operations')));
    $output = compilerOutputPlan($compiler, $definitions);
    $updates = compilerUpdatePlan($compiler, $definitions);
    $unary = compilerUnaryPlan($compiler, $definitions);
    $promotions = compilerPromotionPlan($compiler, $conversions, $definitions, $binary);
    foreach ($definitions as $identity => $definition) {
        $methods = compilerDeclarations($definition['declarations']) . compilerOutput($output[$identity] ?? null) . compilerConversions($conversions, $definitions, $identity) . compilerConditions($conditions, $identity) . compilerBinary($compiler, $definitions, $identity, $safety, $binary, $types) . compilerUpdates($updates[$identity] ?? []) . compilerUnary($unary[$identity] ?? [], $identity, $safety, $definitions, $types) . compilerPromotions($promotions[$identity] ?? [], $definitions);
        if (isset($definition['cpp_header'])) {
            $header = var_export($definition['cpp_header'], true);
            $methods .= "\n\t/** @return string[] */\n\tpublic function cpp_headers(): array /** list<string> */\n\t{\n\t\treturn [$header];\n\t}\n";
        }
        if ($definition['copy_semantics'] === 'trivial') {
            $methods .= "
\t/** Native scalar storage has no observable copy operation. */
\tpublic function cpp_trivial_value(): bool
\t{
\t\treturn true;
\t}
";
        }
        if ($definition['literal_family'] === 'integer') {
            $accept = implode(' || ', array_map(static fn(string $alias): string => '($name === ' . var_export($alias, true) . ')', $definition['aliases']));
            $files['types/' . $definition['class'] . '.php'] = strtr(readFile(__DIR__ . '/../templates/signed_integer.php.tpl'), [
                '@name@' => $identity, '@maximum@' => $definition['maximum'], '@magnitude@' => $definition['magnitude'],
                '@accept_names@' => $accept, '@representation@' => var_export($definition['cpp'], true),
                '@cpp_minimum@' => var_export($definition['minimum_cpp'], true), '@cpp_suffix@' => var_export($definition['suffix'], true),
                '@conversion_methods@' => $methods,
            ]);
            continue;
        }
        $limits = ($definition['strategy'] === 'integer' && !$types[$identity]['signed'] && $types[$identity]['bits'] < 64) ? "\t/** CONSTANTS */\n\tpublic const int MINIMUM /** uint8 */ = 0;\n\tpublic const int MAXIMUM /** uint8 */ = " . decimalPredecessor(decimalPowerOfTwo($types[$identity]['bits'])) . ";\n\n" : '';
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
    [$vectorCode, $vectorCases, $vectorFamily] = compilerVectorTypes($metadata, $runtime, $definitions);
    $files['types/vectors.php'] = $vectorCode;
    [$carrierCode, $carrierCases, $carrierLiterals] = compilerCarrierTypes($runtime, $definitions);
    $files['types/carriers.php'] = $carrierCode;
    $literalCases = array_merge($literalCases, $carrierLiterals);
    $genericCases = [];
    if ($vectorFamily !== null) $genericCases[$vectorFamily] = $vectorCases;
    foreach ($runtime['carrier_families'] ?? [] as $family => $policy) {
        $genericCases[$family] = implode("\n", $carrierCases[$family] ?? []);
    }
    $arity = [];
    $specializations = '';
    foreach ($genericCases as $family => $matches) {
        $name = var_export($family, true);
        $matches = str_replace("\t\t\t", "\t\t\t\t", $matches);
        $arity[] = "\t\t\t$name => 1,";
        $specializations .= "\t\tif (\$family === $name) {\n\t\t\treturn match (\$element) {\n$matches\n\t\t\t\tdefault => null,\n\t\t\t};\n\t\t}\n";
    }
    [$hashCode, $hashCases, $hashFamily] = compilerHashTypes($metadata, $runtime, $definitions);
    $files['types/hashes.php'] = $hashCode;
    if ($hashFamily !== null) {
        $name = var_export($hashFamily, true);
        $arity[] = "\t\t\t$name => 2,";
        $specializations .= "\t\tif (\$family === $name) {\n$hashCases\n\t\t\treturn null;\n\t\t}\n";
    }
    if (isset($runtime['boxed_dispatch'])) {
        $files['types/boxed.php'] = compilerBoxedDefinitions($runtime, $cases);
    }
    $runtimeCases = [];
    foreach ($runtime['string_functions'] ?? [] as $name => $binding) {
        if (!isset($definitions[$binding['parameter']], $definitions[$binding['result']])) continue;
        $parameter = $definitions[$binding['parameter']]['class'];
        $result = $definitions[$binding['result']]['class'];
        $runtimeCases[] = "\t\t\t'func:$name' => \$bindings[\$key] ??= new runtime_function('$name', "
            . "type_$parameter::instance(), type_$result::instance(), '{$binding['symbol']}', '{$binding['header']}'),";
    }
    $files['types/registry.php'] = strtr(readFile(__DIR__ . '/../templates/type_registry.php.tpl'), [
        '@runtime_cases@' => implode("\n", $runtimeCases), '@family_cases@' => $families['cases'], '@family_accessors@' => $families['accessors'],
        '@boxed_require@' => isset($files['types/boxed.php']) ? "require_once __DIR__ . '/boxed.php';\n" : '',
        '@generic_arity@' => implode("\n", $arity), '@specializations@' => $specializations,
        '@cases@' => implode("\n", $cases), '@literal_cases@' => implode("\n", $literalCases),
    ]);
    $files['bindings/entry.php'] = compilerEntryBinding($compiler);
    $files['inventory.json'] = encode(['generator' => 'scpp-compiler-lowering', 'version' => 25,
        'files' => array_keys($files)]) . "\n";
    return $files;
}

/** Resolve the native entry ABI without treating native int as a source-language type. */
function compilerEntryBinding(mixed $compiler): string {
    $entry = at($compiler, 'entry');
    fields($entry, ['checked_binding']);
    $binding = at(at($compiler, 'runtime_bindings'), textValue(at($entry, 'checked_binding')));
    fields($binding, ['operand', 'result', 'symbol', 'header']);
    literals($binding, ['operand' => 'int64', 'result' => 'native_int']);
    $symbol = textValue(at($binding, 'symbol'));
    $header = textValue(at($binding, 'header'));
    requireThat(preg_match('/^scpp::[A-Za-z_][A-Za-z0-9_]*$/D', $symbol) === 1, 'Invalid entry binding symbol');
    requireThat(preg_match('~^scpp/[a-zA-Z0-9_]+(?:/[a-zA-Z0-9_]+)*\.hpp$~D', $header) === 1, 'Invalid entry binding header');
    return strtr(readFile(__DIR__ . '/../templates/entry_binding.php.tpl'), [
        '@symbol@' => var_export($symbol, true), '@header@' => var_export($header, true),
    ]);
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

/** Emit type-owned output dispatch; formatting/lifetime algorithms stay in the backend. */
function compilerOutput(?array $selected): string {
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
        if ($operation === 'format_text') {
            $condition = '$context === conversion_context::explicit_cast && $value->value_type() === type_' . $definitions[$from]['class'] . '::instance()';
            $code = 'value_conversion::format_' . $from;
            $prepare[] = "\t\tif ($condition) {\n\t\t\treturn $code;\n\t\t}";
            $binding = $row['binding'];
            $lower[] = "\t\t\t$code => '{$binding['symbol']}(static_cast<{$binding['source_cpp']}>(' . \$value . '))',";
            $constants[] = "\t\t\t$code => null,";
            $headerCases[] = "\t\t\t$code => [" . var_export($binding['header'], true) . '],';
            continue;
        }
        if ($row['binding'] !== null) {
            $symbol = $row['binding']['symbol'];
            // C++ intermediates (for example LL literals) must match the helper's exact prepared-source ABI.
            $sourceCpp = $row['binding']['source_cpp'];
            $checked = $operation === 'floating_integer' ? 'floating_integer_checked' : 'integer_checked';
            $headerCases[] = "\t\t\tvalue_conversion::$checked => [" . var_export($row['binding']['header'], true) . '],';
        }
        $condition = '$value->value_type() === type_' . $definitions[$from]['class'] . '::instance()';
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
            $bound = $row['floating_bound'] . '.0';
            $lowerBound = $row['integer_signed'] ? '-' . $bound : '0.0';
            $prepare[] = strtr(<<<'CODE'
			if ($value->constant_known()) {
				$constant = $value->constant();
				$truncated = $constant < 0.0 ? ceil($constant) : floor($constant);
				if (!is_finite($constant) || ($truncated < @lower@) || ($truncated >= @upper@)) {
					throw new diagnostic($value->source(), $value->byte_start(), 'Floating conversion is outside ' . $this->name . ' range');
				}
				return value_conversion::floating_integer_proven;
			}
			return value_conversion::floating_integer_checked;
CODE, ['@lower@' => $lowerBound, '@upper@' => $bound]);
            $lower[] = "\t\t\tvalue_conversion::floating_integer_proven => 'static_cast<' . \$this->cpp_type() . '>(' . \$value . ')',";
            $lower[] = "\t\t\tvalue_conversion::floating_integer_checked => '{$symbol}(static_cast<{$sourceCpp}>(' . \$value . '))',";
        } elseif ($operation === 'checked_integer') {
            $prepare[] = <<<'CODE'
			if ($value->constant_known()) {
				$constant = $value->constant();
				if (($constant < self::MINIMUM) || ($constant > self::MAXIMUM)) {
					throw new diagnostic($value->source(), $value->byte_start(), 'Conversion is outside ' . $this->name . ' range');
				}
				return value_conversion::integer_proven;
			}
			return value_conversion::integer_checked;
CODE;
            $lower[] = "\t\t\tvalue_conversion::integer_proven => 'static_cast<' . \$this->cpp_type() . '>(' . \$value . ')',";
            $lower[] = "\t\t\tvalue_conversion::integer_checked => '{$symbol}(static_cast<{$sourceCpp}>(' . \$value . '))',";
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
	public function scalar_conversion_headers(int \$conversion /** uint16 */): array /** list<string> */
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
	public function prepare_scalar_conversion(ast_expression \$value, conversion_context \$context): int /** uint16 */
	{
$prepare
		return parent::prepare_scalar_conversion(\$value, \$context);
	}

	/** Render an approved boundary without repeating source-type resolution or changing facts. */
	public function lower_scalar_conversion(int \$conversion /** uint16 */, string \$value): string
	{
		return match (\$conversion) {
$lower
			default => parent::lower_scalar_conversion(\$conversion, \$value),
		};
	}

	/** Preserve unknown values; convert known payloads using the selected operation only. */
	public function convert_scalar_constant(int \$conversion /** uint16 */, int|float|bool|null \$value /** int64|float64|bool|null */): int|float|bool|null /** int64|float64|bool|null */
	{
		return match (\$conversion) {
$constants
			default => parent::convert_scalar_constant(\$conversion, \$value),
		};
	}
$headers
PHP_CODE;
}

/** Emit direct condition handlers; missing rows retain base rejection without per-expression metadata lookup. */
function compilerConditions(array $conditions, string $identity): string {
    if (!isset($conditions[$identity])) return '';
    $condition = $conditions[$identity];
    $storage = $condition['storage'];
    $expression = match ($condition['strategy']) {
        'identity' => '$value',
        'nonzero_integer' => "'(' . \$value . ' != 0)'",
        'nonzero_float' => "'(' . \$value . ' != 0.0)'",
        'nonempty_except_zero' => var_export("([](const " . $storage['symbol'] . "& condition_value) -> bool { return condition_value.size() != 0 && (condition_value.size() != 1 || condition_value.data()[0] != '0'); })(", true) . " . \$value . ')'",
    };
    $headers = $storage === null ? '[]' : '[' . var_export($storage['header'], true) . ']';
    return "
\t/** Generated condition admission; never an explicit bool conversion. */
\tpublic function prepare_condition(ast_expression \$value): void
\t{
\t\tif (\$value->value_type() !== \$this) {
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

/** Generate direct binary handlers from the validated execution plan. */
function compilerBinary(mixed $compiler, array $definitions, string $identity, array $safety, array $binary, array $types): string {
    $operations = $binary[$identity] ?? [];
    if ($operations === []) return '';
    $results = '';
    $rightCases = '';
    $flavorCases = '';
    $compoundEnabled = isset($compiler->arithmetic->$identity) && $compiler->arithmetic->$identity->compound;
    $compoundOperators = [];
    $cases = '';
    $headers = [];
    $reorderable = [];
    foreach ($operations as $op => $operation) {
        $result = 'type_' . $definitions[$operation['result']]['class'];
        if (($operation['compound'] ?? $compoundEnabled) && $operation['result'] === $identity) $compoundOperators[] = "binary_operator::$op";
        if (isset($operation['flavors'])) {
            $allowed = implode(', ', array_map(static fn(string $flavor): string => 'flavor::' . $flavor, $operation['flavors']));
            $flavorCases .= "\t\t\tbinary_operator::$op => match (\$site->source()->flavor) { $allowed => true, default => false },\n";
        }
        $rightTypes = $operation['counts'] ?? $operation['identity_peers'] ?? null;
        if ($rightTypes !== null) {
            $admission = implode(' || ', array_map(static fn(string $name): string => '$right === type_' . $definitions[$name]['class'] . '::instance()', $rightTypes));
            $rightCases .= "\t\tif (\$operator === binary_operator::$op) {\n\t\t\treturn (" . ($admission === '' ? 'false' : $admission) . ") ? $result::instance() : null;\n\t\t}\n";
        } else {
            $results .= "\t\t\tbinary_operator::$op => $result::instance(),\n";
        }
        if (compilerStrategyReorderable($safety, isset($operation['identity_peers']) ? 'canonical_identity' : $operation['lowering'])) $reorderable[] = "binary_operator::$op";
        if ($operation['lowering'] === 'native') {
            $expression = "'(' . \$left . ' " . $operation['spelling'] . " ' . \$right . ')'";
            if ($definitions[$identity]['strategy'] === 'integer' && $types[$identity]['bits'] < 64 && $operation['result'] === $identity) {
                $expression = "'static_cast<" . $definitions[$identity]['cpp'] . ">(' . " . $expression . " . ')'";
            }
        } elseif (in_array($operation['lowering'], ['native_three_way', 'string_three_way'], true)) {
            $resultCpp = $definitions[$operation['result']]['cpp'];
            $extract = '';
            if ($operation['lowering'] === 'string_three_way') {
                $extract = $operation['result_binding']['extract'] === null ? '' : '.' . $operation['result_binding']['extract'] . '()';
                $headers[$operation['storage']['header']][] = "binary_operator::$op";
                $headers[$operation['result_binding']['header']][] = "binary_operator::$op";
            }
            $expression = "'static_cast<$resultCpp>(((' . \$left . ' > ' . \$right . ')$extract) - ((' . \$left . ' < ' . \$right . ')$extract))'";
        } elseif ($operation['lowering'] === 'runtime_concat') {
            $expression = "'(' . \$left . ' + ' . \$right . ')'";
            $headers[$operation['storage']['header']][] = "binary_operator::$op";
        } elseif ($operation['lowering'] === 'runtime_compare') {
            $extract = $operation['result_binding']['extract'] === null ? '' : '.' . $operation['result_binding']['extract'] . '()';
            $expression = "'((' . \$left . ' " . $operation['spelling'] . " ' . \$right . ')$extract)'";
            $headers[$operation['storage']['header']][] = "binary_operator::$op";
            $headers[$operation['result_binding']['header']][] = "binary_operator::$op";
        } else {
            $symbol = $operation['binding']['symbol'];
            $expression = "'{$symbol}(' . \$left . ', ' . \$right . ')'";
            $headers[$operation['binding']['header']][] = "binary_operator::$op";
        }
        if (isset($operation['identity_peers'])) {
            $different = $operation['different_result'] ? 'true' : 'false';
            $expression = "(\$right_type === \$this) ? $expression : "
                . "'(static_cast<void>(' . \$left . '), static_cast<void>(' . \$right . '), $different)'";
        }
        $cases .= "\t\t\tbinary_operator::$op => $expression,\n";
    }
    $compound = $compoundOperators === [] ? 'false' : "match (\$operator) { " . implode(', ', $compoundOperators) . " => true, default => false }";
    $syntaxMethod = $flavorCases === '' ? '' : "\n\t/** Flavor spelling admission generated from operation metadata. */\n\tpublic function binary_syntax_allowed(binary_operator \$operator, ast_node \$site): bool\n\t{\n\t\treturn match (\$operator) {\n$flavorCases\t\t\tdefault => true,\n\t\t};\n\t}\n";
    $safeCases = $reorderable === [] ? '' : "\t\t\t" . implode(', ', $reorderable) . " => true,\n";
    $headerMethods = '';
    if ($headers !== []) {
        $headerCases = '';
        $perOperator = [];
        foreach ($headers as $header => $operators) {
            foreach ($operators as $operator) $perOperator[$operator][$header] = true;
        }
        $headerGroups = [];
        foreach ($perOperator as $operator => $required) {
            $list = implode(', ', array_map(static fn(string $header): string => var_export($header, true), array_keys($required)));
            $headerGroups[$list][] = $operator;
        }
        foreach ($headerGroups as $list => $operators) {
            $headerCases .= "\t\t\t" . implode(', ', $operators) . " => [$list],\n";
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
    return "$syntaxMethod
\t/** Compound syntax admission is separate from ordinary arithmetic capability. */
\tpublic function supports_compound(binary_operator \$operator): bool
\t{
\t\treturn $compound;
\t}

\t/** Generated same-type admission; result identity follows the selected metadata operation. */
\tpublic function binary_result(binary_operator \$operator, type_definition \$right): ?type_definition
\t{
$rightCases\t\tif (\$right !== \$this) {
\t\t\treturn null;
\t\t}
\t\treturn match (\$operator) {
$results\t\t\tdefault => null,
\t\t};
\t}

\t/** Lower an approved operator on already sequenced operand snapshots. */
\tpublic function lower_cpp_binary(binary_operator \$operator, string \$left, string \$right, type_definition \$right_type): string
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

/** Emit direct update handlers from the validated operator list for one type. */
function compilerUpdates(array $selected): string {
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

/** Emit direct unary handlers from the validated operator list for one type. */
function compilerUnary(array $selected, string $identity, array $safety, array $definitions, array $types): string {
    if ($selected === []) return '';
    $admission = [];
    $lowering = '';
    foreach ($selected as $operator) {
        $admission[] = 'unary_operator::' . $operator;
        $sign = match ($operator) { 'positive' => '+', 'negative' => '-', 'bit_not' => '~' };
        // C++ promotes narrow operands; restore their identity before any enclosing expression uses the result.
        $restore = isset($types[$identity]) && $types[$identity]['bits'] < 64;
        $expression = $restore ? 'static_cast<' . $definitions[$identity]['cpp'] . '>(' . $sign . '(' : '(' . $sign . '(';
        $lowering .= "\t\t\tunary_operator::$operator => '$expression' . \$operand . '))',\n";
    }
    $cases = implode(', ', $admission);
    $safeCases = compilerStrategyReorderable($safety, 'native') ? "\t\t\t$cases => true,\n" : '';
    return "
\t/** Generated prefix admission; operand preparation is owned by the single source traversal. */
\tpublic function prepare_unary(ast_unary_expression \$value): void
\t{
\t\tif ((\$value->operand->value_type() !== \$this) || !match (\$value->operator) {
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

/** Emit promotion handlers from the validated rows belonging to one left-hand type. */
function compilerPromotions(array $rows, array $definitions): string {
    $cases = '';
    $compounds = '';
    foreach ($rows as $row) {
        $rightClass = 'type_' . $definitions[$row['right']]['class'];
        $executionClass = 'type_' . $definitions[$row['execution']]['class'];
        $resultClass = 'type_' . $definitions[$row['result']]['class'];
        $operatorCases = implode(', ', array_map(static fn(string $op): string => 'binary_operator::' . $op, $row['operators']));
        $compoundOperators = $row['compound_operators'];
        $lc = $row['left_conversion'];
        $rc = $row['right_conversion'];
        $writeBack = $row['write_back'];
        if ($compoundOperators !== []) {
            $compoundCases = implode(', ', array_map(static fn(string $op): string => 'binary_operator::' . $op, $compoundOperators));
            $compounds .= "\t\tif (\$this->supports_compound(\$value->operator) && (\$value->value->type_def() === $rightClass::instance()) && match (\$value->operator) {\n"
                . "\t\t\t$compoundCases => true,\n\t\t\tdefault => false,\n\t\t}) {\n\t\t\t\$value->settle_compound(\$local, $executionClass::instance(), value_conversion::$lc, value_conversion::$rc, value_conversion::$writeBack);\n\t\t\treturn;\n\t\t}\n";
        }
        $cases .= "\t\tif ((\$value->left->value_type() === \$this) && (\$value->right->value_type() === $rightClass::instance()) && match (\$value->operator) {\n"
            . "\t\t\t$operatorCases => true,\n\t\t\tdefault => false,\n\t\t}) {\n"
            . "\t\t\t\$value->settle_binary($resultClass::instance(), $executionClass::instance(), value_conversion::$lc, value_conversion::$rc);\n\t\t\treturn;\n\t\t}\n";
    }
    $compoundMethod = $compounds === '' ? '' : "\n\t/** Attach generated operand conversions, execution identity and checked result write-back. */\n"
        . "\tpublic function prepare_compound(ast_assignment_statement \$value, ast_local_binding \$local): void\n\t{\n$compounds\t\tparent::prepare_compound(\$value, \$local);\n\t}\n";
    if ($cases === '') return $compoundMethod;
    return $compoundMethod . "\n\t/** Metadata-authorized promotion; operands are already prepared and conversion behavior is reused. */\n"
        . "\tpublic function prepare_binary(ast_binary_expression \$value): void\n\t{\n$cases\t\tparent::prepare_binary(\$value);\n\t}\n";
}
