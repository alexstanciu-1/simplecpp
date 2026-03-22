<?php
declare(strict_types=1);

namespace Scpp\S2S\IR;

final class Statement
{
	public function __construct(
		public readonly string $kind,
		public readonly mixed $payload,
		public readonly int $line,
	) {
	}
}
