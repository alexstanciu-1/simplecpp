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
		if ($this->isInlineValueType($phpType)) {
			return 'value_p<' . $this->mapUserTypeName($this->unwrapInlineValueType($phpType)) . '>';
		}

		if (str_starts_with($phpType, '?')) {
			$inner = substr($phpType, 1);
			if ($this->isObjectType($inner)) {
				return 'shared_p<' . $this->mapUserTypeName($inner) . '>';
			}
			return 'nullable<' . $this->mapValueType($inner) . '>';
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
			return $mapped . '&';
		}

		return match ($mapped) {
			'string_t', 'vector_t' => 'const ' . $mapped . '&',
			default => $mapped,
		};
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
			return 'auto';
		}

		$mapped = $this->mapDeclaredType($phpType);
		return $explicitRef ? $mapped . '&' : $mapped;
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
		if ($this->isRefLocalType($phpType)) {
			return 'ref_p<' . $this->mapRefTargetType($this->unwrapRefLocalType($phpType)) . '>';
		}

		return $this->mapDeclaredType($phpType);
	}

	public function isInlineValueType(string $phpType): bool
	{
		return str_starts_with($phpType, 'value ');
	}

	public function unwrapInlineValueType(string $phpType): string
	{
		return trim(substr($phpType, strlen('value ')));
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

	private function mapRefTargetType(string $phpType): string
	{
		if ($this->isInlineValueType($phpType)) {
			$phpType = $this->unwrapInlineValueType($phpType);
		}

		if (str_starts_with($phpType, '?')) {
			$phpType = substr($phpType, 1);
		}

		return $this->mapValueType($phpType);
	}

	private function mapValueType(string $phpType): string
	{
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
		return !in_array($phpType, ['int', 'float', 'bool', 'string', 'void', 'vector_t'], true);
	}
}
