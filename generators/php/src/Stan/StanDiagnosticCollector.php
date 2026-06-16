<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanDiagnosticCollector
{
	public function __construct(
		private readonly StanDependencyResolver $dependencyResolver = new StanDependencyResolver(),
		private readonly StanPathMapper $pathMapper = new StanPathMapper(),
	)
	{
	}

	/** @param array<string,mixed> $summary */
	public function countWarnings(array $summary): int
	{
		$count = 0;
		foreach (['build_errors'] as $listKey) {
			$list = $summary[$listKey] ?? [];
			if (is_array($list)) {
				$count += count($list);
			}
		}
		return $count;
	}

	/** @param list<array<string,mixed>> $symbolIndex @return list<array<string,mixed>> */
	public function collectDuplicateDiagnostics(array $symbolIndex): array
	{
		$buckets = [];
		foreach ($symbolIndex as $symbol) {
			$key = (string) ($symbol['key'] ?? '');
			if ($key !== '') {
				$buckets[$key][] = $symbol;
			}
		}
		$diagnostics = [];
		foreach ($buckets as $symbols) {
			if (count($symbols) < 2) {
				continue;
			}
			$first = $symbols[0];
			if ($this->isSyntheticRootEntrypointSymbol($first)) {
				continue;
			}
			$locations = [];
			foreach ($symbols as $symbol) {
				$locations[] = (string) ($symbol['path'] ?? '(unknown)') . ':' . (int) ($symbol['line'] ?? 0);
			}
			sort($locations, SORT_STRING);
			$diagnostics[] = [
				'kind' => 'duplicate_declaration',
				'symbol_kind' => (string) ($first['kind'] ?? 'symbol'),
				'scope' => (string) ($first['scope'] ?? ''),
				'name' => (string) ($first['name'] ?? ''),
				'message' => $this->duplicateMessage($first),
				'locations' => $locations,
			];
		}
		usort($diagnostics, static fn (array $left, array $right): int => strcmp($left['message'], $right['message']));
		return $diagnostics;
	}

	/** @param array<string,mixed> $symbol */
	private function isSyntheticRootEntrypointSymbol(array $symbol): bool
	{
		return (string) ($symbol['kind'] ?? '') === 'function'
			&& (string) ($symbol['scope'] ?? '') === ''
			&& (string) ($symbol['name'] ?? '') === '__scpp_main';
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @param list<array<string,mixed>> $symbolIndex @return list<array<string,mixed>> */
	public function collectResolutionDiagnostics(array $fileSummaries, array $symbolIndex): array
	{
		$lookup = $this->dependencyResolver->buildResolutionLookup($symbolIndex);
		$diagnostics = [];
		foreach ($fileSummaries as $summary) {
			$path = (string) ($summary['path'] ?? '(unknown)');
			foreach (($summary['dependencies'] ?? []) as $dependency) {
				if (!is_array($dependency)) {
					continue;
				}
				$kind = (string) ($dependency['kind'] ?? '');
				$target = (string) ($dependency['target'] ?? '');
				$owner = isset($dependency['owner']) && is_string($dependency['owner']) ? $dependency['owner'] : null;
				if ($kind === '' || $target === '') {
					continue;
				}
				$matches = $this->dependencyResolver->resolveDependencyTarget($kind, $target, $lookup);
				if (count($matches) === 1) {
					continue;
				}
				$diagnostics[] = [
					'kind' => count($matches) === 0 ? 'unresolved_dependency' : 'ambiguous_dependency',
					'dependency_kind' => $kind,
					'target' => $target,
					'owner' => $owner,
					'path' => $path,
					'matches' => array_map(
						static fn (array $symbol): string => (string) $symbol['path'] . ':' . (int) $symbol['line'] . ' [' . (string) $symbol['kind'] . ']',
						$matches
					),
					'message' => $this->dependencyMessage($kind, $target, $owner, count($matches)),
				];
			}
		}
		usort($diagnostics, static fn (array $left, array $right): int => strcmp($left['message'], $right['message']));
		return $diagnostics;
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @param list<array<string,mixed>> $symbolIndex @return list<array<string,mixed>> */
	public function collectOverrideDiagnostics(array $fileSummaries, array $symbolIndex, string $projectRoot): array
	{
		$lookup = $this->dependencyResolver->buildResolutionLookup($symbolIndex);
		$classCatalog = $this->buildClassCatalog($fileSummaries, $projectRoot);
		$diagnostics = [];
		foreach ($classCatalog as $classFqcn => $classInfo) {
			$parentName = isset($classInfo['parent_class']) && is_string($classInfo['parent_class']) ? $classInfo['parent_class'] : '';
			if ($parentName === '') {
				continue;
			}
			$parentMatches = $this->dependencyResolver->resolveDependencyTarget('extends', $parentName, $lookup);
			if (count($parentMatches) !== 1) {
				continue;
			}
			$parentPath = (string) ($parentMatches[0]['path'] ?? '');
			if ($parentPath === '') {
				continue;
			}
			$parentInfo = $this->findClassInfoBySymbol($classCatalog, $this->pathMapper->sourceKey($projectRoot, $parentPath), (string) ($parentMatches[0]['name'] ?? ''));
			if ($parentInfo === null) {
				continue;
			}
			$ancestorMembers = $this->collectAncestorMembers($parentInfo, $classCatalog, $lookup, $projectRoot);
			foreach (['method', 'property'] as $memberKind) {
				foreach (($classInfo[$memberKind . 's'] ?? []) as $memberName => $memberLine) {
					if (!isset($ancestorMembers[$memberKind][$memberName])) {
						continue;
					}
					$ancestor = $ancestorMembers[$memberKind][$memberName];
					$diagnostics[] = [
						'kind' => 'override_declaration',
						'member_kind' => $memberKind,
						'class' => $classFqcn,
						'name' => $memberName,
						'path' => $classInfo['path'],
						'line' => $memberLine,
						'ancestor_class' => $ancestor['class'],
						'ancestor_path' => $ancestor['path'],
						'ancestor_line' => $ancestor['line'],
						'message' => 'Inherited ' . $memberKind . ' override `' . $classFqcn . '::' . $memberName . '` conflicts with ancestor `' . $ancestor['class'] . '::' . $memberName . '`.',
					];
				}
			}
		}
		usort($diagnostics, static fn (array $left, array $right): int => strcmp($left['message'], $right['message']));
		return $diagnostics;
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @param list<array<string,mixed>> $symbolIndex @return list<array<string,mixed>> */
	public function collectInterfaceContractDiagnostics(array $fileSummaries, array $symbolIndex, string $projectRoot): array
	{
		$lookup = $this->dependencyResolver->buildResolutionLookup($symbolIndex);
		$classCatalog = $this->buildClassCatalog($fileSummaries, $projectRoot);
		$diagnostics = [];
		foreach ($classCatalog as $classFqcn => $classInfo) {
			if ((bool) ($classInfo['is_interface'] ?? false) || (bool) ($classInfo['is_abstract'] ?? false)) {
				continue;
			}
			$implementedMethods = $this->collectClassAndAncestorMethods($classInfo, $classCatalog, $lookup, $projectRoot);
			foreach (($classInfo['interfaces'] ?? []) as $interfaceName) {
				if (!is_string($interfaceName) || $interfaceName === '') {
					continue;
				}
				$interfaceMatches = $this->dependencyResolver->resolveDependencyTarget('implements', $interfaceName, $lookup);
				if (count($interfaceMatches) !== 1) {
					continue;
				}
				$interfaceInfo = $this->findClassInfoBySymbol(
					$classCatalog,
					$this->pathMapper->sourceKey($projectRoot, (string) ($interfaceMatches[0]['path'] ?? '')),
					(string) ($interfaceMatches[0]['name'] ?? '')
				);
				if ($interfaceInfo === null || (bool) ($interfaceInfo['is_interface'] ?? false) !== true) {
					continue;
				}
				$interfaceContract = $this->collectInterfaceContractMethods($interfaceInfo, $classCatalog, $lookup, $projectRoot);
				foreach ($interfaceContract['conflicts'] as $conflict) {
					$diagnostics[] = [
						'kind' => 'interface_contract_mismatch',
						'mismatch_kind' => 'inherited_interface_conflict',
						'class' => $classFqcn,
						'interface' => (string) ($interfaceInfo['fqcn'] ?? $interfaceName),
						'name' => (string) ($conflict['name'] ?? ''),
						'path' => (string) ($classInfo['path'] ?? ''),
						'line' => (int) ($classInfo['line'] ?? 0),
						'interface_path' => (string) ($conflict['path'] ?? $interfaceInfo['path'] ?? ''),
						'interface_line' => (int) ($conflict['line'] ?? $interfaceInfo['line'] ?? 0),
						'message' => 'Interface `' . (string) ($interfaceInfo['fqcn'] ?? $interfaceName) . '` inherits conflicting method contract `' . (string) ($conflict['name'] ?? '') . '()` from `' . (string) ($conflict['left_interface'] ?? '') . '` and `' . (string) ($conflict['right_interface'] ?? '') . '`.',
					];
				}
				foreach ($interfaceContract['methods'] as $methodName => $interfaceMethod) {
					$implementedMethod = $implementedMethods[(string) $methodName] ?? null;
					if (!is_array($implementedMethod)) {
						$diagnostics[] = [
							'kind' => 'interface_contract_mismatch',
							'mismatch_kind' => 'missing_method',
							'class' => $classFqcn,
							'interface' => (string) ($interfaceInfo['fqcn'] ?? $interfaceName),
							'name' => (string) $methodName,
							'path' => (string) ($classInfo['path'] ?? ''),
							'line' => (int) ($classInfo['line'] ?? 0),
							'interface_path' => (string) ($interfaceInfo['path'] ?? ''),
							'interface_line' => (int) ($interfaceMethod['line'] ?? $interfaceInfo['line'] ?? 0),
							'message' => 'Class `' . $classFqcn . '` implements interface `' . (string) ($interfaceInfo['fqcn'] ?? $interfaceName) . '` but is missing method `' . (string) $methodName . '()`.',
						];
						continue;
					}
					$implementedVisibility = strtolower((string) ($implementedMethod['visibility'] ?? 'public'));
					if ($implementedVisibility !== 'public') {
						$diagnostics[] = [
							'kind' => 'interface_contract_mismatch',
							'mismatch_kind' => 'method_visibility',
							'class' => $classFqcn,
							'interface' => (string) ($interfaceInfo['fqcn'] ?? $interfaceName),
							'name' => (string) $methodName,
							'path' => (string) ($classInfo['path'] ?? ''),
							'line' => (int) ($implementedMethod['line'] ?? $classInfo['line'] ?? 0),
							'interface_path' => (string) ($interfaceInfo['path'] ?? ''),
							'interface_line' => (int) ($interfaceMethod['line'] ?? $interfaceInfo['line'] ?? 0),
							'visibility' => $implementedVisibility,
							'message' => 'Class `' . $classFqcn . '` method `' . (string) $methodName . '()` implements interface `' . (string) ($interfaceInfo['fqcn'] ?? $interfaceName) . '` but is `' . $implementedVisibility . '`. Interface methods must be implemented as public.',
						];
						continue;
					}
					$interfaceParams = is_array($interfaceMethod['params'] ?? null) ? $interfaceMethod['params'] : [];
					$implementedParams = is_array($implementedMethod['params'] ?? null) ? $implementedMethod['params'] : [];
					if (count($interfaceParams) !== count($implementedParams)) {
						$diagnostics[] = [
							'kind' => 'interface_contract_mismatch',
							'mismatch_kind' => 'parameter_count',
							'class' => $classFqcn,
							'interface' => (string) ($interfaceInfo['fqcn'] ?? $interfaceName),
							'name' => (string) $methodName,
							'path' => (string) ($classInfo['path'] ?? ''),
							'line' => (int) ($implementedMethod['line'] ?? $classInfo['line'] ?? 0),
							'interface_path' => (string) ($interfaceInfo['path'] ?? ''),
							'interface_line' => (int) ($interfaceMethod['line'] ?? $interfaceInfo['line'] ?? 0),
							'expected_count' => count($interfaceParams),
							'actual_count' => count($implementedParams),
							'message' => 'Class `' . $classFqcn . '` method `' . (string) $methodName . '()` does not match interface `' . (string) ($interfaceInfo['fqcn'] ?? $interfaceName) . '`: expected ' . count($interfaceParams) . ' parameter(s), got ' . count($implementedParams) . '.',
						];
						continue;
					}
					foreach ($interfaceParams as $index => $interfaceParam) {
						$implementedParam = $implementedParams[$index] ?? null;
						if (!is_array($interfaceParam) || !is_array($implementedParam)) {
							continue;
						}
						$interfaceParamType = (string) ($interfaceParam['type'] ?? '');
						$implementedParamType = (string) ($implementedParam['type'] ?? '');
						if ($interfaceParamType === '' || $implementedParamType === '' || $interfaceParamType === $implementedParamType) {
							continue;
						}
						$diagnostics[] = [
							'kind' => 'interface_contract_mismatch',
							'mismatch_kind' => 'parameter_type',
							'class' => $classFqcn,
							'interface' => (string) ($interfaceInfo['fqcn'] ?? $interfaceName),
							'name' => (string) $methodName,
							'path' => (string) ($classInfo['path'] ?? ''),
							'line' => (int) ($implementedParam['line'] ?? $implementedMethod['line'] ?? $classInfo['line'] ?? 0),
							'interface_path' => (string) ($interfaceInfo['path'] ?? ''),
							'interface_line' => (int) ($interfaceParam['line'] ?? $interfaceMethod['line'] ?? $interfaceInfo['line'] ?? 0),
							'parameter_index' => $index,
							'parameter_name' => (string) ($interfaceParam['name'] ?? $implementedParam['name'] ?? ('arg' . $index)),
							'expected_type' => $interfaceParamType,
							'actual_type' => $implementedParamType,
							'message' => 'Class `' . $classFqcn . '` method `' . (string) $methodName . '()` parameter $' . (string) ($interfaceParam['name'] ?? $implementedParam['name'] ?? ('arg' . $index)) . ' does not match interface `' . (string) ($interfaceInfo['fqcn'] ?? $interfaceName) . '`: expected `' . $interfaceParamType . '`, got `' . $implementedParamType . '`.',
						];
					}
					$interfaceReturnType = (string) ($interfaceMethod['return_type'] ?? '');
					$implementedReturnType = (string) ($implementedMethod['return_type'] ?? '');
					if ($interfaceReturnType !== '' && $implementedReturnType !== '' && $interfaceReturnType !== $implementedReturnType) {
						$diagnostics[] = [
							'kind' => 'interface_contract_mismatch',
							'mismatch_kind' => 'return_type',
							'class' => $classFqcn,
							'interface' => (string) ($interfaceInfo['fqcn'] ?? $interfaceName),
							'name' => (string) $methodName,
							'path' => (string) ($classInfo['path'] ?? ''),
							'line' => (int) ($implementedMethod['line'] ?? $classInfo['line'] ?? 0),
							'interface_path' => (string) ($interfaceInfo['path'] ?? ''),
							'interface_line' => (int) ($interfaceMethod['line'] ?? $interfaceInfo['line'] ?? 0),
							'expected_type' => $interfaceReturnType,
							'actual_type' => $implementedReturnType,
							'message' => 'Class `' . $classFqcn . '` method `' . (string) $methodName . '()` does not match interface `' . (string) ($interfaceInfo['fqcn'] ?? $interfaceName) . '`: expected return `' . $interfaceReturnType . '`, got `' . $implementedReturnType . '`.',
						];
					}
				}
			}
			foreach ($this->collectAbstractAncestorMethods($classInfo, $classCatalog, $lookup, $projectRoot) as $methodName => $abstractMethod) {
				$implementedMethod = $implementedMethods[(string) $methodName] ?? null;
				if (!is_array($implementedMethod)) {
					$diagnostics[] = [
						'kind' => 'abstract_contract_mismatch',
						'mismatch_kind' => 'missing_method',
						'class' => $classFqcn,
						'abstract_class' => (string) ($abstractMethod['declaring_class'] ?? ''),
						'name' => (string) $methodName,
						'path' => (string) ($classInfo['path'] ?? ''),
						'line' => (int) ($classInfo['line'] ?? 0),
						'abstract_path' => (string) ($abstractMethod['path'] ?? ''),
						'abstract_line' => (int) ($abstractMethod['line'] ?? 0),
						'message' => 'Class `' . $classFqcn . '` extends abstract class `' . (string) ($abstractMethod['declaring_class'] ?? '') . '` but is missing method `' . (string) $methodName . '()`.',
					];
					continue;
				}
				if (!$this->interfaceMethodSignaturesMatch($abstractMethod, $implementedMethod)) {
					$diagnostics[] = [
						'kind' => 'abstract_contract_mismatch',
						'mismatch_kind' => 'method_signature',
						'class' => $classFqcn,
						'abstract_class' => (string) ($abstractMethod['declaring_class'] ?? ''),
						'name' => (string) $methodName,
						'path' => (string) ($classInfo['path'] ?? ''),
						'line' => (int) ($implementedMethod['line'] ?? $classInfo['line'] ?? 0),
						'abstract_path' => (string) ($abstractMethod['path'] ?? ''),
						'abstract_line' => (int) ($abstractMethod['line'] ?? 0),
						'message' => 'Class `' . $classFqcn . '` method `' . (string) $methodName . '()` does not match abstract method contract from `' . (string) ($abstractMethod['declaring_class'] ?? '') . '`.',
					];
				}
			}
		}
		usort($diagnostics, static fn (array $left, array $right): int => strcmp($left['message'], $right['message']));
		return $diagnostics;
	}

	/** @param array<string,mixed> $symbol */
	private function duplicateMessage(array $symbol): string
	{
		$kind = (string) ($symbol['kind'] ?? 'symbol');
		$scope = (string) ($symbol['scope'] ?? '');
		$name = (string) ($symbol['name'] ?? '');
		return $scope === ''
			? 'Duplicate ' . $kind . ' declaration `' . $name . '` in root scope.'
			: 'Duplicate ' . $kind . ' declaration `' . $name . '` in scope `' . $scope . '`.';
	}

	private function dependencyMessage(string $kind, string $target, ?string $owner, int $matchCount): string
	{
		$ownerText = $owner !== null && $owner !== '' ? ' for `' . $owner . '`' : '';
		return $matchCount === 0
			? 'Unresolved ' . $kind . ' target `' . $target . '`' . $ownerText . '.'
			: 'Ambiguous ' . $kind . ' target `' . $target . '`' . $ownerText . '.';
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @return array<string,array<string,mixed>> */
	private function buildClassCatalog(array $fileSummaries, string $projectRoot): array
	{
		$catalog = [];
		foreach ($fileSummaries as $sourceKey => $summary) {
			$path = (string) ($summary['path'] ?? $sourceKey);
			foreach (($summary['root_classes'] ?? []) as $class) {
				if (is_array($class) && (string) ($class['name'] ?? '') !== '') {
					$catalog[(string) $class['name']] = $this->makeClassCatalogEntry($class, '', $sourceKey, $path, $projectRoot);
				}
			}
			foreach (($summary['namespaces'] ?? []) as $namespace) {
				if (!is_array($namespace)) {
					continue;
				}
				$namespaceName = (string) ($namespace['name'] ?? '');
				foreach (($namespace['classes'] ?? []) as $class) {
					if (!is_array($class)) {
						continue;
					}
					$className = (string) ($class['name'] ?? '');
					if ($className === '') {
						continue;
					}
					$fqcn = $namespaceName === '' ? $className : $namespaceName . '\\' . $className;
					$catalog[$fqcn] = $this->makeClassCatalogEntry($class, $namespaceName, $sourceKey, $path, $projectRoot);
				}
			}
		}
		return $catalog;
	}

	/** @param array<string,mixed> $class @return array<string,mixed> */
	private function makeClassCatalogEntry(array $class, string $namespace, string $sourceKey, string $path, string $projectRoot): array
	{
		$methods = [];
		$methodSignatures = [];
		$isInterface = (bool) ($class['is_interface'] ?? false);
		$isAbstract = (bool) ($class['is_abstract'] ?? false);
		foreach (($class['methods'] ?? []) as $method) {
			if (is_array($method) && (string) ($method['name'] ?? '') !== '') {
				$methodName = (string) $method['name'];
				$statementCount = (int) ($method['statement_count'] ?? 0);
				$methods[$methodName] = (int) ($method['line'] ?? 0);
				$methodSignatures[$methodName] = [
					'name' => $methodName,
					'line' => (int) ($method['line'] ?? 0),
					'params' => is_array($method['params'] ?? null) ? $method['params'] : [],
					'return_type' => (string) ($method['return_type'] ?? ''),
					'visibility' => (string) ($method['visibility'] ?? 'public'),
					'is_static' => (bool) ($method['is_static'] ?? false),
					'statement_count' => $statementCount,
					'is_abstract_method' => $isInterface || ($isAbstract && $statementCount === 0),
				];
			}
		}
		$properties = [];
		foreach (($class['properties'] ?? []) as $property) {
			if (is_array($property) && (string) ($property['name'] ?? '') !== '') {
				$properties[(string) $property['name']] = (int) ($property['line'] ?? 0);
			}
		}
		return [
			'name' => (string) ($class['name'] ?? ''),
			'fqcn' => $namespace === '' ? (string) ($class['name'] ?? '') : $namespace . '\\' . (string) ($class['name'] ?? ''),
			'namespace' => $namespace,
			'path' => $path,
			'source_key' => $sourceKey,
			'path_key' => $this->pathMapper->sourceKey($projectRoot, $path),
			'line' => (int) ($class['line'] ?? 0),
			'parent_class' => isset($class['parent_class']) && is_string($class['parent_class']) ? $class['parent_class'] : null,
			'interfaces' => is_array($class['interfaces'] ?? null) ? $class['interfaces'] : [],
			'is_interface' => $isInterface,
			'is_abstract' => $isAbstract,
			'methods' => $methods,
			'method_signatures' => $methodSignatures,
			'properties' => $properties,
		];
	}

	/** @param array<string,array<string,mixed>> $classCatalog */
	private function findClassInfoBySymbol(array $classCatalog, string $sourceKey, string $className): ?array
	{
		foreach ($classCatalog as $classInfo) {
			if (is_array($classInfo) && (string) ($classInfo['path_key'] ?? '') === $sourceKey && (string) ($classInfo['name'] ?? '') === $className) {
				return $classInfo;
			}
		}
		return null;
	}

	/** @param array<string,mixed> $classInfo @param array<string,array<string,mixed>> $classCatalog @param array<string,list<array<string,mixed>>> $lookup @return array<string,array<string,mixed>> */
	private function collectClassAndAncestorMethods(array $classInfo, array $classCatalog, array $lookup, string $projectRoot, array $visited = []): array
	{
		$methods = [];
		$classFqcn = (string) ($classInfo['fqcn'] ?? '');
		if ($classFqcn === '' || isset($visited[$classFqcn])) {
			return $methods;
		}
		$visited[$classFqcn] = true;
		foreach (($classInfo['method_signatures'] ?? []) as $name => $signature) {
			if (is_array($signature) && (bool) ($signature['is_abstract_method'] ?? false) !== true) {
				$methods[(string) $name] = $signature;
			}
		}
		$parentName = isset($classInfo['parent_class']) && is_string($classInfo['parent_class']) ? $classInfo['parent_class'] : '';
		if ($parentName === '') {
			return $methods;
		}
		$parentMatches = $this->dependencyResolver->resolveDependencyTarget('extends', $parentName, $lookup);
		if (count($parentMatches) !== 1) {
			return $methods;
		}
		$parentInfo = $this->findClassInfoBySymbol(
			$classCatalog,
			$this->pathMapper->sourceKey($projectRoot, (string) ($parentMatches[0]['path'] ?? '')),
			(string) ($parentMatches[0]['name'] ?? '')
		);
		if ($parentInfo === null) {
			return $methods;
		}
		foreach ($this->collectClassAndAncestorMethods($parentInfo, $classCatalog, $lookup, $projectRoot, $visited) as $name => $signature) {
			if (!isset($methods[$name])) {
				$methods[$name] = $signature;
			}
		}
		return $methods;
	}

	/** @param array<string,mixed> $interfaceInfo @param array<string,array<string,mixed>> $classCatalog @param array<string,list<array<string,mixed>>> $lookup @return array{methods:array<string,array<string,mixed>>,conflicts:list<array<string,mixed>>} */
	private function collectInterfaceContractMethods(array $interfaceInfo, array $classCatalog, array $lookup, string $projectRoot, array $visited = []): array
	{
		$methods = [];
		$conflicts = [];
		$interfaceFqcn = (string) ($interfaceInfo['fqcn'] ?? '');
		if ($interfaceFqcn === '' || isset($visited[$interfaceFqcn])) {
			return ['methods' => $methods, 'conflicts' => $conflicts];
		}
		$visited[$interfaceFqcn] = true;
		foreach (($interfaceInfo['interfaces'] ?? []) as $parentInterfaceName) {
			if (!is_string($parentInterfaceName) || $parentInterfaceName === '') {
				continue;
			}
			$parentMatches = $this->dependencyResolver->resolveDependencyTarget('implements', $parentInterfaceName, $lookup);
			if (count($parentMatches) !== 1) {
				continue;
			}
			$parentInfo = $this->findClassInfoBySymbol(
				$classCatalog,
				$this->pathMapper->sourceKey($projectRoot, (string) ($parentMatches[0]['path'] ?? '')),
				(string) ($parentMatches[0]['name'] ?? '')
			);
			if ($parentInfo === null || (bool) ($parentInfo['is_interface'] ?? false) !== true) {
				continue;
			}
			$parentContract = $this->collectInterfaceContractMethods($parentInfo, $classCatalog, $lookup, $projectRoot, $visited);
			$conflicts = array_merge($conflicts, $parentContract['conflicts']);
			foreach ($parentContract['methods'] as $methodName => $parentMethod) {
				if (isset($methods[$methodName]) && !$this->interfaceMethodSignaturesMatch($methods[$methodName], $parentMethod)) {
					$conflicts[] = [
						'name' => (string) $methodName,
						'left_interface' => (string) ($methods[$methodName]['declaring_interface'] ?? ''),
						'right_interface' => (string) ($parentMethod['declaring_interface'] ?? ''),
						'path' => (string) ($parentMethod['path'] ?? $parentInfo['path'] ?? ''),
						'line' => (int) ($parentMethod['line'] ?? $parentInfo['line'] ?? 0),
					];
					continue;
				}
				$methods[$methodName] = $parentMethod;
			}
		}
		foreach (($interfaceInfo['method_signatures'] ?? []) as $methodName => $method) {
			if (!is_array($method)) {
				continue;
			}
			$method['declaring_interface'] = $interfaceFqcn;
			$method['path'] = (string) ($interfaceInfo['path'] ?? '');
			if (isset($methods[(string) $methodName]) && !$this->interfaceMethodSignaturesMatch($methods[(string) $methodName], $method)) {
				$conflicts[] = [
					'name' => (string) $methodName,
					'left_interface' => (string) ($methods[(string) $methodName]['declaring_interface'] ?? ''),
					'right_interface' => $interfaceFqcn,
					'path' => (string) ($interfaceInfo['path'] ?? ''),
					'line' => (int) ($method['line'] ?? $interfaceInfo['line'] ?? 0),
				];
				continue;
			}
			$methods[(string) $methodName] = $method;
		}
		return ['methods' => $methods, 'conflicts' => $conflicts];
	}

	/** @param array<string,mixed> $left @param array<string,mixed> $right */
	private function interfaceMethodSignaturesMatch(array $left, array $right): bool
	{
		if ((string) ($left['return_type'] ?? '') !== (string) ($right['return_type'] ?? '')) {
			return false;
		}
		$leftParams = is_array($left['params'] ?? null) ? $left['params'] : [];
		$rightParams = is_array($right['params'] ?? null) ? $right['params'] : [];
		if (count($leftParams) !== count($rightParams)) {
			return false;
		}
		foreach ($leftParams as $index => $leftParam) {
			$rightParam = $rightParams[$index] ?? null;
			if (!is_array($leftParam) || !is_array($rightParam)) {
				continue;
			}
			if ((string) ($leftParam['type'] ?? '') !== (string) ($rightParam['type'] ?? '')) {
				return false;
			}
		}
		return true;
	}

	/** @param array<string,mixed> $classInfo @param array<string,array<string,mixed>> $classCatalog @param array<string,list<array<string,mixed>>> $lookup @return array<string,array<string,mixed>> */
	private function collectAbstractAncestorMethods(array $classInfo, array $classCatalog, array $lookup, string $projectRoot, array $visited = []): array
	{
		$methods = [];
		$classFqcn = (string) ($classInfo['fqcn'] ?? '');
		if ($classFqcn === '' || isset($visited[$classFqcn])) {
			return $methods;
		}
		$visited[$classFqcn] = true;
		$parentName = isset($classInfo['parent_class']) && is_string($classInfo['parent_class']) ? $classInfo['parent_class'] : '';
		if ($parentName === '') {
			return $methods;
		}
		$parentMatches = $this->dependencyResolver->resolveDependencyTarget('extends', $parentName, $lookup);
		if (count($parentMatches) !== 1) {
			return $methods;
		}
		$parentInfo = $this->findClassInfoBySymbol(
			$classCatalog,
			$this->pathMapper->sourceKey($projectRoot, (string) ($parentMatches[0]['path'] ?? '')),
			(string) ($parentMatches[0]['name'] ?? '')
		);
		if ($parentInfo === null) {
			return $methods;
		}
		foreach ($this->collectAbstractAncestorMethods($parentInfo, $classCatalog, $lookup, $projectRoot, $visited) as $name => $method) {
			$methods[$name] = $method;
		}
		foreach (($parentInfo['method_signatures'] ?? []) as $name => $method) {
			if (!is_array($method)) {
				continue;
			}
			if ((bool) ($method['is_abstract_method'] ?? false) === true) {
				$method['declaring_class'] = (string) ($parentInfo['fqcn'] ?? '');
				$method['path'] = (string) ($parentInfo['path'] ?? '');
				$methods[(string) $name] = $method;
				continue;
			}
			unset($methods[(string) $name]);
		}
		return $methods;
	}

	/** @param array<string,mixed> $classInfo @param array<string,array<string,mixed>> $classCatalog @param array<string,list<array<string,mixed>>> $lookup @return array{method:array<string,array{class:string,path:string,line:int}>,property:array<string,array{class:string,path:string,line:int}>} */
	private function collectAncestorMembers(array $classInfo, array $classCatalog, array $lookup, string $projectRoot, array $visited = []): array
	{
		$members = ['method' => [], 'property' => []];
		$classFqcn = (string) ($classInfo['fqcn'] ?? '');
		if ($classFqcn === '' || isset($visited[$classFqcn])) {
			return $members;
		}
		$visited[$classFqcn] = true;
		foreach (['method', 'property'] as $memberKind) {
			foreach (($classInfo[$memberKind . 's'] ?? []) as $name => $line) {
				if ($memberKind === 'method') {
					$signature = $classInfo['method_signatures'][$name] ?? null;
					if (is_array($signature) && (bool) ($signature['is_abstract_method'] ?? false) === true) {
						continue;
					}
				}
				$members[$memberKind][(string) $name] = ['class' => $classFqcn, 'path' => (string) ($classInfo['path'] ?? ''), 'line' => (int) $line];
			}
		}
		$parentName = isset($classInfo['parent_class']) && is_string($classInfo['parent_class']) ? $classInfo['parent_class'] : '';
		if ($parentName === '') {
			return $members;
		}
		$parentMatches = $this->dependencyResolver->resolveDependencyTarget('extends', $parentName, $lookup);
		if (count($parentMatches) !== 1) {
			return $members;
		}
		$parentPath = (string) ($parentMatches[0]['path'] ?? '');
		if ($parentPath === '') {
			return $members;
		}
		$parentInfo = $this->findClassInfoBySymbol($classCatalog, $this->pathMapper->sourceKey($projectRoot, $parentPath), (string) ($parentMatches[0]['name'] ?? ''));
		if ($parentInfo === null) {
			return $members;
		}
		$parentMembers = $this->collectAncestorMembers($parentInfo, $classCatalog, $lookup, $projectRoot, $visited);
		foreach (['method', 'property'] as $memberKind) {
			foreach ($parentMembers[$memberKind] as $name => $memberInfo) {
				if (!isset($members[$memberKind][$name])) {
					$members[$memberKind][$name] = $memberInfo;
				}
			}
		}
		return $members;
	}
}
