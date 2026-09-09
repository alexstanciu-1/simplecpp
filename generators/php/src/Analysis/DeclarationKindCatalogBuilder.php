<?php
declare(strict_types=1);

namespace Scpp\S2S\Analysis;

use Scpp\S2S\Jss\JssParser;
use Scpp\S2S\Jss\JssSummaryExtractor;
use Scpp\S2S\Jss\JssTokenizer;

final class DeclarationKindCatalogBuilder
{
	public function __construct(
		private readonly FrontEndSymbolExtractor $extractor = new FrontEndSymbolExtractor(),
	)
	{
	}

	/** @param list<string> $sourcePaths @param array<string,string> $sourceOverrides @return array<string,string> */
	public function buildFromSources(array $sourcePaths, array $sourceOverrides = []): array
	{
		$catalog = [];
		foreach ($sourcePaths as $sourcePath) {
			if (!is_string($sourcePath) || $sourcePath === '') {
				continue;
			}
			$sourceOverride = $sourceOverrides[$sourcePath] ?? null;
			if ($this->isJssSourcePath($sourcePath)) {
				$source = $sourceOverride ?? file_get_contents($sourcePath);
				if (!is_string($source)) {
					throw new \RuntimeException('Cannot read JSS declarations from ' . $sourcePath);
				}
				$program = (new JssParser())->parse((new JssTokenizer())->tokenize($source));
				$summary = (new JssSummaryExtractor())->summarize($program, $sourcePath);
			} else {
				$summary = $this->extractor->summarize(
					$this->extractor->extract($sourcePath, $sourceOverride),
					$sourceOverride
				);
			}
			$this->collectFromClasses($catalog, $summary['root_classes'] ?? []);
			foreach (($summary['namespaces'] ?? []) as $namespace) {
				if (!is_array($namespace)) {
					continue;
				}
				$this->collectFromClasses($catalog, $namespace['classes'] ?? []);
			}
		}
		ksort($catalog, SORT_STRING);
		return $catalog;
	}

	/** @param array<string,string> $catalog @param mixed $classes */
	private function collectFromClasses(array &$catalog, mixed $classes): void
	{
		foreach (is_array($classes) ? $classes : [] as $class) {
			if (!is_array($class)) {
				continue;
			}
			$name = trim((string) ($class['name'] ?? ''));
			if ($name === '') {
				continue;
			}
			$namespace = trim((string) ($class['namespace'] ?? ''), '\\');
			$kind = strtolower(trim((string) ($class['declaration_kind'] ?? ((bool) ($class['is_union'] ?? false) ? 'union' : ((bool) ($class['is_struct'] ?? false) ? 'struct' : ((bool) ($class['is_enum'] ?? false) ? 'enum' : 'class'))))));
			if (!in_array($kind, ['class', 'enum', 'struct', 'union'], true)) {
				$kind = 'class';
			}
			$catalog[$name] = $kind;
			if ($namespace !== '') {
				$catalog[$namespace . '\\' . $name] = $kind;
			}
		}
	}

	private function isJssSourcePath(string $path): bool
	{
		return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'jss';
	}
}
