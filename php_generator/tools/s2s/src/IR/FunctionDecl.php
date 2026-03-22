<?php
declare(strict_types=1);

namespace Scpp\S2S\IR;

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
