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

		$generated = (new RuntimeShallowSourceGenerator())->generate(resolve_repo_root(), 'strict');
		$strictRuntimeSymbols = $this->read(resolve_repo_root() . '/runtime/generated/stan/runtime_symbols_strict.phs');
		$this->assertSame('strict', $generated['profile'], 'strict shallow runtime generation should complete');
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
