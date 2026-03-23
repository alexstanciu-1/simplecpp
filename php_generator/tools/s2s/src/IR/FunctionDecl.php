<?php
declare(strict_types=1);

namespace Scpp\S2S\IR;

/**
 * IR node representing a top-level PHP function declaration.
 *
 * Relationship to specs:
 * - this type exists to keep the implementation aligned with php_generator/specs/rules.md and rules_catalog.md
 * - the implementation favors explicit normalized data over ad-hoc AST access during emission
 */
final class FunctionDecl
{
	/**
	 * @param list<ParamDecl> $params
	 * @param list<Statement> $statements
	 */
	public function __construct(
		public readonly string $name,
		public readonly array $params,
		public readonly ?string $returnType,
		public readonly bool $returnsByReference,
		public readonly array $statements,
	) {
	}
}
