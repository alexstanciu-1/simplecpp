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
	public function mapDeclaredType(string $phpType): string
	{
		if (str_starts_with($phpType, '?')) {
			$inner = substr($phpType, 1);
			if ($this->isObjectType($inner)) {
				return "shared_p<{$inner}>";
			}
			return 'nullable<' . $this->mapValueType($inner) . '>';
		}

		if ($this->isObjectType($phpType)) {
			return "shared_p<{$phpType}>";
		}

		return $this->mapValueType($phpType);
	}

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

	public function mapReturnType(?string $phpType, bool $explicitRef): string
	{
		if ($phpType === null) {
			return 'auto';
		}

		$mapped = $this->mapDeclaredType($phpType);
		return $explicitRef ? $mapped . '&' : $mapped;
	}

	public function mapTypedLocalType(string $phpType): string
	{
		return $this->mapDeclaredType($phpType);
	}

	private function mapValueType(string $phpType): string
	{
		return match ($phpType) {
			'int' => 'int_t',
			'float' => 'float_t',
			'bool' => 'bool_t',
			'string' => 'string_t',
			'void' => 'void_t',
			'vector_t' => 'vector_t',
			default => $phpType,
		};
	}

	private function isObjectType(string $phpType): bool
	{
		return !in_array($phpType, ['int', 'float', 'bool', 'string', 'void', 'vector_t'], true);
	}
}
