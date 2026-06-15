<?php
declare(strict_types=1);

namespace Scpp\S2S\Jss;

use Scpp\S2S\Stan\StanTakeContractResolver;
use Scpp\S2S\Support\InputException;

final class JssSemanticValidator
{
	/** @var array<string,string> */
	private const BUILTIN_CALL_RETURN_TYPES = [
		'take' => 'bool',
		'count' => 'int',
		'cli_argc' => 'int',
		'shell_exec' => 'string',
	];

	public function __construct(
		private readonly JssCallSurface $callSurface = new JssCallSurface(),
		private readonly StanTakeContractResolver $takeContractResolver = new StanTakeContractResolver(),
	)
	{
	}

	/** @param array<string,string> $types */
	public function validate(JssNode $program, array $types = []): void
	{
		$this->validateStatements($program->fields['statements'] ?? [], $types);
	}

	/** @param mixed $statements @param array<string,string> $types */
	private function validateStatements(mixed $statements, array $types): array
	{
		foreach (is_array($statements) ? $statements : [] as $statement) {
			if (!$statement instanceof JssNode) {
				continue;
			}
			$types = $this->validateStatement($statement, $types);
		}
		return $types;
	}

	/** @param array<string,string> $types @return array<string,string> */
	private function validateStatement(JssNode $statement, array $types): array
	{
		if ($statement->kind === 'function_decl') {
			$this->validateStatements($statement->fields['body'] ?? [], $this->paramTypes($statement->fields['params'] ?? []));
			return $types;
		}
		if ($statement->kind === 'class_decl') {
			foreach (is_array($statement->fields['members'] ?? null) ? $statement->fields['members'] : [] as $member) {
				if ($member instanceof JssNode && in_array($member->kind, ['method_decl', 'constructor_decl'], true)) {
					$methodTypes = $this->paramTypes($member->fields['params'] ?? []);
					if ($member->kind !== 'method_decl' || ($member->fields['static'] ?? false) !== true) {
						$methodTypes['this'] = 'object';
					}
					$this->validateStatements($member->fields['body'] ?? [], $methodTypes);
				}
			}
			return $types;
		}
		if ($statement->kind === 'namespace_block') {
			$this->validateStatements($statement->fields['body'] ?? [], $types);
			return $types;
		}
		if ($statement->kind === 'var_decl') {
			$name = (string) ($statement->fields['name'] ?? '');
			$declaredType = $this->normalizeType($statement->fields['type'] ?? null);
			$initializer = $statement->fields['initializer'] ?? null;
			if ($initializer instanceof JssNode) {
				$this->validateExpression($initializer, $types);
				$this->validateInitializer($initializer, $declaredType, $types);
			}
			$inferredType = $initializer instanceof JssNode ? $this->expressionType($initializer, $types) : null;
			if ($name !== '') {
				$types[$name] = $declaredType ?? $inferredType ?? '';
			}
			return $types;
		}
		if ($statement->kind === 'const_decl') {
			$name = (string) ($statement->fields['name'] ?? '');
			$value = $statement->fields['value'] ?? null;
			if ($value instanceof JssNode) {
				$this->validateExpression($value, $types);
			}
			$inferredType = $value instanceof JssNode ? $this->expressionType($value, $types) : null;
			if ($name !== '' && $inferredType !== null) {
				$types[$name] = $inferredType;
			}
			return $types;
		}
		if ($statement->kind === 'assign') {
			$target = $statement->fields['target'] ?? null;
			$value = $statement->fields['value'] ?? null;
			if ($value instanceof JssNode) {
				$this->validateExpression($value, $types);
			}
			if ($target instanceof JssNode) {
				$this->validateExpression($target, $types);
			}
			if ($target instanceof JssNode && $target->kind === 'identifier' && $value instanceof JssNode) {
				$name = (string) ($target->fields['name'] ?? '');
				$this->validateInitializer($value, $types[$name] ?? null, $types);
			}
			return $types;
		}
		if (in_array($statement->kind, ['if', 'while', 'do_while'], true)) {
			$condition = $statement->fields['condition'] ?? null;
			if ($condition instanceof JssNode) {
				$this->validateExpression($condition, $types);
				$this->validateBoolCondition($condition, $types);
			}
			if ($statement->kind === 'if') {
				$this->validateStatements($statement->fields['then'] ?? [], $types);
				$this->validateStatements($statement->fields['else'] ?? [], $types);
			} else {
				$this->validateStatements($statement->fields['body'] ?? [], $types);
			}
			return $types;
		}
		if ($statement->kind === 'for') {
			$loopTypes = $types;
			$init = $statement->fields['init'] ?? null;
			if ($init instanceof JssNode) {
				$loopTypes = $this->validateStatement($init, $loopTypes);
			}
			$condition = $statement->fields['condition'] ?? null;
			if ($condition instanceof JssNode) {
				$this->validateExpression($condition, $loopTypes);
				$this->validateBoolCondition($condition, $loopTypes);
			}
			$step = $statement->fields['step'] ?? null;
			if ($step instanceof JssNode) {
				$this->validateStatement($step, $loopTypes);
			}
			$this->validateStatements($statement->fields['body'] ?? [], $loopTypes);
			return $types;
		}
		if ($statement->kind === 'foreach_value' || $statement->kind === 'foreach_key_value') {
			$loopTypes = $types;
			$source = $statement->fields['source'] ?? null;
			$sourceType = $source instanceof JssNode ? $this->expressionType($source, $types) : null;
			if ($source instanceof JssNode) {
				$this->validateExpression($source, $types);
				$this->validateForeachSource($statement, $sourceType);
			}
			if ($statement->kind === 'foreach_key_value') {
				$keyName = (string) ($statement->fields['key_name'] ?? '');
				if ($keyName !== '') {
					$loopTypes[$keyName] = 'string';
				}
			}
			$valueName = (string) ($statement->fields['value_name'] ?? '');
			if ($valueName !== '') {
				$loopTypes[$valueName] = $this->normalizeType($statement->fields['value_type'] ?? null) ?? $this->iterableValueType($sourceType) ?? '';
			}
			$this->validateStatements($statement->fields['body'] ?? [], $loopTypes);
			return $types;
		}
		if ($statement->kind === 'expr_stmt') {
			$expression = $statement->fields['expression'] ?? null;
			if ($expression instanceof JssNode) {
				$this->validateExpression($expression, $types);
			}
			return $types;
		}
		if (in_array($statement->kind, ['return', 'compound_assign', 'append', 'update', 'post_increment'], true)) {
			foreach (['value', 'target', 'expression'] as $field) {
				$expression = $statement->fields[$field] ?? null;
				if ($expression instanceof JssNode) {
					$this->validateExpression($expression, $types);
				}
			}
		}
		return $types;
	}

