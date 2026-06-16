<?php
declare(strict_types=1);

namespace Scpp\S2S\Jss;

use Scpp\S2S\Support\InputException;

final class JssParser
{
	/** @var array<string,bool> */
	private const RESERVED_HELPER_ROOTS = [
		'fs' => true,
		'io' => true,
		'json' => true,
		'dt' => true,
	];

	/** @var list<JssToken> */
	private array $tokens = [];
	private int $index = 0;
	private int $thisAllowedDepth = 0;
	private int $blockDepth = 0;

	/** @param list<JssToken> $tokens */
	public function parse(array $tokens): JssNode
	{
		$this->tokens = $tokens;
		$this->index = 0;
		$this->blockDepth = 0;
		$statements = [];
		while (!$this->check('eof')) {
			$statements[] = $this->parseStatement();
		}
		return new JssNode('program', ['statements' => $statements]);
	}

	/** @param array<string,mixed> $fields */
	private function node(string $kind, array $fields = [], ?JssToken $token = null): JssNode
	{
		if ($token !== null) {
			$fields['range'] = [
				'line' => $token->line,
				'column' => $token->column,
				'offset' => $token->offset,
			];
		}
		return new JssNode($kind, $fields);
	}

	/** @param array<string,mixed> $fields */
	private function nodeFrom(string $kind, array $fields, JssNode $source): JssNode
	{
		if (is_array($source->fields['range'] ?? null)) {
			$fields['range'] = $source->fields['range'];
		}
		return new JssNode($kind, $fields);
	}

