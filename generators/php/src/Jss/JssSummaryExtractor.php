<?php
declare(strict_types=1);

namespace Scpp\S2S\Jss;

final class JssSummaryExtractor
{
	private JssFrontendRequestFactory $requestFactory;
	private JssFileSummaryBuilder $summaryBuilder;
	private JssCallSurface $callSurface;
	/** @var array<string,string> */
	private array $functionReturnTypes = [];

	public function __construct(?JssFrontendRequestFactory $requestFactory = null, ?JssFileSummaryBuilder $summaryBuilder = null, ?JssCallSurface $callSurface = null)
	{
		$this->requestFactory = $requestFactory ?? new JssFrontendRequestFactory();
		$this->summaryBuilder = $summaryBuilder ?? new JssFileSummaryBuilder();
		$this->callSurface = $callSurface ?? new JssCallSurface();
	}

	/** @return array<string,mixed> */
	public function summarize(JssNode $program, string $path): array
	{
		$this->requestFactory->reset();
		$this->requestFactory->setPath($path);
		$this->functionReturnTypes = $this->collectFunctionReturnTypes($program->fields['statements'] ?? []);
		$rootConstants = [];
		$rootFunctions = [];
		$rootClasses = [];
		$rootUses = [];
		$namespaces = [];
		$requests = [];
		$visibleTypes = [];
		$currentNamespace = null;

		$this->collectStatements(
			$program->fields['statements'] ?? [],
			$currentNamespace,
			$rootUses,
			$rootConstants,
			$rootFunctions,
			$rootClasses,
			$namespaces,
			$requests,
			$visibleTypes,
		);

		return $this->summaryBuilder->build(
			$path,
			$rootUses,
			$rootConstants,
			$rootFunctions,
			$rootClasses,
			$namespaces,
			$requests,
		);
	}

	/**
	 * @param mixed $statements
	 * @param list<array<string,mixed>> $rootUses
	 * @param list<array<string,mixed>> $rootConstants
	 * @param list<array<string,mixed>> $rootFunctions
	 * @param list<array<string,mixed>> $rootClasses
	 * @param array<string,array<string,mixed>> $namespaces
	 * @param list<array<string,mixed>> $requests
	 * @param array<string,string> $visibleTypes
	 */
	private function collectStatements(
		mixed $statements,
		?string &$currentNamespace,
		array &$rootUses,
		array &$rootConstants,
		array &$rootFunctions,
		array &$rootClasses,
		array &$namespaces,
		array &$requests,
		array &$visibleTypes,
	): void
	{
		foreach (is_array($statements) ? $statements : [] as $statement) {
			if (!$statement instanceof JssNode) {
				continue;
			}
			$this->requestFactory->setNamespace($currentNamespace);
			if ($statement->kind === 'namespace_decl') {
				$currentNamespace = (string) $statement->fields['name'];
				$this->requestFactory->setNamespace($currentNamespace);
				$this->summaryBuilder->ensureNamespace($namespaces, $currentNamespace);
				continue;
			}
			if ($statement->kind === 'namespace_block') {
				$previousNamespace = $currentNamespace;
				$currentNamespace = (string) $statement->fields['name'];
				$this->requestFactory->setNamespace($currentNamespace);
				$this->summaryBuilder->ensureNamespace($namespaces, $currentNamespace);
				$this->collectStatements(
					$statement->fields['body'] ?? [],
					$currentNamespace,
					$rootUses,
					$rootConstants,
					$rootFunctions,
					$rootClasses,
					$namespaces,
					$requests,
					$visibleTypes,
				);
				$currentNamespace = $previousNamespace;
				continue;
			}
			if ($statement->kind === 'use_decl') {
				$use = $this->summaryBuilder->useDeclaration(
					(string) ($statement->fields['kind'] ?? 'class'),
					(string) $statement->fields['name'],
					$statement->fields['alias'] ?? null,
					$this->nodeLine($statement),
				);
				if ($currentNamespace === null) {
					$rootUses[] = $use;
				} else {
					$this->summaryBuilder->ensureNamespace($namespaces, $currentNamespace);
					$namespaces[$currentNamespace]['uses'][] = $use;
				}
				continue;
			}
			if ($statement->kind === 'const_decl') {
				$constantName = (string) $statement->fields['name'];
				$constant = $this->summaryBuilder->constantDeclaration($constantName, $currentNamespace, $this->nodeLine($statement));
				if ($currentNamespace === null) {
					$rootConstants[] = $constant;
				} else {
					$this->summaryBuilder->ensureNamespace($namespaces, $currentNamespace);
					$namespaces[$currentNamespace]['constants'][] = $constant;
				}
				$this->collectExpressionRequests($statement->fields['value'] ?? null, $requests, $visibleTypes);
				$typeHint = $this->expressionTypeHint($statement->fields['value'] ?? null, $visibleTypes);
				if ($typeHint !== null) {
					$visibleTypes[$constantName] = $typeHint;
				}
				continue;
			}
			if ($statement->kind === 'function_decl') {
				$function = $this->summarizeFunctionLike($statement, $currentNamespace);
				if ($currentNamespace === null) {
					$rootFunctions[] = $function;
				} else {
					$this->summaryBuilder->ensureNamespace($namespaces, $currentNamespace);
					$namespaces[$currentNamespace]['functions'][] = $function;
				}
				$this->collectFunctionLikeRequests($statement->fields['params'] ?? [], $statement->fields['body'] ?? [], $requests, $visibleTypes);
				continue;
			}
			if ($statement->kind === 'class_decl' || $statement->kind === 'struct_decl' || $statement->kind === 'union_decl') {
				$classSummary = $this->summarizeClass($statement, $currentNamespace);
				if ($currentNamespace === null) {
					$rootClasses[] = $classSummary;
				} else {
					$this->summaryBuilder->ensureNamespace($namespaces, $currentNamespace);
					$namespaces[$currentNamespace]['classes'][] = $classSummary;
				}
				foreach (($statement->fields['members'] ?? []) as $member) {
					if ($member instanceof JssNode && in_array($member->kind, ['method_decl', 'constructor_decl'], true)) {
						$this->collectFunctionLikeRequests($member->fields['params'] ?? [], $member->fields['body'] ?? [], $requests, $visibleTypes);
					}
					if ($member instanceof JssNode && $member->kind === 'property_decl') {
						$this->collectExpressionRequests($member->fields['default'] ?? null, $requests, $visibleTypes);
					}
					if ($member instanceof JssNode && $member->kind === 'class_const_decl') {
						$this->collectExpressionRequests($member->fields['value'] ?? null, $requests, $visibleTypes);
					}
				}
				continue;
			}
			$this->collectStatementRequests($statement, $requests, $visibleTypes);
		}
	}

