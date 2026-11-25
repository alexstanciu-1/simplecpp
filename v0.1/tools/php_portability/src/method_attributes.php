<?php
declare(strict_types=1);

namespace scpp\portability;

/** Only explicit portable method markers; no attribute instantiation or name resolution. */
final class Method_Attributes {
	public static function read(array $tokens, int &$position, string $path): array {
		$result = [];
		while (($tokens[$position][0] ?? null) === T_ATTRIBUTE) {
			$start = $tokens[$position++];
			self::skip($tokens, $position);
			$name = $tokens[$position++] ?? [0, '', $start[2]];
			self::skip($tokens, $position);
			$close = $tokens[$position++] ?? [0, '', $start[2]];
			if (!in_array($name[1], ['\\Virtual', '\\Override'], true) || $close[1] !== ']') {
				throw new \RuntimeException(($start[3] ?? $path) . ':' . $start[2] . ': expected zero-argument #[\\Virtual] or #[\\Override] method marker');
			}
			if (in_array($name[1], $result, true)) {
				throw new \RuntimeException(($start[3] ?? $path) . ':' . $start[2] . ': duplicate method marker ' . $name[1]);
			}
			$result[] = $name[1];
			self::skip($tokens, $position);
		}
		return $result;
	}

	private static function skip(array $tokens, int &$position): void {
		while (isset($tokens[$position]) && in_array($tokens[$position][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) { ++$position; }
	}
}
