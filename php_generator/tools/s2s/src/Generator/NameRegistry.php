<?php
declare(strict_types=1);

namespace Scpp\S2S\Generator;

use Scpp\S2S\IR\PhpFile;

/**
 * Collects fully-qualified declarations and resolves PHP names to rooted C++ names.
 *
 * The current pass intentionally prefers explicit rooted C++ emission whenever a
 * PHP target can be resolved with high confidence. This avoids relying on C++
 * lookup when PHP namespace rules differ.
 */
final class NameRegistry
{
	/** @var array<string, bool> */
	private array $classes = [];
	/** @var array<string, bool> */
	private array $functions = [];

	public static function fromPhpFile(PhpFile $file): self
	{
		$registry = new self();

		foreach ($file->classes as $class) {
			$registry->classes[$class->name] = true;
		}
		foreach ($file->functions as $function) {
			$registry->functions[$function->name] = true;
		}
		foreach ($file->namespaces as $namespace) {
			foreach ($namespace->classes as $class) {
				$registry->classes[$namespace->name . '\\' . $class->name] = true;
			}
			foreach ($namespace->functions as $function) {
				$registry->functions[$namespace->name . '\\' . $function->name] = true;
			}
		}

		return $registry;
	}

	public function resolveClass(string $phpName, int $flags, ?string $currentNamespace): ?string
	{
		return $this->resolve($phpName, $flags, $currentNamespace, $this->classes);
	}

	public function resolveFunction(string $phpName, int $flags, ?string $currentNamespace): ?string
	{
		return $this->resolve($phpName, $flags, $currentNamespace, $this->functions);
	}

	/**
	 * @param array<string, bool> $symbols
	 */
	private function resolve(string $phpName, int $flags, ?string $currentNamespace, array $symbols): ?string
	{
		$trimmed = ltrim($phpName, '\\');
		if ($trimmed === '') {
			return null;
		}

		// php-ast exposes rooted names with flags=0 in the fixture set used here.
		if ($flags === 0) {
			return isset($symbols[$trimmed]) ? $trimmed : null;
		}

		if (isset($symbols[$trimmed])) {
			return $trimmed;
		}

		if ($currentNamespace !== null && $currentNamespace !== '') {
			$exactCurrent = $currentNamespace . '\\' . $trimmed;
			if (isset($symbols[$exactCurrent])) {
				return $exactCurrent;
			}
		}

		$anchored = $this->resolveAnchored($trimmed, $currentNamespace, $symbols);
		if ($anchored !== null) {
			return $anchored;
		}

		return null;
	}

	/**
	 * Walk ancestor namespace prefixes and look for a unique declaration ending in the requested tail.
	 *
	 * Example:
	 * - current namespace: StageTwo\Paths\App
	 * - requested tail:    Paths\Lib\Tool
	 * - resolved target:   StageTwo\Paths\Lib\Tool
	 */
	private function resolveAnchored(string $tailName, ?string $currentNamespace, array $symbols): ?string
	{
		$prefixes = $this->namespacePrefixes($currentNamespace);
		$wantSuffix = '\\' . $tailName;

		foreach ($prefixes as $prefix) {
			$matches = [];
			foreach (array_keys($symbols) as $candidate) {
				if ($prefix !== '' && !str_starts_with($candidate, $prefix . '\\')) {
					continue;
				}
				if (str_ends_with($candidate, $wantSuffix)) {
					$matches[] = $candidate;
				}
			}
			if (count($matches) === 1) {
				return $matches[0];
			}
		}

		return null;
	}

	/**
	 * @return list<string>
	 */
	private function namespacePrefixes(?string $namespace): array
	{
		if ($namespace === null || $namespace === '') {
			return [''];
		}

		$parts = explode('\\', $namespace);
		$result = [];
		for ($i = count($parts); $i >= 1; $i--) {
			$result[] = implode('\\', array_slice($parts, 0, $i));
		}
		$result[] = '';
		return $result;
	}
}
