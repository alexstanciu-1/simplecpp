<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

use Scpp\S2S\Analysis\RuntimeShallowSourceGenerator;
use Scpp\S2S\Stan\StanPhpRuntimeFunctionCatalog;

final class ScppWebviewModuleTest
{
	public function run(): int
	{
		$config = resolve_runtime_build_config([
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime', 'webview'],
			],
		]);
		$this->assertSame(true, in_array('webview', $config['modules'], true), 'webview should be an accepted runtime module');
		$this->assertSame(true, in_array('ui', $config['modules'], true), 'webview should auto-enable the ui module');
		$this->assertSame('webview', $config['implicit_modules']['ui'] ?? null, 'webview should report that it implicitly enabled ui');
		$explanation = build_explanation_details(
			resolve_repo_root(),
			[
				'compile_runtime' => false,
				'compile_dependencies' => false,
				'force_runtime_rebuild' => false,
			],
			0,
			0,
			[],
			[],
			0,
			null,
			null,
			null,
			null,
			[],
			$config
		);
		$explanationText = implode("\n", render_build_explanation_lines($explanation));
		$this->assertContains('Runtime modules: json, filesystem, datetime, webview, ui (implicit via webview)', $explanationText, 'build explanation should print implicit ui enablement');
		$this->assertContains('WebView backend:', $explanationText, 'build explanation should print the selected WebView backend');

		$source = render_runtime_composition_source($config);
		$this->assertContains('#include "modules/ui/ui.cpp"', $source, 'webview composition should include the ui runtime source');
		$this->assertContains('#include "modules/webview/webview.cpp"', $source, 'webview composition should include the webview runtime source');

		$catalog = new StanPhpRuntimeFunctionCatalog();
		$this->assertSame(true, $catalog->hasFunction('webview_create'), 'STAN catalog should recognize webview_create');
		$this->assertSame('webview', $catalog->requiredModule('webview_load_html'), 'STAN catalog should require the webview module for webview helpers');
		$this->assertSame(true, $catalog->hasFunction('webview_load_app'), 'STAN catalog should recognize webview_load_app');
		$this->assertSame(true, $catalog->hasFunction('webview_reply_ok'), 'STAN catalog should recognize webview_reply_ok');
		$this->assertSame(true, $catalog->hasFunction('webview_reply_error'), 'STAN catalog should recognize webview_reply_error');
		$this->assertSame(true, $catalog->hasFunction('webview_message_id'), 'STAN catalog should recognize webview_message_id');
		$this->assertSame(true, $catalog->hasFunction('webview_message_command'), 'STAN catalog should recognize webview_message_command');
		$this->assertSame(true, $catalog->hasFunction('webview_message_payload_json'), 'STAN catalog should recognize webview_message_payload_json');
		$this->assertSame('webview', $catalog->requiredModule('ui_event_webview'), 'STAN catalog should require the webview module for the WebView event handle accessor');

		$generated = (new RuntimeShallowSourceGenerator())->generate(resolve_repo_root(), 'strict');
		$strictRuntimeSymbols = $this->read(resolve_repo_root() . '/runtime/generated/stan/runtime_symbols_strict.phs');
		$this->assertSame('strict', $generated['profile'], 'strict shallow runtime generation should complete');
		$this->assertContains('function webview_create(ui_window $window): result<webview>', $strictRuntimeSymbols, 'strict shallow runtime should expose result-returning webview_create');
		$this->assertContains('function webview_load_html(webview $view, string $html): result<bool>', $strictRuntimeSymbols, 'strict shallow runtime should expose typed webview_load_html');
		$this->assertContains('function webview_load_app(webview $view, string $folder): result<bool>', $strictRuntimeSymbols, 'strict shallow runtime should expose typed webview_load_app');
		$this->assertContains('function webview_reply_ok(webview $view, int $id, string $value_json): result<bool>', $strictRuntimeSymbols, 'strict shallow runtime should expose typed webview_reply_ok');
		$this->assertContains('function webview_reply_error(webview $view, int $id, string $code, string $message): result<bool>', $strictRuntimeSymbols, 'strict shallow runtime should expose typed webview_reply_error');
		$this->assertContains('function webview_message_id(ui_event $event): int', $strictRuntimeSymbols, 'strict shallow runtime should expose typed webview_message_id');
		$this->assertContains('function webview_message_command(ui_event $event): string', $strictRuntimeSymbols, 'strict shallow runtime should expose typed webview_message_command');
		$this->assertContains('function webview_message_payload_json(ui_event $event): string', $strictRuntimeSymbols, 'strict shallow runtime should expose typed webview_message_payload_json');
		$this->assertContains('function ui_event_webview(ui_event $event): webview', $strictRuntimeSymbols, 'strict shallow runtime should expose typed WebView event access');
		$this->assertContains('function ui_event_message(ui_event $event): string', $strictRuntimeSymbols, 'strict shallow runtime should expose WebView message event payload access');
		$this->assertContains('public webview $webview_handle;', $strictRuntimeSymbols, 'strict shallow runtime should expose the WebView event handle payload');
		$this->assertContains('public string $message;', $strictRuntimeSymbols, 'strict shallow runtime should expose the event message payload');
		$this->assertContains('class webview', $strictRuntimeSymbols, 'strict shallow runtime should expose the webview handle shape');