	/** @return array<string,mixed> */
	private function summarizeFunctionLike(JssNode $node, ?string $namespace): array
	{
		$body = is_array($node->fields['body'] ?? null) ? $node->fields['body'] : [];
		$summary = $this->summaryBuilder->functionDeclaration(
			(string) $node->fields['name'],
			$namespace,
			$this->summarizeParams($node->fields['params'] ?? []),
			$node->fields['return_type'] ?? null,
			$this->collectTypedLocals($body),
			count($body),
			$this->nodeLine($node),
		);
		$summary['return_values'] = $this->summarizeReturnValues($body);
		$summary['returns_on_all_paths'] = $this->statementsReturnOnAllPaths($body);
		$summary['local_type_assignments'] = $this->summarizeLocalTypeAssignments($body);
		return $summary;
	}

	/** @return array<string,mixed> */
	private function summarizeClass(JssNode $class, ?string $namespace = null): array
	{
		$methods = [];
		$properties = [];
		$constants = [];
		foreach (($class->fields['members'] ?? []) as $member) {
			if (!$member instanceof JssNode) {
				continue;
			}
			if ($member->kind === 'property_decl') {
				$properties[] = $this->summaryBuilder->propertyDeclaration(
					(string) $member->fields['name'],
					(string) $member->fields['type'],
					$this->nodeLine($member),
					($member->fields['static'] ?? false) === true,
					($member->fields['default'] ?? null) instanceof JssNode,
				);
				continue;
			}
			if ($member->kind === 'method_decl') {
				$methods[] = $this->summarizeMethod($member);
				continue;
			}
			if ($member->kind === 'constructor_decl') {
				$methods[] = $this->summarizeMethod(new JssNode('method_decl', [
					'name' => '__construct',
					'params' => $member->fields['params'] ?? [],
					'return_type' => null,
					'static' => false,
					'body' => $member->fields['body'] ?? [],
				]));
				continue;
			}
			if ($member->kind === 'class_const_decl') {
				$constants[] = $this->summaryBuilder->classConstantDeclaration(
					(string) $member->fields['name'],
					$this->nodeLine($member),
				);
			}
		}

		return $this->summaryBuilder->classDeclaration(
			(string) $class->fields['name'],
			$namespace,
			$this->nodeLine($class),
			$class->fields['extends'] ?? null,
			$methods,
			$properties,
			$constants,
			$class->kind === 'struct_decl',
			$class->kind === 'union_decl',
		);
	}

