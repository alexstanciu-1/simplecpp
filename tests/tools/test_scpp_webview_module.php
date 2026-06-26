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

		$source = render_runtime_composition_source($config);
		$this->assertContains('#include "modules/ui/ui.cpp"', $source, 'webview composition should include the ui runtime source');
		$this->assertContains('#include "modules/webview/webview.cpp"', $source, 'webview composition should include the webview runtime source');

		$catalog = new StanPhpRuntimeFunctionCatalog();
		$this->assertSame(true, $catalog->hasFunction('webview_create'), 'STAN catalog should recognize webview_create');
		$this->assertSame('webview', $catalog->requiredModule('webview_load_html'), 'STAN catalog should require the webview module for webview helpers');
		$this->assertSame(true, $catalog->hasFunction('webview_reply_ok'), 'STAN catalog should recognize webview_reply_ok');
		$this->assertSame(true, $catalog->hasFunction('webview_reply_error'), 'STAN catalog should recognize webview_reply_error');

		$generated = (new RuntimeShallowSourceGenerator())->generate(resolve_repo_root(), 'strict');
		$strictRuntimeSymbols = $this->read(resolve_repo_root() . '/runtime/generated/stan/runtime_symbols_strict.phs');
		$this->assertSame('strict', $generated['profile'], 'strict shallow runtime generation should complete');
		$this->assertContains('function webview_create(ui_window $window): result<webview>', $strictRuntimeSymbols, 'strict shallow runtime should expose result-returning webview_create');
		$this->assertContains('function webview_load_html(webview $view, string $html): result<bool>', $strictRuntimeSymbols, 'strict shallow runtime should expose typed webview_load_html');
		$this->assertContains('function webview_reply_ok(webview $view, int $id, string $value_json): result<bool>', $strictRuntimeSymbols, 'strict shallow runtime should expose typed webview_reply_ok');
		$this->assertContains('function webview_reply_error(webview $view, int $id, string $code, string $message): result<bool>', $strictRuntimeSymbols, 'strict shallow runtime should expose typed webview_reply_error');
		$this->assertContains('class webview', $strictRuntimeSymbols, 'strict shallow runtime should expose the webview handle shape');

		$webviewBuild = resolve_runtime_webview_build_spec();
		if (PHP_OS_FAMILY === 'Linux' && !$webviewBuild['enabled']) {
			$this->assertContains('-DSCPP_HAS_WEBVIEW=0', implode(' ', $webviewBuild['compile_defines']), 'missing WebKitGTK should disable the webview build spec cleanly');
		} else {
			$this->assertSame(true, $webviewBuild['enabled'], 'webview build spec should be enabled when a backend is available or not required for the platform facade');
			$this->assertContains('-DSCPP_HAS_WEBVIEW=1', implode(' ', $webviewBuild['compile_defines']), 'webview build spec should enable the webview facade');
			if (PHP_OS_FAMILY === 'Linux') {
				$this->assertContains('-DSCPP_WEBVIEW_BACKEND_WEBKITGTK=1', implode(' ', $webviewBuild['compile_defines']), 'Linux webview build spec should select the WebKitGTK backend');
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
