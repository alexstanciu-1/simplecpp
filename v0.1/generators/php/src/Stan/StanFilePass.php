<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

use Scpp\S2S\Analysis\FrontEndSymbolExtractor;
use Scpp\S2S\Jss\JssParser;
use Scpp\S2S\Jss\JssSummaryExtractor;
use Scpp\S2S\Jss\JssTokenizer;
use Throwable;

final class StanFilePass
{
	public function __construct(
		private readonly StanStateStore $stateStore = new StanStateStore(),
		private readonly FrontEndSymbolExtractor $extractor = new FrontEndSymbolExtractor(),
		private readonly JssTokenizer $jssTokenizer = new JssTokenizer(),
		private readonly JssParser $jssParser = new JssParser(),
		private readonly JssSummaryExtractor $jssSummaryExtractor = new JssSummaryExtractor(),
		private readonly StanDiagnosticCollector $diagnosticCollector = new StanDiagnosticCollector(),
	)
	{
	}

	/** @param array<string,mixed> $state @param list<StanSourceUnit> $sourceUnits @return array{files:list<string>,file_summaries:array<string,array<string,mixed>>,files_state:array<string,array<string,mixed>>,analyzed_count:int,reused_count:int,warning_count:int} */
	public function analyze(
		string $projectRoot,
		string $statePath,
		string $cacheDir,
		string $stanSignature,
		array $state,
		array $sourceUnits,
	): array {
		$files = [];

		foreach ($sourceUnits as $sourceUnit) {
			$files[] = $sourceUnit->path;
		}

		$analyzedCount = 0;
		$reusedCount = 0;
		$warningCount = 0;
		$newFilesState = [];
		$fileSummaries = [];

		foreach ($sourceUnits as $sourceUnit) {
			$sourcePath = $sourceUnit->path;
			$relativeKey = $sourceUnit->sourceKey;
			$meta = $sourceUnit->meta;
			$previous = is_array($state['files'][$relativeKey] ?? null) ? $state['files'][$relativeKey] : null;
			$cachePath = $cacheDir . '/' . $this->cacheFileName($relativeKey, $sourceUnit) . '.php';
			$needsAnalyze = $this->collectReasons($previous, $meta, $stanSignature, $cachePath) !== [];
			$summary = null;
			if ($needsAnalyze) {
				try {
					if ($this->isJssSource($sourcePath)) {
						$contents = $sourceUnit->contents ?? file_get_contents($sourcePath);
						if (!is_string($contents)) {
							throw new \RuntimeException('Failed to read JSS input.');
						}
						$summary = $this->jssSummaryExtractor->summarize(
							$this->jssParser->parse($this->jssTokenizer->tokenize($contents)),
							$sourcePath
						);
					} else {
						$summary = $this->extractor->summarize(
							$this->extractor->extract($sourcePath, $sourceUnit->contents),
							$sourceUnit->contents
						);
					}
				} catch (Throwable $throwable) {
					$summary = [
						'path' => $sourcePath,
						'build_errors' => ['STAN extraction failed: ' . $throwable->getMessage()],
						'dependencies' => [],
						'root_uses' => [],
						'root_functions' => [],
						'root_classes' => [],
						'namespaces' => [],
						'scanner_annotations' => [],
						'prologue_includes' => [],
					];
				}
				$apiHash = $this->summaryApiHash($summary);
				$bodyHash = $this->summaryBodyHash($summary);
				$this->stateStore->save($cachePath, [
					'version' => 1,
					'source_path' => $sourcePath,
					'source_key' => $relativeKey,
					'source_meta' => $meta,
					'stan_signature' => $stanSignature,
					'api_hash' => $apiHash,
					'body_hash' => $bodyHash,
					'summary' => $summary,
				]);
				$analyzedCount++;
			} else {
				$cached = $this->stateStore->load($cachePath);
				$summary = is_array($cached['summary'] ?? null) ? $cached['summary'] : [
					'path' => $sourcePath,
					'build_errors' => ['STAN cache missing summary for reused file.'],
					'dependencies' => [],
					'root_uses' => [],
					'root_functions' => [],
					'root_classes' => [],
					'namespaces' => [],
					'scanner_annotations' => [],
					'prologue_includes' => [],
				];
				$reusedCount++;
			}

			$apiHash = isset($apiHash) && is_string($apiHash) ? $apiHash : $this->summaryApiHash($summary);
			$bodyHash = isset($bodyHash) && is_string($bodyHash) ? $bodyHash : $this->summaryBodyHash($summary);
			$summary['is_runtime_shallow'] = $sourceUnit->isRuntimeShallow;
			$fileWarningCount = $this->diagnosticCollector->countWarnings($summary);
			$warningCount += $fileWarningCount;
			$fileSummaries[$relativeKey] = $summary;
			$newFilesState[$relativeKey] = [
				'size' => $meta['size'],
				'mtime' => $meta['mtime'],
				'content_hash' => $meta['content_hash'],
				'api_hash' => $apiHash,
				'body_hash' => $bodyHash,
				'file_warning_count' => $fileWarningCount,
				'stan_signature' => $stanSignature,
				'cache_path' => \normalize_path($cachePath),
				'is_runtime_shallow' => $sourceUnit->isRuntimeShallow,
			];
			unset($apiHash, $bodyHash);
		}

		return [
			'files' => $files,
			'file_summaries' => $fileSummaries,
			'files_state' => $newFilesState,
			'analyzed_count' => $analyzedCount,
			'reused_count' => $reusedCount,
			'warning_count' => $warningCount,
		];
	}

