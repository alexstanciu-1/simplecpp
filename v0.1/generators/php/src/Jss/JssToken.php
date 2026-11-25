<?php
declare(strict_types=1);

namespace Scpp\S2S\Jss;

final class JssToken
{
	public function __construct(
		public readonly string $kind,
		public readonly string $text,
		public readonly int $offset,
		public readonly int $line,
		public readonly int $column,
	) {
	}
}
