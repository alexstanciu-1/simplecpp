<?php
declare(strict_types=1);

namespace Scpp\S2S\IR;

/**
 * IR node representing one namespace bucket after namespace flattening.
 *
 * Relationship to specs:
 * - this type exists to keep the implementation aligned with php_generator/specs/rules.md and rules_catalog.md
 * - the implementation favors explicit normalized data over ad-hoc AST access during emission
 */
final class NamespaceBlock
{
	/**
	 * @param list<UseDecl> $uses
	 * @param list<ConstantDecl> $constants
	 * @param list<ClassDecl> $classes
	 * @param list<FunctionDecl> $functions
	 * @param list<Statement> $statements
	 */
	public function __construct(
		public readonly string $name,
		public readonly array $uses,
		public readonly array $constants,
		public readonly array $classes,
		public readonly array $functions,
		public readonly array $statements,
	) {
	}
}
