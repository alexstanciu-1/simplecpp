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
		$this->assertContains('class webview', $strictRuntimeSymbols, 'strict shallow runtime should expose the webview handle shape');

		$webviewBuild = resolve_runtime_webview_build_spec();
		$this->assertSame($webviewBuild['backend'], $explanation['runtime_modules']['webview']['backend'] ?? null, 'build explanation should report the selected webview backend');
		if (PHP_OS_FAMILY === 'Linux' && !$webviewBuild['enabled']) {
			$this->assertSame('none', $webviewBuild['backend'], 'missing WebKitGTK should report no selected backend');
			$this->assertContains('-DSCPP_HAS_WEBVIEW=0', implode(' ', $webviewBuild['compile_defines']), 'missing WebKitGTK should disable the webview build spec cleanly');
		} elseif (PHP_OS_FAMILY === 'Windows' && !$webviewBuild['enabled']) {
			$this->assertSame('webview2', $webviewBuild['backend'], 'Windows webview build spec should report WebView2 even when the SDK has not been restored');
			$this->assertContains('-DSCPP_HAS_WEBVIEW=0', implode(' ', $webviewBuild['compile_defines']), 'missing WebView2 SDK should disable the webview build spec cleanly');
			$this->assertSame([], $webviewBuild['ldflags'], 'missing WebView2 SDK should not emit loader link flags');
		} else {
			$this->assertSame(true, $webviewBuild['enabled'], 'webview build spec should be enabled when a backend is available or not required for the platform facade');
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
			} else {
				$this->assertSame('facade', $webviewBuild['backend'], 'unsupported WebView render platforms should report facade-only backend metadata');
			}
		}

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
