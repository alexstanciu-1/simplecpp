<?php
declare(strict_types=1);

namespace Scpp\S2S\Emit;

/**
 * One physical generated C++ line with optional source origin.
 */
final class CodeBlock
{
	public function __construct(
		public readonly string $text,
		public readonly int $srcLine = -1,
		public readonly int $srcColumn = -1,
	) {
	}
}
