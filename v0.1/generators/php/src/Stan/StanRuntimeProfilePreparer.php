<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

use Scpp\S2S\Analysis\RuntimeShallowSourceGenerator;

final class StanRuntimeProfilePreparer
{
	public function __construct(
		private readonly RuntimeShallowSourceGenerator $runtimeShallowGenerator = new RuntimeShallowSourceGenerator(),
	)
	{
	}

	/** @param array<string,mixed> $config @return array{php_profile:string,runtime_shallow_sources:list<array{profile:string,path:string,generated:int,skipped:list<string>}>,active_runtime_shallow_path:string} */
	public function prepare(string $repoRoot, array $config): array
	{
		$runtimeConfig = \resolve_runtime_build_config($config);
		$phpProfile = \resolve_php_runtime_profile($runtimeConfig);

		$runtimeShallowSources = [
			$this->runtimeShallowGenerator->generate($repoRoot, 'strict'),
			$this->runtimeShallowGenerator->generate($repoRoot, 'legacy'),
		];

		$activeRuntimeShallowPath = null;
		foreach ($runtimeShallowSources as $runtimeSource) {
			if (($runtimeSource['profile'] ?? null) === $phpProfile) {
				$activeRuntimeShallowPath = (string) ($runtimeSource['path'] ?? '');
				break;
			}
		}

		if (!is_string($activeRuntimeShallowPath) || $activeRuntimeShallowPath === '') {
			\scpp_fail('Internal error: missing active STAN runtime shallow source for profile `' . $phpProfile . '`.' . PHP_EOL, 4);
		}

		return [
			'php_profile' => $phpProfile,
			'runtime_shallow_sources' => $runtimeShallowSources,
			'active_runtime_shallow_path' => $activeRuntimeShallowPath,
		];
	}
}