	private function parseStatement(): JssNode
	{
		if ($this->match('namespace')) {
			$start = $this->previous();
			$name = $this->parseQualifiedName();
			$this->rejectReservedHelperNamespace($name, $start);
			if ($this->match(';')) {
				return $this->node('namespace_decl', ['name' => $name], $start);
			}
			if ($this->check('{')) {
				return $this->node('namespace_block', [
					'name' => $name,
					'body' => $this->parseBlock(),
				], $start);
			}
			$token = $this->peek();
			throw new InputException('Expected `;` or `{` after namespace declaration. Found `' . $token->text . '` at ' . $token->line . ':' . $token->column . '.');
		}

		if ($this->match('use')) {
			$start = $this->previous();
			$useKind = 'class';
			if ($this->match('function')) {
				$useKind = 'function';
			} elseif ($this->match('const')) {
				$useKind = 'const';
			}
			$name = $this->parseQualifiedName();
			$alias = null;
			if ($this->match('as')) {
				$alias = $this->consume('identifier', 'Expected alias after `as`.')->text;
			}
			$this->rejectReservedHelperUse($name, $alias, $start);
			$this->consume(';', 'Expected `;` after use declaration.');
			return $this->node('use_decl', ['kind' => $useKind, 'name' => $name, 'alias' => $alias], $start);
		}

		if ($this->check('import') || $this->check('export')) {
			$token = $this->peek();
			throw new InputException('JSS JavaScript module syntax `' . $token->text . '` is not supported at ' . $token->line . ':' . $token->column . '. Use static `use`, `use function`, or `use const` declarations.');
		}

		if ($this->check('const') && $this->checkNext('identifier') && $this->checkAhead(2, '=')) {
			if ($this->blockDepth > 0) {
				$token = $this->peek();
				throw new InputException('JSS local `const` declarations are not supported at ' . $token->line . ':' . $token->column . '. Use `let` until local immutability has a PHS/STAN contract.');
			}
			$this->consume('const', 'Expected `const`.');
			$start = $this->previous();
			$name = $this->consume('identifier', 'Expected constant name.')->text;
			$this->consume('=', 'Expected `=` after constant name.');
			$value = $this->parseExpression();
			$this->consume(';', 'Expected `;` after constant declaration.');
			return $this->node('const_decl', ['name' => $name, 'value' => $value], $start);
		}

		if ($this->match('class')) {
			$start = $this->previous();
			$name = $this->consume('identifier', 'Expected class name.')->text;
			$extends = null;
			if ($this->match('extends')) {
				$extends = $this->consume('identifier', 'Expected parent class name after `extends`.')->text;
			}
			$this->consume('{', 'Expected `{` after class name.');
			$members = [];
			$constructorSeen = false;
			while (!$this->check('}') && !$this->check('eof')) {
				$member = $this->parseClassMember();
				if ($member->kind === 'constructor_decl') {
					if ($constructorSeen) {
						$range = $member->fields['range'] ?? [];
						$line = is_array($range) ? (int) ($range['line'] ?? 0) : 0;
						$column = is_array($range) ? (int) ($range['column'] ?? 0) : 0;
						throw new InputException('JSS classes may declare only one constructor' . ($line > 0 && $column > 0 ? ' at ' . $line . ':' . $column : '') . '.');
					}
					$constructorSeen = true;
				}
				$members[] = $member;
			}
			$this->consume('}', 'Expected `}` after class body.');
			return $this->node('class_decl', ['name' => $name, 'extends' => $extends, 'members' => $members], $start);
		}

		if ($this->match('async')) {
			$start = $this->previous();
			$this->consume('function', 'Expected `function` after `async`.');
			$name = $this->consume('identifier', 'Expected function name.')->text;
			$this->consume('(', 'Expected `(` after function name.');
			$params = $this->parseParameterList();
			$this->consume(')', 'Expected `)` after parameters.');
			$this->consume(':', 'Expected explicit return type after JSS function parameters.');
			$returnType = $this->parseTypeSpelling();
			return $this->node('function_decl', [
				'name' => $name,
				'params' => $params,
				'return_type' => $returnType,
				'body' => $this->parseBlock(),
				'is_async' => true,
			], $start);
		}

		if ($this->match('function')) {
			$start = $this->previous();
			$name = $this->consume('identifier', 'Expected function name.')->text;
			$this->consume('(', 'Expected `(` after function name.');
			$params = $this->parseParameterList();
			$this->consume(')', 'Expected `)` after parameters.');
			$this->consume(':', 'Expected explicit return type after JSS function parameters.');
			$returnType = $this->parseTypeSpelling();
			return $this->node('function_decl', [
				'name' => $name,
				'params' => $params,
				'return_type' => $returnType,
				'body' => $this->parseBlock(),
			], $start);
		}

		if ($this->match('return')) {
			$start = $this->previous();
			$value = null;
			if (!$this->check(';')) {
				$value = $this->parseExpression();
			}
			$this->consume(';', 'Expected `;` after return.');
			return $this->node('return', ['value' => $value], $start);
		}

		if ($this->match('break')) {
			$start = $this->previous();
			$this->consume(';', 'Expected `;` after break.');
			return $this->node('break', [], $start);
		}

		if ($this->match('continue')) {
			$start = $this->previous();
			$this->consume(';', 'Expected `;` after continue.');
			return $this->node('continue', [], $start);
		}

		if ($this->match('delete')) {
			$start = $this->previous();
			$target = $this->parseExpression();
			$this->consume(';', 'Expected `;` after JSS delete.');
			return $this->node('delete', ['target' => $target], $start);
		}

		if ($this->match('if')) {
			$start = $this->previous();
			$this->consume('(', 'Expected `(` after `if`.');
			$condition = $this->parseExpression();
			$this->consume(')', 'Expected `)` after if condition.');
			$thenStatements = $this->parseBlock();
			$elseStatements = [];
			if ($this->match('else')) {
				$elseStatements = $this->check('if') ? [$this->parseStatement()] : $this->parseBlock();
			}
			return $this->node('if', [
				'condition' => $condition,
				'then' => $thenStatements,
				'else' => $elseStatements,
			], $start);
		}

		if ($this->match('while')) {
			$start = $this->previous();
			$this->consume('(', 'Expected `(` after `while`.');
			$condition = $this->parseExpression();
			$this->consume(')', 'Expected `)` after while condition.');
			return $this->node('while', [
				'condition' => $condition,
				'body' => $this->parseBlock(),
			], $start);
		}

		if ($this->match('do')) {
			$start = $this->previous();
			$body = $this->parseBlock();
			$this->consume('while', 'Expected `while` after do block.');
			$this->consume('(', 'Expected `(` after `while`.');
			$condition = $this->parseExpression();
			$this->consume(')', 'Expected `)` after do while condition.');
			$this->consume(';', 'Expected `;` after do while.');
			return $this->node('do_while', [
				'condition' => $condition,
				'body' => $body,
			], $start);
		}

		if ($this->match('switch')) {
			$start = $this->previous();
			$this->consume('(', 'Expected `(` after `switch`.');
			$expression = $this->parseExpression();
			$this->consume(')', 'Expected `)` after switch expression.');
			$this->consume('{', 'Expected `{` after switch expression.');
			$cases = [];
			while (!$this->check('}') && !$this->check('eof')) {
				$value = null;
				if ($this->match('case')) {
					$value = $this->parseExpression();
					$this->consume(':', 'Expected `:` after case value.');
				} elseif ($this->match('default')) {
					$this->consume(':', 'Expected `:` after default.');
				} else {
					$token = $this->peek();
					throw new InputException('Expected `case` or `default` in switch at ' . $token->line . ':' . $token->column . '.');
				}
				$body = [];
				while (!$this->check('case') && !$this->check('default') && !$this->check('}') && !$this->check('eof')) {
					$body[] = $this->parseStatement();
				}
				$cases[] = ['value' => $value, 'body' => $body];
			}
			$this->consume('}', 'Expected `}` after switch body.');
			return $this->node('switch', [
				'expression' => $expression,
				'cases' => $cases,
			], $start);
		}

		if ($this->match('for')) {
			$start = $this->previous();
			$this->consume('(', 'Expected `(` after `for`.');
			if ($this->forHeaderContains('of')) {
				$kind = null;
				if ($this->match('let') || $this->match('const')) {
					$kind = $this->previous()->kind;
					if ($kind === 'const') {
						$token = $this->previous();
						throw new InputException('JSS `for...of` const loop locals are not supported at ' . $token->line . ':' . $token->column . '. Use `let` until local immutability has a PHS/STAN contract.');
					}
				} else {
					$token = $this->peek();
					throw new InputException('JSS `for...of` requires `let` or `const` loop locals at ' . $token->line . ':' . $token->column . '.');
				}
				$firstName = $this->consume('identifier', 'Expected foreach value name.')->text;
				$firstType = null;
				if ($this->match(':')) {
					$firstType = $this->parseTypeSpelling();
				}
				$keyName = null;
				$valueName = $firstName;
				$valueType = $firstType;
				if ($this->match(',')) {
					$keyName = $firstName;
					$valueName = $this->consume('identifier', 'Expected foreach value name after key.')->text;
					$valueType = null;
					if ($this->match(':')) {
						$valueType = $this->parseTypeSpelling();
					}
				}
				$this->consume('of', 'Expected `of` in foreach header.');
				$source = $this->parseExpression();
				$this->consume(')', 'Expected `)` after foreach source.');
				if ($keyName !== null) {
					return $this->node('foreach_key_value', [
						'mutable' => $kind !== 'const',
						'key_name' => $keyName,
						'value_name' => $valueName,
						'value_type' => $valueType,
						'source' => $source,
						'body' => $this->parseBlock(),
					], $start);
				}
				return $this->node('foreach_value', [
					'mutable' => $kind !== 'const',
					'value_name' => $valueName,
					'value_type' => $valueType,
					'source' => $source,
					'body' => $this->parseBlock(),
				], $start);
			}
			$init = $this->parseForClauseStatement(';');
			$this->consume(';', 'Expected `;` after for initializer.');
			$condition = $this->parseExpression();
			$this->consume(';', 'Expected `;` after for condition.');
			$step = $this->parseForClauseStatement(')');
			$this->consume(')', 'Expected `)` after for step.');
			return $this->node('for', [
				'init' => $init,
				'condition' => $condition,
				'step' => $step,
				'body' => $this->parseBlock(),
			], $start);
		}

		if ($this->match('let') || $this->match('const')) {
			$kind = $this->previous()->kind;
			$start = $this->previous();
			if ($kind === 'const') {
				throw new InputException('JSS local `const` declarations are not supported at ' . $start->line . ':' . $start->column . '. Use top-level `const NAME = value;` for constants, or `let` for locals.');
			}
			if ($this->check('{') || $this->check('[')) {
				$token = $this->peek();
				throw new InputException('JSS destructuring declarations are not supported at ' . $token->line . ':' . $token->column . '. Use explicit typed locals instead.');
			}
			$name = $this->consume('identifier', 'Expected identifier after `' . $kind . '`.')->text;
			$type = null;
			if ($this->match(':')) {
				$type = $this->parseTypeSpelling();
			}
			$initializer = null;
			if ($this->match('=')) {
				$initializer = $this->parseExpression();
			}
			$this->consume(';', 'Expected `;` after JSS declaration.');
			return $this->node('var_decl', [
				'mutable' => $kind === 'let',
				'name' => $name,
				'type' => $type,
				'initializer' => $initializer,
			], $start);
		}

		if (($this->check('++') || $this->check('--')) && $this->checkNext('identifier')) {
			$operator = $this->consume($this->peek()->kind, 'Expected update operator.')->text;
			$start = $this->previous();
			$target = $this->parseExpression();
			$this->consume(';', 'Expected `;` after JSS update.');
			return $this->node('update', ['operator' => $operator, 'target' => $target, 'prefix' => true], $start);
		}

		$expression = $this->parseExpression();
		if ($this->check('=>')) {
			$token = $this->peek();
			throw new InputException('JSS arrow functions are not supported yet at ' . $token->line . ':' . $token->column . '.');
		}
		if ($this->match('=')) {
			$value = $this->parseExpression();
			$this->consume(';', 'Expected `;` after JSS assignment.');
			return $this->nodeFrom('assign', ['target' => $expression, 'value' => $value], $expression);
		}
		if ($this->match('+=') || $this->match('-=') || $this->match('*=') || $this->match('/=') || $this->match('%=')) {
			$operator = $this->previous()->text;
			$value = $this->parseExpression();
			$this->consume(';', 'Expected `;` after JSS compound assignment.');
			return $this->nodeFrom('compound_assign', ['target' => $expression, 'operator' => $operator, 'value' => $value], $expression);
		}
		if ($this->match('++') || $this->match('--')) {
			$operator = $this->previous()->text;
			$this->consume(';', 'Expected `;` after JSS update.');
			return $this->nodeFrom('update', ['operator' => $operator, 'target' => $expression, 'prefix' => false], $expression);
		}
		$this->consume(';', 'Expected `;` after JSS expression.');
		if ($expression->kind === 'call' && $this->isPushCall($expression)) {
			return $this->nodeFrom('append', [
				'target' => $expression->fields['callee']->fields['object'] ?? null,
				'value' => ($expression->fields['args'] ?? [])[0] ?? null,
			], $expression);
		}
		return $this->nodeFrom('expr_stmt', ['expression' => $expression], $expression);
	}

