<?php
declare(strict_types=1);

namespace Scpp\S2S\Jss;

final class JssEmitter
{
	/** @var array<string,string> */
	private array $localTypes = [];
	/** @var array<string,bool> */
	private array $localNames = [];
	/** @var array<string,bool> */
	private array $classNames = [];
	/** @var array<string,bool> */
	private array $constantNames = [];
	/** @var array<string,bool> */
	private array $namespaceConstantNames = [];

	/** @var array<string,bool> */
	private array $staticPropertyNames = [];
	/** @var array<string,string> */
	private array $useAliases = [];
	/** @var array<string,array<string,mixed>> */
	private array $memberClassifications = [];
	/** @var array<string,array<string,mixed>> */
	private array $identifierClassifications = [];
	/** @var array<string,array<string,mixed>> */
	private array $binaryPlusClassifications = [];
	private bool $requireFrontendClassifications = false;
	private string $currentNamespace = '';

	public function __construct(
		private readonly JssFrontendClassificationNormalizer $classificationNormalizer = new JssFrontendClassificationNormalizer(),
		private readonly JssCallSurface $callSurface = new JssCallSurface(),
	)
	{
	}

	/** @param array<string,array<string,mixed>> $frontendClassifications */
	public function emit(JssNode $program, array $frontendClassifications = [], bool $requireFrontendClassifications = false): string
	{
		$this->localTypes = [];
		$this->localNames = [];
		$this->classNames = [];
		$this->constantNames = [];
		$this->namespaceConstantNames = [];
		$this->staticPropertyNames = [];
		$this->useAliases = [];
		$this->memberClassifications = $this->classificationNormalizer->byMember($frontendClassifications);
		$this->identifierClassifications = $this->classificationNormalizer->byIdentifier($frontendClassifications);
		$this->binaryPlusClassifications = $this->classificationNormalizer->byBinaryPlus($frontendClassifications);
		$this->requireFrontendClassifications = $requireFrontendClassifications;
		$this->currentNamespace = '';
		$lines = [];
		foreach ($program->fields['statements'] ?? [] as $statement) {
			if ($statement instanceof JssNode) {
				$lines[] = $this->emitStatement($statement);
			}
		}
		return implode("\n", $lines) . "\n";
	}

