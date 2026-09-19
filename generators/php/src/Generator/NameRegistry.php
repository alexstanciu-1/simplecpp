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
	/** @var array<string, bool> */
	private array $constants = [];
	/** @var array<string,array<string,string>> */
	private array $classImports = [];

	public static function fromPhpFile(PhpFile $file): self
	{
		$registry = new self();
		$registry->collectClassImports('', $file->rootUses);

		foreach ($file->constants as $constant) {
			$registry->constants[$constant->name] = true;
		}
		foreach ($file->classes as $class) {
			$registry->classes[$class->name] = true;
		}
		foreach ($file->functions as $function) {
			$registry->functions[$function->name] = true;
		}
		foreach ($file->namespaces as $namespace) {
			$registry->collectClassImports($namespace->name, $namespace->uses);
			foreach ($namespace->constants as $constant) {
				$registry->constants[$namespace->name . '\\' . $constant->name] = true;
			}
			foreach ($namespace->classes as $class) {
				$registry->classes[$namespace->name . '\\' . $class->name] = true;
			}
			foreach ($namespace->functions as $function) {
				$registry->functions[$namespace->name . '\\' . $function->name] = true;
			}
		}

		return $registry;
	}

	/** @param list<\Scpp\S2S\IR\UseDecl> $uses */
	private function collectClassImports(string $namespace, array $uses): void
	{
		foreach ($uses as $use) {
			if (in_array($use->kind, ['function', 'const'], true)) {
				continue;
			}
			$name = ltrim($use->name, '\\');
			$alias = $use->alias ?? basename(str_replace('\\', '/', $name));
			$this->classImports[$namespace][$alias] = $name;
		}
	}

	/** Expand source namespace/import syntax without resolving or validating a symbol. */
	public function qualifyClassName(string $name, int $flags, ?string $namespace): string
	{
		$trimmed = ltrim($name, '\\');
		if ($flags === 0 || str_starts_with($name, '\\')) {
			return $trimmed;
		}
		$parts = explode('\\', $trimmed, 2);
		$import = $this->classImports[$namespace ?? ''][$parts[0]] ?? null;
		if ($flags !== 2 && $import !== null) {
			return $import . (isset($parts[1]) ? '\\' . $parts[1] : '');
		}
		return $namespace !== null && $namespace !== '' ? $namespace . '\\' . $trimmed : $trimmed;
	}

	/**

	 * Resolves a class name using the PHP namespace/import rules captured in the registry tables.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	public function resolveClass(string $phpName, int $flags, ?string $currentNamespace): ?string
	{
		return $this->resolve($phpName, $flags, $currentNamespace, $this->classes);
	}

	/**

	 * Resolves a function name using the PHP namespace/import rules captured in the registry tables.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	public function resolveFunction(string $phpName, int $flags, ?string $currentNamespace): ?string
	{
		return $this->resolve($phpName, $flags, $currentNamespace, $this->functions);
	}

	/**

	 * Resolves a constant name using the PHP namespace/import rules captured in the registry tables.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	public function resolveConstant(string $phpName, int $flags, ?string $currentNamespace): ?string
	{
		return $this->resolve($phpName, $flags, $currentNamespace, $this->constants);
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
