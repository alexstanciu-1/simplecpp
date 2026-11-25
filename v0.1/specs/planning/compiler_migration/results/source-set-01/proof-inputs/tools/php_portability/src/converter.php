<?php
declare(strict_types=1);

namespace scpp\portability;

require_once __DIR__ . "/import_policy.php";
require_once __DIR__ . "/exception_policy.php";
require_once __DIR__ . "/declaration_index.php";
require_once __DIR__ . "/container_type.php";

final class Node {
	/** @param list<Node> $children */
	public function __construct(
		public string $kind,
		public string $text,
		public int $line,
		public array $children = [],
	) {}
}

/** Local structural parsing and emission after direct trait expansion. No type analysis. */
final class Converter {
	private array $tokens = [];
	private int $position = 0;
	private string $path = '';
	private int $exceptionCounter = 0;

	public function __construct(private array $map) {}

	public function convert(string $source, string $path): string {
		$source = (new Import_Policy($this->map))->prepare($source, $path);
		try {
			$raw = token_get_all($source, TOKEN_PARSE);
		} catch (\ParseError $error) {
			throw new \RuntimeException($path . ': ' . $error->getMessage());
		}
		$tokens = [];
		$line = 1;
		foreach ($raw as $token) {
			[$id, $text, $start] = is_array($token) ? $token : [0, $token, $line];
			$tokens[] = [$id, $text, $start, $path];
			$line = $start + substr_count($text, "\n");
		}
		$index = new Declaration_Index([$path => ['hash' => hash('sha256', $source),
			'declarations' => Declaration_Index::inspect($tokens, $path)]]);
		$index->dependencies($path);
		return $this->convertTokens($index->expand($path, static fn(string $file): array => $tokens), $path);
	}

	/** Prepared tokens may include direct trait members, retaining their source locations. */
	public function convertTokens(array $tokens, string $path): string {
		$this->path = $path;
		$this->exceptionCounter = 0;
		$this->tokens = $tokens;
		foreach ($tokens as $token) {
			[$id, $text, $start] = $token;
			if ($id === T_VARIABLE && str_starts_with($text, '$__scpp_portability_')) {
				$this->path = $token[3] ?? $path;
				$this->fail($start, 'reserved portability temporary prefix');
			}
		}
		if (($this->tokens[0][0] ?? null) !== T_OPEN_TAG) {
			$this->fail(1, 'expected PHP opening tag');
		}
		$this->position = 1;
		$tree = new Node('file', '', 1, $this->sequence(null));
		return $this->emit($tree);
	}

	private function fail(int $line, string $message): never {
		throw new \RuntimeException("{$this->path}:{$line}: {$message}");
	}

	private function nextSignificant(int $at): int {
		while (isset($this->tokens[$at]) && in_array($this->tokens[$at][0], [T_WHITESPACE, T_COMMENT], true)) {
			++$at;
		}
		return $at;
	}

	/** Consume local syntax, without looking up names or their declaration types. */
	private function significant(): array {
		$this->position = $this->nextSignificant($this->position);
		$token = $this->tokens[$this->position++] ?? [0, '', 1];
		$this->path = $token[3] ?? $this->path;
		return $token;
	}

	private function expect(string $text): void {
		$token = $this->significant();
		if ($token[1] !== $text) { $this->fail($token[2], 'expected ' . $text); }
	}