	private function emitStatement(JssNode $statement): string
	{
		if ($statement->kind === 'namespace_decl') {
			$this->currentNamespace = (string) $statement->fields['name'];
			return 'namespace ' . $this->currentNamespace . ';';
		}
		if ($statement->kind === 'namespace_block') {
			$previousNamespace = $this->currentNamespace;
			$this->currentNamespace = (string) $statement->fields['name'];
			$lines = ['namespace ' . $this->currentNamespace . ';'];
			foreach (($statement->fields['body'] ?? []) as $child) {
				if ($child instanceof JssNode) {
					$lines[] = $this->emitStatement($child);
				}
			}
			$this->currentNamespace = $previousNamespace;
			return implode("\n", $lines);
		}
		if ($statement->kind === 'use_decl') {
			$name = (string) $statement->fields['name'];
			$alias = is_string($statement->fields['alias'] ?? null) ? (string) $statement->fields['alias'] : basename(str_replace('\\', '/', $name));
			$useKind = (string) ($statement->fields['kind'] ?? 'class');
			$this->useAliases[$alias] = $name;
			if ($useKind === 'const') {
				$this->constantNames[$alias] = true;
			} else {
				$this->classNames[$alias] = true;
			}
			$usePrefix = $useKind === 'function' ? 'function ' : ($useKind === 'const' ? 'const ' : '');
			return 'use ' . $usePrefix . $name . (is_string($statement->fields['alias'] ?? null) ? ' as ' . $alias : '') . ';';
		}
		if ($statement->kind === 'const_decl') {
			$name = (string) $statement->fields['name'];
			$this->constantNames[$name] = true;
			if ($this->currentNamespace !== '') {
				$this->namespaceConstantNames[$this->currentNamespace . '.' . $name] = true;
			}
			return 'const ' . $name . ' = ' . $this->emitExpression($statement->fields['value'] ?? null) . ';';
		}
		if ($statement->kind === 'class_decl') {
			$name = (string) $statement->fields['name'];
			$this->classNames[$name] = true;
			if ($this->currentNamespace !== '') {
				$this->classNames[$this->currentNamespace . '\\' . $name] = true;
			}
			foreach (($statement->fields['members'] ?? []) as $member) {
				if ($member instanceof JssNode && $member->kind === 'property_decl' && ($member->fields['static'] ?? false) === true) {
					$this->staticPropertyNames[$name . '.' . (string) $member->fields['name']] = true;
				}
			}
			$extends = is_string($statement->fields['extends'] ?? null) ? ' extends ' . (string) $statement->fields['extends'] : '';
			$lines = ['class ' . $name . $extends . ' {'];
			foreach (($statement->fields['members'] ?? []) as $member) {
				if ($member instanceof JssNode) {
					$lines[] = "\t" . str_replace("\n", "\n\t", $this->emitClassMember($member));
				}
			}
			$lines[] = '}';
			return implode("\n", $lines);
		}
		if ($statement->kind === 'function_decl') {
			$params = $this->emitParameters($statement->fields['params'] ?? []);
			$returnType = is_string($statement->fields['return_type'] ?? null) ? ': ' . (string) $statement->fields['return_type'] : '';
			$lines = ['function ' . (string) $statement->fields['name'] . '(' . implode(', ', $params) . ')' . $returnType . ' {'];
			$lines = array_merge($lines, $this->emitScopedBody($statement->fields['params'] ?? [], $statement->fields['body'] ?? [], false));
			$lines[] = '}';
			return implode("\n", $lines);
		}
		if ($statement->kind === 'return') {
			$value = $statement->fields['value'] ?? null;
			return $value instanceof JssNode ? 'return ' . $this->emitExpression($value) . ';' : 'return;';
		}
		if ($statement->kind === 'break') {
			return 'break;';
		}
		if ($statement->kind === 'continue') {
			return 'continue;';
		}
		if ($statement->kind === 'if') {
			$lines = ['if (' . $this->emitExpression($statement->fields['condition'] ?? null) . ') {'];
			foreach (($statement->fields['then'] ?? []) as $child) {
				if ($child instanceof JssNode) {
					$lines[] = $this->indentStatement($child);
				}
			}
			$elseStatements = $statement->fields['else'] ?? [];
			if (is_array($elseStatements) && $elseStatements !== []) {
				$lines[] = '} else {';
				foreach ($elseStatements as $child) {
					if ($child instanceof JssNode) {
						$lines[] = $this->indentStatement($child);
					}
				}
			}
			$lines[] = '}';
			return implode("\n", $lines);
		}
		if ($statement->kind === 'while') {
			$lines = ['while (' . $this->emitExpression($statement->fields['condition'] ?? null) . ') {'];
			foreach (($statement->fields['body'] ?? []) as $child) {
				if ($child instanceof JssNode) {
					$lines[] = $this->indentStatement($child);
				}
			}
			$lines[] = '}';
			return implode("\n", $lines);
		}
		if ($statement->kind === 'do_while') {
			$lines = ['do {'];
			foreach (($statement->fields['body'] ?? []) as $child) {
				if ($child instanceof JssNode) {
					$lines[] = $this->indentStatement($child);
				}
			}
			$lines[] = '} while (' . $this->emitExpression($statement->fields['condition'] ?? null) . ');';
			return implode("\n", $lines);
		}
		if ($statement->kind === 'switch') {
			$lines = ['switch (' . $this->emitExpression($statement->fields['expression'] ?? null) . ') {'];
			foreach (is_array($statement->fields['cases'] ?? null) ? $statement->fields['cases'] : [] as $case) {
				if (!is_array($case)) {
					continue;
				}
				$value = $case['value'] ?? null;
				$lines[] = $value instanceof JssNode ? "\tcase " . $this->emitExpression($value) . ':' : "\tdefault:";
				foreach (is_array($case['body'] ?? null) ? $case['body'] : [] as $child) {
					if ($child instanceof JssNode) {
						$lines[] = "\t" . $this->indentStatement($child);
					}
				}
			}
			$lines[] = '}';
			return implode("\n", $lines);
		}
		if ($statement->kind === 'for') {
			$previousLocalTypes = $this->localTypes;
			$previousLocalNames = $this->localNames;
			$lines = [
				'for ('
				. $this->emitForClause($statement->fields['init'] ?? null)
				. '; '
				. $this->emitExpression($statement->fields['condition'] ?? null)
				. '; '
				. $this->emitForClause($statement->fields['step'] ?? null)
				. ') {'
			];
			$lines = array_merge($lines, $this->emitStatementList($statement->fields['body'] ?? []));
			$this->localTypes = $previousLocalTypes;
			$this->localNames = $previousLocalNames;
			$lines[] = '}';
			return implode("\n", $lines);
		}
		if ($statement->kind === 'foreach_value') {
			$valueName = (string) $statement->fields['value_name'];
			$valueType = is_string($statement->fields['value_type'] ?? null) ? (string) $statement->fields['value_type'] : '';
			$value = '$' . $valueName . ($valueType !== '' ? ' ' . $valueType : '');
			$lines = ['foreach (' . $this->emitExpression($statement->fields['source'] ?? null) . ' as ' . $value . ') {'];
			$lines = array_merge($lines, $this->emitForeachBody($statement, null, $valueName, $valueType));
			$lines[] = '}';
			return implode("\n", $lines);
		}
		if ($statement->kind === 'foreach_key_value') {
			$key = '$' . (string) $statement->fields['key_name'];
			$valueName = (string) $statement->fields['value_name'];
			$valueType = is_string($statement->fields['value_type'] ?? null) ? (string) $statement->fields['value_type'] : '';
			$value = '$' . $valueName . ($valueType !== '' ? ' ' . $valueType : '');
			$lines = ['foreach (' . $this->emitExpression($statement->fields['source'] ?? null) . ' as ' . $key . ' => ' . $value . ') {'];
			$lines = array_merge($lines, $this->emitForeachBody($statement, (string) $statement->fields['key_name'], $valueName, $valueType));
			$lines[] = '}';
			return implode("\n", $lines);
		}
		if ($statement->kind === 'var_decl') {
			$name = (string) $statement->fields['name'];
			$type = is_string($statement->fields['type'] ?? null) ? (string) $statement->fields['type'] : null;
			$this->localNames[$name] = true;
			if ($type !== null && $type !== '') {
				$this->localTypes[$name] = $type;
			}
			$left = '$' . $name . ($type !== null && $type !== '' ? ' ' . $type : '');
			$initializer = $statement->fields['initializer'] ?? null;
			if ($initializer instanceof JssNode) {
				return $left . ' = ' . $this->emitExpression($initializer) . ';';
			}
			return $left . ';';
		}
		if ($statement->kind === 'assign') {
			$target = $statement->fields['target'] ?? null;
			if ($target instanceof JssNode) {
				return $this->emitExpression($target) . ' = ' . $this->emitExpression($statement->fields['value'] ?? null) . ';';
			}
			return '$' . (string) ($statement->fields['name'] ?? '') . ' = ' . $this->emitExpression($statement->fields['value'] ?? null) . ';';
		}
		if ($statement->kind === 'compound_assign') {
			return $this->emitExpression($statement->fields['target'] ?? null) . ' ' . (string) ($statement->fields['operator'] ?? '') . ' ' . $this->emitExpression($statement->fields['value'] ?? null) . ';';
		}
		if ($statement->kind === 'append') {
			return $this->emitExpression($statement->fields['target'] ?? null) . '[] = ' . $this->emitExpression($statement->fields['value'] ?? null) . ';';
		}
		if ($statement->kind === 'update') {
			$operator = (string) ($statement->fields['operator'] ?? '');
			$target = $this->emitExpression($statement->fields['target'] ?? null);
			return (($statement->fields['prefix'] ?? false) === true ? $operator . $target : $target . $operator) . ';';
		}
		if ($statement->kind === 'post_increment') {
			return '$' . (string) $statement->fields['name'] . '++;';
		}
		if ($statement->kind === 'expr_stmt') {
			$expression = $statement->fields['expression'] ?? null;
			if ($expression instanceof JssNode && $this->isPrintCall($expression)) {
				return 'echo ' . implode(', ', $this->emitArguments($expression)) . ';';
			}
			return $this->emitExpression($expression) . ';';
		}
		return '';
	}