	private function parseClassMember(): JssNode
	{
		if ($this->check('identifier') && in_array($this->peek()->text, ['public', 'private', 'protected'], true)) {
			$modifier = $this->peek();
			if ($modifier->text !== 'public') {
				throw new InputException('JSS class member modifier `' . $modifier->text . '` is not supported at ' . $modifier->line . ':' . $modifier->column . '. Use default/public members; ES6 `#name` private fields are a future feature.');
			}
			$this->consume('identifier', 'Expected member modifier.');
		}
		$isStatic = $this->match('static');

		if ($this->match('const')) {
			$start = $this->previous();
			$name = $this->consume('identifier', 'Expected class constant name.')->text;
			$type = null;
			if ($this->match(':')) {
				$type = $this->parseTypeSpelling();
			}
			$this->consume('=', 'Expected `=` after class constant name.');
			$value = $this->parseExpression();
			$this->consume(';', 'Expected `;` after class constant declaration.');
			return $this->node('class_const_decl', ['name' => $name, 'type' => $type, 'value' => $value], $start);
		}

		if ($this->match('constructor')) {
			$start = $this->previous();
			$this->consume('(', 'Expected `(` after constructor.');
			$params = $this->parseParameterList();
			$this->consume(')', 'Expected `)` after constructor parameters.');
			if ($this->check(':')) {
				$token = $this->peek();
				throw new InputException('JSS constructors do not declare return types at ' . $token->line . ':' . $token->column . '.');
			}
			return $this->node('constructor_decl', [
				'params' => $params,
				'body' => $this->parseThisAwareBlock(true),
			], $start);
		}

		$nameToken = $this->consume('identifier', 'Expected class member name.');
		$name = $nameToken->text;
		if ($name === '__construct') {
			throw new InputException('JSS constructors must use `constructor(...)`, not `__construct(...)`, at ' . $nameToken->line . ':' . $nameToken->column . '.');
		}
		if ($this->match('(')) {
			$params = $this->parseParameterList();
			$this->consume(')', 'Expected `)` after method parameters.');
			$this->consume(':', 'Expected explicit return type after JSS method parameters.');
			$returnType = $this->parseTypeSpelling();
			return $this->node('method_decl', [
				'name' => $name,
				'params' => $params,
				'return_type' => $returnType,
				'static' => $isStatic,
				'body' => $this->parseThisAwareBlock(!$isStatic),
			], $nameToken);
		}

		$this->consume(':', 'Expected `:` after property name.');
		$type = $this->parseTypeSpelling();
		$default = null;
		if ($this->match('=')) {
			$default = $this->parseExpression();
		}
		$this->consume(';', 'Expected `;` after property declaration.');
		return $this->node('property_decl', ['name' => $name, 'type' => $type, 'default' => $default, 'static' => $isStatic], $nameToken);
	}

