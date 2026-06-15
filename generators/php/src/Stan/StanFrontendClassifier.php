<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanFrontendClassifier
{
	public function __construct(
		private readonly StanPhpRuntimeFunctionCatalog $phpRuntimeFunctionCatalog = new StanPhpRuntimeFunctionCatalog(),
		private readonly StanTakeContractResolver $takeContractResolver = new StanTakeContractResolver(),
	)
	{
	}

	/**
	 * @param array<string,array<string,mixed>> $fileSummaries
	 * @param list<array<string,mixed>> $symbolIndex
	 * @param list<string>|null $activeRuntimeModules
	 * @return array<string,array<string,mixed>>
	 */
	public function classify(array $fileSummaries, array $symbolIndex, ?array $activeRuntimeModules = null): array
	{
		$results = [];
		foreach ($fileSummaries as $summary) {
			foreach (($summary['frontend_classification_requests'] ?? []) as $request) {
				if (!is_array($request)) {
					continue;
				}
				$id = (string) ($request['id'] ?? '');
				if ($id === '') {
					continue;
				}
				$classification = $this->classifyRequest($request, $symbolIndex, $activeRuntimeModules);
				$classification['request_id'] = $id;
				$classification['request_kind'] = (string) ($request['kind'] ?? '');
				if (isset($request['name'])) {
					$classification['name'] = (string) $request['name'];
				}
				if (is_array($request['chain'] ?? null)) {
					$classification['chain'] = array_values(array_map('strval', $request['chain']));
				}
				if (isset($request['is_call'])) {
					$classification['is_call'] = ($request['is_call'] ?? false) === true;
				}
				if (isset($request['expression_key'])) {
					$classification['expression_key'] = (string) $request['expression_key'];
				}
				if (isset($request['namespace'])) {
					$classification['namespace'] = (string) $request['namespace'];
				}
				if (isset($request['path'])) {
					$classification['path'] = (string) $request['path'];
				}
				if (is_array($request['range'] ?? null)) {
					$classification['range'] = $request['range'];
					$classification['line'] = (int) ($request['range']['line'] ?? 0);
					$classification['column'] = (int) ($request['range']['column'] ?? 0);
				}
				$results[$id] = $classification;
			}
		}
		return $results;
	}

	/** @param array<string,mixed> $request @param list<array<string,mixed>> $symbolIndex @param list<string>|null $activeRuntimeModules @return array<string,mixed> */
	private function classifyRequest(array $request, array $symbolIndex, ?array $activeRuntimeModules): array
	{
		$kind = (string) ($request['kind'] ?? '');
		if ($kind === 'identifier_role') {
			return $this->classifyIdentifier($request, $symbolIndex, $activeRuntimeModules);
		}
		if ($kind === 'member_access') {
			return $this->classifyMemberAccess($request, $symbolIndex, $activeRuntimeModules);
		}
		if ($kind === 'binary_plus') {
			return $this->classifyBinaryPlus($request);
		}
		if ($kind === 'take_contract') {
			return $this->classifyTakeContract($request);
		}
		return [
			'kind' => 'unknown_request',
			'diagnostics' => [$this->diagnostic('Unknown frontend classification request `' . $kind . '`.')],
		];
	}

	/** @param array<string,mixed> $request @return array<string,mixed> */
	private function classifyTakeContract(array $request): array
	{
		$sourceCallTarget = strtolower(trim((string) ($request['source_call_target'] ?? '')));
		$catalogSourceType = $sourceCallTarget !== '' ? $this->phpRuntimeFunctionCatalog->returnType($sourceCallTarget) : null;
		$sourceType = strtolower(trim((string) ($catalogSourceType ?? ($request['source_type'] ?? ''))));
		$outputs = is_array($request['outputs'] ?? null) ? $request['outputs'] : [];
		if ($sourceType === '') {
			return [
				'kind' => 'take_contract_unresolved',
				'source_call_target' => $sourceCallTarget,
				'diagnostics' => [],
			];
		}
		$contract = $this->takeContractResolver->resolve($sourceType);
		if ($contract === null) {
			return [
				'kind' => 'invalid_take_contract',
				'source_type' => $sourceType,
				'source_call_target' => $sourceCallTarget,
				'diagnostics' => [$this->diagnostic('`take(...)` requires a nullable<T>, result<T>, result_or_false<T>, or result_or_bool<T> source expression.')],
			];
		}

		$expectedTypes = array_values(array_map(static fn (string $type): string => strtolower($type), $contract['output_types']));
		$diagnostics = [];
		if (count($outputs) !== count($expectedTypes)) {
			$diagnostics[] = $this->diagnostic($this->takeArityMessage((string) $contract['family']));
		}
		foreach ($expectedTypes as $index => $expectedType) {
			$output = is_array($outputs[$index] ?? null) ? $outputs[$index] : [];
			$name = (string) ($output['name'] ?? '');
			$actualType = strtolower(trim((string) ($output['type'] ?? '')));
			if ($actualType === '') {
				continue;
			}
			if ($actualType !== $expectedType) {
				$diagnostics[] = $this->diagnostic('`take(...)` expects output `' . $name . '` to have type `' . $expectedType . '` but found `' . $actualType . '`.');
			}
		}

		return [
			'kind' => $diagnostics === [] ? 'take_contract' : 'invalid_take_contract',
			'family' => (string) $contract['family'],
			'source_type' => $sourceType,
			'source_call_target' => $sourceCallTarget,
			'output_types' => $expectedTypes,
			'diagnostics' => $diagnostics,
		];
	}

	private function takeArityMessage(string $family): string
	{
		return match ($family) {
			'nullable', 'result_or_false' => '`take(...)` for nullable/result_or_false sources requires one output variable plus the source expression.',
			'result' => '`take(...)` for result<T> sources requires two output variables plus the source expression.',
			'result_or_bool' => '`take(...)` for result_or_bool<T> sources requires two output variables plus the source expression.',
			default => '`take(...)` requires a supported wrapper source expression.',
		};
	}

	/** @param array<string,mixed> $request @return array<string,mixed> */
	private function classifyBinaryPlus(array $request): array
	{
		$leftType = strtolower((string) ($request['left_type'] ?? ''));
		$rightType = strtolower((string) ($request['right_type'] ?? ''));
		if (in_array($leftType, ['mixed', 'dynamic'], true) || in_array($rightType, ['mixed', 'dynamic'], true)) {
			return [
				'kind' => 'dynamic_plus',
				'operator' => 'js_plus',
				'left_type' => $leftType,
				'right_type' => $rightType,
				'diagnostics' => [],
			];
		}
		if (($leftType === 'string' && $this->isJssPrintablePlusType($rightType)) || ($rightType === 'string' && $this->isJssPrintablePlusType($leftType))) {
			return [
				'kind' => 'string_concat',
				'operator' => '.',
				'left_type' => $leftType,
				'right_type' => $rightType,
				'diagnostics' => [],
			];
		}
		if (in_array($leftType, ['int', 'float'], true) && in_array($rightType, ['int', 'float'], true)) {
			return [
				'kind' => 'numeric_add',
				'operator' => '+',
				'left_type' => $leftType,
				'right_type' => $rightType,
				'diagnostics' => [],
			];
		}
		return [
			'kind' => 'binary_plus_unresolved',
			'operator' => '+',
			'left_type' => $leftType,
			'right_type' => $rightType,
			'diagnostics' => [$this->diagnostic('JSS `+` requires numeric operands, a `mixed`/`dynamic` boundary, or one static string operand plus a known printable type.')],
		];
	}

	private function isJssPrintablePlusType(string $type): bool
	{
		return in_array($type, ['string', 'int', 'float', 'bool'], true);
	}

	/** @param array<string,mixed> $request @param list<array<string,mixed>> $symbolIndex @param list<string>|null $activeRuntimeModules @return array<string,mixed> */
	private function classifyIdentifier(array $request, array $symbolIndex, ?array $activeRuntimeModules = null): array
	{
		$name = (string) ($request['name'] ?? '');
		if (in_array($name, ['count', 'shell_exec', 'cli_argc', 'cli_argv', 'cli_args', 'take'], true)) {
			return [
				'kind' => 'builtin_function',
				'name' => $name,
				'target' => $name,
				'return_type' => $this->phpRuntimeFunctionCatalog->returnType($name),
				'diagnostics' => [],
			];
		}
		if (in_array($name, ['argc', 'argv'], true)) {
			return [
				'kind' => 'builtin_global',
				'name' => $name,
				'target' => '$' . $name,
				'diagnostics' => [],
			];
		}
		$requestNamespace = (string) ($request['namespace'] ?? '');
		$aliasMatches = $this->findSymbols($symbolIndex, 'use_alias', $requestNamespace, $name);
		foreach ($aliasMatches as $alias) {
			$useKind = (string) ($alias['use_kind'] ?? 'class');
			if ($useKind === 'function') {
				return [
					'kind' => 'function',
					'name' => $name,
					'target' => (string) ($alias['target'] ?? ''),
					'diagnostics' => [],
				];
			}
			if ($useKind === 'const') {
				return [
					'kind' => 'constant',
					'name' => $name,
					'target' => (string) ($alias['target'] ?? ''),
					'diagnostics' => [],
				];
			}
		}
		foreach (['class', 'function', 'constant'] as $kind) {
			$matches = $this->findSymbols($symbolIndex, $kind, '', $name);
			if (count($matches) === 1) {
				$target = match ($kind) {
					'function' => $name,
					'constant' => $name,
					default => (string) ($matches[0]['key'] ?? ''),
				};
				return [
					'kind' => $kind,
					'name' => $name,
					'target' => $target,
					'diagnostics' => [],
				];
			}
		}
		if ($this->phpRuntimeFunctionCatalog->hasFunction($name)) {
			$requiredModule = $this->phpRuntimeFunctionCatalog->requiredModule($name);
			if ($requiredModule !== null && !$this->hasRuntimeModule($activeRuntimeModules, $requiredModule)) {
				return [
					'kind' => 'unavailable_runtime_module',
					'name' => $name,
					'target' => $name,
					'required_module' => $requiredModule,
					'diagnostics' => [$this->diagnostic('Runtime helper `' . $name . '()` requires module `' . $requiredModule . '` in the active project runtime config.')],
				];
			}
			return [
				'kind' => 'function',
				'name' => $name,
				'target' => $name,
				'return_type' => $this->phpRuntimeFunctionCatalog->returnType($name),
				'diagnostics' => [],
			];
		}
		return [
			'kind' => 'unresolved_identifier',
			'name' => $name,
			'diagnostics' => [$this->diagnostic('JSS identifier `' . $name . '` could not be resolved by STAN.')],
		];
	}

	/** @param array<string,mixed> $request @param list<array<string,mixed>> $symbolIndex @param list<string>|null $activeRuntimeModules @return array<string,mixed> */
	private function classifyMemberAccess(array $request, array $symbolIndex, ?array $activeRuntimeModules = null): array
	{
		$chain = is_array($request['chain'] ?? null) ? array_values(array_map('strval', $request['chain'])) : [];
		if (count($chain) < 2) {
			return [
				'kind' => 'invalid_member_access',
				'diagnostics' => [$this->diagnostic('Member access classification requires at least two path segments.')],
			];
		}

		$base = $chain[0];
		$member = $chain[count($chain) - 1];
		$isCall = ($request['is_call'] ?? false) === true;
		$requestNamespace = (string) ($request['namespace'] ?? '');
		$normalizedCallTarget = is_string($request['normalized_call_target'] ?? null) ? trim((string) $request['normalized_call_target']) : '';
		if ($isCall && $normalizedCallTarget !== '') {
			return $this->classifyIdentifier([
				'name' => $normalizedCallTarget,
				'namespace' => $requestNamespace,
			], $symbolIndex, $activeRuntimeModules);
		}

		$namespacedResolution = $this->resolveNamespacedSymbolAccess($chain, $symbolIndex, $requestNamespace);
		if ($namespacedResolution !== null) {
			if ($isCall && $namespacedResolution['kind'] === 'function') {
				return [
					'kind' => 'namespaced_function',
					'namespace' => $namespacedResolution['namespace'],
					'member' => $member,
					'target' => $namespacedResolution['target'],
					'diagnostics' => [],
				];
			}
			if (!$isCall && $namespacedResolution['kind'] === 'constant') {
				return [
					'kind' => 'namespaced_constant',
					'namespace' => $namespacedResolution['namespace'],
					'member' => $member,
					'target' => $namespacedResolution['target'],
					'diagnostics' => [],
				];
			}
		}

		if (!$isCall && count($chain) === 2) {
			$constantMatches = $this->findSymbols($symbolIndex, 'constant', $base, $member);
			if (count($constantMatches) === 1) {
				return [
					'kind' => 'namespaced_constant',
					'namespace' => $base,
					'member' => $member,
					'target' => '\\' . $base . '\\' . $member,
					'diagnostics' => [],
				];
			}
		}

		$classResolution = $this->resolveClassAccess($chain, $symbolIndex, $requestNamespace);
		if ($classResolution === null) {
			return [
				'kind' => 'instance_member',
				'base' => $base,
				'member' => $member,
				'diagnostics' => [],
			];
		}
		$className = $classResolution['class_name'];
		$classScope = $classResolution['class_scope'];
		$emittedClass = $classResolution['emitted_class'];

		if ($isCall) {
			$methodMatches = $this->findSymbols($symbolIndex, 'method', $classScope, $member);
			foreach ($methodMatches as $method) {
				if (($method['is_static'] ?? false) === true) {
					return [
						'kind' => 'static_method',
						'class' => $className,
						'member' => $member,
						'target' => $emittedClass . '::' . $member,
						'diagnostics' => [],
					];
				}
			}
			return [
				'kind' => 'invalid_static_method',
				'class' => $className,
				'member' => $member,
				'diagnostics' => [$this->diagnostic('Static method `' . implode('.', $chain) . '()` could not be resolved.')],
			];
		}

		$propertyMatches = $this->findSymbols($symbolIndex, 'property', $classScope, $member);
		if (count($propertyMatches) === 1 && ($propertyMatches[0]['is_static'] ?? false) === true) {
			return [
				'kind' => 'static_property',
				'class' => $className,
				'member' => $member,
				'target' => $emittedClass . '::$' . $member,
				'diagnostics' => [],
			];
		}

		$constantMatches = $this->findSymbols($symbolIndex, 'class_constant', $classScope, $member);
		if (count($constantMatches) === 1) {
			return [
				'kind' => 'class_constant',
				'class' => $className,
				'member' => $member,
				'target' => $emittedClass . '::' . $member,
				'diagnostics' => [],
			];
		}

		return [
			'kind' => 'invalid_class_member',
			'class' => $className,
			'member' => $member,
			'diagnostics' => [$this->diagnostic('Class member `' . implode('.', $chain) . '` could not be resolved as a static property or class constant.')],
		];
	}

	/** @param list<string>|null $activeRuntimeModules */
	private function hasRuntimeModule(?array $activeRuntimeModules, string $requiredModule): bool
	{
		if ($activeRuntimeModules === null) {
			return true;
		}
		$modules = [];
		foreach ($activeRuntimeModules as $module) {
			if (!is_string($module)) {
				continue;
			}
			$module = strtolower(trim($module));
			if ($module !== '') {
				$modules[] = $module;
			}
		}
		return in_array(strtolower(trim($requiredModule)), $modules, true);
	}

	/** @param list<string> $chain @param list<array<string,mixed>> $symbolIndex @return array{kind:string,namespace:string,target:string}|null */
	private function resolveNamespacedSymbolAccess(array $chain, array $symbolIndex, string $requestNamespace = ''): ?array
	{
		if (count($chain) < 2) {
			return null;
		}
		$name = $chain[count($chain) - 1];
		$namespaceParts = array_slice($chain, 0, -1);
		$namespace = implode('\\', $namespaceParts);
		if ($namespace !== '') {
			if (count($this->findSymbols($symbolIndex, 'function', $namespace, $name)) === 1) {
				return [
					'kind' => 'function',
					'namespace' => $namespace,
					'target' => '\\' . $namespace . '\\' . $name,
				];
			}
			if (count($this->findSymbols($symbolIndex, 'constant', $namespace, $name)) === 1) {
				return [
					'kind' => 'constant',
					'namespace' => $namespace,
					'target' => '\\' . $namespace . '\\' . $name,
				];
			}
		}
		$aliasMatches = $this->findSymbols($symbolIndex, 'use_alias', $requestNamespace, $namespaceParts[0] ?? '');
		if (count($aliasMatches) !== 1) {
			return null;
		}
		$targetNamespace = (string) ($aliasMatches[0]['target'] ?? '');
		$resolvedNamespaceParts = array_values(array_filter(explode('\\', $targetNamespace), static fn (string $part): bool => $part !== ''));
		$resolvedNamespaceParts = array_merge($resolvedNamespaceParts, array_slice($namespaceParts, 1));
		$resolvedNamespace = implode('\\', $resolvedNamespaceParts);
		if ($resolvedNamespace === '') {
			return null;
		}
		if (count($this->findSymbols($symbolIndex, 'function', $resolvedNamespace, $name)) === 1) {
			return [
				'kind' => 'function',
				'namespace' => $resolvedNamespace,
				'target' => '\\' . $resolvedNamespace . '\\' . $name,
			];
		}
		if (count($this->findSymbols($symbolIndex, 'constant', $resolvedNamespace, $name)) === 1) {
			return [
				'kind' => 'constant',
				'namespace' => $resolvedNamespace,
				'target' => '\\' . $resolvedNamespace . '\\' . $name,
			];
		}
		return null;
	}

	/** @param list<string> $chain @param list<array<string,mixed>> $symbolIndex @return array{class_name:string,class_scope:string,emitted_class:string}|null */
	private function resolveClassAccess(array $chain, array $symbolIndex, string $requestNamespace = ''): ?array
	{
		if (count($chain) < 2) {
			return null;
		}
		$classParts = array_slice($chain, 0, -1);
		if (count($classParts) === 1) {
			$className = $classParts[0];
			if (count($this->findSymbols($symbolIndex, 'class', '', $className)) === 1) {
				return [
					'class_name' => $className,
					'class_scope' => $className,
					'emitted_class' => $className,
				];
			}
			$aliasMatches = $this->findSymbols($symbolIndex, 'use_alias', $requestNamespace, $className);
			if (count($aliasMatches) === 1) {
				$target = (string) ($aliasMatches[0]['target'] ?? '');
				$targetParts = array_values(array_filter(explode('\\', $target), static fn (string $part): bool => $part !== ''));
				$targetClass = $targetParts[count($targetParts) - 1] ?? '';
				$targetNamespace = implode('\\', array_slice($targetParts, 0, -1));
				if ($targetClass !== '' && count($this->findSymbols($symbolIndex, 'class', $targetNamespace, $targetClass)) === 1) {
					return [
						'class_name' => $targetClass,
						'class_scope' => ($targetNamespace !== '' ? $targetNamespace . '::' : '') . $targetClass,
						'emitted_class' => $className,
					];
				}
			}
			if ($requestNamespace !== '' && count($this->findSymbols($symbolIndex, 'class', $requestNamespace, $className)) === 1) {
				return [
					'class_name' => $className,
					'class_scope' => $requestNamespace . '::' . $className,
					'emitted_class' => $className,
				];
			}
			return null;
		}

		$aliasMatches = $this->findSymbols($symbolIndex, 'use_alias', $requestNamespace, $classParts[0]);
		if (count($aliasMatches) === 1) {
			$targetNamespace = (string) ($aliasMatches[0]['target'] ?? '');
			$className = $classParts[1] ?? '';
			if ($targetNamespace !== '' && $className !== '' && count($this->findSymbols($symbolIndex, 'class', $targetNamespace, $className)) === 1) {
				return [
					'class_name' => $className,
					'class_scope' => $targetNamespace . '::' . $className,
					'emitted_class' => $classParts[0] . '\\' . $className,
				];
			}
		}

		$className = $classParts[count($classParts) - 1];
		$namespace = implode('\\', array_slice($classParts, 0, -1));
		if ($namespace !== '' && count($this->findSymbols($symbolIndex, 'class', $namespace, $className)) === 1) {
			return [
				'class_name' => $className,
				'class_scope' => $namespace . '::' . $className,
				'emitted_class' => '\\' . $namespace . '\\' . $className,
			];
		}
		return null;
	}

	/** @param list<array<string,mixed>> $symbolIndex @return list<array<string,mixed>> */
	private function findSymbols(array $symbolIndex, string $kind, string $scope, string $name): array
	{
		$matches = [];
		foreach ($symbolIndex as $symbol) {
			if ((string) ($symbol['kind'] ?? '') !== $kind) {
				continue;
			}
			if ((string) ($symbol['scope'] ?? '') !== $scope) {
				continue;
			}
			if ((string) ($symbol['name'] ?? '') !== $name) {
				continue;
			}
			$matches[] = $symbol;
		}
		return $matches;
	}

	/** @return array{message:string} */
	private function diagnostic(string $message): array
	{
		return ['message' => $message];
	}
}
