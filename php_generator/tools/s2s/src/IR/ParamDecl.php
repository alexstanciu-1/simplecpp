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
		public readonly mixed $default,
		public readonly int $line = 0,
	) {
		$this->type = $nativeType ?? $docType;
	}
}