	/** @return array<string,mixed> */
	private function summarizeMethod(JssNode $method): array
	{
		$body = is_array($method->fields['body'] ?? null) ? $method->fields['body'] : [];
		$summary = $this->summaryBuilder->functionDeclaration(
			(string) $method->fields['name'],
			null,
			$this->summarizeParams($method->fields['params'] ?? []),
			$method->fields['return_type'] ?? null,
			$this->collectTypedLocals($body),
			count($body),
			$this->nodeLine($method),
			($method->fields['static'] ?? false) === true,
		);
		$summary['return_values'] = $this->summarizeReturnValues($body);
		$summary['returns_on_all_paths'] = $this->statementsReturnOnAllPaths($body);
		$summary['local_type_assignments'] = $this->summarizeLocalTypeAssignments($body);
		return $summary;
	}

	/** @return list<array{name:string,type:string,default:mixed}> */
	private function summarizeParams(mixed $params): array
	{
		$result = [];
		foreach (is_array($params) ? $params : [] as $param) {
			if (!is_array($param)) {
				continue;
			}
			$result[] = $this->summaryBuilder->parameter(
				(string) ($param['name'] ?? ''),
				(string) ($param['type'] ?? ''),
				$param['default'] ?? null,
			);
		}
		return $result;
	}

	/** @return list<array{name:string,type:string,line:int}> */
	private function collectTypedLocals(mixed $statements): array
	{
		$locals = [];
		foreach (is_array($statements) ? $statements : [] as $statement) {
			if (!$statement instanceof JssNode) {
				continue;
			}
			if ($statement->kind === 'var_decl' && is_string($statement->fields['type'] ?? null)) {
				$locals[] = $this->summaryBuilder->typedLocal(
					(string) $statement->fields['name'],
					(string) $statement->fields['type'],
					$this->nodeLine($statement),
				);
			}
			foreach (['body', 'then', 'else'] as $field) {
				if (is_array($statement->fields[$field] ?? null)) {
					$locals = array_merge($locals, $this->collectTypedLocals($statement->fields[$field]));
				}
			}
		}
		return $locals;
	}

