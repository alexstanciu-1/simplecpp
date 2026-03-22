<?php
declare(strict_types=1);

namespace Scpp\S2S\Metadata;

/**
 * Extracts strict immediate-after-variable local type comments.
 */
final class TypeCommentExtractor
{
	/**
	 * @param array<int, mixed> $tokens
	 * @return array<int, array{name:string,type:string,line:int}>
	 */
	public function extract(array $tokens): array
	{
		$result = [];
		$count = count($tokens);

		for ($i = 0; $i < $count; $i++) {
			$current = $tokens[$i];
			if (!is_array($current) || !$this->looksLikeVariableToken($current)) {
				continue;
			}

			$j = $i + 1;
			while ($j < $count && is_array($tokens[$j]) && $this->looksLikeWhitespaceToken($tokens[$j])) {
				$j++;
			}

			$next = $tokens[$j] ?? null;
			if (!is_array($next) || !$this->looksLikeDocCommentToken($next)) {
				continue;
			}

			$type = $this->extractInlineType((string) $next[1]);
			if ($type === null) {
				continue;
			}

			$result[] = [
				'name' => ltrim((string) $current[1], '$'),
				'type' => $type,
				'line' => (int) ($current[2] ?? 0),
			];
		}

		return $result;
	}

	private function looksLikeVariableToken(array $token): bool
	{
		return isset($token[1]) && is_string($token[1]) && str_starts_with($token[1], '$');
	}

	private function looksLikeWhitespaceToken(array $token): bool
	{
		return isset($token[1]) && is_string($token[1]) && trim($token[1]) === '';
	}

	private function looksLikeDocCommentToken(array $token): bool
	{
		return isset($token[1]) && is_string($token[1]) && str_starts_with($token[1], '/**');
	}

	private function extractInlineType(string $docComment): ?string
	{
		$inner = trim($docComment);
		if (!str_starts_with($inner, '/**') || !str_ends_with($inner, '*/')) {
			return null;
		}

		$inner = trim(substr($inner, 3, -2));
		if ($inner === '') {
			return null;
		}

		if ($inner[0] === '?') {
			$body = substr($inner, 1);
			if ($body === '') {
				return null;
			}
			return $this->isTypeName($body) ? $inner : null;
		}

		return $this->isTypeName($inner) ? $inner : null;
	}

	private function isTypeName(string $type): bool
	{
		$len = strlen($type);
		for ($i = 0; $i < $len; $i++) {
			$ch = $type[$i];
			if (($ch >= 'A' && $ch <= 'Z') || ($ch >= 'a' && $ch <= 'z') || ($ch >= '0' && $ch <= '9') || $ch === '_' || $ch === '\\') {
				continue;
			}
			return false;
		}

		$first = $type[0];
		return ($first >= 'A' && $first <= 'Z') || ($first >= 'a' && $first <= 'z') || $first === '_';
	}
}