	private function parseQualifiedName(): string
	{
		$name = $this->consume('identifier', 'Expected qualified name.')->text;
		while ($this->match('.')) {
			$name .= '\\' . $this->consume('identifier', 'Expected qualified name segment.')->text;
		}
		return $name;
	}

	private function rejectReservedHelperNamespace(string $name, JssToken $token): void
	{
		$root = strtolower(strtok(str_replace('\\', '.', $name), '.') ?: '');
		if (!isset(self::RESERVED_HELPER_ROOTS[$root])) {
			return;
		}
		throw new InputException('JSS namespace root `' . $root . '` is reserved for helper-family calls like `' . $root . '.…` and cannot be used as a user namespace at ' . $token->line . ':' . $token->column . '.');
	}

	private function rejectReservedHelperUse(string $name, ?string $alias, JssToken $token): void
	{
		$root = strtolower(strtok(str_replace('\\', '.', $name), '.') ?: '');
		if (isset(self::RESERVED_HELPER_ROOTS[$root])) {
			throw new InputException('JSS reserved helper root `' . $root . '` is not imported through `use`; call `' . $root . '.…` directly at ' . $token->line . ':' . $token->column . '.');
		}
		if ($alias !== null && isset(self::RESERVED_HELPER_ROOTS[strtolower($alias)])) {
			throw new InputException('JSS alias `' . $alias . '` is reserved for helper-family calls and cannot be reused at ' . $token->line . ':' . $token->column . '.');
		}
	}

