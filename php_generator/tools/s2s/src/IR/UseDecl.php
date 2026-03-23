<?php
declare(strict_types=1);

namespace Scpp\S2S\IR;

final class UseDecl
{
	public function __construct(
		public readonly string $kind,
		public readonly string $name,
		public readonly ?string $alias,
		public readonly int $line,
		public readonly bool $isGrouped = false,
	) {
	}
}
