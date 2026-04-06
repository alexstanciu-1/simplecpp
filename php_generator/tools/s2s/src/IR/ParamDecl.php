<?php
declare(strict_types=1);

namespace Scpp\S2S\IR;

/**
 * IR node representing one lowered function or method parameter.
 *
 * Relationship to specs:
 * - this type exists to keep the implementation aligned with php_generator/specs/rules.md and rules_catalog.md
 * - the implementation favors explicit normalized data over ad-hoc AST access during emission
 */
final class ParamDecl
{
	public readonly ?string $type;
	public readonly ?string $primaryType;
	/** @var list<string> */
	public readonly array $unionTypes;

	/**
	 * Stores collaborators and default state for this phase object.
	 *
	 * Relationship to specs:
	 * - preserves the subset and lowering rules documented for the prototype
	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit
	 */
	public function __construct(
		public readonly string $name,
		public readonly ?string $nativeType,
		public readonly ?string $docType,
		public readonly bool $isReference,
		public readonly bool $isVariadic,
		public readonly mixed $default,
		public readonly int $line = 0,
	) {
		$this->type = $nativeType ?? $docType;
		$this->unionTypes = self::splitUnionTypes($this->type);
		$this->primaryType = $this->unionTypes[0] ?? $this->type;
	}

	private static function splitUnionTypes(?string $type): array
	{
		if ($type === null) {
			return [];
		}

		$parts = array_values(array_filter(array_map(static fn (string $part): string => trim($part), explode('|', $type)), static fn (string $part): bool => $part !== ''));
		return $parts === [] ? [] : $parts;
	}
}