	/** @return list<array{name:string,type:string,default:mixed}> */
	private function parseParameterList(): array
	{
		$params = [];
		if ($this->check(')')) {
			return $params;
		}
		do {
			$paramName = $this->consume('identifier', 'Expected parameter name.')->text;
			$this->consume(':', 'Expected `:` after parameter name.');
			$paramType = $this->parseTypeSpelling();
			$default = null;
			if ($this->match('=')) {
				$default = $this->parseExpression();
			}
			$params[] = [
				'name' => $paramName,
				'type' => $paramType,
				'default' => $default,
			];
		} while ($this->match(','));
		return $params;
	}

	private function parseExpression(): JssNode
	{
		return $this->parseTernary();
	}

	private function parseTernary(): JssNode
	{
		$expr = $this->parseNullCoalesce();
		if (!$this->match('?')) {
			return $expr;
		}
		$question = $this->previous();
		$whenTrue = $this->parseExpression();
		$this->consume(':', 'Expected `:` after JSS ternary true branch.');
		$whenFalse = $this->parseExpression();
		foreach ([$whenTrue, $whenFalse] as $branch) {
			if ($branch instanceof JssNode && $branch->kind === 'ternary') {
				$range = $branch->fields['range'] ?? null;
				$line = is_array($range) ? (int) ($range['line'] ?? 0) : 0;
				$column = is_array($range) ? (int) ($range['column'] ?? 0) : 0;
				throw new InputException('JSS ternary currently supports only a single `cond ? a : b` site at ' . ($line > 0 && $column > 0 ? $line . ':' . $column : $question->line . ':' . $question->column) . '.');
			}
		}
		return $this->nodeFrom('ternary', [
			'condition' => $expr,
			'when_true' => $whenTrue,
			'when_false' => $whenFalse,
		], $expr);
	}

	private function parseNullCoalesce(): JssNode
	{
		$expr = $this->parseLogicalOr();
		if (!$this->match('??')) {
			return $expr;
		}
		$operator = $this->previous();
		$right = $this->parseLogicalOr();
		if ($this->check('??')) {
			$token = $this->peek();
			throw new InputException('JSS null coalescing currently supports only a single `lhs ?? rhs` site at ' . $token->line . ':' . $token->column . '.');
		}
		return $this->nodeFrom('binary', [
			'operator' => $operator->text,
			'left' => $expr,
			'right' => $right,
		], $expr);
	}

	private function parseLogicalOr(): JssNode
	{
		$expr = $this->parseLogicalAnd();
		while ($this->match('||')) {
			$operator = $this->previous();
			$right = $this->parseLogicalAnd();
			$expr = $this->nodeFrom('binary', [
				'operator' => $operator->text,
				'left' => $expr,
				'right' => $right,
			], $expr);
		}
		return $expr;
	}

	private function parseLogicalAnd(): JssNode
	{
		$expr = $this->parseEquality();
		while ($this->match('&&')) {
			$operator = $this->previous();
			$right = $this->parseEquality();
			$expr = $this->nodeFrom('binary', [
				'operator' => $operator->text,
				'left' => $expr,
				'right' => $right,
			], $expr);
		}
		return $expr;
	}

	private function forHeaderContains(string $kind): bool
	{
		for ($i = $this->index; isset($this->tokens[$i]) && $this->tokens[$i]->kind !== ')'; $i++) {
			if ($this->tokens[$i]->kind === $kind) {
				return true;
			}
		}
		return false;
	}

	private function parseForClauseStatement(string $terminator): JssNode
	{
		if ($this->check('let') || $this->check('const')) {
			$kind = $this->consume($this->peek()->kind, 'Expected for initializer.')->kind;
			$start = $this->previous();
			if ($kind === 'const') {
				throw new InputException('JSS local `const` declarations are not supported at ' . $start->line . ':' . $start->column . '. Use `let` in classic for initializers.');
			}
			$name = $this->consume('identifier', 'Expected identifier after `' . $kind . '`.')->text;
			$type = null;
			if ($this->match(':')) {
				$type = $this->parseTypeSpelling();
			}
			$initializer = null;
			if ($this->match('=')) {
				$initializer = $this->parseExpression();
			}
			return $this->node('var_decl', [
				'mutable' => $kind === 'let',
				'name' => $name,
				'type' => $type,
				'initializer' => $initializer,
			], $start);
		}
		if ($this->check($terminator)) {
			return new JssNode('empty');
		}
		if (($this->check('++') || $this->check('--')) && $this->checkNext('identifier')) {
			$operator = $this->consume($this->peek()->kind, 'Expected update operator.')->text;
			return $this->node('update', ['operator' => $operator, 'target' => $this->parseExpression(), 'prefix' => true], $this->previous());
		}
		$expression = $this->parseExpression();
		if ($this->match('=')) {
			return $this->nodeFrom('assign', ['target' => $expression, 'value' => $this->parseExpression()], $expression);
		}
		if ($this->match('+=') || $this->match('-=') || $this->match('*=') || $this->match('/=') || $this->match('%=')) {
			return $this->nodeFrom('compound_assign', ['target' => $expression, 'operator' => $this->previous()->text, 'value' => $this->parseExpression()], $expression);
		}
		if ($this->match('++') || $this->match('--')) {
			return $this->nodeFrom('update', ['operator' => $this->previous()->text, 'target' => $expression, 'prefix' => false], $expression);
		}
		return $this->nodeFrom('expr_stmt', ['expression' => $expression], $expression);
	}

