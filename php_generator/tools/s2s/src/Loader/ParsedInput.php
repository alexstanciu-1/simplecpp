<?php
declare(strict_types=1);

namespace Scpp\S2S\Loader;

/**
 * Holds the raw PHP source together with the fixture-provided AST and tokens.
 */
final class ParsedInput
{
	/**
	 * @param array<int, mixed> $tokens
	 */
	public function __construct(
		public readonly string $path,
		public readonly string $source,
		public readonly array $tokens,
		public readonly mixed $ast,
	) {
	}
}