	private function emitClassMember(JssNode $member): string
	{
		if ($member->kind === 'property_decl') {
			$line = 'public ' . (($member->fields['static'] ?? false) === true ? 'static ' : '') . (string) $member->fields['type'] . ' $' . (string) $member->fields['name'];
			if (($member->fields['default'] ?? null) instanceof JssNode) {
				$line .= ' = ' . $this->emitExpression($member->fields['default']);
			}
			return $line . ';';
		}
		if ($member->kind === 'class_const_decl') {
			return 'public const ' . (string) $member->fields['name'] . ' = ' . $this->emitExpression($member->fields['value'] ?? null) . ';';
		}
		if ($member->kind === 'constructor_decl') {
			return $this->emitMethodLike('__construct', $member->fields['params'] ?? [], null, $member->fields['body'] ?? []);
		}
		if ($member->kind === 'method_decl') {
			$returnType = is_string($member->fields['return_type'] ?? null) ? (string) $member->fields['return_type'] : null;
			return $this->emitMethodLike((string) $member->fields['name'], $member->fields['params'] ?? [], $returnType, $member->fields['body'] ?? [], ($member->fields['static'] ?? false) === true);
		}
		return '';
	}

	private function emitMethodLike(string $name, mixed $params, ?string $returnType, mixed $body, bool $isStatic = false): string
	{
		$signature = 'public ' . ($isStatic ? 'static ' : '') . 'function ' . $name . '(' . implode(', ', $this->emitParameters($params)) . ')';
		if ($returnType !== null && $returnType !== '') {
			$signature .= ': ' . $returnType;
		}
		$lines = [$signature . ' {'];
		$lines = array_merge($lines, $this->emitScopedBody($params, $body, true));
		$lines[] = '}';
		return implode("\n", $lines);
	}

