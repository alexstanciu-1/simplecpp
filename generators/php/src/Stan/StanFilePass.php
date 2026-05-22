<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

use Scpp\S2S\Analysis\FrontEndSymbolExtractor;
use Throwable;

final class StanFilePass
{
	public function __construct(
		private readonly StanStateStore $stateStore = new StanStateStore(),
		private readonly FrontEndSymbolExtractor $extractor = new FrontEndSymbolExtractor(),
		private readonly StanPathMapper $pathMapper = new StanPathMapper(),
		private readonly StanDiagnosticCollector $diagnosticCollector = new StanDiagnosticCollector(),
	)
	{
	}

	/** @param array<string,mixed> $state @param array<string,array<string,mixed>> $projectContexts @param list<string> $sourceFiles @return array{files:list<string>,file_summaries:array<string,array<string,mixed>>,files_state:array<string,array<string,mixed>>,analyzed_count:int,reused_count:int,warning_count:int} */
	public function analyze(
		string $projectRoot,
		string $statePath,
		string $cacheDir,
		string $stanSignature,
		array $state,
		array $projectContexts,
		array $sourceFiles,
	): array {
		$files = [];
		foreach ($projectContexts as $contextProjectRoot => $_projectContext) {
			foreach (\collect_project_php_files($contextProjectRoot) as $phpPathAbs) {
				$files[] = \normalize_path($phpPathAbs);
			}
		}
		foreach ($sourceFiles as $sourceFile) {
			$files[] = \normalize_path($sourceFile);
		}
		$files = array_values(array_unique($files));
		sort($files, SORT_STRING);

		$currentFileMetas = [];
		foreach ($files as $sourcePath) {
			$currentFileMetas[$this->pathMapper->sourceKey($projectRoot, $sourcePath)] = \build_file_meta($sourcePath);
		}

		$analyzedCount = 0;
		$reusedCount = 0;
		$warningCount = 0;
		$newFilesState = [];
		$fileSummaries = [];

		foreach ($files as $sourcePath) {
			$relativeKey = $this->pathMapper->sourceKey($projectRoot, $sourcePath);
			$meta = $currentFileMetas[$relativeKey];
			$previous = is_array($state['files'][$relativeKey] ?? null) ? $state['files'][$relativeKey] : null;
			$cachePath = $cacheDir . '/' . sha1($relativeKey) . '.php';
			$needsAnalyze = $this->collectReasons($previous, $meta, $stanSignature, $cachePath, $currentFileMetas, is_array($state['files'] ?? null) ? $state['files'] : []) !== [];
			$summary = null;
			if ($needsAnalyze) {
				try {
					$summary = $this->extractor->summarize($this->extractor->extract($sourcePath));
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
				'is_runtime_shallow' => in_array(\normalize_path($sourcePath), array_map('\normalize_path', $sourceFiles), true),
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

	/** @param array<string,mixed>|null $previous @param array{size:int,mtime:int,content_hash:string} $meta @param array<string,array{size:int,mtime:int,content_hash:string}> $currentFileMetas @param array<string,array<string,mixed>> $previousFilesState @return list<string> */
	private function collectReasons(?array $previous, array $meta, string $stanSignature, string $cachePath, array $currentFileMetas = [], array $previousFilesState = []): array
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
}
