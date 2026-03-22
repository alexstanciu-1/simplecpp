<?php
declare(strict_types=1);

namespace Scpp\S2S\IR;

final class NamespaceBlock
{
	/**
	 * @param list<ClassDecl> $classes
	 * @param list<FunctionDecl> $functions
	 * @param list<Statement> $statements
	 */
	public function __construct(
		public readonly string $name,
		public readonly array $classes,
		public readonly array $functions,
		public readonly array $statements,
	) {
	}
}
