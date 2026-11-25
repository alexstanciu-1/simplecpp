<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanPathMapper
{
	public function sourceKey(string $projectRoot, string $sourcePath): string
	{
		$normalizedProjectRoot = \normalize_path($projectRoot);
		$normalizedSourcePath = \normalize_path($sourcePath);
		if (\path_is_inside($normalizedProjectRoot, $normalizedSourcePath)) {
			return \normalize_config_path(\relative_path($normalizedProjectRoot, $normalizedSourcePath));
		}
		return '@external/' . sha1($normalizedSourcePath) . '/' . basename($normalizedSourcePath);
	}
}
