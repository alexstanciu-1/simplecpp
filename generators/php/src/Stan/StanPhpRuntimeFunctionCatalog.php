<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanPhpRuntimeFunctionCatalog
{
	/** @var array<string,bool>|null */
	private static ?array $knownFunctions = null;
	/** @var array<string,string>|null */
	private static ?array $returnTypes = null;
	/** @var array<string,string>|null */
	private static ?array $knownConstants = null;

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

	public function returnType(string $name): ?string
	{
		$normalized = strtolower(trim($name));
		if ($normalized === '') {
			return null;
		}
		$returnTypes = self::$returnTypes;
		if ($returnTypes === null) {
			$returnTypes = $this->loadReturnTypes();
			self::$returnTypes = $returnTypes;
		}
		return $returnTypes[$normalized] ?? null;
	}

	public function hasConstant(string $name): bool
	{
		$normalized = strtoupper(trim($name));
		if ($normalized === '') {
			return false;
		}
		$known = self::$knownConstants;
		if ($known === null) {
			$known = $this->loadKnownConstants();
			self::$knownConstants = $known;
		}
		return isset($known[$normalized]);
	}

	public function constantRequiredModule(string $name): ?string
	{
		$normalized = strtoupper(trim($name));
		if ($normalized === '') {
			return null;
		}
		$known = self::$knownConstants;
		if ($known === null) {
			$known = $this->loadKnownConstants();
			self::$knownConstants = $known;
		}
		return $known[$normalized] ?? null;
	}

	public function requiredModule(string $name): ?string
	{
		$normalized = strtolower(trim($name));
		if ($normalized === '') {
			return null;
		}
		if (str_starts_with($normalized, 'fs_') || str_starts_with($normalized, 'io_')) {
			return 'filesystem';
		}
		if (str_starts_with($normalized, 'json_')) {
			return 'json';
		}
		if (str_starts_with($normalized, 'dt_')) {
			return 'datetime';
		}
		if (str_starts_with($normalized, 'regex_')) {
			return 'regex';
		}
		if (str_starts_with($normalized, 'curl_')) {
			return 'curl';
		}
		return null;
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
		$known['async_sleep_ms'] = true;
		$known['async_wait'] = true;
		return $known;
	}

	/** @return array<string,string> */
	private function loadReturnTypes(): array
	{
		$returnTypes = [];
		foreach ([
			__DIR__ . '/../../../../runtime/generated/stan/runtime_symbols_legacy.php',
			__DIR__ . '/../../../../runtime/generated/stan/runtime_symbols_strict.phs',
		] as $path) {
			if (!is_file($path)) {
				continue;
			}
			$contents = file_get_contents($path);
			if (!is_string($contents)) {
				continue;
			}
			if (preg_match_all('/function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\([^)]*\)\s*:\s*([^\\s{]+)\s*\\{/m', $contents, $matches, PREG_SET_ORDER) > 0) {
				foreach ($matches as $match) {
					$returnTypes[strtolower((string) $match[1])] = (string) $match[2];
				}
			}
			if (preg_match_all('/function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\([^)]*\)\s*\\/\\*\\*\\s*([^*]+?)\\s*\\*\\//m', $contents, $matches, PREG_SET_ORDER) > 0) {
				foreach ($matches as $match) {
					$returnTypes[strtolower((string) $match[1])] = trim((string) $match[2]);
				}
			}
		}
		$returnTypes['async_sleep_ms'] = 'void';
		$returnTypes['async_wait'] = 'mixed';
		return $returnTypes;
	}

	/** @return array<string,string> */
	private function loadKnownConstants(): array
	{
		$curlConstants = [
			'CURLOPT_URL',
			'CURLOPT_RETURNTRANSFER',
			'CURLOPT_HTTPHEADER',
			'CURLOPT_POST',
			'CURLOPT_POSTFIELDS',
			'CURLOPT_CUSTOMREQUEST',
			'CURLOPT_TIMEOUT',
			'CURLOPT_CONNECTTIMEOUT',
			'CURLOPT_FOLLOWLOCATION',
			'CURLOPT_USERAGENT',
			'CURLOPT_SSL_VERIFYPEER',
			'CURLOPT_SSL_VERIFYHOST',
			'CURLINFO_RESPONSE_CODE',
			'CURLINFO_EFFECTIVE_URL',
			'CURLINFO_CONTENT_TYPE',
			'CURLINFO_TOTAL_TIME_MS',
			'CURLINFO_HEADER_SIZE',
			'CURLINFO_REQUEST_SIZE',
			'CURLINFO_REDIRECT_COUNT',
		];
		$known = [];
		foreach ($curlConstants as $constant) {
			$known[$constant] = 'curl';
		}
		return $known;
	}
}