	/** @return list<string> */
	private function emitScopedBody(mixed $params, mixed $body, bool $includeThis): array
	{
		$previousLocalTypes = $this->localTypes;
		$previousLocalNames = $this->localNames;
		$this->localTypes = [];
		$this->localNames = [];
		if ($includeThis) {
			$this->localNames['this'] = true;
		}
		$this->registerParameterLocals($params);

		$lines = [];
		foreach (is_array($body) ? $body : [] as $child) {
			if ($child instanceof JssNode) {
				$lines[] = $this->indentStatement($child);
			}
		}

		$this->localTypes = $previousLocalTypes;
		$this->localNames = $previousLocalNames;
		return $lines;
	}

	/** @return list<string> */
	private function emitForeachBody(JssNode $statement, ?string $keyName, string $valueName, string $valueType): array
	{
		$previousLocalTypes = $this->localTypes;
		$previousLocalNames = $this->localNames;
		if ($keyName !== null && $keyName !== '') {
			$this->localNames[$keyName] = true;
		}
		$this->localNames[$valueName] = true;
		if ($valueType !== '') {
			$this->localTypes[$valueName] = $valueType;
		}

		$lines = [];
		foreach (($statement->fields['body'] ?? []) as $child) {
			if ($child instanceof JssNode) {
				$lines[] = $this->indentStatement($child);
			}
		}

		$this->localTypes = $previousLocalTypes;
		$this->localNames = $previousLocalNames;
		return $lines;
	}

	private function registerParameterLocals(mixed $params): void
	{
		foreach (is_array($params) ? $params : [] as $param) {
			if (!is_array($param)) {
				continue;
			}
			$name = (string) ($param['name'] ?? '');
			if ($name === '') {
				continue;
			}
			$this->localNames[$name] = true;
			$type = (string) ($param['type'] ?? '');
			if ($type !== '') {
				$this->localTypes[$name] = $type;
			}
		}
	}

	private function indentStatement(JssNode $statement): string
	{
		return "\t" . str_replace("\n", "\n\t", $this->emitStatement($statement));
	}

	/** @return list<string> */
	private function emitStatementList(mixed $statements): array
	{
		$lines = [];
		foreach (is_array($statements) ? $statements : [] as $child) {
			if ($child instanceof JssNode) {
				$lines[] = $this->indentStatement($child);
			}
		}
		return $lines;
	}

	/** @return list<string> */
	private function emitParameters(mixed $params): array
	{
		$emitted = [];
		foreach (is_array($params) ? $params : [] as $param) {
			if (!is_array($param)) {
				continue;
			}
			$paramText = (string) ($param['type'] ?? '') . ' $' . (string) ($param['name'] ?? '');
			if (($param['default'] ?? null) instanceof JssNode) {
				$paramText .= ' = ' . $this->emitExpression($param['default']);
			}
			$emitted[] = $paramText;
		}
		return $emitted;
	}

	private function emitForClause(mixed $statement): string
	{
		if (!$statement instanceof JssNode || $statement->kind === 'empty') {
			return '';
		}
		return rtrim($this->emitStatement($statement), ';');
	}

