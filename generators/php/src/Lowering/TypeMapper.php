<?php
declare(strict_types=1);

namespace Scpp\S2S\Lowering;

use Scpp\S2S\Support\GenerationException;

/**
 * Centralized type mapping.
 *
 * Keeping this in one place is essential for consistency and simplicity.
 */
final class TypeMapper
{
	/** @var array<string, bool> */
	private array $enumNames = [];

	/**
	 * Maps a declared PHP property or constant-adjacent type into the canonical Prism++ type.
	 *
	 * Relationship to specs:
	 * - preserves the subset and lowering rules documented for the prototype
	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit
	 */
	/** @param array<string, bool> $enumNames */
	public function setEnumNames(array $enumNames): void
	{
		$this->enumNames = $enumNames;
	}

	/** @return list<string> */
	public function splitUnionTypes(string $phpType): array
	{
		$normalized = trim($phpType);
		if ($normalized === '' || !str_contains($normalized, '|')) {
			return $normalized === '' ? [] : [$normalized];
		}

		return array_values(array_filter(array_map(static fn (string $part): string => trim($part), explode('|', $normalized)), static fn (string $part): bool => $part !== ''));
	}

	public function getPrimaryDeclaredType(string $phpType): string
	{
		$parts = $this->splitUnionTypes($phpType);
		return $parts[0] ?? trim($phpType);
	}

	public function normalizeNullableUnionType(string $phpType): string
	{
		$normalized = trim($phpType);
		$parts = $this->splitUnionTypes($normalized);
		if (count($parts) <= 1) {
			return $normalized;
		}

		$nonNullParts = [];
		$hasNull = false;
		foreach ($parts as $part) {
			$trimmed = trim($part);
			if (strtolower($trimmed) === 'null') {
				$hasNull = true;
				continue;
			}
			$nonNullParts[] = $trimmed;
		}

		if ($hasNull && count($nonNullParts) === 1) {
			return '?' . ltrim($nonNullParts[0], '?');
		}

		return $normalized;
	}

	public function mapDeclaredType(string $phpType): string
	{
		$phpType = $this->normalizeNullableUnionType($phpType);
		$phpType = $this->getPrimaryDeclaredType($phpType);
		$normalized = trim($phpType);
		if ($this->isFunctionType($normalized)) {
			return $this->mapFunctionType($normalized);
		}

		$phpType = $this->guardTypeDefinitionSyntax($phpType);
		if ($this->isInlineValueType($phpType)) {
			return 'value_p<' . $this->mapUserTypeName($this->unwrapInlineValueType($phpType)) . '>';
		}

		if ($this->isNullableInlineValueType($phpType)) {
			return 'nullable<' . $this->mapUserTypeName($this->unwrapNullableInlineValueType($phpType)) . '>';
		}

		if ($this->isGenericNullableType($phpType)) {
			return 'nullable<' . $this->mapDeclaredType($this->unwrapGenericNullableType($phpType)) . '>';
		}

		if ($this->isOrFalseType($phpType)) {
			return 'result_or_false<' . $this->mapDeclaredType($this->unwrapOrFalseType($phpType)) . '>';
		}

		if ($this->isOrBoolType($phpType)) {
			return 'result_or_bool<' . $this->mapDeclaredType($this->unwrapOrBoolType($phpType)) . '>';
		}

		if ($this->isOrErrorType($phpType)) {
			return 'result<' . $this->mapDeclaredType($this->unwrapOrErrorType($phpType)) . '>';
		}

		if (str_starts_with($phpType, '?')) {
			$inner = substr($phpType, 1);
			if ($this->isDirectHandleType($inner) || $this->isHandleAliasType($inner)) {
				return $this->mapValueType($inner);
			}
			if ($this->isObjectType($inner)) {
				return $this->isEnumType($inner) ? $this->mapUserTypeName($inner) : 'shared_p<' . $this->mapUserTypeName($inner) . '>';
			}
			return 'nullable<' . $this->mapValueType($inner) . '>';
		}

		if ($this->isDirectHandleType($phpType) || $this->isHandleAliasType($phpType)) {
			return $this->mapValueType($phpType);
		}

		if ($this->isObjectType($phpType)) {
			return $this->isEnumType($phpType) ? $this->mapUserTypeName($phpType) : 'shared_p<' . $this->mapUserTypeName($phpType) . '>';
		}

		return $this->mapValueType($phpType);
	}

