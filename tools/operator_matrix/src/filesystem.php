<?php

declare(strict_types=1);

/**
 * Filesystem helpers for the operator-matrix tool.
 */
function om_normalize_path(string $path): string
{
	$normalized = str_replace('\\', '/', $path);
	return rtrim($normalized, '/');
}

/**
 * @return array<string, mixed>
 */
function om_read_json_file(string $path): array
{
	if (!is_file($path)) {
		throw new RuntimeException('JSON file not found: ' . $path);
	}

	$content = file_get_contents($path);
	if ($content === false) {
		throw new RuntimeException('Unable to read JSON file: ' . $path);
	}

	$data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
	if (!is_array($data)) {
		throw new RuntimeException('JSON root must decode to an object: ' . $path);
	}

	return $data;
}

function om_ensure_directory(string $path): void
{
	if (is_dir($path)) {
		return;
	}

	if (!mkdir($path, 0777, true) && !is_dir($path)) {
		throw new RuntimeException('Unable to create directory: ' . $path);
	}
}

/**
 * @param array<mixed> $data
 */
function om_write_json_file(string $path, array $data): void
{
	$encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
	if ($encoded === false) {
		throw new RuntimeException('Unable to encode JSON for: ' . $path);
	}

	$encoded .= PHP_EOL;
	if (file_put_contents($path, $encoded) === false) {
		throw new RuntimeException('Unable to write JSON file: ' . $path);
	}
}
