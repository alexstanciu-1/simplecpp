<?php
declare(strict_types=1);

namespace Scpp\S2S\Lowering;

/**
 * Centralized type mapping.
 *
 * Keeping this in one place is essential for consistency and simplicity.
 */
final class TypeMapper
{
	/**
	 * Maps a declared PHP property or constant-adjacent type into the canonical Simple C++ type.
	 *
	 * Relationship to specs:
	 * - preserves the subset and lowering rules documented for the prototype
	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit
	 */
	public function mapDeclaredType(string $phpType): string
	{
		$phpType = $this->guardTypeDefinitionSyntax($phpType);
		if ($this->isInlineValueType($phpType)) {
			return 'value_p<' . $this->mapUserTypeName($this->unwrapInlineValueType($phpType)) . '>';
		}

		if (str_starts_with($phpType, '?')) {
			$inner = substr($phpType, 1);
			if ($this->isDirectHandleType($inner) || $this->isHandleAliasType($inner)) {
				return $this->mapValueType($inner);
			}
			if ($this->isObjectType($inner)) {
				return 'shared_p<' . $this->mapUserTypeName($inner) . '>';
			}
			return 'nullable<' . $this->mapValueType($inner) . '>';
		}

		if ($this->isDirectHandleType($phpType) || $this->isHandleAliasType($phpType)) {
			return $this->mapValueType($phpType);
		}

		if ($this->isObjectType($phpType)) {
			return 'shared_p<' . $this->mapUserTypeName($phpType) . '>';
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

		if ($mapped === 'string_t' || str_starts_with($mapped, 'vector_t<')) {
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
		$phpType = $this->guardTypeDefinitionSyntax($phpType);
		if ($this->isRefLocalType($phpType)) {
			return $this->appendLvalueReference($this->mapDeclaredType($this->unwrapRefLocalType($phpType)));
		}

		return $this->mapDeclaredType($phpType);
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

	public function isInlineValueType(string $phpType): bool
	{
		$normalized = trim($phpType);
		if (str_starts_with($normalized, 'value ')) {
			return $this->isObjectType(trim(substr($normalized, strlen('value '))));
		}

		if (preg_match('/^value\s*<\s*(.+)\s*>$/', $normalized, $matches) !== 1) {
			return false;
		}

		$inner = trim($matches[1]);
		if ($inner === '' || str_starts_with($inner, 'value<') || str_starts_with($inner, 'value <')) {
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

	public function isBareObjectWrapperShortcut(string $phpType): bool
	{
		return in_array(trim($phpType), ['value', 'shared', 'unique'], true);
	}

	public function specializeBareObjectWrapperShortcut(string $wrapper, string $phpType): string
	{
		$normalizedWrapper = trim($wrapper);
		if (!$this->isBareObjectWrapperShortcut($normalizedWrapper)) {
			throw new \InvalidArgumentException('Unsupported bare object-wrapper shortcut: ' . $wrapper);
		}

		$normalizedType = $this->guardTypeDefinitionSyntax($phpType);
		if (!$this->isObjectType($normalizedType)) {
			throw new \InvalidArgumentException('Bare object-wrapper shortcuts require a user object type: ' . $phpType);
		}

		return $normalizedWrapper . '<' . $normalizedType . '>';
	}



	public function hasInvalidNestedWrapperType(string $phpType): bool
	{
		$normalized = $this->guardTypeDefinitionSyntax($phpType);
		foreach (['value', 'shared', 'unique'] as $wrapper) {
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

	 * Maps one scalar/object PHP type name into its runtime-backed Simple C++ counterpart.

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
			throw new \InvalidArgumentException('Unsupported C++ type form in reference lowering: ' . $mappedType);
		}
		if (str_contains($mappedType, '&')) {
			throw new \InvalidArgumentException('Type mapping attempted to create a nested or pre-existing reference type: ' . $mappedType);
		}

		return $mappedType . '&';
	}

	private function guardTypeDefinitionSyntax(string $phpType): string
	{
		$normalized = trim($phpType);
		if (str_contains($normalized, '&&')) {
			throw new \InvalidArgumentException('Rvalue references (&&) are not supported in type definitions: ' . $phpType);
		}
		if (str_contains($normalized, '*')) {
			throw new \InvalidArgumentException('Pointer syntax (*) is not supported in type definitions: ' . $phpType);
		}
		if (str_contains($normalized, '&')) {
			throw new \InvalidArgumentException('Reference syntax (&) must not appear inside type definitions. Use explicit PHP reference forms instead: ' . $phpType);
		}

		return $normalized;
	}

	private function mapValueType(string $phpType): string
	{
		if ($this->isVectorType($phpType)) {
			return $this->mapVectorType($phpType);
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
			'void' => 'void',
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
			return '::scpp::' . str_replace('\\', '::', $trimmed);
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

	private function isObjectType(string $phpType): bool
	{
		if ($this->isVectorType($phpType)) {
			return false;
		}

		if ($this->isDirectHandleType($phpType) || $this->isHandleAliasType($phpType)) {
			return false;
		}

		return !in_array($phpType, ['int', 'float', 'bool', 'string', 'void', 'vector_t'], true);
	}
}