	private function parseTypeSpelling(): string
	{
		$nullablePrefix = $this->match('?');
		if (!$this->check('identifier') && !$this->check('void') && !$this->check('null')) {
			$token = $this->peek();
			throw new InputException('Expected type name after `:`. Found `' . $token->text . '` at ' . $token->line . ':' . $token->column . '.');
		}
		$type = $this->consume($this->peek()->kind, 'Expected type name after `:`.')->text;
		if ($this->match('<')) {
			$inner = $this->parseTypeSpelling();
			$this->consume('>', 'Expected `>` after generic type argument.');
			$type .= '<' . $inner . '>';
		}
		if ($this->match('|')) {
			$right = $this->parseTypeSpelling();
			if ($right === 'null') {
				$type = '?' . $type;
			} elseif ($type === 'null') {
				$type = '?' . $right;
			} else {
				$type .= '|' . $right;
			}
		}
		if ($nullablePrefix && $type[0] !== '?') {
			$type = '?' . $type;
		}
		return $type;
	}

	/** @return list<JssNode> */
	private function parseBlock(): array
	{
		$this->consume('{', 'Expected `{` to start block.');
		$this->blockDepth++;
		$statements = [];
		try {
			while (!$this->check('}') && !$this->check('eof')) {
				$statements[] = $this->parseStatement();
			}
		} finally {
			$this->blockDepth--;
		}
		$this->consume('}', 'Expected `}` to end block.');
		return $statements;
	}

	/** @return list<JssNode> */
	private function parseThisAwareBlock(bool $allowThis): array
	{
		if (!$allowThis) {
			return $this->parseBlock();
		}
		$this->thisAllowedDepth++;
		try {
			return $this->parseBlock();
		} finally {
			$this->thisAllowedDepth--;
		}
	}

	private function parseEquality(): JssNode
	{
		$expr = $this->parseComparison();
		while ($this->match('===') || $this->match('!==') || $this->match('==') || $this->match('!=')) {
			$operator = $this->previous();
			$right = $this->parseComparison();
			$expr = $this->nodeFrom('binary', [
				'operator' => $operator->text,
				'left' => $expr,
				'right' => $right,
			], $expr);
		}
		return $expr;
	}

	private function parseComparison(): JssNode
	{
		$expr = $this->parseAddition();
		while ($this->match('<') || $this->match('>') || $this->match('<=') || $this->match('>=')) {
			$operator = $this->previous();
			$right = $this->parseAddition();
			$expr = $this->nodeFrom('binary', [
				'operator' => $operator->text,
				'left' => $expr,
				'right' => $right,
			], $expr);
		}
		return $expr;
	}

	private function parseAddition(): JssNode
	{
		$expr = $this->parseMultiplication();
		while ($this->match('+') || $this->match('-')) {
			$operator = $this->previous();
			$right = $this->parseMultiplication();
			$expr = $this->nodeFrom('binary', [
				'operator' => $operator->text,
				'left' => $expr,
				'right' => $right,
			], $expr);
		}
		return $expr;
	}

	private function parseMultiplication(): JssNode
	{
		$expr = $this->parseUnary();
		while ($this->match('*') || $this->match('/') || $this->match('%')) {
			$operator = $this->previous();
			$right = $this->parseUnary();
			$expr = $this->nodeFrom('binary', [
				'operator' => $operator->text,
				'left' => $expr,
				'right' => $right,
			], $expr);
		}
		return $expr;
	}

	private function parseUnary(): JssNode
	{
		if ($this->match('await')) {
			return $this->node('await', [
				'expression' => $this->parseUnary(),
			], $this->previous());
		}
		if ($this->match('!') || $this->match('-')) {
			return $this->node('unary', [
				'operator' => $this->previous()->text,
				'expression' => $this->parseUnary(),
			], $this->previous());
		}
		if ($this->match('&')) {
			$start = $this->previous();
			$target = $this->consume('identifier', 'Expected simple identifier after JSS reference `&`.');
			return $this->node('reference', ['target' => $target->text], $start);
		}
		return $this->parseCallMember();
	}

