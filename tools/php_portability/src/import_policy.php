<?php
declare(strict_types=1);

namespace scpp\portability;

/** One fixed import block for every source file; no project symbol lookup. */
final class Import_Policy {
	private const BEGIN = '// <scpp-imports>';
	private const END = '// </scpp-imports>';

	public function __construct(private array $functions) {}

	public function block(): string {
		$lines = [self::BEGIN];
		foreach ($this->functions as $name => $rule) {
			if ($rule['php'] !== null) {
				$lines[] = 'use function ' . $rule['php'] . ' as ' . $name . ';';
			}
		}
		$lines[] = self::END;
		return implode("\n", $lines) . "\n";
	}

	/** Split a single optional strict declaration/semicolon namespace from the body.
	 * Comments may precede either declaration. Managed imports belong after the
	 * namespace, so PHP and native files retain the same namespace scope.
	 * @return array{string, string}
	 */
	private function parts(string $source, string $path): array {
		$tokens = token_get_all($source);
		if (!isset($tokens[0]) || !is_array($tokens[0]) || $tokens[0][0] !== T_OPEN_TAG) {
			throw new \RuntimeException($path . ':1: expected PHP opening tag');
		}
		$significant = [];
		$offset = 0;
		foreach ($tokens as $token) {
			[$id, $text, $line] = is_array($token) ? $token : [0, $token, 1];
			$offset += strlen($text);
			if (!in_array($id, [T_OPEN_TAG, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
				$significant[] = [$id, $text, $line, $offset];
			}
		}
		$at = 0;
		$end = strlen($tokens[0][1]);
		if (($significant[$at][0] ?? null) === T_DECLARE) {
			$declaration = array_slice($significant, $at, 7);
			if (implode('', array_column($declaration, 1)) !== 'declare(strict_types=1);') {
				throw new \RuntimeException($path . ':' . $significant[$at][2] . ': only declare(strict_types=1) is supported');
			}
			$end = $declaration[6][3];
			$at += 7;
		}
		if (($significant[$at][0] ?? null) === T_NAMESPACE) {
			$name = $significant[$at + 1][1] ?? '';
			$valid = $name !== '';
			foreach (explode('\\', $name) as $part) {
				$valid = $valid && preg_match('/^[a-z_][a-z0-9_]*$/D', $part);
			}
			if (!$valid || ($significant[$at + 2][1] ?? '') !== ';') {
				throw new \RuntimeException($path . ':' . $significant[$at][2] . ': expected one lowercase semicolon namespace');
			}
			$end = $significant[$at + 2][3];
			$at += 3;
		}
		foreach (array_slice($significant, $at) as $token) {
			if (in_array($token[0], [T_DECLARE, T_NAMESPACE], true)) {
				throw new \RuntimeException($path . ':' . $token[2] . ': namespace/declare must form a single leading prologue');
			}
		}
		if ($end > strlen($tokens[0][1]) && preg_match('/^\r?\n/', substr($source, $end), $newline)) {
			$end += strlen($newline[0]);
		}
		return [substr($source, 0, $end), substr($source, $end)];
	}

	/** Strict PHP execution is host-only; retain its line count for diagnostics. */
	private function nativePrefix(string $prefix): string {
		$out = '';
		$inDeclare = false;
		foreach (token_get_all($prefix) as $token) {
			$id = is_array($token) ? $token[0] : 0;
			$text = is_array($token) ? $token[1] : $token;
			if ($id === T_DECLARE) { $inDeclare = true; }
			$out .= $inDeclare ? str_repeat("\n", substr_count($text, "\n")) : $text;
			if ($inDeclare && $text === ';') { $inDeclare = false; }
		}
		return $out;
	}

	public function synchronize(string $source, string $path): string {
		[$opening, $body] = $this->parts($source, $path);
		if (str_starts_with($body, self::BEGIN . "\n")) {
			// Find the closing marker as a comment token, never inside a string.
			$offset = 0;
			$end = null;
			foreach (token_get_all('<?php ' . $body) as $index => $token) {
				if ($index === 0) { continue; }
				$text = is_array($token) ? $token[1] : $token;
				$offset += strlen($text);
				if (is_array($token) && $token[0] === T_COMMENT && $text === self::END) {
					$end = $offset;
					break;
				}
			}
			if ($end === null) { throw new \RuntimeException($path . ':1: unclosed managed imports'); }
			$imports = substr($body, strlen(self::BEGIN) + 1, $end - strlen(self::BEGIN) - 1 - strlen(self::END));
			foreach (explode("\n", $imports) as $import) {
				if (trim($import) === '') { continue; }
				// Only replace generated import declarations, never authored statements.
				if (!preg_match('~^use function [a-z_][a-z0-9_]*(?:\\\\[a-z_][a-z0-9_]*)+ as [a-z_][a-z0-9_]*;$~D', $import)) {
					throw new \RuntimeException($path . ': unexpected content in managed imports; source left unchanged');
				}
			}
			$body = substr($body, $end);
			if (str_starts_with($body, "\n")) { $body = substr($body, 1); }
		}
		$depth = 0;
		$memberDepth = null;
		$declaration = false;
		$bodyTokens = token_get_all('<?php ' . $body);
		foreach ($bodyTokens as $offset => $token) {
			$id = is_array($token) ? $token[0] : 0;
			$text = is_array($token) ? $token[1] : $token;
			if (in_array($id, [T_CLASS, T_TRAIT], true) && $depth === 0) { $declaration = true; }
			if ($text === '{') {
				++$depth;
				if ($declaration) { $memberDepth = $depth; $declaration = false; }
			}
			if ($id === T_USE && $depth !== $memberDepth) {
				$next = $offset + 1;
				while (isset($bodyTokens[$next]) && is_array($bodyTokens[$next]) && in_array($bodyTokens[$next][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) { ++$next; }
				// A closure capture list is expression syntax, not an import.
				if (($bodyTokens[$next] ?? null) === '(') { continue; }
				throw new \RuntimeException($path . ': manual imports/use syntax are unsupported; use the central policy');
			}
			if ($text === '}') {
				if ($depth === $memberDepth) { $memberDepth = null; }
				--$depth;
			}
		}
		return $opening . $this->block() . $body;
	}

	public function prepare(string $source, string $path): string {
		[$opening, $body] = $this->parts($source, $path);
		$block = $this->block();
		if (!str_starts_with($body, $block)) {
			throw new \RuntimeException($path . ':1: missing or stale managed imports; run sync_imports.php');
		}
		// Keep original source line numbers while removing PHP-only imports.
		return $this->nativePrefix($opening) . str_repeat("\n", substr_count($block, "\n")) . substr($body, strlen($block));
	}
}