	/** @param array<string,string> $types */
	private function validateInitializer(JssNode $value, ?string $targetType, array $types): void
	{
		$valueType = $this->expressionType($value, $types);
		if ($value->kind === 'null' && !$this->isNullableType($targetType)) {
			throw new InputException($this->message('JSS `null` requires an explicit nullable target type such as `?T` or `T | null`', $value));
		}
		if ($targetType === 'int' && $valueType === 'float') {
			throw new InputException($this->message('JSS cannot assign `float` to `int` without an explicit conversion', $value));
		}
		if ($value->kind === 'array_literal' && !$this->isVectorType($targetType)) {
			throw new InputException($this->message('JSS array literals require an explicit `vector<T>` target type', $value));
		}
		if ($value->kind === 'object_literal' && !$this->isHashType($targetType)) {
			throw new InputException($this->message('JSS object/hash literals require an explicit `hash<T>` target type', $value));
		}
	}

	/** @param array<string,string> $types */
	private function validateBoolCondition(JssNode $condition, array $types): void
	{
		$type = $this->expressionType($condition, $types);
		if ($type !== 'bool') {
			throw new InputException($this->message('JSS conditions require `bool`. Use an explicit comparison or predicate instead of truthiness', $condition));
		}
	}

	/** @param array<string,string> $types */
	private function validateExpression(JssNode $expression, array $types): void
	{
		if ($expression->kind === 'binary') {
			$left = $expression->fields['left'] ?? null;
			$right = $expression->fields['right'] ?? null;
			if ($left instanceof JssNode) {
				$this->validateExpression($left, $types);
			}
			if ($right instanceof JssNode) {
				$this->validateExpression($right, $types);
			}
			if ((string) ($expression->fields['operator'] ?? '') === '+') {
				$this->validateBinaryPlus($expression, $types);
			}
			if ((string) ($expression->fields['operator'] ?? '') === '??') {
				$this->validateNullCoalesce($expression, $types);
			}
			if (in_array((string) ($expression->fields['operator'] ?? ''), ['&&', '||'], true)) {
				$this->validateLogicalBinary($expression, $types);
			}
			return;
		}
		if ($expression->kind === 'unary') {
			$inner = $expression->fields['expression'] ?? null;
			if ($inner instanceof JssNode) {
				$this->validateExpression($inner, $types);
			}
			if ((string) ($expression->fields['operator'] ?? '') === '!') {
				$this->validateLogicalUnary($expression, $types);
			}
			if ((string) ($expression->fields['operator'] ?? '') === '-') {
				$this->validateNumericUnary($expression, $types);
			}
			return;
		}
		if ($expression->kind === 'call') {
			$callee = $expression->fields['callee'] ?? null;
			if ($callee instanceof JssNode) {
				$this->validateExpression($callee, $types);
			}
			foreach (is_array($expression->fields['args'] ?? null) ? $expression->fields['args'] : [] as $arg) {
				if ($arg instanceof JssNode) {
					$this->validateExpression($arg, $types);
				}
			}
			$this->validateCall($expression, $types);
			return;
		}
		if ($expression->kind === 'ternary') {
			$condition = $expression->fields['condition'] ?? null;
			$whenTrue = $expression->fields['when_true'] ?? null;
			$whenFalse = $expression->fields['when_false'] ?? null;
			if ($condition instanceof JssNode) {
				$this->validateExpression($condition, $types);
				$this->validateBoolCondition($condition, $types);
			}
			if ($whenTrue instanceof JssNode) {
				$this->validateExpression($whenTrue, $types);
			}
			if ($whenFalse instanceof JssNode) {
				$this->validateExpression($whenFalse, $types);
			}
			$this->validateTernaryBranches($expression, $types);
			return;
		}
		foreach (['object', 'index'] as $field) {
			$child = $expression->fields[$field] ?? null;
			if ($child instanceof JssNode) {
				$this->validateExpression($child, $types);
			}
		}
		foreach (['items', 'pairs'] as $field) {
			foreach (is_array($expression->fields[$field] ?? null) ? $expression->fields[$field] : [] as $item) {
				if ($item instanceof JssNode) {
					$this->validateExpression($item, $types);
				} elseif (is_array($item) && ($item['value'] ?? null) instanceof JssNode) {
					$this->validateExpression($item['value'], $types);
				}
			}
		}
	}

