<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanSymbolIndexBuilder
{
	/** @param array<string,array<string,mixed>> $fileSummaries @return list<array<string,mixed>> */
	public function build(array $fileSummaries): array
	{
		$symbols = [];
		foreach ($fileSummaries as $sourceKey => $summary) {
			$path = (string) ($summary['path'] ?? $sourceKey);
			foreach (($summary['root_constants'] ?? []) as $constant) {
				if (is_array($constant)) {
					$symbols[] = $this->makeSymbolRecord('constant', '', (string) ($constant['name'] ?? ''), $path, (int) ($constant['line'] ?? 0), null);
				}
			}
			foreach (($summary['root_functions'] ?? []) as $function) {
				if (is_array($function)) {
					$symbols[] = $this->makeSymbolRecord('function', '', (string) ($function['name'] ?? ''), $path, (int) ($function['line'] ?? 0), null);
				}
			}
			foreach (($summary['root_classes'] ?? []) as $class) {
				if (!is_array($class)) {
					continue;
				}
				$symbols[] = $this->makeSymbolRecord('class', '', (string) ($class['name'] ?? ''), $path, (int) ($class['line'] ?? 0), null);
				$symbols = array_merge($symbols, $this->collectClassMemberSymbols($class, '', $path));
			}
			foreach (($summary['namespaces'] ?? []) as $namespace) {
				if (!is_array($namespace)) {
					continue;
				}
				$namespaceName = (string) ($namespace['name'] ?? '');
				foreach (($namespace['constants'] ?? []) as $constant) {
					if (is_array($constant)) {
						$symbols[] = $this->makeSymbolRecord('constant', $namespaceName, (string) ($constant['name'] ?? ''), $path, (int) ($constant['line'] ?? 0), null);
					}
				}
				foreach (($namespace['functions'] ?? []) as $function) {
					if (is_array($function)) {
						$symbols[] = $this->makeSymbolRecord('function', $namespaceName, (string) ($function['name'] ?? ''), $path, (int) ($function['line'] ?? 0), null);
					}
				}
				foreach (($namespace['classes'] ?? []) as $class) {
					if (!is_array($class)) {
						continue;
					}
					$symbols[] = $this->makeSymbolRecord('class', $namespaceName, (string) ($class['name'] ?? ''), $path, (int) ($class['line'] ?? 0), null);
					$symbols = array_merge($symbols, $this->collectClassMemberSymbols($class, $namespaceName, $path));
				}
			}
		}
		return array_values(array_filter($symbols, static fn (array $symbol): bool => $symbol['name'] !== ''));
	}

	/** @param array<string,mixed> $class @return list<array<string,mixed>> */
	private function collectClassMemberSymbols(array $class, string $namespace, string $path): array
	{
		$symbols = [];
		$className = (string) ($class['name'] ?? '');
		$scope = $namespace === '' ? $className : $namespace . '::' . $className;
		foreach (($class['properties'] ?? []) as $property) {
			if (is_array($property)) {
				$symbols[] = $this->makeSymbolRecord('property', $scope, (string) ($property['name'] ?? ''), $path, (int) ($property['line'] ?? 0), $className);
			}
		}
		foreach (($class['methods'] ?? []) as $method) {
			if (is_array($method)) {
				$symbols[] = $this->makeSymbolRecord('method', $scope, (string) ($method['name'] ?? ''), $path, (int) ($method['line'] ?? 0), $className);
			}
		}
		return $symbols;
	}

	/** @return array<string,mixed> */
	private function makeSymbolRecord(string $kind, string $scope, string $name, string $path, int $line, ?string $ownerClass): array
	{
		return [
			'kind' => $kind,
			'scope' => $scope,
			'name' => $name,
			'path' => $path,
			'line' => $line,
			'owner_class' => $ownerClass,
			'key' => $kind . '|' . $scope . '|' . $name,
		];
	}
}