		$webviewBuild = resolve_runtime_webview_build_spec();
		$this->assertSame($webviewBuild['backend'], $explanation['runtime_modules']['webview']['backend'] ?? null, 'build explanation should report the selected webview backend');
		$this->assertSame(is_array($webviewBuild['diagnostics'] ?? null), is_array($explanation['runtime_modules']['webview']['diagnostics'] ?? null), 'build explanation should carry webview diagnostics metadata');
		if (PHP_OS_FAMILY === 'Linux' && !$webviewBuild['enabled']) {
			$this->assertSame('none', $webviewBuild['backend'], 'missing WebKitGTK should report no selected backend');
			$this->assertContains('-DSCPP_HAS_WEBVIEW=0', implode(' ', $webviewBuild['compile_defines']), 'missing WebKitGTK should disable the webview build spec cleanly');
			$this->assertSame(true, count($webviewBuild['diagnostics']) > 0, 'missing WebKitGTK should include an actionable diagnostic');
		} elseif (PHP_OS_FAMILY === 'Windows' && !$webviewBuild['enabled']) {
			$this->assertSame('webview2', $webviewBuild['backend'], 'Windows webview build spec should report WebView2 even when the SDK has not been restored');
			$this->assertContains('-DSCPP_HAS_WEBVIEW=0', implode(' ', $webviewBuild['compile_defines']), 'missing WebView2 SDK should disable the webview build spec cleanly');
			$this->assertSame([], $webviewBuild['ldflags'], 'missing WebView2 SDK should not emit loader link flags');
		} else {
			$this->assertSame(true, $webviewBuild['enabled'], 'webview build spec should be enabled when a backend is available or not required for the platform facade');
			$this->assertSame([], $webviewBuild['diagnostics'], 'enabled webview build spec should not report dependency diagnostics');
			$this->assertContains('-DSCPP_HAS_WEBVIEW=1', implode(' ', $webviewBuild['compile_defines']), 'webview build spec should enable the webview facade');
			if (PHP_OS_FAMILY === 'Linux') {
				$this->assertSame('webkitgtk', $webviewBuild['backend'], 'Linux webview build spec should report WebKitGTK as the selected backend');
				$this->assertContains('-DSCPP_WEBVIEW_BACKEND_WEBKITGTK=1', implode(' ', $webviewBuild['compile_defines']), 'Linux webview build spec should select the WebKitGTK backend');
			} elseif (PHP_OS_FAMILY === 'Darwin') {
				$this->assertSame('wkwebview', $webviewBuild['backend'], 'macOS webview build spec should report WKWebView as the selected backend');
				$this->assertContains('-DSCPP_WEBVIEW_BACKEND_WKWEBVIEW=1', implode(' ', $webviewBuild['compile_defines']), 'macOS webview build spec should select the WKWebView backend');
				$this->assertContains('WebKit', implode(' ', $webviewBuild['ldflags']), 'macOS webview build spec should link WebKit');
			} elseif (PHP_OS_FAMILY === 'Windows') {
				$this->assertSame('webview2', $webviewBuild['backend'], 'Windows webview build spec should report WebView2 as the selected backend');
				$this->assertContains('-DSCPP_WEBVIEW_BACKEND_WEBVIEW2=1', implode(' ', $webviewBuild['compile_defines']), 'Windows webview build spec should select the WebView2 backend');
				$ldflagsText = implode(' ', $webviewBuild['ldflags']);
				if (!str_contains($ldflagsText, 'WebView2Loader.dll.lib') && !str_contains($ldflagsText, 'WebView2LoaderStatic.lib')) {
					throw new RuntimeException('Windows webview build spec should link the WebView2 loader');
				}
				$this->assertContains('advapi32.lib', implode(' ', $webviewBuild['ldflags']), 'Windows webview build spec should link WebView2 loader system dependencies');
				$this->assertContains('ole32.lib', implode(' ', $webviewBuild['ldflags']), 'Windows webview build spec should link COM memory dependencies');
				$this->assertContains('uuid.lib', implode(' ', $webviewBuild['ldflags']), 'Windows webview build spec should link COM GUID dependencies');
			} else {
				$this->assertSame('facade', $webviewBuild['backend'], 'unsupported WebView render platforms should report facade-only backend metadata');
			}
		}