	/** @param list<JssNode> $statements @return list<array<string,mixed>> */
	private function summarizeReturnValues(array $statements): array
	{
		$values = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof JssNode || $statement->kind !== 'return') {
				continue;
			}
			$value = $statement->fields['value'] ?? null;
			$values[] = [
				'line' => $this->nodeLine($statement),
				'descriptor' => $this->expressionDescriptor($value, []),
				'direct_call_name' => $this->directCallName($value),
			];
		}
		return $values;
	}

	/** @param list<JssNode> $statements @return list<JssNode> */
	private function flattenStatements(array $statements): array
	{
		$flat = [];
		foreach ($statements as $statement) {
			if (!$statement instanceof JssNode) {
				continue;
			}
			$flat[] = $statement;
			foreach (['body', 'then', 'else'] as $field) {
				if (is_array($statement->fields[$field] ?? null)) {
					$flat = array_merge($flat, $this->flattenStatements($statement->fields[$field]));
				}
			}
			foreach (is_array($statement->fields['cases'] ?? null) ? $statement->fields['cases'] : [] as $case) {
				if (is_array($case) && is_array($case['body'] ?? null)) {
					$flat = array_merge($flat, $this->flattenStatements($case['body']));
				}
			}
		}
		return $flat;
	}

	/** @param list<JssNode> $statements */
	private function statementsReturnOnAllPaths(array $statements): bool
	{
		foreach ($statements as $statement) {
			if (!$statement instanceof JssNode) {
				continue;
			}
			if ($statement->kind === 'return') {
				return true;
			}
			if ($statement->kind === 'if' && $this->ifStatementReturnsOnAllPaths($statement)) {
				return true;
			}
		}
		return false;
	}

	private function ifStatementReturnsOnAllPaths(JssNode $statement): bool
	{
		$thenStatements = $statement->fields['then'] ?? null;
		$elseStatements = $statement->fields['else'] ?? null;
		return is_array($thenStatements)
			&& is_array($elseStatements)
			&& $elseStatements !== []
			&& $this->statementsReturnOnAllPaths($thenStatements)
			&& $this->statementsReturnOnAllPaths($elseStatements);
	}

	/** @param list<JssNode> $statements @return list<array<string,mixed>> */
	private function summarizeLocalTypeAssignments(array $statements): array
	{
		$assignments = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof JssNode || $statement->kind !== 'var_decl') {
				continue;
			}
			$name = (string) ($statement->fields['name'] ?? '');
			$initializer = $statement->fields['initializer'] ?? null;
			if ($name === '' || !$initializer instanceof JssNode) {
				continue;
			}
			$type = $this->expressionTypeHint($initializer, []);
			if ($type === null || $type === '') {
				continue;
			}
			$assignments[] = [
				'line' => $this->nodeLine($statement),
				'name' => $name,
				'type' => $type,
				'statement_kind' => 'var_decl',
			];
		}
		return $assignments;
	}

	/** @param list<array<string,mixed>> $requests */
	private function collectStatementListRequests(mixed $statements, array &$requests, array $localTypes = []): void
	{
		foreach (is_array($statements) ? $statements : [] as $statement) {
			$this->collectStatementRequests($statement, $requests, $localTypes);
		}
	}

	/** @param list<array<string,mixed>> $requests @param array<string,string> $outerTypes */
	private function collectFunctionLikeRequests(mixed $params, mixed $body, array &$requests, array $outerTypes = []): void
	{
		$localTypes = $outerTypes;
		foreach (is_array($params) ? $params : [] as $param) {
			if (!is_array($param)) {
				continue;
			}
			$name = (string) ($param['name'] ?? '');
			$type = (string) ($param['type'] ?? '');
			if ($name !== '' && $type !== '') {
				$localTypes[$name] = $type;
			}
			$this->collectExpressionRequests($param['default'] ?? null, $requests, $localTypes);
		}
		$this->collectStatementListRequests($body, $requests, $localTypes);
	}

	/** @param list<array<string,mixed>> $requests */
	private function collectStatementRequests(mixed $statement, array &$requests, array &$localTypes = []): void
	{
		if (!$statement instanceof JssNode) {
			return;
		}
		if ($statement->kind === 'var_decl') {
			$this->collectExpressionRequests($statement->fields['initializer'] ?? null, $requests, $localTypes);
			if (is_string($statement->fields['type'] ?? null)) {
				$localTypes[(string) $statement->fields['name']] = (string) $statement->fields['type'];
			} else {
				$typeHint = $this->expressionTypeHint($statement->fields['initializer'] ?? null, $localTypes);
				if ($typeHint !== null) {
					$localTypes[(string) $statement->fields['name']] = $typeHint;
				}
			}
			return;
		}
		if ($statement->kind === 'assign') {
			$this->collectExpressionRequests($statement->fields['target'] ?? null, $requests, $localTypes);
			$this->collectExpressionRequests($statement->fields['value'] ?? null, $requests, $localTypes);
			$target = $statement->fields['target'] ?? null;
			if ($target instanceof JssNode && $target->kind === 'identifier') {
				$typeHint = $this->expressionTypeHint($statement->fields['value'] ?? null, $localTypes);
				if ($typeHint !== null) {
					$localTypes[(string) $target->fields['name']] = $typeHint;
				}
			}
			return;
		}
		if ($statement->kind === 'compound_assign') {
			$this->collectExpressionRequests($statement->fields['target'] ?? null, $requests, $localTypes);
			$this->collectExpressionRequests($statement->fields['value'] ?? null, $requests, $localTypes);
			return;
		}
		if ($statement->kind === 'append') {
			$this->collectExpressionRequests($statement->fields['target'] ?? null, $requests, $localTypes);
			$this->collectExpressionRequests($statement->fields['value'] ?? null, $requests, $localTypes);
			return;
		}
		if ($statement->kind === 'delete') {
			$this->collectExpressionRequests($statement->fields['target'] ?? null, $requests, $localTypes);
			return;
		}
		if ($statement->kind === 'update') {
			$this->collectExpressionRequests($statement->fields['target'] ?? null, $requests, $localTypes);
			return;
		}
		if ($statement->kind === 'foreach_value' || $statement->kind === 'foreach_key_value') {
			$this->collectExpressionRequests($statement->fields['source'] ?? null, $requests, $localTypes);
			$bodyTypes = $localTypes;
			if (is_string($statement->fields['value_type'] ?? null)) {
				$bodyTypes[(string) $statement->fields['value_name']] = (string) $statement->fields['value_type'];
			}
			$this->collectStatementListRequests($statement->fields['body'] ?? [], $requests, $bodyTypes);
			return;
		}
		if ($statement->kind === 'for') {
			$bodyTypes = $localTypes;
			$this->collectStatementRequests($statement->fields['init'] ?? null, $requests, $bodyTypes);
			$this->collectExpressionRequests($statement->fields['condition'] ?? null, $requests, $bodyTypes);
			$this->collectStatementRequests($statement->fields['step'] ?? null, $requests, $bodyTypes);
			$this->collectStatementListRequests($statement->fields['body'] ?? [], $requests, $bodyTypes);
			return;
		}
		if ($statement->kind === 'switch') {
			$this->collectExpressionRequests($statement->fields['expression'] ?? null, $requests, $localTypes);
			foreach (is_array($statement->fields['cases'] ?? null) ? $statement->fields['cases'] : [] as $case) {
				if (!is_array($case)) {
					continue;
				}
				$this->collectExpressionRequests($case['value'] ?? null, $requests, $localTypes);
				$caseTypes = $localTypes;
				$this->collectStatementListRequests($case['body'] ?? [], $requests, $caseTypes);
			}
			return;
		}
		foreach (['initializer', 'value', 'condition', 'source', 'target', 'expression'] as $field) {
			$this->collectExpressionRequests($statement->fields[$field] ?? null, $requests, $localTypes);
		}
		foreach (['body', 'then', 'else'] as $field) {
			$branchTypes = $localTypes;
			$this->collectStatementListRequests($statement->fields[$field] ?? [], $requests, $branchTypes);
		}
	}

	/** @param list<array<string,mixed>> $requests */
	private function collectExpressionRequests(mixed $expression, array &$requests, array $localTypes = []): void
	{
		if (!$expression instanceof JssNode) {
			return;
		}
		if ($expression->kind === 'identifier') {
			$name = (string) $expression->fields['name'];
			if (array_key_exists($name, $localTypes) && preg_match('/^[A-Z_][A-Z0-9_]*$/', $name) !== 1) {
				return;
			}
			$requests[] = $this->makeRequest('identifier_role', [
				'name' => $name,
			], $expression);
			return;
		}
		if ($expression->kind === 'template') {
			foreach ($this->templateExpressionDescriptors((string) ($expression->fields['value'] ?? '')) as $descriptor) {
				if (count($descriptor) === 1) {
					$requests[] = $this->makeRequest('identifier_role', [
						'name' => $descriptor[0],
					], $expression);
					continue;
				}
				$requests[] = $this->makeRequest('member_access', [
					'chain' => $descriptor,
					'base' => $descriptor[0] ?? '',
					'member' => $descriptor[count($descriptor) - 1] ?? '',
					'is_call' => false,
				], $expression);
			}
			return;
		}
		if ($expression->kind === 'member') {
			$chain = $this->flattenMemberChain($expression);
			$requests[] = $this->makeRequest('member_access', [
				'chain' => $chain,
				'base' => $chain[0] ?? '',
				'member' => $chain[count($chain) - 1] ?? '',
				'is_call' => false,
			], $expression);
			$this->collectExpressionRequests($expression->fields['object'] ?? null, $requests, $localTypes);
			return;
		}
		if ($expression->kind === 'optional_member') {
			$this->collectExpressionRequests($expression->fields['object'] ?? null, $requests, $localTypes);
			return;
		}
		if ($expression->kind === 'binary') {
			if ((string) ($expression->fields['operator'] ?? '') === '+') {
				$requests[] = $this->makeRequest('binary_plus', [
					'expression_key' => $this->expressionKey($expression),
					'left_type' => $this->expressionTypeHint($expression->fields['left'] ?? null, $localTypes),
					'right_type' => $this->expressionTypeHint($expression->fields['right'] ?? null, $localTypes),
					'left_expression' => $this->expressionDescriptor($expression->fields['left'] ?? null, $localTypes),
					'right_expression' => $this->expressionDescriptor($expression->fields['right'] ?? null, $localTypes),
				], $expression);
			}
			$this->collectExpressionRequests($expression->fields['left'] ?? null, $requests, $localTypes);
			$this->collectExpressionRequests($expression->fields['right'] ?? null, $requests, $localTypes);
			return;
		}
		if ($expression->kind === 'call') {
			$callee = $expression->fields['callee'] ?? null;
			if ($callee instanceof JssNode && $callee->kind === 'identifier' && strtolower((string) ($callee->fields['name'] ?? '')) === 'take') {
				$takeRequest = $this->takeContractRequestFields($expression, $localTypes);
				if ($takeRequest !== null) {
					$requests[] = $this->makeRequest('take_contract', $takeRequest, $expression);
				}
			}
			if ($callee instanceof JssNode && $callee->kind === 'member') {
				$chain = $this->flattenMemberChain($callee);
				$request = [
					'chain' => $chain,
					'base' => $chain[0] ?? '',
					'member' => $chain[count($chain) - 1] ?? '',
					'is_call' => true,
				];
				$normalizedTarget = $this->callSurface->resolveNormalizedCallTarget($chain);
				if ($normalizedTarget !== null) {
					$request['normalized_call_target'] = $normalizedTarget;
				}
				$requests[] = $this->makeRequest('member_access', $request, $callee);
			} else {
				$this->collectExpressionRequests($callee, $requests, $localTypes);
			}
			foreach (($expression->fields['args'] ?? []) as $arg) {
				$this->collectExpressionRequests($arg, $requests, $localTypes);
			}
			return;
		}
		if ($expression->kind === 'arrow_function') {
			$arrowTypes = $localTypes;
			foreach (is_array($expression->fields['params'] ?? null) ? $expression->fields['params'] : [] as $param) {
				if (!is_array($param)) {
					continue;
				}
				$name = (string) ($param['name'] ?? '');
				$type = (string) ($param['type'] ?? '');
				if ($name !== '' && $type !== '') {
					$arrowTypes[$name] = $type;
				}
			}
			$this->collectExpressionRequests($expression->fields['body'] ?? null, $requests, $arrowTypes);
			return;
		}
		if ($expression->kind === 'ternary') {
			$this->collectExpressionRequests($expression->fields['condition'] ?? null, $requests, $localTypes);
			$this->collectExpressionRequests($expression->fields['when_true'] ?? null, $requests, $localTypes);
			$this->collectExpressionRequests($expression->fields['when_false'] ?? null, $requests, $localTypes);
			return;
		}
		foreach (['object', 'index', 'expression'] as $field) {
			$this->collectExpressionRequests($expression->fields[$field] ?? null, $requests, $localTypes);
		}
		foreach (['items', 'args'] as $field) {
			foreach (is_array($expression->fields[$field] ?? null) ? $expression->fields[$field] : [] as $child) {
				$this->collectExpressionRequests($child, $requests, $localTypes);
			}
		}
		foreach (is_array($expression->fields['pairs'] ?? null) ? $expression->fields['pairs'] : [] as $pair) {
			if (is_array($pair)) {
				$this->collectExpressionRequests($pair['value'] ?? null, $requests, $localTypes);
			}
		}
	}

	/** @param array<string,string> $localTypes */
	private function expressionTypeHint(mixed $expression, array $localTypes): ?string
	{
		if (!$expression instanceof JssNode) {
			return null;
		}
		if ($expression->kind === 'string') {
			return 'string';
		}
		if ($expression->kind === 'template') {
			return 'string';
		}
		if ($expression->kind === 'number') {
			return str_contains((string) $expression->fields['value'], '.') ? 'float' : 'int';
		}
		if ($expression->kind === 'boolean') {
			return 'bool';
		}
		if ($expression->kind === 'null') {
			return 'null';
		}
		if ($expression->kind === 'identifier') {
			$name = (string) $expression->fields['name'];
			return $localTypes[$name] ?? null;
		}
		if ($expression->kind === 'await') {
			return $this->expressionTypeHint($expression->fields['expression'] ?? null, $localTypes);
		}
		if ($expression->kind === 'unary') {
			$operator = (string) ($expression->fields['operator'] ?? '');
			$innerType = $this->expressionTypeHint($expression->fields['expression'] ?? null, $localTypes);
			if ($operator === '-') {
				return in_array($innerType, ['int', 'float'], true) ? $innerType : null;
			}
			if ($operator === '!') {
				return 'bool';
			}
		}
		if ($expression->kind === 'binary') {
			$operator = (string) ($expression->fields['operator'] ?? '');
			if (in_array($operator, ['+', '-', '*', '/', '%'], true)) {
				$leftType = $this->expressionTypeHint($expression->fields['left'] ?? null, $localTypes);
				$rightType = $this->expressionTypeHint($expression->fields['right'] ?? null, $localTypes);
				if ($operator === '+') {
					if (in_array($leftType, ['mixed', 'dynamic'], true) || in_array($rightType, ['mixed', 'dynamic'], true)) {
						return 'mixed';
					}
					if (($leftType === 'string' && $this->isPrintablePlusType($rightType)) || ($rightType === 'string' && $this->isPrintablePlusType($leftType))) {
						return 'string';
					}
				}
				if (in_array($leftType, ['int', 'float'], true) && in_array($rightType, ['int', 'float'], true)) {
					if ($operator === '%') {
						return 'int';
					}
					return $leftType === 'float' || $rightType === 'float' || $operator === '/' ? 'float' : 'int';
				}
			}
		}
		if ($expression->kind === 'call') {
			$callee = $expression->fields['callee'] ?? null;
			if ($callee instanceof JssNode && $callee->kind === 'member') {
				return $this->callSurface->resolveCallReturnType($this->flattenMemberChain($callee));
			}
			if ($callee instanceof JssNode && $callee->kind === 'identifier' && strtolower((string) ($callee->fields['name'] ?? '')) === 'take') {
				return 'bool';
			}
			if ($callee instanceof JssNode && $callee->kind === 'identifier') {
				$name = strtolower((string) ($callee->fields['name'] ?? ''));
				return $this->functionReturnTypes[$name] ?? null;
			}
		}
		return null;
	}

	/** @param array<string,string> $localTypes @return array<string,mixed>|null */
	private function takeContractRequestFields(JssNode $call, array $localTypes): ?array
	{
		$args = is_array($call->fields['args'] ?? null) ? $call->fields['args'] : [];
		if (count($args) < 2) {
			return null;
		}
		$source = $args[count($args) - 1] ?? null;
		$outputs = [];
		for ($index = 0; $index < count($args) - 1; $index++) {
			$output = $args[$index] ?? null;
			if (!$output instanceof JssNode || $output->kind !== 'identifier') {
				continue;
			}
			$name = (string) ($output->fields['name'] ?? '');
			$outputs[] = [
				'name' => $name,
				'type' => $localTypes[$name] ?? '',
			];
		}
		return [
			'source_type' => $this->expressionTypeHint($source, $localTypes),
			'source_call_target' => $this->sourceCallTarget($source),
			'outputs' => $outputs,
		];
	}

	private function sourceCallTarget(mixed $source): ?string
	{
		if (!$source instanceof JssNode || $source->kind !== 'call') {
			return null;
		}
		$callee = $source->fields['callee'] ?? null;
		if (!$callee instanceof JssNode || $callee->kind !== 'member') {
			return null;
		}
		return $this->callSurface->resolveNormalizedCallTarget($this->flattenMemberChain($callee));
	}

	private function isPrintablePlusType(?string $type): bool
	{
		return in_array($type, ['string', 'int', 'float', 'bool'], true);
	}

	private function expressionKey(JssNode $expression): string
	{
		return match ($expression->kind) {
			'identifier' => 'id:' . (string) $expression->fields['name'],
			'string' => 'str:' . (string) $expression->fields['value'],
			'template' => 'template:' . sha1((string) $expression->fields['value']),
			'number' => 'num:' . (string) $expression->fields['value'],
			'boolean' => 'bool:' . (string) $expression->fields['value'],
			'null' => 'null',
			'binary' => 'binary:'
				. (string) ($expression->fields['operator'] ?? '')
				. '(' . $this->expressionKey($expression->fields['left'] ?? new JssNode('empty')) . ','
				. $this->expressionKey($expression->fields['right'] ?? new JssNode('empty')) . ')',
			'member' => 'member:' . implode('.', $this->flattenMemberChain($expression)),
			'call' => 'call:' . $this->expressionKey($expression->fields['callee'] ?? new JssNode('empty')),
			default => $expression->kind,
		};
	}

	/** @param array<string,string> $localTypes @return array<string,mixed>|null */
	private function expressionDescriptor(mixed $expression, array $localTypes): ?array
	{
		if (!$expression instanceof JssNode) {
			return null;
		}
		$typeHint = $this->expressionTypeHint($expression, $localTypes);
		if ($typeHint !== null) {
			return [
				'kind' => 'type',
				'type' => $typeHint,
			];
		}
		if ($expression->kind === 'identifier') {
			$name = (string) ($expression->fields['name'] ?? '');
			return [
				'kind' => 'alias',
				'source' => $name,
			];
		}
		if ($expression->kind === 'member') {
			return [
				'kind' => 'chain',
				'chain' => [
					'root_kind' => 'variable',
					'root_name' => $this->flattenMemberChain($expression)[0] ?? '',
					'segments' => array_slice($this->flattenMemberChain($expression), 1),
					'line' => $this->nodeLine($expression),
				],
			];
		}
		if ($expression->kind === 'call') {
			$callee = $expression->fields['callee'] ?? null;
			if ($callee instanceof JssNode && $callee->kind === 'member') {
				return [
					'kind' => 'call',
					'callee_chain' => $this->flattenMemberChain($callee),
				];
			}
			if ($callee instanceof JssNode && $callee->kind === 'identifier') {
				return [
					'kind' => 'call',
					'callee_name' => (string) ($callee->fields['name'] ?? ''),
				];
			}
		}
		if ($expression->kind === 'binary') {
			return [
				'kind' => 'binary',
				'operator' => (string) ($expression->fields['operator'] ?? ''),
				'left' => $this->expressionDescriptor($expression->fields['left'] ?? null, $localTypes),
				'right' => $this->expressionDescriptor($expression->fields['right'] ?? null, $localTypes),
			];
		}
		return [
			'kind' => $expression->kind,
		];
	}

	/** @return array<string,string> */
	private function collectFunctionReturnTypes(mixed $statements): array
	{
		$types = [];
		foreach (is_array($statements) ? $statements : [] as $statement) {
			if (!$statement instanceof JssNode) {
				continue;
			}
			if ($statement->kind === 'function_decl') {
				$name = strtolower((string) ($statement->fields['name'] ?? ''));
				$type = $statement->fields['return_type'] ?? null;
				if ($name !== '' && is_string($type) && $type !== '') {
					$types[$name] = strtolower($type);
				}
				continue;
			}
			if ($statement->kind === 'namespace_block') {
				$types = array_merge($types, $this->collectFunctionReturnTypes($statement->fields['body'] ?? []));
			}
		}
		return $types;
	}

	private function directCallName(mixed $expression): ?string
	{
		if (!$expression instanceof JssNode || $expression->kind !== 'call') {
			return null;
		}
		$callee = $expression->fields['callee'] ?? null;
		if (!$callee instanceof JssNode || $callee->kind !== 'identifier') {
			return null;
		}
		$name = trim((string) ($callee->fields['name'] ?? ''));
		return $name !== '' ? $name : null;
	}

	/** @param array<string,mixed> $fields @return array<string,mixed> */
	private function makeRequest(string $kind, array $fields, ?JssNode $source = null): array
	{
		if ($source instanceof JssNode && is_array($source->fields['range'] ?? null)) {
			$fields['range'] = $source->fields['range'];
		}
		return $this->requestFactory->make($kind, $fields);
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

	private function nodeLine(JssNode $node): int
	{
		$range = $node->fields['range'] ?? null;
		return is_array($range) ? (int) ($range['line'] ?? 0) : 0;
	}

	/** @return list<list<string>> */
	private function templateExpressionDescriptors(string $value): array
	{
		if (!preg_match_all('/\$\{([A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*)*)\}/', $value, $matches)) {
			return [];
		}
		$descriptors = [];
		$seen = [];
		foreach ($matches[1] as $match) {
			$chain = array_values(array_filter(explode('.', (string) $match), static fn (string $part): bool => $part !== ''));
			$key = implode('.', $chain);
			if ($chain === [] || isset($seen[$key])) {
				continue;
			}
			$seen[$key] = true;
			$descriptors[] = $chain;
		}
		return $descriptors;
	}
}