	private function cacheFileName(string $relativeKey, StanSourceUnit $sourceUnit): string
	{
		if ($sourceUnit->contents !== null) {
			return sha1($relativeKey . "\0override\0" . $sourceUnit->meta['content_hash']);
		}
		return sha1($relativeKey);
	}

	/** @param array<string,mixed>|null $previous @param array{size:int,mtime:int,content_hash:string} $meta @return list<string> */
	private function collectReasons(?array $previous, array $meta, string $stanSignature, string $cachePath): array
	{
		$reasons = [];
		if (!is_array($previous)) {
			$reasons[] = 'new source file';
			return $reasons;
		}
		if (!isset($previous['size'], $previous['mtime'], $previous['content_hash'], $previous['stan_signature'])) {
			$reasons[] = 'cached STAN metadata incomplete';
		}
		if ((string) ($previous['stan_signature'] ?? '') !== $stanSignature) {
			$reasons[] = 'STAN signature changed';
		}
		if ((int) ($previous['size'] ?? -1) !== $meta['size']) {
			$reasons[] = 'source file size changed';
		}
		if ((string) ($previous['content_hash'] ?? '') !== $meta['content_hash']) {
			$reasons[] = 'source file content changed';
		}
		if (!is_file($cachePath)) {
			$reasons[] = 'per-file STAN cache missing';
		}
		return $reasons;
	}

	/** @param array<string,mixed> $summary */
	private function summaryApiHash(array $summary): string
	{
		return hash('sha256', serialize($this->summaryApiSurface($summary)));
	}

	/** @param array<string,mixed> $summary */
	private function summaryBodyHash(array $summary): string
	{
		return hash('sha256', serialize($summary));
	}

	/** @param array<string,mixed> $summary @return array<string,mixed> */
	private function summaryApiSurface(array $summary): array
	{
		return [
			'prologue_includes' => $summary['prologue_includes'] ?? [],
			'root_uses' => $summary['root_uses'] ?? [],
			'root_constants' => $summary['root_constants'] ?? [],
			'root_functions' => $this->functionApiList($summary['root_functions'] ?? []),
			'root_classes' => $this->classApiList($summary['root_classes'] ?? []),
			'namespaces' => $this->namespaceApiList($summary['namespaces'] ?? []),
			'dependencies' => $this->apiDependencies($summary['dependencies'] ?? []),
			'build_errors' => $summary['build_errors'] ?? [],
			'scanner_annotations' => $summary['scanner_annotations'] ?? [],
		];
	}