		$missingPkgConfig = resolve_runtime_webview_build_spec(
			'Linux',
			static fn (array $commands): ?string => null,
			static fn (string $command): string => ''
		);
		$this->assertSame(false, $missingPkgConfig['enabled'], 'Linux webview build spec should disable cleanly without pkg-config');
		$this->assertContains('pkg-config was not found', implode("\n", $missingPkgConfig['diagnostics']), 'missing pkg-config diagnostic should name the missing tool');

		$missingWebKit = resolve_runtime_webview_build_spec(
			'Linux',
			static fn (array $commands): ?string => '/usr/bin/pkg-config',
			static fn (string $command): string => ''
		);
		$this->assertSame(false, $missingWebKit['enabled'], 'Linux webview build spec should disable cleanly without WebKitGTK');
		$this->assertContains('WebKitGTK pkg-config package', implode("\n", $missingWebKit['diagnostics']), 'missing WebKitGTK diagnostic should name the missing pkg-config package');

		$simulatedWebKit = resolve_runtime_webview_build_spec(
			'Linux',
			static fn (array $commands): ?string => '/usr/bin/pkg-config',
			static function (string $command): string {
				if (str_contains($command, '--libs')) {
					return '-lwebkit2gtk-4.1';
				}
				if (str_contains($command, '--cflags')) {
					return '-I/usr/include/webkitgtk-4.1';
				}
				return '';
			}
		);
		$this->assertSame(true, $simulatedWebKit['enabled'], 'Linux webview build spec should enable when WebKitGTK pkg-config output is available');
		$this->assertSame([], $simulatedWebKit['diagnostics'], 'available WebKitGTK should not emit dependency diagnostics');

		$diagnosticLines = render_runtime_module_explanation_lines([
			'modules' => [['name' => 'webview', 'implicit_reason' => null]],
			'webview' => [
				'backend' => 'none',
				'enabled' => false,
				'diagnostics' => $missingWebKit['diagnostics'],
			],
		]);
		$this->assertContains('WebView diagnostic: WebView disabled on Linux:', implode("\n", $diagnosticLines), 'build explanation should render WebView dependency diagnostics');

		$sampleRoot = resolve_repo_root() . '/docs/examples/php/strict/project_samples/strict_webview_events';
		$sampleSource = $this->read($sampleRoot . '/main.phs');
		$sampleConfig = json_decode($this->read($sampleRoot . '/prism.json'), true);
		if (!is_array($sampleConfig)) {
			throw new RuntimeException('strict_webview_events prism.json should decode');
		}
		$sampleModules = $sampleConfig['runtime']['modules'] ?? [];
		$this->assertSame(true, is_array($sampleModules) && in_array('webview', $sampleModules, true), 'strict WebView event sample should opt into the webview runtime module');
		$this->assertContains('take($app, $err, ui_app_create())', $sampleSource, 'strict WebView event sample should use take at the ui_app creation boundary');
		$this->assertContains('take($view, $err, webview_create($window))', $sampleSource, 'strict WebView event sample should use take at the webview creation boundary');
		$this->assertContains('ui_event_type($event)', $sampleSource, 'strict WebView event sample should branch on ui_event_type');
		$this->assertContains('ui_event_message($event)', $sampleSource, 'strict WebView event sample should read webview message payloads');
		$this->assertContains('ui_event_url($event)', $sampleSource, 'strict WebView event sample should read webview URL payloads');
		$this->assertContains('webview_message', $sampleSource, 'strict WebView event sample should demonstrate the message event');

		echo "PASS: scpp webview module\n";
		return 0;
	}

	private function assertSame(mixed $expected, mixed $actual, string $message): void
	{
		if ($expected !== $actual) {
			throw new RuntimeException($message . ' expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
		}
	}

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (!str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' missing `' . $needle . '`');
		}
	}

	private function read(string $path): string
	{
		$contents = file_get_contents($path);
		if (!is_string($contents)) {
			throw new RuntimeException('Failed to read ' . $path);
		}
		return $contents;
	}
}

exit((new ScppWebviewModuleTest())->run());
