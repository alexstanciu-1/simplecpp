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

/** Generate the supported core scalar definitions; module membership determines availability. */
function renderCompiler(mixed $metadata, array $facts): array {
    $types = registry($metadata, $facts);
    $compiler = at($metadata, 'compiler');
    fields($compiler, ['modules', 'integer_literals', 'value_types', 'conversions', 'floating_literals']);
    $active = [];
    foreach (object(at($compiler, 'modules')) as $module => $row) {
        fields($row, ['always_available', 'types']);
        $members = names(at($row, 'types'), 'Duplicate compiler module type');
        if (boolean(at($row, 'always_available'))) {
            requireThat($module === 'default-runtime', 'Only the default runtime is supported in this compiler slice');
            $active = array_merge($active, $members);
        }
    }
    requireThat($active === ['bool_t', 'byte', 'float64', 'int64', 'null_t', 'string'] && isset($types['int64']), 'Compiler lowering requires int64 metadata in the default runtime');
    $name = 'int64';
    $type = $types[$name];
    requireThat($type['signed'] && $type['bits'] === 64, 'Compiler slice requires signed int64');
    $sources = fields(at($metadata, 'source_types'), ['int', 'bool', 'null', 'float']);
    requireThat(($sources['int'] ?? null) === $name, 'Compiler int requires int64 metadata');
    $aliases = [$name];
    foreach ($sources as $alias => $identity) {
        if ($identity === $name) $aliases[] = $alias;
    }
    $aliases = array_values(array_unique($aliases));
    requireThat($aliases === ['int64', 'int'], 'Compiler slice registers only int/int64 aliases');
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
    $accept = implode(' || ', array_map(static fn(string $alias): string => '($name === ' . var_export($alias, true) . ')', $aliases));
    $source = strtr(readFile(__DIR__ . '/../templates/signed_integer.php.tpl'), [
        '@name@' => $name, '@maximum@' => $maximum, '@magnitude@' => $magnitude,
        '@accept_names@' => $accept, '@representation@' => var_export($type['representation'], true),
        '@cpp_minimum@' => var_export($minimumCpp, true), '@cpp_suffix@' => var_export($suffix, true),
    ]);
    $files = ['types/' . $name . '.php' => $source];
    $definitions = ['int64' => ['class' => 'int64', 'aliases' => $aliases, 'cpp' => $type['representation']]];
    foreach (object(at($compiler, 'value_types')) as $identity => $row) {
        fields($row, ['class', 'source_names', 'cpp_type', 'literals']);
        requireThat(in_array($identity, ['null_t', 'bool_t', 'byte', 'float64', 'string'], true), 'Unsupported compiler value type');
        $class = textValue(at($row, 'class'));
        requireThat($class === ['null_t' => 'null', 'bool_t' => 'bool', 'byte' => 'byte', 'float64' => 'float64', 'string' => 'string'][$identity], 'Invalid generated type class');
        $aliases = names(at($row, 'source_names'), 'Duplicate source type name');
        requireThat($aliases === ($identity === 'float64' ? ['float', 'float64'] : [$class]), 'Unsupported core source aliases');
        if ($identity === 'float64') {
            fields($row, ['class', 'source_names', 'cpp_type']);
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
        } elseif ($identity === 'string') {
            fields($row, ['class', 'source_names', 'cpp_type']);
            literals(at($metadata, 'string'), ['type' => 'string', 'source_type' => 'string',
                'storage' => 'private_std_string', 'count_type' => 'int64', 'construction' => 'explicit']);
            $cpp = textValue(at($row, 'cpp_type'));
            requireThat($cpp === 'scpp::string', 'Unsupported string representation');
            $literals = [];
        } elseif ($identity !== 'byte') {
            requireThat(($sources[$class] ?? null) === $identity, 'Source type identity mismatch');
            $value = at(at($metadata, 'values'), $identity);
            requireThat(at($value, 'representation') === ($identity === 'bool_t' ? 'native_bool' : 'empty'), 'Unsupported scalar representation');
            $cpp = textValue(at($row, 'cpp_type'));
            requireThat($cpp === ($identity === 'bool_t' ? 'bool' : 'decltype(nullptr)'), 'Unsupported compiler scalar representation');
            $literals = object(at($row, 'literals'));
            requireThat($literals === ($identity === 'bool_t' ? ['false' => false, 'true' => true] : ['null' => null]), 'Unsupported scalar literal policy');
        } else {
            $cpp = $types[$identity]['representation'];
            $literals = [];
        }
        $definitions[$identity] = ['class' => $class, 'aliases' => $aliases, 'cpp' => $cpp, 'literals' => $literals];
    }
    $scalarPolicies = names(at($metadata, 'scalar_casts'), 'Duplicate scalar cast policy');
    foreach (listValue(at($compiler, 'conversions')) as $conversion) {
        $operation = textValue(at($conversion, 'operation'));
        $required = match ($operation) {
            'boolean_integer' => 'boolean_to_numeric_01',
            'integer_boolean', 'floating_boolean' => 'numeric_to_boolean_nonzero',
            'floating_integer' => 'floating_to_integer_truncate_checked',
            'numeric_floating' => at($conversion, 'from') === 'bool_t'
                ? 'boolean_to_numeric_01' : 'integer_to_floating_rounding',
            default => null,
        };
        requireThat($required === null || in_array($required, $scalarPolicies, true), 'Compiler conversion lacks runtime scalar policy');
    }
    $identities = array_keys($definitions);
    sort($identities, SORT_STRING);
    requireThat($identities === $active, 'Missing default runtime type definition');
    foreach ($definitions as $identity => $definition) {
        $methods = compilerConversions($compiler, $definitions, $identity);
        if ($identity === 'int64') {
            $files['types/int64.php'] = str_replace('@conversion_methods@', $methods, $files['types/int64.php']);
            continue;
        }
        $limits = $identity === 'byte' ? "\t/** CONSTANTS */\n\tpublic const int MINIMUM /** uint8 */ = 0;\n\tpublic const int MAXIMUM /** uint8 */ = " . decimalPredecessor(decimalPowerOfTwo($types[$identity]['bits'])) . ";\n\n" : '';
        $accept = implode(' || ', array_map(static fn(string $alias): string => '($name === ' . var_export($alias, true) . ')', $definition['aliases']));
        $literalMethods = $identity === 'float64'
            ? strtr(readFile(__DIR__ . '/../templates/floating_literal_methods.php.tpl'), [
                '@minimum_exponent@' => (string)(2 - $facts['double']['max_exponent']),
                '@exponent_bias@' => (string)($facts['double']['max_exponent'] - 1),
            ]) : compilerLiteralMethods($definition);
        if ($identity === 'string') $literalMethods = readFile(__DIR__ . '/../templates/string_literal_methods.php.tpl');
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
        '@cases@' => implode("\n", $cases), '@literal_cases@' => implode("\n", $literalCases), '@name@' => 'int64',
    ]);
    $files['inventory.json'] = encode(['generator' => 'scpp-compiler-lowering', 'version' => 6,
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

/** Emit direct conversion branches; no conversion matrix is interpreted by the running compiler. */
function compilerConversions(mixed $compiler, array $definitions, string $target): string {
    $prepare = [];
    $lower = [];
    $constants = [];
    $seen = [];
    foreach (listValue(at($compiler, 'conversions')) as $row) {
        fields($row, ['from', 'to', 'explicit', 'operation']);
        $from = textValue(at($row, 'from'));
        $to = textValue(at($row, 'to'));
        $explicit = boolean(at($row, 'explicit'));
        $operation = textValue(at($row, 'operation'));
        requireThat(isset($definitions[$from], $definitions[$to]), 'Conversion references inactive type');
        $key = $from . ':' . $to;
        requireThat(!isset($seen[$key]), 'Duplicate compiler conversion');
        $seen[$key] = true;
        $allowed = ['bool_t:int64' => 'boolean_integer', 'int64:bool_t' => 'integer_boolean',
            'int64:byte' => 'checked_integer', 'byte:int64' => 'integer_widen',
            'int64:float64' => 'numeric_floating', 'bool_t:float64' => 'numeric_floating',
            'float64:bool_t' => 'floating_boolean', 'float64:int64' => 'floating_integer'];
        requireThat(($allowed[$key] ?? null) === $operation, 'Unsupported compiler conversion operation');
        // Byte's distinct contract must not be weakened by a publication descriptor.
        requireThat(($from !== 'byte' && $to !== 'byte') || $explicit, 'Byte conversions must be explicit');
        if ($to !== $target) continue;
        $condition = '$value->type_def() === type_' . $definitions[$from]['class'] . '::instance()';
        if ($explicit) $condition = '($explicit) && (' . $condition . ')';
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
            $lower[] = "\t\t\tvalue_conversion::floating_integer_checked => '(scpp::int64{scpp::float64{' . \$value . '}}).native_value()',";
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
            $lower[] = "\t\t\tvalue_conversion::integer_checked => '(scpp::byte{' . \$value . '}).native_value()',";
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
    $headers = $target === 'byte' ? <<<'CODE'

	/** @return string[] Public runtime includes required by the selected conversion only. */
	public function conversion_headers(value_conversion $conversion): array /** list<string> */
	{
		return $conversion === value_conversion::integer_checked ? ['scpp/integers.hpp'] : [];
	}
CODE : ($target === 'int64' ? <<<'CODE'

	/** @return string[] Public wrappers provide the existing checked floating-to-integer boundary. */
	public function conversion_headers(value_conversion $conversion): array /** list<string> */
	{
		return $conversion === value_conversion::floating_integer_checked ? ['scpp/integers.hpp', 'scpp/values.hpp'] : [];
	}
CODE : '');
    return <<<PHP_CODE
	/** Select the conversion once; the caller marks written destinations or explicit cast boundaries. */
	public function prepare_conversion(ast_expression \$value, bool \$explicit): value_conversion
	{
$prepare
		return parent::prepare_conversion(\$value, \$explicit);
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
