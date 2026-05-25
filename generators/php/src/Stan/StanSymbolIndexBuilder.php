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
					$symbols[] = $this->makeSymbolRecord(
						'function',
						'',
						(string) ($function['name'] ?? ''),
						$path,
						(int) ($function['line'] ?? 0),
						null,
						[
							'params' => is_array($function['params'] ?? null) ? $function['params'] : [],
							'return_type' => (string) ($function['return_type'] ?? ''),
							'signature' => $this->buildFunctionSignature((string) ($function['name'] ?? ''), is_array($function['params'] ?? null) ? $function['params'] : [], (string) ($function['return_type'] ?? '')),
						]
					);
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
						$symbols[] = $this->makeSymbolRecord(
							'function',
							$namespaceName,
							(string) ($function['name'] ?? ''),
							$path,
							(int) ($function['line'] ?? 0),
							null,
							[
								'params' => is_array($function['params'] ?? null) ? $function['params'] : [],
								'return_type' => (string) ($function['return_type'] ?? ''),
								'signature' => $this->buildFunctionSignature((string) ($function['name'] ?? ''), is_array($function['params'] ?? null) ? $function['params'] : [], (string) ($function['return_type'] ?? '')),
							]
						);
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
				$propertyName = (string) ($property['name'] ?? '');
				$propertyType = (string) ($property['type'] ?? '');
				$symbols[] = $this->makeSymbolRecord(
					'property',
					$scope,
					$propertyName,
					$path,
					(int) ($property['line'] ?? 0),
					$className,
					[
						'property_type' => $propertyType,
						'signature' => trim('property ' . $className . '::$' . $propertyName . ($propertyType !== '' ? ': ' . $propertyType : '')),
					]
				);
			}
		}
		foreach (($class['methods'] ?? []) as $method) {
			if (is_array($method)) {
				$methodName = (string) ($method['name'] ?? '');
				$symbols[] = $this->makeSymbolRecord(
					'method',
					$scope,
					$methodName,
					$path,
					(int) ($method['line'] ?? 0),
					$className,
					[
						'params' => is_array($method['params'] ?? null) ? $method['params'] : [],
						'return_type' => (string) ($method['return_type'] ?? ''),
						'is_static' => (bool) ($method['is_static'] ?? false),
						'signature' => $this->buildMethodSignature($className, $methodName, is_array($method['params'] ?? null) ? $method['params'] : [], (string) ($method['return_type'] ?? ''), (bool) ($method['is_static'] ?? false)),
					]
				);
			}
		}
		return $symbols;
	}

	/** @return array<string,mixed> */
	private function makeSymbolRecord(string $kind, string $scope, string $name, string $path, int $line, ?string $ownerClass, array $metadata = []): array
	{
		return [
			'kind' => $kind,
			'scope' => $scope,
			'name' => $name,
			'path' => $path,
			'line' => $line,
			'owner_class' => $ownerClass,
			'key' => $kind . '|' . $scope . '|' . $name,
		] + $metadata;
	}

	/** @param list<array<string,mixed>> $params */
	private function buildFunctionSignature(string $name, array $params, string $returnType): string
	{
		$signature = 'function ' . $name . '(' . $this->renderParamList($params) . ')';
		if ($returnType !== '') {
			$signature .= ': ' . $returnType;
		}
		return $signature;
	}

	/** @param list<array<string,mixed>> $params */
	private function buildMethodSignature(string $className, string $name, array $params, string $returnType, bool $isStatic): string
	{
		$signature = ($isStatic ? 'static method ' : 'method ') . $className . '::' . $name . '(' . $this->renderParamList($params) . ')';
		if ($returnType !== '') {
			$signature .= ': ' . $returnType;
		}
		return $signature;
	}

	/** @param list<array<string,mixed>> $params */
	private function renderParamList(array $params): string
	{
		$parts = [];
		foreach ($params as $param) {
			if (!is_array($param)) {
				continue;
			}
			$type = (string) ($param['type'] ?? '');
			$name = (string) ($param['name'] ?? '');
			if ($name !== '' && $name[0] !== '$') {
				$name = '$' . $name;
			}
			$text = trim(($type !== '' ? $type . ' ' : '') . $name);
			if ($text !== '') {
				$parts[] = $text;
			}
		}
		return implode(', ', $parts);
	}
}