	private function emitExpression(mixed $expression): string
	{
		if (!$expression instanceof JssNode) {
			return '';
		}
		return match ($expression->kind) {
			'number' => (string) $expression->fields['value'],
			'string' => (string) $expression->fields['value'],
			'template' => $this->emitTemplate($expression),
			'boolean' => (string) $expression->fields['value'],
			'null' => 'null',
			'identifier' => $this->emitIdentifier($expression),
			'array_literal' => '[' . implode(', ', $this->emitArrayItems($expression)) . ']',
			'object_literal' => '[' . implode(', ', $this->emitObjectPairs($expression)) . ']',
			'new' => 'new ' . (string) $expression->fields['class_name'] . '(' . implode(', ', $this->emitNewArguments($expression)) . ')',
			'member' => $this->emitMember($expression),
			'index' => $this->emitExpression($expression->fields['object'] ?? null) . '[' . $this->emitExpression($expression->fields['index'] ?? null) . ']',
			'call' => $this->emitCall($expression),
			'unary' => $this->emitUnary($expression),
			'binary' => $this->emitBinary($expression),
			'ternary' => $this->emitTernary($expression),
			default => '',
		};
	}

	private function emitTernary(JssNode $ternary): string
	{
		return '('
			. $this->emitExpression($ternary->fields['condition'] ?? null)
			. ' ? '
			. $this->emitExpression($ternary->fields['when_true'] ?? null)
			. ' : '
			. $this->emitExpression($ternary->fields['when_false'] ?? null)
			. ')';
	}

	private function emitIdentifier(JssNode $identifier): string
	{
		$name = (string) $identifier->fields['name'];
		$classification = $this->identifierClassifications[$name] ?? null;
		if (is_array($classification) && (string) ($classification['kind'] ?? '') === 'constant') {
			return $name;
		}
		if (is_array($classification) && (string) ($classification['kind'] ?? '') === 'builtin_global') {
			return '$' . $name;
		}
		if ($this->requireFrontendClassifications && !isset($this->localNames[$name])) {
			if (!is_array($classification)) {
				$this->throwMissingIdentifierClassification($name, $identifier);
			}
			if ((string) ($classification['kind'] ?? '') === 'unresolved_identifier') {
				$this->throwUnresolvedIdentifierClassification($name, $classification);
			}
		}
		if (isset($this->constantNames[$name])) {
			return $name;
		}
		return '$' . $name;
	}

	private function emitMember(JssNode $member): string
	{
		$classified = $this->emitClassifiedMember($member, false);
		if ($classified !== null) {
			return $classified;
		}

		$chain = $this->flattenMemberChain($member);
		if (count($chain) >= 2) {
			if (count($chain) === 2 && isset($this->namespaceConstantNames[$chain[0] . '.' . $chain[1]])) {
				return '\\' . $chain[0] . '\\' . $chain[1];
			}
			if (isset($this->useAliases[$chain[0]])) {
				if (count($chain) >= 3) {
					$classPath = $chain[0] . '\\' . $chain[1];
					$memberPath = implode('->', array_slice($chain, 2));
				} else {
					$classPath = $chain[0];
					$memberPath = implode('->', array_slice($chain, 1));
				}
				return $classPath . '::' . $memberPath;
			}
			for ($i = count($chain) - 1; $i >= 1; $i--) {
				$classPath = implode('\\', array_slice($chain, 0, $i));
				if (isset($this->classNames[$classPath])) {
					$memberParts = array_slice($chain, $i);
					$memberPath = implode('->', array_map(static fn (string $part): string => $part, $memberParts));
					if ($memberPath !== '') {
						if (count($memberParts) === 1 && isset($this->staticPropertyNames[$classPath . '.' . $memberParts[0]])) {
							return ($i > 1 ? '\\' : '') . $classPath . '::$' . $memberParts[0];
						}
						return ($i > 1 ? '\\' : '') . $classPath . '::' . $memberPath;
					}
				}
			}
		}
		$object = $member->fields['object'] ?? null;
		if ($object instanceof JssNode && $object->kind === 'identifier') {
			$name = (string) $object->fields['name'];
			if (isset($this->classNames[$name])) {
				if (isset($this->staticPropertyNames[$name . '.' . (string) $member->fields['member']])) {
					return $name . '::$' . (string) $member->fields['member'];
				}
				return $name . '::' . (string) $member->fields['member'];
			}
		}
		return $this->emitExpression($object) . '->' . (string) $member->fields['member'];
	}

	private function emitReservedHelperCallTarget(JssNode $member): ?string
	{
		$chain = $this->flattenMemberChain($member);
		if (count($chain) < 2) {
			return null;
		}
		return $this->callSurface->resolveNormalizedCallTarget($chain);
	}

	/** @return list<string> */
	private function flattenMemberChain(JssNode $member): array
	{
		$parts = [(string) $member->fields['member']];
		$object = $member->fields['object'] ?? null;
		while ($object instanceof JssNode && $object->kind === 'member') {
			array_unshift($parts, (string) $object->fields['member']);
			$object = $object->fields['object'] ?? null;
		}
		if ($object instanceof JssNode && $object->kind === 'identifier') {
			array_unshift($parts, (string) $object->fields['name']);
		}
		return $parts;
	}

