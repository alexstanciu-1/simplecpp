<?php
declare(strict_types=1);

namespace Scpp\S2S\Metadata;

/**
 * Extracts supported inline type comments from the exported token stream.
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

			if (is_array($current) && $this->looksLikeVariableToken($current)) {
				$this->collectTrailingVariableTypeComment($tokens, $i, $result);
				continue;
			}

			if (!is_array($current) || !$this->looksLikeDocCommentToken($current)) {
				continue;
			}

			$type = $this->extractInlineType((string) $current[1]);
			if ($type === null) {
				continue;
			}

			$nextIndex = $this->findNextNonWhitespaceTokenIndex($tokens, $i + 1);
			if ($nextIndex === null) {
				continue;
			}

			$next = $tokens[$nextIndex] ?? null;
			if ($next === '&') {
				$afterAmpIndex = $this->findNextNonWhitespaceTokenIndex($tokens, $nextIndex + 1);
				$afterAmp = $afterAmpIndex !== null ? ($tokens[$afterAmpIndex] ?? null) : null;
				if (is_array($afterAmp) && $this->looksLikeVariableToken($afterAmp)) {
					$this->pushResult($result, ltrim((string) $afterAmp[1], '$'), $type, (int) ($afterAmp[2] ?? 0));
					continue;
				}
			}

			if (is_array($next) && $this->looksLikeVariableToken($next)) {
				$this->pushResult($result, ltrim((string) $next[1], '$'), $type, (int) ($next[2] ?? 0));
				continue;
			}

			$prevIndex = $this->findPreviousNonWhitespaceTokenIndex($tokens, $i - 1);
			$prev = $prevIndex !== null ? ($tokens[$prevIndex] ?? null) : null;
			if ($this->isConstKeywordToken($prev) && is_array($next) && $this->looksLikeIdentifierToken($next)) {
				$this->pushResult($result, (string) $next[1], $type, (int) ($next[2] ?? 0));
			}
		}

		return array_values($result);
	}

	/** @param array<int, mixed> $tokens @param array<string, array{name:string,type:string,line:int}> $result */
	private function collectTrailingVariableTypeComment(array $tokens, int $index, array &$result): void
	{
		$j = $index + 1;
		$count = count($tokens);
		while ($j < $count && is_array($tokens[$j]) && $this->looksLikeWhitespaceToken($tokens[$j])) {
			$j++;
		}

		$next = $tokens[$j] ?? null;
		if (!is_array($next) || !$this->looksLikeDocCommentToken($next)) {
			return;
		}

		$type = $this->extractInlineType((string) $next[1]);
		if ($type === null) {
			return;
		}

		$current = $tokens[$index];
		$this->pushResult($result, ltrim((string) $current[1], '$'), $type, (int) ($current[2] ?? 0));
	}

	/** @param array<string, array{name:string,type:string,line:int}> $result */
	private function pushResult(array &$result, string $name, string $type, int $line): void
	{
		$key = $line . ':' . $name;
		$result[$key] = [
			'name' => $name,
			'type' => $type,
			'line' => $line,
		];
	}

	/** @param array<int, mixed> $tokens */
	private function findNextNonWhitespaceTokenIndex(array $tokens, int $start): ?int
	{
		$count = count($tokens);
		for ($i = $start; $i < $count; $i++) {
			$token = $tokens[$i];
			if (!is_array($token) || !$this->looksLikeWhitespaceToken($token)) {
				return $i;
			}
		}

		return null;
	}

	/** @param array<int, mixed> $tokens */
	private function findPreviousNonWhitespaceTokenIndex(array $tokens, int $start): ?int
	{
		for ($i = $start; $i >= 0; $i--) {
			$token = $tokens[$i];
			if (!is_array($token) || !$this->looksLikeWhitespaceToken($token)) {
				return $i;
			}
		}

		return null;
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

	private function looksLikeIdentifierToken(array $token): bool
	{
		if (!isset($token[1]) || !is_string($token[1])) {
			return false;
		}

		$value = $token[1];
		return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value) === 1;
	}

	private function isConstKeywordToken(mixed $token): bool
	{
		return is_array($token) && isset($token[1]) && is_string($token[1]) && strtolower($token[1]) === 'const';
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

		foreach (['value', 'shared', 'unique'] as $wrapper) {
			if ($inner === $wrapper) {
				return $wrapper;
			}

			if (preg_match('/^' . preg_quote($wrapper, '/') . '\s*<\s*(.+)\s*>$/', $inner, $matches) === 1) {
				$body = trim($matches[1]);
				# if ($body === '' || preg_match('/^(?:value|shared|unique)\s*</', $body) === 1) {
				if ($body === '') {
 					return null;
 				}

				// Preserve syntactically valid nested wrapper spellings here so the
				// generator/type-mapper layer can reject them with an explicit
				// diagnostic instead of silently dropping the annotation.
 				return $this->isTypeName($body) ? $wrapper . '<' . $body . '>' : null;
			}

			if (str_starts_with($inner, $wrapper . ' ')) {
				$body = trim(substr($inner, strlen($wrapper . ' ')));
				return $this->isTypeName($body) ? $wrapper . '<' . $body . '>' : null;
			}
		}

		if (str_starts_with($inner, 'ref ')) {
			$body = trim(substr($inner, strlen('ref ')));
			return $this->isTypeName($body) ? 'ref ' . $body : null;
		}

		return $this->isTypeName($inner) ? $inner : null;
	}

	private function isTypeName(string $type): bool
	{
		$normalized = trim($type);
		if ($normalized === '') {
			return false;
		}

		$depth = 0;
		$len = strlen($normalized);
		for ($i = 0; $i < $len; $i++) {
			$ch = $normalized[$i];
			if (($ch >= 'A' && $ch <= 'Z') || ($ch >= 'a' && $ch <= 'z') || ($ch >= '0' && $ch <= '9') || $ch === '_' || $ch === '\\' || $ch === ',' || $ch === ' ') {
				continue;
			}
			if ($ch === '<') {
				$depth++;
				continue;
			}
			if ($ch === '>') {
				$depth--;
				if ($depth < 0) {
					return false;
				}
				continue;
			}
			return false;
		}

		$first = $normalized[0];
		return $depth === 0 && ((($first >= 'A' && $first <= 'Z') || ($first >= 'a' && $first <= 'z') || $first === '_'));
	}
}
