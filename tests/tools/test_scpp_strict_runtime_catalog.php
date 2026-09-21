<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

use Scpp\S2S\Analysis\RuntimeShallowSourceGenerator;
use Scpp\S2S\Stan\StanPhpRuntimeFunctionCatalog;

final class ScppStrictRuntimeCatalogTest
{
	public function run(): int
	{
		$catalog = new StanPhpRuntimeFunctionCatalog();
		foreach ([
			'layout_sizeof',
			'layout_alignof',
			'layout_offsetof',
			'layout_field_sizeof',
			'memory_get_usage',
			'memory_get_peak_usage',
		] as $name) {
			$this->assertSame(true, $catalog->hasFunction($name), 'STAN catalog should recognize ' . $name);
			$this->assertSame('int', $catalog->returnType($name), 'STAN catalog should infer int return type for ' . $name);
			$this->assertSame(null, $catalog->requiredModule($name), 'STAN catalog should treat ' . $name . ' as a core helper');
		}

		$this->assertSame(true, $catalog->hasFunction('collection_map'), 'collection map is registered');
		$this->assertSame(null, $catalog->returnType('collection_map'), 'polymorphic calls have no fixed return type');
		$this->assertSame(null, $catalog->requiredModule('collection_map'), 'collection helpers are core');

		$this->assertSame('result<mixed>', $catalog->returnType('json_decode'), 'STAN should expose the checked JSON result');
		$this->assertSame('result<string>', $catalog->returnType('json_encode'), 'STAN should expose the checked JSON encoding result');
		foreach (['fs_lock_try' => 'result<bool>', 'fs_lock_release' => 'result<bool>', 'fs_lock_transfer' => 'result<file_lock_handle>'] as $name => $return) {
			$this->assertSame(true, $catalog->hasFunction($name), 'STAN should recognize lock operation ' . $name);
			$this->assertSame($return, $catalog->returnType($name), 'STAN should preserve lock result ' . $name);
			$this->assertSame('filesystem', $catalog->requiredModule($name), 'lock operations require filesystem');
		}

		foreach (['process_start' => 'result<process_handle>', 'process_poll' => 'result<bool>', 'process_result' => 'result<process_output>', 'process_stop' => 'result<bool>', 'process_close' => 'result<bool>'] as $name => $return) {
			$this->assertSame(true, $catalog->hasFunction($name), 'STAN recognizes ' . $name);
			$this->assertSame($return, $catalog->returnType($name), 'typed process result');
			$this->assertSame('process', $catalog->requiredModule($name), 'process module ownership');
		}

		foreach (['sequence_map', 'sequence_filter', 'keyed_map', 'keyed_filter'] as $name) {
			$this->assertSame(true, $catalog->hasFunction($name), 'constrained adapter registered');
			$this->assertSame(null, $catalog->returnType($name), 'adapter return requires instantiation');
			$this->assertSame(null, $catalog->requiredModule($name), 'adapters are core');
		}

		$generated = (new RuntimeShallowSourceGenerator())->generate(resolve_repo_root(), 'strict');
		$strictRuntimeSymbols = $this->read(resolve_repo_root() . '/runtime/generated/stan/runtime_symbols_strict.phs');
		$this->assertContains('public function get_message(): string', $strictRuntimeSymbols, 'captured errors should expose their message to STAN');
		$this->assertSame('strict', $generated['profile'], 'strict shallow runtime generation should complete');
		$this->assertContains('function fs_lock_try(file_lock_handle &$out, string $path, bool $shared = false): result<bool>', $strictRuntimeSymbols, 'normalized contracts should retain reference output and optional shared mode');
		$this->assertContains('class file_lock_handle', $strictRuntimeSymbols, 'STAN should know the opaque lock type');
		$this->assertContains('function layout_sizeof(mixed $type_name): int', $strictRuntimeSymbols, 'strict shallow runtime should expose layout_sizeof');
		$this->assertContains('function layout_alignof(mixed $type_name): int', $strictRuntimeSymbols, 'strict shallow runtime should expose layout_alignof');
		$this->assertContains('function layout_offsetof(mixed $type_name, mixed $field_name): int', $strictRuntimeSymbols, 'strict shallow runtime should expose layout_offsetof');
		$this->assertContains('function layout_field_sizeof(mixed $type_name, mixed $field_name): int', $strictRuntimeSymbols, 'strict shallow runtime should expose layout_field_sizeof');
		$this->assertContains('function memory_get_usage(bool $real_usage = false): int', $strictRuntimeSymbols, 'strict shallow runtime should expose memory_get_usage');
		$this->assertContains('function memory_get_peak_usage(bool $real_usage = false): int', $strictRuntimeSymbols, 'strict shallow runtime should expose memory_get_peak_usage');

		echo "PASS: scpp strict runtime catalog\n";
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

exit((new ScppStrictRuntimeCatalogTest())->run());
