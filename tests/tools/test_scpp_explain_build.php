<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppExplainBuildTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_explain_build_' . getmypid() . '_' . bin2hex(random_bytes(4)));
	}

	public function run(): int
	{
		if (find_command_path(['ninja']) === null) {
			echo "SKIP: ninja not found\n";
			return 0;
		}
		if (resolve_compiler(['build' => []]) === null) {
			echo "SKIP: compiler not found\n";
			return 0;
		}

		try {
			$project = $this->root . '/app';
			$this->mkdir($project . '/native_cpp');
			$this->write($project . '/main.phs', "echo \"hello\\n\";\n");
			$config = [
				'config_version' => 1,
				'project_name' => 'explain_build',
				'entrypoint' => 'main.phs',
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
			$this->write($project . '/prism.json', $json . PHP_EOL);

			$fullBuild = scpp_run_build_service($project, $project . '/prism.json', [
				'compile_runtime' => true,
				'compile_dependencies' => false,
			]);
			$this->assertSame(true, $fullBuild['ok'], 'seed build should succeed');

			$warmBuild = scpp_run_build_service($project, $project . '/prism.json');
			$this->assertSame(true, $warmBuild['ok'], 'warm build should succeed');

			$report = json_decode($this->read($project . '/.prism/last_run.json'), true);
			if (!is_array($report)) {
				throw new RuntimeException('last_run.json should decode as an object');
			}
			$details = is_array($report['details'] ?? null) ? $report['details'] : null;
			if (!is_array($details)) {
				throw new RuntimeException('last_run.json should contain details');
			}
			$explanation = is_array($details['build_explanation'] ?? null) ? $details['build_explanation'] : null;
			if (!is_array($explanation)) {
				throw new RuntimeException('last_run.json should contain build_explanation');
			}
			$this->assertSame('success', $explanation['status'] ?? null, 'build explanation should record success');
			$this->assertSame('reuse', $explanation['runtime']['action'] ?? null, 'warm build should report runtime reuse');
			$this->assertSame(['reusing existing runtime artifact by default'], $explanation['runtime']['reasons'] ?? null, 'warm build should preserve runtime reuse reason');

			$sources = is_array($explanation['sources'] ?? null) ? $explanation['sources'] : [];
			$mainSource = $sources[0] ?? null;
			if (!is_array($mainSource)) {
				throw new RuntimeException('build explanation should include at least one source record');
			}
			$this->assertSame('main.phs', $mainSource['path'] ?? null, 'source explanation should preserve relative path');
			$this->assertSame('reused', $mainSource['action'] ?? null, 'warm build should reuse unchanged source');

			$script = normalize_path(resolve_repo_root() . '/bin/scpp.php');
			$explain = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build'], [], 20.0);
			$this->assertSame(0, $explain['exit_code'], 'scpp explain-build should succeed');
			$this->assertContains('Explain build: build', $explain['stdout'], 'explain-build should identify the saved command');
			$this->assertContains('Runtime: reuse (reusing existing runtime artifact by default)', $explain['stdout'], 'explain-build should explain runtime reuse');
			$this->assertContains('main.phs -> reused (source metadata and generated artifacts unchanged)', $explain['stdout'], 'explain-build should explain source reuse');

			$transpiledView = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build', 'files-transpiled'], [], 20.0);
			$this->assertSame(0, $transpiledView['exit_code'], 'scpp explain-build files-transpiled should succeed');
			$this->assertContains('Files transpiled: none', $transpiledView['stdout'], 'files-transpiled should report no warm-build transpiles');

			$reusedView = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build', 'files-reused'], [], 20.0);
			$this->assertSame(0, $reusedView['exit_code'], 'scpp explain-build files-reused should succeed');
			$this->assertContains('Files reused:', $reusedView['stdout'], 'files-reused should include a header');
			$this->assertContains('main.phs (source metadata and generated artifacts unchanged)', $reusedView['stdout'], 'files-reused should list the reused source');

			$entrypointView = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build', 'entrypoint'], [], 20.0);
			$this->assertSame(0, $entrypointView['exit_code'], 'scpp explain-build entrypoint should succeed');
			$this->assertContains('Entrypoint: main.phs', $entrypointView['stdout'], 'entrypoint should list the entry source');
			$this->assertContains('Generated C++: .prism/generated/main.cpp', $entrypointView['stdout'], 'entrypoint should list the generated C++ path');
			$this->assertContains('Object: .prism/build/main.o', $entrypointView['stdout'], 'entrypoint should list the object path');

			$finalOutputView = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build', 'final-output'], [], 20.0);
			$this->assertSame(0, $finalOutputView['exit_code'], 'scpp explain-build final-output should succeed');
			$this->assertContains('Final output: .prism/build/main', $finalOutputView['stdout'], 'final-output should list the executable path');

			$generatedFilesView = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build', 'generated-files'], [], 20.0);
			$this->assertSame(0, $generatedFilesView['exit_code'], 'scpp explain-build generated-files should succeed');
			$this->assertContains('Generated files:', $generatedFilesView['stdout'], 'generated-files should include a header');
			$this->assertContains('main.phs -> .prism/generated/main.cpp -> .prism/build/main.o', $generatedFilesView['stdout'], 'generated-files should map source to generated and object outputs');

			echo "PASS: scpp explain-build\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function write(string $path, string $contents): void
	{
		if (file_put_contents($path, $contents) === false) {
			throw new RuntimeException('Failed to write ' . $path);
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

	private function mkdir(string $path): void
	{
		if (!is_dir($path) && !mkdir($path, 0777, true)) {
			throw new RuntimeException('Failed to create ' . $path);
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

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (!str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' missing `' . $needle . '`');
		}
	}
}

exit((new ScppExplainBuildTest())->run());
