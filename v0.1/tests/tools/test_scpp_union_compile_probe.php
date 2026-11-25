<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppUnionCompileProbeTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_union_compile_probe_' . getmypid() . '_' . bin2hex(random_bytes(4)));
	}

	public function run(): int
	{
		try {
			if (find_command_path(['ninja']) === null || resolve_compiler(['build' => []]) === null) {
				echo "SKIP: scpp union compile probe (native compiler unavailable)\n";
				return 0;
			}

			$project = $this->root . '/app';
			$this->mkdir($project . '/native_cpp');
			$this->write($project . '/payload.phs', implode("\n", [
				'struct PairPayload {',
				'	uint16 $left = 0;',
				'	uint16 $right = 0;',
				'}',
				'union ExpressionPayload {',
				'	uint32 $int_value;',
				'	PairPayload $pair;',
				'}',
				'struct Row {',
				'	ExpressionPayload $payload;',
				'}',
				'',
			]));
			$this->write($project . '/main.phs', implode("\n", [
				'echo layout_sizeof(ExpressionPayload), " ", layout_alignof(ExpressionPayload), "\n";',
				'',
			]));
			$this->write($project . '/native_cpp/probe.cpp', implode("\n", [
				'#include "payload.hpp"',
				'namespace scpp_union_compile_probe {',
				'void force_row_value_construction() {',
				'	scpp::Row row{};',
				'	(void) row;',
				'}',
				'}',
				'',
			]));
			$this->writeProjectConfig($project, 'union_compile_probe', 'main.phs');

			$build = scpp_run_build_service($project, $project . '/prism.json', [
				'compile_runtime' => true,
				'disable_stan' => true,
			]);
			$this->assertSame(
				true,
				$build['ok'],
				"union payload project should compile natively\nSTDOUT:\n"
					. (string) ($build['output'] ?? '')
					. "\nSTDERR:\n"
					. (string) ($build['error'] ?? '')
			);

			echo "PASS: scpp union compile probe\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function writeProjectConfig(string $projectRoot, string $projectName, string $entrypoint): void
	{
		$config = [
			'config_version' => 1,
			'project_name' => $projectName,
			'entrypoint' => $entrypoint,
			'build_dir' => '.prism/build',
			'generated_dir' => '.prism/generated',
			'cache_dir' => '.prism/cache',
			'native_cpp_dir' => 'native_cpp',
			'dependencies' => [],
			'libraries' => [],
			'build' => [
				'backend' => 'ninja',
				'cxx' => null,
				'mode' => 'debug',
			],
			'runtime' => [
				'languages' => ['php'],
				'modules' => ['json', 'filesystem'],
				'language_profiles' => [
					'php' => ['profile' => 'strict'],
				],
			],
		];
		$json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		if (!is_string($json)) {
			throw new RuntimeException('Failed to encode prism.json');
		}
		$this->write($projectRoot . '/prism.json', $json . PHP_EOL);
	}

	private function mkdir(string $path): void
	{
		if (!is_dir($path) && !mkdir($path, 0777, true)) {
			throw new RuntimeException('Failed to create ' . $path);
		}
	}

	private function write(string $path, string $contents): void
	{
		$dir = dirname($path);
		if (!is_dir($dir)) {
			$this->mkdir($dir);
		}
		if (file_put_contents($path, $contents) === false) {
			throw new RuntimeException('Failed to write ' . $path);
		}
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
			if ($item->isDir()) {
				@rmdir($item->getPathname());
			} else {
				@unlink($item->getPathname());
			}
		}
		@rmdir($path);
	}

	private function assertSame(mixed $expected, mixed $actual, string $message): void
	{
		if ($expected !== $actual) {
			throw new RuntimeException($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . '.');
		}
	}
}

exit((new ScppUnionCompileProbeTest())->run());