	private function parseCallMember(): JssNode
	{
		$expr = $this->parsePrimary();
		while (true) {
			if ($this->match('::')) {
				if ($expr->kind !== 'late_static_scope') {
					$range = $expr->fields['range'] ?? null;
					$line = is_array($range) ? (int) ($range['line'] ?? 0) : $this->previous()->line;
					$column = is_array($range) ? (int) ($range['column'] ?? 0) : $this->previous()->column;
					throw new InputException('JSS `::` is currently reserved for direct `static::name` late-static access at ' . $line . ':' . $column . '. Use dotted class/member access for named classes.');
				}
				$memberToken = $this->consume('identifier', 'Expected member name after `static::`.');
				$expr = $this->nodeFrom('late_static_member', ['member' => $memberToken->text], $expr);
				continue;
			}
			if ($this->match('.')) {
				$memberToken = $this->consume('identifier', 'Expected member name after `.`.');
				$member = $memberToken->text;
				if ($member === 'prototype') {
					throw new InputException('JSS prototype access is not supported at ' . $memberToken->line . ':' . $memberToken->column . '. Use class declarations and instance members instead.');
				}
				$expr = $this->nodeFrom('member', ['object' => $expr, 'member' => $member], $expr);
				continue;
			}
			if ($this->match('?.')) {
				$memberToken = $this->consume('identifier', 'Expected member name after `?.`.');
				$expr = $this->nodeFrom('optional_member', ['object' => $expr, 'member' => $memberToken->text], $expr);
				continue;
			}
			if ($this->match('(')) {
				$args = [];
				if (!$this->check(')')) {
					do {
						$args[] = $this->parseExpression();
					} while ($this->match(','));
				}
				$this->consume(')', 'Expected `)` after call arguments.');
				$expr = $this->nodeFrom('call', ['callee' => $expr, 'args' => $args], $expr);
				continue;
			}
			if ($this->match('[')) {
				$index = $this->parseExpression();
				$this->consume(']', 'Expected `]` after index expression.');
				$expr = $this->nodeFrom('index', ['object' => $expr, 'index' => $index], $expr);
				continue;
			}
			break;
		}
		return $expr;
	}

	private function parsePrimary(): JssNode
	{
		if ($this->check('...')) {
			$token = $this->peek();
			throw new InputException('JSS spread/rest syntax is not supported at ' . $token->line . ':' . $token->column . '.');
		}
		if ($this->check('=>')) {
			$token = $this->peek();
			throw new InputException('JSS arrow functions are not supported yet at ' . $token->line . ':' . $token->column . '.');
		}
		if ($this->match('number')) {
			return $this->node('number', ['value' => $this->previous()->text], $this->previous());
		}
		if ($this->match('string')) {
			return $this->node('string', ['value' => $this->previous()->text], $this->previous());
		}
		if ($this->match('template')) {
			$this->assertSupportedTemplateLiteral($this->previous());
			return $this->node('template', ['value' => $this->previous()->text], $this->previous());
		}
		if ($this->match('true') || $this->match('false')) {
			return $this->node('boolean', ['value' => $this->previous()->text], $this->previous());
		}
		if ($this->match('null')) {
			return $this->node('null', [], $this->previous());
		}
		if ($this->match('this')) {
			$token = $this->previous();
			if ($this->thisAllowedDepth <= 0) {
				throw new InputException('JSS dynamic `this` binding is not supported at ' . $token->line . ':' . $token->column . '. Use explicit instance methods and member access instead.');
			}
			return $this->node('identifier', ['name' => 'this'], $token);
		}
		if ($this->match('static')) {
			return $this->node('late_static_scope', [], $this->previous());
		}
		if ($this->match('new')) {
			$start = $this->previous();
			$className = $this->consume('identifier', 'Expected class name after `new`.')->text;
			$this->consume('(', 'Expected `(` after class name.');
			$args = [];
			if (!$this->check(')')) {
				do {
					$args[] = $this->parseExpression();
				} while ($this->match(','));
			}
			$this->consume(')', 'Expected `)` after constructor arguments.');
			return $this->node('new', ['class_name' => $className, 'args' => $args], $start);
		}
		if ($this->match('identifier')) {
			if ($this->previous()->text === 'prototype') {
				$token = $this->previous();
				throw new InputException('JSS prototype objects are not supported at ' . $token->line . ':' . $token->column . '. Use class declarations and instance members instead.');
			}
			return $this->node('identifier', ['name' => $this->previous()->text], $this->previous());
		}
		if ($this->match('[')) {
			$start = $this->previous();
			$items = [];
			if (!$this->check(']')) {
				do {
					$items[] = $this->parseExpression();
				} while ($this->match(','));
			}
			$this->consume(']', 'Expected `]` after array literal.');
			return $this->node('array_literal', ['items' => $items], $start);
		}
		if ($this->match('{')) {
			$start = $this->previous();
			$pairs = [];
			if (!$this->check('}')) {
				do {
					$key = $this->parseObjectLiteralKey();
					$this->consume(':', 'Expected `:` after object literal key.');
					$pairs[] = ['key' => $key, 'value' => $this->parseExpression()];
				} while ($this->match(','));
			}
			$this->consume('}', 'Expected `}` after object literal.');
			return $this->node('object_literal', ['pairs' => $pairs], $start);
		}
		if ($this->match('(')) {
			if ($this->looksLikeArrowFunctionAfterOpenParen()) {
				return $this->parseArrowFunctionAfterOpenParen($this->previous());
			}
			$expr = $this->parseExpression();
			$this->consume(')', 'Expected `)` after grouped expression.');
			return $expr;
		}
		$token = $this->peek();
		throw new InputException('Unexpected JSS token `' . $token->text . '` at ' . $token->line . ':' . $token->column . '.');
	}