	/** @return list<string> */
	private function emitNewArguments(JssNode $new): array
	{
		$args = [];
		foreach (($new->fields['args'] ?? []) as $arg) {
			$args[] = $this->emitExpression($arg);
		}
		return $args;
	}

	/** @return list<string> */
	private function emitArrayItems(JssNode $array): array
	{
		$items = [];
		foreach (($array->fields['items'] ?? []) as $item) {
			$items[] = $this->emitExpression($item);
		}
		return $items;
	}

	/** @return list<string> */
	private function emitObjectPairs(JssNode $object): array
	{
		$pairs = [];
		foreach (($object->fields['pairs'] ?? []) as $pair) {
			if (!is_array($pair)) {
				continue;
			}
			$pairs[] = (string) ($pair['key'] ?? '') . ' => ' . $this->emitExpression($pair['value'] ?? null);
		}
		return $pairs;
	}

	private function emitCall(JssNode $call): string
	{
		if ($this->isPrintCall($call)) {
			return 'print(' . implode(', ', $this->emitArguments($call)) . ')';
		}
		$callee = $call->fields['callee'] ?? null;
		if ($callee instanceof JssNode && $callee->kind === 'identifier') {
			$calleeText = $this->emitIdentifierCallee($callee);
		} elseif ($callee instanceof JssNode && $callee->kind === 'member') {
			$calleeText = $this->emitClassifiedMember($callee, true) ?? $this->emitReservedHelperCallTarget($callee) ?? $this->emitExpression($callee);
		} else {
			$calleeText = $this->emitExpression($callee);
		}
		return $calleeText . '(' . implode(', ', $this->emitArguments($call)) . ')';
	}

	private function emitIdentifierCallee(JssNode $callee): string
	{
		$name = (string) $callee->fields['name'];
		if (!$this->requireFrontendClassifications) {
			return $name;
		}
		$classification = $this->identifierClassifications[$name] ?? null;
		if (!is_array($classification)) {
			$this->throwMissingIdentifierClassification($name, $callee);
		}
		if (in_array((string) ($classification['kind'] ?? ''), ['function', 'builtin_function'], true)) {
			return $name;
		}
		if ((string) ($classification['kind'] ?? '') === 'unresolved_identifier') {
			$this->throwUnresolvedIdentifierClassification($name, $classification);
		}
		throw new \RuntimeException($this->withClassificationLocation('JSS call target `' . $name . '` was classified as `' . (string) ($classification['kind'] ?? 'unknown') . '`, not a function.', $classification));
	}

	private function emitClassifiedMember(JssNode $member, bool $isCall): ?string
	{
		$chain = $this->flattenMemberChain($member);
		if ($chain === []) {
			return null;
		}
		$classification = $this->memberClassifications[$this->classificationNormalizer->memberKey($chain, $isCall)] ?? null;
		if (!is_array($classification)) {
			if ($this->requireFrontendClassifications) {
				$this->throwMissingMemberClassification($chain, $member);
			}
			return null;
		}
		$kind = (string) ($classification['kind'] ?? '');
		if ($this->requireFrontendClassifications && $this->hasDiagnostics($classification)) {
			$this->throwMemberClassificationDiagnostic($chain, $classification);
		}
		if ($kind === 'instance_member') {
			$object = $member->fields['object'] ?? null;
			return $object instanceof JssNode ? $this->emitExpression($object) . '->' . (string) $member->fields['member'] : null;
		}
		if (in_array($kind, ['static_property', 'static_method', 'class_constant', 'namespaced_constant', 'namespaced_function'], true) || ($isCall && in_array($kind, ['function', 'builtin_function'], true))) {
			$target = (string) ($classification['target'] ?? '');
			return $target !== '' ? $target : null;
		}
		return null;
	}

	/** @param array<string,mixed> $classification */
	private function hasDiagnostics(array $classification): bool
	{
		$diagnostics = $classification['diagnostics'] ?? [];
		return is_array($diagnostics) && $diagnostics !== [];
	}