	/** Check literal bytes locally before handing spelling to the selected target. */
	private function stringLiteral(array $token): string {
		[, $text, $line] = $token;
		$quote = $text[0];
		$bytes = '';
		$end = strlen($text) - 1;
		for ($i = 1; $i < $end; ++$i) {
			$byte = $text[$i];
			if ($byte !== '\\' || $i + 1 >= $end) { $bytes .= $byte; continue; }
			$next = $text[++$i];
			if ($quote === "'") {
				$bytes .= in_array($next, ["'", '\\'], true) ? $next : '\\' . $next;
				continue;
			}
			$escapes = ['n' => "\n", 'r' => "\r", 't' => "\t", 'v' => "\v", 'e' => "\x1b", 'f' => "\f", '\\' => '\\', '$' => '$', '"' => '"'];
			if (isset($escapes[$next])) { $bytes .= $escapes[$next]; continue; }
			if ($next === 'u' && ($text[$i + 1] ?? '') === '{') {
				$this->fail($line, 'Unicode escape literals are not portable yet; use UTF-8 source text');
			}
			if ($next === 'x' && preg_match('/^[0-9a-fA-F]{1,2}/', substr($text, $i + 1, $end - $i - 1), $match)) {
				$bytes .= chr(hexdec($match[0]));
				$i += strlen($match[0]);
			} elseif ($next >= '0' && $next <= '7') {
				preg_match('/^[0-7]{1,3}/', substr($text, $i, $end - $i), $match);
				$bytes .= chr(octdec($match[0]) & 255);
				$i += strlen($match[0]) - 1;
			} else {
				$bytes .= '\\' . $next;
			}
		}
		if (str_contains($bytes, "\0") || preg_match('//u', $bytes) !== 1) {
			$this->fail($line, 'binary string literal is not portable to the selected target (NUL or invalid UTF-8)');
		}
		return $text;
	}

