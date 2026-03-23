<?php
declare(strict_types=1);

namespace Scpp\S2S\IR;

/**
 * IR node representing one lowered executable statement or expression statement.
 *
 * Relationship to specs:
 * - this type exists to keep the implementation aligned with php_generator/specs/rules.md and rules_catalog.md
 * - the implementation favors explicit normalized data over ad-hoc AST access during emission
 */
final class Statement
{
	/**
	 * Stores collaborators and default state for this phase object.
	 *
	 * Relationship to specs:
	 * - preserves the subset and lowering rules documented for the prototype
	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit
	 */
	public function __construct(
		public readonly string $kind,
		public readonly mixed $payload,
		public readonly int $line,
	) {
	}
}