	/** @param mixed $functions @return list<array<string,mixed>> */
	private function functionApiList(mixed $functions): array
	{
		$results = [];
		foreach (is_array($functions) ? $functions : [] as $function) {
			if (is_array($function)) {
				$results[] = $this->functionApi($function);
			}
		}
		return $results;
	}

	/** @param array<string,mixed> $function @return array<string,mixed> */
	private function functionApi(array $function): array
	{
		return [
			'name' => $function['name'] ?? '',
			'namespace' => $function['namespace'] ?? null,
			'params' => $function['params'] ?? [],
			'return_type' => $function['return_type'] ?? null,
			'line' => $function['line'] ?? 0,
			'returns_by_reference' => (bool) ($function['returns_by_reference'] ?? false),
			'is_lib_export' => (bool) ($function['is_lib_export'] ?? false),
			'is_synthetic_entrypoint' => (bool) ($function['is_synthetic_entrypoint'] ?? false),
		];
	}

	/** @param mixed $classes @return list<array<string,mixed>> */
	private function classApiList(mixed $classes): array
	{
		$results = [];
		foreach (is_array($classes) ? $classes : [] as $class) {
			if (is_array($class)) {
				$results[] = $this->classApi($class);
			}
		}
		return $results;
	}

	/** @param array<string,mixed> $class @return array<string,mixed> */
	private function classApi(array $class): array
	{
		$methods = [];
		foreach (is_array($class['methods'] ?? null) ? $class['methods'] : [] as $method) {
			if (is_array($method)) {
				$methods[] = [
					'name' => $method['name'] ?? '',
					'params' => $method['params'] ?? [],
					'return_type' => $method['return_type'] ?? null,
					'line' => $method['line'] ?? 0,
					'returns_by_reference' => (bool) ($method['returns_by_reference'] ?? false),
					'is_static' => (bool) ($method['is_static'] ?? false),
					'visibility' => $method['visibility'] ?? 'public',
				];
			}
		}
		return [
			'name' => $class['name'] ?? '',
			'namespace' => $class['namespace'] ?? null,
			'line' => $class['line'] ?? 0,
			'parent_class' => $class['parent_class'] ?? null,
			'interfaces' => $class['interfaces'] ?? [],
			'is_interface' => (bool) ($class['is_interface'] ?? false),
			'is_abstract' => (bool) ($class['is_abstract'] ?? false),
			'is_enum' => (bool) ($class['is_enum'] ?? false),
			'is_struct' => (bool) ($class['is_struct'] ?? false),
			'is_union' => (bool) ($class['is_union'] ?? false),
			'declaration_kind' => $class['declaration_kind'] ?? 'class',
			'is_lib_export' => (bool) ($class['is_lib_export'] ?? false),
			'methods' => $methods,
			'properties' => $class['properties'] ?? [],
			'constants' => $class['constants'] ?? [],
		];
	}

	/** @param mixed $namespaces @return list<array<string,mixed>> */
	private function namespaceApiList(mixed $namespaces): array
	{
		$results = [];
		foreach (is_array($namespaces) ? $namespaces : [] as $namespace) {
			if (!is_array($namespace)) {
				continue;
			}
			$results[] = [
				'name' => $namespace['name'] ?? '',
				'uses' => $namespace['uses'] ?? [],
				'constants' => $namespace['constants'] ?? [],
				'functions' => $this->functionApiList($namespace['functions'] ?? []),
				'classes' => $this->classApiList($namespace['classes'] ?? []),
			];
		}
		return $results;
	}

	/** @param mixed $dependencies @return list<array<string,mixed>> */
	private function apiDependencies(mixed $dependencies): array
	{
		$results = [];
		foreach (is_array($dependencies) ? $dependencies : [] as $dependency) {
			if (!is_array($dependency)) {
				continue;
			}
			$kind = (string) ($dependency['kind'] ?? '');
			if ($kind === '' || str_contains($kind, '_body_') || str_starts_with($kind, 'executable_body')) {
				continue;
			}
			$results[] = $dependency;
		}
		return $results;
	}

	private function isJssSource(string $path): bool
	{
		return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'jss';
	}
}
