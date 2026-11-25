<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanLspServerSession
{
	/** @var array<string,array<string,mixed>> */
	private array $snapshotCache = [];
	/** @var array<string,array{source:string,version:int|null}> */
	private array $documentOverlays = [];
	private ?string $projectRoot = null;
	private ?string $configPath = null;

	public function __construct(
		private readonly StanWorkspaceSession $workspaceSession = new StanWorkspaceSession(),
	)
	{
	}

	public function initializeProject(string $projectRoot, string $configPath): void
	{
		$normalizedProjectRoot = \normalize_path($projectRoot);
		$normalizedConfigPath = \normalize_path($configPath);
		if ($this->projectRoot === $normalizedProjectRoot && $this->configPath === $normalizedConfigPath) {
			return;
		}
		$this->projectRoot = $normalizedProjectRoot;
		$this->configPath = $normalizedConfigPath;
		$this->snapshotCache = [];
	}

	/** @param array<string,string> $sourceOverrides @return array<string,mixed> */
	public function documentDiagnostics(string $documentPath, array $sourceOverrides = [], bool $debug = false): array
	{
		[$snapshot, $cacheStatus] = $this->getSnapshot($sourceOverrides);
		$result = \build_stan_document_diagnostics_from_snapshot($this->workspaceSession, $snapshot, $documentPath);
		return $debug ? $this->attachDebugMetadata($result, $snapshot, 'serve', $cacheStatus) : $this->stripSnapshotDebug($result);
	}

	/** @param array<string,string> $sourceOverrides @return array<string,mixed> */
	public function documentSymbols(string $documentPath, array $sourceOverrides = [], bool $debug = false): array
	{
		[$snapshot, $cacheStatus] = $this->getSnapshot($sourceOverrides);
		$result = \build_stan_document_symbols_from_snapshot($this->workspaceSession, $snapshot, $documentPath);
		return $debug ? $this->attachDebugMetadata($result, $snapshot, 'serve', $cacheStatus) : $this->stripSnapshotDebug($result);
	}

	/** @param array<string,string> $sourceOverrides @return array<string,mixed> */
	public function hover(string $documentPath, int $line, ?int $column = null, array $sourceOverrides = [], bool $debug = false): array
	{
		[$snapshot, $cacheStatus] = $this->getSnapshot($sourceOverrides);
		$result = \build_stan_hover_from_snapshot($this->workspaceSession, $snapshot, $documentPath, $line, $column);
		return $debug ? $this->attachDebugMetadata($result, $snapshot, 'serve', $cacheStatus) : $this->stripSnapshotDebug($result);
	}

	/** @param array<string,string> $sourceOverrides @return array<string,mixed> */
	public function definition(string $documentPath, int $line, ?int $column = null, array $sourceOverrides = [], bool $debug = false): array
	{
		[$snapshot, $cacheStatus] = $this->getSnapshot($sourceOverrides);
		$result = \build_stan_definition_from_snapshot($this->workspaceSession, $snapshot, $documentPath, $line, $column);
		return $debug ? $this->attachDebugMetadata($result, $snapshot, 'serve', $cacheStatus) : $this->stripSnapshotDebug($result);
	}

	/** @param array<string,string> $sourceOverrides @return array<string,mixed> */
	public function references(string $documentPath, int $line, ?int $column = null, array $sourceOverrides = [], bool $debug = false): array
	{
		[$snapshot, $cacheStatus] = $this->getSnapshot($sourceOverrides);
		$result = \build_stan_references_from_snapshot($this->workspaceSession, $snapshot, $documentPath, $line, $column);
		return $debug ? $this->attachDebugMetadata($result, $snapshot, 'serve', $cacheStatus) : $this->stripSnapshotDebug($result);
	}

	public function didOpen(string $documentPath, string $source, ?int $version = null, bool $debug = false): array
	{
		$this->documentOverlays[\normalize_path($documentPath)] = [
			'source' => $source,
			'version' => $version,
		];
		$this->snapshotCache = [];
		return $this->buildPublishDiagnosticsNotification($documentPath, $debug);
	}

	public function didChange(string $documentPath, string $source, ?int $version = null, bool $debug = false): array
	{
		$this->documentOverlays[\normalize_path($documentPath)] = [
			'source' => $source,
			'version' => $version,
		];
		$this->snapshotCache = [];
		return $this->buildPublishDiagnosticsNotification($documentPath, $debug);
	}

	public function didClose(string $documentPath, bool $debug = false): array
	{
		unset($this->documentOverlays[\normalize_path($documentPath)]);
		$this->snapshotCache = [];
		return $this->buildPublishDiagnosticsNotification($documentPath, $debug);
	}

	public function didSave(string $documentPath, ?string $source = null, ?int $version = null, bool $debug = false): array
	{
		$normalizedPath = \normalize_path($documentPath);
		if ($source !== null) {
			$this->documentOverlays[$normalizedPath] = [
				'source' => $source,
				'version' => $version,
			];
		} elseif ($version !== null && isset($this->documentOverlays[$normalizedPath])) {
			$this->documentOverlays[$normalizedPath]['version'] = $version;
		}
		$this->snapshotCache = [];
		return $this->buildPublishDiagnosticsNotification($documentPath, $debug);
	}

	/** @param list<string> $changedPaths @return list<array<string,mixed>> */
	public function didChangeWatchedFiles(array $changedPaths, bool $debug = false): array
	{
		$this->snapshotCache = [];
		$targets = [];
		foreach ($changedPaths as $path) {
			$normalizedPath = \normalize_path($path);
			if ($normalizedPath === '') {
				continue;
			}
			if (str_ends_with($normalizedPath, '/prism.json')) {
				foreach (array_keys($this->documentOverlays) as $overlayPath) {
					$targets[$overlayPath] = true;
				}
				continue;
			}
			if (preg_match('/\.(phs|php)$/', $normalizedPath) === 1) {
				$targets[$normalizedPath] = true;
			}
		}
		return $this->buildPublishDiagnosticsNotifications(array_keys($targets), $debug);
	}

	/** @param array<string,string> $sourceOverrides @return array{0:array<string,mixed>,1:string} */
	private function getSnapshot(array $sourceOverrides): array
	{
		if (!is_string($this->projectRoot) || !is_string($this->configPath)) {
			throw new \RuntimeException('STAN LSP session is not initialized with a project root yet.');
		}
		$mergedOverrides = $this->buildEffectiveSourceOverrides($sourceOverrides);
		$cacheKey = \build_stan_lsp_snapshot_cache_key($this->projectRoot, $this->configPath, $mergedOverrides);
		if (isset($this->snapshotCache[$cacheKey])) {
			return [$this->snapshotCache[$cacheKey], 'hit'];
		}
		$this->snapshotCache[$cacheKey] = $this->workspaceSession->createBridgeSnapshot($this->projectRoot, $this->configPath, $mergedOverrides);
		return [$this->snapshotCache[$cacheKey], 'miss'];
	}

	/** @param array<string,string> $sourceOverrides @return array<string,string> */
	private function buildEffectiveSourceOverrides(array $sourceOverrides): array
	{
		$merged = [];
		foreach ($this->documentOverlays as $path => $overlay) {
			$merged[$path] = $overlay['source'];
		}
		foreach ($sourceOverrides as $path => $source) {
			$merged[\normalize_path($path)] = $source;
		}
		return $merged;
	}

	/** @return array<string,mixed> */
	private function buildPublishDiagnosticsNotification(string $documentPath, bool $debug): array
	{
		$normalizedPath = \normalize_path($documentPath);
		$diagnostics = \build_stan_lsp_diagnostic_report($this->documentDiagnostics($normalizedPath, [], $debug));
		$overlay = $this->documentOverlays[$normalizedPath] ?? null;
		return [
			'jsonrpc' => '2.0',
			'method' => 'textDocument/publishDiagnostics',
			'params' => [
				'uri' => 'file://' . $normalizedPath,
				'diagnostics' => $diagnostics['items'] ?? [],
			]
				+ ($overlay !== null && $overlay['version'] !== null ? ['version' => $overlay['version']] : [])
				+ (isset($diagnostics['_debug']) ? ['_debug' => $diagnostics['_debug']] : []),
		];
	}

	/** @param list<string> $documentPaths @return list<array<string,mixed>> */
	private function buildPublishDiagnosticsNotifications(array $documentPaths, bool $debug): array
	{
		$notifications = [];
		foreach ($documentPaths as $documentPath) {
			$notifications[] = $this->buildPublishDiagnosticsNotification($documentPath, $debug);
		}
		return $notifications;
	}

	/** @param array<string,mixed> $payload @param array<string,mixed> $snapshot @return array<string,mixed> */
	private function attachDebugMetadata(array $payload, array $snapshot, string $mode, string $cacheStatus): array
	{
		return \attach_stan_debug_metadata($payload, [
			'mode' => $mode,
			'snapshot_cache' => $cacheStatus,
			'analyzed_count' => (int) (($snapshot['debug']['analyzed_count'] ?? 0)),
			'reused_count' => (int) (($snapshot['debug']['reused_count'] ?? 0)),
			'source_unit_count' => (int) (($snapshot['debug']['source_unit_count'] ?? 0)),
			'warning_count' => (int) (($snapshot['debug']['warning_count'] ?? ($payload['warning_count'] ?? 0))),
		]);
	}

	/** @param array<string,mixed> $payload @return array<string,mixed> */
	private function stripSnapshotDebug(array $payload): array
	{
		unset($payload['_snapshot_debug']);
		return $payload;
	}
}
