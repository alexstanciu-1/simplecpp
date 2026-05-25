<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanWorkspaceContext
{
	/**
	 * @param array<string,mixed> $config
	 * @param array<string,mixed> $projectGraph
	 * @param array<string,array<string,mixed>> $projectContexts
	 * @param list<array{profile:string,path:string,generated:int,skipped:list<string>}> $runtimeShallowSources
	 */
	public function __construct(
		public readonly string $projectRoot,
		public readonly string $configPath,
		public readonly array $config,
		public readonly array $projectGraph,
		public readonly array $projectContexts,
		public readonly string $repoRoot,
		public readonly string $phpProfile,
		public readonly array $runtimeShallowSources,
		public readonly string $activeRuntimeShallowPath,
		/** @var list<StanSourceUnit> */
		public readonly array $sourceUnits,
		public readonly string $stanSignature,
		public readonly string $statePath,
		public readonly string $cacheDir,
	)
	{
	}
}