	/**

	 * Maps a parameter type, including reference-specific object-handle conventions from the current rules.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	public function mapParamType(?string $phpType, bool $explicitRef): string
	{
		if ($phpType === null) {
			return $explicitRef ? 'auto&' : 'auto';
		}

		$mapped = $this->mapDeclaredType($phpType);
		if ($explicitRef) {
			return $this->appendLvalueReference($mapped);
		}

		if ($mapped === 'string_t' || str_starts_with($mapped, 'vector_t<') || str_starts_with($mapped, 'hash_t<')) {
			return 'const ' . $mapped . '&';
		}

		return $mapped;
	}

	/**

	 * Maps a function or method return type, including reference-aware object behavior where supported.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	public function mapReturnType(?string $phpType, bool $explicitRef): string
	{
		if ($phpType === null) {
			return $explicitRef ? 'auto&' : 'auto';
		}

		$mapped = $this->mapDeclaredType($phpType);
		return $explicitRef ? $this->appendLvalueReference($mapped) : $mapped;
	}

	/**

	 * Maps a typed local-variable annotation into the same value-type space used for declarations.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	public function mapTypedLocalType(string $phpType): string
	{
		$normalized = trim($phpType);
		if ($this->isFunctionType($normalized)) {
			return $this->mapFunctionType($normalized);
		}

		$phpType = $this->guardTypeDefinitionSyntax($phpType);
		if ($this->isRefLocalType($phpType)) {
			return $this->appendLvalueReference($this->mapDeclaredType($this->unwrapRefLocalType($phpType)));
		}

		return $this->mapDeclaredType($phpType);
	}


	public function mapReferenceProxyType(string $phpType): ?string
	{
		// Typed scalar by-reference proxy lowering (int_ref/float_ref/bool_ref/string_ref)
		// is no longer generated by the S2S layer. Scalar typed refs now use the
		// normalized template path, so keep the proxy hook disabled.
		return null;
	}

	public function mapReferenceProxyFactory(string $phpType): ?string
	{
		return null;
	}
	public function isVectorType(string $phpType): bool
	{
		$normalized = trim($phpType);
		return preg_match('/^(?:vector|vector_t)<.+>$/', $normalized) === 1;
	}

	public function mapVectorType(string $phpType): string
	{
		$normalized = trim($phpType);
		if (preg_match('/^(?:vector|vector_t)<(.+)>$/', $normalized, $matches) !== 1) {
			return $this->mapDeclaredType($phpType);
		}

		$inner = trim($matches[1]);
		return 'vector_t<' . $this->mapDeclaredType($inner) . '>';
	}

	public function isHashType(string $phpType): bool
	{
		$normalized = trim($phpType);
		return preg_match('/^(?:hash|hash_t)<.+>$/', $normalized) === 1;
	}

	public function mapHashType(string $phpType): string
	{
		$normalized = trim($phpType);
		if (preg_match('/^(?:hash|hash_t)<(.+)>$/', $normalized, $matches) !== 1) {
			return $this->mapDeclaredType($phpType);
		}

		$args = $this->splitTopLevelGenericArgs($matches[1]);
		if (count($args) < 1 || count($args) > 2) {
			return $this->mapDeclaredType($phpType);
		}

		$valueType = $this->mapDeclaredType($args[0]);
		if (count($args) === 1) {
			return 'hash_t<' . $valueType . '>';
		}

		$keyType = $this->mapDeclaredType($args[1]);
		return 'hash_t<' . $valueType . ', ' . $keyType . '>';
	}

	/** @return list<string> */
	public function splitTopLevelGenericArgs(string $payload): array
	{
		$parts = [];
		$current = '';
		$depth = 0;
		$length = strlen($payload);
		for ($i = 0; $i < $length; ++$i) {
			$ch = $payload[$i];
			if ($ch === '<') {
				$depth++;
				$current .= $ch;
				continue;
			}
			if ($ch === '>') {
				$depth--;
				$current .= $ch;
				continue;
			}
			if ($ch === ',' && $depth === 0) {
				$trimmed = trim($current);
				if ($trimmed !== '') {
					$parts[] = $trimmed;
				}
				$current = '';
				continue;
			}
			$current .= $ch;
		}

		$trimmed = trim($current);
		if ($trimmed !== '') {
			$parts[] = $trimmed;
		}

		return $parts;
	}

