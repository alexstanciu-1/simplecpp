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
			$this->assertSame(false, $buildCli['compile_runtime'], 'scpp build should reuse runtime by default');
			$this->assertSame(false, $buildCli['compile_dependencies'], 'scpp build should reuse dependencies by default');
			$this->assertSame(false, $buildCli['force_runtime_rebuild'], 'scpp build should not force runtime rebuild by default');

			$buildExplicit = parse_build_command_arguments(['--build-runtime', '--build-dependencies']);
			$this->assertSame(true, $buildExplicit['compile_runtime'], 'build-runtime should enable runtime compilation');
			$this->assertSame(true, $buildExplicit['compile_dependencies'], 'build-dependencies should enable dependency compilation');

			$buildForce = parse_build_command_arguments(['--force']);
			$this->assertSame(true, $buildForce['compile_runtime'], 'force should imply runtime compilation');
			$this->assertSame(true, $buildForce['force_runtime_rebuild'], 'force should request runtime rebuild');

			$buildEntry = parse_build_command_arguments(['--entry=tests/sample.phs']);
			$this->assertSame('tests/sample.phs', $buildEntry['entry_override'], 'build should accept an entry override');

			$runDefault = parse_run_command_arguments(['--', 'arg1', 'arg2']);
			$this->assertSame(false, $runDefault['build_options']['compile_runtime'], 'scpp run should reuse runtime by default');
			$this->assertSame(false, $runDefault['build_options']['compile_dependencies'], 'scpp run should reuse dependencies by default');
			$this->assertSame(['arg1', 'arg2'], $runDefault['run_args'], 'run args after separator should be preserved');

			$runExplicit = parse_run_command_arguments(['--build-runtime', '--build-dependencies', '--', 'arg1']);
			$this->assertSame(true, $runExplicit['build_options']['compile_runtime'], 'run build-runtime should enable runtime compilation');
			$this->assertSame(true, $runExplicit['build_options']['compile_dependencies'], 'run build-dependencies should enable dependency compilation');
			$this->assertSame(['arg1'], $runExplicit['run_args'], 'run args should stay intact when build flags are present');

			$runForce = parse_run_command_arguments(['--force', '--', 'arg1']);
			$this->assertSame(true, $runForce['build_options']['compile_runtime'], 'run force should imply runtime compilation');
			$this->assertSame(true, $runForce['build_options']['force_runtime_rebuild'], 'run force should request runtime rebuild');

			$runEntry = parse_run_command_arguments(['--entry=tests/sample.phs', '--', 'arg1']);
			$this->assertSame('tests/sample.phs', $runEntry['build_options']['entry_override'], 'run should accept an entry override');

			$runImplicitArgs = parse_run_command_arguments(['hello', 'world']);
			$this->assertSame(['hello', 'world'], $runImplicitArgs['run_args'], 'plain run args without separator should still work');

			$runtimeBuildDefault = parse_runtime_build_command_arguments([]);
			$this->assertSame('debug', $runtimeBuildDefault['build_mode'], 'runtime-build should default to debug mode');
			$this->assertSame(false, $runtimeBuildDefault['force'], 'runtime-build should not force by default');

			$runtimeBuildRelease = parse_runtime_build_command_arguments(['--release', '--force']);
			$this->assertSame('release', $runtimeBuildRelease['build_mode'], 'runtime-build should accept release mode');
			$this->assertSame(true, $runtimeBuildRelease['force'], 'runtime-build should accept force');

			$this->assertUpdateArgumentHandling();

			$this->assertNinjaRenderingRespectsReuseFlags();
			$this->assertEntryOverrideCanSelectAnotherFile();

			echo "PASS: scpp build options\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function assertUpdateArgumentHandling(): void
	{
		ob_start();
		try {
			handle_update(['--definitely-unknown']);
			throw new RuntimeException('handle_update should reject unknown flags');
		} catch (ScppCliException $ex) {
			ob_get_clean();
			$this->assertSame(1, $ex->exitCode, 'unknown update flags should exit with code 1');
			$this->assertContains('Unknown option for `scpp update`', $ex->getMessage(), 'unknown update flags should report the offending option');
			return;
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

	private function assertEntryOverrideCanSelectAnotherFile(): void
	{
		if (find_command_path(['ninja']) === null) {
			return;
		}
		if (resolve_compiler(['build' => []]) === null) {
			return;
		}

		$projectRoot = $this->root . '/entry_project';
		$this->mkdir($projectRoot . '/native_cpp');
		$this->write($projectRoot . '/main.phs', "<?php\necho \"main\\n\";\n");
		$this->write($projectRoot . '/alt.phs', "<?php\necho \"alt\\n\";\n");
		$config = [
			'config_version' => 1,
			'project_name' => 'entry_project',
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
					'php' => ['profile' => 'legacy'],
				],
			],
		];
		$json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		if (!is_string($json)) {
			throw new RuntimeException('Failed to encode entry override prism.json');
		}
		$this->write($projectRoot . '/prism.json', $json . PHP_EOL);

		$runtimeSeed = scpp_run_build_service($projectRoot, $projectRoot . '/prism.json', [
			'compile_runtime' => true,
		]);
		$this->assertSame(true, $runtimeSeed['ok'], 'initial runtime-seeded build should succeed');

		$overrideBuild = scpp_run_build_service($projectRoot, $projectRoot . '/prism.json', [
			'entry_override' => 'alt.phs',
		]);
		$this->assertSame(true, $overrideBuild['ok'], 'entry override build should succeed');
		$this->assertContains('.prism/build/alt', $overrideBuild['output'], 'entry override build should target the alternate entry output');
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