	/** @param list<string> $chain @param array<string,mixed> $classification */
	private function throwMemberClassificationDiagnostic(array $chain, array $classification): void
	{
		$message = 'JSS member access `' . implode('.', $chain) . '` could not be classified by STAN.';
		$diagnostics = $classification['diagnostics'] ?? [];
		if (is_array($diagnostics) && isset($diagnostics[0]) && is_array($diagnostics[0]) && is_string($diagnostics[0]['message'] ?? null)) {
			$message .= ' ' . $diagnostics[0]['message'];
		}
		throw new \RuntimeException($this->withClassificationLocation($message, $classification));
	}

	/** @param list<string> $chain */
	private function throwMissingMemberClassification(array $chain, JssNode $member): void
	{
		throw new \RuntimeException($this->withNodeLocation('JSS member access `' . implode('.', $chain) . '` has no STAN classification.', $member));
	}

	private function throwMissingIdentifierClassification(string $name, JssNode $identifier): void
	{
		throw new \RuntimeException($this->withNodeLocation('JSS identifier `' . $name . '` has no STAN classification.', $identifier));
	}

	/** @param array<string,mixed> $classification */
	private function throwUnresolvedIdentifierClassification(string $name, array $classification): void
	{
		$diagnostics = $classification['diagnostics'] ?? [];
		if (is_array($diagnostics) && isset($diagnostics[0]) && is_array($diagnostics[0]) && is_string($diagnostics[0]['message'] ?? null)) {
			throw new \RuntimeException($this->withClassificationLocation($diagnostics[0]['message'], $classification));
		}
		throw new \RuntimeException($this->withClassificationLocation('JSS identifier `' . $name . '` could not be resolved by STAN.', $classification));
	}

	/** @param list<string> $chain */
	/** @return list<string> */
	private function emitArguments(JssNode $call): array
	{
		$args = [];
		foreach (($call->fields['args'] ?? []) as $arg) {
			$args[] = $this->emitExpression($arg);
		}
		return $args;
	}

	private function emitBinary(JssNode $binary): string
	{
		$left = $binary->fields['left'] ?? null;
		$right = $binary->fields['right'] ?? null;
		$operator = (string) ($binary->fields['operator'] ?? '');
		if ($operator === '+') {
			$classification = $this->binaryPlusClassifications[$this->expressionKey($binary)] ?? null;
			if (is_array($classification) && in_array((string) ($classification['kind'] ?? ''), ['string_concat', 'numeric_add'], true)) {
				$operator = (string) ($classification['operator'] ?? '+');
			} elseif (is_array($classification) && (string) ($classification['kind'] ?? '') === 'dynamic_plus') {
				return 'js_plus(' . $this->emitExpression($left) . ', ' . $this->emitExpression($right) . ')';
			} else {
				if ($this->requireFrontendClassifications) {
					$this->throwMissingBinaryPlusClassification($binary, is_array($classification) ? $classification : null);
				}
				if ($this->expressionIsDynamicCarrier($left) || $this->expressionIsDynamicCarrier($right)) {
					return 'js_plus(' . $this->emitExpression($left) . ', ' . $this->emitExpression($right) . ')';
				}
				$operator = $this->expressionIsKnownString($left) || $this->expressionIsKnownString($right) ? '.' : '+';
			}
		}
		if ($operator === '??') {
			return $this->emitExpression($left) . ' ?? ' . $this->emitExpression($right);
		}
		return $this->emitExpression($left) . ' ' . $operator . ' ' . $this->emitExpression($right);
	}

	/** @param array<string,mixed>|null $classification */
	private function throwMissingBinaryPlusClassification(JssNode $binary, ?array $classification): void
	{
		$message = 'JSS `+` could not be classified by STAN for expression key `' . $this->expressionKey($binary) . '`.';
		if (is_array($classification)) {
			$diagnostics = $classification['diagnostics'] ?? [];
			if (is_array($diagnostics) && isset($diagnostics[0]) && is_array($diagnostics[0]) && is_string($diagnostics[0]['message'] ?? null)) {
				$message .= ' ' . $diagnostics[0]['message'];
			}
			$message = $this->withClassificationLocation($message, $classification);
		} else {
			$message = $this->withNodeLocation($message, $binary);
		}
		throw new \RuntimeException($message);
	}

	/** @param array<string,mixed> $classification */
	private function withClassificationLocation(string $message, array $classification): string
	{
		$line = (int) ($classification['line'] ?? 0);
		$column = (int) ($classification['column'] ?? 0);
		if ($line <= 0 || $column <= 0) {
			$range = $classification['range'] ?? null;
			if (is_array($range)) {
				$line = (int) ($range['line'] ?? 0);
				$column = (int) ($range['column'] ?? 0);
			}
		}
		if ($line <= 0 || $column <= 0) {
			return $message;
		}
		$path = (string) ($classification['path'] ?? '');
		$location = $path !== '' ? $path . ':' . $line . ':' . $column : $line . ':' . $column;
		return $message . ' at ' . $location . '.';
	}

