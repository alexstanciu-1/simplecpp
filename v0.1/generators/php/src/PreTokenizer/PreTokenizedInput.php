<?php
declare(strict_types=1);

namespace Scpp\S2S\PreTokenizer;

/**
 * Holds the PHP-compatible rewritten source together with remembered annotations.
 */
final class PreTokenizedInput
{
	/**
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
		public readonly string $source,
		public readonly array $annotations,
	) {
	}
}
