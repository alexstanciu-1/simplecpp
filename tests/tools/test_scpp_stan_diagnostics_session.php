<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

use Scpp\S2S\Stan\StanWorkspaceSession;

final class ScppStanDiagnosticsSessionTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_stan_diagnostics_session_' . getmypid() . '_' . bin2hex(random_bytes(4)));
	}

	public function run(): int
	{
		try {
			$project = $this->root . '/app';
			$this->mkdir($project);
			$this->write($project . '/prism.json', json_encode([
				'config_version' => 1,
				'project_name' => 'stan-diagnostics-session',
				'entrypoint' => 'main.phs',
				'build_dir' => '.prism/build',
				'generated_dir' => '.prism/generated',
				'cache_dir' => '.prism/cache',
				'runtime' => [
					'languages' => [
						'php' => ['profile' => 'strict'],
					],
					'modules' => ['json', 'filesystem'],
				],
			], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

			$originalSource = implode("\n", [
				'function main(): void',
				'{',
				'	$value = null;',
				'	$value = "ok";',
				'}',
				'',
			]);
			$fixedSource = implode("\n", [
				'function main(): void',
				'{',
				'	$value = "ok";',
				'}',
				'',
			]);
			$usageSource = implode("\n", [
				'function main(): void',
				'{',
				'	helper();',
				'}',
				'',
				'function helper(): void',
				'{',
				'}',
				'',
			]);

			$mainPath = $project . '/main.phs';
			$this->write($mainPath, $originalSource);

			$session = new StanWorkspaceSession();

			$normal = $session->run($project, $project . '/prism.json');
			$this->assertSame(1, $normal['warning_count'] ?? null, 'normal run should report one warning');
			$this->assertSame(1, $normal['local_type_warning_count'] ?? null, 'normal run should report one local type warning');

			$diagnostics = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame($this->normalizeRunSummary($normal), $this->normalizeDiagnosticsSummary($diagnostics), 'runDiagnostics summary should match normal run summary');
			$this->assertSame(1, count($diagnostics['diagnostics'] ?? []), 'runDiagnostics should expose one diagnostic');

			$firstDiagnostic = $diagnostics['diagnostics'][0] ?? null;
			if (!is_array($firstDiagnostic)) {
				throw new RuntimeException('runDiagnostics should expose the first diagnostic as an array');
			}
			$this->assertSame('stan.local_type_morph_warning', $firstDiagnostic['code'] ?? null, 'diagnostic code should be stable');
			$this->assertSame(normalize_path($mainPath), $firstDiagnostic['path'] ?? null, 'diagnostic path should point to main.phs');
			$this->assertSame(4, $firstDiagnostic['line'] ?? null, 'diagnostic line should point to the morphing reassignment');
			$groupedMainDiagnostics = $diagnostics['diagnostics_by_path'][normalize_path($mainPath)] ?? null;
			if (!is_array($groupedMainDiagnostics)) {
				throw new RuntimeException('diagnostics_by_path should contain the main file entry');
			}
			$this->assertSame(1, count($groupedMainDiagnostics), 'diagnostics_by_path should group the main file diagnostics');

			$overrideRun = $session->runWithOverrides($project, $project . '/prism.json', [$mainPath => $fixedSource]);
			$this->assertSame(0, $overrideRun['warning_count'] ?? null, 'override run should remove the warning');
			$this->assertSame(1, $overrideRun['analyzed_count'] ?? null, 'override run should reanalyze only the changed source');

			$overrideDiagnostics = $session->runDiagnostics($project, $project . '/prism.json', [$mainPath => $fixedSource]);
			$this->assertSame(0, $overrideDiagnostics['warning_count'] ?? null, 'override diagnostics should remove the warning');
			$this->assertSame([], $overrideDiagnostics['diagnostics'] ?? null, 'override diagnostics list should be empty');
			$this->assertSame([], $overrideDiagnostics['diagnostics_by_path'] ?? null, 'override diagnostics_by_path should be empty');

			$normalAfterOverride = $session->run($project, $project . '/prism.json');
			$this->assertSame(1, $normalAfterOverride['warning_count'] ?? null, 'normal run after override should keep the canonical source warning');
			$this->assertSame(0, $normalAfterOverride['analyzed_count'] ?? null, 'normal run after override should reuse canonical cached summaries');

			$plainCli = $this->runCommand([
				PHP_BINARY,
				resolve_repo_root() . '/bin/scpp.php',
				'stan-lsp',
				'document-diagnostics',
				'--path',
				'main.phs',
			], $project, 30);
			$this->assertSame(0, $plainCli['exit_code'], 'stan-lsp plain diagnostics command should succeed');
			$plainPayload = json_decode($plainCli['stdout'], true);
			if (!is_array($plainPayload)) {
				throw new RuntimeException('stan-lsp plain diagnostics should emit JSON');
			}
			$this->assertSame(normalize_path($mainPath), $plainPayload['path'] ?? null, 'stan-lsp plain diagnostics should report the requested path');
			$this->assertSame(1, $plainPayload['warning_count'] ?? null, 'stan-lsp plain diagnostics should report one warning');
			$this->assertSame('file://' . normalize_path($mainPath), $plainPayload['uri'] ?? null, 'stan-lsp plain diagnostics should emit a file URI');

			$symbolsCli = $this->runCommand([
				PHP_BINARY,
				resolve_repo_root() . '/bin/scpp.php',
				'stan-lsp',
				'document-symbols',
				'--path',
				'main.phs',
			], $project, 30);
			$this->assertSame(0, $symbolsCli['exit_code'], 'stan-lsp document-symbols command should succeed');
			$symbolsPayload = json_decode($symbolsCli['stdout'], true);
			if (!is_array($symbolsPayload)) {
				throw new RuntimeException('stan-lsp document-symbols should emit JSON');
			}
			$this->assertSame(1, $symbolsPayload['symbol_count'] ?? null, 'stan-lsp document-symbols should report one symbol');
			$this->assertSame('main', $symbolsPayload['symbols'][0]['name'] ?? null, 'stan-lsp document-symbols should return the main function');
			$this->assertSame(12, $symbolsPayload['symbols'][0]['lsp_kind'] ?? null, 'stan-lsp document-symbols should expose an LSP symbol kind for functions');

			$hoverCli = $this->runCommand([
				PHP_BINARY,
				resolve_repo_root() . '/bin/scpp.php',
				'stan-lsp',
				'hover',
				'--path',
				'main.phs',
				'--line',
				'4',
				'--column',
				'2',
			], $project, 30);
			$this->assertSame(0, $hoverCli['exit_code'], 'stan-lsp hover command should succeed');
			$hoverPayload = json_decode($hoverCli['stdout'], true);
			if (!is_array($hoverPayload)) {
				throw new RuntimeException('stan-lsp hover should emit JSON');
			}
			$this->assertSame(4, $hoverPayload['line'] ?? null, 'stan-lsp hover should echo the requested line');
			$this->assertSame('local_type_morph_warning', $hoverPayload['hover']['diagnostics'][0]['kind'] ?? null, 'stan-lsp hover should surface the line diagnostic');

			$definitionCli = $this->runCommand([
				PHP_BINARY,
				resolve_repo_root() . '/bin/scpp.php',
				'stan-lsp',
				'definition',
				'--path',
				'main.phs',
				'--line',
				'1',
				'--column',
				'12',
			], $project, 30);
			$this->assertSame(0, $definitionCli['exit_code'], 'stan-lsp definition command should succeed');
			$definitionPayload = json_decode($definitionCli['stdout'], true);
			if (!is_array($definitionPayload)) {
				throw new RuntimeException('stan-lsp definition should emit JSON');
			}
			$this->assertSame('main', $definitionPayload['definition']['name'] ?? null, 'stan-lsp definition should return the main function symbol');
			$this->assertSame(normalize_path($mainPath), $definitionPayload['definition']['path'] ?? null, 'stan-lsp definition should point at main.phs');

			$referencesCli = $this->runCommand([
				PHP_BINARY,
				resolve_repo_root() . '/bin/scpp.php',
				'stan-lsp',
				'references',
				'--path',
				'main.phs',
				'--line',
				'1',
				'--column',
				'12',
			], $project, 30);
			$this->assertSame(0, $referencesCli['exit_code'], 'stan-lsp references command should succeed');
			$referencesPayload = json_decode($referencesCli['stdout'], true);
			if (!is_array($referencesPayload)) {
				throw new RuntimeException('stan-lsp references should emit JSON');
			}
			$this->assertSame(true, (int) ($referencesPayload['reference_count'] ?? 0) >= 1, 'stan-lsp references should report at least one reference in the narrow stub');
			$this->assertSame('main', $referencesPayload['references'][0]['name'] ?? null, 'stan-lsp references should return the main function symbol');

			$this->write($mainPath, $usageSource);
			$usageDefinitionCli = $this->runCommand([
				PHP_BINARY,
				resolve_repo_root() . '/bin/scpp.php',
				'stan-lsp',
				'definition',
				'--path',
				'main.phs',
				'--line',
				'3',
				'--column',
				'3',
			], $project, 30);
			$this->assertSame(0, $usageDefinitionCli['exit_code'], 'stan-lsp definition at usage site should succeed');
			$usageDefinitionPayload = json_decode($usageDefinitionCli['stdout'], true);
			if (!is_array($usageDefinitionPayload)) {
				throw new RuntimeException('stan-lsp usage definition should emit JSON');
			}
			$this->assertSame('helper', $usageDefinitionPayload['definition']['name'] ?? null, 'stan-lsp definition at usage site should resolve helper');
			$usageReferencesCli = $this->runCommand([
				PHP_BINARY,
				resolve_repo_root() . '/bin/scpp.php',
				'stan-lsp',
				'references',
				'--path',
				'main.phs',
				'--line',
				'3',
				'--column',
				'3',
			], $project, 30);
			$this->assertSame(0, $usageReferencesCli['exit_code'], 'stan-lsp references at usage site should succeed');
			$usageReferencesPayload = json_decode($usageReferencesCli['stdout'], true);
			if (!is_array($usageReferencesPayload)) {
				throw new RuntimeException('stan-lsp usage references should emit JSON');
			}
			$this->assertSame(true, (int) ($usageReferencesPayload['reference_count'] ?? 0) >= 2, 'stan-lsp references at usage site should include declaration and usage');
			$this->write($mainPath, $originalSource);

			$debugCli = $this->runCommand([
				PHP_BINARY,
				resolve_repo_root() . '/bin/scpp.php',
				'stan-lsp',
				'document-diagnostics',
				'--path',
				'main.phs',
				'--debug',
			], $project, 30);
			$this->assertSame(0, $debugCli['exit_code'], 'stan-lsp debug diagnostics command should succeed');
			$debugPayload = json_decode($debugCli['stdout'], true);
			if (!is_array($debugPayload)) {
				throw new RuntimeException('stan-lsp debug diagnostics should emit JSON');
			}
			$this->assertSame('one-shot', $debugPayload['_debug']['mode'] ?? null, 'stan-lsp debug diagnostics should report one-shot mode');
			$this->assertSame('miss', $debugPayload['_debug']['snapshot_cache'] ?? null, 'stan-lsp debug diagnostics should report a cache miss');
			$this->assertSame(
				$debugPayload['_debug']['source_unit_count'] ?? null,
				($debugPayload['_debug']['analyzed_count'] ?? 0) + ($debugPayload['_debug']['reused_count'] ?? 0),
				'stan-lsp debug diagnostics should expose sane analyzed/reused totals'
			);

			$overrideFile = $project . '/override_main.phs';
			$this->write($overrideFile, $fixedSource);
			$jsonrpcCli = $this->runCommand([
				PHP_BINARY,
				resolve_repo_root() . '/bin/scpp.php',
				'stan-lsp',
				'document-diagnostics',
				'--path',
				'main.phs',
				'--override-source',
				'override_main.phs',
				'--jsonrpc-id',
				'7',
			], $project, 30);
			$this->assertSame(0, $jsonrpcCli['exit_code'], 'stan-lsp JSON-RPC diagnostics command should succeed');
			$jsonrpcPayload = json_decode($jsonrpcCli['stdout'], true);
			if (!is_array($jsonrpcPayload)) {
				throw new RuntimeException('stan-lsp JSON-RPC diagnostics should emit JSON');
			}
			$this->assertSame('2.0', $jsonrpcPayload['jsonrpc'] ?? null, 'stan-lsp JSON-RPC diagnostics should emit jsonrpc version');
			$this->assertSame('7', $jsonrpcPayload['id'] ?? null, 'stan-lsp JSON-RPC diagnostics should echo the request id');
			$this->assertSame(0, $jsonrpcPayload['result']['warning_count'] ?? null, 'stan-lsp JSON-RPC override diagnostics should remove the warning');
			$this->assertSame([], $jsonrpcPayload['result']['diagnostics'] ?? null, 'stan-lsp JSON-RPC override diagnostics should be empty');

			$serveRequests = implode("\n", [
				json_encode([
					'jsonrpc' => '2.0',
					'id' => 0,
					'method' => 'initialize',
					'params' => ['rootUri' => 'file://' . normalize_path($project)],
				], JSON_UNESCAPED_SLASHES),
				json_encode([
					'jsonrpc' => '2.0',
					'id' => 1,
					'method' => 'stan/documentSymbols',
					'params' => ['path' => 'main.phs'],
				], JSON_UNESCAPED_SLASHES),
				json_encode([
					'jsonrpc' => '2.0',
					'id' => 2,
					'method' => 'stan/hover',
					'params' => ['path' => 'main.phs', 'line' => 4, 'column' => 2],
				], JSON_UNESCAPED_SLASHES),
				json_encode([
					'jsonrpc' => '2.0',
					'id' => 3,
					'method' => 'stan/documentDiagnostics',
					'params' => ['path' => 'main.phs', 'source' => $fixedSource],
				], JSON_UNESCAPED_SLASHES),
				json_encode([
					'jsonrpc' => '2.0',
					'id' => 4,
					'method' => 'stan/definition',
					'params' => ['path' => 'main.phs', 'line' => 1, 'column' => 12],
				], JSON_UNESCAPED_SLASHES),
				json_encode([
					'jsonrpc' => '2.0',
					'id' => 5,
					'method' => 'stan/references',
					'params' => ['path' => 'main.phs', 'line' => 1, 'column' => 12],
				], JSON_UNESCAPED_SLASHES),
				json_encode([
					'jsonrpc' => '2.0',
					'id' => 6,
					'method' => 'textDocument/documentSymbol',
					'params' => ['textDocument' => ['uri' => 'file://' . normalize_path($mainPath)]],
				], JSON_UNESCAPED_SLASHES),
				json_encode([
					'jsonrpc' => '2.0',
					'id' => 7,
					'method' => 'textDocument/hover',
					'params' => [
						'textDocument' => ['uri' => 'file://' . normalize_path($mainPath)],
						'position' => ['line' => 3, 'character' => 1],
					],
				], JSON_UNESCAPED_SLASHES),
				json_encode([
					'jsonrpc' => '2.0',
					'id' => 8,
					'method' => 'textDocument/definition',
					'params' => [
						'textDocument' => ['uri' => 'file://' . normalize_path($mainPath)],
						'position' => ['line' => 0, 'character' => 11],
					],
				], JSON_UNESCAPED_SLASHES),
				'',
			]);
			$serve = $this->runCommandWithInput([
				PHP_BINARY,
				resolve_repo_root() . '/bin/scpp.php',
				'stan-lsp',
				'serve',
			], $project, $serveRequests, 30);
			$this->assertSame(0, $serve['exit_code'], 'stan-lsp serve should succeed');
			$responses = array_values(array_filter(array_map('trim', explode("\n", trim($serve['stdout']))), static fn (string $line): bool => $line !== ''));
			$this->assertSame(9, count($responses), 'stan-lsp serve should emit one response per request');
			$serveResponse0 = json_decode($responses[0], true);
			$serveResponse1 = json_decode($responses[1], true);
			$serveResponse2 = json_decode($responses[2], true);
			$serveResponse3 = json_decode($responses[3], true);
			$serveResponse4 = json_decode($responses[4], true);
			$serveResponse5 = json_decode($responses[5], true);
			$serveResponse6 = json_decode($responses[6], true);
			$serveResponse7 = json_decode($responses[7], true);
			$serveResponse8 = json_decode($responses[8], true);
			$this->assertSame(0, $serveResponse0['id'] ?? null, 'serve initialize response should preserve id');
			$this->assertSame(true, (bool) ($serveResponse0['result']['capabilities']['hoverProvider'] ?? false), 'serve initialize response should advertise hover support');
			$this->assertSame(1, $serveResponse1['id'] ?? null, 'serve symbols response should preserve id');
			$this->assertSame('main', $serveResponse1['result']['symbols'][0]['name'] ?? null, 'serve symbols response should return main');
			$this->assertSame(2, $serveResponse2['id'] ?? null, 'serve hover response should preserve id');
			$this->assertSame('local_type_morph_warning', $serveResponse2['result']['hover']['diagnostics'][0]['kind'] ?? null, 'serve hover response should return the line diagnostic');
			$this->assertSame(3, $serveResponse3['id'] ?? null, 'serve diagnostics response should preserve id');
			$this->assertSame(0, $serveResponse3['result']['warning_count'] ?? null, 'serve diagnostics response should honor source overrides');
			$this->assertSame(4, $serveResponse4['id'] ?? null, 'serve definition response should preserve id');
			$this->assertSame('main', $serveResponse4['result']['definition']['name'] ?? null, 'serve definition response should return main');
			$this->assertSame(5, $serveResponse5['id'] ?? null, 'serve references response should preserve id');
			$this->assertSame(true, (int) ($serveResponse5['result']['reference_count'] ?? 0) >= 1, 'serve references response should return at least one narrow reference');
			$this->assertSame(6, $serveResponse6['id'] ?? null, 'serve LSP documentSymbol response should preserve id');
			$this->assertSame('main', $serveResponse6['result'][0]['name'] ?? null, 'serve LSP documentSymbol response should return main');
			$this->assertSame(12, $serveResponse6['result'][0]['kind'] ?? null, 'serve LSP documentSymbol response should expose the LSP kind directly');
			$this->assertSame(7, $serveResponse7['id'] ?? null, 'serve LSP hover response should preserve id');
			$this->assertSame('markdown', $serveResponse7['result']['contents']['kind'] ?? null, 'serve LSP hover response should return markdown content');
			$this->assertSame(true, str_contains((string) ($serveResponse7['result']['contents']['value'] ?? ''), 'morphs into multiple types'), 'serve LSP hover response should mention the diagnostic text');
			$this->assertSame(8, $serveResponse8['id'] ?? null, 'serve LSP definition response should preserve id');
			$this->assertSame('file://' . normalize_path($mainPath), $serveResponse8['result']['uri'] ?? null, 'serve LSP definition response should return a location uri');

			$serveDebugRequests = implode("\n", [
				json_encode([
					'jsonrpc' => '2.0',
					'id' => 11,
					'method' => 'stan/documentDiagnostics',
					'params' => ['path' => 'main.phs', 'debug' => true],
				], JSON_UNESCAPED_SLASHES),
				json_encode([
					'jsonrpc' => '2.0',
					'id' => 12,
					'method' => 'stan/documentDiagnostics',
					'params' => ['path' => 'main.phs', 'debug' => true],
				], JSON_UNESCAPED_SLASHES),
				'',
			]);
			$serveDebug = $this->runCommandWithInput([
				PHP_BINARY,
				resolve_repo_root() . '/bin/scpp.php',
				'stan-lsp',
				'serve',
			], $project, $serveDebugRequests, 30);
			$this->assertSame(0, $serveDebug['exit_code'], 'stan-lsp serve debug should succeed');
			$serveDebugResponses = array_values(array_filter(array_map('trim', explode("\n", trim($serveDebug['stdout']))), static fn (string $line): bool => $line !== ''));
			$this->assertSame(2, count($serveDebugResponses), 'stan-lsp serve debug should emit two responses');
			$serveDebug1 = json_decode($serveDebugResponses[0], true);
			$serveDebug2 = json_decode($serveDebugResponses[1], true);
			$this->assertSame('serve', $serveDebug1['result']['_debug']['mode'] ?? null, 'serve debug response should report serve mode');
			$this->assertSame('miss', $serveDebug1['result']['_debug']['snapshot_cache'] ?? null, 'first serve debug request should miss the snapshot cache');
			$this->assertSame('hit', $serveDebug2['result']['_debug']['snapshot_cache'] ?? null, 'second serve debug request should hit the snapshot cache');

			$outsideDir = $this->root . '/outside';
			$this->mkdir($outsideDir);
			$lazyInitRequests = implode("\n", [
				json_encode([
					'jsonrpc' => '2.0',
					'id' => 31,
					'method' => 'initialize',
					'params' => ['rootUri' => 'file://' . normalize_path($project)],
				], JSON_UNESCAPED_SLASHES),
				json_encode([
					'jsonrpc' => '2.0',
					'id' => 32,
					'method' => 'textDocument/diagnostic',
					'params' => [
						'textDocument' => ['uri' => 'file://' . normalize_path($mainPath)],
					],
				], JSON_UNESCAPED_SLASHES),
				'',
			]);
			$lazyInit = $this->runCommandWithInput([
				PHP_BINARY,
				resolve_repo_root() . '/bin/stan_lsp_server.php',
			], $outsideDir, $lazyInitRequests, 30);
			$this->assertSame(0, $lazyInit['exit_code'], 'stan_lsp_server.php should initialize from rootUri outside the project cwd');
			$lazyInitResponses = array_values(array_filter(array_map('trim', explode("\n", trim($lazyInit['stdout']))), static fn (string $line): bool => $line !== ''));
			$this->assertSame(2, count($lazyInitResponses), 'stan_lsp_server.php lazy init should emit initialize and diagnostic responses');
			$lazyInitResponse0 = json_decode($lazyInitResponses[0], true);
			$lazyInitResponse1 = json_decode($lazyInitResponses[1], true);
			$this->assertSame(31, $lazyInitResponse0['id'] ?? null, 'lazy init initialize should preserve id');
			$this->assertSame(32, $lazyInitResponse1['id'] ?? null, 'lazy init diagnostic should preserve id');
			$this->assertSame(1, count($lazyInitResponse1['result']['items'] ?? []), 'lazy init diagnostic should resolve the project from rootUri');

			$lspLifecycleRequests = implode("\n", [
				json_encode([
					'jsonrpc' => '2.0',
					'id' => 21,
					'method' => 'initialize',
					'params' => ['rootUri' => 'file://' . normalize_path($project)],
				], JSON_UNESCAPED_SLASHES),
				json_encode([
					'jsonrpc' => '2.0',
					'method' => 'initialized',
					'params' => new stdClass(),
				], JSON_UNESCAPED_SLASHES),
				json_encode([
					'jsonrpc' => '2.0',
					'method' => 'textDocument/didOpen',
					'params' => [
						'textDocument' => [
							'uri' => 'file://' . normalize_path($mainPath),
							'languageId' => 'simplecpp-php-strict',
							'version' => 1,
							'text' => $fixedSource,
						],
					],
				], JSON_UNESCAPED_SLASHES),
				json_encode([
					'jsonrpc' => '2.0',
					'id' => 22,
					'method' => 'textDocument/diagnostic',
					'params' => [
						'textDocument' => ['uri' => 'file://' . normalize_path($mainPath)],
					],
				], JSON_UNESCAPED_SLASHES),
				json_encode([
					'jsonrpc' => '2.0',
					'method' => 'textDocument/didChange',
					'params' => [
						'textDocument' => [
							'uri' => 'file://' . normalize_path($mainPath),
							'version' => 2,
						],
						'contentChanges' => [
							['text' => $originalSource],
						],
					],
				], JSON_UNESCAPED_SLASHES),
				json_encode([
					'jsonrpc' => '2.0',
					'id' => 23,
					'method' => 'textDocument/diagnostic',
					'params' => [
						'textDocument' => ['uri' => 'file://' . normalize_path($mainPath)],
					],
				], JSON_UNESCAPED_SLASHES),
				json_encode([
					'jsonrpc' => '2.0',
					'method' => 'textDocument/didClose',
					'params' => [
						'textDocument' => ['uri' => 'file://' . normalize_path($mainPath)],
					],
				], JSON_UNESCAPED_SLASHES),
				json_encode([
					'jsonrpc' => '2.0',
					'method' => 'textDocument/didSave',
					'params' => [
						'textDocument' => [
							'uri' => 'file://' . normalize_path($mainPath),
							'version' => 3,
						],
						'text' => $fixedSource,
					],
				], JSON_UNESCAPED_SLASHES),
				json_encode([
					'jsonrpc' => '2.0',
					'id' => 24,
					'method' => 'textDocument/diagnostic',
					'params' => [
						'textDocument' => ['uri' => 'file://' . normalize_path($mainPath)],
					],
				], JSON_UNESCAPED_SLASHES),
				json_encode([
					'jsonrpc' => '2.0',
					'method' => 'workspace/didChangeWatchedFiles',
					'params' => [
						'changes' => [
							['uri' => 'file://' . normalize_path($mainPath), 'type' => 2],
						],
					],
				], JSON_UNESCAPED_SLASHES),
				'',
			]);
			$lspLifecycle = $this->runCommandWithInput([
				PHP_BINARY,
				resolve_repo_root() . '/bin/scpp.php',
				'stan-lsp',
				'serve',
			], $project, $lspLifecycleRequests, 30);
			$this->assertSame(0, $lspLifecycle['exit_code'], 'stan-lsp serve lifecycle should succeed');
			$lspLifecycleResponses = array_values(array_filter(array_map('trim', explode("\n", trim($lspLifecycle['stdout']))), static fn (string $line): bool => $line !== ''));
			$this->assertSame(9, count($lspLifecycleResponses), 'stan-lsp serve lifecycle should emit initialize response, five publishDiagnostics notifications, and three diagnostics responses');
			$lifecycleInit = json_decode($lspLifecycleResponses[0], true);
			$lifecycleOpenPublish = json_decode($lspLifecycleResponses[1], true);
			$lifecycleDiagnosticFixed = json_decode($lspLifecycleResponses[2], true);
			$lifecycleChangePublish = json_decode($lspLifecycleResponses[3], true);
			$lifecycleDiagnosticOriginal = json_decode($lspLifecycleResponses[4], true);
			$lifecycleClosePublish = json_decode($lspLifecycleResponses[5], true);
			$lifecycleSavePublish = json_decode($lspLifecycleResponses[6], true);
			$lifecycleDiagnosticClosed = json_decode($lspLifecycleResponses[7], true);
			$lifecycleWatchedPublish = json_decode($lspLifecycleResponses[8], true);
			$this->assertSame(21, $lifecycleInit['id'] ?? null, 'lifecycle initialize should preserve id');
			$this->assertSame('textDocument/publishDiagnostics', $lifecycleOpenPublish['method'] ?? null, 'didOpen should emit publishDiagnostics');
			$this->assertSame(0, count($lifecycleOpenPublish['params']['diagnostics'] ?? []), 'didOpen with fixed source should publish zero diagnostics');
			$this->assertSame(1, $lifecycleOpenPublish['params']['version'] ?? null, 'didOpen should carry the opened document version');
			$this->assertSame(22, $lifecycleDiagnosticFixed['id'] ?? null, 'diagnostic-after-open should preserve id');
			$this->assertSame(0, count($lifecycleDiagnosticFixed['result']['items'] ?? []), 'diagnostic-after-open should use overlay content');
			$this->assertSame('textDocument/publishDiagnostics', $lifecycleChangePublish['method'] ?? null, 'didChange should emit publishDiagnostics');
			$this->assertSame(1, count($lifecycleChangePublish['params']['diagnostics'] ?? []), 'didChange back to original should publish one diagnostic');
			$this->assertSame(2, $lifecycleChangePublish['params']['version'] ?? null, 'didChange should carry the changed document version');
			$this->assertSame(23, $lifecycleDiagnosticOriginal['id'] ?? null, 'diagnostic-after-change should preserve id');
			$this->assertSame(1, count($lifecycleDiagnosticOriginal['result']['items'] ?? []), 'diagnostic-after-change should reflect changed overlay content');
			$this->assertSame('textDocument/publishDiagnostics', $lifecycleClosePublish['method'] ?? null, 'didClose should emit publishDiagnostics');
			$this->assertSame(1, count($lifecycleClosePublish['params']['diagnostics'] ?? []), 'didClose should fall back to on-disk diagnostics');
			$this->assertSame('textDocument/publishDiagnostics', $lifecycleSavePublish['method'] ?? null, 'didSave should emit publishDiagnostics');
			$this->assertSame(0, count($lifecycleSavePublish['params']['diagnostics'] ?? []), 'didSave with fixed source should publish zero diagnostics');
			$this->assertSame(3, $lifecycleSavePublish['params']['version'] ?? null, 'didSave should carry the saved document version');
			$this->assertSame(24, $lifecycleDiagnosticClosed['id'] ?? null, 'diagnostic-after-close should preserve id');
			$this->assertSame(0, count($lifecycleDiagnosticClosed['result']['items'] ?? []), 'diagnostic-after-save should match the saved overlay content');
			$this->assertSame('textDocument/publishDiagnostics', $lifecycleWatchedPublish['method'] ?? null, 'watched file refresh should emit publishDiagnostics');
			$this->assertSame(0, count($lifecycleWatchedPublish['params']['diagnostics'] ?? []), 'watched file refresh should reuse the saved clean content');

			echo "PASS: scpp stan diagnostics session\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	/** @param array<string,mixed> $result @return array<string,int|string> */
	private function normalizeRunSummary(array $result): array
	{
		return [
			'project_root' => (string) ($result['project_root'] ?? ''),
			'php_profile' => (string) ($result['php_profile'] ?? ''),
			'source_unit_count' => (int) ($result['source_unit_count'] ?? 0),
			'warning_count' => (int) ($result['warning_count'] ?? 0),
			'local_type_warning_count' => (int) ($result['local_type_warning_count'] ?? 0),
			'call_site_warning_count' => (int) ($result['call_site_warning_count'] ?? 0),
			'return_type_warning_count' => (int) ($result['return_type_warning_count'] ?? 0),
		];
	}

	/** @param array<string,mixed> $result @return array<string,int|string> */
	private function normalizeDiagnosticsSummary(array $result): array
	{
		$diagnostics = is_array($result['diagnostics'] ?? null) ? $result['diagnostics'] : [];
		$localTypeCount = 0;
		$callSiteCount = 0;
		$returnTypeCount = 0;
		foreach ($diagnostics as $diagnostic) {
			if (!is_array($diagnostic)) {
				continue;
			}
			$kind = (string) ($diagnostic['kind'] ?? '');
			if ($kind === 'local_type_morph_warning') {
				$localTypeCount++;
			} elseif ($kind === 'argument_type_mismatch' || str_starts_with($kind, 'unresolved_')) {
				$callSiteCount++;
			} elseif ($kind === 'return_type_mismatch' || $kind === 'missing_return') {
				$returnTypeCount++;
			}
		}

		return [
			'project_root' => (string) ($result['project_root'] ?? ''),
			'php_profile' => (string) ($result['php_profile'] ?? ''),
			'source_unit_count' => (int) ($result['source_unit_count'] ?? 0),
			'warning_count' => (int) ($result['warning_count'] ?? 0),
			'local_type_warning_count' => $localTypeCount,
			'call_site_warning_count' => $callSiteCount,
			'return_type_warning_count' => $returnTypeCount,
		];
	}

	private function write(string $path, string $contents): void
	{
		if (file_put_contents($path, $contents) === false) {
			throw new RuntimeException('Failed to write ' . $path);
		}
	}

	private function mkdir(string $path): void
	{
		if (!is_dir($path) && !mkdir($path, 0777, true)) {
			throw new RuntimeException('Failed to create ' . $path);
		}
	}

	/** @return array{exit_code:int,stdout:string,stderr:string} */
	private function runCommand(array $command, string $cwd, int $timeoutSeconds): array
	{
		return $this->runCommandWithInput($command, $cwd, '', $timeoutSeconds);
	}

	/** @return array{exit_code:int,stdout:string,stderr:string} */
	private function runCommandWithInput(array $command, string $cwd, string $stdin, int $timeoutSeconds): array
	{
		$descriptor = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$process = proc_open($command, $descriptor, $pipes, $cwd, scpp_build_process_environment([
			'SCPP_CXX_LAUNCHER' => ' ',
		]));
		if (!is_resource($process)) {
			throw new RuntimeException('Failed to start command: ' . implode(' ', $command));
		}
		fwrite($pipes[0], $stdin);
		fclose($pipes[0]);

		$stdout = '';
		$stderr = '';
		$started = microtime(true);
		$observedExitCode = null;
		foreach ([1, 2] as $index) {
			stream_set_blocking($pipes[$index], false);
		}
		while (true) {
			$status = proc_get_status($process);
			$stdout .= (string) stream_get_contents($pipes[1]);
			$stderr .= (string) stream_get_contents($pipes[2]);
			if (($status['running'] ?? false) !== true) {
				$exitCode = $status['exitcode'] ?? null;
				$observedExitCode = is_int($exitCode) ? $exitCode : null;
				break;
			}
			if ((microtime(true) - $started) > $timeoutSeconds) {
				proc_terminate($process);
				throw new RuntimeException('Timed out after ' . $timeoutSeconds . 's: ' . implode(' ', $command));
			}
			usleep(100000);
		}
		$stdout .= (string) stream_get_contents($pipes[1]);
		$stderr .= (string) stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$exitCode = proc_close($process);
		return [
			'exit_code' => $observedExitCode ?? (is_int($exitCode) ? $exitCode : 1),
			'stdout' => $stdout,
			'stderr' => $stderr,
		];
	}

	private function removeTree(string $path): void
	{
		if (!is_dir($path)) {
			return;
		}
		$items = scandir($path);
		if ($items === false) {
			return;
		}
		foreach ($items as $item) {
			if ($item === '.' || $item === '..') {
				continue;
			}
			$child = $path . '/' . $item;
			if (is_dir($child) && !is_link($child)) {
				$this->removeTree($child);
				continue;
			}
			unlink($child);
		}
		rmdir($path);
	}

	private function assertSame(mixed $expected, mixed $actual, string $message): void
	{
		if ($expected !== $actual) {
			throw new RuntimeException($message . ' expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
		}
	}
}

exit((new ScppStanDiagnosticsSessionTest())->run());
