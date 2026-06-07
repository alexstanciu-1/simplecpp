<?php
declare(strict_types=1);

namespace Scpp\S2S\IR;

/**
 * IR node representing a class method declaration.
 *
 * Relationship to specs:
 * - this type exists to keep the implementation aligned with generators/php/specs/rules.md and rules_catalog.md
 * - the implementation favors explicit normalized data over ad-hoc AST access during emission
 */
final class MethodDecl
{
	/**
	 * @param list<ParamDecl> $params
	 * @param list<Statement> $statements
	 * @param list<ArgNormalizationRule> $argNormalizationRules
	 */
	public function __construct(
		public readonly string $name,
		public readonly array $params,
		public readonly ?string $returnType,
		public readonly bool $returnsByReference,
		public readonly bool $isStatic,
		public readonly array $statements,
		public readonly int $line = 0,
		public readonly string $visibility = 'public',
		public readonly array $argNormalizationRules = [],
	) {
	}
}