	public function isInlineValueType(string $phpType): bool
	{
		$normalized = trim($phpType);
		if (str_starts_with($normalized, 'value ')) {
			$inner = trim(substr($normalized, strlen('value ')));
			if (str_starts_with($inner, '?')) {
				return false;
			}
			return $this->isObjectType($inner);
		}

		if (preg_match('/^value\s*<\s*(.+)\s*>$/', $normalized, $matches) !== 1) {
			return false;
		}

		$inner = trim($matches[1]);
		if ($inner === '' || str_starts_with($inner, '?') || str_starts_with($inner, 'value<') || str_starts_with($inner, 'value <')) {
			return false;
		}

		return $this->isObjectType($inner);
	}

	public function isNullableInlineValueType(string $phpType): bool
	{
		$normalized = trim($phpType);
		if (preg_match('/^value\s*<\s*\?\s*(.+)\s*>$/', $normalized, $matches) !== 1) {
			return false;
		}

		$inner = trim($matches[1]);
		if ($inner === '' || preg_match('/^(?:value|shared|unique|weak|weakref|shared_p|unique_p|weak_p)\s*(?:<|$)/', $inner) === 1) {
			return false;
		}

		return $this->isObjectType($inner);
	}

	public function unwrapInlineValueType(string $phpType): string
	{
		$normalized = trim($phpType);
		if (str_starts_with($normalized, 'value ')) {
			return trim(substr($normalized, strlen('value ')));
		}

		if (preg_match('/^value\s*<\s*(.+)\s*>$/', $normalized, $matches) === 1) {
			return trim($matches[1]);
		}

		return $normalized;
	}

	public function unwrapNullableInlineValueType(string $phpType): string
	{
		$normalized = trim($phpType);
		if (preg_match('/^value\s*<\s*\?\s*(.+)\s*>$/', $normalized, $matches) === 1) {
			return trim($matches[1]);
		}

		return $normalized;
	}

	public function isGenericNullableType(string $phpType): bool
	{
		$normalized = trim($phpType);
		if (preg_match('/^nullable\s*<\s*(.+)\s*>$/', $normalized, $matches) !== 1) {
			return false;
		}

		$inner = trim($matches[1]);
		return $inner !== '' && preg_match('/^(?:nullable|value|shared|unique|weak|weakref|shared_p|unique_p|weak_p)\s*(?:<|$)/', $inner) !== 1;
	}

	public function unwrapGenericNullableType(string $phpType): string
	{
		$normalized = trim($phpType);
		if (preg_match('/^nullable\s*<\s*(.+)\s*>$/', $normalized, $matches) === 1) {
			return trim($matches[1]);
		}

		return $normalized;
	}

	public function isOrFalseType(string $phpType): bool
	{
		$normalized = trim($phpType);
		if (preg_match('/^result_or_false\s*<\s*(.+)\s*>$/', $normalized, $matches) !== 1) {
			return false;
		}
		$inner = trim($matches[1]);
		return $inner !== '' && preg_match('/^(?:value|shared|unique|weak|weakref|shared_p|unique_p|weak_p|result_or_false|result_or_bool|result)\s*(?:<|$)/', $inner) !== 1;
	}

