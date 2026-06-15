<?php
declare(strict_types=1);

namespace Scpp\S2S\Jss;

final class JssNode
{
	/**
	 * @param array<string,mixed> $fields
	 */
	public function __construct(
		public readonly string $kind,
		public readonly array $fields = [],
	) {
	}
}
