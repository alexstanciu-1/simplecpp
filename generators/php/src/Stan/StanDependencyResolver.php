<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanDependencyResolver
{
	public function __construct(
		private readonly StanPathMapper $pathMapper = new StanPathMapper(),
	)
	{
	}

	/** @param list<array<string,mixed>> $symbolIndex @return array<string,list<array<string,mixed>>> */
	public function buildResolutionLookup(array $symbolIndex): array
	{
		$lookup = [];
		foreach ($symbolIndex as $symbol) {
			$name = (string) ($symbol['name'] ?? '');
			$scope = (string) ($symbol['scope'] ?? '');
			$kind = (string) ($symbol['kind'] ?? '');
			if ($name === '' || $kind === '') {
				continue;
			}
			$fq = $scope === '' ? $name : $scope . '::' . $name;
			$lookup[$kind . '|' . strtolower($name)][] = $symbol;
			$lookup[$kind . '|' . strtolower($fq)][] = $symbol;
			if ($kind === 'class') {
				$lookup['interface|' . strtolower($name)][] = $symbol;
				$lookup['interface|' . strtolower($fq)][] = $symbol;
			}
		}
		return $lookup;
	}

	/** @param array<string,list<array<string,mixed>>> $lookup @return list<array<string,mixed>> */
	public function resolveDependencyTarget(string $kind, string $target, array $lookup): array
	{
		$normalizedTarget = strtolower(str_replace('\\', '::', ltrim($target, '\\')));
		$shortTarget = strtolower(($pos = strrpos($normalizedTarget, '::')) !== false ? substr($normalizedTarget, $pos + 2) : $normalizedTarget);
		$keys = [];
		if ($kind === 'extends' || $kind === 'implements') {
			$keys = ['class|' . $normalizedTarget, 'class|' . $shortTarget, 'interface|' . $normalizedTarget, 'interface|' . $shortTarget];
		} elseif ($kind === 'use') {
			$keys = ['function|' . $normalizedTarget, 'function|' . $shortTarget, 'class|' . $normalizedTarget, 'class|' . $shortTarget, 'constant|' . $normalizedTarget, 'constant|' . $shortTarget];
		} else {
			$keys = ['class|' . $normalizedTarget, 'class|' . $shortTarget, 'function|' . $normalizedTarget, 'function|' . $shortTarget, 'constant|' . $normalizedTarget, 'constant|' . $shortTarget];
		}
		$matches = [];
		foreach ($keys as $key) {
			foreach (($lookup[$key] ?? []) as $symbol) {
				$bucketKey = (string) ($symbol['key'] ?? '') . '|' . (string) ($symbol['path'] ?? '') . '|' . (int) ($symbol['line'] ?? 0);
				$matches[$bucketKey] = $symbol;
			}
		}
		return array_values($matches);
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @param list<array<string,mixed>> $symbolIndex @return array<string,list<string>> */
	public function collectFileDependencyKeys(array $fileSummaries, array $symbolIndex, string $projectRoot): array
	{
		$lookup = $this->buildResolutionLookup($symbolIndex);
		$dependencyKeys = [];
		foreach ($fileSummaries as $sourceKey => $summary) {
			$keys = [];
			foreach (($summary['dependencies'] ?? []) as $dependency) {
				if (!is_array($dependency)) {
					continue;
				}
				$kind = (string) ($dependency['kind'] ?? '');
				$target = (string) ($dependency['target'] ?? '');
				if ($kind === '' || $target === '') {
					continue;
				}
				foreach ($this->resolveDependencyTarget($kind, $target, $lookup) as $symbol) {
					$path = (string) ($symbol['path'] ?? '');
					if ($path === '') {
						continue;
					}
					$resolvedKey = $this->pathMapper->sourceKey($projectRoot, $path);
					if ($resolvedKey !== $sourceKey) {
						$keys[$resolvedKey] = true;
					}
				}
			}
			$resolvedKeys = array_keys($keys);
			sort($resolvedKeys, SORT_STRING);
			$dependencyKeys[$sourceKey] = $resolvedKeys;
		}
		return $dependencyKeys;
	}
}
