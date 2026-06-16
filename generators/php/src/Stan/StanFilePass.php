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
		bool $respectDependencyInvalidation = true,
	): array {
		$files = [];

		$currentFileMetas = [];
		foreach ($sourceUnits as $sourceUnit) {
			$files[] = $sourceUnit->path;
			$currentFileMetas[$sourceUnit->sourceKey] = $sourceUnit->meta;
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
			$needsAnalyze = $this->collectReasons($previous, $meta, $stanSignature, $cachePath, $currentFileMetas, is_array($state['files'] ?? null) ? $state['files'] : [], $respectDependencyInvalidation) !== [];
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
				$this->stateStore->save($cachePath, [
					'version' => 1,
					'source_path' => $sourcePath,
					'source_key' => $relativeKey,
					'source_meta' => $meta,
					'stan_signature' => $stanSignature,
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

			$warningCount += $this->diagnosticCollector->countWarnings($summary);
			$fileSummaries[$relativeKey] = $summary;
			$newFilesState[$relativeKey] = [
				'size' => $meta['size'],
				'mtime' => $meta['mtime'],
				'content_hash' => $meta['content_hash'],
				'stan_signature' => $stanSignature,
				'cache_path' => \normalize_path($cachePath),
				'is_runtime_shallow' => $sourceUnit->isRuntimeShallow,
			];
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

	/** @param array<string,mixed>|null $previous @param array{size:int,mtime:int,content_hash:string} $meta @param array<string,array{size:int,mtime:int,content_hash:string}> $currentFileMetas @param array<string,array<string,mixed>> $previousFilesState @return list<string> */
	private function collectReasons(?array $previous, array $meta, string $stanSignature, string $cachePath, array $currentFileMetas = [], array $previousFilesState = [], bool $respectDependencyInvalidation = true): array
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
		if (!$respectDependencyInvalidation) {
			return $reasons;
		}
		return array_merge($reasons, $this->collectDependencyReasons($previous, $currentFileMetas, $previousFilesState));
	}

	/** @param array<string,mixed> $previous @param array<string,array{size:int,mtime:int,content_hash:string}> $currentFileMetas @param array<string,array<string,mixed>> $previousFilesState @return list<string> */
	private function collectDependencyReasons(array $previous, array $currentFileMetas, array $previousFilesState): array
	{
		$reasons = [];
		$dependencyKeys = $previous['dependency_keys'] ?? [];
		if (!is_array($dependencyKeys)) {
			return $reasons;
		}
		foreach ($dependencyKeys as $dependencyKey) {
			if (!is_string($dependencyKey) || $dependencyKey === '') {
				continue;
			}
			$previousDependency = is_array($previousFilesState[$dependencyKey] ?? null) ? $previousFilesState[$dependencyKey] : null;
			$currentDependency = is_array($currentFileMetas[$dependencyKey] ?? null) ? $currentFileMetas[$dependencyKey] : null;
			if ($previousDependency === null) {
				$reasons[] = 'dependency metadata missing for `' . $dependencyKey . '`';
				continue;
			}
			if ($currentDependency === null) {
				$reasons[] = 'dependency source removed `' . $dependencyKey . '`';
				continue;
			}
			if ((int) ($previousDependency['size'] ?? -1) !== $currentDependency['size']) {
				$reasons[] = 'dependency size changed `' . $dependencyKey . '`';
				continue;
			}
			if ((string) ($previousDependency['content_hash'] ?? '') !== $currentDependency['content_hash']) {
				$reasons[] = 'dependency content changed `' . $dependencyKey . '`';
			}
		}
		return $reasons;
	}

	private function isJssSource(string $path): bool
	{
		return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'jss';
	}
}
