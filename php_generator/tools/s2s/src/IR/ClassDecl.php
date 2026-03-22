<?php
declare(strict_types=1);

namespace Scpp\S2S\IR;

final class ClassDecl
{
	/**
	 * @param list<MethodDecl> $methods
	 */
	public function __construct(
		public readonly string $name,
		public readonly array $methods,
	) {
	}
}
