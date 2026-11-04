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
    fields($compiler, ['modules', 'integer_literals', 'value_types', 'conversions']);
    $active = [];
    foreach (object(at($compiler, 'modules')) as $module => $row) {
        fields($row, ['always_available', 'types']);
        $members = names(at($row, 'types'), 'Duplicate compiler module type');
        if (boolean(at($row, 'always_available'))) {
            requireThat($module === 'default-runtime', 'Only the default runtime is supported in this compiler slice');
            $active = array_merge($active, $members);
        }
    }
    requireThat($active === ['bool_t', 'byte', 'int64', 'null_t'] && isset($types['int64']), 'Compiler lowering requires int64 metadata in the default runtime');
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
        requireThat(in_array($identity, ['null_t', 'bool_t', 'byte'], true), 'Unsupported compiler value type');
        $class = textValue(at($row, 'class'));
        requireThat($class === ['null_t' => 'null', 'bool_t' => 'bool', 'byte' => 'byte'][$identity], 'Invalid generated type class');
        $aliases = names(at($row, 'source_names'), 'Duplicate source type name');
        requireThat($aliases === [$class], 'Unsupported core source aliases');
        if ($identity !== 'byte') {
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
        $literalMethods = compilerLiteralMethods($definition);
        $files['types/' . $definition['class'] . '.php'] = strtr(readFile(__DIR__ . '/../templates/scalar_type.php.tpl'), [
            '@name@' => $identity, '@class@' => $definition['class'], '@limits@' => $limits,
            '@accept_names@' => $accept, '@representation@' => var_export($definition['cpp'], true),
            '@literal_methods@' => $literalMethods, '@conversion_methods@' => $methods,
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
    $files['inventory.json'] = encode(['generator' => 'scpp-compiler-lowering', 'version' => 4,
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
            'int64:byte' => 'checked_integer', 'byte:int64' => 'integer_widen'];
        requireThat(($allowed[$key] ?? null) === $operation, 'Unsupported compiler conversion operation');
        // Byte's distinct contract must not be weakened by a publication descriptor.
        requireThat(($from !== 'byte' && $to !== 'byte') || $explicit, 'Byte conversions must be explicit');
        if ($to !== $target) continue;
        $condition = '$value->type_def() === type_' . $definitions[$from]['class'] . '::instance()';
        if ($explicit) $condition = '($explicit) && (' . $condition . ')';
        $prepare[] = "\t\tif ($condition) {";
        $constantOperation = match ($operation) {
            'boolean_integer' => '$value === null ? null : (int) $value', 'integer_boolean' => '$value === null ? null : $value !== 0',
            default => '$value',
        };
        $constantCases = $operation === 'checked_integer'
            ? 'value_conversion::integer_proven, value_conversion::integer_checked' : 'value_conversion::' . $operation;
        $constants[] = "\t\t\t" . $constantCases . ' => ' . $constantOperation . ',';
        if ($operation === 'checked_integer') {
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
            $expression = $operation === 'integer_boolean' ? "'(' . \$value . ' != 0)'" : "'static_cast<' . \$this->cpp_type() . '>(' . \$value . ')'";
            $lower[] = "\t\t\tvalue_conversion::$operation => $expression,";
        }
        $prepare[] = "\t\t}";
    }
    if ($prepare === []) return '';
    $prepare = implode("\n", $prepare);
    $lower = implode("\n", $lower);
    $constants = implode("\n", $constants);
    $headers = $target === 'byte' ? <<<'CODE'

	/** @return string[] Public runtime includes required by the selected conversion only. */
	public function conversion_headers(value_conversion $conversion): array /** list<string> */
	{
		return $conversion === value_conversion::integer_checked ? ['scpp/integers.hpp'] : [];
	}
CODE : '';
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
	public function convert_constant(value_conversion \$conversion, int|bool|null \$value /** int64|bool|null */): int|bool|null /** int64|bool|null */
	{
		return match (\$conversion) {
$constants
			default => parent::convert_constant(\$conversion, \$value),
		};
	}
$headers
PHP_CODE;
}
