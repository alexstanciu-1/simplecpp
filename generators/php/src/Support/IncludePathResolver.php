<?php
declare(strict_types=1);

namespace Scpp\S2S\Support;

final class IncludePathResolver
{
	public function __construct(
		private readonly ?string $includingProjectRoot = null,
		private readonly ?string $includingGeneratedDir = null,
	) {
	}

	public function resolve(string $includingSourcePath, string $literalPath): string
	{
		$trimmedLiteral = trim($literalPath);
		if ($trimmedLiteral === '') {
			return $literalPath;
		}

		$sourceSuffixMapped = $this->mapSupportedSourceExtensionToHeader($trimmedLiteral);
		if ($this->includingProjectRoot === null || $this->includingGeneratedDir === null) {
			return $sourceSuffixMapped;
		}

		$includingSourceAbs = $this->normalizePath($includingSourcePath);
		$resolvedSourceAbs = $this->normalizePath(dirname($includingSourceAbs) . '/' . $trimmedLiteral);
		$targetProject = $this->findProjectConfigForPath($resolvedSourceAbs);
		if ($targetProject === null) {
			throw new InputException('Prism++ require_once target must resolve into a Prism project: ' . $trimmedLiteral . ' from ' . $includingSourcePath);
		}

		$includingRelativePhp = $this->relativePath($this->includingProjectRoot, $includingSourceAbs);
		if (str_starts_with($includingRelativePhp, '..')) {
			throw new InputException('Including source must stay inside its Prism project root: ' . $includingSourcePath);
		}

		$targetRelativePhp = $this->relativePath($targetProject['project_root'], $resolvedSourceAbs);
		if (str_starts_with($targetRelativePhp, '..')) {
			throw new InputException('Prism++ require_once target escaped its resolved project root: ' . $trimmedLiteral);
		}

		$includingGeneratedHeader = $this->buildGeneratedHeaderPath($this->includingGeneratedDir, $includingRelativePhp);
		$targetGeneratedHeader = $this->buildGeneratedHeaderPath($targetProject['generated_dir'], $targetRelativePhp);

		return $this->normalizeConfigPath(
			$this->relativePath(dirname($includingGeneratedHeader), $targetGeneratedHeader)
		);
	}

	private function buildGeneratedHeaderPath(string $generatedDir, string $relativePhp): string
	{
		$trimmed = $this->stripSupportedSourceExtension($relativePhp);
		if ($trimmed === '') {
			$trimmed = 'entry';
		}
		return $this->normalizePath($generatedDir . '/' . $trimmed . '.hpp');
	}

	private function mapSupportedSourceExtensionToHeader(string $path): string
	{
		$normalized = $this->normalizeConfigPath($path);
		foreach (['phs', 'php'] as $extension) {
			$suffix = '.' . $extension;
			if (str_ends_with(strtolower($normalized), $suffix)) {
				return substr($normalized, 0, -strlen($suffix)) . '.hpp';
			}
		}
		return $normalized;
	}

	private function stripSupportedSourceExtension(string $path): string
	{
		$normalized = $this->normalizeConfigPath($path);
		foreach (['phs', 'php'] as $extension) {
			$suffix = '.' . $extension;
			if (str_ends_with(strtolower($normalized), $suffix)) {
				return substr($normalized, 0, -strlen($suffix));
			}
		}
		return $normalized;
	}

	/** @return array{project_root:string,generated_dir:string}|null */
	private function findProjectConfigForPath(string $path): ?array
	{
		$current = is_dir($path) ? $this->normalizePath($path) : $this->normalizePath(dirname($path));
		while (true) {
			$configPath = $current . '/prism.json';
			if (is_file($configPath)) {
				$config = $this->loadProjectConfig($configPath);
				$generatedDir = $config['generated_dir'] ?? '.prism/generated';
				if (!is_string($generatedDir) || trim($generatedDir) === '') {
					$generatedDir = '.prism/generated';
				}
				return [
					'project_root' => $current,
					'generated_dir' => $this->normalizePath($current . '/' . $this->normalizeConfigPath($generatedDir)),
				];
			}

			$parent = dirname($current);
			if ($parent === $current) {
				return null;
			}
			$current = $parent;
		}
	}

	/** @return array<string,mixed> */
	private function loadProjectConfig(string $configPath): array
	{
		$json = file_get_contents($configPath);
		if ($json === false) {
			throw new InputException('Failed to read Prism project config: ' . $configPath);
		}

		try {
			$config = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
		} catch (\JsonException $e) {
			throw new InputException('Invalid JSON in Prism project config: ' . $configPath . ' (' . $e->getMessage() . ')', 0, $e);
		}

		if (!is_array($config)) {
			throw new InputException('Invalid Prism project config shape: ' . $configPath);
		}

		return $config;
	}

	private function normalizeConfigPath(string $path): string
	{
		return str_replace('\\', '/', $path);
	}

	private function normalizePath(string $path): string
	{
		$normalized = $this->normalizeConfigPath($path);
		$isAbsolute = str_starts_with($normalized, '/')
			|| preg_match('/^[A-Za-z]:\//', $normalized) === 1;

		$prefix = '';
		if (preg_match('/^[A-Za-z]:\//', $normalized) === 1) {
			$prefix = substr($normalized, 0, 2);
			$normalized = substr($normalized, 2);
		}

		$segments = explode('/', $normalized);
		$result = [];
		foreach ($segments as $segment) {
			if ($segment === '' || $segment === '.') {
				continue;
			}
			if ($segment === '..') {
				$last = end($result);
				if ($last !== false && $last !== '..') {
					array_pop($result);
					continue;
				}
				if (!$isAbsolute) {
					$result[] = $segment;
				}
				continue;
			}
			$result[] = $segment;
		}

		$joined = implode('/', $result);
		if ($prefix !== '') {
			return $prefix . '/' . $joined;
		}
		if ($isAbsolute) {
			return '/' . $joined;
		}
		return $joined === '' ? '.' : $joined;
	}

	private function relativePath(string $from, string $to): string
	{
		$fromNormalized = $this->normalizePath($from);
		$toNormalized = $this->normalizePath($to);

		$fromParts = $this->pathSegments($fromNormalized);
		$toParts = $this->pathSegments($toNormalized);

		$max = min(count($fromParts), count($toParts));
		$common = 0;
		while ($common < $max && $fromParts[$common] === $toParts[$common]) {
			$common++;
		}

		$relativeParts = array_fill(0, count($fromParts) - $common, '..');
		for ($i = $common; $i < count($toParts); $i++) {
			$relativeParts[] = $toParts[$i];
		}

		if ($relativeParts === []) {
			return '.';
		}

		return implode('/', $relativeParts);
	}

	/** @return list<string> */
	private function pathSegments(string $path): array
	{
		$normalized = $this->normalizeConfigPath($path);
		if (preg_match('/^[A-Za-z]:\//', $normalized) === 1) {
			$normalized = substr($normalized, 3);
		} else {
			$normalized = ltrim($normalized, '/');
		}
		if ($normalized === '') {
			return [];
		}
		return array_values(array_filter(explode('/', $normalized), static fn (string $segment): bool => $segment !== ''));
	}
}
