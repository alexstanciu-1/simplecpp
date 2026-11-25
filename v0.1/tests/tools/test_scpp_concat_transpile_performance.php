<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppConcatTranspilePerformanceTest
{
	public function run(): int
	{
		$root = normalize_path(sys_get_temp_dir() . '/scpp_concat_transpile_' . getmypid() . '_' . bin2hex(random_bytes(4)));
		$this->mkdir($root);
		try {
			$sourcePath = $root . '/long_concat.phs';
			file_put_contents($sourcePath, $this->longConcatSource());
			$runnerPath = $root . '/transpile_strict.php';
			file_put_contents($runnerPath, $this->strictTranspileRunnerSource());

			$result = scpp_run_optional_command(
				$root,
				[PHP_BINARY, $runnerPath, $sourcePath],
				[],
				4.0
			);

			$this->assertSame(0, $result['exit_code'], "long concat translation should complete without timing out:\nSTDOUT:\n" . $result['stdout'] . "\nSTDERR:\n" . $result['stderr']);
			$this->assertContains('storage_context_local_id()', $result['stdout'], 'generated output should include helper calls from the concat chain');
			$this->assertContains('row->field_119', $result['stdout'], 'generated output should include the tail property read from the concat chain');
		} finally {
			$this->removeTree($root);
		}

		echo "PASS: scpp concat transpile performance\n";
		return 0;
	}

	private function longConcatSource(): string
	{
		$segments = [
			'"    {\\"storage_context\\": \\"local\\", \\"storage_context_id\\": "',
			'storage_context_local_id()',
			'", \\"symbol_key\\": \\""',
			'$row->row_key',
			'"\\""',
		];
		for ($i = 0; $i < 120; $i++) {
			$segments[] = '", \\"field_' . $i . '\\": \\""';
			$segments[] = '$row->field_' . $i;
			$segments[] = '"\\""';
		}
		$segments[] = '" }"';

		$properties = [];
		for ($i = 0; $i < 120; $i++) {
			$properties[] = "\t" . 'public string $field_' . $i . ';';
		}

		return implode("\n", array_merge([
			'class Row {',
			"\t" . 'public string $row_key;',
		], $properties, [
			'}',
			'',
			'function storage_context_local_id(): int {',
			"\t" . 'return 7;',
			'}',
			'',
			'function build_row(Row $row): string {',
			"\t" . 'return ' . implode(' . ', $segments) . ';',
			'}',
			'',
		]));
	}

	private function strictTranspileRunnerSource(): string
	{
		return implode("\n", [
			'<?php',
			'declare(strict_types=1);',
			'',
			'require_once ' . var_export(resolve_repo_root() . '/bin/bootstrap.php', true) . ';',
			'',
			'$transpiler = new Scpp\S2S\Transpiler(phpProfile: "strict");',
			'$cppFile = $transpiler->transpile($argv[1]);',
			'echo implode(PHP_EOL, $cppFile->sourceLines), PHP_EOL;',
			'',
		]);
	}

	private function mkdir(string $path): void
	{
		if (is_dir($path)) {
			return;
		}
		if (!mkdir($path, 0777, true) && !is_dir($path)) {
			throw new RuntimeException('Failed to create directory: ' . $path);
		}
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
			if (is_dir($child)) {
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

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (!str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' missing `' . $needle . '`');
		}
	}
}

exit((new ScppConcatTranspilePerformanceTest())->run());