	public function unwrapOrFalseType(string $phpType): string
	{
		$normalized = trim($phpType);
		if (preg_match('/^result_or_false\s*<\s*(.+)\s*>$/', $normalized, $matches) === 1) {
			return trim($matches[1]);
		}
		return $normalized;
	}


	public function isOrBoolType(string $phpType): bool
	{
		$normalized = trim($phpType);
		if (preg_match('/^result_or_bool\s*<\s*(.+)\s*>$/', $normalized, $matches) !== 1) {
			return false;
		}
		$inner = trim($matches[1]);
		return $inner !== '' && preg_match('/^(?:value|shared|unique|weak|weakref|shared_p|unique_p|weak_p|result_or_false|result_or_bool|result)\s*(?:<|$)/', $inner) !== 1;
	}

	public function unwrapOrBoolType(string $phpType): string
	{
		$normalized = trim($phpType);
		if (preg_match('/^result_or_bool\s*<\s*(.+)\s*>$/', $normalized, $matches) === 1) {
			return trim($matches[1]);
		}
		return $normalized;
	}

	public function isOrErrorType(string $phpType): bool
	{
		$normalized = trim($phpType);
		if (preg_match('/^result\s*<\s*(.+)\s*>$/', $normalized, $matches) !== 1) {
			return false;
		}
		$inner = trim($matches[1]);
		return $inner !== '' && preg_match('/^(?:value|shared|unique|weak|weakref|shared_p|unique_p|weak_p|result_or_false|result_or_bool|result)\s*(?:<|$)/', $inner) !== 1;
	}

	public function unwrapOrErrorType(string $phpType): string
	{
		$normalized = trim($phpType);
		if (preg_match('/^result\s*<\s*(.+)\s*>$/', $normalized, $matches) === 1) {
			return trim($matches[1]);
		}
		return $normalized;
	}

	public function isBareObjectWrapperShortcut(string $phpType): bool
	{
		return in_array(trim($phpType), ['value', 'shared', 'unique', 'result_or_false', 'result_or_bool', 'result'], true);
	}

	public function specializeBareObjectWrapperShortcut(string $wrapper, string $phpType): string
	{
		$normalizedWrapper = trim($wrapper);
		if (!$this->isBareObjectWrapperShortcut($normalizedWrapper)) {
			throw new GenerationException('Unsupported bare object-wrapper shortcut: ' . $wrapper);
		}

		$normalizedType = $this->guardTypeDefinitionSyntax($phpType);
		if (!$this->isObjectType($normalizedType)) {
			throw new GenerationException('Bare object-wrapper shortcuts require a user object type: ' . $phpType);
		}

		return $normalizedWrapper . '<' . $normalizedType . '>';
	}



	public function hasInvalidNestedWrapperType(string $phpType): bool
	{
		$normalized = $this->guardTypeDefinitionSyntax($phpType);
		foreach (['value', 'shared', 'unique', 'result_or_false', 'result_or_bool', 'result'] as $wrapper) {
			if (preg_match('/^' . preg_quote($wrapper, '/') . '\s*<\s*(.+)\s*>$/', $normalized, $matches) !== 1) {
				continue;
			}

			$inner = trim($matches[1]);
			if ($inner === '') {
				return false;
			}

			if (preg_match('/^(?:value|shared|unique)\s*(?:<|$)/', $inner) === 1) {
				return true;
			}
		}

		return false;
	}

	public function isRefLocalType(string $phpType): bool
	{
		return str_starts_with($phpType, 'ref ');
	}

	public function unwrapRefLocalType(string $phpType): string
	{
		return trim(substr($phpType, strlen('ref ')));
	}