	/** @param array<string,string> $types */
	private function validateBinaryPlus(JssNode $expression, array $types): void
	{
		$leftType = $this->expressionType($expression->fields['left'] ?? null, $types);
		$rightType = $this->expressionType($expression->fields['right'] ?? null, $types);
		if ($this->isDynamicType($leftType) || $this->isDynamicType($rightType)) {
			return;
		}
		if ($leftType === null || $rightType === null) {
			return;
		}
		if ($this->isNumericType($leftType) && $this->isNumericType($rightType)) {
			return;
		}
		if (($leftType === 'string' && $this->isPrintablePlusType($rightType)) || ($rightType === 'string' && $this->isPrintablePlusType($leftType))) {
			return;
		}
		throw new InputException($this->message('JSS `+` requires numeric operands, a `mixed`/`dynamic` boundary, or one static string operand plus a known printable type', $expression));
	}

	/** @param array<string,string> $types */
	private function validateLogicalBinary(JssNode $expression, array $types): void
	{
		$leftType = $this->expressionType($expression->fields['left'] ?? null, $types);
		$rightType = $this->expressionType($expression->fields['right'] ?? null, $types);
		if ($leftType === 'bool' && $rightType === 'bool') {
			return;
		}
		throw new InputException($this->message('JSS logical operators `&&` and `||` require `bool` operands. Use explicit comparisons or predicates instead of truthiness', $expression));
	}

