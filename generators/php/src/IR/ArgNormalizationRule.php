<?php
declare(strict_types=1);

namespace Scpp\S2S\IR;

/**
 * IR node representing one @arg.<param>.from(Type) normalization rule.
 */
final class ArgNormalizationRule
{
	public function __construct(
		public readonly string $paramName,
		public readonly string $sourceType,
		public readonly string $expression,
		public readonly int $line = 0,
	) {
	}
}
