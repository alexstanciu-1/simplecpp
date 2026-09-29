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
			[$methods, $uses, $fields] = self::members($tokens, $open, $end, $kind, $namespace, $path);
			$key = strtolower($name);
			if (isset($declarations[$key])) { self::fail($path, $tokens[$i], 'duplicate declaration ' . $name); }
			$declarations[$key] = ['name' => $name, 'kind' => $kind, 'namespace' => $namespace,
				'file' => $path, 'line' => $tokens[$i][2], 'start' => $i, 'open' => $open, 'end' => $end,
				'methods' => $methods, 'uses' => $uses, 'fields' => $fields];
			$i = $end;
		}
		return $declarations;
	}

	private static function members(array $tokens, int $open, int $end, string $kind, string $namespace, string $path): array {
		$methods = [];
		$uses = [];
		$fields = [];
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
			} elseif ($kind === 'class' || $kind === 'trait') {
				// Only a directly declared named instance field can bind a trait signature.
				$typeAt = $tokens[$i][1] === '?' ? self::skip($tokens, $i + 1) : $i;
				$fieldAt = self::skip($tokens, $typeAt + 1);
				$modifiers = array_column(array_slice($tokens, $start, $i - $start), 0);
				if (($tokens[$fieldAt][0] ?? null) === T_VARIABLE
					&& in_array($tokens[$typeAt][0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)
					&& !in_array(T_STATIC, $modifiers, true)) {
					$fields[substr($tokens[$fieldAt][1], 1)] = $tokens[$typeAt];
				} elseif ($kind === 'trait') {
					self::fail($path, $tokens[$start], 'traits require explicit instance fields or methods');
				}
			}
			// Skip one field/constant/case or a method signature and body. No callee lookup.
			while ($i < $end && !in_array($tokens[$i][1], [';', '{'], true)) { ++$i; }
			if ($tokens[$i][1] === '{') { $i = self::close($tokens, $i, $path); }
			++$i;
		}
		return [$methods, $uses, $fields];
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
			$fields = $declaration['fields'];
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
						foreach ($trait['fields'] as $field => $type) {
							if (isset($fields[$field])) { $error = 'trait field collision ' . $field . ' from ' . $trait['file'] . ':' . $type[2]; break; }
							$fields[$field] = $type;
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

	/** Explicit trait signature annotations copy one consuming class field's named type. */
	private static function bindFieldTypes(array $tokens, array $consumer, string $path): array {
		$depth = 0;
		$signature = false;
		foreach ($tokens as $at => $token) {
			if ($depth === 0 && $token[0] === T_FUNCTION) { $signature = true; }
			if ($token[1] === '{') { ++$depth; $signature = false; }
			if ($token[1] === '}') { --$depth; }
			if ($depth === 0 && $token[1] === ';') { $signature = false; }
			if ($token[0] !== T_DOC_COMMENT || !str_contains($token[1], '@field-type')) { continue; }
			if (!preg_match('~^/\*\*\s*@field-type ([a-zA-Z_][a-zA-Z_0-9]*)\s*\*/$~D', $token[1], $match)) {
				self::fail($path, $token, 'expected /** @field-type field_name */');
			}
			$previous = $at - 1;
			while ($previous >= 0 && $tokens[$previous][0] === T_WHITESPACE) { --$previous; }
			if (!$signature || $previous < 0 || $tokens[$previous][1] !== 'object') {
				self::fail($path, $token, '@field-type must immediately follow an object signature type');
			}
			$type = $consumer['fields'][$match[1]] ?? null;
			if ($type === null || in_array(strtolower($type[1]), ['int', 'float', 'string', 'bool', 'mixed', 'object', 'iterable', 'self', 'parent', 'static'], true)) {
				self::fail($path, $token, '@field-type requires a directly declared named instance field ' . $match[1] . ' in ' . $consumer['name']);
			}
			$tokens[$previous][0] = $type[0];
			$tokens[$previous][1] = $type[1];
			$tokens[$at][0] = T_WHITESPACE;
			$tokens[$at][1] = ' ';
		}
		return $tokens;
	}

	public function expand(string $path, callable $load): array {
		$tokens = $load($path);
		$replacements = [];
		foreach ($this->files[$path]['declarations'] as $declaration) {
			foreach ($declaration['uses'] as $use) {
				$body = [];
				foreach ($use['names'] as $name) {
					$trait = $this->declarations[$name];
					$members = array_slice($load($trait['file']), $trait['open'] + 1, $trait['end'] - $trait['open'] - 1);
					array_push($body, ...self::bindFieldTypes($members, $declaration, $trait['file']));
				}
				$replacements[$use['start']] = [$use['end'] - $use['start'] + 1, $body];
			}
		}
		krsort($replacements);
		foreach ($replacements as $at => [$length, $body]) { array_splice($tokens, $at, $length, $body); }
		return $tokens;
	}
}
