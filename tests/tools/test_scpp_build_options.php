<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppBuildOptionsTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_build_options_test_' . getmypid() . '_' . bin2hex(random_bytes(4)));
	}

	public function run(): int
	{
		try {
			$defaultBuild = normalize_build_execution_options([]);
			$this->assertSame(false, $defaultBuild['compile_runtime'], 'service builds should reuse runtime by default');
			$this->assertSame(false, $defaultBuild['compile_dependencies'], 'service builds should reuse dependency artifacts by default');

			$buildCli = parse_build_command_arguments([]);
			$this->assertSame(true, $buildCli['compile_runtime'], 'scpp build should compile runtime by default');
			$this->assertSame(true, $buildCli['compile_dependencies'], 'scpp build should compile dependencies by default');

			$buildReuse = parse_build_command_arguments(['--reuse-runtime', '--reuse-dependencies']);
			$this->assertSame(false, $buildReuse['compile_runtime'], 'reuse-runtime should disable runtime compilation');
			$this->assertSame(false, $buildReuse['compile_dependencies'], 'reuse-dependencies should disable dependency compilation');

			$runDefault = parse_run_command_arguments(['--', 'arg1', 'arg2']);
			$this->assertSame(true, $runDefault['build_options']['compile_runtime'], 'scpp run should compile runtime by default');
			$this->assertSame(true, $runDefault['build_options']['compile_dependencies'], 'scpp run should compile dependencies by default');
			$this->assertSame(['arg1', 'arg2'], $runDefault['run_args'], 'run args after separator should be preserved');

			$runReuse = parse_run_command_arguments(['--reuse-runtime', '--reuse-dependencies', '--', 'arg1']);
			$this->assertSame(false, $runReuse['build_options']['compile_runtime'], 'run reuse-runtime should disable runtime compilation');
			$this->assertSame(false, $runReuse['build_options']['compile_dependencies'], 'run reuse-dependencies should disable dependency compilation');
			$this->assertSame(['arg1'], $runReuse['run_args'], 'run args should stay intact when reuse flags are present');

			$runImplicitArgs = parse_run_command_arguments(['hello', 'world']);
			$this->assertSame(['hello', 'world'], $runImplicitArgs['run_args'], 'plain run args without separator should still work');

			$this->assertNinjaRenderingRespectsReuseFlags();

			echo "PASS: scpp build options\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function assertNinjaRenderingRespectsReuseFlags(): void
	{
		$projectRoot = $this->root . '/app';
		$dependencyRoot = $this->root . '/dep';
		$repoRoot = $this->root . '/repo';
		$buildDir = $projectRoot . '/.prism/build';
		$generatedDir = $projectRoot . '/.prism/generated';
		$this->mkdir($buildDir);
		$this->mkdir($generatedDir);
		$this->mkdir($dependencyRoot . '/.prism/generated');
		$this->mkdir($repoRoot . '/runtime/include');

		$generatedUnits = [
			[
				'project_root' => $projectRoot,
				'relative_php' => 'main.phs',
				'generated_cpp' => $generatedDir . '/main.cpp',
				'object_path' => $buildDir . '/main.o',
				'is_entrypoint' => true,
				'force_include_header' => null,
			],
			[
				'project_root' => $dependencyRoot,
				'relative_php' => 'dep.phs',
				'generated_cpp' => $dependencyRoot . '/.prism/generated/dep.cpp',
				'object_path' => $dependencyRoot . '/.prism/build/dep.o',
				'is_entrypoint' => false,
				'force_include_header' => null,
			],
		];
		$compiler = [
			'command' => 'g++',
			'kind' => 'gnu_like',
			'launcher' => null,
			'linker_flags' => [],
		];
		$runtimeConfig = [
			'languages' => ['php'],
			'modules' => ['json', 'filesystem'],
			'language_profiles' => [
				'php' => ['profile' => 'legacy'],
			],
		];

		$reuseNinja = render_build_ninja(
			$projectRoot,
			$repoRoot,
			$buildDir,
			$generatedDir,
			$generatedUnits,
			[],
			'app',
			$compiler,
			'debug',
			$runtimeConfig,
			[],
			null,
			['compile_runtime' => false, 'compile_dependencies' => false]
		);
		$this->assertNotContains('rule compile_runtime', $reuseNinja, 'reuse runtime mode should omit runtime compile rules');
		$this->assertNotContains('compile_pch_runtime', $reuseNinja, 'reuse runtime mode should omit runtime pch rules');
		$this->assertNotContains('build ../dep/.prism/build/dep.o: compile', $reuseNinja, 'reuse dependency mode should omit dependency compile edges');

		$fullNinja = render_build_ninja(
			$projectRoot,
			$repoRoot,
			$buildDir,
			$generatedDir,
			$generatedUnits,
			[],
			'app',
			$compiler,
			'debug',
			$runtimeConfig,
			[],
			null,
			['compile_runtime' => true, 'compile_dependencies' => true]
		);
		$this->assertContains('rule compile_runtime', $fullNinja, 'full build mode should include runtime compile rules');
		$this->assertContains('compile_pch_runtime', $fullNinja, 'full build mode should include runtime pch rules');
		$this->assertContains('build ../dep/.prism/build/dep.o: compile', $fullNinja, 'full build mode should include dependency compile edges');
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

	private function assertNotContains(string $needle, string $haystack, string $message): void
	{
		if (str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' unexpectedly found `' . $needle . '`');
		}
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
}

exit((new ScppBuildOptionsTest())->run());
