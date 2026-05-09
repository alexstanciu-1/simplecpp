<?php
declare(strict_types=1);

namespace Scpp\S2S\Emit;

/**
 * C++ output pair for one PHP++ input file.
 */
final class CppFile
{
	/**
	 * @param list<string> $headerLines
	 * @param list<int> $headerLineMap
	 * @param list<string> $sourceLines
	 * @param list<int> $sourceLineMap
	 * @param list<string> $errors
	 * @param list<string> $warnings
	 */
	public function __construct(
		public readonly string $baseName,
		public readonly array $headerLines,
		public readonly array $headerLineMap,
		public readonly array $exportManifest,
		public readonly array $sourceLines,
		public readonly array $sourceLineMap,
		public readonly array $errors,
		public readonly array $warnings = [],
	) {
	}
}
