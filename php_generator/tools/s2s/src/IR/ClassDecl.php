<?php
declare(strict_types=1);

namespace Scpp\S2S\IR;

/**
 * IR node representing a PHP class after php-ast normalization.
 *
 * Relationship to specs:
 * - this type exists to keep the implementation aligned with php_generator/specs/rules.md and rules_catalog.md
 * - the implementation favors explicit normalized data over ad-hoc AST access during emission
 */
final class ClassDecl
{
	/**
	 * @param list<PropertyDecl> $properties
	 * @param list<MethodDecl> $methods
	 */
	public function __construct(
		public readonly string $name,
		public readonly array $properties,
		public readonly array $methods,
	) {
	}
}