	/** @param array<string,string> $types */
	private function validateLogicalUnary(JssNode $expression, array $types): void
	{
		$operandType = $this->expressionType($expression->fields['expression'] ?? null, $types);
		if ($operandType === 'bool') {
			return;
		}
		throw new InputException($this->message('JSS logical operator `!` requires a `bool` operand. Use an explicit comparison or predicate instead of truthiness', $expression));
	}

	/** @param array<string,string> $types */
	private function validateNumericUnary(JssNode $expression, array $types): void
	{
		$operandType = $this->expressionType($expression->fields['expression'] ?? null, $types);
		if ($this->isNumericType($operandType)) {
			return;
		}
		throw new InputException($this->message('JSS unary `-` requires an `int` or `float` operand', $expression));
	}

	/** @param array<string,string> $types */
	private function validateNullCoalesce(JssNode $expression, array $types): void
	{
		$leftType = $this->expressionType($expression->fields['left'] ?? null, $types);
		$rightType = $this->expressionType($expression->fields['right'] ?? null, $types);
		if (!$this->isNullableType($leftType) && !$this->isDynamicType($leftType)) {
			throw new InputException($this->message('JSS `??` requires a nullable or explicit `mixed`/`dynamic` left operand', $expression));
		}
		if ($this->isDynamicType($leftType) || $leftType === null || $rightType === null) {
			return;
		}
		$baseType = $this->stripNullableType($leftType);
		if ($baseType === null) {
			return;
		}
		if ($baseType === $rightType) {
			return;
		}
		if ($baseType === 'float' && $rightType === 'int') {
			return;
		}
		throw new InputException($this->message('JSS `??` fallback must match the nullable value type in the current subset', $expression));
	}

	private function validateForeachSource(JssNode $statement, ?string $sourceType): void
	{
		if ($sourceType === null || $this->isDynamicType($sourceType)) {
			return;
		}
		if ($statement->kind === 'foreach_key_value' && !$this->isHashType($sourceType)) {
			throw new InputException($this->message('JSS key/value `for...of` requires a `hash<T>` source', $statement));
		}
		if (!$this->isVectorType($sourceType) && !$this->isHashType($sourceType)) {
			throw new InputException($this->message('JSS `for...of` requires a `vector<T>` or `hash<T>` source', $statement));
		}
	}

	/** @param array<string,string> $types */
	private function validateTernaryBranches(JssNode $expression, array $types): void
	{
		$trueType = $this->expressionType($expression->fields['when_true'] ?? null, $types);
		$falseType = $this->expressionType($expression->fields['when_false'] ?? null, $types);
		if ($trueType === null || $falseType === null) {
			return;
		}
		if ($trueType === $falseType) {
			return;
		}
		if ($trueType === 'null' && $falseType !== 'null') {
			return;
		}
		if ($falseType === 'null' && $trueType !== 'null') {
			return;
		}
		throw new InputException($this->message('JSS ternary branches must resolve to the same type or a `T` / `null` pair in the current subset', $expression));
	}