	public function isObjectLikeType(string $phpType): bool
	{
		if ($this->isInlineValueType($phpType)) {
			return false;
		}

		if ($this->isRefLocalType($phpType)) {
			$phpType = $this->unwrapRefLocalType($phpType);
		}

		if (str_starts_with($phpType, '?')) {
			$phpType = substr($phpType, 1);
		}

		return $this->isObjectType($phpType);
	}


	/**

	 * Maps one scalar/object PHP type name into its runtime-backed Prism++ counterpart.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */


	public function mapClassName(string $phpType): string
	{
		return $this->mapUserTypeName($phpType);
	}

	private function appendLvalueReference(string $mappedType): string
	{
		if (str_contains($mappedType, '&&') || str_contains($mappedType, '*')) {
			throw new GenerationException('Unsupported C++ type form in reference lowering: ' . $mappedType);
		}
		if (str_contains($mappedType, '&')) {
			throw new GenerationException('Type mapping attempted to create a nested or pre-existing reference type: ' . $mappedType);
		}

		return $mappedType . '&';
	}


	private function isFunctionType(string $phpType): bool
	{
		return preg_match('/^function\s*<.*>$/', trim($phpType)) === 1;
	}

	private function mapFunctionType(string $phpType): string
	{
		$normalized = trim($phpType);
		if (preg_match('/^function\s*<\s*(.+)\s*>$/', $normalized, $matches) !== 1) {
			throw new GenerationException('Invalid function type syntax: ' . $phpType);
		}

		$inner = trim($matches[1]);
		$signature = $this->splitFunctionTypeSignature($inner);
		if ($signature === null) {
			throw new GenerationException('Invalid function type syntax: ' . $phpType);
		}

		$returnType = $signature['return'];
		$paramsInner = $signature['params'];
		if ($returnType === '') {
			throw new GenerationException('Function type requires an explicit return type: ' . $phpType);
		}

		$mappedReturn = $this->mapReturnType($returnType, false);
		$mappedParams = [];
		foreach ($this->splitTopLevelCommaList($paramsInner) as $param) {
			$param = trim($param);
			if ($param === '') {
				continue;
			}
			$byRef = false;
			if (str_starts_with($param, 'ref ')) {
				$byRef = true;
				$param = trim(substr($param, 4));
			} elseif (str_ends_with($param, '&')) {
				$byRef = true;
				$param = rtrim(substr($param, 0, -1));
			}
			$mappedParams[] = $this->mapParamType($param, $byRef);
		}

		return 'std::function<' . $mappedReturn . '(' . implode(', ', $mappedParams) . ')>';
	}

	/** @return array{return:string,params:string}|null */
	private function splitFunctionTypeSignature(string $inner): ?array
	{
		$angleDepth = 0;
		$parenDepth = 0;
		$open = null;
		$length = strlen($inner);
		for ($i = 0; $i < $length; ++$i) {
			$ch = $inner[$i];
			if ($ch === '<') {
				++$angleDepth;
				continue;
			}
			if ($ch === '>') {
				--$angleDepth;
				continue;
			}
			if ($ch === '(') {
				if ($angleDepth === 0 && $parenDepth === 0) {
					$open = $i;
					break;
				}
				++$parenDepth;
				continue;
			}
			if ($ch === ')' && $parenDepth > 0) {
				--$parenDepth;
			}
		}

		if (!is_int($open)) {
			return null;
		}

		$angleDepth = 0;
		$parenDepth = 0;
		$close = null;
		for ($i = $open; $i < $length; ++$i) {
			$ch = $inner[$i];
			if ($ch === '<') {
				++$angleDepth;
			} elseif ($ch === '>') {
				--$angleDepth;
			} elseif ($ch === '(') {
				++$parenDepth;
			} elseif ($ch === ')') {
				--$parenDepth;
				if ($angleDepth === 0 && $parenDepth === 0) {
					$close = $i;
					break;
				}
			}
		}

		if (!is_int($close) || $close < $open) {
			return null;
		}

		return [
			'return' => trim(substr($inner, 0, $open)),
			'params' => trim(substr($inner, $open + 1, $close - $open - 1)),
		];
	}

