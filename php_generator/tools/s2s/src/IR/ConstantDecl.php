<?php
declare(strict_types=1);

namespace Scpp\S2S\IR;

final class ConstantDecl
{
	public function __construct(
		public readonly string $name,
		public readonly mixed $value,
	) {
	}
}
