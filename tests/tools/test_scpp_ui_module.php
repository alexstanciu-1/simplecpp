<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

use Scpp\S2S\Analysis\RuntimeShallowSourceGenerator;
use Scpp\S2S\Stan\StanPhpRuntimeFunctionCatalog;

final class ScppUiModuleTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_ui_module_' . getmypid() . '_' . bin2hex(random_bytes(4)));
		register_shutdown_function(function (): void {
			$this->removeTree($this->root);
		});
	}

	public function run(): int
	{
		try {
			$config = resolve_runtime_build_config([
				'runtime' => [
					'languages' => [
						'php' => ['profile' => 'strict'],
					],
					'modules' => ['json', 'filesystem', 'datetime', 'ui'],
				],
			]);
			$this->assertSame(true, in_array('ui', $config['modules'], true), 'ui should be an accepted runtime module');

			$source = render_runtime_composition_source($config);
			$this->assertContains('#include "modules/ui/ui.cpp"', $source, 'ui module composition should include the ui runtime source');

			$catalog = new StanPhpRuntimeFunctionCatalog();
			$this->assertSame(true, $catalog->hasFunction('ui_app_create'), 'STAN catalog should recognize ui_app_create');
			$this->assertSame('ui', $catalog->requiredModule('ui_window_create'), 'STAN catalog should require the ui module for ui helpers');
			$this->assertSame(true, $catalog->hasFunction('ui_event_text'), 'STAN catalog should recognize ui_event_text');

			$generated = (new RuntimeShallowSourceGenerator())->generate(resolve_repo_root(), 'strict');
			$strictRuntimeSymbols = $this->read(resolve_repo_root() . '/runtime/generated/stan/runtime_symbols_strict.phs');
			$this->assertSame('strict', $generated['profile'], 'strict shallow runtime generation should complete');
			$this->assertContains('function ui_app_create(): result<ui_app>', $strictRuntimeSymbols, 'strict shallow runtime should expose result-returning ui_app_create');
			$this->assertContains('function ui_window_create(ui_app $app, string $title, int $width, int $height): result<ui_window>', $strictRuntimeSymbols, 'strict shallow runtime should expose typed ui_window_create');
			$this->assertContains('function ui_event_text(ui_event $event): string', $strictRuntimeSymbols, 'strict shallow runtime should expose ui_event_text payload access');
			$this->assertContains('class ui_event', $strictRuntimeSymbols, 'strict shallow runtime should expose the ui_event handle shape');

			$uiBuild = resolve_runtime_ui_build_spec();
			if (PHP_OS_FAMILY === 'Linux' && !$uiBuild['enabled']) {
				$this->assertContains('-DSCPP_HAS_UI=0', implode(' ', $uiBuild['compile_defines']), 'missing GTK should disable the ui build spec cleanly');
			} else {
				$this->assertContains('-DSCPP_HAS_UI=1', implode(' ', $uiBuild['compile_defines']), 'available native ui backend should enable the ui build spec');
				if (PHP_OS_FAMILY === 'Linux') {
					$this->assertContains('-DSCPP_UI_BACKEND_GTK=1', implode(' ', $uiBuild['compile_defines']), 'Linux ui build spec should select the GTK backend');
				} elseif (PHP_OS_FAMILY === 'Darwin') {
					$this->assertContains('-DSCPP_UI_BACKEND_APPKIT=1', implode(' ', $uiBuild['compile_defines']), 'macOS ui build spec should select the AppKit backend');
					$this->assertContains('-framework', implode(' ', $uiBuild['ldflags']), 'macOS ui build spec should link Cocoa frameworks');
					$this->assertContains('Cocoa', implode(' ', $uiBuild['ldflags']), 'macOS ui build spec should link Cocoa');
				} elseif (PHP_OS_FAMILY === 'Windows') {
					$this->assertContains('-DSCPP_UI_BACKEND_WIN32=1', implode(' ', $uiBuild['compile_defines']), 'Windows ui build spec should select the Win32 backend');
					$this->assertContains('user32.lib', implode(' ', $uiBuild['ldflags']), 'Windows ui build spec should link user32');
					$this->assertContains('gdi32.lib', implode(' ', $uiBuild['ldflags']), 'Windows ui build spec should link gdi32');
					$this->assertContains('ole32.lib', implode(' ', $uiBuild['ldflags']), 'Windows ui build spec should link OLE for STA initialization');
				}
			}

			echo "PASS: scpp ui module\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
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

	private function removeTree(string $path): void
	{
		if (!is_dir($path)) {
			return;
		}
		$items = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ($items as $item) {
			if (!$item instanceof SplFileInfo) {
				continue;
			}
			$itemPath = $item->getPathname();
			if ($item->isDir() && !$item->isLink()) {
				rmdir($itemPath);
			} else {
				unlink($itemPath);
			}
		}
		rmdir($path);
	}
}

exit((new ScppUiModuleTest())->run());