	/** @return list<string> */
	private function splitTopLevelCommaList(string $value): array
	{
		$value = trim($value);
		if ($value === '') {
			return [];
		}

		$out = [];
		$current = '';
		$angleDepth = 0;
		$parenDepth = 0;
		$length = strlen($value);
		for ($i = 0; $i < $length; ++$i) {
			$ch = $value[$i];
			if ($ch === '<') {
				++$angleDepth;
			} elseif ($ch === '>') {
				--$angleDepth;
			} elseif ($ch === '(') {
				++$parenDepth;
			} elseif ($ch === ')') {
				--$parenDepth;
			} elseif ($ch === ',' && $angleDepth == 0 && $parenDepth == 0) {
				$out[] = trim($current);
				$current = '';
				continue;
			}
			$current .= $ch;
		}
		if (trim($current) !== '') {
			$out[] = trim($current);
		}
		return $out;
	}

	private function guardTypeDefinitionSyntax(string $phpType): string
	{
		$normalized = trim($phpType);
		if (str_contains($normalized, '&&')) {
			throw new GenerationException('Rvalue references (&&) are not supported in type definitions: ' . $phpType);
		}
		if (str_contains($normalized, '*')) {
			throw new GenerationException('Pointer syntax (*) is not supported in type definitions: ' . $phpType);
		}
		if (str_contains($normalized, '&')) {
			throw new GenerationException('Reference syntax (&) must not appear inside type definitions. Use explicit PHP reference forms instead: ' . $phpType);
		}
		if (substr_count($normalized, '?') > 1) {
			throw new GenerationException('Nullable marker (?) appears more than once in type definition: ' . $phpType);
		}
		if ($this->hasDisallowedNullableMarkerPosition($normalized)) {
			throw new GenerationException('Nullable marker (?) is only supported as a leading type marker or in value<?T>: ' . $phpType);
		}
		if ((str_contains($normalized, '<') || str_contains($normalized, '>')) && preg_match('/^(?:nullable|value|shared|unique|weak|weakref|function|vector|vector_t|hash|hash_t|result_or_false|result_or_bool|result)\s*<.+>$|^(?:shared_p|unique_p|weak_p)<.+>$/', $normalized) !== 1) {
			throw new GenerationException('Unsupported explicit type syntax: ' . $phpType);
		}
		if (preg_match('/^value\s*<\s*(.+)\s*>$/', $normalized, $matches) === 1) {
			$body = trim($matches[1]);
			if (str_starts_with($body, '?')) {
				$body = trim(substr($body, 1));
			}
			if ($body === '') {
				throw new GenerationException('Invalid value<T> type syntax: ' . $phpType);
			}
			if (preg_match('/^(?:value|shared|unique|weak|weakref|shared_p|unique_p|weak_p)\s*(?:<|$)/', $body) === 1) {
				throw new GenerationException('Invalid nested wrapper type: ' . $phpType . ' is not allowed.');
			}
		}

		return $normalized;
	}

	private function hasDisallowedNullableMarkerPosition(string $phpType): bool
	{
		if (!str_contains($phpType, '?')) {
			return false;
		}
		if (str_starts_with($phpType, '?')) {
			return false;
		}
		if (preg_match('/^value\s*<\s*\?\s*.+\s*>$/', $phpType) === 1) {
			return false;
		}

		$angleDepth = 0;
		$length = strlen($phpType);
		for ($i = 0; $i < $length; ++$i) {
			$ch = $phpType[$i];
			if ($ch === '<') {
				++$angleDepth;
				continue;
			}
			if ($ch === '>') {
				--$angleDepth;
				continue;
			}
			if ($ch === '?' && $angleDepth === 0) {
				return true;
			}
		}

		return false;
	}