	/** Unit or literal integer enum vocabulary; no reflection or symbol lookup. */
	private function enumDeclaration(int $line): Node {
		$name = $this->significant();
		if ($name[0] !== T_STRING) { $this->fail($line, 'expected enum name'); }
		$backing = '';
		if (($this->tokens[$this->nextSignificant($this->position)][1] ?? '') === ':') {
			$this->expect(':');
			$this->expect('int');
			$backing = ' : int';
		}
		$this->expect('{');
		$cases = [];
		while (isset($this->tokens[$this->position])) {
			[$id, $text, $at] = $this->tokens[$this->position++];
			if ($text === '}') { return new Node('enum', $name[1] . $backing, $line, $cases); }
			if (in_array($id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
				$cases[] = new Node('comment', $text, $at);
				continue;
			}
			if ($id !== T_CASE) { $this->fail($at, 'only unit or literal integer enum cases are supported'); }
			$case = $this->significant();
			if ($case[0] !== T_STRING) { $this->fail($at, 'expected enum case name'); }
			$initializer = '';
			if ($backing !== '') {
				$this->expect('=');
				$value = $this->significant();
				if ($value[0] !== T_LNUMBER) { $this->fail($at, 'expected nonnegative integer enum literal'); }
				$initializer = ' = ' . $value[1];
			}
			$this->expect(';');
			$cases[] = new Node('enum_case', 'case ' . $case[1] . $initializer . ';', $at);
		}
		$this->fail($line, 'unclosed enum');
	}

	/** Declaration-only contracts. PHP/STAN check implementations, not this parser. */
	private function interfaceDeclaration(int $line): Node {
		$name = $this->significant();
		if ($name[0] !== T_STRING) { $this->fail($line, 'expected interface name'); }
		$this->expect('{');
		$members = [];
		while (isset($this->tokens[$this->position])) {
			[$id, $text, $at] = $this->tokens[$this->position++];
			if ($text === '}') { return new Node('interface', $name[1], $line, $members); }
			if (in_array($id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
				$members[] = new Node('comment', $text, $at);
				continue;
			}
			if ($id !== T_PUBLIC) { $this->fail($at, 'expected public interface method'); }
			$signature = $this->methodSignature($at, 'public', false, false);
			$this->expect(';');
			$members[] = new Node('method_signature', $signature . ';', $at);
		}
		$this->fail($line, 'unclosed interface');
	}

	/** Literal Type::member syntax only; the target owns member existence/type checks. */
	private function namedConstant(array $type, bool $allowCall = false): string {
		if (!in_array($type[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)
			|| in_array(strtolower($type[1]), ['self', 'parent', 'static'], true)) {
			$this->fail($type[2], 'expected literal type name');
		}
		$this->expect('::');
		$member = $this->significant();
		if ($member[0] !== T_STRING) { $this->fail($member[2], 'expected literal constant name'); }
		$next = $this->nextSignificant($this->position);
		if (!$allowCall && ($this->tokens[$next][1] ?? '') === '(') { $this->fail($member[2], 'static calls are unsupported in field defaults'); }
		return $type[1] . '::' . $member[1];
	}

	/** Explicit signature spelling only; named types are left for target resolution. */
	private function signatureType(bool $return): string {
		$type = $this->significant();
		if ($return && $type[0] === T_ARRAY) { return $this->containerAnnotation($this->significant()); }
		$excluded = ['mixed', 'object', 'iterable', 'never', 'self', 'parent', 'static',
			'null', 'false', 'true', 'array', 'callable'];
		if (!$return) { $excluded[] = 'void'; }
		if (!in_array($type[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)
			|| in_array(strtolower($type[1]), $excluded, true)) {
			$this->fail($type[2], 'expected explicit scalar or named method type' . ($return ? ', or void return' : ''));
		}
		return Exception_Policy::name($type[1]);
	}

	private function methodSignature(int $line, string $visibility, bool $static, bool $containerReturn = true): string {
		$this->expect('function');
		$name = $this->significant();
		if ($name[0] !== T_STRING) { $this->fail($line, 'expected method name'); }
		$this->expect('(');
		$parameters = [];
		if (($this->tokens[$this->nextSignificant($this->position)][1] ?? '') !== ')') {
			do {
				$type = $this->signatureType(false);
				$parameter = $this->significant();
				if ($parameter[0] !== T_VARIABLE) { $this->fail($parameter[2], 'expected named parameter'); }
				$parameters[] = $type . ' ' . $parameter[1];
				$separator = $this->significant();
			} while ($separator[1] === ',');
			if ($separator[1] !== ')') { $this->fail($separator[2], 'expected parameter separator'); }
		} else {
			$this->expect(')');
		}
		$this->expect(':');
		$return = $this->signatureType(true);
		if (!$containerReturn && (str_starts_with($return, 'vector<') || str_starts_with($return, 'hash<'))) {
			$this->fail($line, 'container interface returns require native target support; unsupported on selected v0.1.76');
		}
		return $visibility . ($static ? ' static' : '') . ' function ' . $name[1] . '(' . implode(', ', $parameters) . '): ' . $return;
	}

	private function method(int $line, string $visibility, bool $static): Node {
		$signature = $this->methodSignature($line, $visibility, $static);
		$this->expect('{');
		return new Node('method', $signature, $line, $this->sequence('}'));
	}

	/** Parse explicit recursive containers once for every supported declaration site. */
	private function containerAnnotation(array $token): string {
		if ($token[0] !== T_DOC_COMMENT) { $this->fail($token[2], 'expected explicit container type annotation'); }
		try { return Container_Type::parse($token[1]); }
		catch (\RuntimeException $error) { $this->fail($token[2], $error->getMessage()); }
	}

	/** Expand promotion from its own declaration, never from a lookup at the call site. */
	private function promotedConstructor(int $line): array {
		$this->expect('__construct');
		$this->expect('(');
		$fields = [];
		$parameters = [];
		$assignments = [];
		while (($this->tokens[$this->nextSignificant($this->position)][1] ?? '') !== ')') {
			while (($this->tokens[$this->nextSignificant($this->position)][0] ?? null) === T_DOC_COMMENT) { $this->significant(); }
			$this->expect('public');
			$type = $this->significant();
			$readonly = $type[0] === T_READONLY;
			if ($readonly) { $type = $this->significant(); }
			$nullable = $type[1] === '?';
			if ($nullable) { $type = $this->significant(); }
			if (!in_array($type[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_ARRAY], true)
				|| in_array(strtolower($type[1]), ['mixed', 'object', 'iterable', 'void', 'never', 'self', 'parent', 'static'], true)) {
				$this->fail($type[2], 'expected scalar or named promoted type');
			}
			$mappedType = Exception_Policy::name($type[1]);
			$field = $this->significant();
			if ($field[0] !== T_VARIABLE) { $this->fail($field[2], 'expected promoted parameter name'); }
			if ($type[0] === T_ARRAY) {
				if (!$nullable) { $this->fail($type[2], 'this slice supports only nullable promoted lists'); }
				$mappedType = $this->containerAnnotation($this->significant());
			}
			$typeName = $nullable ? 'nullable<' . $mappedType . '>' : $mappedType;
			$default = '';
			$separator = $this->significant();
			if ($separator[1] === '=') {
				$value = $this->significant();
				if ($nullable && strtolower($value[1]) === 'null') {
					$default = ' = null';
				} elseif ($type[0] === T_ARRAY) {
					$this->fail($value[2], 'nullable promoted list default must be null');
				} elseif ($type[1] === 'int' && $value[0] === T_LNUMBER) {
					$default = ' = ' . $value[1];
				} elseif ($type[1] === 'bool' && in_array(strtolower($value[1]), ['true', 'false'], true)) {
					$default = ' = ' . $value[1];
				} elseif ($type[1] === 'string' && $value[0] === T_CONSTANT_ENCAPSED_STRING) {
					$default = ' = ' . $this->stringLiteral($value);
				} elseif (!in_array($type[1], ['int', 'bool', 'string'], true)) {
					$default = ' = ' . $this->namedConstant($value);
				} else {
					$this->fail($value[2], 'unsupported promoted parameter default');
				}
				$separator = $this->significant();
			}
			$annotated = $nullable || $type[0] === T_ARRAY;
			$property = $annotated
				? ($readonly ? '/** PHP readonly; native usage contract. */ ' : '') . 'public ' . $field[1] . ' ' . $typeName . ';'
				: 'public ' . ($readonly ? 'readonly ' : '') . $typeName . ' ' . $field[1] . ';';
			$fields[] = new Node('property', $property, $line);
			$parameters[] = ($annotated ? $field[1] . ' ' . $typeName : $typeName . ' ' . $field[1]) . $default;
			$assignments[] = new Node('initialization', '$this->' . substr($field[1], 1) . ' = ' . $field[1] . ';', $line);
			if ($separator[1] === ')') { --$this->position; break; }
			if ($separator[1] !== ',') { $this->fail($separator[2], 'expected promoted parameter separator'); }
		}
		$this->expect(')');
		$this->expect('{');
		$assignments = array_merge($assignments, $this->sequence('}'));
		$fields[] = new Node('method', 'public function __construct(' . implode(', ', $parameters) . ')', $line, $assignments);
		return $fields;
	}

	/** A reference class with explicit initialized public fields; no value-record inference. */
	private function referenceClass(int $line, bool $final = false): Node {
		$name = $this->significant();
		if ($name[0] !== T_STRING) { $this->fail($line, 'expected class name'); }
		if (str_starts_with(strtolower($name[1]), 'scpp_portability_')) { $this->fail($line, 'reserved native framework class prefix'); }
		$implements = [];
		if (($this->tokens[$this->nextSignificant($this->position)][0] ?? null) === T_IMPLEMENTS) {
			$this->significant();
			do {
				$interface = $this->significant();
				if (!in_array($interface[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) { $this->fail($interface[2], 'expected literal implemented interface name'); }
				$implements[] = $interface[1];
				$next = $this->tokens[$this->nextSignificant($this->position)][1] ?? '';
				if ($next === ',') { $this->significant(); }
			} while ($next === ',');
		}
		$className = $name[1] . ($implements === [] ? '' : ' implements ' . implode(', ', $implements));
		$this->expect('{');
		$fields = [];
		while (isset($this->tokens[$this->position])) {
			$this->path = $this->tokens[$this->position][3] ?? $this->path;
			[$id, $text, $at] = $this->tokens[$this->position++];
			if ($text === '}') { return new Node($final ? 'final_class' : 'class', $className, $line, $fields); }
			if (in_array($id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
				$fields[] = new Node('comment', $text, $at);
				continue;
			}
			if (!in_array($id, [T_PUBLIC, T_PRIVATE, T_PROTECTED], true)) { $this->fail($at, 'expected explicit member visibility'); }
			$visibility = $text;
			$type = $this->significant();
			if ($type[0] === T_FUNCTION) {
				if (($this->tokens[$this->nextSignificant($this->position)][1] ?? '') === '__construct') {
					if ($visibility !== 'public') { $this->fail($at, 'expected public promoted constructor'); }
					array_push($fields, ...$this->promotedConstructor($at));
				} else {
					--$this->position;
					$fields[] = $this->method($at, $visibility, false);
				}
				continue;
			}
			if ($type[0] === T_STATIC) {
				$fields[] = $this->method($at, $visibility, true);
				continue;
			}
			$nullable = $type[1] === '?';
			if ($nullable) { $type = $this->significant(); }
			if ($type[0] === T_ARRAY) {
				$field = $this->significant();
				if ($field[0] !== T_VARIABLE) { $this->fail($field[2], 'expected named list property'); }
				$vector = $this->containerAnnotation($this->significant());
				$this->expect('=');
				if ($nullable) { $this->expect('null'); }
				else { $this->expect('['); $this->expect(']'); }
				$this->expect(';');
				$nativeType = $nullable ? 'nullable<' . $vector . '>' : $vector;
				$fields[] = new Node('property', $visibility . ' ' . $field[1] . ' ' . $nativeType . ' = ' . ($nullable ? 'null' : '[]') . ';', $at);
				continue;
			}
			if (!in_array($type[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
				$this->fail($type[2], 'expected explicit scalar or named property type');
			}
			$field = $this->significant();
			if ($field[0] !== T_VARIABLE) { $this->fail($field[2], 'expected named property'); }
			$this->expect('=');
			$value = $this->significant();
			if ($nullable) {
				if ($value[1] !== 'null' || in_array(strtolower($type[1]), ['mixed', 'object', 'iterable', 'void', 'never', 'self', 'parent', 'static'], true)) { $this->fail($at, 'nullable property requires an explicit scalar/named type and null default'); }
				$this->expect(';');
				$fields[] = new Node('property', $visibility . ' ' . $field[1] . ' nullable<' . Exception_Policy::name($type[1]) . '> = null;', $at);
				continue;
			}
			$valid = match ($type[1]) {
				'bool' => in_array(strtolower($value[1]), ['true', 'false'], true),
				'int' => $value[0] === T_LNUMBER,
				'string' => $value[0] === T_CONSTANT_ENCAPSED_STRING,
				default => false,
			};
			if (!in_array($type[1], ['bool', 'int', 'string'], true)) {
				$value[1] = $this->namedConstant($value);
				$valid = true;
			}
			if (!$valid) { $this->fail($value[2], 'property default must be a matching scalar literal'); }
			if ($type[1] === 'string') { $value[1] = $this->stringLiteral($value); }
			$this->expect(';');
			$fields[] = new Node('property', $visibility . ' ' . $type[1] . ' ' . $field[1] . ' = ' . $value[1] . ';', $at);
		}
		$this->fail($line, 'unclosed class');
	}

	/** One ordered catch dispatch, avoiding the selected target's repeated-wrapper-catch bug. */
	private function tryBlock(int $line): Node {
		$this->expect('{');
		$children = [new Node('body', '', $line, $this->sequence('}'))];
		while (($this->tokens[$this->nextSignificant($this->position)][0] ?? null) === T_CATCH) {
			$this->significant();
			$this->expect('(');
			$type = $this->significant();
			$kind = Exception_Policy::kind($type[1]);
			if ($kind === null) { $this->fail($type[2], 'catch requires an explicitly qualified framework exception type'); }
			$variable = $this->significant();
			if ($variable[0] !== T_VARIABLE) { $this->fail($line, 'expected catch variable'); }
			$this->expect(')');
			$this->expect('{');
			$children[] = new Node('catch_clause', $variable[1], $type[2], [
				new Node('kind', (string) $kind, $type[2]), new Node('body', '', $type[2], $this->sequence('}'))]);
		}
		if (count($children) === 1) { $this->fail($line, 'try requires a supported catch; finally is not yet portable'); }
		return new Node('try', '$__scpp_portability_exception_' . ++$this->exceptionCounter, $line, $children);
	}

	private function emitTry(Node $node): string {
		$result = 'try {' . $this->emit($node->children[0]) . '} catch (\\scpp_portability_exception ' . $node->text . ') {';
		foreach (array_slice($node->children, 1) as $index => $catch) {
			$result .= ($index === 0 ? 'if' : 'else if') . ' (' . $node->text . '->matches(' . $catch->children[0]->text . ')) {'
				. $catch->text . ' = ' . $node->text . ';' . $this->emit($catch->children[1]) . '}';
		}
		return $result . 'else { throw ' . $node->text . '; }}';
	}

	/** By-value iteration with explicit bindings; container types remain authored. */
	private function foreachLoop(int $line): Node {
		$this->expect('(');
		$iterable = $this->sequence('as');
		if (trim($this->emit(new Node('body', '', $line, $iterable))) === '') { $this->fail($line, 'foreach requires an iterable expression'); }
		$first = $this->significant();
		if ($first[0] !== T_VARIABLE) { $this->fail($first[2], 'foreach requires a by-value variable binding'); }
		$binding = $first[1];
		$separator = $this->significant();
		if ($separator[0] === T_DOUBLE_ARROW) {
			$value = $this->significant();
			if ($value[0] !== T_VARIABLE) { $this->fail($value[2], 'foreach requires a by-value variable binding'); }
			$binding .= ' => ' . $value[1];
			$this->expect(')');
		} elseif ($separator[1] !== ')') { $this->fail($separator[2], 'expected foreach closing parenthesis'); }
		$this->expect('{');
		return new Node('foreach', $binding, $line, [new Node('body', '', $line, $iterable), new Node('body', '', $line, $this->sequence('}'))]);
	}

	/** One non-mutating keyed path; no calls, assignments or multiple operands. */
	private function issetProbe(int $line): Node {
		$this->expect('(');
		$root = $this->significant();
		if ($root[0] !== T_VARIABLE) { $this->fail($line, 'isset requires one keyed variable path'); }
		$text = $root[1];
		$keyed = false;
		while (true) {
			$next = $this->significant();
			if ($next[1] === ')') { break; }
			if ($next[0] === T_OBJECT_OPERATOR) {
				$member = $this->significant();
				if ($member[0] !== T_STRING) { $this->fail($member[2], 'isset requires a fixed member name'); }
				$text .= '->' . $member[1];
			} elseif ($next[1] === '[') {
				$keyed = true;
				$key = $this->significant();
				if (!in_array($key[0], [T_VARIABLE, T_LNUMBER, T_CONSTANT_ENCAPSED_STRING], true)) { $this->fail($key[2], 'isset requires an explicit literal or variable key'); }
				$text .= '[' . ($key[0] === T_CONSTANT_ENCAPSED_STRING ? $this->stringLiteral($key) : $key[1]);
				$end = $this->significant();
				while ($key[0] === T_VARIABLE && $end[0] === T_OBJECT_OPERATOR) {
					$member = $this->significant();
					if ($member[0] !== T_STRING) { $this->fail($member[2], 'isset requires a fixed key member'); }
					$text .= '->' . $member[1];
					$end = $this->significant();
				}
				if ($end[1] !== ']') { $this->fail($end[2], 'unsupported isset key expression'); }
				$text .= ']';
			} else { $this->fail($next[2], 'isset requires one keyed variable path'); }
		}
		if (!$keyed) { $this->fail($line, 'this slice supports keyed isset only'); }
		return new Node('probe', 'isset(' . $text . ')', $line);
	}

	/** @return list<Node> */
	private function sequence(?string $closing): array {
		$nodes = [];
		while (isset($this->tokens[$this->position])) {
			$this->path = $this->tokens[$this->position][3] ?? $this->path;
			[$id, $text, $line] = $this->tokens[$this->position++];
			if ($text === $closing) {
				return $nodes;
			}
			if ($id === T_CONSTANT_ENCAPSED_STRING) {
				$nodes[] = new Node('string_literal', $this->stringLiteral([$id, $text, $line]), $line);
				continue;
			}
			if ($id === T_FOREACH) { $nodes[] = $this->foreachLoop($line); continue; }
			if ($id === T_ISSET) { $nodes[] = $this->issetProbe($line); continue; }
			if ($id === T_BREAK || $id === T_CONTINUE) {
				$this->expect(';');
				$nodes[] = new Node('loop_transfer', $text . ';', $line);
				continue;
			}
			if ($id === T_TRY) { $nodes[] = $this->tryBlock($line); continue; }
			if ($id === T_NAMESPACE) {
				if ($closing !== null) { $this->fail($line, 'namespace must be a file prologue'); }
				$name = $this->significant();
				$this->expect(';');
				$nodes[] = new Node('namespace', 'namespace ' . $name[1] . ';', $line);
				continue;
			}
			if ($id === T_INTERFACE) {
				if ($closing !== null) { $this->fail($line, 'interface declarations must be at file scope'); }
				$nodes[] = $this->interfaceDeclaration($line);
				continue;
			}
			if ($id === T_TRAIT) {
				if ($closing !== null) { $this->fail($line, 'trait declarations must be at file scope'); }
				// Validate the supported member grammar even for an unused trait.
				$this->referenceClass($line);
				continue;
			}
			if ($id === T_CONST) {
				if ($closing !== null) { $this->fail($line, 'constants must be at file scope'); }
				$name = $this->significant();
				if ($name[0] !== T_STRING) { $this->fail($line, 'expected constant name'); }
				$this->expect('=');
				$value = $this->significant();
				if ($value[0] !== T_LNUMBER) { $this->fail($line, 'expected integer literal constant'); }
				$this->expect(';');
				$nodes[] = new Node('constant', 'const ' . $name[1] . ' = ' . $value[1] . ';', $line);
				continue;
			}
			if ($id === T_ENUM) {
				if ($closing !== null) { $this->fail($line, 'enum declarations must be at file scope'); }
				$nodes[] = $this->enumDeclaration($line);
				continue;
			}
			if (in_array($id, [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)
				&& ($this->tokens[$this->nextSignificant($this->position)][1] ?? '') === '::') {
				$name = $this->namedConstant([$id, $text, $line], true);
				$next = $this->nextSignificant($this->position);
				if (($this->tokens[$next][1] ?? '') === '(') {
					$this->position = $next + 1;
					$nodes[] = new Node('call', $name, $line, $this->sequence(')'));
				} else {
					$nodes[] = new Node('named_constant', $name, $line);
				}
				continue;
			}
			if ($id === T_FINAL) {
				if ($closing !== null) { $this->fail($line, 'class declarations must be at file scope'); }
				$this->expect('class');
				$nodes[] = $this->referenceClass($line, true);
				continue;
			}
			if ($id === T_CLASS) {
				if ($closing !== null) { $this->fail($line, 'class declarations must be at file scope'); }
				$nodes[] = $this->referenceClass($line);
				continue;
			}
			if ($id === T_NEW) {
				$name = $this->significant();
				if (!in_array($name[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
					$this->fail($line, 'construction requires a literal class name');
				}
				$this->expect('(');
				try { $nativeName = Exception_Policy::constructionName($name[1]); }
				catch (\RuntimeException $error) { $this->fail($line, $error->getMessage()); }
				$nodes[] = new Node('call', 'new ' . $nativeName, $line, $this->sequence(')'));
				continue;
			}
			if ($id === T_OBJECT_OPERATOR) {
				$name = $this->significant();
				if ($name[0] !== T_STRING) { $this->fail($line, 'property access requires a literal name'); }
				$next = $this->nextSignificant($this->position);
				if (($this->tokens[$next][1] ?? '') === '(') {
					$this->position = $next + 1;
					$nodes[] = new Node('call', '->' . $name[1], $line, $this->sequence(')'));
					continue;
				}
				$nodes[] = new Node('property_access', '->' . $name[1], $line);
				continue;
			}
			if ($id === T_VARIABLE) {
				$at = $this->nextSignificant($this->position);
				if (($this->tokens[$at][1] ?? '') === '(') {
					$this->fail($line, 'dynamic calls are unsupported');
				}
				if (($this->tokens[$at][0] ?? null) === T_DOC_COMMENT) {
					$annotation = $this->tokens[$at][1];
					if (preg_match('~^/\*\*\s*(?:vector|hash)\s*<~', $annotation)) {
						$type = $this->containerAnnotation($this->tokens[$at]);
					} else {
						if (!preg_match('~^/\*\*\s*((?:(?:nullable|result_or_false|result_or_bool)<)?(?:int|uint32|bool|string)>?)\s*\*/$~D', $annotation, $match)) {
							$this->fail($line, 'unsupported local type annotation');
						}
						$type = $match[1];
					}
					if (substr_count($type, '<') !== substr_count($type, '>')) {
						$this->fail($line, 'unbalanced type annotation');
					}
					if (in_array($type, ['result_or_false<bool>', 'result_or_bool<bool>'], true)) {
						$this->fail($line, 'boolean payload wrappers require explicit tagged states (not implemented)');
					}
					$after = $this->nextSignificant($at + 1);
					if (!in_array($this->tokens[$after][1] ?? '', ['=', ';'], true)) {
						$this->fail($line, 'annotation must describe a local declaration');
					}
					$this->position = $at + 1;
					$nodes[] = new Node('local', $text . ' ' . $type, $line);
					continue;
				}
			}
			if ($id === T_NAME_FULLY_QUALIFIED || ($id === T_STRING && isset($this->map[strtolower($text)]))) {
				$name = strtolower($text);
				$rule = $this->map[$name] ?? null;
				if ($id === T_NAME_FULLY_QUALIFIED) {
					foreach ($this->map as $alias => $candidate) {
						if ($candidate['php'] !== null && $name === '\\' . $alias) {
							$this->fail($line, 'global bypass of framework-owned name: ' . $alias);
						}
						if ($name === '\\' . strtolower($candidate['php'] ?? $alias)) {
							$rule = $candidate;
						}
					}
				}
				if ($rule === null) {
					$this->fail($line, 'unmapped qualified operation ' . $text);
				}
				$at = $this->nextSignificant($this->position);
				if (($this->tokens[$at][1] ?? '') !== '(') {
					$this->fail($line, 'mapped operation must be a direct call');
				}
				$this->position = $at + 1;
				$children = $this->sequence(')');
				$count = 1;
				$meaningful = false;
				foreach ($children as $child) {
					if ($child->kind === 'token' && $child->text === ',') { ++$count; }
					if (trim($child->text) !== '' && $child->kind !== 'comment') { $meaningful = true; }
				}
				if (!$meaningful || !in_array($count, (array) $rule['arity'], true)) {
					$this->fail($line, 'wrong argument count for ' . $text);
				}
				$nodes[] = new Node('call', $rule['targets'][$count] ?? $rule['target'], $line, $children);
				continue;
			}
			if ($id === 0 && in_array($text, ['(', '{', '['], true)) {
				$nodes[] = new Node('group', $text, $line, $this->sequence(match ($text) { '(' => ')', '[' => ']', default => '}' }));
				continue;
			}
			$allowed = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_VARIABLE, T_LNUMBER, T_CONSTANT_ENCAPSED_STRING,
				T_TRY, T_THROW, T_FOR, T_INC, T_CONCAT_EQUAL, T_INT_CAST, T_RETURN, T_IS_GREATER_OR_EQUAL, T_ECHO, T_IF, T_ELSE, T_ELSEIF, T_IS_IDENTICAL, T_IS_NOT_IDENTICAL, T_BOOLEAN_AND, T_BOOLEAN_OR];
			if ($id === T_STRING && in_array(strtolower($text), ['true', 'false', 'null'], true)) {
				// Literal keywords only; arbitrary calls/names are outside this slice.
			} elseif (!in_array($id, $allowed, true) && !($id === 0 && in_array($text, ['=', ';', ',', '?', ':', '!', '.', '+', '-', '*', '/', '%', '<', '>'], true))) {
				$this->fail($line, 'unsupported syntax: ' . $text);
			}
			$nodes[] = new Node(in_array($id, [T_COMMENT, T_DOC_COMMENT], true) ? 'comment' : 'token', $text, $line);
		}
		if ($closing !== null) { $this->fail(1, 'unclosed group'); }
		return $nodes;
	}

	private function emit(Node $node): string {
		if ($node->kind === 'foreach') { return 'foreach (' . $this->emit($node->children[0]) . ' as ' . $node->text . ') {' . $this->emit($node->children[1]) . '}'; }
		if ($node->kind === 'try') { return $this->emitTry($node); }
		$body = '';
		foreach ($node->children as $child) { $body .= $this->emit($child); }
		return match ($node->kind) {
			'file', 'body' => $body,
			'enum' => 'enum ' . $node->text . ' {' . $body . '}',
			'method' => $node->text . ' {' . $body . '}',
			'interface' => 'interface ' . $node->text . ' {' . $body . '}',
			'final_class' => 'final class ' . $node->text . ' {' . $body . '}',
			'class' => 'class ' . $node->text . ' {' . $body . '}',
			'call' => $node->text . '(' . $body . ')',
			'group' => $node->text . $body . (match ($node->text) { '(' => ')', '[' => ']', default => '}' }),
			default => $node->text,
		};
	}
}
