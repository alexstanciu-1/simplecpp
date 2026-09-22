<?php
declare(strict_types=1);

namespace scpp\portability;

/** File-local declaration facts and direct trait expansion, never expression/type resolution. */
final class Declaration_Index {
	private array $declarations = [];

	private static function skip(array $tokens, int $at): int {
		while (isset($tokens[$at]) && in_array($tokens[$at][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) { ++$at; }
		return $at;
	}

	private static function fail(string $path, array $token, string $message): never {
		throw new \RuntimeException($path . ':' . $token[2] . ': ' . $message);
	}

	private static function close(array $tokens, int $open, string $path): int {
		$depth = 0;
		for ($i = $open; isset($tokens[$i]); ++$i) {
			if ($tokens[$i][1] === '{') { ++$depth; }
			if ($tokens[$i][1] === '}' && --$depth === 0) { return $i; }
		}
		self::fail($path, $tokens[$open], 'unclosed declaration body');
	}

	/** Index only unconditional named declarations. Import_Policy already validates the prologue. */
	public static function inspect(array $tokens, string $path): array {
		$namespace = '';
		$depth = 0;
		$declarations = [];
		for ($i = 0; isset($tokens[$i]); ++$i) {
			[$id, $text] = $tokens[$i];
			if ($id === T_NAMESPACE) { $namespace = $tokens[self::skip($tokens, $i + 1)][1]; }
			if ($text === '{') { ++$depth; }
			if ($text === '}') { --$depth; }
			if (!in_array($id, [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) { continue; }
			$nameAt = self::skip($tokens, $i + 1);
			if ($depth !== 0 || ($tokens[$nameAt][0] ?? null) !== T_STRING) {
				self::fail($path, $tokens[$i], 'only unconditional named top-level declarations are supported');
			}
			$name = ($namespace === '' ? '' : $namespace . '\\') . $tokens[$nameAt][1];
			$open = $nameAt + 1;
			while (isset($tokens[$open]) && $tokens[$open][1] !== '{') { ++$open; }
			if (!isset($tokens[$open])) { self::fail($path, $tokens[$i], 'missing declaration body'); }
			$end = self::close($tokens, $open, $path);
			$kind = match ($id) { T_TRAIT => 'trait', T_INTERFACE => 'interface', T_ENUM => 'enum', default => 'class' };
			[$methods, $uses] = self::members($tokens, $open, $end, $kind, $namespace, $path);
			$key = strtolower($name);
			if (isset($declarations[$key])) { self::fail($path, $tokens[$i], 'duplicate declaration ' . $name); }
			$declarations[$key] = ['name' => $name, 'kind' => $kind, 'namespace' => $namespace,
				'file' => $path, 'line' => $tokens[$i][2], 'start' => $i, 'open' => $open, 'end' => $end,
				'methods' => $methods, 'uses' => $uses];
			$i = $end;
		}
		return $declarations;
	}

	private static function members(array $tokens, int $open, int $end, string $kind, string $namespace, string $path): array {
		$methods = [];
		$uses = [];
		for ($i = self::skip($tokens, $open + 1); $i < $end; $i = self::skip($tokens, $i)) {
			$start = $i;
			if ($tokens[$i][0] === T_USE) {
				if ($kind !== 'class') { self::fail($path, $tokens[$i], 'only classes may use traits; traits cannot use traits'); }
				$names = [];
				do {
					$i = self::skip($tokens, $i + 1);
					$t = $tokens[$i];
					if (!in_array($t[0], [T_STRING, T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED], true)) {
						self::fail($path, $t, 'expected literal same-namespace trait name');
					}
					$name = str_starts_with($t[1], '\\') ? substr($t[1], 1) : ($namespace === '' ? '' : $namespace . '\\') . $t[1];
					$parts = explode('\\', $name);
					array_pop($parts);
					if (implode('\\', $parts) !== $namespace) { self::fail($path, $t, 'traits must be in the consuming class namespace'); }
					$names[] = strtolower($name);
					$i = self::skip($tokens, $i + 1);
				} while ($tokens[$i][1] === ',');
				if ($tokens[$i][1] !== ';') { self::fail($path, $tokens[$i], 'trait adaptations (as/insteadof) are unsupported'); }
				$uses[] = ['start' => $start, 'end' => $i, 'names' => $names, 'line' => $tokens[$start][2]];
				++$i;
				continue;
			}
			while (in_array($tokens[$i][0], [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_FINAL, T_ABSTRACT, T_READONLY], true)) {
				$i = self::skip($tokens, $i + 1);
			}
			if ($tokens[$i][0] === T_FUNCTION) {
				$nameAt = self::skip($tokens, $i + 1);
				if (($tokens[$nameAt][0] ?? null) !== T_STRING) { self::fail($path, $tokens[$i], 'expected named method'); }
				$name = strtolower($tokens[$nameAt][1]);
				if (isset($methods[$name])) { self::fail($path, $tokens[$nameAt], 'duplicate method ' . $name); }
				if ($kind === 'trait' && str_starts_with($name, '__')) { self::fail($path, $tokens[$nameAt], 'magic methods are unsupported in traits'); }
				$methods[$name] = $tokens[$nameAt][2];
			} elseif ($kind === 'trait') {
				self::fail($path, $tokens[$start], 'traits support explicit methods only');
			}
			// Skip one field/constant/case or a method signature and body. No callee lookup.
			while ($i < $end && !in_array($tokens[$i][1], [';', '{'], true)) { ++$i; }
			if ($tokens[$i][1] === '{') { $i = self::close($tokens, $i, $path); }
			++$i;
		}
		return [$methods, $uses];
	}

	public function __construct(private array $files) {
		foreach ($files as $file) {
			foreach ($file['declarations'] as $key => $declaration) {
				if (isset($this->declarations[$key])) {
					throw new \RuntimeException($declaration['file'] . ':' . $declaration['line'] . ': duplicate declaration ' . $declaration['name'] . ' (also in ' . $this->declarations[$key]['file'] . ':' . $this->declarations[$key]['line'] . ')');
				}
				$this->declarations[$key] = $declaration;
			}
		}
	}

	/** Validate every direct dependency and collision, even on files whose output can be reused. */
	public function dependencies(string $path): array {
		$dependencies = [];
		foreach ($this->files[$path]['declarations'] as $declaration) {
			$methods = $declaration['methods'];
			$seen = [];
			foreach ($declaration['uses'] as $use) {
				foreach ($use['names'] as $name) {
					$trait = $this->declarations[$name] ?? null;
					$error = null;
					if ($trait === null || $trait['kind'] !== 'trait') { $error = 'missing trait declaration ' . $name; }
					elseif (isset($seen[$name])) { $error = 'duplicate trait use ' . $name; }
					else {
						foreach ($trait['methods'] as $method => $line) {
							if (isset($methods[$method])) { $error = 'trait method collision ' . $method . ' from ' . $trait['file'] . ':' . $line; break; }
							$methods[$method] = $line;
						}
					}
					if ($error !== null) { throw new \RuntimeException($path . ':' . $use['line'] . ': ' . $error); }
					$seen[$name] = true;
					$dependencies[$trait['file']] = $this->files[$trait['file']]['hash'];
				}
			}
		}
		ksort($dependencies);
		return $dependencies;
	}

	public function expand(string $path, callable $load): array {
		$tokens = $load($path);
		$replacements = [];
		foreach ($this->files[$path]['declarations'] as $declaration) {
			foreach ($declaration['uses'] as $use) {
				$body = [];
				foreach ($use['names'] as $name) {
					$trait = $this->declarations[$name];
					array_push($body, ...array_slice($load($trait['file']), $trait['open'] + 1, $trait['end'] - $trait['open'] - 1));
				}
				$replacements[$use['start']] = [$use['end'] - $use['start'] + 1, $body];
			}
		}
		krsort($replacements);
		foreach ($replacements as $at => [$length, $body]) { array_splice($tokens, $at, $length, $body); }
		return $tokens;
	}
}
