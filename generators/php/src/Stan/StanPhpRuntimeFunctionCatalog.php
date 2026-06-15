<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanPhpRuntimeFunctionCatalog
{
	/** @var array<string,bool>|null */
	private static ?array $knownFunctions = null;

	public function hasFunction(string $name): bool
	{
		$normalized = strtolower(trim($name));
		if ($normalized === '') {
			return false;
		}
		$known = self::$knownFunctions;
		if ($known === null) {
			$known = $this->loadKnownFunctions();
			self::$knownFunctions = $known;
		}
		return isset($known[$normalized]);
	}

	/** @return array<string,bool> */
	private function loadKnownFunctions(): array
	{
		$known = [];
		foreach ([
			__DIR__ . '/../../specs/php_runtime_symbols_legacy.json',
			__DIR__ . '/../../specs/php_runtime_symbols_strict.json',
		] as $path) {
			if (!is_file($path)) {
				continue;
			}
			$decoded = json_decode((string) file_get_contents($path), true);
			$targets = is_array($decoded['php_runtime_symbol_targets'] ?? null) ? $decoded['php_runtime_symbol_targets'] : [];
			foreach ($targets as $name => $_target) {
				if (is_string($name) && $name !== '') {
					$known[strtolower($name)] = true;
				}
			}
		}
		return $known;
	}
}