	private function mapValueType(string $phpType): string
	{
		if ($this->isVectorType($phpType)) {
			return $this->mapVectorType($phpType);
		}

		if ($this->isHashType($phpType)) {
			return $this->mapHashType($phpType);
		}

		if ($this->isDirectHandleType($phpType)) {
			return $this->normalizeHandleType($phpType);
		}

		if ($this->isHandleAliasType($phpType)) {
			return $this->normalizeHandleAliasType($phpType);
		}

		return match ($phpType) {
			'int' => 'int_t',
			'float' => 'float_t',
			'bool' => 'bool_t',
			'string' => 'string_t',
			'array' => 'mixed_t',
			'mixed' => 'mixed_t',
			'void' => 'void',
			'error' => 'error_t',
			'resource_handle' => 'resource_handle_t',
			'nullable_resource_handle' => 'nullable_resource_handle_t',
			'falseable_resource_handle' => 'falseable_resource_handle_t',
			'vector_t' => 'vector_t',
			default => $this->mapUserTypeName($phpType),
		};
	}


	private function isDirectHandleType(string $phpType): bool
	{
		$normalized = trim($phpType);
		return preg_match('/^(?:shared_p|unique_p|weak_p)<.+>$/', $normalized) === 1;
	}

	private function isHandleAliasType(string $phpType): bool
	{
		$normalized = trim($phpType);
		return preg_match('/^(?:shared|unique|weak|weakref)<.+>$/', $normalized) === 1;
	}

	private function normalizeHandleType(string $phpType): string
	{
		$normalized = trim($phpType);
		if (preg_match('/^(shared_p|unique_p|weak_p)<(.+)>$/', $normalized, $matches) !== 1) {
			return $normalized;
		}

		$wrapper = $matches[1];
		$inner = trim($matches[2]);
		return $wrapper . '<' . $this->mapUserTypeName($inner) . '>';
	}

	private function normalizeHandleAliasType(string $phpType): string
	{
		$normalized = trim($phpType);
		if (preg_match('/^(shared|unique|weak|weakref)<(.+)>$/', $normalized, $matches) !== 1) {
			return $normalized;
		}

		$wrapper = match ($matches[1]) {
			'shared' => 'shared_p',
			'unique' => 'unique_p',
			'weak', 'weakref' => 'weak_p',
		};
		$inner = trim($matches[2]);
		return $wrapper . '<' . $this->mapUserTypeName($inner) . '>';
	}

	private function mapUserTypeName(string $phpType): string
	{
		$trimmed = ltrim($phpType, '\\');
		if (str_contains($trimmed, '\\')) {
			return str_replace('\\', '::', $trimmed);
		}

		return $trimmed;
	}

	/**

	 * Classifies whether a PHP type name should lower to an owning/shared object handle.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */


	private function isEnumType(string $phpType): bool
	{
		$trimmed = ltrim(trim($phpType), '\\');
		return isset($this->enumNames[$trimmed]);
	}
	private function isObjectType(string $phpType): bool
	{
		if ($this->isVectorType($phpType)) {
			return false;
		}

		if ($this->isHashType($phpType)) {
			return false;
		}

		if ($this->isDirectHandleType($phpType) || $this->isHandleAliasType($phpType)) {
			return false;
		}

		return !in_array($phpType, [
			'int',
			'float',
			'bool',
			'string',
			'array',
			'mixed',
			'void',
			'error',
			'resource_handle',
			'nullable_resource_handle',
			'falseable_resource_handle',
			'vector_t',
			'int_t',
			'float_t',
			'bool_t',
			'string_t',
			'mixed_t',
			'error_t',
			'hash_t',
			'::scpp::hash_t',
			'resource_handle_t',
			'nullable_resource_handle_t',
			'falseable_resource_handle_t',
		], true);
	}
}
