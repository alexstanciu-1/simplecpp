<?php
declare(strict_types=1);

namespace Scpp\S2S\Support;

/**
 * Adapts headerless Prism++ source to the host PHP parser/tokenizer without
 * changing user-facing source line ownership.
 */
final class PhpParserCompatibility
{
	private const HEADERLESS_PREFIX = '<?php ';

	public static function wrapSource(string $source): string
	{
		if (!self::needsSyntheticOpenTag($source)) {
			return $source;
		}

		return self::HEADERLESS_PREFIX . $source;
	}

	/** @return array<int, mixed> */
	public static function tokenizeSource(string $source): array
	{
		$wrapped = self::wrapSource($source);
		$tokens = token_get_all($wrapped);

		if ($wrapped === $source) {
			return $tokens;
		}

		if (isset($tokens[0]) && is_array($tokens[0]) && $tokens[0][0] === T_OPEN_TAG) {
			array_shift($tokens);
		}

		return $tokens;
	}

	public static function needsSyntheticOpenTag(string $source): bool
	{
		return preg_match('/^\xEF\xBB\xBF?\s*<\?php\b/i', $source) !== 1;
	}

	public static function syntheticPrefixLength(string $source): int
	{
		return self::needsSyntheticOpenTag($source) ? strlen(self::HEADERLESS_PREFIX) : 0;
	}
}