	private function withNodeLocation(string $message, JssNode $node): string
	{
		$range = $node->fields['range'] ?? null;
		if (!is_array($range)) {
			return $message;
		}
		$line = (int) ($range['line'] ?? 0);
		$column = (int) ($range['column'] ?? 0);
		if ($line <= 0 || $column <= 0) {
			return $message;
		}
		return $message . ' at ' . $line . ':' . $column . '.';
	}

	private function expressionKey(JssNode $expression): string
	{
		return match ($expression->kind) {
			'identifier' => 'id:' . (string) $expression->fields['name'],
			'string' => 'str:' . (string) $expression->fields['value'],
			'number' => 'num:' . (string) $expression->fields['value'],
			'boolean' => 'bool:' . (string) $expression->fields['value'],
			'binary' => 'binary:'
				. (string) ($expression->fields['operator'] ?? '')
				. '(' . $this->expressionKey($expression->fields['left'] ?? new JssNode('empty')) . ','
				. $this->expressionKey($expression->fields['right'] ?? new JssNode('empty')) . ')',
			default => $expression->kind,
		};
	}

	private function emitUnary(JssNode $unary): string
	{
		return (string) ($unary->fields['operator'] ?? '') . $this->emitExpression($unary->fields['expression'] ?? null);
	}

	private function emitTemplate(JssNode $template): string
	{
		$value = (string) ($template->fields['value'] ?? '');
		$parts = [];
		$offset = 0;
		if (preg_match_all('/\$\{([A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*)*)\}/', $value, $matches, PREG_OFFSET_CAPTURE)) {
			foreach ($matches[0] as $index => $match) {
				$start = (int) $match[1];
				$text = substr($value, $offset, $start - $offset);
				if ($text !== '') {
					$parts[] = $this->quoteTemplateText($text);
				}
				$expression = (string) $matches[1][$index][0];
				$parts[] = $this->emitTemplateInterpolation($expression);
				$offset = $start + strlen((string) $match[0]);
			}
		}
		$tail = substr($value, $offset);
		if ($tail !== '') {
			$parts[] = $this->quoteTemplateText($tail);
		}
		if ($parts === []) {
			return '""';
		}
		return implode(' . ', $parts);
	}

	private function emitTemplateInterpolation(string $expression): string
	{
		$chain = array_values(array_filter(explode('.', $expression), static fn (string $part): bool => $part !== ''));
		if ($chain === []) {
			return '""';
		}
		if (count($chain) === 1) {
			return $this->emitIdentifier(new JssNode('identifier', ['name' => $chain[0]]));
		}
		return $this->emitMember($this->templateChainToMemberNode($chain));
	}

	/** @param list<string> $chain */
	private function templateChainToMemberNode(array $chain): JssNode
	{
		$node = new JssNode('identifier', ['name' => array_shift($chain)]);
		foreach ($chain as $member) {
			$node = new JssNode('member', ['object' => $node, 'member' => $member]);
		}
		return $node;
	}

	private function quoteTemplateText(string $text): string
	{
		$escaped = str_replace(
			["\r", "\n", "\t", '"'],
			['\\r', '\\n', '\\t', '\\"'],
			$text
		);
		return '"' . $escaped . '"';
	}

	private function expressionIsKnownString(mixed $expression): bool
	{
		if (!$expression instanceof JssNode) {
			return false;
		}
		if ($expression->kind === 'string') {
			return true;
		}
		if ($expression->kind === 'identifier') {
			$name = (string) $expression->fields['name'];
			return strtolower($this->localTypes[$name] ?? '') === 'string';
		}
		return false;
	}

	private function expressionIsDynamicCarrier(mixed $expression): bool
	{
		if (!$expression instanceof JssNode || $expression->kind !== 'identifier') {
			return false;
		}
		$name = (string) $expression->fields['name'];
		return in_array(strtolower($this->localTypes[$name] ?? ''), ['mixed', 'dynamic'], true);
	}

	private function isPrintCall(JssNode $expression): bool
	{
		if ($expression->kind !== 'call') {
			return false;
		}
		$callee = $expression->fields['callee'] ?? null;
		return $callee instanceof JssNode
			&& $callee->kind === 'identifier'
			&& in_array((string) $callee->fields['name'], ['print', 'console_log'], true);
	}
}
