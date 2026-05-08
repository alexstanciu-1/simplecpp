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
	 * @param list<array{
	 *   kind:string,
	 *   name:?string,
	 *   type:string,
	 *   line:int,
	 *   startOffset:int,
	 *   endOffset:int,
	 *   ownerName?:?string
	 * }> $annotations
	 */
	public function __construct(
		public readonly string $path,
		public readonly string $source,
		public readonly string $originalSource,
		public readonly array $tokens,
		public readonly mixed $ast,
		public readonly array $annotations = [],
	) {
	}
}
