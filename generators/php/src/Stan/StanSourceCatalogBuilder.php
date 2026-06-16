<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanSourceCatalogBuilder
{
	public function __construct(
		private readonly StanPathMapper $pathMapper = new StanPathMapper(),
		private readonly StanSourceMetaBuilder $metaBuilder = new StanSourceMetaBuilder(),
	)
	{
	}

	/**
	 * @param array<string,array<string,mixed>> $projectContexts
	 * @param list<string> $runtimeShallowPaths
	 * @param array<string,string> $sourceOverrides
	 * @return list<StanSourceUnit>
	 */
	public function build(string $projectRoot, array $projectContexts, array $runtimeShallowPaths, array $sourceOverrides = []): array
	{
		$paths = [];
		foreach ($projectContexts as $contextProjectRoot => $_projectContext) {
			foreach (\collect_project_stan_source_files($contextProjectRoot) as $phpPathAbs) {
				$paths[\normalize_path($phpPathAbs)] = false;
			}
		}
		foreach ($runtimeShallowPaths as $runtimePath) {
			$paths[\normalize_path($runtimePath)] = true;
		}

		$units = [];
		ksort($paths, SORT_STRING);
		foreach ($paths as $path => $isRuntimeShallow) {
			$overrideContents = array_key_exists($path, $sourceOverrides) ? (string) $sourceOverrides[$path] : null;
			$units[] = new StanSourceUnit(
				path: $path,
				sourceKey: $this->pathMapper->sourceKey($projectRoot, $path),
				meta: is_string($overrideContents) ? $this->metaBuilder->fromContents($overrideContents) : $this->metaBuilder->fromPath($path),
				isRuntimeShallow: $isRuntimeShallow,
				contents: $overrideContents,
			);
		}

		return $units;
	}
}
