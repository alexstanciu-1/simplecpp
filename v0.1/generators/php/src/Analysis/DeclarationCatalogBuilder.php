<?php
declare(strict_types=1);

namespace Scpp\S2S\Analysis;

use Scpp\S2S\Generator\NameRegistry;
use Scpp\S2S\IR\PhpFile;
use Scpp\S2S\Lowering\TypeMapper;
use Scpp\S2S\Jss\JssParser;
use Scpp\S2S\Jss\JssSummaryExtractor;
use Scpp\S2S\Jss\JssTokenizer;

final class DeclarationCatalogBuilder
{
	public function __construct(
		private readonly FrontEndSymbolExtractor $extractor = new FrontEndSymbolExtractor(),
	)
	{
	}

	/** @param list<string> $sourcePaths @param array<string,string> $sourceOverrides @return array<string,string> */
	public function buildFromSources(array $sourcePaths, array $sourceOverrides = []): array
	{
		return $this->buildCatalogFromSources($sourcePaths, $sourceOverrides)['declared_type_kinds'];
	}

	/** Collect structural declarations only; no expression inference or override validation. */
	public function buildCatalogFromSources(array $sourcePaths, array $sourceOverrides = []): array
	{
		$catalog = [];
		$accessors = [];
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
				$file = $this->extractor->extract($sourcePath, $sourceOverride);
				$summary = $this->extractor->summarize($file, $sourceOverride);
				$accessors = array_replace($accessors, self::accessorsFromFile($file));
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
		ksort($accessors, SORT_STRING);
		return ['declared_type_kinds' => $catalog, 'accessor_declarations' => $accessors];
	}

	/** Return fully qualified ancestor edges and eligible named-object accessor signatures. */
	public static function accessorsFromFile(PhpFile $file): array
	{
		$registry = NameRegistry::fromPhpFile($file);
		$mapper = new TypeMapper();
		$groups = [[null, $file->classes]];
		foreach ($file->namespaces as $block) {
			$groups[] = [$block->name, $block->classes];
		}
		$result = [];
		foreach ($groups as [$namespace, $classes]) {
			$qualify = static fn (string $name): string => $registry->qualifyClassName($name, str_starts_with($name, '\\') ? 0 : 1, $namespace);
			foreach ($classes as $class) {
				$parents = $class->interfaces;
				if ($class->parentClass !== null) {
					$parents[] = $class->parentClass;
				}
				$methods = [];
				foreach ($class->methods as $method) {
					if ($method->isStatic || $method->returnsByReference || $method->params !== [] || $method->visibility === 'private' || $method->returnType === null) {
						continue;
					}
					$type = $method->returnType;
					if (!str_starts_with($mapper->mapReturnType($type, false), 'shared_p<')) {
						continue;
					}
					// Preserve explicit wrappers for local lowering; this catalog covers named returns.
					if (strpbrk($type, '<>|?') !== false) {
						continue;
					}
					$methods[$method->name] = $qualify($type);
				}
				ksort($methods, SORT_STRING);
				$result[$qualify($class->name)] = ['parents' => array_map($qualify, $parents), 'methods' => $methods];
			}
		}
		ksort($result, SORT_STRING);
		return $result;
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
