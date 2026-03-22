<?php
declare(strict_types=1);

namespace Scpp\S2S\Loader;

/**
 * Loads a PHP input file together with its sidecar JSON fixture.
 *
 * The generator starts in fixture-driven mode on purpose:
 * - deterministic development
 * - no dependency on the php-ast extension during early implementation
 * - easier debugging against known sample data
 */
final class InputLoader
{
	public function load(string $phpPath): ParsedInput
	{
		if (!is_file($phpPath)) {
			throw new \RuntimeException("Input file not found: {$phpPath}");
		}

		$jsonPath = $phpPath . '.json';
		if (!is_file($jsonPath)) {
			throw new \RuntimeException("Fixture JSON not found next to input file: {$jsonPath}");
		}

		$source = (string) file_get_contents($phpPath);
		$data = json_decode((string) file_get_contents($jsonPath), true, flags: JSON_THROW_ON_ERROR);

		return new ParsedInput(
			path: $phpPath,
			source: $source,
			tokens: $data['tokens'] ?? [],
			ast: $data['ast'] ?? null,
		);
	}
}