	private function parseObjectLiteralKey(): string
	{
		if ($this->match('string') || $this->match('identifier') || $this->match('number')) {
			return $this->previous()->text;
		}
		$token = $this->peek();
		throw new InputException('Expected object literal key at ' . $token->line . ':' . $token->column . '.');
	}

	private function looksLikeArrowFunctionAfterOpenParen(): bool
	{
		$depth = 1;
		for ($i = $this->index; isset($this->tokens[$i]); $i++) {
			$kind = $this->tokens[$i]->kind;
			if ($kind === '(') {
				$depth++;
			} elseif ($kind === ')') {
				$depth--;
				if ($depth === 0) {
					if (($this->tokens[$i + 1] ?? null)?->kind !== ':') {
						return false;
					}
					for ($j = $i + 2; isset($this->tokens[$j]); $j++) {
						if ($this->tokens[$j]->kind === '=>') {
							return true;
						}
						if (in_array($this->tokens[$j]->kind, [';', ')', '{', '}'], true)) {
							return false;
						}
					}
					return false;
				}
			}
		}
		return false;
	}

	private function parseArrowFunctionAfterOpenParen(JssToken $start): JssNode
	{
		$params = [];
		if (!$this->check(')')) {
			do {
				$paramName = $this->consume('identifier', 'Expected JSS arrow parameter name.')->text;
				$this->consume(':', 'Expected `:` after JSS arrow parameter name.');
				$params[] = [
					'name' => $paramName,
					'type' => $this->parseTypeSpelling(),
					'default' => null,
				];
			} while ($this->match(','));
		}
		$this->consume(')', 'Expected `)` after JSS arrow parameters.');
		$this->consume(':', 'Expected explicit return type after JSS arrow parameters.');
		$returnType = $this->parseTypeSpelling();
		$this->consume('=>', 'Expected `=>` after JSS arrow return type.');
		return $this->node('arrow_function', [
			'params' => $params,
			'return_type' => $returnType,
			'body' => $this->parseExpression(),
		], $start);
	}

	private function isPushCall(JssNode $expression): bool
	{
		$callee = $expression->fields['callee'] ?? null;
		if (!$callee instanceof JssNode || $callee->kind !== 'member') {
			return false;
		}
		if ((string) ($callee->fields['member'] ?? '') !== 'push') {
			return false;
		}
		return count(is_array($expression->fields['args'] ?? null) ? $expression->fields['args'] : []) === 1;
	}

	private function assertSupportedTemplateLiteral(JssToken $token): void
	{
		$value = $token->text;
		if (!preg_match_all('/\$\{([^}]*)\}/', $value, $matches)) {
			return;
		}
		foreach ($matches[1] as $expression) {
			if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*)*$/', (string) $expression)) {
				throw new InputException('JSS template literals currently support only `${identifier}` and `${a.b}`-style interpolation at ' . $token->line . ':' . $token->column . '.');
			}
		}
	}

	private function match(string $kind): bool
	{
		if (!$this->check($kind)) {
			return false;
		}
		$this->index++;
		return true;
	}

	private function check(string $kind): bool
	{
		return $this->peek()->kind === $kind;
	}

	private function checkNext(string $kind): bool
	{
		return ($this->tokens[$this->index + 1] ?? null)?->kind === $kind;
	}

	private function checkAhead(int $offset, string $kind): bool
	{
		return ($this->tokens[$this->index + $offset] ?? null)?->kind === $kind;
	}

	private function consume(string $kind, string $message): JssToken
	{
		if ($this->check($kind)) {
			$this->index++;
			return $this->previous();
		}
		$token = $this->peek();
		throw new InputException($message . ' Found `' . $token->text . '` at ' . $token->line . ':' . $token->column . '.');
	}

	private function peek(): JssToken
	{
		return $this->tokens[$this->index] ?? $this->tokens[array_key_last($this->tokens)];
	}

	private function previous(): JssToken
	{
		return $this->tokens[$this->index - 1];
	}
}