	/** @param array<string,string> $types */
	private function expressionType(mixed $expression, array $types): ?string
	{
		if (!$expression instanceof JssNode) {
			return null;
		}
		if ($expression->kind === 'string' || $expression->kind === 'template') {
			return 'string';
		}
		if ($expression->kind === 'number') {
			return str_contains((string) $expression->fields['value'], '.') ? 'float' : 'int';
		}
		if ($expression->kind === 'boolean') {
			return 'bool';
		}
		if ($expression->kind === 'null') {
			return 'null';
		}
		if ($expression->kind === 'identifier') {
			$name = (string) ($expression->fields['name'] ?? '');
			$type = $types[$name] ?? null;
			return $type !== '' ? $type : null;
		}
		if ($expression->kind === 'ternary') {
			$trueType = $this->expressionType($expression->fields['when_true'] ?? null, $types);
			$falseType = $this->expressionType($expression->fields['when_false'] ?? null, $types);
			if ($trueType === $falseType) {
				return $trueType;
			}
			if ($trueType === 'null' && $falseType !== null && $falseType !== 'null') {
				return '?' . $falseType;
			}
			if ($falseType === 'null' && $trueType !== null && $trueType !== 'null') {
				return '?' . $trueType;
			}
			return $trueType ?? $falseType;
		}
		if ($expression->kind === 'unary' && (string) ($expression->fields['operator'] ?? '') === '!') {
			return 'bool';
		}
		if ($expression->kind === 'unary' && (string) ($expression->fields['operator'] ?? '') === '-') {
			$innerType = $this->expressionType($expression->fields['expression'] ?? null, $types);
			return $this->isNumericType($innerType) ? $innerType : null;
		}
		if ($expression->kind === 'binary') {
			$operator = (string) ($expression->fields['operator'] ?? '');
			if (in_array($operator, ['===', '!==', '==', '!=', '<', '>', '<=', '>=', '&&', '||'], true)) {
				return 'bool';
			}
			if ($operator === '??') {
				$leftType = $this->expressionType($expression->fields['left'] ?? null, $types);
				$rightType = $this->expressionType($expression->fields['right'] ?? null, $types);
				if ($this->isDynamicType($leftType)) {
					return $leftType;
				}
				$baseType = $this->stripNullableType($leftType);
				if ($baseType !== null && ($rightType === null || $rightType === $baseType || ($baseType === 'float' && $rightType === 'int'))) {
					return $baseType;
				}
				return $rightType;
			}
			if (in_array($operator, ['+', '-', '*', '/', '%'], true)) {
				$leftType = $this->expressionType($expression->fields['left'] ?? null, $types);
				$rightType = $this->expressionType($expression->fields['right'] ?? null, $types);
				if ($operator === '+' && ($leftType === 'string' || $rightType === 'string')) {
					return 'string';
				}
				if ($this->isNumericType($leftType) && $this->isNumericType($rightType)) {
					if ($operator === '%') {
						return 'int';
					}
					return $leftType === 'float' || $rightType === 'float' || $operator === '/' ? 'float' : 'int';
				}
			}
		}
		if ($expression->kind === 'call') {
			$callee = $expression->fields['callee'] ?? null;
			if ($callee instanceof JssNode && $callee->kind === 'identifier') {
				$name = strtolower((string) ($callee->fields['name'] ?? ''));
				return self::BUILTIN_CALL_RETURN_TYPES[$name] ?? null;
			}
			if ($callee instanceof JssNode && $callee->kind === 'member') {
				return $this->callSurface->resolveCallReturnType($this->flattenMemberChain($callee));
			}
		}
		return null;
	}

	/** @param array<string,string> $types */
	private function validateCall(JssNode $call, array $types): void
	{
		$callee = $call->fields['callee'] ?? null;
		if (!$callee instanceof JssNode || $callee->kind !== 'identifier') {
			return;
		}
		if (strtolower((string) ($callee->fields['name'] ?? '')) !== 'take') {
			return;
		}
		$args = is_array($call->fields['args'] ?? null) ? $call->fields['args'] : [];
		$arity = count($args);
		if ($arity !== 2 && $arity !== 3) {
			throw new InputException($this->message('JSS `take(...)` expects exactly 2 or 3 arguments in the current subset', $call));
		}
		for ($i = 0; $i < $arity - 1; $i++) {
			$output = $args[$i] ?? null;
			if (!$output instanceof JssNode || $output->kind !== 'identifier') {
				throw new InputException($this->message('JSS `take(...)` output arguments must be simple local variables', $output instanceof JssNode ? $output : $call));
			}
		}
		$source = $args[$arity - 1] ?? null;
		$sourceType = $this->expressionType($source, $types);
		if (!is_string($sourceType) || $sourceType === '') {
			return;
		}
		$contract = $this->takeContractResolver->resolve($sourceType);
		if ($contract === null) {
			throw new InputException($this->message('JSS `take(...)` requires a nullable<T>, result<T>, result_or_false<T>, or result_or_bool<T> source expression', $call));
		}
		$expectedOutputCount = count($contract['output_types']);
		if ($arity !== $expectedOutputCount + 1) {
			throw new InputException($this->message($this->takeArityMessage((string) $contract['family']), $call));
		}
		foreach ($contract['output_types'] as $index => $expectedType) {
			$this->assertTakeOutputType($args[$index] ?? null, $expectedType, $types, $call);
		}
	}

