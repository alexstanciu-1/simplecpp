<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanExpressionTypeResolver
{
	public function __construct(
		private readonly StanDependencyResolver $dependencyResolver = new StanDependencyResolver(),
	)
	{
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @param list<array<string,mixed>> $symbolIndex @return list<array<string,mixed>> */
	public function resolveReturnChains(array $fileSummaries, array $symbolIndex): array
	{
		$classCatalog = $this->buildClassCatalog($fileSummaries);
		$classLookup = $this->buildClassLookup($classCatalog);
		$functionLookup = $this->buildFunctionLookup($fileSummaries);
		$observations = [];

		foreach ($fileSummaries as $summary) {
			$path = (string) ($summary['path'] ?? '(unknown)');

			foreach (($summary['root_functions'] ?? []) as $function) {
				if (is_array($function)) {
					$observations = array_merge($observations, $this->resolveFunctionChains($function, null, $path, $classLookup, $functionLookup));
				}
			}

			foreach (($summary['root_classes'] ?? []) as $class) {
				if (is_array($class)) {
					$observations = array_merge($observations, $this->resolveClassMethodChains($class, '', $path, $classLookup, $functionLookup));
				}
			}

			foreach (($summary['namespaces'] ?? []) as $namespace) {
				if (!is_array($namespace)) {
					continue;
				}
				$namespaceName = (string) ($namespace['name'] ?? '');
				foreach (($namespace['functions'] ?? []) as $function) {
					if (is_array($function)) {
						$observations = array_merge($observations, $this->resolveFunctionChains($function, $namespaceName, $path, $classLookup, $functionLookup));
					}
				}
				foreach (($namespace['classes'] ?? []) as $class) {
					if (is_array($class)) {
						$observations = array_merge($observations, $this->resolveClassMethodChains($class, $namespaceName, $path, $classLookup, $functionLookup));
					}
				}
			}
		}

		usort($observations, static fn (array $a, array $b): int => strcmp($a['context'], $b['context']));
		return $observations;
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @param list<array<string,mixed>> $symbolIndex @return list<array<string,mixed>> */
	public function resolveExpressionChains(array $fileSummaries, array $symbolIndex): array
	{
		return $this->resolveChainsByField($fileSummaries, 'expression_chains', $symbolIndex, 'expression_chain');
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @param list<array<string,mixed>> $symbolIndex @return list<array<string,mixed>> */
	public function collectReturnChainDiagnostics(array $fileSummaries, array $symbolIndex): array
	{
		return $this->collectChainDiagnosticsByField($fileSummaries, 'return_chains', $symbolIndex, 'return_chain_resolution_warning');
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @param list<array<string,mixed>> $symbolIndex @return list<array<string,mixed>> */
	public function collectExpressionChainDiagnostics(array $fileSummaries, array $symbolIndex): array
	{
		return $this->collectChainDiagnosticsByField($fileSummaries, 'expression_chains', $symbolIndex, 'expression_chain_resolution_warning');
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @param list<array<string,mixed>> $symbolIndex @return list<array<string,mixed>> */
	public function collectLocalTypeDiagnostics(array $fileSummaries, array $symbolIndex): array
	{
		$classCatalog = $this->buildClassCatalog($fileSummaries);
		$classLookup = $this->buildClassLookup($classCatalog);
		$functionLookup = $this->buildFunctionLookup($fileSummaries);
		$diagnostics = [];

		foreach ($fileSummaries as $summary) {
			$path = (string) ($summary['path'] ?? '(unknown)');

			foreach (($summary['root_functions'] ?? []) as $function) {
				if (is_array($function)) {
					$diagnostics = array_merge($diagnostics, $this->collectFunctionLocalTypeDiagnostics($function, null, $path, $classLookup, $functionLookup));
				}
			}

			foreach (($summary['root_classes'] ?? []) as $class) {
				if (is_array($class)) {
					$diagnostics = array_merge($diagnostics, $this->collectClassMethodLocalTypeDiagnostics($class, '', $path, $classLookup, $functionLookup));
				}
			}

			foreach (($summary['namespaces'] ?? []) as $namespace) {
				if (!is_array($namespace)) {
					continue;
				}
				$namespaceName = (string) ($namespace['name'] ?? '');
				foreach (($namespace['functions'] ?? []) as $function) {
					if (is_array($function)) {
						$diagnostics = array_merge($diagnostics, $this->collectFunctionLocalTypeDiagnostics($function, $namespaceName, $path, $classLookup, $functionLookup));
					}
				}
				foreach (($namespace['classes'] ?? []) as $class) {
					if (is_array($class)) {
						$diagnostics = array_merge($diagnostics, $this->collectClassMethodLocalTypeDiagnostics($class, $namespaceName, $path, $classLookup, $functionLookup));
					}
				}
			}
		}

		usort($diagnostics, static fn (array $a, array $b): int => strcmp($a['message'], $b['message']));
		return $diagnostics;
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @param list<array<string,mixed>> $symbolIndex @return list<array<string,mixed>> */
	public function collectPropertyTypeDiagnostics(array $fileSummaries, array $symbolIndex): array
	{
		$classCatalog = $this->buildClassCatalog($fileSummaries);
		$classLookup = $this->buildClassLookup($classCatalog);
		$functionLookup = $this->buildFunctionLookup($fileSummaries);
		$diagnostics = [];

		foreach ($fileSummaries as $summary) {
			$path = (string) ($summary['path'] ?? '(unknown)');
			foreach (($summary['root_functions'] ?? []) as $function) {
				if (is_array($function)) {
					$diagnostics = array_merge($diagnostics, $this->collectFunctionPropertyTypeDiagnostics($function, null, $path, $classLookup, $functionLookup));
				}
			}
			foreach (($summary['root_classes'] ?? []) as $class) {
				if (is_array($class)) {
					$diagnostics = array_merge($diagnostics, $this->collectClassMethodPropertyTypeDiagnostics($class, '', $path, $classLookup, $functionLookup));
				}
			}
			foreach (($summary['namespaces'] ?? []) as $namespace) {
				if (!is_array($namespace)) {
					continue;
				}
				$namespaceName = (string) ($namespace['name'] ?? '');
				foreach (($namespace['functions'] ?? []) as $function) {
					if (is_array($function)) {
						$diagnostics = array_merge($diagnostics, $this->collectFunctionPropertyTypeDiagnostics($function, $namespaceName, $path, $classLookup, $functionLookup));
					}
				}
				foreach (($namespace['classes'] ?? []) as $class) {
					if (is_array($class)) {
						$diagnostics = array_merge($diagnostics, $this->collectClassMethodPropertyTypeDiagnostics($class, $namespaceName, $path, $classLookup, $functionLookup));
					}
				}
			}
		}

		usort($diagnostics, static fn (array $a, array $b): int => strcmp($a['message'], $b['message']));
		return $diagnostics;
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @param list<array<string,mixed>> $symbolIndex @return list<array<string,mixed>> */
	public function collectPropertyReadDiagnostics(array $fileSummaries, array $symbolIndex): array
	{
		$classCatalog = $this->buildClassCatalog($fileSummaries);
		$classLookup = $this->buildClassLookup($classCatalog);
		$functionLookup = $this->buildFunctionLookup($fileSummaries);
		$diagnostics = [];

		foreach ($fileSummaries as $summary) {
			$path = (string) ($summary['path'] ?? '(unknown)');
			foreach (($summary['root_functions'] ?? []) as $function) {
				if (is_array($function)) {
					$diagnostics = array_merge($diagnostics, $this->collectFunctionPropertyReadDiagnostics($function, null, $path, $classLookup, $functionLookup));
				}
			}
			foreach (($summary['root_classes'] ?? []) as $class) {
				if (is_array($class)) {
					$diagnostics = array_merge($diagnostics, $this->collectClassMethodPropertyReadDiagnostics($class, '', $path, $classLookup, $functionLookup));
				}
			}
			foreach (($summary['namespaces'] ?? []) as $namespace) {
				if (!is_array($namespace)) {
					continue;
				}
				$namespaceName = (string) ($namespace['name'] ?? '');
				foreach (($namespace['functions'] ?? []) as $function) {
					if (is_array($function)) {
						$diagnostics = array_merge($diagnostics, $this->collectFunctionPropertyReadDiagnostics($function, $namespaceName, $path, $classLookup, $functionLookup));
					}
				}
				foreach (($namespace['classes'] ?? []) as $class) {
					if (is_array($class)) {
						$diagnostics = array_merge($diagnostics, $this->collectClassMethodPropertyReadDiagnostics($class, $namespaceName, $path, $classLookup, $functionLookup));
					}
				}
			}
		}

		usort($diagnostics, static fn (array $a, array $b): int => strcmp($a['message'], $b['message']));
		return $diagnostics;
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @param list<array<string,mixed>> $symbolIndex @return list<array<string,mixed>> */
	public function collectInitializationDiagnostics(array $fileSummaries, array $symbolIndex): array
	{
		$classCatalog = $this->buildClassCatalog($fileSummaries);
		$classLookup = $this->buildClassLookup($classCatalog);
		$functionLookup = $this->buildFunctionLookup($fileSummaries);
		$diagnostics = [];

		foreach ($fileSummaries as $summary) {
			$path = (string) ($summary['path'] ?? '(unknown)');
			foreach (($summary['root_functions'] ?? []) as $function) {
				if (is_array($function)) {
					$diagnostics = array_merge($diagnostics, $this->collectFunctionInitializationDiagnostics($function, null, $path, $classLookup, $functionLookup));
				}
			}
			foreach (($summary['root_classes'] ?? []) as $class) {
				if (is_array($class)) {
					$diagnostics = array_merge($diagnostics, $this->collectClassMethodInitializationDiagnostics($class, '', $path, $classLookup, $functionLookup));
				}
			}
			foreach (($summary['namespaces'] ?? []) as $namespace) {
				if (!is_array($namespace)) {
					continue;
				}
				$namespaceName = (string) ($namespace['name'] ?? '');
				foreach (($namespace['functions'] ?? []) as $function) {
					if (is_array($function)) {
						$diagnostics = array_merge($diagnostics, $this->collectFunctionInitializationDiagnostics($function, $namespaceName, $path, $classLookup, $functionLookup));
					}
				}
				foreach (($namespace['classes'] ?? []) as $class) {
					if (is_array($class)) {
						$diagnostics = array_merge($diagnostics, $this->collectClassMethodInitializationDiagnostics($class, $namespaceName, $path, $classLookup, $functionLookup));
					}
				}
			}
		}

		usort($diagnostics, static fn (array $a, array $b): int => strcmp($a['message'], $b['message']));
		return $diagnostics;
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @param list<array<string,mixed>> $symbolIndex @return list<array<string,mixed>> */
	public function collectCallSiteDiagnostics(array $fileSummaries, array $symbolIndex): array
	{
		$classCatalog = $this->buildClassCatalog($fileSummaries);
		$classLookup = $this->buildClassLookup($classCatalog);
		$functionCatalog = $this->buildFunctionCatalog($fileSummaries);
		$functionLookup = $this->buildFunctionLookup($fileSummaries);
		$diagnostics = [];

		foreach ($fileSummaries as $summary) {
			$path = (string) ($summary['path'] ?? '(unknown)');
			foreach (($summary['root_functions'] ?? []) as $function) {
				if (is_array($function)) {
					$diagnostics = array_merge($diagnostics, $this->collectFunctionCallSiteDiagnostics($function, null, $path, $classLookup, $functionLookup, $functionCatalog));
				}
			}
			foreach (($summary['root_classes'] ?? []) as $class) {
				if (is_array($class)) {
					$diagnostics = array_merge($diagnostics, $this->collectClassMethodCallSiteDiagnostics($class, '', $path, $classLookup, $functionLookup, $functionCatalog));
				}
			}
			foreach (($summary['namespaces'] ?? []) as $namespace) {
				if (!is_array($namespace)) {
					continue;
				}
				$namespaceName = (string) ($namespace['name'] ?? '');
				foreach (($namespace['functions'] ?? []) as $function) {
					if (is_array($function)) {
						$diagnostics = array_merge($diagnostics, $this->collectFunctionCallSiteDiagnostics($function, $namespaceName, $path, $classLookup, $functionLookup, $functionCatalog));
					}
				}
				foreach (($namespace['classes'] ?? []) as $class) {
					if (is_array($class)) {
						$diagnostics = array_merge($diagnostics, $this->collectClassMethodCallSiteDiagnostics($class, $namespaceName, $path, $classLookup, $functionLookup, $functionCatalog));
					}
				}
			}
		}

		usort($diagnostics, static fn (array $a, array $b): int => strcmp($a['message'], $b['message']));
		return $diagnostics;
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @param list<array<string,mixed>> $symbolIndex @return list<array<string,mixed>> */
	public function collectReturnTypeDiagnostics(array $fileSummaries, array $symbolIndex): array
	{
		$classCatalog = $this->buildClassCatalog($fileSummaries);
		$classLookup = $this->buildClassLookup($classCatalog);
		$functionLookup = $this->buildFunctionLookup($fileSummaries);
		$diagnostics = [];

		foreach ($fileSummaries as $summary) {
			$path = (string) ($summary['path'] ?? '(unknown)');
			foreach (($summary['root_functions'] ?? []) as $function) {
				if (is_array($function)) {
					$diagnostics = array_merge($diagnostics, $this->collectFunctionReturnTypeDiagnostics($function, null, $path, $classLookup, $functionLookup));
				}
			}
			foreach (($summary['root_classes'] ?? []) as $class) {
				if (is_array($class)) {
					$diagnostics = array_merge($diagnostics, $this->collectClassMethodReturnTypeDiagnostics($class, '', $path, $classLookup, $functionLookup));
				}
			}
			foreach (($summary['namespaces'] ?? []) as $namespace) {
				if (!is_array($namespace)) {
					continue;
				}
				$namespaceName = (string) ($namespace['name'] ?? '');
				foreach (($namespace['functions'] ?? []) as $function) {
					if (is_array($function)) {
						$diagnostics = array_merge($diagnostics, $this->collectFunctionReturnTypeDiagnostics($function, $namespaceName, $path, $classLookup, $functionLookup));
					}
				}
				foreach (($namespace['classes'] ?? []) as $class) {
					if (is_array($class)) {
						$diagnostics = array_merge($diagnostics, $this->collectClassMethodReturnTypeDiagnostics($class, $namespaceName, $path, $classLookup, $functionLookup));
					}
				}
			}
		}

		usort($diagnostics, static fn (array $a, array $b): int => strcmp($a['message'], $b['message']));
		return $diagnostics;
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @param list<array<string,mixed>> $symbolIndex @return list<array<string,mixed>> */
	public function collectWrapperBoundaryDiagnostics(array $fileSummaries, array $symbolIndex): array
	{
		$classCatalog = $this->buildClassCatalog($fileSummaries);
		$classLookup = $this->buildClassLookup($classCatalog);
		$functionCatalog = $this->buildFunctionCatalog($fileSummaries);
		$functionLookup = $this->buildFunctionLookup($fileSummaries);
		$diagnostics = [];

		foreach ($fileSummaries as $summary) {
			$path = (string) ($summary['path'] ?? '(unknown)');
			foreach (($summary['root_functions'] ?? []) as $function) {
				if (is_array($function)) {
					$diagnostics = array_merge($diagnostics, $this->collectFunctionWrapperBoundaryDiagnostics($function, null, $path, $classLookup, $functionLookup, $functionCatalog));
				}
			}
			foreach (($summary['root_classes'] ?? []) as $class) {
				if (is_array($class)) {
					$diagnostics = array_merge($diagnostics, $this->collectClassMethodWrapperBoundaryDiagnostics($class, '', $path, $classLookup, $functionLookup, $functionCatalog));
				}
			}
			foreach (($summary['namespaces'] ?? []) as $namespace) {
				if (!is_array($namespace)) {
					continue;
				}
				$namespaceName = (string) ($namespace['name'] ?? '');
				foreach (($namespace['functions'] ?? []) as $function) {
					if (is_array($function)) {
						$diagnostics = array_merge($diagnostics, $this->collectFunctionWrapperBoundaryDiagnostics($function, $namespaceName, $path, $classLookup, $functionLookup, $functionCatalog));
					}
				}
				foreach (($namespace['classes'] ?? []) as $class) {
					if (is_array($class)) {
						$diagnostics = array_merge($diagnostics, $this->collectClassMethodWrapperBoundaryDiagnostics($class, $namespaceName, $path, $classLookup, $functionLookup, $functionCatalog));
					}
				}
			}
		}

		usort($diagnostics, static fn (array $a, array $b): int => strcmp($a['message'], $b['message']));
		return $diagnostics;
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @param list<array<string,mixed>> $symbolIndex @return list<array<string,mixed>> */
	private function collectChainDiagnosticsByField(array $fileSummaries, string $fieldName, array $symbolIndex, string $diagnosticKind): array
	{
		$classCatalog = $this->buildClassCatalog($fileSummaries);
		$classLookup = $this->buildClassLookup($classCatalog);
		$functionLookup = $this->buildFunctionLookup($fileSummaries);
		$diagnostics = [];

		foreach ($fileSummaries as $summary) {
			$path = (string) ($summary['path'] ?? '(unknown)');

			foreach (($summary['root_functions'] ?? []) as $function) {
				if (is_array($function)) {
					$diagnostics = array_merge($diagnostics, $this->collectFunctionChainDiagnostics($function, null, $path, $classLookup, $functionLookup, $fieldName, $diagnosticKind));
				}
			}

			foreach (($summary['root_classes'] ?? []) as $class) {
				if (is_array($class)) {
					$diagnostics = array_merge($diagnostics, $this->collectClassMethodChainDiagnostics($class, '', $path, $classLookup, $functionLookup, $fieldName, $diagnosticKind));
				}
			}

			foreach (($summary['namespaces'] ?? []) as $namespace) {
				if (!is_array($namespace)) {
					continue;
				}
				$namespaceName = (string) ($namespace['name'] ?? '');
				foreach (($namespace['functions'] ?? []) as $function) {
					if (is_array($function)) {
						$diagnostics = array_merge($diagnostics, $this->collectFunctionChainDiagnostics($function, $namespaceName, $path, $classLookup, $functionLookup, $fieldName, $diagnosticKind));
					}
				}
				foreach (($namespace['classes'] ?? []) as $class) {
					if (is_array($class)) {
						$diagnostics = array_merge($diagnostics, $this->collectClassMethodChainDiagnostics($class, $namespaceName, $path, $classLookup, $functionLookup, $fieldName, $diagnosticKind));
					}
				}
			}
		}

		usort($diagnostics, static fn (array $a, array $b): int => strcmp($a['message'], $b['message']));
		return $diagnostics;
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @param list<array<string,mixed>> $symbolIndex @return list<array<string,mixed>> */
	private function resolveChainsByField(array $fileSummaries, string $fieldName, array $symbolIndex, string $kindPrefix): array
	{
		$classCatalog = $this->buildClassCatalog($fileSummaries);
		$classLookup = $this->buildClassLookup($classCatalog);
		$functionLookup = $this->buildFunctionLookup($fileSummaries);
		$observations = [];

		foreach ($fileSummaries as $summary) {
			$path = (string) ($summary['path'] ?? '(unknown)');

			foreach (($summary['root_functions'] ?? []) as $function) {
				if (is_array($function)) {
					$observations = array_merge($observations, $this->resolveFunctionChainsByField($function, null, $path, $classLookup, $functionLookup, $fieldName, $kindPrefix));
				}
			}

			foreach (($summary['root_classes'] ?? []) as $class) {
				if (is_array($class)) {
					$observations = array_merge($observations, $this->resolveClassMethodChainsByField($class, '', $path, $classLookup, $functionLookup, $fieldName, $kindPrefix));
				}
			}

			foreach (($summary['namespaces'] ?? []) as $namespace) {
				if (!is_array($namespace)) {
					continue;
				}
				$namespaceName = (string) ($namespace['name'] ?? '');
				foreach (($namespace['functions'] ?? []) as $function) {
					if (is_array($function)) {
						$observations = array_merge($observations, $this->resolveFunctionChainsByField($function, $namespaceName, $path, $classLookup, $functionLookup, $fieldName, $kindPrefix));
					}
				}
				foreach (($namespace['classes'] ?? []) as $class) {
					if (is_array($class)) {
						$observations = array_merge($observations, $this->resolveClassMethodChainsByField($class, $namespaceName, $path, $classLookup, $functionLookup, $fieldName, $kindPrefix));
					}
				}
			}
		}

		usort($observations, static fn (array $a, array $b): int => strcmp($a['context'], $b['context']));
		return $observations;
	}

	/** @param array<string,mixed> $function @param array<string,array<string,mixed>> $classLookup @return list<array<string,mixed>> */
	private function resolveFunctionChains(array $function, ?string $namespace, string $path, array $classLookup, array $functionLookup): array
	{
		return $this->resolveFunctionChainsByField($function, $namespace, $path, $classLookup, $functionLookup, 'return_chains', 'function_return_chain');
	}

	/** @param array<string,mixed> $function @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @return list<array<string,mixed>> */
	private function resolveFunctionChainsByField(array $function, ?string $namespace, string $path, array $classLookup, array $functionLookup, string $fieldName, string $kind): array
	{
		$baseTypes = $this->buildParamTypeMap($function['params'] ?? []);
		$context = ($namespace !== null && $namespace !== '' ? $namespace . '\\' : '') . (string) ($function['name'] ?? '');
		$analysis = $this->analyzeChainSequence($function, $baseTypes, null, $classLookup, $functionLookup, $context, $path);
		return $this->filterObservationResults($analysis['observations'], $fieldName, $kind);
	}

	/** @param array<string,mixed> $function @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @return list<array<string,mixed>> */
	private function collectFunctionChainDiagnostics(array $function, ?string $namespace, string $path, array $classLookup, array $functionLookup, string $fieldName, string $diagnosticKind): array
	{
		$baseTypes = $this->buildParamTypeMap($function['params'] ?? []);
		$context = ($namespace !== null && $namespace !== '' ? $namespace . '\\' : '') . (string) ($function['name'] ?? '');
		$analysis = $this->analyzeChainSequence($function, $baseTypes, null, $classLookup, $functionLookup, $context, $path);
		return $this->filterDiagnosticResults($analysis['diagnostics'], $fieldName, $diagnosticKind);
	}

	/** @param array<string,mixed> $function @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @return list<array<string,mixed>> */
	private function collectFunctionLocalTypeDiagnostics(array $function, ?string $namespace, string $path, array $classLookup, array $functionLookup): array
	{
		$baseTypes = $this->buildParamTypeMap($function['params'] ?? []);
		$context = ($namespace !== null && $namespace !== '' ? $namespace . '\\' : '') . (string) ($function['name'] ?? '');
		$analysis = $this->analyzeChainSequence($function, $baseTypes, null, $classLookup, $functionLookup, $context, $path);
		return $this->filterLocalTypeDiagnostics($analysis['diagnostics']);
	}

	private function collectFunctionPropertyTypeDiagnostics(array $function, ?string $namespace, string $path, array $classLookup, array $functionLookup): array
	{
		$baseTypes = $this->buildParamTypeMap($function['params'] ?? []);
		$context = ($namespace !== null && $namespace !== '' ? $namespace . '\\' : '') . (string) ($function['name'] ?? '');
		$analysis = $this->analyzeChainSequence($function, $baseTypes, null, $classLookup, $functionLookup, $context, $path);
		return $this->filterPropertyTypeDiagnostics($analysis['diagnostics']);
	}

	private function collectFunctionPropertyReadDiagnostics(array $function, ?string $namespace, string $path, array $classLookup, array $functionLookup): array
	{
		$baseTypes = $this->buildParamTypeMap($function['params'] ?? []);
		$context = ($namespace !== null && $namespace !== '' ? $namespace . '\\' : '') . (string) ($function['name'] ?? '');
		$analysis = $this->analyzeChainSequence($function, $baseTypes, null, $classLookup, $functionLookup, $context, $path);
		return $analysis['property_read_diagnostics'];
	}

	private function collectFunctionInitializationDiagnostics(array $function, ?string $namespace, string $path, array $classLookup, array $functionLookup): array
	{
		$baseTypes = $this->buildParamTypeMap($function['params'] ?? []);
		$context = ($namespace !== null && $namespace !== '' ? $namespace . '\\' : '') . (string) ($function['name'] ?? '');
		$analysis = $this->analyzeChainSequence($function, $baseTypes, null, $classLookup, $functionLookup, $context, $path);
		return $this->filterInitializationDiagnostics($analysis['diagnostics']);
	}

	private function collectFunctionCallSiteDiagnostics(array $function, ?string $namespace, string $path, array $classLookup, array $functionLookup, array $functionCatalog): array
	{
		$baseTypes = $this->buildParamTypeMap($function['params'] ?? []);
		$context = ($namespace !== null && $namespace !== '' ? $namespace . '\\' : '') . (string) ($function['name'] ?? '');
		$analysis = $this->analyzeChainSequence($function, $baseTypes, null, $classLookup, $functionLookup, $context, $path, $functionCatalog);
		return $analysis['call_site_diagnostics'];
	}

	/** @param array<string,mixed> $class @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @return list<array<string,mixed>> */
	private function resolveClassMethodChains(array $class, string $namespace, string $path, array $classLookup, array $functionLookup): array
	{
		return $this->resolveClassMethodChainsByField($class, $namespace, $path, $classLookup, $functionLookup, 'return_chains', 'method_return_chain');
	}

	/** @param array<string,mixed> $class @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @return list<array<string,mixed>> */
	private function resolveClassMethodChainsByField(array $class, string $namespace, string $path, array $classLookup, array $functionLookup, string $fieldName, string $kind): array
	{
		$className = (string) ($class['name'] ?? '');
		$classType = $namespace === '' ? $className : $namespace . '\\' . $className;
		$constructorInitializedProperties = $this->collectConstructorInitializedPropertiesForClass($class, $classType, $classLookup);
		$results = [];
		foreach (($class['methods'] ?? []) as $method) {
			if (!is_array($method)) {
				continue;
			}
			$baseTypes = $this->buildParamTypeMap($method['params'] ?? []);
			$methodName = (string) ($method['name'] ?? '');
			$context = $classType . '::' . $methodName;
			$analysis = $this->analyzeChainSequence($method, $baseTypes, $classType, $classLookup, $functionLookup, $context, $path, null, $this->constructorBaselineForMethod($methodName, $constructorInitializedProperties));
			$results = array_merge($results, $this->filterObservationResults($analysis['observations'], $fieldName, $kind));
		}
		return $results;
	}

	/** @param array<string,mixed> $class @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @return list<array<string,mixed>> */
	private function collectClassMethodChainDiagnostics(array $class, string $namespace, string $path, array $classLookup, array $functionLookup, string $fieldName, string $diagnosticKind): array
	{
		$className = (string) ($class['name'] ?? '');
		$classType = $namespace === '' ? $className : $namespace . '\\' . $className;
		$constructorInitializedProperties = $this->collectConstructorInitializedPropertiesForClass($class, $classType, $classLookup);
		$results = [];
		foreach (($class['methods'] ?? []) as $method) {
			if (!is_array($method)) {
				continue;
			}
			$baseTypes = $this->buildParamTypeMap($method['params'] ?? []);
			$methodName = (string) ($method['name'] ?? '');
			$context = $classType . '::' . $methodName;
			$analysis = $this->analyzeChainSequence($method, $baseTypes, $classType, $classLookup, $functionLookup, $context, $path, null, $this->constructorBaselineForMethod($methodName, $constructorInitializedProperties));
			$results = array_merge($results, $this->filterDiagnosticResults($analysis['diagnostics'], $fieldName, $diagnosticKind));
		}
		return $results;
	}

	/** @param array<string,mixed> $class @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @return list<array<string,mixed>> */
	private function collectClassMethodLocalTypeDiagnostics(array $class, string $namespace, string $path, array $classLookup, array $functionLookup): array
	{
		$className = (string) ($class['name'] ?? '');
		$classType = $namespace === '' ? $className : $namespace . '\\' . $className;
		$constructorInitializedProperties = $this->collectConstructorInitializedPropertiesForClass($class, $classType, $classLookup);
		$results = [];
		foreach (($class['methods'] ?? []) as $method) {
			if (!is_array($method)) {
				continue;
			}
			$baseTypes = $this->buildParamTypeMap($method['params'] ?? []);
			$methodName = (string) ($method['name'] ?? '');
			$context = $classType . '::' . $methodName;
			$analysis = $this->analyzeChainSequence($method, $baseTypes, $classType, $classLookup, $functionLookup, $context, $path, null, $this->constructorBaselineForMethod($methodName, $constructorInitializedProperties));
			$results = array_merge($results, $this->filterLocalTypeDiagnostics($analysis['diagnostics']));
		}
		return $results;
	}

	private function collectClassMethodPropertyTypeDiagnostics(array $class, string $namespace, string $path, array $classLookup, array $functionLookup): array
	{
		$className = (string) ($class['name'] ?? '');
		$classType = $namespace === '' ? $className : $namespace . '\\' . $className;
		$constructorInitializedProperties = $this->collectConstructorInitializedPropertiesForClass($class, $classType, $classLookup);
		$results = [];
		foreach (($class['methods'] ?? []) as $method) {
			if (!is_array($method)) {
				continue;
			}
			$baseTypes = $this->buildParamTypeMap($method['params'] ?? []);
			$methodName = (string) ($method['name'] ?? '');
			$context = $classType . '::' . $methodName;
			$analysis = $this->analyzeChainSequence($method, $baseTypes, $classType, $classLookup, $functionLookup, $context, $path, null, $this->constructorBaselineForMethod($methodName, $constructorInitializedProperties));
			$results = array_merge($results, $this->filterPropertyTypeDiagnostics($analysis['diagnostics']));
		}
		return $results;
	}

	private function collectClassMethodPropertyReadDiagnostics(array $class, string $namespace, string $path, array $classLookup, array $functionLookup): array
	{
		$className = (string) ($class['name'] ?? '');
		$classType = $namespace === '' ? $className : $namespace . '\\' . $className;
		$constructorInitializedProperties = $this->collectConstructorInitializedPropertiesForClass($class, $classType, $classLookup);
		$results = [];
		foreach (($class['methods'] ?? []) as $method) {
			if (!is_array($method)) {
				continue;
			}
			$baseTypes = $this->buildParamTypeMap($method['params'] ?? []);
			$methodName = (string) ($method['name'] ?? '');
			$context = $classType . '::' . $methodName;
			$analysis = $this->analyzeChainSequence($method, $baseTypes, $classType, $classLookup, $functionLookup, $context, $path, null, $this->constructorBaselineForMethod($methodName, $constructorInitializedProperties));
			$results = array_merge($results, $analysis['property_read_diagnostics']);
		}
		return $results;
	}

	private function collectClassMethodInitializationDiagnostics(array $class, string $namespace, string $path, array $classLookup, array $functionLookup): array
	{
		$className = (string) ($class['name'] ?? '');
		$classType = $namespace === '' ? $className : $namespace . '\\' . $className;
		$constructorInitializedProperties = $this->collectConstructorInitializedPropertiesForClass($class, $classType, $classLookup);
		$results = [];
		foreach (($class['methods'] ?? []) as $method) {
			if (!is_array($method)) {
				continue;
			}
			$baseTypes = $this->buildParamTypeMap($method['params'] ?? []);
			$methodName = (string) ($method['name'] ?? '');
			$context = $classType . '::' . $methodName;
			$analysis = $this->analyzeChainSequence($method, $baseTypes, $classType, $classLookup, $functionLookup, $context, $path, null, $this->constructorBaselineForMethod($methodName, $constructorInitializedProperties));
			$results = array_merge($results, $this->filterInitializationDiagnostics($analysis['diagnostics']));
		}
		return $results;
	}

	private function collectClassMethodCallSiteDiagnostics(array $class, string $namespace, string $path, array $classLookup, array $functionLookup, array $functionCatalog): array
	{
		$className = (string) ($class['name'] ?? '');
		$classType = $namespace === '' ? $className : $namespace . '\\' . $className;
		$constructorInitializedProperties = $this->collectConstructorInitializedPropertiesForClass($class, $classType, $classLookup);
		$results = [];
		foreach (($class['methods'] ?? []) as $method) {
			if (!is_array($method)) {
				continue;
			}
			$baseTypes = $this->buildParamTypeMap($method['params'] ?? []);
			$methodName = (string) ($method['name'] ?? '');
			$context = $classType . '::' . $methodName;
			$analysis = $this->analyzeChainSequence($method, $baseTypes, $classType, $classLookup, $functionLookup, $context, $path, $functionCatalog, $this->constructorBaselineForMethod($methodName, $constructorInitializedProperties));
			$results = array_merge($results, $analysis['call_site_diagnostics']);
		}
		return $results;
	}

	private function collectFunctionWrapperBoundaryDiagnostics(array $function, ?string $namespace, string $path, array $classLookup, array $functionLookup, array $functionCatalog): array
	{
		$baseTypes = $this->buildParamTypeMap($function['params'] ?? []);
		$context = ($namespace !== null && $namespace !== '' ? $namespace . '\\' : '') . (string) ($function['name'] ?? '');
		$analysis = $this->analyzeChainSequence($function, $baseTypes, null, $classLookup, $functionLookup, $context, $path);
		return $this->collectWrapperBoundaryDiagnosticsForOwner($function, $analysis['final_local_types'], null, $classLookup, $functionLookup, $functionCatalog, $context, $path);
	}

	private function collectClassMethodWrapperBoundaryDiagnostics(array $class, string $namespace, string $path, array $classLookup, array $functionLookup, array $functionCatalog): array
	{
		$className = (string) ($class['name'] ?? '');
		$classType = $namespace === '' ? $className : $namespace . '\\' . $className;
		$constructorInitializedProperties = $this->collectConstructorInitializedPropertiesForClass($class, $classType, $classLookup);
		$results = [];
		foreach (($class['methods'] ?? []) as $method) {
			if (!is_array($method)) {
				continue;
			}
			$baseTypes = $this->buildParamTypeMap($method['params'] ?? []);
			$methodName = (string) ($method['name'] ?? '');
			$context = $classType . '::' . $methodName;
			$analysis = $this->analyzeChainSequence($method, $baseTypes, $classType, $classLookup, $functionLookup, $context, $path, null, $this->constructorBaselineForMethod($methodName, $constructorInitializedProperties));
			$results = array_merge($results, $this->collectWrapperBoundaryDiagnosticsForOwner($method, $analysis['final_local_types'], $classType, $classLookup, $functionLookup, $functionCatalog, $context, $path));
		}
		return $results;
	}

	private function collectFunctionReturnTypeDiagnostics(array $function, ?string $namespace, string $path, array $classLookup, array $functionLookup): array
	{
		$baseTypes = $this->buildParamTypeMap($function['params'] ?? []);
		$context = ($namespace !== null && $namespace !== '' ? $namespace . '\\' : '') . (string) ($function['name'] ?? '');
		$analysis = $this->analyzeChainSequence($function, $baseTypes, null, $classLookup, $functionLookup, $context, $path);
		return $this->collectReturnDiagnosticsForOwner($function, $analysis['final_local_types'], null, $classLookup, $functionLookup, $context, $path);
	}

	private function collectClassMethodReturnTypeDiagnostics(array $class, string $namespace, string $path, array $classLookup, array $functionLookup): array
	{
		$className = (string) ($class['name'] ?? '');
		$classType = $namespace === '' ? $className : $namespace . '\\' . $className;
		$constructorInitializedProperties = $this->collectConstructorInitializedPropertiesForClass($class, $classType, $classLookup);
		$results = [];
		foreach (($class['methods'] ?? []) as $method) {
			if (!is_array($method)) {
				continue;
			}
			$baseTypes = $this->buildParamTypeMap($method['params'] ?? []);
			$methodName = (string) ($method['name'] ?? '');
			$context = $classType . '::' . $methodName;
			$analysis = $this->analyzeChainSequence($method, $baseTypes, $classType, $classLookup, $functionLookup, $context, $path, null, $this->constructorBaselineForMethod($methodName, $constructorInitializedProperties));
			$results = array_merge($results, $this->collectReturnDiagnosticsForOwner($method, $analysis['final_local_types'], $classType, $classLookup, $functionLookup, $context, $path));
		}
		return $results;
	}

	/** @param list<array<string,mixed>> $params @return array<string,string> */
	private function buildParamTypeMap(array $params): array
	{
		$map = [];
		foreach ($params as $param) {
			if (!is_array($param)) {
				continue;
			}
			$name = (string) ($param['name'] ?? '');
			$type = (string) ($param['primary_type'] ?? $param['type'] ?? '');
			if ($name !== '' && $type !== '') {
				$map[$name] = $type;
			}
		}
		return $map;
	}

	/** @param array<string,string> $paramTypes @return array<string,list<string>> */
	private function buildInitialLocalTypeMap(array $paramTypes): array
	{
		$map = [];
		foreach ($paramTypes as $name => $type) {
			$map[$name] = $this->normalizeTypeSet([$type]);
		}
		return $map;
	}

	/** @param array<string,mixed> $chain @param array<string,string> $paramTypes @param array<string,array<string,mixed>> $classLookup */
	private function resolveChainType(array $chain, array $paramTypes, ?string $selfType, array $classLookup, array $functionLookup): string
	{
		$result = $this->resolveChain($chain, $paramTypes, $selfType, $classLookup, $functionLookup);
		$resolved = $result['resolved_type'] ?? 'unknown';
		return is_array($resolved) ? implode('|', $resolved) : (string) $resolved;
	}

	/** @param array<string,mixed> $chain @param array<string,list<string>> $paramTypes @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @return array<string,mixed> */
	private function resolveChain(array $chain, array $paramTypes, ?string $selfType, array $classLookup, array $functionLookup): array
	{
		$rootName = (string) ($chain['root_name'] ?? '');
		$rootKind = (string) ($chain['root_kind'] ?? 'variable');
		if ($rootName === '') {
			return ['resolved_type' => 'unknown', 'failure_kind' => 'unknown_root', 'failure_segment' => null];
		}

		$currentTypes = [];
		if ($rootKind === 'function_call') {
			$resolved = $functionLookup[strtolower($rootName)] ?? null;
			if (is_string($resolved) && $resolved !== '') {
				$currentTypes = [$resolved];
			}
		} elseif ($rootKind === 'static_call') {
			$className = $this->resolveStaticRootClassName((string) ($chain['root_class'] ?? ''), $selfType, $classLookup);
			$methodName = (string) ($chain['root_method'] ?? '');
			$classInfo = $this->findClassInfo($className, $classLookup);
			$resolved = $classInfo !== null ? (string) ($classInfo['method_return_types'][$methodName] ?? '') : '';
			if ($resolved !== '') {
				$currentTypes = [$resolved];
			}
		} elseif ($rootName === 'this') {
			if ($selfType !== null && $selfType !== '') {
				$currentTypes = [$selfType];
			}
		} elseif (isset($paramTypes[$rootName])) {
			$currentTypes = $paramTypes[$rootName];
		}
		$currentTypes = $this->normalizeTypeSet($currentTypes);
		if ($currentTypes === []) {
			$rootSegment = $rootKind === 'function_call' ? $rootName . '()' : '$' . $rootName;
			if ($rootKind === 'static_call') {
				$rootSegment = $rootName . '()';
			}
			return ['resolved_type' => 'unknown', 'failure_kind' => 'unknown_root_type', 'failure_segment' => $rootSegment];
		}

		foreach (($chain['segments'] ?? []) as $segment) {
			if (!is_array($segment)) {
				return ['resolved_type' => 'unknown', 'failure_kind' => 'invalid_segment', 'failure_segment' => null];
			}
			$name = (string) ($segment['name'] ?? '');
			$kind = (string) ($segment['kind'] ?? '');
			if ($kind !== 'property' && $kind !== 'method') {
				return ['resolved_type' => 'unknown', 'failure_kind' => 'unknown_segment_kind', 'failure_segment' => $this->formatSegment($segment)];
			}

			$nextTypes = [];
			foreach ($currentTypes as $currentType) {
				$receiverType = $this->unwrapMemberReceiverType($currentType);
				$classInfo = $this->findClassInfo($receiverType, $classLookup, $selfType);
				if ($classInfo === null) {
					return [
						'resolved_type' => 'unknown',
						'failure_kind' => $this->isKnownNonObjectType($receiverType) ? 'non_object_receiver_type' : 'unknown_receiver_type',
						'failure_segment' => $this->formatSegment($segment),
						'receiver_type' => $currentType,
					];
				}
				if ($kind === 'property') {
					$nextType = (string) ($classInfo['property_types'][$name] ?? '');
					if ($nextType === '') {
						return ['resolved_type' => 'unknown', 'failure_kind' => 'missing_property', 'failure_segment' => $this->formatSegment($segment), 'receiver_type' => $currentType];
					}
					$nextTypes[] = $nextType;
					continue;
				}

				$nextType = (string) ($classInfo['method_return_types'][$name] ?? '');
				if ($nextType === '') {
					return ['resolved_type' => 'unknown', 'failure_kind' => 'missing_method_or_return_type', 'failure_segment' => $this->formatSegment($segment), 'receiver_type' => $currentType];
				}
				$nextTypes[] = $nextType;
			}
			$currentTypes = $this->normalizeTypeSet($nextTypes);
			if (count($currentTypes) > 1) {
				return [
					'resolved_type' => 'unknown',
					'failure_kind' => 'ambiguous_merged_member_type',
					'failure_segment' => $this->formatSegment($segment),
					'candidate_types' => $currentTypes,
					'member_kind' => $kind,
				];
			}
		}

		$resolvedType = count($currentTypes) === 1 ? $currentTypes[0] : $currentTypes;
		return ['resolved_type' => $resolvedType, 'failure_kind' => null, 'failure_segment' => null];
	}

	/** @param array<string,mixed> $chain */
	private function formatChain(array $chain): string
	{
		$rootName = (string) ($chain['root_name'] ?? '');
		$rootKind = (string) ($chain['root_kind'] ?? 'variable');
		$text = match ($rootKind) {
			'function_call', 'static_call' => $rootName . '()',
			default => '$' . $rootName,
		};
		foreach (($chain['segments'] ?? []) as $segment) {
			if (!is_array($segment)) {
				continue;
			}
			$text .= $segment['kind'] === 'method'
				? '->' . (string) ($segment['name'] ?? '') . '()'
				: '->' . (string) ($segment['name'] ?? '');
		}
		return $text;
	}

	/** @param list<array<string,mixed>> $chains @param array<string,list<string>> $paramTypes @param array<string,array<string,mixed>> $classLookup @return list<array<string,mixed>> */
	private function collectChainDiagnosticsForContext(array $chains, array $paramTypes, ?string $selfType, array $classLookup, array $functionLookup, string $context, string $path, string $diagnosticKind): array
	{
		$diagnostics = [];
		foreach ($chains as $chain) {
			if (!is_array($chain)) {
				continue;
			}
			$result = $this->resolveChain($chain, $paramTypes, $selfType, $classLookup, $functionLookup);
			if (($result['failure_kind'] ?? null) === null) {
				continue;
			}
			$chainText = $this->formatChain($chain);
			$diagnostics[] = [
				'kind' => $diagnosticKind,
				'context' => $context,
				'path' => $path,
				'line' => (int) ($chain['line'] ?? 0),
				'chain' => $chainText,
				'statement_kind' => (string) ($chain['statement_kind'] ?? 'return'),
				'failure_kind' => (string) ($result['failure_kind'] ?? 'unknown'),
				'failure_segment' => $result['failure_segment'] ?? null,
				'receiver_type' => $result['receiver_type'] ?? null,
				'candidate_types' => $result['candidate_types'] ?? null,
				'message' => $this->formatChainFailureMessage($context, $chainText, $result, (string) ($chain['statement_kind'] ?? 'return')),
			];
		}
		return $diagnostics;
	}

	/** @param array<string,mixed> $ownerNode @param array<string,string> $baseTypes @param array<string,array<string,mixed>> $classLookup @param array<string,array<string,mixed>>|null $functionCatalog @param array<string,bool> $constructorInitializedProperties @return array{observations:list<array<string,mixed>>,diagnostics:list<array<string,mixed>>,final_local_types:array<string,list<string>>,call_site_diagnostics:list<array<string,mixed>>,property_read_diagnostics:list<array<string,mixed>>} */
	private function analyzeChainSequence(array $ownerNode, array $baseTypes, ?string $selfType, array $classLookup, array $functionLookup, string $context, string $path, ?array $functionCatalog = null, array $constructorInitializedProperties = []): array
	{
		$localTypes = [];
		foreach ($baseTypes as $baseName => $baseType) {
			$localTypes[$baseName] = $this->canonicalizeTypeSet([$baseType], $classLookup, $selfType);
		}
		$functionCatalog = $functionCatalog ?? [];
		$declaredLocals = [];
		$initializedLocals = [];
		$initializedLocalLines = [];
		foreach (array_keys($baseTypes) as $baseName) {
			$declaredLocals[$baseName] = true;
			$initializedLocals[$baseName] = true;
			$initializedLocalLines[$baseName] = 0;
		}
		$initializedProperties = array_replace($this->buildInitialPropertyInitializationMap($selfType, $classLookup), $constructorInitializedProperties);
		$initializedPropertyLines = [];
		foreach (array_keys($initializedProperties) as $initializedPropertyName) {
			$initializedPropertyLines[$initializedPropertyName] = 0;
		}
		$morphedLocals = [];
		$events = $this->buildChainEvents($ownerNode);
		$typedBoundaryAssignmentKeys = $this->buildTypedBoundaryAssignmentKeys($ownerNode);
		$observations = [];
		$diagnostics = [];
		$callSiteDiagnostics = [];
		$propertyReadDiagnostics = [];
		$initializationKeys = [];

		foreach ($events as $event) {
			if ($event['event_kind'] === 'typed_local') {
				$name = (string) ($event['name'] ?? '');
				if ($name !== '') {
					$declaredLocals[$name] = true;
					$type = (string) ($event['type'] ?? '');
					if ($type !== '') {
						$localTypes[$name] = $this->canonicalizeTypeSet([$type], $classLookup, $selfType);
						$this->recordFixedWidthIntegerLiteralRangeDiagnostic($diagnostics, $name, $type, $event['literal_int_value'] ?? null, $context, $path, (int) ($event['line'] ?? 0));
					}
					if ((bool) ($event['is_initialized'] ?? false)) {
						$this->markLocalInitialized($initializedLocals, $initializedLocalLines, $name, (int) ($event['line'] ?? 0));
					}
				}
				continue;
			}

			if ($event['event_kind'] === 'foreach_local') {
				$name = (string) ($event['name'] ?? '');
				if ($name === '') {
					continue;
				}
				$declaredLocals[$name] = true;
				$source = is_array($event['source'] ?? null) ? $event['source'] : null;
				$resolvedTypes = $this->resolveForeachValueTypes($source, (string) ($event['role'] ?? 'value'), $localTypes, $selfType, $classLookup, $functionLookup);
				if ($resolvedTypes !== []) {
					$this->applyLocalTypeAssignment($localTypes, $morphedLocals, $diagnostics, $name, $resolvedTypes, $context, $path, (int) ($event['line'] ?? 0), $classLookup, $selfType);
				} else {
					unset($morphedLocals[$name]);
					unset($localTypes[$name]);
				}
				$this->markLocalInitialized($initializedLocals, $initializedLocalLines, $name, (int) ($event['line'] ?? 0));
				continue;
			}

			if ($event['event_kind'] === 'for_loop_local') {
				$name = (string) ($event['name'] ?? '');
				if ($name === '') {
					continue;
				}
				$declaredLocals[$name] = true;
				$source = is_array($event['source'] ?? null) ? $event['source'] : null;
				$resolvedTypes = $source !== null
					? $this->resolveExpressionDescriptorTypes($source, $localTypes, $selfType, $classLookup, $functionLookup)
					: [];
				$resolvedTypes = $this->normalizeTypeSet($resolvedTypes);
				if ($resolvedTypes !== []) {
					$this->applyLocalTypeAssignment($localTypes, $morphedLocals, $diagnostics, $name, $resolvedTypes, $context, $path, (int) ($event['line'] ?? 0), $classLookup, $selfType);
				} else {
					unset($morphedLocals[$name]);
					unset($localTypes[$name]);
				}
				$this->markLocalInitialized($initializedLocals, $initializedLocalLines, $name, (int) ($event['line'] ?? 0));
				continue;
			}

			if ($event['event_kind'] === 'non_null_guard') {
				$name = (string) ($event['name'] ?? '');
				if ($name !== '' && isset($localTypes[$name])) {
					$narrowedTypes = $this->removeNullTypes($localTypes[$name]);
					if ($narrowedTypes !== []) {
						$localTypes[$name] = $narrowedTypes;
					}
				}
				continue;
			}

			if ($event['event_kind'] === 'non_false_guard') {
				$name = (string) ($event['name'] ?? '');
				if ($name !== '' && isset($localTypes[$name])) {
					$narrowedTypes = $this->removeFalseTypes($localTypes[$name]);
					if ($narrowedTypes !== []) {
						$localTypes[$name] = $narrowedTypes;
					}
				}
				continue;
			}

			if ($event['event_kind'] === 'local_alias') {
				$target = (string) ($event['target'] ?? '');
				$source = (string) ($event['source'] ?? '');
				if ($target !== '') {
					$declaredLocals[$target] = true;
				}
				if ($target !== '' && $source !== '' && !isset($initializedLocals[$source])) {
					$this->recordInitializationWarning($diagnostics, $initializationKeys, 'maybe_uninitialized_local', $context, $path, (int) ($event['line'] ?? 0), 'Local `$' . $source . '` may be used before initialization in `' . $context . '`.');
					unset($morphedLocals[$target]);
					unset($localTypes[$target]);
					unset($initializedLocals[$target]);
					unset($initializedLocalLines[$target]);
				} elseif ($target !== '' && $source !== '' && isset($localTypes[$source])) {
					$this->applyLocalTypeAssignment($localTypes, $morphedLocals, $diagnostics, $target, $localTypes[$source], $context, $path, (int) ($event['line'] ?? 0), $classLookup, $selfType);
					$this->markLocalInitialized($initializedLocals, $initializedLocalLines, $target, (int) ($event['line'] ?? 0));
				} elseif ($target !== '' && $source !== '' && isset($initializedLocals[$source])) {
					unset($morphedLocals[$target]);
					unset($localTypes[$target]);
					$this->markLocalInitialized($initializedLocals, $initializedLocalLines, $target, (int) ($event['line'] ?? 0));
				} elseif ($target !== '') {
					unset($morphedLocals[$target]);
					unset($localTypes[$target]);
					unset($initializedLocals[$target]);
					unset($initializedLocalLines[$target]);
				}
				continue;
			}

			if ($event['event_kind'] === 'local_literal') {
				$name = (string) ($event['name'] ?? '');
				$type = (string) ($event['type'] ?? '');
				if ($name !== '' && $type !== '') {
					$declaredLocals[$name] = true;
					$this->applyLocalTypeAssignment($localTypes, $morphedLocals, $diagnostics, $name, [$type], $context, $path, (int) ($event['line'] ?? 0), $classLookup, $selfType);
					$this->markLocalInitialized($initializedLocals, $initializedLocalLines, $name, (int) ($event['line'] ?? 0));
				}
				continue;
			}

			if ($event['event_kind'] === 'local_constructed') {
				$name = (string) ($event['name'] ?? '');
				$type = (string) ($event['type'] ?? '');
				if ($name !== '' && $type !== '') {
					$declaredLocals[$name] = true;
					$this->applyLocalTypeAssignment($localTypes, $morphedLocals, $diagnostics, $name, [$type], $context, $path, (int) ($event['line'] ?? 0), $classLookup, $selfType);
					$this->markLocalInitialized($initializedLocals, $initializedLocalLines, $name, (int) ($event['line'] ?? 0));
				}
				continue;
			}

			if ($event['event_kind'] === 'local_descriptor') {
				$name = (string) ($event['name'] ?? '');
				$descriptor = is_array($event['descriptor'] ?? null) ? $event['descriptor'] : null;
				if ($name === '' || $descriptor === null) {
					continue;
				}
				$declaredLocals[$name] = true;
				$resolvedTypes = $this->resolveAssignmentDescriptorTypes($descriptor, $localTypes, $selfType, $classLookup, $functionLookup);
				$resolvedTypes = $this->normalizeTypeSet($resolvedTypes);
				if ($resolvedTypes !== []) {
					$this->applyLocalTypeAssignment($localTypes, $morphedLocals, $diagnostics, $name, $resolvedTypes, $context, $path, (int) ($event['line'] ?? 0), $classLookup, $selfType);
				} else {
					unset($morphedLocals[$name]);
					unset($localTypes[$name]);
				}
				$this->markLocalInitialized($initializedLocals, $initializedLocalLines, $name, (int) ($event['line'] ?? 0));
				continue;
			}

			if ($event['event_kind'] === 'local_branch_merge') {
				$name = (string) ($event['name'] ?? '');
				if ($name === '') {
					continue;
				}
				$hadPriorInitialization = isset($initializedLocals[$name]) && ((int) ($initializedLocalLines[$name] ?? PHP_INT_MIN) < (int) ($event['line'] ?? 0));
				$branchCount = max(0, (int) ($event['branch_count'] ?? count($event['branches'] ?? [])));
				$assignedBranchCount = is_array($event['branches'] ?? null) ? count($event['branches']) : 0;
				$coveredBranchCount = max($assignedBranchCount, (int) ($event['covered_branch_count'] ?? 0));
				$everyBranchAssigns = ($event['has_fallthrough'] ?? false) === false && $branchCount > 0 && $coveredBranchCount === $branchCount;
				if (!$hadPriorInitialization && !$everyBranchAssigns) {
					$this->recordInitializationWarning($diagnostics, $initializationKeys, 'branch_partial_local_initialization', $context, $path, (int) ($event['line'] ?? 0), 'Local `$' . $name . '` is initialized on only some branch paths in `' . $context . '`.');
				}
				$mergedTypes = [];
				if (($event['has_fallthrough'] ?? false) === true && isset($localTypes[$name])) {
					$mergedTypes = array_merge($mergedTypes, $localTypes[$name]);
				}
				if (!$everyBranchAssigns && $hadPriorInitialization && isset($localTypes[$name])) {
					$mergedTypes = array_merge($mergedTypes, $localTypes[$name]);
				}
				foreach (($event['branches'] ?? []) as $branch) {
					if (!is_array($branch)) {
						continue;
					}
					$branchTypes = $this->resolveBranchDescriptorTypes($branch, $localTypes, $selfType, $classLookup, $functionLookup);
					$mergedTypes = array_merge($mergedTypes, $branchTypes);
				}
				$mergedTypes = $this->canonicalizeTypeSet($mergedTypes, $classLookup, $selfType);
				if ($mergedTypes === []) {
					unset($morphedLocals[$name]);
					unset($localTypes[$name]);
					if (!$hadPriorInitialization && !$everyBranchAssigns) {
						unset($initializedLocals[$name]);
						unset($initializedLocalLines[$name]);
					}
				} elseif (count($mergedTypes) > 1) {
					$this->recordLocalTypeMorph($diagnostics, $localTypes, $morphedLocals, $name, $mergedTypes, $context, $path, (int) ($event['line'] ?? 0));
					unset($initializedLocals[$name]);
					unset($initializedLocalLines[$name]);
				} else {
					$declaredLocals[$name] = true;
					$this->applyLocalTypeAssignment($localTypes, $morphedLocals, $diagnostics, $name, $mergedTypes, $context, $path, (int) ($event['line'] ?? 0), $classLookup, $selfType);
					if ($hadPriorInitialization || $everyBranchAssigns) {
						$this->markLocalInitialized($initializedLocals, $initializedLocalLines, $name, (int) ($event['line'] ?? 0));
					} else {
						unset($initializedLocals[$name]);
						unset($initializedLocalLines[$name]);
					}
				}
				continue;
			}

			if ($event['event_kind'] === 'property_branch_merge') {
				$propertyName = (string) ($event['property_name'] ?? '');
				if ($propertyName === '') {
					continue;
				}
				$hadPriorInitialization = (bool) ($initializedProperties[$propertyName] ?? false) && ((int) ($initializedPropertyLines[$propertyName] ?? PHP_INT_MIN) < (int) ($event['line'] ?? 0));
				$branchCount = max(0, (int) ($event['branch_count'] ?? count($event['branches'] ?? [])));
				$assignedBranchCount = is_array($event['branches'] ?? null) ? count($event['branches']) : 0;
				$coveredBranchCount = max($assignedBranchCount, (int) ($event['covered_branch_count'] ?? 0));
				$everyBranchAssigns = ($event['has_fallthrough'] ?? false) === false && $branchCount > 0 && $coveredBranchCount === $branchCount;
				if (!$hadPriorInitialization && !$everyBranchAssigns) {
					$this->recordInitializationWarning($diagnostics, $initializationKeys, 'branch_partial_property_initialization', $context, $path, (int) ($event['line'] ?? 0), 'Property `$this->' . $propertyName . '` is initialized on only some branch paths in `' . $context . '`.');
				}
				foreach (($event['branches'] ?? []) as $branch) {
					if (!is_array($branch)) {
						continue;
					}
					$this->checkDescriptorInitialization($diagnostics, $initializationKeys, $branch, (int) ($event['line'] ?? 0), $context, $path, $declaredLocals, $initializedLocals, $initializedProperties, $selfType, $classLookup);
				}
				if ($hadPriorInitialization || $everyBranchAssigns) {
					$initializedProperties[$propertyName] = true;
					$this->markPropertyInitialized($initializedProperties, $initializedPropertyLines, $propertyName, (int) ($event['line'] ?? 0));
				} else {
					unset($initializedProperties[$propertyName]);
					unset($initializedPropertyLines[$propertyName]);
				}
				continue;
			}

			if ($event['event_kind'] === 'local_invalidation') {
				$name = (string) ($event['name'] ?? '');
				if ($name !== '') {
					$declaredLocals[$name] = true;
					unset($morphedLocals[$name]);
					unset($localTypes[$name]);
					$this->markLocalInitialized($initializedLocals, $initializedLocalLines, $name, (int) ($event['line'] ?? 0));
				}
				continue;
			}

			if ($event['event_kind'] === 'property_assignment') {
				$this->applyPropertyAssignment($diagnostics, $declaredLocals, $localTypes, $initializedLocals, $morphedLocals, $initializedProperties, $initializedPropertyLines, $initializationKeys, $event, $selfType, $classLookup, $functionLookup, $context, $path);
				continue;
			}

			if ($event['event_kind'] === 'static_property_access') {
				$diagnostic = $this->collectStaticPropertyVisibilityDiagnostic($event, $selfType, $classLookup, $context, $path);
				if ($diagnostic !== null) {
					$propertyReadDiagnostics[] = $diagnostic;
				}
				continue;
			}

			if ($event['event_kind'] === 'class_constant_access') {
				$diagnostic = $this->collectClassConstantVisibilityDiagnostic($event, $selfType, $classLookup, $context, $path);
				if ($diagnostic !== null) {
					$propertyReadDiagnostics[] = $diagnostic;
				}
				continue;
			}

			if ($event['event_kind'] === 'call_site_check') {
				$callSite = is_array($event['call_site'] ?? null) ? $event['call_site'] : null;
				$this->checkCallSiteInitialization($diagnostics, $initializationKeys, $callSite, $declaredLocals, $initializedLocals, $initializedProperties, $selfType, $classLookup, $context, $path);
				if ($callSite !== null) {
					$callSiteDiagnostics = array_merge(
						$callSiteDiagnostics,
						$this->collectCallSiteDiagnosticForCallSite($callSite, $localTypes, $selfType, $classLookup, $functionLookup, $functionCatalog, $context, $path)
					);
				}
				continue;
			}

			if ($event['event_kind'] === 'return_value_check') {
				$this->checkDescriptorInitialization($diagnostics, $initializationKeys, is_array($event['descriptor'] ?? null) ? $event['descriptor'] : null, (int) ($event['line'] ?? 0), $context, $path, $declaredLocals, $initializedLocals, $initializedProperties, $selfType, $classLookup);
				continue;
			}

			if ($event['event_kind'] === 'property_read_check') {
				$propertyRead = is_array($event['property_read'] ?? null) ? $event['property_read'] : null;
				if ($propertyRead !== null && is_array($propertyRead['chain'] ?? null)) {
					$this->checkChainInitialization($diagnostics, $initializationKeys, $propertyRead['chain'], (int) ($propertyRead['line'] ?? 0), $context, $path, $declaredLocals, $initializedLocals, $initializedProperties, $selfType, $classLookup);
					$propertyReadDiagnostics = array_merge(
						$propertyReadDiagnostics,
						$this->collectPropertyReadDiagnosticForRead($propertyRead, $localTypes, $selfType, $classLookup, $functionLookup, $context, $path)
					);
				}
				continue;
			}

			$chain = $event['chain'];
			$rootName = (string) ($chain['root_name'] ?? '');
			$this->checkChainInitialization($diagnostics, $initializationKeys, $chain, (int) ($chain['line'] ?? 0), $context, $path, $declaredLocals, $initializedLocals, $initializedProperties, $selfType, $classLookup);
			$hasInitializationIssue = $this->chainMayUseUninitializedValue($chain, $initializedLocals, $initializedProperties);
			$result = isset($morphedLocals[$rootName])
				? ['resolved_type' => 'unknown', 'failure_kind' => 'morphed_local_type', 'failure_segment' => '$' . $rootName]
				: $this->resolveChain($chain, $localTypes, $selfType, $classLookup, $functionLookup);
			$resolvedTypeValue = $result['resolved_type'] ?? 'unknown';
			$resolvedType = is_array($resolvedTypeValue) ? implode('|', $resolvedTypeValue) : (string) $resolvedTypeValue;
			$observations[] = [
				'field' => $event['field'],
				'kind' => $event['field'] === 'return_chains' ? 'method_return_chain' : 'expression_chain',
				'context' => $context,
				'path' => $path,
				'line' => (int) ($chain['line'] ?? 0),
				'chain' => $this->formatChain($chain),
				'resolved_type' => $resolvedType,
				'statement_kind' => (string) ($chain['statement_kind'] ?? 'return'),
			];

			if (($result['failure_kind'] ?? null) !== null && ($result['failure_kind'] ?? null) !== 'morphed_local_type' && !($hasInitializationIssue && ($result['failure_kind'] ?? null) === 'unknown_root_type')) {
				$diagnostics[] = [
					'field' => $event['field'],
					'kind' => $event['field'] === 'return_chains' ? 'return_chain_resolution_warning' : 'expression_chain_resolution_warning',
					'context' => $context,
					'path' => $path,
					'line' => (int) ($chain['line'] ?? 0),
					'chain' => $this->formatChain($chain),
					'statement_kind' => (string) ($chain['statement_kind'] ?? 'return'),
					'failure_kind' => (string) ($result['failure_kind'] ?? 'unknown'),
					'failure_segment' => $result['failure_segment'] ?? null,
					'receiver_type' => $result['receiver_type'] ?? null,
					'candidate_types' => $result['candidate_types'] ?? null,
					'message' => $this->formatChainFailureMessage($context, $this->formatChain($chain), $result, (string) ($chain['statement_kind'] ?? 'return')),
				];
			}

			$assignedVar = isset($chain['assigned_var']) && is_string($chain['assigned_var']) ? $chain['assigned_var'] : null;
			if ($assignedVar !== null && $assignedVar !== '') {
				$declaredLocals[$assignedVar] = true;
				$typedBoundaryKey = (int) ($chain['line'] ?? 0) . '|' . $assignedVar;
				if (isset($typedBoundaryAssignmentKeys[$typedBoundaryKey])) {
					unset($morphedLocals[$assignedVar]);
				} elseif ($resolvedType !== 'unknown') {
					$resolvedSet = $resolvedTypeValue === 'unknown'
						? []
						: (is_array($resolvedTypeValue) ? $resolvedTypeValue : [$resolvedType]);
					$this->applyLocalTypeAssignment($localTypes, $morphedLocals, $diagnostics, $assignedVar, $resolvedSet, $context, $path, (int) ($chain['line'] ?? 0), $classLookup, $selfType);
				} else {
					unset($morphedLocals[$assignedVar]);
					unset($localTypes[$assignedVar]);
				}
				$this->markLocalInitialized($initializedLocals, $initializedLocalLines, $assignedVar, (int) ($chain['line'] ?? 0));
			}
		}

		return [
			'observations' => $observations,
			'diagnostics' => $diagnostics,
			'final_local_types' => $localTypes,
			'call_site_diagnostics' => $callSiteDiagnostics,
			'property_read_diagnostics' => $this->filterPropertyReadDiagnostics($propertyReadDiagnostics),
		];
	}

	/** @param array<string,mixed> $ownerNode @return array<string,bool> */
	private function buildTypedBoundaryAssignmentKeys(array $ownerNode): array
	{
		$keys = [];
		foreach (($ownerNode['typed_boundary_assignments'] ?? []) as $assignment) {
			if (!is_array($assignment)) {
				continue;
			}
			$name = (string) ($assignment['name'] ?? '');
			$line = (int) ($assignment['line'] ?? 0);
			if ($name !== '' && $line > 0) {
				$keys[$line . '|' . $name] = true;
			}
		}
		return $keys;
	}

	/** @param array<string,mixed> $ownerNode @return list<array<string,mixed>> */
	private function buildChainEvents(array $ownerNode): array
	{
		$events = [];
		foreach (($ownerNode['typed_locals'] ?? []) as $typedLocal) {
			if (!is_array($typedLocal)) {
				continue;
			}
			$events[] = [
				'event_kind' => 'typed_local',
				'line' => (int) ($typedLocal['line'] ?? 0),
				'priority' => -1,
				'name' => (string) ($typedLocal['name'] ?? ''),
				'type' => (string) ($typedLocal['type'] ?? ''),
				'is_initialized' => (bool) ($typedLocal['is_initialized'] ?? false),
				'literal_int_value' => $typedLocal['literal_int_value'] ?? null,
			];
		}
		foreach (['expression_chains', 'return_chains'] as $field) {
			foreach (($ownerNode[$field] ?? []) as $chain) {
				if (!is_array($chain)) {
					continue;
				}
				$events[] = [
					'field' => $field,
					'chain' => $chain,
					'line' => (int) ($chain['line'] ?? 0),
					'priority' => $field === 'expression_chains' ? 2 : 3,
				];
			}
		}
		foreach (($ownerNode['local_alias_assignments'] ?? []) as $alias) {
			if (!is_array($alias)) {
				continue;
			}
			$events[] = [
				'event_kind' => 'local_alias',
				'line' => (int) ($alias['line'] ?? 0),
				'priority' => 0,
				'target' => (string) ($alias['target'] ?? ''),
				'source' => (string) ($alias['source'] ?? ''),
			];
		}
		foreach (($ownerNode['local_literal_assignments'] ?? []) as $literal) {
			if (!is_array($literal)) {
				continue;
			}
			$events[] = [
				'event_kind' => 'local_literal',
				'line' => (int) ($literal['line'] ?? 0),
				'priority' => 0,
				'name' => (string) ($literal['name'] ?? ''),
				'type' => (string) ($literal['type'] ?? ''),
			];
		}
		foreach (($ownerNode['local_type_assignments'] ?? []) as $typedAssignment) {
			if (!is_array($typedAssignment)) {
				continue;
			}
			$events[] = [
				'event_kind' => 'local_literal',
				'line' => (int) ($typedAssignment['line'] ?? 0),
				'priority' => 0,
				'name' => (string) ($typedAssignment['name'] ?? ''),
				'type' => (string) ($typedAssignment['type'] ?? ''),
			];
		}
		foreach (($ownerNode['local_constructed_assignments'] ?? []) as $constructed) {
			if (!is_array($constructed)) {
				continue;
			}
			$events[] = [
				'event_kind' => 'local_constructed',
				'line' => (int) ($constructed['line'] ?? 0),
				'priority' => 0,
				'name' => (string) ($constructed['name'] ?? ''),
				'type' => (string) ($constructed['type'] ?? ''),
			];
		}
		foreach (($ownerNode['local_descriptor_assignments'] ?? []) as $descriptorAssignment) {
			if (!is_array($descriptorAssignment)) {
				continue;
			}
			$events[] = [
				'event_kind' => 'local_descriptor',
				'line' => (int) ($descriptorAssignment['line'] ?? 0),
				'priority' => 0,
				'name' => (string) ($descriptorAssignment['name'] ?? ''),
				'descriptor' => $descriptorAssignment['descriptor'] ?? null,
			];
		}
		foreach (($ownerNode['local_branch_assignments'] ?? []) as $branchMerge) {
			if (!is_array($branchMerge)) {
				continue;
			}
			$events[] = [
				'event_kind' => 'local_branch_merge',
				'line' => (int) ($branchMerge['line'] ?? 0),
				'priority' => 1,
				'name' => (string) ($branchMerge['name'] ?? ''),
				'branches' => $branchMerge['branches'] ?? [],
				'branch_count' => (int) ($branchMerge['branch_count'] ?? 0),
				'covered_branch_count' => (int) ($branchMerge['covered_branch_count'] ?? 0),
				'has_fallthrough' => (bool) ($branchMerge['has_fallthrough'] ?? false),
			];
		}
		foreach (($ownerNode['non_null_guards'] ?? []) as $guard) {
			if (!is_array($guard)) {
				continue;
			}
			$events[] = [
				'event_kind' => 'non_null_guard',
				'line' => (int) ($guard['line'] ?? 0),
				'priority' => 1,
				'name' => (string) ($guard['name'] ?? ''),
			];
		}
		foreach (($ownerNode['non_false_guards'] ?? []) as $guard) {
			if (!is_array($guard)) {
				continue;
			}
			$events[] = [
				'event_kind' => 'non_false_guard',
				'line' => (int) ($guard['line'] ?? 0),
				'priority' => 1,
				'name' => (string) ($guard['name'] ?? ''),
			];
		}
		foreach (($ownerNode['foreach_locals'] ?? []) as $foreachLocal) {
			if (!is_array($foreachLocal)) {
				continue;
			}
			$events[] = [
				'event_kind' => 'foreach_local',
				'line' => (int) ($foreachLocal['line'] ?? 0),
				'priority' => 0,
				'name' => (string) ($foreachLocal['name'] ?? ''),
				'role' => (string) ($foreachLocal['role'] ?? 'value'),
				'source' => $foreachLocal['source'] ?? null,
			];
		}
		foreach (($ownerNode['for_loop_locals'] ?? []) as $forLoopLocal) {
			if (!is_array($forLoopLocal)) {
				continue;
			}
			$events[] = [
				'event_kind' => 'for_loop_local',
				'line' => (int) ($forLoopLocal['line'] ?? 0),
				'priority' => 0,
				'name' => (string) ($forLoopLocal['name'] ?? ''),
				'source' => $forLoopLocal['source'] ?? null,
			];
		}
		foreach (($ownerNode['property_branch_assignments'] ?? []) as $propertyBranchMerge) {
			if (!is_array($propertyBranchMerge)) {
				continue;
			}
			$events[] = [
				'event_kind' => 'property_branch_merge',
				'line' => (int) ($propertyBranchMerge['line'] ?? 0),
				'priority' => 1,
				'property_name' => (string) ($propertyBranchMerge['property_name'] ?? ''),
				'branches' => $propertyBranchMerge['branches'] ?? [],
				'branch_count' => (int) ($propertyBranchMerge['branch_count'] ?? 0),
				'covered_branch_count' => (int) ($propertyBranchMerge['covered_branch_count'] ?? 0),
				'has_fallthrough' => (bool) ($propertyBranchMerge['has_fallthrough'] ?? false),
			];
		}
		foreach (($ownerNode['property_assignments'] ?? []) as $propertyAssignment) {
			if (!is_array($propertyAssignment)) {
				continue;
			}
			$events[] = [
				'event_kind' => 'property_assignment',
				'line' => (int) ($propertyAssignment['line'] ?? 0),
				'priority' => 0,
				'target_chain' => $propertyAssignment['target_chain'] ?? null,
				'source' => $propertyAssignment['source'] ?? null,
			];
		}
		foreach (($ownerNode['static_property_assignments'] ?? []) as $staticPropertyAssignment) {
			if (!is_array($staticPropertyAssignment)) {
				continue;
			}
			$events[] = [
				'event_kind' => 'static_property_access',
				'operation' => 'write',
				'line' => (int) ($staticPropertyAssignment['line'] ?? 0),
				'priority' => 0,
				'class_name' => (string) ($staticPropertyAssignment['class_name'] ?? ''),
				'property_name' => (string) ($staticPropertyAssignment['property_name'] ?? ''),
			];
		}
		foreach (($ownerNode['call_sites'] ?? []) as $callSite) {
			if (!is_array($callSite)) {
				continue;
			}
			$events[] = [
				'event_kind' => 'call_site_check',
				'line' => (int) ($callSite['line'] ?? 0),
				'priority' => 3,
				'call_site' => $callSite,
			];
		}
		foreach (($ownerNode['return_values'] ?? []) as $returnValue) {
			if (!is_array($returnValue)) {
				continue;
			}
			$events[] = [
				'event_kind' => 'return_value_check',
				'line' => (int) ($returnValue['line'] ?? 0),
				'priority' => 3,
				'descriptor' => $returnValue['descriptor'] ?? null,
			];
		}
		foreach (($ownerNode['property_reads'] ?? []) as $propertyRead) {
			if (!is_array($propertyRead)) {
				continue;
			}
			$events[] = [
				'event_kind' => 'property_read_check',
				'line' => (int) ($propertyRead['line'] ?? 0),
				'priority' => 3,
				'property_read' => $propertyRead,
			];
		}
		foreach (($ownerNode['static_property_reads'] ?? []) as $staticPropertyRead) {
			if (!is_array($staticPropertyRead)) {
				continue;
			}
			$events[] = [
				'event_kind' => 'static_property_access',
				'operation' => 'read',
				'line' => (int) ($staticPropertyRead['line'] ?? 0),
				'priority' => 3,
				'class_name' => (string) ($staticPropertyRead['class_name'] ?? ''),
				'property_name' => (string) ($staticPropertyRead['property_name'] ?? ''),
			];
		}
		foreach (($ownerNode['class_constant_accesses'] ?? []) as $classConstantAccess) {
			if (!is_array($classConstantAccess)) {
				continue;
			}
			$events[] = [
				'event_kind' => 'class_constant_access',
				'line' => (int) ($classConstantAccess['line'] ?? 0),
				'priority' => 3,
				'class_name' => (string) ($classConstantAccess['class_name'] ?? ''),
				'constant_name' => (string) ($classConstantAccess['constant_name'] ?? ''),
			];
		}
		foreach (($ownerNode['local_invalidations'] ?? []) as $invalidation) {
			if (!is_array($invalidation)) {
				continue;
			}
			$events[] = [
				'event_kind' => 'local_invalidation',
				'line' => (int) ($invalidation['line'] ?? 0),
				'priority' => 3,
				'name' => (string) ($invalidation['name'] ?? ''),
			];
		}
		usort($events, static function (array $a, array $b): int {
			$lineCompare = $a['line'] <=> $b['line'];
			if ($lineCompare !== 0) {
				return $lineCompare;
			}
			return $a['priority'] <=> $b['priority'];
		});
		foreach ($events as &$event) {
			if (!isset($event['event_kind'])) {
				$event['event_kind'] = 'chain';
			}
		}
		unset($event);
		return $events;
	}

	/** @param list<array<string,mixed>> $observations @return list<array<string,mixed>> */
	private function filterObservationResults(array $observations, string $fieldName, string $kind): array
	{
		$results = [];
		foreach ($observations as $observation) {
			if (($observation['field'] ?? null) !== $fieldName) {
				continue;
			}
			$observation['kind'] = $kind;
			unset($observation['field']);
			$results[] = $observation;
		}
		return $results;
	}

	/** @param list<array<string,mixed>> $diagnostics @return list<array<string,mixed>> */
	private function filterDiagnosticResults(array $diagnostics, string $fieldName, string $diagnosticKind): array
	{
		$results = [];
		foreach ($diagnostics as $diagnostic) {
			if (($diagnostic['field'] ?? null) !== $fieldName) {
				continue;
			}
			$diagnostic['kind'] = $diagnosticKind;
			unset($diagnostic['field']);
			$results[] = $diagnostic;
		}
		return $results;
	}

	/** @param list<array<string,mixed>> $diagnostics @return list<array<string,mixed>> */
	private function filterLocalTypeDiagnostics(array $diagnostics): array
	{
		$results = [];
		foreach ($diagnostics as $diagnostic) {
			if (!in_array((string) ($diagnostic['kind'] ?? ''), ['local_type_morph_warning', 'fixed_width_integer_literal_range', 'fixed_width_integer_assignment'], true)) {
				continue;
			}
			$results[] = $diagnostic;
		}
		return $results;
	}

	private function filterPropertyTypeDiagnostics(array $diagnostics): array
	{
		$results = [];
		foreach ($diagnostics as $diagnostic) {
			$kind = (string) ($diagnostic['kind'] ?? '');
			if (!in_array($kind, ['property_type_morph_warning', 'unresolved_property_write', 'member_visibility_violation'], true)) {
				continue;
			}
			$results[] = $diagnostic;
		}
		return $results;
	}

	/** @param list<array<string,mixed>> $diagnostics @return list<array<string,mixed>> */
	private function filterPropertyReadDiagnostics(array $diagnostics): array
	{
		$results = [];
		foreach ($diagnostics as $diagnostic) {
			$kind = (string) ($diagnostic['kind'] ?? '');
			if (!in_array($kind, ['unresolved_property_read', 'invalid_property_read', 'member_visibility_violation'], true)) {
				continue;
			}
			$results[] = $diagnostic;
		}
		return $results;
	}

	/** @param list<array<string,mixed>> $diagnostics @return list<array<string,mixed>> */
	private function filterInitializationDiagnostics(array $diagnostics): array
	{
		$results = [];
		foreach ($diagnostics as $diagnostic) {
			if (($diagnostic['kind'] ?? null) !== 'initialization_warning') {
				continue;
			}
			$results[] = $diagnostic;
		}
		return $results;
	}

	/** @param array<string,mixed> $result */
	private function formatChainFailureMessage(string $context, string $chainText, array $result, string $statementKind): string
	{
		$failureKind = (string) ($result['failure_kind'] ?? 'unknown');
		$segment = isset($result['failure_segment']) && is_string($result['failure_segment']) ? $result['failure_segment'] : null;
		$receiverType = isset($result['receiver_type']) && is_string($result['receiver_type']) ? $result['receiver_type'] : null;
		$candidateTypes = isset($result['candidate_types']) && is_array($result['candidate_types']) ? $result['candidate_types'] : [];
		$statementLabel = $statementKind === 'return' ? 'return chain' : 'expression chain';

		if ($failureKind === 'unknown_root_type') {
			return 'Unknown ' . $statementLabel . ' root type in `' . $context . '` for `' . $chainText . '`.';
		}
		if ($failureKind === 'missing_property') {
			return 'Missing property segment `' . $segment . '` on receiver type `' . $receiverType . '` in `' . $context . '` for ' . $statementLabel . ' `' . $chainText . '`.';
		}
		if ($failureKind === 'missing_method_or_return_type') {
			return 'Missing method or return type for segment `' . $segment . '` on receiver type `' . $receiverType . '` in `' . $context . '` for ' . $statementLabel . ' `' . $chainText . '`.';
		}
		if ($failureKind === 'unknown_receiver_type') {
			return 'Unknown receiver type `' . $receiverType . '` while resolving segment `' . $segment . '` in `' . $context . '` for ' . $statementLabel . ' `' . $chainText . '`.';
		}
		if ($failureKind === 'non_object_receiver_type') {
			if ($receiverType === 'null') {
				return 'Cannot resolve segment `' . $segment . '` on `null` receiver in `' . $context . '` for ' . $statementLabel . ' `' . $chainText . '`.';
			}
			if ($receiverType === 'bool' || $receiverType === 'int' || $receiverType === 'float') {
				return 'Cannot resolve segment `' . $segment . '` on scalar receiver type `' . $receiverType . '` in `' . $context . '` for ' . $statementLabel . ' `' . $chainText . '`.';
			}
			return 'Cannot resolve segment `' . $segment . '` on non-object receiver type `' . $receiverType . '` in `' . $context . '` for ' . $statementLabel . ' `' . $chainText . '`.';
		}
		if ($failureKind === 'ambiguous_merged_member_type') {
			return 'Ambiguous merged member type for segment `' . $segment . '` in `' . $context . '` for ' . $statementLabel . ' `' . $chainText . '`: candidates `'
				. implode('`, `', $candidateTypes) . '`.';
		}
		return 'Unable to resolve ' . $statementLabel . ' `' . $chainText . '` in `' . $context . '`.';
	}

	/** @param array<string,mixed> $segment */
	private function formatSegment(array $segment): string
	{
		$name = (string) ($segment['name'] ?? '');
		$kind = (string) ($segment['kind'] ?? '');
		if ($kind === 'method') {
			return '->' . $name . '()';
		}
		return '->' . $name;
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @return array<string,array<string,mixed>> */
	private function buildClassCatalog(array $fileSummaries): array
	{
		$catalog = [];
		foreach ($fileSummaries as $summary) {
			foreach (($summary['root_classes'] ?? []) as $class) {
				if (is_array($class)) {
					$name = (string) ($class['name'] ?? '');
					if ($name !== '') {
						$catalog[$name] = $this->makeClassInfo($class, '');
					}
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
					$name = (string) ($class['name'] ?? '');
					if ($name !== '') {
						$fqcn = $namespaceName === '' ? $name : $namespaceName . '\\' . $name;
						$catalog[$fqcn] = $this->makeClassInfo($class, $namespaceName);
					}
				}
			}
		}
		foreach (array_keys($catalog) as $fqcn) {
			$this->mergeInheritedMembersIntoCatalog($catalog, $fqcn, []);
		}
		return $catalog;
	}

	/** @param array<string,array<string,mixed>> $catalog @return array<string,array<string,mixed>> */
	private function buildClassLookup(array $catalog): array
	{
		$lookup = [];
		foreach ($catalog as $fqcn => $info) {
			$lookup[strtolower($fqcn)] = $info;
			$short = strtolower((string) ($info['name'] ?? ''));
			if ($short !== '' && !isset($lookup[$short])) {
				$lookup[$short] = $info;
			}
		}
		return $lookup;
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @return array<string,string> */
	private function buildFunctionLookup(array $fileSummaries): array
	{
		$lookup = [];
		foreach ($fileSummaries as $summary) {
			foreach (($summary['root_functions'] ?? []) as $function) {
				if (!is_array($function)) {
					continue;
				}
				$this->addFunctionLookupEntry($lookup, $function, null);
			}
			foreach (($summary['namespaces'] ?? []) as $namespace) {
				if (!is_array($namespace)) {
					continue;
				}
				$namespaceName = (string) ($namespace['name'] ?? '');
				foreach (($namespace['functions'] ?? []) as $function) {
					if (!is_array($function)) {
						continue;
					}
					$this->addFunctionLookupEntry($lookup, $function, $namespaceName);
				}
			}
		}
		$lookup['async_sleep_ms'] = 'void';
		$lookup['async_wait'] = 'mixed';
		return $lookup;
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @return array<string,array<string,mixed>> */
	private function buildFunctionCatalog(array $fileSummaries): array
	{
		$catalog = [];
		foreach ($fileSummaries as $summary) {
			foreach (($summary['root_functions'] ?? []) as $function) {
				if (!is_array($function)) {
					continue;
				}
				$this->addFunctionCatalogEntry($catalog, $function, null);
			}
			foreach (($summary['namespaces'] ?? []) as $namespace) {
				if (!is_array($namespace)) {
					continue;
				}
				$namespaceName = (string) ($namespace['name'] ?? '');
				foreach (($namespace['functions'] ?? []) as $function) {
					if (!is_array($function)) {
						continue;
					}
					$this->addFunctionCatalogEntry($catalog, $function, $namespaceName);
				}
			}
		}
		$catalog['async_sleep_ms'] = [
			'name' => 'async_sleep_ms',
			'namespace' => null,
			'params' => [['name' => 'duration_ms', 'type' => 'int']],
			'return_type' => 'void',
			'line' => 0,
			'is_static' => false,
		];
		$catalog['async_wait'] = [
			'name' => 'async_wait',
			'namespace' => null,
			'params' => [['name' => 'task', 'type' => 'mixed']],
			'return_type' => 'mixed',
			'line' => 0,
			'is_static' => false,
		];
		return $catalog;
	}

	/** @param list<string> $types @return list<string> */
	private function normalizeTypeSet(array $types): array
	{
		$normalized = [];
		foreach ($types as $type) {
			if (!is_string($type)) {
				continue;
			}
			$type = trim($type);
			if ($type === '') {
				continue;
			}
			if (str_contains($type, '|')) {
				foreach (explode('|', $type) as $part) {
					$part = trim($part);
					if ($part === '') {
						continue;
					}
					$normalized[strtolower($part)] = $part;
				}
				continue;
			}
			$normalized[strtolower($type)] = $type;
		}
		return $this->collapseNullableTypeFamily(array_values($normalized));
	}

	/** @param list<string> $types @param array<string,array<string,mixed>> $classLookup @return list<string> */
	private function canonicalizeTypeSet(array $types, array $classLookup, ?string $scopeType): array
	{
		$out = [];
		foreach ($types as $type) {
			if (!is_string($type)) {
				continue;
			}
			$out[] = $this->canonicalizeResolvedType($type, $classLookup, $scopeType);
		}
		return $this->normalizeTypeSet($out);
	}

	private function canonicalizeResolvedType(string $type, array $classLookup, ?string $scopeType): string
	{
		$trimmed = trim($type);
		if ($trimmed === '') {
			return $trimmed;
		}

		$nullableInner = $this->unwrapNullableType($trimmed);
		if ($nullableInner !== null) {
			$canonicalInner = $this->canonicalizeResolvedType($nullableInner, $classLookup, $scopeType);
			return '?' . ltrim($canonicalInner, '\\');
		}

		if (preg_match('/^([a-zA-Z_\\\\][a-zA-Z0-9_\\\\]*)\\s*<\\s*(.+)\\s*>$/', $trimmed, $matches) === 1) {
			$outer = trim((string) $matches[1]);
			$inner = trim((string) $matches[2]);
			return $outer . '<' . $this->canonicalizeResolvedType($inner, $classLookup, $scopeType) . '>';
		}

		$classInfo = $this->findClassInfo($trimmed, $classLookup, $scopeType);
		if ($classInfo !== null) {
			$fqcn = (string) ($classInfo['fqcn'] ?? '');
			if ($fqcn !== '') {
				return $fqcn;
			}
		}

		return $trimmed;
	}

	/** @param list<string> $types @return list<string> */
	private function collapseNullableTypeFamily(array $types): array
	{
		if ($types === []) {
			return [];
		}

		$nullSeen = false;
		$innerTypes = [];
		foreach ($types as $type) {
			$trimmed = trim($type);
			if ($trimmed === '') {
				continue;
			}
			if (strtolower($trimmed) === 'null') {
				$nullSeen = true;
				continue;
			}
			$nullableInner = $this->unwrapNullableType($trimmed);
			if ($nullableInner !== null) {
				$nullSeen = true;
				$innerTypes[] = $nullableInner;
				continue;
			}
			$innerTypes[] = $trimmed;
		}

		$innerTypes = array_values(array_unique($innerTypes));
		if (count($innerTypes) !== 1) {
			return $types;
		}

		$inner = $innerTypes[0];
		$innerLower = strtolower($inner);
		if (in_array($innerLower, ['mixed', 'dynamic', 'unknown', 'void', 'null'], true)) {
			return $types;
		}

		return [$nullSeen ? '?' . ltrim($inner, '\\') : $inner];
	}

	/** @param array<string,mixed> $descriptor @param array<string,list<string>> $localTypes @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @return list<string> */
	private function resolveBranchDescriptorTypes(array $descriptor, array $localTypes, ?string $selfType, array $classLookup, array $functionLookup): array
	{
		$kind = (string) ($descriptor['kind'] ?? '');
		if ($kind === 'alias') {
			$source = (string) ($descriptor['source'] ?? '');
			return $source !== '' ? $this->canonicalizeTypeSet($localTypes[$source] ?? [], $classLookup, $selfType) : [];
		}
		if ($kind === 'element' && is_array($descriptor['source'] ?? null)) {
			return $this->canonicalizeTypeSet(
				$this->resolveContainerElementTypes($descriptor['source'], $localTypes, $selfType, $classLookup, $functionLookup),
				$classLookup,
				$selfType
			);
		}
		if ($kind === 'arithmetic') {
			$leftTypes = is_array($descriptor['left'] ?? null)
				? $this->resolveExpressionDescriptorTypes($descriptor['left'], $localTypes, $selfType, $classLookup, $functionLookup)
				: [];
			$rightTypes = is_array($descriptor['right'] ?? null)
				? $this->resolveExpressionDescriptorTypes($descriptor['right'], $localTypes, $selfType, $classLookup, $functionLookup)
				: [];
			$leftTypes = $this->normalizeTypeSet($leftTypes);
			$rightTypes = $this->normalizeTypeSet($rightTypes);
			if ($leftTypes !== [] && $rightTypes !== []
				&& $this->typeSetsAreCompatible($leftTypes, ['int'], $classLookup, false)
				&& $this->typeSetsAreCompatible($rightTypes, ['int'], $classLookup, false)) {
				return ['int'];
			}
			return [];
		}
		if ($kind === 'conditional') {
			$merged = [];
			if (is_array($descriptor['if_true'] ?? null)) {
				$merged = array_merge($merged, $this->resolveBranchDescriptorTypes($descriptor['if_true'], $localTypes, $selfType, $classLookup, $functionLookup));
			}
			if (is_array($descriptor['if_false'] ?? null)) {
				$merged = array_merge($merged, $this->resolveBranchDescriptorTypes($descriptor['if_false'], $localTypes, $selfType, $classLookup, $functionLookup));
			}
			return $this->normalizeTypeSet($merged);
		}
		if ($kind === 'type') {
			$type = (string) ($descriptor['type'] ?? '');
			return $type !== '' ? [$type] : [];
		}
		if ($kind === 'chain' && isset($descriptor['chain']) && is_array($descriptor['chain'])) {
			$result = $this->resolveChain($descriptor['chain'], $localTypes, $selfType, $classLookup, $functionLookup);
			$resolved = $result['resolved_type'] ?? 'unknown';
			if ($resolved === 'unknown') {
				return [];
			}
			return is_array($resolved) ? $resolved : [$resolved];
		}
		return [];
	}

	private function resolveAssignmentDescriptorTypes(array $descriptor, array $localTypes, ?string $selfType, array $classLookup, array $functionLookup): array
	{
		return $this->resolveBranchDescriptorTypes($descriptor, $localTypes, $selfType, $classLookup, $functionLookup);
	}

	/** @param array<string,mixed> $ownerNode @param array<string,list<string>> $localTypes @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @param array<string,array<string,mixed>> $functionCatalog @return list<array<string,mixed>> */
	private function collectCallSiteDiagnosticsForOwner(array $ownerNode, array $localTypes, ?string $selfType, array $classLookup, array $functionLookup, array $functionCatalog, string $context, string $path): array
	{
		$diagnostics = [];
		foreach (($ownerNode['call_sites'] ?? []) as $callSite) {
			if (!is_array($callSite)) {
				continue;
			}
			$diagnostics = array_merge($diagnostics, $this->collectCallSiteDiagnosticForCallSite($callSite, $localTypes, $selfType, $classLookup, $functionLookup, $functionCatalog, $context, $path));
		}
		return $diagnostics;
	}

	/** @param array<string,mixed> $ownerNode @param array<string,list<string>> $localTypes @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @param array<string,array<string,mixed>> $functionCatalog @return list<array<string,mixed>> */
	private function collectWrapperBoundaryDiagnosticsForOwner(array $ownerNode, array $localTypes, ?string $selfType, array $classLookup, array $functionLookup, array $functionCatalog, string $context, string $path): array
	{
		$diagnostics = [];
		foreach (($ownerNode['typed_boundary_assignments'] ?? []) as $assignment) {
			if (!is_array($assignment)) {
				continue;
			}
			$targetType = (string) ($assignment['target_type'] ?? '');
			if (!$this->isRequiredPlainBoundaryType($targetType)) {
				continue;
			}
			$descriptor = is_array($assignment['descriptor'] ?? null) ? $assignment['descriptor'] : null;
			if ($descriptor === null) {
				continue;
			}
			$sourceTypes = $this->normalizeTypeSet($this->resolveExpressionDescriptorTypes($descriptor, $localTypes, $selfType, $classLookup, $functionLookup));
			$wrapperTypes = array_values(array_filter($sourceTypes, $this->isWrapperCarrierType(...)));
			if ($wrapperTypes !== []) {
				$diagnostics[] = $this->makeCallDiagnostic(
					'unchecked_wrapper_boundary',
					$context,
					$path,
					(int) ($assignment['line'] ?? 0),
					'Unchecked wrapper result assigned to required `' . $targetType . '` local `$' . (string) ($assignment['name'] ?? '') . '` in `' . $context . '`: source `' . $this->formatDescriptor($descriptor) . '` has `' . implode('|', $wrapperTypes) . '`. Use `take(...)`, `isset(...)`, or an explicit false/null/error-state check before the typed boundary.'
				);
				continue;
			}
			$dynamicShapeTypes = $this->resolveDynamicShapeSourceTypes($descriptor, $localTypes, $selfType, $classLookup, $functionLookup, $targetType);
			if ($dynamicShapeTypes !== []) {
				$diagnostics[] = $this->makeCallDiagnostic(
					'dynamic_shape_boundary',
					$context,
					$path,
					(int) ($assignment['line'] ?? 0),
					'Dynamic value assigned to required `' . $targetType . '` local `$' . (string) ($assignment['name'] ?? '') . '` in `' . $context . '`: source `' . $this->formatDescriptor($descriptor) . '` has `' . implode('|', $dynamicShapeTypes) . '`. Guard the field with `isset(...)`, normalize through a typed extraction helper, or assign through an explicitly validated local before the required typed boundary.'
				);
			}
		}
		foreach (($ownerNode['call_sites'] ?? []) as $callSite) {
			if (!is_array($callSite)) {
				continue;
			}
			$diagnostics = array_merge($diagnostics, $this->collectWrapperArgumentDiagnosticsForCallSite($callSite, $localTypes, $selfType, $classLookup, $functionLookup, $functionCatalog, $context, $path));
		}
		$declaredReturnType = (string) ($ownerNode['return_type'] ?? '');
		if ($this->isRequiredPlainBoundaryType($declaredReturnType)) {
			foreach (($ownerNode['return_values'] ?? []) as $returnValue) {
				if (!is_array($returnValue)) {
					continue;
				}
				$descriptor = is_array($returnValue['descriptor'] ?? null) ? $returnValue['descriptor'] : null;
				if ($descriptor === null) {
					continue;
				}
				$sourceTypes = $this->normalizeTypeSet($this->resolveExpressionDescriptorTypes($descriptor, $localTypes, $selfType, $classLookup, $functionLookup));
				$wrapperTypes = array_values(array_filter($sourceTypes, $this->isWrapperCarrierType(...)));
				if ($wrapperTypes === []) {
					continue;
				}
				$diagnostics[] = $this->makeCallDiagnostic(
					'unchecked_wrapper_return',
					$context,
					$path,
					(int) ($returnValue['line'] ?? 0),
					'Unchecked wrapper result returned from required `' . $declaredReturnType . '` function `' . $context . '`: return expression `' . $this->formatDescriptor($descriptor) . '` has `' . implode('|', $wrapperTypes) . '`. Use `take(...)`, `isset(...)`, or an explicit false/null/error-state check before returning.'
				);
			}
		}
		foreach (($ownerNode['property_assignments'] ?? []) as $assignment) {
			if (!is_array($assignment)) {
				continue;
			}
			$targetInfo = $this->resolvePropertyAssignmentBoundary($assignment, $localTypes, $selfType, $classLookup, $functionLookup);
			if ($targetInfo === null || !$this->isRequiredPlainBoundaryType((string) ($targetInfo['type'] ?? ''))) {
				continue;
			}
			$descriptor = is_array($assignment['source'] ?? null) ? $assignment['source'] : null;
			if ($descriptor === null) {
				continue;
			}
			$sourceTypes = $this->normalizeTypeSet($this->resolveAssignmentDescriptorTypes($descriptor, $localTypes, $selfType, $classLookup, $functionLookup));
			$wrapperTypes = array_values(array_filter($sourceTypes, $this->isWrapperCarrierType(...)));
			if ($wrapperTypes === []) {
				continue;
			}
			$diagnostics[] = $this->makeCallDiagnostic(
				'unchecked_wrapper_property_boundary',
				$context,
				$path,
				(int) ($assignment['line'] ?? 0),
				'Unchecked wrapper result assigned to required `' . (string) $targetInfo['type'] . '` property `' . (string) $targetInfo['label'] . '` in `' . $context . '`: source `' . $this->formatDescriptor($descriptor) . '` has `' . implode('|', $wrapperTypes) . '`. Use `take(...)`, `isset(...)`, or an explicit false/null/error-state check before the property write.'
			);
		}
		return $diagnostics;
	}

	/** @param array<string,mixed> $assignment @param array<string,list<string>> $localTypes @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @return array{type:string,label:string}|null */
	private function resolvePropertyAssignmentBoundary(array $assignment, array $localTypes, ?string $selfType, array $classLookup, array $functionLookup): ?array
	{
		$targetChain = is_array($assignment['target_chain'] ?? null) ? $assignment['target_chain'] : null;
		if ($targetChain === null) {
			return null;
		}
		$segments = $targetChain['segments'] ?? [];
		if (!is_array($segments) || $segments === []) {
			return null;
		}
		$propertySegment = $segments[count($segments) - 1] ?? null;
		if (!is_array($propertySegment) || ($propertySegment['kind'] ?? '') !== 'property') {
			return null;
		}
		$propertyName = (string) ($propertySegment['name'] ?? '');
		if ($propertyName === '') {
			return null;
		}
		$receiverChain = $targetChain;
		array_pop($receiverChain['segments']);
		$receiverTypes = $this->resolveReceiverTypesForPropertyChain($receiverChain, $localTypes, $selfType, $classLookup, $functionLookup);
		if (count($receiverTypes) !== 1) {
			return null;
		}
		$receiverType = $receiverTypes[0];
		$receiverInfo = $this->findClassInfo($receiverType, $classLookup, $selfType);
		if ($receiverInfo === null) {
			return null;
		}
		$declaredType = (string) ($receiverInfo['property_types'][$propertyName] ?? '');
		if ($declaredType === '') {
			return null;
		}
		return [
			'type' => $declaredType,
			'label' => $receiverType . '::$' . $propertyName,
		];
	}

	/** @param array<string,mixed> $callSite @param array<string,list<string>> $localTypes @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @param array<string,array<string,mixed>> $functionCatalog @return list<array<string,mixed>> */
	private function collectWrapperArgumentDiagnosticsForCallSite(array $callSite, array $localTypes, ?string $selfType, array $classLookup, array $functionLookup, array $functionCatalog, string $context, string $path): array
	{
		$signature = $this->resolveCallSiteSignature($callSite, $localTypes, $selfType, $classLookup, $functionLookup, $functionCatalog);
		if ($signature === null) {
			return [];
		}
		$args = is_array($callSite['args'] ?? null) ? $callSite['args'] : [];
		$params = is_array($signature['params'] ?? null) ? $signature['params'] : [];
		$targetText = (string) ($signature['target_text'] ?? 'call');
		$diagnostics = [];
		$limit = min(count($args), count($params));
		for ($index = 0; $index < $limit; $index++) {
			$param = $params[$index];
			if (!is_array($param)) {
				continue;
			}
			$expectedType = (string) ($param['primary_type'] ?? $param['type'] ?? '');
			if (!$this->isRequiredPlainBoundaryType($expectedType)) {
				continue;
			}
			$descriptor = is_array($args[$index] ?? null) ? $args[$index] : null;
			if ($descriptor === null) {
				continue;
			}
			$sourceTypes = $this->normalizeTypeSet($this->resolveExpressionDescriptorTypes($descriptor, $localTypes, $selfType, $classLookup, $functionLookup));
			$wrapperTypes = array_values(array_filter($sourceTypes, $this->isWrapperCarrierType(...)));
			if ($wrapperTypes === []) {
				continue;
			}
			$diagnostics[] = $this->makeCallDiagnostic(
				'unchecked_wrapper_argument',
				$context,
				$path,
				(int) ($callSite['line'] ?? 0),
				'Unchecked wrapper result passed to required `' . $expectedType . '` parameter $' . (string) ($param['name'] ?? ('arg' . $index)) . ' of `' . $targetText . '` in `' . $context . '`: argument `' . $this->formatDescriptor($descriptor) . '` has `' . implode('|', $wrapperTypes) . '`. Use `take(...)`, `isset(...)`, or an explicit false/null/error-state check before the call.'
			);
		}
		return $diagnostics;
	}

	/** @param array<string,mixed> $callSite @param array<string,list<string>> $localTypes @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @param array<string,array<string,mixed>> $functionCatalog @return array<string,mixed>|null */
	private function resolveCallSiteSignature(array $callSite, array $localTypes, ?string $selfType, array $classLookup, array $functionLookup, array $functionCatalog): ?array
	{
		$callKind = (string) ($callSite['call_kind'] ?? '');
		if ($callKind === 'function') {
			$name = strtolower((string) ($callSite['name'] ?? ''));
			$signature = $functionCatalog[$name] ?? null;
			if (!is_array($signature)) {
				return null;
			}
			$signature['target_text'] = (string) ($callSite['name'] ?? '') . '()';
			return $signature;
		}
		if ($callKind === 'static_method') {
			$className = (string) ($callSite['class_name'] ?? '');
			$resolvedClassName = $this->resolveStaticRootClassName($className, $selfType, $classLookup);
			$methodName = (string) ($callSite['method_name'] ?? '');
			$runtimeSignature = $this->resolveRuntimeStaticCallSignature($resolvedClassName, $methodName, $functionCatalog);
			if ($runtimeSignature !== null) {
				$runtimeSignature['target_text'] = $resolvedClassName . '::' . $methodName . '()';
				return $runtimeSignature;
			}
			$classInfo = $this->findClassInfo($resolvedClassName, $classLookup);
			if ($classInfo === null) {
				return null;
			}
			$signature = $this->findMethodSignature($classInfo, $methodName);
			if ($signature === null || !(bool) ($signature['is_static'] ?? false)) {
				return null;
			}
			$visibility = $this->normalizeMemberVisibility((string) ($signature['visibility'] ?? 'public'));
			if (!$this->memberAccessAllowed($visibility, $classInfo, $selfType, $classLookup)) {
				return null;
			}
			$signature['target_text'] = $resolvedClassName . '::' . $methodName . '()';
			return $signature;
		}
		if ($callKind === 'method') {
			$receiverDescriptor = $callSite['receiver'] ?? null;
			$receiverTypes = is_array($receiverDescriptor)
				? $this->resolveExpressionDescriptorTypes($receiverDescriptor, $localTypes, $selfType, $classLookup, $functionLookup)
				: [];
			$receiverTypes = $this->normalizeTypeSet($receiverTypes);
			$methodName = (string) ($callSite['method_name'] ?? '');
			if (count($receiverTypes) !== 1) {
				return null;
			}
			$receiverType = $this->unwrapMemberReceiverType($receiverTypes[0]);
			$classInfo = $this->findClassInfo($receiverType, $classLookup);
			if ($classInfo === null) {
				return null;
			}
			$signature = $this->findMethodSignature($classInfo, $methodName);
			if ($signature === null || (bool) ($signature['is_static'] ?? false)) {
				return null;
			}
			$visibility = $this->normalizeMemberVisibility((string) ($signature['visibility'] ?? 'public'));
			if (!$this->memberAccessAllowed($visibility, $classInfo, $selfType, $classLookup)) {
				return null;
			}
			$signature['target_text'] = $receiverType . '->' . $methodName . '()';
			return $signature;
		}
		return null;
	}

	/** @param array<string,mixed> $callSite @param array<string,list<string>> $localTypes @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @param array<string,array<string,mixed>> $functionCatalog @return list<array<string,mixed>> */
	private function collectCallSiteDiagnosticForCallSite(array $callSite, array $localTypes, ?string $selfType, array $classLookup, array $functionLookup, array $functionCatalog, string $context, string $path): array
	{
		$callKind = (string) ($callSite['call_kind'] ?? '');
		if ($callKind === 'function') {
			$name = strtolower((string) ($callSite['name'] ?? ''));
			$signature = $functionCatalog[$name] ?? null;
			if (!is_array($signature)) {
				return [$this->makeCallDiagnostic('unresolved_call', $context, $path, (int) ($callSite['line'] ?? 0), 'Unresolved function call `' . (string) ($callSite['name'] ?? '') . '()` in `' . $context . '`.')];
			}
			return $this->checkSignatureCompatibility($callSite, $signature, $localTypes, $selfType, $classLookup, $functionLookup, $context, $path, (string) ($callSite['name'] ?? '') . '()');
		}

		if ($callKind === 'static_method') {
			$className = (string) ($callSite['class_name'] ?? '');
			$resolvedClassName = $this->resolveStaticRootClassName($className, $selfType, $classLookup);
			$methodName = (string) ($callSite['method_name'] ?? '');
			$runtimeSignature = $this->resolveRuntimeStaticCallSignature($resolvedClassName, $methodName, $functionCatalog);
			if ($runtimeSignature !== null) {
				return $this->checkSignatureCompatibility($callSite, $runtimeSignature, $localTypes, $selfType, $classLookup, $functionLookup, $context, $path, $resolvedClassName . '::' . $methodName . '()');
			}
			$classInfo = $this->findClassInfo($resolvedClassName, $classLookup);
			if ($classInfo === null) {
				return [$this->makeCallDiagnostic('unresolved_static_call', $context, $path, (int) ($callSite['line'] ?? 0), 'Unresolved static call receiver `' . $className . '` in `' . $context . '`.')];
			}
			$methodSignature = $this->findMethodSignature($classInfo, $methodName);
			if ($methodSignature === null) {
				return [$this->makeCallDiagnostic('unresolved_static_call', $context, $path, (int) ($callSite['line'] ?? 0), 'Unresolved static method call `' . $resolvedClassName . '::' . $methodName . '()` in `' . $context . '`.')];
			}
			if (!(bool) ($methodSignature['is_static'] ?? false)) {
				return [$this->makeCallDiagnostic('static_instance_misuse', $context, $path, (int) ($callSite['line'] ?? 0), 'Static call `' . $resolvedClassName . '::' . $methodName . '()` targets a non-static method in `' . $context . '`.')];
			}
			$visibility = $this->normalizeMemberVisibility((string) ($methodSignature['visibility'] ?? 'public'));
			if (!$this->memberAccessAllowed($visibility, $classInfo, $selfType, $classLookup)) {
				return [$this->makeCallDiagnostic('member_visibility_violation', $context, $path, (int) ($callSite['line'] ?? 0), 'Cannot access ' . $visibility . ' method `' . $resolvedClassName . '::' . $methodName . '()` from `' . $context . '`.')];
			}
			return $this->checkSignatureCompatibility($callSite, $methodSignature, $localTypes, $selfType, $classLookup, $functionLookup, $context, $path, $resolvedClassName . '::' . $methodName . '()');
		}

		if ($callKind === 'method') {
			$receiverDescriptor = $callSite['receiver'] ?? null;
			$receiverTypes = is_array($receiverDescriptor)
				? $this->resolveExpressionDescriptorTypes($receiverDescriptor, $localTypes, $selfType, $classLookup, $functionLookup)
				: [];
			$receiverTypes = $this->normalizeTypeSet($receiverTypes);
			$methodName = (string) ($callSite['method_name'] ?? '');
			if (count($receiverTypes) !== 1) {
				return [$this->makeCallDiagnostic('unresolved_method_call', $context, $path, (int) ($callSite['line'] ?? 0), 'Unresolved method call `' . $methodName . '()` in `' . $context . '` due to unknown receiver type.')];
			}
			$receiverType = $this->unwrapMemberReceiverType($receiverTypes[0]);
			$classInfo = $this->findClassInfo($receiverType, $classLookup);
			if ($classInfo === null) {
				return [$this->makeCallDiagnostic('unresolved_method_call', $context, $path, (int) ($callSite['line'] ?? 0), 'Unresolved method call `' . $receiverTypes[0] . '::' . $methodName . '()` in `' . $context . '`.')];
			}
			$methodSignature = $this->findMethodSignature($classInfo, $methodName);
			if ($methodSignature === null) {
				return [$this->makeCallDiagnostic('unresolved_method_call', $context, $path, (int) ($callSite['line'] ?? 0), 'Missing method `' . $receiverType . '::' . $methodName . '()` in `' . $context . '`.')];
			}
			if ((bool) ($methodSignature['is_static'] ?? false)) {
				return [$this->makeCallDiagnostic('static_instance_misuse', $context, $path, (int) ($callSite['line'] ?? 0), 'Instance call `' . $receiverType . '->' . $methodName . '()` targets a static method in `' . $context . '`.')];
			}
			$visibility = $this->normalizeMemberVisibility((string) ($methodSignature['visibility'] ?? 'public'));
			if (!$this->memberAccessAllowed($visibility, $classInfo, $selfType, $classLookup)) {
				return [$this->makeCallDiagnostic('member_visibility_violation', $context, $path, (int) ($callSite['line'] ?? 0), 'Cannot access ' . $visibility . ' method `' . $receiverType . '->' . $methodName . '()` from `' . $context . '`.')];
			}
			return $this->checkSignatureCompatibility($callSite, $methodSignature, $localTypes, $selfType, $classLookup, $functionLookup, $context, $path, $receiverType . '->' . $methodName . '()');
		}

		return [];
	}

	/** @param array<string,array<string,mixed>> $functionCatalog @return array<string,mixed>|null */
	private function resolveRuntimeStaticCallSignature(string $className, string $methodName, array $functionCatalog): ?array
	{
		$symbolName = $this->runtimeStaticTargetSymbols()[strtolower(ltrim($className, '\\') . '::' . $methodName)] ?? null;
		if (!is_string($symbolName) || $symbolName === '') {
			return null;
		}
		$signature = $functionCatalog[strtolower($symbolName)] ?? null;
		return is_array($signature) ? $signature : null;
	}

	/** @return array<string,string> */
	private function runtimeStaticTargetSymbols(): array
	{
		static $symbols = null;
		if (is_array($symbols)) {
			return $symbols;
		}

		$symbols = [];
		foreach ([
			__DIR__ . '/../../specs/php_runtime_symbols_legacy.json',
			__DIR__ . '/../../specs/php_runtime_symbols_strict.json',
		] as $path) {
			if (!is_file($path)) {
				continue;
			}
			$decoded = json_decode((string) file_get_contents($path), true);
			$targets = is_array($decoded['php_runtime_symbol_targets'] ?? null) ? $decoded['php_runtime_symbol_targets'] : [];
			foreach ($targets as $symbolName => $target) {
				if (!is_string($symbolName) || !is_string($target) || !str_contains($target, '::')) {
					continue;
				}
				$symbols[strtolower(ltrim($target, '\\'))] = $symbolName;
			}
		}
		return $symbols;
	}

	/** @param array<string,mixed> $ownerNode @param array<string,list<string>> $localTypes @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @return list<array<string,mixed>> */
	private function collectPropertyReadDiagnosticsForOwner(array $ownerNode, array $localTypes, ?string $selfType, array $classLookup, array $functionLookup, string $context, string $path): array
	{
		$diagnostics = [];
		foreach (($ownerNode['property_reads'] ?? []) as $propertyRead) {
			if (!is_array($propertyRead)) {
				continue;
			}
			$diagnostics = array_merge($diagnostics, $this->collectPropertyReadDiagnosticForRead($propertyRead, $localTypes, $selfType, $classLookup, $functionLookup, $context, $path));
		}
		return $this->filterPropertyReadDiagnostics($diagnostics);
	}

	/** @param array<string,mixed> $propertyRead @param array<string,list<string>> $localTypes @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @return list<array<string,mixed>> */
	private function collectPropertyReadDiagnosticForRead(array $propertyRead, array $localTypes, ?string $selfType, array $classLookup, array $functionLookup, string $context, string $path): array
	{
		$chain = $propertyRead['chain'] ?? null;
		if (!is_array($chain)) {
			return [];
		}
		$chainText = (string) ($propertyRead['chain_text'] ?? $this->formatChain($chain));
		$visibilityDiagnostic = $this->collectPropertyVisibilityDiagnostic($chain, $localTypes, $selfType, $classLookup, $functionLookup, $context, $path, (int) ($propertyRead['line'] ?? 0), $chainText, 'read');
		if ($visibilityDiagnostic !== null) {
			return [$visibilityDiagnostic];
		}
		$result = $this->resolveChain($chain, $localTypes, $selfType, $classLookup, $functionLookup);
		$failureKind = (string) ($result['failure_kind'] ?? '');
		if ($failureKind === '' || $failureKind === 'morphed_local_type') {
			return [];
		}
		$statementKind = (string) ($propertyRead['statement_kind'] ?? 'expr');
		return [[
			'kind' => in_array($failureKind, ['missing_property', 'unknown_root_type', 'unknown_receiver_type'], true) ? 'unresolved_property_read' : 'invalid_property_read',
			'context' => $context,
			'path' => $path,
			'line' => (int) ($propertyRead['line'] ?? 0),
			'chain' => $chainText,
			'statement_kind' => $statementKind,
			'failure_kind' => $failureKind,
			'failure_segment' => $result['failure_segment'] ?? null,
			'receiver_type' => $result['receiver_type'] ?? null,
			'candidate_types' => $result['candidate_types'] ?? null,
			'message' => $this->formatPropertyReadFailureMessage($context, $chainText, $result, $statementKind),
		]];
	}

	/** @param array<string,mixed> $chain @param array<string,list<string>> $localTypes @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @return array<string,mixed>|null */
	private function collectPropertyVisibilityDiagnostic(array $chain, array $localTypes, ?string $selfType, array $classLookup, array $functionLookup, string $context, string $path, int $line, string $chainText, string $operation): ?array
	{
		$segments = $chain['segments'] ?? [];
		if (!is_array($segments) || $segments === []) {
			return null;
		}
		$propertySegment = $segments[count($segments) - 1] ?? null;
		if (!is_array($propertySegment) || (($propertySegment['kind'] ?? '') !== 'property')) {
			return null;
		}
		$receiverChain = $chain;
		array_pop($receiverChain['segments']);
		$receiverTypes = $this->resolveReceiverTypesForPropertyChain($receiverChain, $localTypes, $selfType, $classLookup, $functionLookup);
		if (count($receiverTypes) !== 1) {
			return null;
		}
		$propertyName = (string) ($propertySegment['name'] ?? '');
		$receiverInfo = $this->findClassInfo($receiverTypes[0], $classLookup, $selfType);
		if ($receiverInfo === null || !isset($receiverInfo['property_types'][$propertyName])) {
			return null;
		}
		$visibility = $this->normalizeMemberVisibility((string) ($receiverInfo['property_visibility'][$propertyName] ?? 'public'));
		$declaringInfo = $this->findPropertyDeclaringClassInfo($receiverInfo, $propertyName, $classLookup) ?? $receiverInfo;
		if ($this->memberAccessAllowed($visibility, $declaringInfo, $selfType, $classLookup)) {
			return null;
		}
		return [
			'kind' => 'member_visibility_violation',
			'context' => $context,
			'path' => $path,
			'line' => $line,
			'chain' => $chainText,
			'operation' => $operation,
			'property_name' => $propertyName,
			'receiver_type' => $receiverTypes[0],
			'visibility' => $visibility,
			'message' => 'Cannot ' . $operation . ' ' . $visibility . ' property `' . $receiverTypes[0] . '::$' . $propertyName . '` from `' . $context . '`.',
		];
	}

	/** @param array<string,mixed> $event @param array<string,array<string,mixed>> $classLookup @return array<string,mixed>|null */
	private function collectStaticPropertyVisibilityDiagnostic(array $event, ?string $selfType, array $classLookup, string $context, string $path): ?array
	{
		$className = (string) ($event['class_name'] ?? '');
		$propertyName = (string) ($event['property_name'] ?? '');
		if ($className === '' || $propertyName === '') {
			return null;
		}
		$resolvedClassName = $this->resolveStaticRootClassName($className, $selfType, $classLookup);
		$classInfo = $this->findClassInfo($resolvedClassName, $classLookup, $selfType);
		if ($classInfo === null || !isset($classInfo['property_types'][$propertyName])) {
			return null;
		}
		$visibility = $this->normalizeMemberVisibility((string) ($classInfo['property_visibility'][$propertyName] ?? 'public'));
		$declaringInfo = $this->findPropertyDeclaringClassInfo($classInfo, $propertyName, $classLookup) ?? $classInfo;
		if ($this->memberAccessAllowed($visibility, $declaringInfo, $selfType, $classLookup)) {
			return null;
		}
		$operation = (string) ($event['operation'] ?? 'access');
		if (!in_array($operation, ['read', 'write'], true)) {
			$operation = 'access';
		}
		return [
			'kind' => 'member_visibility_violation',
			'context' => $context,
			'path' => $path,
			'line' => (int) ($event['line'] ?? 0),
			'operation' => $operation,
			'property_name' => $propertyName,
			'receiver_type' => $resolvedClassName,
			'visibility' => $visibility,
			'message' => 'Cannot ' . $operation . ' ' . $visibility . ' static property `' . $resolvedClassName . '::$' . $propertyName . '` from `' . $context . '`.',
		];
	}

	/** @param array<string,mixed> $event @param array<string,array<string,mixed>> $classLookup @return array<string,mixed>|null */
	private function collectClassConstantVisibilityDiagnostic(array $event, ?string $selfType, array $classLookup, string $context, string $path): ?array
	{
		$className = (string) ($event['class_name'] ?? '');
		$constantName = (string) ($event['constant_name'] ?? '');
		if ($className === '' || $constantName === '') {
			return null;
		}
		$resolvedClassName = $this->resolveStaticRootClassName($className, $selfType, $classLookup);
		$classInfo = $this->findClassInfo($resolvedClassName, $classLookup, $selfType);
		if ($classInfo === null || (bool) ($classInfo['is_enum'] ?? false) || !isset($classInfo['constant_visibility'][$constantName])) {
			return null;
		}
		$visibility = $this->normalizeMemberVisibility((string) ($classInfo['constant_visibility'][$constantName] ?? 'public'));
		$declaringInfo = $this->findConstantDeclaringClassInfo($classInfo, $constantName, $classLookup) ?? $classInfo;
		if ($this->memberAccessAllowed($visibility, $declaringInfo, $selfType, $classLookup)) {
			return null;
		}
		return [
			'kind' => 'member_visibility_violation',
			'context' => $context,
			'path' => $path,
			'line' => (int) ($event['line'] ?? 0),
			'operation' => 'read',
			'constant_name' => $constantName,
			'receiver_type' => $resolvedClassName,
			'visibility' => $visibility,
			'message' => 'Cannot read ' . $visibility . ' class constant `' . $resolvedClassName . '::' . $constantName . '` from `' . $context . '`.',
		];
	}

	/** @param array<string,mixed> $receiverChain @param array<string,list<string>> $localTypes @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @return list<string> */
	private function resolveReceiverTypesForPropertyChain(array $receiverChain, array $localTypes, ?string $selfType, array $classLookup, array $functionLookup): array
	{
		if (($receiverChain['segments'] ?? []) === []) {
			$rootName = (string) ($receiverChain['root_name'] ?? '');
			$rootKind = (string) ($receiverChain['root_kind'] ?? 'variable');
			if ($rootKind === 'variable' && $rootName === 'this' && $selfType !== null && $selfType !== '') {
				return [$selfType];
			}
			if ($rootKind === 'variable' && isset($localTypes[$rootName])) {
				return $this->normalizeTypeSet($localTypes[$rootName]);
			}
			return [];
		}
		$resolvedReceiver = $this->resolveChain($receiverChain, $localTypes, $selfType, $classLookup, $functionLookup);
		$resolvedValue = $resolvedReceiver['resolved_type'] ?? 'unknown';
		if ($resolvedValue === 'unknown') {
			return [];
		}
		return $this->normalizeTypeSet(is_array($resolvedValue) ? $resolvedValue : [$resolvedValue]);
	}

	/** @param array<string,mixed> $ownerNode @param array<string,list<string>> $localTypes @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @return list<array<string,mixed>> */
	private function collectReturnDiagnosticsForOwner(array $ownerNode, array $localTypes, ?string $selfType, array $classLookup, array $functionLookup, string $context, string $path): array
	{
		$diagnostics = [];
		$declaredReturnType = (string) ($ownerNode['return_type'] ?? '');
		$statementCount = (int) ($ownerNode['statement_count'] ?? 0);
		if ((bool) ($ownerNode['is_synthetic_entrypoint'] ?? false) === true) {
			return $diagnostics;
		}
		if ($statementCount > 0 && $declaredReturnType !== '' && strtolower($declaredReturnType) !== 'void' && (bool) ($ownerNode['returns_on_all_paths'] ?? false) === false) {
			$diagnostics[] = $this->makeCallDiagnostic('missing_return', $context, $path, (int) ($ownerNode['line'] ?? 0), 'Function `' . $context . '` declared as `' . $declaredReturnType . '` may exit without returning a value.');
		}
		foreach (($ownerNode['return_values'] ?? []) as $returnValue) {
			if (!is_array($returnValue) || $declaredReturnType === '') {
				continue;
			}
			$directCallName = trim((string) ($returnValue['direct_call_name'] ?? ''));
			if ($directCallName !== '' && $this->isDirectSelfCallName($directCallName, $context)) {
				$diagnostics[] = $this->makeCallDiagnostic('direct_self_recursion', $context, $path, (int) ($returnValue['line'] ?? 0), 'Direct self-recursive return in `' . $context . '` has no visible terminating branch.');
			}
			$resolvedTypes = $this->resolveExpressionDescriptorTypes(is_array($returnValue['descriptor'] ?? null) ? $returnValue['descriptor'] : ['kind' => 'unknown'], $localTypes, $selfType, $classLookup, $functionLookup);
			$resolvedTypes = $this->normalizeTypeSet($resolvedTypes);
			if ($resolvedTypes === [] || $this->typeSetsAreCompatible($resolvedTypes, [$declaredReturnType], $classLookup, true)) {
				continue;
			}
			if ($this->isRequiredPlainBoundaryType($declaredReturnType) && array_values(array_filter($resolvedTypes, $this->isWrapperCarrierType(...))) !== []) {
				continue;
			}
			$diagnostics[] = $this->makeCallDiagnostic('return_type_mismatch', $context, $path, (int) ($returnValue['line'] ?? 0), 'Return type mismatch in `' . $context . '`: declared `' . $declaredReturnType . '`, got `' . implode('|', $resolvedTypes) . '`.');
		}
		return $diagnostics;
	}

	private function isDirectSelfCallName(string $callName, string $context): bool
	{
		$callName = strtolower(ltrim(str_replace('\\\\', '\\', trim($callName)), '\\'));
		$context = strtolower(ltrim(str_replace('\\\\', '\\', trim($context)), '\\'));
		if ($callName === '' || $context === '') {
			return false;
		}
		if ($callName === $context) {
			return true;
		}
		$contextParts = preg_split('/\\\\|::/', $context);
		$shortContext = is_array($contextParts) && $contextParts !== [] ? (string) end($contextParts) : $context;
		return $callName === $shortContext;
	}

	/** @param array<string,mixed> $callSite @param array<string,mixed> $signature @param array<string,list<string>> $localTypes @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @return list<array<string,mixed>> */
	private function checkSignatureCompatibility(array $callSite, array $signature, array $localTypes, ?string $selfType, array $classLookup, array $functionLookup, string $context, string $path, string $targetText): array
	{
		$diagnostics = [];
		$args = is_array($callSite['args'] ?? null) ? $callSite['args'] : [];
		$params = is_array($signature['params'] ?? null) ? $signature['params'] : [];
		$requiredCount = 0;
		foreach ($params as $param) {
			if (!is_array($param)) {
				continue;
			}
			if ((bool) ($param['is_variadic'] ?? false)) {
				continue;
			}
			if (!(bool) ($param['has_default'] ?? false)) {
				$requiredCount++;
			}
		}
		$maxCount = null;
		$hasVariadic = false;
		foreach ($params as $param) {
			if (is_array($param) && (bool) ($param['is_variadic'] ?? false)) {
				$hasVariadic = true;
				break;
			}
		}
		if (!$hasVariadic) {
			$maxCount = count($params);
		}
		if (count($args) < $requiredCount || ($maxCount !== null && count($args) > $maxCount)) {
			$expectedText = $maxCount !== null && $maxCount !== $requiredCount
				? $requiredCount . '-' . $maxCount
				: (string) $requiredCount;
			$diagnostics[] = $this->makeCallDiagnostic('argument_count_mismatch', $context, $path, (int) ($callSite['line'] ?? 0), 'Argument count mismatch for `' . $targetText . '` in `' . $context . '`: expected ' . $expectedText . ', got ' . count($args) . '.');
		}
		$limit = min(count($args), count($params));
		for ($index = 0; $index < $limit; $index++) {
			$param = $params[$index];
			if (!is_array($param)) {
				continue;
			}
			$expectedType = (string) ($param['primary_type'] ?? $param['type'] ?? '');
			if ($expectedType === '') {
				continue;
			}
			$actualTypes = $this->resolveExpressionDescriptorTypes(is_array($args[$index] ?? null) ? $args[$index] : ['kind' => 'unknown'], $localTypes, $selfType, $classLookup, $functionLookup);
			$actualTypes = $this->normalizeTypeSet($actualTypes);
			if ($actualTypes === [] || $this->typeSetsAreCompatible($actualTypes, [$expectedType], $classLookup, false)) {
				continue;
			}
			if ($this->isRequiredPlainBoundaryType($expectedType) && array_values(array_filter($actualTypes, $this->isWrapperCarrierType(...))) !== []) {
				continue;
			}
			$diagnostics[] = $this->makeCallDiagnostic('argument_type_mismatch', $context, $path, (int) ($callSite['line'] ?? 0), 'Argument type mismatch for `' . $targetText . '` parameter $' . (string) ($param['name'] ?? ('arg' . $index)) . ' in `' . $context . '`: expected `' . $expectedType . '`, got `' . implode('|', $actualTypes) . '`.');
		}
		return $diagnostics;
	}

	/** @param array<string,mixed> $descriptor @param array<string,list<string>> $localTypes @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @return list<string> */
	private function resolveExpressionDescriptorTypes(array $descriptor, array $localTypes, ?string $selfType, array $classLookup, array $functionLookup): array
	{
		$kind = (string) ($descriptor['kind'] ?? 'unknown');
		if ($kind === 'type') {
			$type = (string) ($descriptor['type'] ?? '');
			return $type !== '' ? [$type] : [];
		}
		if ($kind === 'alias') {
			$source = (string) ($descriptor['source'] ?? '');
			return $source !== '' ? ($localTypes[$source] ?? []) : [];
		}
		if ($kind === 'element' && is_array($descriptor['source'] ?? null)) {
			return $this->resolveContainerElementTypes($descriptor['source'], $localTypes, $selfType, $classLookup, $functionLookup);
		}
		if ($kind === 'conditional') {
			$merged = [];
			if (is_array($descriptor['if_true'] ?? null)) {
				$merged = array_merge($merged, $this->resolveExpressionDescriptorTypes($descriptor['if_true'], $localTypes, $selfType, $classLookup, $functionLookup));
			}
			if (is_array($descriptor['if_false'] ?? null)) {
				$merged = array_merge($merged, $this->resolveExpressionDescriptorTypes($descriptor['if_false'], $localTypes, $selfType, $classLookup, $functionLookup));
			}
			return $this->canonicalizeTypeSet($merged, $classLookup, $selfType);
		}
		if ($kind === 'chain' && is_array($descriptor['chain'] ?? null)) {
			$result = $this->resolveChain($descriptor['chain'], $localTypes, $selfType, $classLookup, $functionLookup);
			$resolved = $result['resolved_type'] ?? 'unknown';
			if ($resolved === 'unknown') {
				return [];
			}
			return $this->canonicalizeTypeSet(is_array($resolved) ? $resolved : [$resolved], $classLookup, $selfType);
		}
		return [];
	}

	private function isRequiredPlainBoundaryType(string $type): bool
	{
		$trimmed = trim($type);
		if ($trimmed === '') {
			return false;
		}
		$lower = strtolower($trimmed);
		if (in_array($lower, ['mixed', 'dynamic', 'void', 'null'], true)) {
			return false;
		}
		return !$this->isWrapperCarrierType($trimmed);
	}

	private function isWrapperCarrierType(string $type): bool
	{
		$trimmed = trim($type);
		if ($trimmed === '') {
			return false;
		}
		if ($this->unwrapNullableType($trimmed) !== null) {
			return true;
		}
		return preg_match('/^(result|result_or_false|result_or_bool|nullable)\s*</i', $trimmed) === 1;
	}

	/** @param array<string,mixed> $descriptor @param array<string,list<string>> $localTypes @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @return list<string> */
	private function resolveDynamicShapeSourceTypes(array $descriptor, array $localTypes, ?string $selfType, array $classLookup, array $functionLookup, ?string $targetType = null): array
	{
		$kind = (string) ($descriptor['kind'] ?? 'unknown');
		if ($kind === 'element' && is_array($descriptor['source'] ?? null)) {
			return $this->filterDynamicShapeTypes($this->resolveExpressionDescriptorTypes($descriptor['source'], $localTypes, $selfType, $classLookup, $functionLookup));
		}
		if ($kind === 'chain' || $kind === 'alias') {
			return $this->filterDynamicShapeTypes($this->resolveExpressionDescriptorTypes($descriptor, $localTypes, $selfType, $classLookup, $functionLookup));
		}
		if ($kind === 'conditional') {
			if ($targetType !== null && $targetType !== '') {
				$trueDescriptor = is_array($descriptor['if_true'] ?? null) ? $descriptor['if_true'] : null;
				$falseDescriptor = is_array($descriptor['if_false'] ?? null) ? $descriptor['if_false'] : null;
				$trueDynamic = $trueDescriptor !== null ? $this->resolveDynamicShapeSourceTypes($trueDescriptor, $localTypes, $selfType, $classLookup, $functionLookup) : [];
				$falseDynamic = $falseDescriptor !== null ? $this->resolveDynamicShapeSourceTypes($falseDescriptor, $localTypes, $selfType, $classLookup, $functionLookup) : [];
				$trueTypes = $trueDescriptor !== null ? $this->resolveExpressionDescriptorTypes($trueDescriptor, $localTypes, $selfType, $classLookup, $functionLookup) : [];
				$falseTypes = $falseDescriptor !== null ? $this->resolveExpressionDescriptorTypes($falseDescriptor, $localTypes, $selfType, $classLookup, $functionLookup) : [];
				if ($trueDynamic !== [] && $falseDynamic === [] && $this->typeSetsAreCompatible($falseTypes, [$targetType], $classLookup, false)) {
					return [];
				}
				if ($falseDynamic !== [] && $trueDynamic === [] && $this->typeSetsAreCompatible($trueTypes, [$targetType], $classLookup, false)) {
					return [];
				}
			}
			$types = [];
			if (is_array($descriptor['if_true'] ?? null)) {
				$types = array_merge($types, $this->resolveDynamicShapeSourceTypes($descriptor['if_true'], $localTypes, $selfType, $classLookup, $functionLookup, $targetType));
			}
			if (is_array($descriptor['if_false'] ?? null)) {
				$types = array_merge($types, $this->resolveDynamicShapeSourceTypes($descriptor['if_false'], $localTypes, $selfType, $classLookup, $functionLookup, $targetType));
			}
			return $this->normalizeTypeSet($types);
		}
		return [];
	}

	/** @param list<string> $types @return list<string> */
	private function filterDynamicShapeTypes(array $types): array
	{
		return array_values(array_filter(
			$this->normalizeTypeSet($types),
			static fn (string $type): bool => in_array(strtolower(trim($type)), ['mixed', 'dynamic'], true)
		));
	}

	/** @param array<string,mixed> $descriptor */
	private function formatDescriptor(array $descriptor): string
	{
		$kind = (string) ($descriptor['kind'] ?? 'unknown');
		if ($kind === 'chain' && is_array($descriptor['chain'] ?? null)) {
			return $this->formatChain($descriptor['chain']);
		}
		if ($kind === 'alias') {
			return '$' . (string) ($descriptor['source'] ?? '');
		}
		if ($kind === 'element') {
			return 'indexed value';
		}
		return $kind;
	}

	/** @param array<string,mixed> $sourceDescriptor @param array<string,list<string>> $localTypes @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @return list<string> */
	private function resolveContainerElementTypes(array $sourceDescriptor, array $localTypes, ?string $selfType, array $classLookup, array $functionLookup): array
	{
		$sourceTypes = $this->resolveExpressionDescriptorTypes($sourceDescriptor, $localTypes, $selfType, $classLookup, $functionLookup);
		$elementTypes = [];
		foreach ($this->normalizeTypeSet($sourceTypes) as $sourceType) {
			if (preg_match('/^vector(?:_t)?<\s*(.+)\s*>$/i', $sourceType, $matches) === 1) {
				$elementTypes[] = trim((string) $matches[1]);
				continue;
			}
			if (preg_match('/^fixed_array(?:_t)?<\s*(.+)\s*>$/i', $sourceType, $matches) === 1) {
				$parts = array_map('trim', explode(',', (string) $matches[1], 2));
				if (($parts[0] ?? '') !== '') {
					$elementTypes[] = $parts[0];
				}
				continue;
			}
			if (preg_match('/^hash(?:_t)?<\s*(.+)\s*>$/i', $sourceType, $matches) === 1) {
				$inner = trim((string) $matches[1]);
				$parts = array_map('trim', explode(',', $inner, 2));
				$elementTypes[] = count($parts) === 2 ? $parts[1] : $parts[0];
			}
		}
		return $this->canonicalizeTypeSet($elementTypes, $classLookup, $selfType);
	}

	private function makeCallDiagnostic(string $kind, string $context, string $path, int $line, string $message): array
	{
		return [
			'kind' => $kind,
			'context' => $context,
			'path' => $path,
			'line' => $line,
			'message' => $message,
		];
	}

	/** @param array<string,list<string>> $localTypes @param array<string,bool> $morphedLocals @param list<array<string,mixed>> $diagnostics @param list<string> $assignedTypes */
	private function applyLocalTypeAssignment(array &$localTypes, array &$morphedLocals, array &$diagnostics, string $name, array $assignedTypes, string $context, string $path, int $line, array $classLookup, ?string $selfType): void
	{
		$assignedTypes = $this->canonicalizeTypeSet($assignedTypes, $classLookup, $selfType);
		if ($assignedTypes === []) {
			unset($morphedLocals[$name]);
			unset($localTypes[$name]);
			return;
		}

		if (count($assignedTypes) > 1) {
			$this->recordLocalTypeMorph($diagnostics, $localTypes, $morphedLocals, $name, $assignedTypes, $context, $path, $line);
			return;
		}

		$currentTypes = $localTypes[$name] ?? [];
		$currentTypes = $this->canonicalizeTypeSet($currentTypes, $classLookup, $selfType);
		if ($currentTypes !== [] && !$this->typeSetsAreCompatible($assignedTypes, $currentTypes, [], false)) {
			if (count($currentTypes) === 1 && count($assignedTypes) === 1 && $this->fixedWidthIntegerInfo($currentTypes[0]) !== null && $this->fixedWidthIntegerInfo($assignedTypes[0]) !== null) {
				$this->recordFixedWidthIntegerAssignmentDiagnostic($diagnostics, $name, $assignedTypes[0], $currentTypes[0], $context, $path, $line);
				unset($morphedLocals[$name]);
				return;
			}
			$this->recordLocalTypeMorph($diagnostics, $localTypes, $morphedLocals, $name, $this->canonicalizeTypeSet(array_merge($currentTypes, $assignedTypes), $classLookup, $selfType), $context, $path, $line);
			return;
		}

		unset($morphedLocals[$name]);
		$localTypes[$name] = $currentTypes !== [] ? $currentTypes : $assignedTypes;
	}

	/** @param list<array<string,mixed>> $diagnostics */
	private function recordFixedWidthIntegerAssignmentDiagnostic(array &$diagnostics, string $name, string $sourceType, string $targetType, string $context, string $path, int $line): void
	{
		$diagnostics[] = [
			'kind' => 'fixed_width_integer_assignment',
			'severity' => 'error',
			'context' => $context,
			'path' => $path,
			'line' => $line,
			'local_name' => $name,
			'source_type' => $sourceType,
			'target_type' => $targetType,
			'message' => 'Fixed-width integer assignment to local `$' . $name . '` in `' . $context . '` requires compatible same-signedness widening: cannot assign `' . $sourceType . '` to `' . $targetType . '`.',
		];
	}

	/** @param array<string,list<string>> $localTypes @param array<string,bool> $morphedLocals @param list<array<string,mixed>> $diagnostics @param list<string> $candidateTypes */
	private function recordLocalTypeMorph(array &$diagnostics, array &$localTypes, array &$morphedLocals, string $name, array $candidateTypes, string $context, string $path, int $line): void
	{
		$candidateTypes = $this->normalizeTypeSet($candidateTypes);
		$diagnostics[] = [
			'kind' => 'local_type_morph_warning',
			'context' => $context,
			'path' => $path,
			'line' => $line,
			'local_name' => $name,
			'candidate_types' => $candidateTypes,
			'message' => 'Local `$' . $name . '` morphs into multiple types in `' . $context . '`: `'
				. implode('`, `', $candidateTypes) . '`. Simple C++ scope locals must stay single-typed.',
		];
		$morphedLocals[$name] = true;
		unset($localTypes[$name]);
	}

	/** @param list<array<string,mixed>> $diagnostics */
	private function recordFixedWidthIntegerLiteralRangeDiagnostic(array &$diagnostics, string $name, string $type, mixed $literalValue, string $context, string $path, int $line): void
	{
		if (!is_int($literalValue)) {
			return;
		}
		$range = $this->fixedWidthIntegerRange($type);
		if ($range === null) {
			return;
		}
		$min = $range['min'];
		$max = $range['max'];
		if ($literalValue >= $min && ($max === null || $literalValue <= $max)) {
			return;
		}
		$targetType = strtolower(ltrim($type, '\\'));
		$diagnostics[] = [
			'kind' => 'fixed_width_integer_literal_range',
			'severity' => 'error',
			'context' => $context,
			'path' => $path,
			'line' => $line,
			'local_name' => $name,
			'target_type' => $targetType,
			'literal_value' => $literalValue,
			'message' => 'Integer literal `' . (string) $literalValue . '` is outside the range of fixed-width type `' . $targetType . '` for local `$' . $name . '` in `' . $context . '`: expected ' . $this->formatFixedWidthRange($min, $max) . '.',
		];
	}

	/** @return array{min:int,max:int|null}|null */
	private function fixedWidthIntegerRange(string $type): ?array
	{
		return match (strtolower(ltrim($type, '\\'))) {
			'int8' => ['min' => -128, 'max' => 127],
			'int16' => ['min' => -32768, 'max' => 32767],
			'int32' => ['min' => -2147483648, 'max' => 2147483647],
			'int64' => ['min' => PHP_INT_MIN, 'max' => PHP_INT_MAX],
			'uint8', 'byte' => ['min' => 0, 'max' => 255],
			'uint16' => ['min' => 0, 'max' => 65535],
			'uint32' => ['min' => 0, 'max' => 4294967295],
			'uint64' => ['min' => 0, 'max' => null],
			default => null,
		};
	}

	private function formatFixedWidthRange(int $min, ?int $max): string
	{
		if ($max === null) {
			return (string) $min . '..18446744073709551615';
		}
		return (string) $min . '..' . (string) $max;
	}

	private function applyPropertyAssignment(array &$diagnostics, array $declaredLocals, array $localTypes, array $initializedLocals, array $morphedLocals, array &$initializedProperties, array &$initializedPropertyLines, array &$initializationKeys, array $event, ?string $selfType, array $classLookup, array $functionLookup, string $context, string $path): void
	{
		$targetChain = $event['target_chain'] ?? null;
		$source = $event['source'] ?? null;
		if (!is_array($targetChain) || !is_array($source)) {
			return;
		}
		$segments = $targetChain['segments'] ?? [];
		if (!is_array($segments) || $segments === []) {
			return;
		}
		$propertySegment = $segments[count($segments) - 1] ?? null;
		if (!is_array($propertySegment) || (($propertySegment['kind'] ?? '') !== 'property')) {
			return;
		}

		$receiverChain = $targetChain;
		array_pop($receiverChain['segments']);
		$receiverTypes = [];
		if (($receiverChain['segments'] ?? []) === []) {
			$rootName = (string) ($receiverChain['root_name'] ?? '');
			$rootKind = (string) ($receiverChain['root_kind'] ?? 'variable');
			if ($rootKind === 'variable' && isset($morphedLocals[$rootName])) {
				return;
			}
			if ($rootKind === 'variable' && $rootName === 'this' && $selfType !== null && $selfType !== '') {
				$receiverTypes = [$selfType];
			} elseif ($rootKind === 'variable' && isset($localTypes[$rootName])) {
				$receiverTypes = $localTypes[$rootName];
			} else {
				$this->recordUnresolvedPropertyWrite($diagnostics, $context, $path, (int) ($event['line'] ?? 0), (string) ($propertySegment['name'] ?? ''), 'Unknown property write receiver in `' . $context . '` for `$' . $rootName . '->' . (string) ($propertySegment['name'] ?? '') . '`.');
				return;
			}
		} else {
			$resolvedReceiver = $this->resolveChain($receiverChain, $localTypes, $selfType, $classLookup, $functionLookup);
			$resolvedValue = $resolvedReceiver['resolved_type'] ?? 'unknown';
			if ($resolvedValue === 'unknown') {
				$this->recordUnresolvedPropertyWrite($diagnostics, $context, $path, (int) ($event['line'] ?? 0), (string) ($propertySegment['name'] ?? ''), 'Unknown property write receiver in `' . $context . '` for `' . $this->formatChain($receiverChain) . '->' . (string) ($propertySegment['name'] ?? '') . '`.');
				return;
			}
			$receiverTypes = is_array($resolvedValue) ? $resolvedValue : [$resolvedValue];
		}
		$receiverTypes = $this->normalizeTypeSet($receiverTypes);
		if (count($receiverTypes) !== 1) {
			$this->recordUnresolvedPropertyWrite($diagnostics, $context, $path, (int) ($event['line'] ?? 0), (string) ($propertySegment['name'] ?? ''), 'Ambiguous property write receiver in `' . $context . '` for property `' . (string) ($propertySegment['name'] ?? '') . '`.');
			return;
		}

		$receiverInfo = $this->findClassInfo($receiverTypes[0], $classLookup, $selfType);
		$propertyName = (string) ($propertySegment['name'] ?? '');
		if ($receiverInfo === null) {
			$this->recordUnresolvedPropertyWrite($diagnostics, $context, $path, (int) ($event['line'] ?? 0), $propertyName, 'Cannot write property `' . $propertyName . '` on non-object or unresolved receiver type `' . $receiverTypes[0] . '` in `' . $context . '`.');
			return;
		}
		$declaredType = (string) ($receiverInfo['property_types'][$propertyName] ?? '');
		if ($declaredType === '') {
			$this->recordUnresolvedPropertyWrite($diagnostics, $context, $path, (int) ($event['line'] ?? 0), $propertyName, 'Missing property write target `' . $receiverTypes[0] . '::$' . $propertyName . '` in `' . $context . '`.');
			return;
		}
		$visibility = $this->normalizeMemberVisibility((string) ($receiverInfo['property_visibility'][$propertyName] ?? 'public'));
		$declaringInfo = $this->findPropertyDeclaringClassInfo($receiverInfo, $propertyName, $classLookup) ?? $receiverInfo;
		if (!$this->memberAccessAllowed($visibility, $declaringInfo, $selfType, $classLookup)) {
			$diagnostics[] = [
				'kind' => 'member_visibility_violation',
				'context' => $context,
				'path' => $path,
				'line' => (int) ($event['line'] ?? 0),
				'property_name' => $propertyName,
				'receiver_type' => $receiverTypes[0],
				'visibility' => $visibility,
				'operation' => 'write',
				'message' => 'Cannot write ' . $visibility . ' property `' . $receiverTypes[0] . '::$' . $propertyName . '` from `' . $context . '`.',
			];
			return;
		}
		$declaredType = $this->canonicalizeResolvedType($declaredType, $classLookup, $selfType);

		$this->checkDescriptorInitialization($diagnostics, $initializationKeys, $source, (int) ($event['line'] ?? 0), $context, $path, $declaredLocals, $initializedLocals, $initializedProperties, $selfType, $classLookup);
		$assignedTypes = $this->canonicalizeTypeSet($this->resolveAssignmentDescriptorTypes($source, $localTypes, $selfType, $classLookup, $functionLookup), $classLookup, $selfType);
		if ($assignedTypes === []) {
			if ($this->isDirectSelfPropertyTarget($targetChain, $propertyName)) {
				$this->markPropertyInitialized($initializedProperties, $initializedPropertyLines, $propertyName, (int) ($event['line'] ?? 0));
			}
			return;
		}
		if (!$this->typeSetsAreCompatible($assignedTypes, [$declaredType], $classLookup, false)) {
			if ($this->isRequiredPlainBoundaryType($declaredType) && array_values(array_filter($assignedTypes, $this->isWrapperCarrierType(...))) !== []) {
				if ($this->isDirectSelfPropertyTarget($targetChain, $propertyName)) {
					$this->markPropertyInitialized($initializedProperties, $initializedPropertyLines, $propertyName, (int) ($event['line'] ?? 0));
				}
				return;
			}
			$diagnostics[] = [
				'kind' => 'property_type_morph_warning',
				'context' => $context,
				'path' => $path,
				'line' => (int) ($event['line'] ?? 0),
				'property_name' => $propertyName,
				'receiver_type' => $receiverTypes[0],
				'declared_type' => $declaredType,
				'assigned_types' => $assignedTypes,
				'message' => 'Property `' . $receiverTypes[0] . '::$' . $propertyName . '` in `' . $context . '` is declared as `'
					. $declaredType . '` but assigned `'
					. implode('`, `', $assignedTypes) . '`. Simple C++ properties must stay single-typed.',
			];
		}
		if ($this->isDirectSelfPropertyTarget($targetChain, $propertyName)) {
			$this->markPropertyInitialized($initializedProperties, $initializedPropertyLines, $propertyName, (int) ($event['line'] ?? 0));
		}
	}

	private function recordUnresolvedPropertyWrite(array &$diagnostics, string $context, string $path, int $line, string $propertyName, string $message): void
	{
		$diagnostics[] = [
			'kind' => 'unresolved_property_write',
			'context' => $context,
			'path' => $path,
			'line' => $line,
			'property_name' => $propertyName,
			'message' => $message,
		];
	}

	/** @param array<string,mixed>|null $callSite @param array<string,bool> $declaredLocals @param array<string,bool> $initializedLocals @param array<string,bool> $initializedProperties @param array<string,array<string,mixed>> $classLookup */
	private function checkCallSiteInitialization(array &$diagnostics, array &$initializationKeys, ?array $callSite, array $declaredLocals, array $initializedLocals, array $initializedProperties, ?string $selfType, array $classLookup, string $context, string $path): void
	{
		if ($callSite === null) {
			return;
		}
		if (is_array($callSite['receiver'] ?? null)) {
			$this->checkDescriptorInitialization($diagnostics, $initializationKeys, $callSite['receiver'], (int) ($callSite['line'] ?? 0), $context, $path, $declaredLocals, $initializedLocals, $initializedProperties, $selfType, $classLookup);
		}
		foreach (($callSite['args'] ?? []) as $arg) {
			if (!is_array($arg)) {
				continue;
			}
			$this->checkDescriptorInitialization($diagnostics, $initializationKeys, $arg, (int) ($callSite['line'] ?? 0), $context, $path, $declaredLocals, $initializedLocals, $initializedProperties, $selfType, $classLookup);
		}
	}

	/** @param array<string,mixed>|null $descriptor @param array<string,bool> $declaredLocals @param array<string,bool> $initializedLocals @param array<string,bool> $initializedProperties @param array<string,array<string,mixed>> $classLookup */
	private function checkDescriptorInitialization(array &$diagnostics, array &$initializationKeys, ?array $descriptor, int $line, string $context, string $path, array $declaredLocals, array $initializedLocals, array $initializedProperties, ?string $selfType, array $classLookup): void
	{
		if ($descriptor === null) {
			return;
		}
		$kind = (string) ($descriptor['kind'] ?? 'unknown');
		if ($kind === 'alias') {
			$source = (string) ($descriptor['source'] ?? '');
			if ($source !== '' && !isset($initializedLocals[$source]) && !$this->hasPartialBranchInitializationWarning($initializationKeys, $context, $path, 'local', $source)) {
				$this->recordInitializationWarning($diagnostics, $initializationKeys, 'maybe_uninitialized_local', $context, $path, $line, 'Local `$' . $source . '` may be used before initialization in `' . $context . '`.');
			}
			return;
		}
		if ($kind === 'chain' && is_array($descriptor['chain'] ?? null)) {
			$this->checkChainInitialization($diagnostics, $initializationKeys, $descriptor['chain'], $line, $context, $path, $declaredLocals, $initializedLocals, $initializedProperties, $selfType, $classLookup);
		}
	}

	/** @param array<string,mixed> $chain @param array<string,bool> $declaredLocals @param array<string,bool> $initializedLocals @param array<string,bool> $initializedProperties @param array<string,array<string,mixed>> $classLookup */
	private function checkChainInitialization(array &$diagnostics, array &$initializationKeys, array $chain, int $line, string $context, string $path, array $declaredLocals, array $initializedLocals, array $initializedProperties, ?string $selfType, array $classLookup): void
	{
		$rootKind = (string) ($chain['root_kind'] ?? 'variable');
		$rootName = (string) ($chain['root_name'] ?? '');
		if ($rootKind === 'variable' && $rootName !== '' && $rootName !== 'this' && !isset($initializedLocals[$rootName]) && !$this->hasPartialBranchInitializationWarning($initializationKeys, $context, $path, 'local', $rootName)) {
			$this->recordInitializationWarning($diagnostics, $initializationKeys, 'maybe_uninitialized_local', $context, $path, $line, 'Local `$' . $rootName . '` may be used before initialization in `' . $context . '`.');
			return;
		}

		$segments = $chain['segments'] ?? [];
		if (!is_array($segments) || $segments === []) {
			return;
		}
		$firstSegment = $segments[0] ?? null;
		if ($rootKind === 'variable' && $rootName === 'this' && is_array($firstSegment) && (($firstSegment['kind'] ?? '') === 'property')) {
			$propertyName = (string) ($firstSegment['name'] ?? '');
			if ($propertyName !== '' && !($initializedProperties[$propertyName] ?? false)) {
		$classInfo = $selfType !== null ? $this->findClassInfo($selfType, $classLookup, $selfType) : null;
				$hasDefault = $classInfo !== null ? (bool) ($classInfo['property_has_default'][$propertyName] ?? false) : false;
				if (!$hasDefault && !$this->hasPartialBranchInitializationWarning($initializationKeys, $context, $path, 'property', $propertyName)) {
					$this->recordInitializationWarning($diagnostics, $initializationKeys, 'maybe_uninitialized_property', $context, $path, $line, 'Property `$this->' . $propertyName . '` may be read before initialization in `' . $context . '`.');
				}
			}
		}
	}

	/** @param array<string,mixed> $chain @param array<string,bool> $initializedLocals @param array<string,bool> $initializedProperties */
	private function chainMayUseUninitializedValue(array $chain, array $initializedLocals, array $initializedProperties): bool
	{
		$rootKind = (string) ($chain['root_kind'] ?? 'variable');
		$rootName = (string) ($chain['root_name'] ?? '');
		if ($rootKind === 'variable' && $rootName !== '' && $rootName !== 'this' && !isset($initializedLocals[$rootName])) {
			return true;
		}
		$segments = $chain['segments'] ?? [];
		if ($rootKind === 'variable' && $rootName === 'this' && is_array($segments) && is_array($segments[0] ?? null) && (($segments[0]['kind'] ?? '') === 'property')) {
			$propertyName = (string) ($segments[0]['name'] ?? '');
			return $propertyName !== '' && !($initializedProperties[$propertyName] ?? false);
		}
		return false;
	}

	private function recordInitializationWarning(array &$diagnostics, array &$initializationKeys, string $subkind, string $context, string $path, int $line, string $message): void
	{
		$key = $subkind . '|' . $context . '|' . $path . '|' . $line . '|' . $message;
		if (isset($initializationKeys[$key])) {
			return;
		}
		$initializationKeys[$key] = true;
		$diagnostics[] = [
			'kind' => 'initialization_warning',
			'initialization_kind' => $subkind,
			'context' => $context,
			'path' => $path,
			'line' => $line,
			'message' => $message,
		];
	}

	/** @param array<string,bool> $initializedLocals @param array<string,int> $initializedLocalLines */
	private function markLocalInitialized(array &$initializedLocals, array &$initializedLocalLines, string $name, int $line): void
	{
		$initializedLocals[$name] = true;
		if (!isset($initializedLocalLines[$name])) {
			$initializedLocalLines[$name] = $line;
			return;
		}
		$initializedLocalLines[$name] = min($initializedLocalLines[$name], $line);
	}

	/** @param array<string,bool> $initializedProperties @param array<string,int> $initializedPropertyLines */
	private function markPropertyInitialized(array &$initializedProperties, array &$initializedPropertyLines, string $name, int $line): void
	{
		$initializedProperties[$name] = true;
		if (!isset($initializedPropertyLines[$name])) {
			$initializedPropertyLines[$name] = $line;
			return;
		}
		$initializedPropertyLines[$name] = min($initializedPropertyLines[$name], $line);
	}

	private function hasPartialBranchInitializationWarning(array $initializationKeys, string $context, string $path, string $subjectKind, string $subjectName): bool
	{
		$needle = $subjectKind === 'property'
			? '`$this->' . $subjectName . '` is initialized on only some branch paths'
			: 'Local `$' . $subjectName . '` is initialized on only some branch paths';
		$prefix = $subjectKind === 'property'
			? 'branch_partial_property_initialization|'
			: 'branch_partial_local_initialization|';
		foreach (array_keys($initializationKeys) as $key) {
			if (!str_starts_with($key, $prefix . $context . '|' . $path . '|')) {
				continue;
			}
			if (str_contains($key, $needle)) {
				return true;
			}
		}
		return false;
	}

	/** @param array<string,array<string,mixed>> $classLookup @return array<string,bool> */
	private function buildInitialPropertyInitializationMap(?string $selfType, array $classLookup): array
	{
		if ($selfType === null || $selfType === '') {
			return [];
		}
		$classInfo = $this->findClassInfo($selfType, $classLookup, $selfType);
		if ($classInfo === null) {
			return [];
		}
		$map = [];
		foreach (($classInfo['property_has_default'] ?? []) as $propertyName => $hasDefault) {
			if ((bool) $hasDefault) {
				$map[(string) $propertyName] = true;
			}
		}
		return $map;
	}

	/** @param array<string,mixed> $class @return array<string,bool> */
	private function collectPropertiesInitializedByEveryConstructor(array $class): array
	{
		$constructors = [];
		foreach (($class['methods'] ?? []) as $method) {
			if (is_array($method) && strtolower((string) ($method['name'] ?? '')) === '__construct') {
				$constructors[] = $method;
			}
		}
		if ($constructors === []) {
			return [];
		}
		$common = null;
		foreach ($constructors as $constructor) {
			$assigned = $this->collectDirectSelfPropertyAssignments($constructor);
			$common = $common === null
				? $assigned
				: array_intersect_key($common, $assigned);
		}
		return is_array($common) ? $common : [];
	}

	/** @param array<string,mixed> $class @param array<string,array<string,mixed>> $classLookup @return array<string,bool> */
	private function collectConstructorInitializedPropertiesForClass(array $class, string $classType, array $classLookup): array
	{
		$ownConstructors = [];
		foreach (($class['methods'] ?? []) as $method) {
			if (is_array($method) && strtolower((string) ($method['name'] ?? '')) === '__construct') {
				$ownConstructors[] = $method;
			}
		}
		if ($ownConstructors !== []) {
			return $this->collectPropertiesInitializedByEveryConstructor($class);
		}
		$classInfo = $this->findClassInfo($classType, $classLookup, $classType);
		if (!is_array($classInfo)) {
			return [];
		}
		return (array) ($classInfo['constructor_initialized_properties'] ?? []);
	}

	/** @param array<string,mixed> $method @return array<string,bool> */
	private function collectDirectSelfPropertyAssignments(array $method): array
	{
		$assigned = [];
		foreach (($method['property_assignments'] ?? []) as $assignment) {
			if (!is_array($assignment) || !is_array($assignment['target_chain'] ?? null)) {
				continue;
			}
			$targetChain = $assignment['target_chain'];
			$segments = $targetChain['segments'] ?? [];
			if (($targetChain['root_kind'] ?? '') !== 'variable' || ($targetChain['root_name'] ?? '') !== 'this' || !is_array($segments) || count($segments) !== 1) {
				continue;
			}
			$segment = $segments[0] ?? null;
			if (!is_array($segment) || ($segment['kind'] ?? '') !== 'property') {
				continue;
			}
			$name = (string) ($segment['name'] ?? '');
			if ($name !== '') {
				$assigned[$name] = true;
			}
		}
		foreach (($method['property_branch_assignments'] ?? []) as $branchAssignment) {
			if (!is_array($branchAssignment)) {
				continue;
			}
			$name = (string) ($branchAssignment['property_name'] ?? '');
			if ($name === '' || !isset($assigned[$name])) {
				continue;
			}
			$branchCount = max(0, (int) ($branchAssignment['branch_count'] ?? count($branchAssignment['branches'] ?? [])));
			$coveredBranchCount = max(
				is_array($branchAssignment['branches'] ?? null) ? count($branchAssignment['branches']) : 0,
				(int) ($branchAssignment['covered_branch_count'] ?? 0)
			);
			$everyBranchAssigns = ($branchAssignment['has_fallthrough'] ?? false) === false && $branchCount > 0 && $coveredBranchCount === $branchCount;
			if (!$everyBranchAssigns) {
				unset($assigned[$name]);
			}
		}
		return $assigned;
	}

	/** @param array<string,bool> $constructorInitializedProperties @return array<string,bool> */
	private function constructorBaselineForMethod(string $methodName, array $constructorInitializedProperties): array
	{
		return strtolower($methodName) === '__construct' ? [] : $constructorInitializedProperties;
	}

	/** @param array<string,mixed> $targetChain */
	private function isDirectSelfPropertyTarget(array $targetChain, string $propertyName): bool
	{
		$segments = $targetChain['segments'] ?? [];
		return (($targetChain['root_kind'] ?? '') === 'variable')
			&& (($targetChain['root_name'] ?? '') === 'this')
			&& is_array($segments)
			&& count($segments) === 1
			&& is_array($segments[0] ?? null)
			&& (($segments[0]['kind'] ?? '') === 'property')
			&& (($segments[0]['name'] ?? '') === $propertyName);
	}

	/** @param array<string,mixed> $result */
	private function formatPropertyReadFailureMessage(string $context, string $chainText, array $result, string $statementKind): string
	{
		$failureKind = (string) ($result['failure_kind'] ?? 'unknown');
		$segment = isset($result['failure_segment']) && is_string($result['failure_segment']) ? $result['failure_segment'] : null;
		$receiverType = isset($result['receiver_type']) && is_string($result['receiver_type']) ? $result['receiver_type'] : null;
		$statementLabel = $statementKind === 'return' ? 'return' : 'property read';

		if ($failureKind === 'unknown_root_type') {
			return 'Unknown root type while resolving property read `' . $chainText . '` in `' . $context . '`.';
		}
		if ($failureKind === 'missing_property') {
			return 'Missing property read target `' . $segment . '` on receiver type `' . $receiverType . '` in `' . $context . '` for `' . $chainText . '`.';
		}
		if ($failureKind === 'unknown_receiver_type') {
			return 'Unknown receiver type `' . $receiverType . '` while resolving property read segment `' . $segment . '` in `' . $context . '` for `' . $chainText . '`.';
		}
		if ($failureKind === 'non_object_receiver_type') {
			if ($receiverType === 'null') {
				return 'Cannot read property segment `' . $segment . '` on `null` receiver in `' . $context . '` for `' . $chainText . '`.';
			}
			if ($receiverType === 'bool' || $receiverType === 'int' || $receiverType === 'float') {
				return 'Cannot read property segment `' . $segment . '` on scalar receiver type `' . $receiverType . '` in `' . $context . '` for `' . $chainText . '`.';
			}
			return 'Cannot read property segment `' . $segment . '` on non-object receiver type `' . $receiverType . '` in `' . $context . '` for `' . $chainText . '`.';
		}
		if ($failureKind === 'missing_method_or_return_type') {
			return 'Missing method or return type before property read `' . $chainText . '` in `' . $context . '` at segment `' . $segment . '`.';
		}
		if ($failureKind === 'ambiguous_merged_member_type') {
			$candidateTypes = isset($result['candidate_types']) && is_array($result['candidate_types']) ? $result['candidate_types'] : [];
			return 'Ambiguous merged member type before property read `' . $chainText . '` in `' . $context . '`: candidates `'
				. implode('`, `', $candidateTypes) . '`.';
		}
		return 'Unable to resolve ' . $statementLabel . ' `' . $chainText . '` in `' . $context . '`.';
	}

	/** @param array<string,string> $lookup @param array<string,mixed> $function */
	private function addFunctionLookupEntry(array &$lookup, array $function, ?string $namespace): void
	{
		$name = (string) ($function['name'] ?? '');
		$returnType = (string) ($function['return_type'] ?? '');
		if ($name === '' || $returnType === '') {
			return;
		}
		$fqfn = $namespace !== null && $namespace !== '' ? $namespace . '\\' . $name : $name;
		$lookup[strtolower($fqfn)] = $returnType;
		if (!isset($lookup[strtolower($name)])) {
			$lookup[strtolower($name)] = $returnType;
		}
	}

	/** @param array<string,array<string,mixed>> $catalog @param array<string,mixed> $function */
	private function addFunctionCatalogEntry(array &$catalog, array $function, ?string $namespace): void
	{
		$name = (string) ($function['name'] ?? '');
		if ($name === '') {
			return;
		}
		$key = strtolower(($namespace !== null && $namespace !== '' ? $namespace . '\\' : '') . $name);
		$catalog[$key] = [
			'name' => $name,
			'namespace' => $namespace,
			'params' => is_array($function['params'] ?? null) ? $function['params'] : [],
			'return_type' => (string) ($function['return_type'] ?? ''),
			'line' => (int) ($function['line'] ?? 0),
			'is_static' => false,
		];
		if (!isset($catalog[strtolower($name)])) {
			$catalog[strtolower($name)] = $catalog[$key];
		}
	}

	/** @param array<string,mixed> $class @return array<string,mixed> */
	private function makeClassInfo(array $class, string $namespace): array
	{
		$methodReturnTypes = [];
		$methodSignatures = [];
		foreach (($class['methods'] ?? []) as $method) {
			if (!is_array($method)) {
				continue;
			}
			$name = (string) ($method['name'] ?? '');
			$returnType = (string) ($method['return_type'] ?? '');
			if ($name !== '' && $returnType !== '') {
				$methodReturnTypes[$name] = $returnType;
			}
			if ($name !== '') {
				$methodSignatures[$name] = [
					'name' => $name,
					'params' => is_array($method['params'] ?? null) ? $method['params'] : [],
					'return_type' => $returnType,
					'is_static' => (bool) ($method['is_static'] ?? false),
					'visibility' => $this->normalizeMemberVisibility((string) ($method['visibility'] ?? 'public')),
					'line' => (int) ($method['line'] ?? 0),
				];
			}
		}

		$propertyTypes = [];
		$propertyHasDefault = [];
		$propertyVisibility = [];
		$propertyDeclaringClass = [];
		$fqcn = $namespace === '' ? (string) ($class['name'] ?? '') : $namespace . '\\' . (string) ($class['name'] ?? '');
		foreach (($class['properties'] ?? []) as $property) {
			if (!is_array($property)) {
				continue;
			}
			$name = (string) ($property['name'] ?? '');
			$type = (string) ($property['type'] ?? '');
			if ($name !== '' && $type !== '') {
				$propertyTypes[$name] = $type;
			}
			if ($name !== '') {
				$propertyHasDefault[$name] = (bool) ($property['has_default'] ?? false);
				$propertyVisibility[$name] = $this->normalizeMemberVisibility((string) ($property['visibility'] ?? 'public'));
				$propertyDeclaringClass[$name] = $fqcn;
			}
		}
		$constructorInitializedProperties = $this->collectPropertiesInitializedByEveryConstructor($class);
		$hasOwnConstructor = false;
		foreach (($class['methods'] ?? []) as $method) {
			if (is_array($method) && strtolower((string) ($method['name'] ?? '')) === '__construct') {
				$hasOwnConstructor = true;
				break;
			}
		}
		$constantVisibility = [];
		$constantDeclaringClass = [];
		foreach (($class['constants'] ?? []) as $constant) {
			if (!is_array($constant)) {
				continue;
			}
			$name = (string) ($constant['name'] ?? '');
			if ($name !== '') {
				$constantVisibility[$name] = $this->normalizeMemberVisibility((string) ($constant['visibility'] ?? 'public'));
				$constantDeclaringClass[$name] = $fqcn;
			}
		}

		return [
			'name' => (string) ($class['name'] ?? ''),
			'fqcn' => $fqcn,
			'parent_class' => (string) ($class['parent_class'] ?? ''),
			'is_enum' => (bool) ($class['is_enum'] ?? false),
			'ancestor_types' => [],
			'method_return_types' => $methodReturnTypes,
			'method_signatures' => $methodSignatures,
			'property_types' => $propertyTypes,
			'property_has_default' => $propertyHasDefault,
			'property_visibility' => $propertyVisibility,
			'property_declaring_class' => $propertyDeclaringClass,
			'constructor_initialized_properties' => $constructorInitializedProperties,
			'has_own_constructor' => $hasOwnConstructor,
			'constant_visibility' => $constantVisibility,
			'constant_declaring_class' => $constantDeclaringClass,
		];
	}

	/** @param array<string,array<string,mixed>> $classLookup */
	private function findClassInfo(string $type, array $classLookup, ?string $scopeType = null): ?array
	{
		$raw = trim($type, "\\ \t\n\r\0\x0B");
		$nullableInner = $this->unwrapNullableType($raw);
		$resolved = $nullableInner ?? $raw;
		$normalized = strtolower($resolved);
		if ($normalized === '') {
			return null;
		}
		if (isset($classLookup[$normalized])) {
			return $classLookup[$normalized];
		}
		if (!str_contains($resolved, '\\') && $scopeType !== null && str_contains($scopeType, '\\')) {
			$scopeNamespace = substr($scopeType, 0, (int) strrpos($scopeType, '\\'));
			$scoped = strtolower($scopeNamespace . '\\' . $resolved);
			if (isset($classLookup[$scoped])) {
				return $classLookup[$scoped];
			}
		}
		return null;
	}

	private function findMethodSignature(array $classInfo, string $methodName): ?array
	{
		$signatures = $classInfo['method_signatures'] ?? null;
		if (!is_array($signatures)) {
			return null;
		}
		$signature = $signatures[$methodName] ?? null;
		return is_array($signature) ? $signature : null;
	}

	private function normalizeMemberVisibility(string $visibility): string
	{
		$normalized = strtolower(trim($visibility));
		return in_array($normalized, ['public', 'protected', 'private'], true) ? $normalized : 'public';
	}

	private function memberAccessAllowed(string $visibility, array $declaringClassInfo, ?string $selfType, array $classLookup): bool
	{
		$visibility = $this->normalizeMemberVisibility($visibility);
		if ($visibility === 'public') {
			return true;
		}
		if ($selfType === null || $selfType === '') {
			return false;
		}
		$selfInfo = $this->findClassInfo($selfType, $classLookup);
		if ($selfInfo === null) {
			return false;
		}
		$declaringFqcn = (string) ($declaringClassInfo['fqcn'] ?? $declaringClassInfo['name'] ?? '');
		$selfFqcn = (string) ($selfInfo['fqcn'] ?? $selfInfo['name'] ?? $selfType);
		if (strcasecmp($declaringFqcn, $selfFqcn) === 0) {
			return true;
		}
		if ($visibility === 'private') {
			return false;
		}
		foreach ((array) ($selfInfo['ancestor_types'] ?? []) as $ancestorType) {
			if (strcasecmp((string) $ancestorType, $declaringFqcn) === 0) {
				return true;
			}
		}
		return false;
	}

	/** @param array<string,mixed> $classInfo @param array<string,array<string,mixed>> $classLookup @return array<string,mixed>|null */
	private function findPropertyDeclaringClassInfo(array $classInfo, string $propertyName, array $classLookup): ?array
	{
		$declaringClass = (string) ($classInfo['property_declaring_class'][$propertyName] ?? '');
		if ($declaringClass === '') {
			return null;
		}
		return $this->findClassInfo($declaringClass, $classLookup);
	}

	/** @param array<string,mixed> $classInfo @param array<string,array<string,mixed>> $classLookup @return array<string,mixed>|null */
	private function findConstantDeclaringClassInfo(array $classInfo, string $constantName, array $classLookup): ?array
	{
		$declaringClass = (string) ($classInfo['constant_declaring_class'][$constantName] ?? '');
		if ($declaringClass === '') {
			return null;
		}
		return $this->findClassInfo($declaringClass, $classLookup);
	}

	private function isKnownNonObjectType(string $type): bool
	{
		$normalized = strtolower(trim($type));
		if (preg_match('/^fixed_array(?:_t)?<.+>$/', $normalized) === 1) {
			return true;
		}
		return in_array($normalized, ['string', 'int', 'int8', 'int16', 'int32', 'int64', 'uint8', 'byte', 'uint16', 'uint32', 'uint64', 'float', 'bool', 'null', 'array', 'mixed', 'dynamic', 'void'], true);
	}

	private function resolveStaticRootClassName(string $className, ?string $selfType, array $classLookup): string
	{
		$normalized = strtolower(trim($className, "\\ \t\n\r\0\x0B"));
		if ($normalized === 'self' || $normalized === 'static') {
			return $selfType ?? $className;
		}
		if ($normalized === 'parent' && $selfType !== null) {
			$selfInfo = $this->findClassInfo($selfType, $classLookup);
			$parentClass = is_array($selfInfo) ? (string) ($selfInfo['parent_class'] ?? '') : '';
			if ($parentClass !== '') {
				return $parentClass;
			}
		}
		return $className;
	}

	/** @param array<string,array<string,mixed>> $catalog @param list<string> $stack */
	private function mergeInheritedMembersIntoCatalog(array &$catalog, string $fqcn, array $stack): ?array
	{
		$info = $catalog[$fqcn] ?? null;
		if (!is_array($info)) {
			return null;
		}
		if (($info['__merged_inheritance'] ?? false) === true) {
			return $info;
		}
		if (in_array(strtolower($fqcn), $stack, true)) {
			return $info;
		}
		$parentClass = (string) ($info['parent_class'] ?? '');
		if ($parentClass !== '') {
			$parentInfo = $this->resolveParentCatalogEntry($catalog, $fqcn, $parentClass, array_merge($stack, [strtolower($fqcn)]));
			if (is_array($parentInfo)) {
				$info['method_return_types'] = array_replace((array) ($parentInfo['method_return_types'] ?? []), (array) ($info['method_return_types'] ?? []));
				$info['method_signatures'] = array_replace((array) ($parentInfo['method_signatures'] ?? []), (array) ($info['method_signatures'] ?? []));
				$info['property_types'] = array_replace((array) ($parentInfo['property_types'] ?? []), (array) ($info['property_types'] ?? []));
				$info['property_has_default'] = array_replace((array) ($parentInfo['property_has_default'] ?? []), (array) ($info['property_has_default'] ?? []));
				$info['property_visibility'] = array_replace((array) ($parentInfo['property_visibility'] ?? []), (array) ($info['property_visibility'] ?? []));
				$info['property_declaring_class'] = array_replace((array) ($parentInfo['property_declaring_class'] ?? []), (array) ($info['property_declaring_class'] ?? []));
				if (($info['has_own_constructor'] ?? false) !== true) {
					$info['constructor_initialized_properties'] = (array) ($parentInfo['constructor_initialized_properties'] ?? []);
				}
				$info['constant_visibility'] = array_replace((array) ($parentInfo['constant_visibility'] ?? []), (array) ($info['constant_visibility'] ?? []));
				$info['constant_declaring_class'] = array_replace((array) ($parentInfo['constant_declaring_class'] ?? []), (array) ($info['constant_declaring_class'] ?? []));
				$info['ancestor_types'] = $this->normalizeTypeSet(array_merge(
					[(string) ($parentInfo['fqcn'] ?? $parentClass)],
					(array) ($parentInfo['ancestor_types'] ?? [])
				));
			}
		}
		$info['__merged_inheritance'] = true;
		$catalog[$fqcn] = $info;
		return $info;
	}

	/** @param array<string,array<string,mixed>> $catalog @param list<string> $stack */
	private function resolveParentCatalogEntry(array &$catalog, string $fqcn, string $parentClass, array $stack): ?array
	{
		$candidates = [ltrim($parentClass, '\\')];
		if (!str_contains($parentClass, '\\') && str_contains($fqcn, '\\')) {
			$namespace = substr($fqcn, 0, (int) strrpos($fqcn, '\\'));
			$candidates[] = $namespace . '\\' . $parentClass;
		}
		foreach ($candidates as $candidate) {
			if (isset($catalog[$candidate])) {
				return $this->mergeInheritedMembersIntoCatalog($catalog, $candidate, $stack);
			}
		}
		return null;
	}

	/** @param list<string> $actualTypes @param list<string> $expectedTypes */
	private function typeSetsAreCompatible(array $actualTypes, array $expectedTypes, array $classLookup, bool $allowVoidNull): bool
	{
		$actualTypes = $this->normalizeTypeSet($actualTypes);
		$expectedTypes = $this->normalizeTypeSet($expectedTypes);
		if ($actualTypes === [] || $expectedTypes === []) {
			return false;
		}
		foreach ($actualTypes as $actualType) {
			$matched = false;
			foreach ($expectedTypes as $expectedType) {
				if ($this->isSingleTypeCompatible($actualType, $expectedType, $classLookup, $allowVoidNull)) {
					$matched = true;
					break;
				}
			}
			if (!$matched) {
				return false;
			}
		}
		return true;
	}

	private function isSingleTypeCompatible(string $actualType, string $expectedType, array $classLookup, bool $allowVoidNull): bool
	{
		$actual = trim($this->canonicalizeResolvedType($actualType, $classLookup, null));
		$expected = trim($this->canonicalizeResolvedType($expectedType, $classLookup, null));
		if ($actual === $expected) {
			return true;
		}
		if ($this->isFixedWidthIntegerAssignable($actual, $expected)) {
			return true;
		}
		if (strtolower($expected) === 'mixed') {
			return true;
		}
		if ($allowVoidNull && strtolower($expected) === 'void' && strtolower($actual) === 'null') {
			return true;
		}
		$expectedNullableInner = $this->unwrapNullableType($expected);
		if ($expectedNullableInner !== null) {
			return strtolower($actual) === 'null' || $this->isSingleTypeCompatible($actual, $expectedNullableInner, $classLookup, false);
		}
		$actualNullableInner = $this->unwrapNullableType($actual);
		if ($actualNullableInner !== null) {
			return $this->isSingleTypeCompatible($actualNullableInner, $expected, $classLookup, false);
		}
		if ($this->isClassTypeAssignable($actual, $expected, $classLookup)) {
			return true;
		}
		return false;
	}

	private function isFixedWidthIntegerAssignable(string $actualType, string $expectedType): bool
	{
		$actual = $this->fixedWidthIntegerInfo($actualType);
		$expected = $this->fixedWidthIntegerInfo($expectedType);
		if ($actual === null || $expected === null) {
			return false;
		}
		if ($actual['signed'] !== $expected['signed']) {
			return false;
		}
		return $actual['bytes'] <= $expected['bytes'];
	}

	/** @return array{signed:bool,bytes:int}|null */
	private function fixedWidthIntegerInfo(string $type): ?array
	{
		return match (strtolower(ltrim(trim($type), '\\'))) {
			'int8' => ['signed' => true, 'bytes' => 1],
			'int16' => ['signed' => true, 'bytes' => 2],
			'int32' => ['signed' => true, 'bytes' => 4],
			'int64', 'int' => ['signed' => true, 'bytes' => 8],
			'uint8', 'byte' => ['signed' => false, 'bytes' => 1],
			'uint16' => ['signed' => false, 'bytes' => 2],
			'uint32' => ['signed' => false, 'bytes' => 4],
			'uint64' => ['signed' => false, 'bytes' => 8],
			default => null,
		};
	}

	private function unwrapNullableType(string $type): ?string
	{
		$type = trim($type);
		if ($type === '') {
			return null;
		}
		if ($type[0] === '?') {
			return ltrim(substr($type, 1), '\\');
		}
		if (preg_match('/^nullable\s*<\s*(.+)\s*>$/i', $type, $matches) === 1) {
			return trim((string) $matches[1]);
		}
		return null;
	}

	private function unwrapMemberReceiverType(string $type): string
	{
		$current = trim($type);
		while ($current !== '') {
			$nullableInner = $this->unwrapNullableType($current);
			if ($nullableInner !== null) {
				$current = $nullableInner;
				continue;
			}
			if (preg_match('/^result(?:_or_bool|_or_false)?\s*<\s*(.+)\s*>$/i', $current, $matches) === 1) {
				$current = trim((string) $matches[1]);
				continue;
			}
			if (preg_match('/^shared_p\s*<\s*(.+)\s*>$/i', $current, $matches) === 1) {
				$current = trim((string) $matches[1]);
				continue;
			}
			break;
		}
		return $current;
	}

	/** @param list<string> $types @return list<string> */
	private function removeNullTypes(array $types): array
	{
		$out = [];
		foreach ($this->normalizeTypeSet($types) as $type) {
			$normalized = strtolower($type);
			if ($normalized === 'null') {
				continue;
			}
			$nullableInner = $this->unwrapNullableType($type);
			if ($nullableInner !== null) {
				$out[] = $nullableInner;
				continue;
			}
			$out[] = $type;
		}
		return $this->normalizeTypeSet($out);
	}

	/** @param list<string> $types @return list<string> */
	private function removeFalseTypes(array $types): array
	{
		$out = [];
		foreach ($this->normalizeTypeSet($types) as $type) {
			$normalized = strtolower(trim($type));
			if ($normalized === 'false') {
				continue;
			}
			if (preg_match('/^result_or_false\s*<\s*(.+)\s*>$/i', $type, $matches) === 1) {
				$out[] = trim((string) $matches[1]);
				continue;
			}
			if (preg_match('/^result_or_bool\s*<\s*(.+)\s*>$/i', $type, $matches) === 1) {
				$out[] = trim((string) $matches[1]);
				continue;
			}
			$out[] = $type;
		}
		return $this->normalizeTypeSet($out);
	}

	private function isClassTypeAssignable(string $actualType, string $expectedType, array $classLookup): bool
	{
		$actualInfo = $this->findClassInfo($actualType, $classLookup);
		$expectedInfo = $this->findClassInfo($expectedType, $classLookup);
		if ($actualInfo === null || $expectedInfo === null) {
			return false;
		}
		return strtolower((string) ($actualInfo['fqcn'] ?? '')) === strtolower((string) ($expectedInfo['fqcn'] ?? ''))
			|| in_array(strtolower((string) ($expectedInfo['fqcn'] ?? '')), array_map('strtolower', (array) ($actualInfo['ancestor_types'] ?? [])), true);
	}

	/** @param array<string,mixed>|null $source @param array<string,list<string>> $localTypes @param array<string,array<string,mixed>> $classLookup @param array<string,string> $functionLookup @return list<string> */
	private function resolveForeachValueTypes(?array $source, string $role, array $localTypes, ?string $selfType, array $classLookup, array $functionLookup): array
	{
		$sourceTypes = $source !== null
			? $this->resolveExpressionDescriptorTypes($source, $localTypes, $selfType, $classLookup, $functionLookup)
			: [];
		if ($role === 'key') {
			$keyTypes = [];
			foreach ($this->normalizeTypeSet($sourceTypes) as $sourceType) {
				if (preg_match('/^vector(?:_t)?<\s*(.+)\s*>$/i', $sourceType) === 1) {
					$keyTypes[] = 'int';
					continue;
				}
				if (preg_match('/^fixed_array(?:_t)?<\s*(.+)\s*>$/i', $sourceType) === 1) {
					$keyTypes[] = 'int';
					continue;
				}
				if (preg_match('/^hash(?:_t)?<\s*(.+)\s*>$/i', $sourceType, $matches) === 1) {
					$parts = array_map('trim', explode(',', (string) $matches[1], 2));
					$keyTypes[] = count($parts) === 2 ? $parts[0] : 'string';
				}
			}
			return $this->normalizeTypeSet($keyTypes !== [] ? $keyTypes : ['mixed']);
		}
		$valueTypes = [];
		foreach ($this->normalizeTypeSet($sourceTypes) as $sourceType) {
			if (preg_match('/^vector(?:_t)?<\s*(.+)\s*>$/i', $sourceType, $matches) === 1) {
				$valueTypes[] = trim((string) $matches[1]);
				continue;
			}
			if (preg_match('/^fixed_array(?:_t)?<\s*(.+)\s*>$/i', $sourceType, $matches) === 1) {
				$parts = array_map('trim', explode(',', (string) $matches[1], 2));
				if (($parts[0] ?? '') !== '') {
					$valueTypes[] = $parts[0];
				}
				continue;
			}
			if (preg_match('/^hash(?:_t)?<\s*(.+)\s*>$/i', $sourceType, $matches) === 1) {
				$parts = array_map('trim', explode(',', (string) $matches[1], 2));
				if (count($parts) === 2) {
					$valueTypes[] = $parts[1];
				} elseif (count($parts) === 1 && $parts[0] !== '') {
					$valueTypes[] = $parts[0];
				}
			}
		}
		return $this->normalizeTypeSet($valueTypes);
	}
}
