<?php
declare(strict_types=1);

namespace Scpp\S2S\Emit;

/**
 * C++ output pair for one PHP input file.
 */
final class CppFile
{
	/**
	 * @param list<string> $headerLines
	 * @param list<string> $sourceLines
	 * @param list<string> $errors
	 */
	public function __construct(
		public readonly string $baseName,
		public readonly array $headerLines,
		public readonly array $sourceLines,
		public readonly array $errors,
	) {
	}
}
