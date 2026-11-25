<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppDocsTest
{
	public function run(): int
	{
		$registry = scpp_docs_registry();
		$this->assertArrayHasKey('strict', $registry, 'docs registry should expose strict quick-learn');
		$this->assertArrayHasKey('diagnostics', $registry, 'docs registry should expose diagnostics guidance');
		$this->assertArrayHasKey('skill', $registry, 'docs registry should expose the strict Agent Skill');
		$this->assertArrayHasKey('ui-webview', $registry, 'docs registry should expose UI/WebView preview guidance');

		$repoRoot = resolve_repo_root();
		foreach ($registry as $name => $entry) {
			$path = normalize_path($repoRoot . '/' . $entry['path']);
			if (!is_file($path)) {
				throw new RuntimeException('docs registry entry `' . $name . '` points to missing file `' . $entry['path'] . '`');
			}
		}

		$index = render_docs_index($registry);
		$this->assertContains('Usage: scpp docs <name>', $index, 'docs index should show usage');
		$this->assertContains('strict', $index, 'docs index should list strict docs');
		$this->assertContains('ui-webview', $index, 'docs index should list UI/WebView preview docs');

		$script = normalize_path($repoRoot . '/bin/scpp.php');
		$strict = scpp_run_optional_command($repoRoot, [PHP_BINARY, $script, 'docs', 'strict'], [], 5.0);
		$this->assertSame(0, $strict['exit_code'], 'strict docs command should succeed');
		$this->assertContains('Doc: strict', $strict['stdout'], 'strict docs output should identify requested doc');
		$this->assertContains('Source: specs/simple_cpp_php_strict_quick_learn.md', $strict['stdout'], 'strict docs output should identify source path');
		$this->assertContains('PHP++ Quick Learn', $strict['stdout'], 'strict docs output should print content');

		$uiWebview = scpp_run_optional_command($repoRoot, [PHP_BINARY, $script, 'docs', 'ui-webview'], [], 5.0);
		$this->assertSame(0, $uiWebview['exit_code'], 'ui-webview docs command should succeed');
		$this->assertContains('Doc: ui-webview', $uiWebview['stdout'], 'ui-webview docs output should identify requested doc');
		$this->assertContains('Source: docs/ui_webview_preview.md', $uiWebview['stdout'], 'ui-webview docs output should identify source path');
		$this->assertContains('Frozen Initial API', $uiWebview['stdout'], 'ui-webview docs output should print API freeze content');
		$this->assertContains('strict_webview_bridge', $uiWebview['stdout'], 'ui-webview docs output should reference the golden bridge sample');

		$unknown = scpp_run_optional_command($repoRoot, [PHP_BINARY, $script, 'docs', 'definitely-missing-doc'], [], 5.0);
		$this->assertSame(1, $unknown['exit_code'], 'unknown docs name should fail');
		$this->assertContains('Unknown docs name: definitely-missing-doc', $unknown['stderr'], 'unknown docs name should be reported');
		$this->assertContains('Known docs:', $unknown['stderr'], 'unknown docs failure should include docs index');

		echo "PASS: scpp docs\n";
		return 0;
	}

	private function assertArrayHasKey(string $key, array $array, string $message): void
	{
		if (!array_key_exists($key, $array)) {
			throw new RuntimeException($message . ' missing key `' . $key . '`');
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
}

exit((new ScppDocsTest())->run());
