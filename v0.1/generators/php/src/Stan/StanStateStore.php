<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanStateStore
{
	/** @return array<string,mixed> */
	public function load(string $statePath): array
	{
		if (!is_file($statePath)) {
			return ['version' => 1, 'files' => []];
		}

		$state = require $statePath;
		if (!is_array($state)) {
			return ['version' => 1, 'files' => []];
		}
		if (!isset($state['files']) || !is_array($state['files'])) {
			$state['files'] = [];
		}
		return $state;
	}

	/** @param array<string,mixed> $state */
	public function save(string $statePath, array $state): void
	{
		$contents = "<?php\nreturn " . var_export($state, true) . ";\n";
		\write_text_file($statePath, $contents);
	}
}