	private function takeArityMessage(string $family): string
	{
		return match ($family) {
			'nullable', 'result_or_false' => 'JSS `take(...)` for nullable/result_or_false sources requires one output variable plus the source expression',
			'result' => 'JSS `take(...)` for result<T> sources requires two output variables plus the source expression',
			'result_or_bool' => 'JSS `take(...)` for result_or_bool<T> sources requires two output variables plus the source expression',
			default => 'JSS `take(...)` requires a supported wrapper source expression',
		};
	}

	/** @param array<string,string> $types */
	private function assertTakeOutputType(mixed $output, string $expectedType, array $types, JssNode $call): void
	{
		if (!$output instanceof JssNode || $output->kind !== 'identifier') {
			throw new InputException($this->message('JSS `take(...)` output arguments must be simple local variables', $call));
		}
		$name = (string) ($output->fields['name'] ?? '');
		$actualType = $types[$name] ?? null;
		if ($actualType === null || $actualType === '') {
			return;
		}
		if ($actualType !== strtolower($expectedType)) {
			throw new InputException($this->message('JSS `take(...)` expects output `' . $name . '` to have type `' . strtolower($expectedType) . '` but found `' . $actualType . '`', $output));
		}
	}

	/** @return array<string,string> */
	private function paramTypes(mixed $params): array
	{
		$types = [];
		foreach (is_array($params) ? $params : [] as $param) {
			if (!is_array($param)) {
				continue;
			}
			$name = (string) ($param['name'] ?? '');
			if ($name !== '') {
				$types[$name] = $this->normalizeType($param['type'] ?? null) ?? '';
			}
		}
		return $types;
	}

	private function normalizeType(mixed $type): ?string
	{
		if (!is_string($type) || $type === '') {
			return null;
		}
		return strtolower($type);
	}

	private function isNumericType(?string $type): bool
	{
		return in_array($type, ['int', 'float'], true);
	}

	private function isDynamicType(?string $type): bool
	{
		return in_array($type, ['mixed', 'dynamic'], true);
	}

	private function isPrintablePlusType(?string $type): bool
	{
		return in_array($type, ['string', 'int', 'float', 'bool'], true);
	}

	private function isNullableType(?string $type): bool
	{
		return is_string($type) && ($type[0] === '?' || str_contains($type, '|null') || str_contains($type, 'null|'));
	}

	private function stripNullableType(?string $type): ?string
	{
		if (!is_string($type) || $type === '') {
			return null;
		}
		if ($type[0] === '?') {
			return substr($type, 1);
		}
		if (str_ends_with($type, '|null')) {
			return substr($type, 0, -strlen('|null'));
		}
		if (str_starts_with($type, 'null|')) {
			return substr($type, strlen('null|'));
		}
		return null;
	}

	private function isVectorType(?string $type): bool
	{
		return is_string($type) && str_starts_with($type, 'vector<');
	}

	private function isHashType(?string $type): bool
	{
		return is_string($type) && str_starts_with($type, 'hash<');
	}

	private function iterableValueType(?string $type): ?string
	{
		if (!$this->isVectorType($type) && !$this->isHashType($type)) {
			return null;
		}
		$start = strpos((string) $type, '<');
		$end = strrpos((string) $type, '>');
		if ($start === false || $end === false || $end <= $start + 1) {
			return null;
		}
		return substr((string) $type, $start + 1, $end - $start - 1);
	}

	/** @return list<string> */
	private function flattenMemberChain(JssNode $member): array
	{
		$parts = [(string) ($member->fields['member'] ?? '')];
		$object = $member->fields['object'] ?? null;
		while ($object instanceof JssNode && $object->kind === 'member') {
			array_unshift($parts, (string) ($object->fields['member'] ?? ''));
			$object = $object->fields['object'] ?? null;
		}
		if ($object instanceof JssNode && $object->kind === 'identifier') {
			array_unshift($parts, (string) ($object->fields['name'] ?? ''));
		}
		return array_values(array_filter($parts, static fn (string $part): bool => $part !== ''));
	}

	private function message(string $message, JssNode $node): string
	{
		$range = $node->fields['range'] ?? null;
		if (!is_array($range)) {
			return $message . '.';
		}
		$line = (int) ($range['line'] ?? 0);
		$column = (int) ($range['column'] ?? 0);
		if ($line <= 0 || $column <= 0) {
			return $message . '.';
		}
		return $message . ' at ' . $line . ':' . $column . '.';
	}
}
