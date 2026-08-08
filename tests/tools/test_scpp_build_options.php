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
		register_shutdown_function(function (): void {
			$this->removeTree($this->root);
		});
	}

	public function run(): int
	{
		try {
			$defaultBuild = normalize_build_execution_options([]);
			$this->assertSame(false, $defaultBuild['compile_runtime'], 'service builds should reuse runtime by default');
			$this->assertSame(false, $defaultBuild['compile_dependencies'], 'service builds should reuse dependency artifacts by default');
			$this->assertSame(false, $defaultBuild['show_timings'], 'service builds should not print timings by default');

			$buildCli = parse_build_command_arguments([]);
			$this->assertSame(false, $buildCli['compile_runtime'], 'scpp build should reuse runtime by default');
			$this->assertSame(false, $buildCli['compile_dependencies'], 'scpp build should reuse dependencies by default');
			$this->assertSame(false, $buildCli['force_runtime_rebuild'], 'scpp build should not force runtime rebuild by default');
			$this->assertSame(false, $buildCli['show_timings'], 'scpp build should not show timings by default');

			$buildExplicit = parse_build_command_arguments(['--build-runtime', '--build-dependencies']);
			$this->assertSame(true, $buildExplicit['compile_runtime'], 'build-runtime should enable runtime compilation');
			$this->assertSame(true, $buildExplicit['compile_dependencies'], 'build-dependencies should enable dependency compilation');

			$buildForce = parse_build_command_arguments(['--force']);
			$this->assertSame(true, $buildForce['compile_runtime'], 'force should imply runtime compilation');
			$this->assertSame(true, $buildForce['force_runtime_rebuild'], 'force should request runtime rebuild');

			$buildEntry = parse_build_command_arguments(['--entry=tests/sample.phs']);
			$this->assertSame('tests/sample.phs', $buildEntry['entry_override'], 'build should accept an entry override');

			$buildTimings = parse_build_command_arguments(['--timings']);
			$this->assertSame(true, $buildTimings['show_timings'], 'build should accept a timings flag');

			$buildMode = parse_build_command_arguments(['--mode=release']);
			$this->assertSame('release', $buildMode['build_mode'], 'build should accept release mode selection');

			$runDefault = parse_run_command_arguments(['--', 'arg1', 'arg2']);
			$this->assertSame(false, $runDefault['build_options']['compile_runtime'], 'scpp run should reuse runtime by default');
			$this->assertSame(false, $runDefault['build_options']['compile_dependencies'], 'scpp run should reuse dependencies by default');
			$this->assertSame(false, $runDefault['build_options']['show_timings'], 'scpp run should not show timings by default');
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

			$runTimings = parse_run_command_arguments(['--timings', '--', 'arg1']);
			$this->assertSame(true, $runTimings['build_options']['show_timings'], 'run should accept a timings flag');
			$this->assertSame(['arg1'], $runTimings['run_args'], 'run timings flag should not consume program args');

			$runMode = parse_run_command_arguments(['--mode=debug', '--', 'arg1']);
			$this->assertSame('debug', $runMode['build_options']['build_mode'], 'run should accept debug mode selection');
			$this->assertSame(['arg1'], $runMode['run_args'], 'run mode flag should not consume program args');

			$runImplicitArgs = parse_run_command_arguments(['hello', 'world']);
			$this->assertSame(['hello', 'world'], $runImplicitArgs['run_args'], 'plain run args without separator should still work');

			$runtimeBuildDefault = parse_runtime_build_command_arguments([]);
			$this->assertSame('debug', $runtimeBuildDefault['build_mode'], 'runtime-build should default to debug mode');
			$this->assertSame(false, $runtimeBuildDefault['force'], 'runtime-build should not force by default');

			$runtimeBuildRelease = parse_runtime_build_command_arguments(['--release', '--force']);
			$this->assertSame('release', $runtimeBuildRelease['build_mode'], 'runtime-build should accept release mode');
			$this->assertSame(true, $runtimeBuildRelease['force'], 'runtime-build should accept force');

			$this->assertUpdateArgumentHandling();
			$this->assertBuildProfileResolution();
			$this->assertRuntimeLaunchEnvironment();
			$this->assertRuntimeArtifactPlacementPolicy();
			$this->assertSubprocessCaptureDoesNotDeadlockOnLargeStderr();

			$this->assertNinjaRenderingRespectsReuseFlags();
			$this->assertClangTimeTraceCanBeEnabled();
			$this->assertCrossFileEnumTypesLowerThroughDeclarationCatalog();
			$this->assertCrossFileStructTypesLowerThroughDeclarationCatalog();
			$this->assertStructPointerStyleFieldAccessCompiles();
			$this->assertStructKeyedInitializerCompiles();
			$this->assertStructContainersLowerThroughDeclarationCatalog();
			$this->assertStructValidationRejectsClassLikeFeatures();
			$this->assertFixedWidthEnumBackingLowersExactly();
			$this->assertLayoutProbesLowerToCppOperators();
			$this->assertUnionPayloadsLowerAndProbeLayout();
			$this->assertUnionPointerStylePayloadAccessCompiles();
			$this->assertUnionValidationRejectsClassLikeFeatures();
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

	private function assertSubprocessCaptureDoesNotDeadlockOnLargeStderr(): void
	{
		$result = scpp_run_command_capture(sys_get_temp_dir(), [
			PHP_BINARY,
			'-r',
			'fwrite(STDOUT, "ready\n"); fwrite(STDERR, str_repeat("E", 262144)); exit(7);',
		], [], 5.0);

		$this->assertSame(7, $result['exit_code'], 'large-stderr subprocess exit code should be captured');
		$this->assertSame("ready\n", $result['stdout'], 'large-stderr subprocess stdout should be captured');
		$this->assertSame(262144, strlen($result['stderr']), 'large stderr payload should be drained without deadlock');
	}

	private function assertBuildProfileResolution(): void
	{
		$config = [
			'build_dir' => '.prism/build',
			'generated_dir' => '.prism/generated',
			'cache_dir' => '.prism/cache',
			'build' => [
				'backend' => 'ninja',
				'mode' => 'debug',
			],
			'profiles' => [
				'release' => [
					'build_dir' => '.prism/build/release-custom',
					'generated_dir' => '.prism/generated/release-custom',
					'cache_dir' => '.prism/cache/release-custom',
					'build' => [
						'mode' => 'release',
					],
				],
			],
		];

		$release = apply_build_profile_to_config($config, 'release', true);
		$this->assertSame('.prism/build/release-custom', $release['build_dir'], 'release profile should override build_dir');
		$this->assertSame('.prism/generated/release-custom', $release['generated_dir'], 'release profile should override generated_dir');
		$this->assertSame('.prism/cache/release-custom', $release['cache_dir'], 'release profile should override cache_dir');
		$this->assertSame('release', $release['build']['mode'], 'release profile should set build mode');

		$debug = apply_build_profile_to_config([
			'build' => [
				'backend' => 'ninja',
			],
		], 'debug', true);
		$this->assertSame('.prism/build/debug', $debug['build_dir'], 'explicit debug mode without profile should get a separated build root');
		$this->assertSame('.prism/generated/debug', $debug['generated_dir'], 'explicit debug mode without profile should get a separated generated root');
		$this->assertSame('.prism/cache/debug', $debug['cache_dir'], 'explicit debug mode without profile should get a separated cache root');
		$this->assertSame('debug', $debug['build']['mode'], 'explicit debug mode should set build mode');
	}

	private function assertRuntimeLaunchEnvironment(): void
	{
		$env = scpp_runtime_library_process_environment('/tmp/scpp-runtime');
		if (PHP_OS_FAMILY === 'Windows') {
			$this->assertSame(true, isset($env['PATH']), 'Windows runtime launch should prepend PATH');
			$this->assertContains('/tmp/scpp-runtime', $env['PATH'], 'Windows PATH should include the runtime directory');
			return;
		}
		if (PHP_OS_FAMILY === 'Darwin') {
			$this->assertSame(true, isset($env['DYLD_LIBRARY_PATH']), 'macOS runtime launch should prepend DYLD_LIBRARY_PATH');
			$this->assertContains('/tmp/scpp-runtime', $env['DYLD_LIBRARY_PATH'], 'macOS DYLD_LIBRARY_PATH should include the runtime directory');
			return;
		}
		$this->assertSame(true, isset($env['LD_LIBRARY_PATH']), 'Unix runtime launch should prepend LD_LIBRARY_PATH');
		$this->assertContains('/tmp/scpp-runtime', $env['LD_LIBRARY_PATH'], 'Unix LD_LIBRARY_PATH should include the runtime directory');
	}

	private function assertRuntimeArtifactPlacementPolicy(): void
	{
		$repoRoot = $this->root . '/repo';
		$projectRoot = $this->root . '/project';
		$this->mkdir($repoRoot . '/runtime/include');
		$this->mkdir($projectRoot);

		$compiler = detect_default_compiler();
		if ($compiler === null) {
			return;
		}
		$sharedRuntimeConfig = [
			'languages' => ['php'],
			'modules' => ['json', 'filesystem', 'datetime'],
			'language_profiles' => [
				'php' => ['profile' => 'strict'],
			],
		];
		$customRuntimeConfig = [
			'languages' => ['php'],
			'modules' => ['json', 'filesystem', 'datetime', 'mysqli'],
			'language_profiles' => [
				'php' => ['profile' => 'strict'],
			],
		];

		$reuseShared = build_runtime_artifact_spec($repoRoot, $projectRoot, $compiler, 'debug', $sharedRuntimeConfig, 'reuse');
		$this->assertContains('.prism/runtime/release/php-strict/debug/', normalize_config_path($reuseShared['artifact_path']), 'default strict debug runtime should reuse the shared release path');

		$localBuild = build_runtime_artifact_spec($repoRoot, $projectRoot, $compiler, 'debug', $sharedRuntimeConfig, 'local');
		$this->assertContains('.prism/runtime/project/php-strict/', normalize_config_path($localBuild['artifact_path']), 'explicit runtime compilation should target a project-local runtime path');

		$reuseCustom = build_runtime_artifact_spec($repoRoot, $projectRoot, $compiler, 'debug', $customRuntimeConfig, 'reuse');
		$this->assertContains('.prism/runtime/release/php-strict/debug/', normalize_config_path($reuseCustom['artifact_path']), 'supported shared optional module composition should still reuse the shared release base runtime path');
		$sharedModules = resolve_shared_runtime_bundle_specs($repoRoot, $projectRoot, $compiler, 'debug', $customRuntimeConfig)['modules'];
		$this->assertSame(1, count($sharedModules), 'mysqli config should request exactly one shared optional module artifact');
		$this->assertContains('/modules/mysqli/', normalize_config_path((string) $sharedModules[0]['artifact_path']), 'mysqli shared module should publish under the shared module runtime path');
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
		$this->assertNotContains('build ../../../dep/.prism/build/dep.o: compile', $reuseNinja, 'reuse dependency mode should omit dependency compile edges');

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
		$this->assertContains('build main.o: compile ../generated/main.cpp', $fullNinja, 'root project compile edges should be relative to build_dir');
		$this->assertContains('build ../../../dep/.prism/build/dep.o: compile', $fullNinja, 'full build mode should include dependency compile edges');
		$this->assertContains('$base_ldflags $in $runtime_ldflags -o $out', $fullNinja, 'GNU-like shared runtime link should place runtime libraries after object inputs');
		$this->assertContains('$cxx $in $ldflags -o $out', $fullNinja, 'GNU-like app link should place libraries after object inputs');
		if (PHP_OS_FAMILY === 'Linux') {
			$this->assertContains('-Wl,-soname,libruntime.so', $fullNinja, 'Linux shared runtime should declare a SONAME so executables do not need a slash-containing DT_NEEDED path');
		}

		$msvcCompiler = [
			'command' => 'cl',
			'kind' => 'msvc',
			'launcher' => null,
			'linker_flags' => ['base.lib'],
		];
		$msvcNinja = render_build_ninja(
			$projectRoot,
			$repoRoot,
			$buildDir,
			$generatedDir,
			$generatedUnits,
			[],
			'app.exe',
			$msvcCompiler,
			'debug',
			$runtimeConfig,
			['project.lib'],
			null,
			['compile_runtime' => true, 'compile_dependencies' => true]
		);
		$this->assertContains('ldflags = base.lib project.lib', $msvcNinja, 'MSVC Ninja rendering should preserve configured linker flags');
		$this->assertContains('$cxx /nologo $in $ldflags /Fe$out', $msvcNinja, 'MSVC link rule should pass ldflags to the compiler driver');
	}

	private function assertClangTimeTraceCanBeEnabled(): void
	{
		$projectRoot = $this->root . '/trace_project';
		$repoRoot = $this->root . '/trace_repo';
		$buildDir = $projectRoot . '/.prism/build';
		$generatedDir = $projectRoot . '/.prism/generated';
		$this->mkdir($buildDir);
		$this->mkdir($generatedDir);
		$this->mkdir($repoRoot . '/runtime/include');

		$generatedUnits = [[
			'project_root' => $projectRoot,
			'relative_php' => 'main.phs',
			'generated_cpp' => $generatedDir . '/main.cpp',
			'object_path' => $buildDir . '/main.o',
			'is_entrypoint' => true,
			'force_include_header' => null,
		]];
		$compiler = [
			'command' => 'clang++',
			'kind' => 'gnu_like',
			'launcher' => null,
			'linker_flags' => [],
		];
		$runtimeConfig = [
			'languages' => ['php'],
			'modules' => ['json'],
			'language_profiles' => [
				'php' => ['profile' => 'strict'],
			],
		];

		$previousTrace = getenv('SCPP_CLANG_TIME_TRACE');
		$previousGranularity = getenv('SCPP_CLANG_TIME_TRACE_GRANULARITY_US');
		putenv('SCPP_CLANG_TIME_TRACE=1');
		putenv('SCPP_CLANG_TIME_TRACE_GRANULARITY_US=250');
		try {
			$ninja = render_build_ninja(
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
		} finally {
			if ($previousTrace === false) {
				putenv('SCPP_CLANG_TIME_TRACE');
			} else {
				putenv('SCPP_CLANG_TIME_TRACE=' . $previousTrace);
			}
			if ($previousGranularity === false) {
				putenv('SCPP_CLANG_TIME_TRACE_GRANULARITY_US');
			} else {
				putenv('SCPP_CLANG_TIME_TRACE_GRANULARITY_US=' . $previousGranularity);
			}
		}

		$this->assertContains('-ftime-trace', $ninja, 'clang time tracing should append -ftime-trace when enabled');
		$this->assertContains('-ftime-trace-granularity=250', $ninja, 'clang time tracing should honor granularity threshold');
		$this->assertContains('runtime_cxxflags = ', $ninja, 'rendered Ninja should still contain runtime flags');
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
		$this->write($projectRoot . '/main.phs', "echo \"main\\n\";\n");
		$this->write($projectRoot . '/alt.phs', "echo \"alt\\n\";\n");
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

	private function assertCrossFileEnumTypesLowerThroughDeclarationCatalog(): void
	{
		if (find_command_path(['ninja']) === null) {
			return;
		}
		if (resolve_compiler(['build' => []]) === null) {
			return;
		}

		$projectRoot = $this->root . '/cross_file_enum_project';
		$this->mkdir($projectRoot . '/native_cpp');
		$this->write($projectRoot . '/kind.phs', implode("\n", [
			'enum Kind {',
			'	case One;',
			'}',
			'',
		]));
		$this->write($projectRoot . '/main.phs', implode("\n", [
			'class Row {',
			'	public Kind $kind = Kind::One;',
			'}',
			'$row = new Row();',
			'echo "ok\n";',
			'',
		]));
		$config = [
			'config_version' => 1,
			'project_name' => 'cross_file_enum_project',
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
			throw new RuntimeException('Failed to encode cross-file enum prism.json');
		}
		$this->write($projectRoot . '/prism.json', $json . PHP_EOL);

		scpp_run_build_service($projectRoot, $projectRoot . '/prism.json', [
			'disable_stan' => true,
		]);

		$mainHeader = file_get_contents($projectRoot . '/.prism/generated/main.hpp');
		if (!is_string($mainHeader)) {
			throw new RuntimeException('Expected generated main.hpp for cross-file enum project');
		}
		$this->assertContains('Kind kind = Kind::One;', $mainHeader, 'cross-file enum property should lower to raw enum storage');
		$this->assertNotContains('shared_p<Kind> kind', $mainHeader, 'cross-file enum property should not lower as shared object handle');
		$this->assertNotContains('class Kind;', $mainHeader, 'cross-file enum property should not emit a bogus class forward declaration');
	}

	private function assertCrossFileStructTypesLowerThroughDeclarationCatalog(): void
	{
		if (find_command_path(['ninja']) === null) {
			return;
		}
		if (resolve_compiler(['build' => []]) === null) {
			return;
		}

		$projectRoot = $this->root . '/cross_file_struct_project';
		$this->mkdir($projectRoot . '/native_cpp');
		$this->write($projectRoot . '/span.phs', implode("\n", [
			'struct CompactChildSpan {',
			'	uint32 $first_child_index = 0;',
			'	uint16 $child_count = 0;',
			'}',
			'',
		]));
		$this->write($projectRoot . '/main.phs', implode("\n", [
			'class Row {',
			'	public CompactChildSpan $children;',
			'}',
			'$row = new Row();',
			'echo "ok\n";',
			'',
		]));
		$config = [
			'config_version' => 1,
			'project_name' => 'cross_file_struct_project',
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
			throw new RuntimeException('Failed to encode cross-file struct prism.json');
		}
		$this->write($projectRoot . '/prism.json', $json . PHP_EOL);

		scpp_run_build_service($projectRoot, $projectRoot . '/prism.json', [
			'disable_stan' => true,
		]);

		$spanHeader = file_get_contents($projectRoot . '/.prism/generated/span.hpp');
		if (!is_string($spanHeader)) {
			throw new RuntimeException('Expected generated span.hpp for cross-file struct project');
		}
		$this->assertContains('struct CompactChildSpan {', $spanHeader, 'struct source should lower to a C++ struct');
		$this->assertContains('int_t<std::uint32_t> first_child_index', $spanHeader, 'uint32 struct fields should keep exact generated storage width');
		$this->assertContains('int_t<std::uint16_t> child_count', $spanHeader, 'uint16 struct fields should keep exact generated storage width');

		$mainHeader = file_get_contents($projectRoot . '/.prism/generated/main.hpp');
		if (!is_string($mainHeader)) {
			throw new RuntimeException('Expected generated main.hpp for cross-file struct project');
		}
		$this->assertContains('CompactChildSpan children;', $mainHeader, 'cross-file struct property should lower to raw value storage');
		$this->assertNotContains('shared_p<CompactChildSpan> children', $mainHeader, 'cross-file struct property should not lower as shared object handle');
		$this->assertNotContains('class CompactChildSpan;', $mainHeader, 'cross-file struct property should not emit a bogus class forward declaration');
	}

	private function assertStructContainersLowerThroughDeclarationCatalog(): void
	{
		if (find_command_path(['ninja']) === null || resolve_compiler(['build' => []]) === null) {
			return;
		}
		$projectRoot = $this->root . '/struct_container_project';
		$this->mkdir($projectRoot . '/native_cpp');
		$this->write($projectRoot . '/span.phs', implode("\n", [
			'struct CompactChildSpan {',
			'	uint32 $first_child_index = 0;',
			'}',
			'',
		]));
		$this->write($projectRoot . '/main.phs', implode("\n", [
			'class RowStore {',
			'	public $spans vector_t<CompactChildSpan>;',
			'	public $by_name hash_t<CompactChildSpan>;',
			'	public $first_two fixed_array_t<CompactChildSpan, 2>;',
			'}',
			'$store = new RowStore();',
			'echo "ok\n";',
			'',
		]));
		$this->writeProjectConfig($projectRoot, 'struct_container_project', 'main.phs');

		scpp_run_build_service($projectRoot, $projectRoot . '/prism.json', ['disable_stan' => true]);

		$mainHeader = file_get_contents($projectRoot . '/.prism/generated/main.hpp');
		if (!is_string($mainHeader)) {
			throw new RuntimeException('Expected generated main.hpp for struct container project');
		}
		$this->assertContains('vector_t<CompactChildSpan> spans;', $mainHeader, 'vector_t<StructName> should lower with raw struct element type');
		$this->assertContains('hash_t<CompactChildSpan> by_name;', $mainHeader, 'hash_t<StructName> should lower with raw struct value type');
		$this->assertContains('fixed_array_t<CompactChildSpan, 2> first_two;', $mainHeader, 'fixed_array_t<StructName, N> should lower with raw struct element type');
		$this->assertNotContains('shared_p<CompactChildSpan>', $mainHeader, 'struct containers should not wrap elements as shared object handles');
	}

	private function assertStructPointerStyleFieldAccessCompiles(): void
	{
		if (find_command_path(['ninja']) === null || resolve_compiler(['build' => []]) === null) {
			return;
		}
		$projectRoot = $this->root . '/struct_pointer_access_project';
		$this->mkdir($projectRoot . '/native_cpp');
		$this->write($projectRoot . '/main.phs', implode("\n", [
			'struct Row {',
			'	public uint32 $x = 0;',
			'}',
			'function make_row(uint32 $value): Row {',
			'	$row Row;',
			'	$row->x = $value;',
			'	return $row;',
			'}',
			'$row Row = make_row(7);',
			'echo layout_sizeof(Row), "\n";',
			'',
		]));
		$this->writeProjectConfig($projectRoot, 'struct_pointer_access_project', 'main.phs');

		$build = scpp_run_build_service($projectRoot, $projectRoot . '/prism.json', [
			'compile_runtime' => true,
			'disable_stan' => true,
		]);
		$this->assertSame(true, $build['ok'], "value struct pointer-style field access should compile\n" . (string) ($build['output'] ?? '') . "\n" . (string) ($build['error'] ?? ''));

		$mainHeader = file_get_contents($projectRoot . '/.prism/generated/main.hpp');
		if (!is_string($mainHeader)) {
			throw new RuntimeException('Expected generated main.hpp for struct pointer access project');
		}
		$this->assertContains('Row* operator->() { return this; }', $mainHeader, 'value structs should expose pointer-style mutable field access');
		$this->assertContains('const Row* operator->() const { return this; }', $mainHeader, 'value structs should expose pointer-style const field access');
	}

	private function assertStructKeyedInitializerCompiles(): void
	{
		if (find_command_path(['ninja']) === null || resolve_compiler(['build' => []]) === null) {
			return;
		}
		$projectRoot = $this->root . '/struct_keyed_initializer_project';
		$this->mkdir($projectRoot . '/native_cpp');
		$this->write($projectRoot . '/main.phs', implode("\n", [
			'struct Point {',
			'	public uint32 $x = 0;',
			'	public uint32 $y = 0;',
			'}',
			'struct Row {',
			'	public uint32 $id = 0;',
			'	public Point $pos;',
			'}',
			'$row Row = ["pos" => ["y" => 20, "x" => 10], "id" => 7];',
			'$empty Row = [];',
			'echo layout_sizeof(Row), "\n";',
			'',
		]));
		$this->writeProjectConfig($projectRoot, 'struct_keyed_initializer_project', 'main.phs');

		$build = scpp_run_build_service($projectRoot, $projectRoot . '/prism.json', [
			'compile_runtime' => true,
			'disable_stan' => true,
		]);
		$this->assertSame(true, $build['ok'], "value struct keyed initializer should compile\n" . (string) ($build['output'] ?? '') . "\n" . (string) ($build['error'] ?? ''));

		$mainCpp = file_get_contents($projectRoot . '/.prism/generated/main.cpp');
		if (!is_string($mainCpp)) {
			throw new RuntimeException('Expected generated main.cpp for struct keyed initializer project');
		}
		$this->assertContains('Row row = Row{.id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(7)), .pos = Point{.x = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(10)), .y = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(20))}};', $mainCpp, 'struct initializer should emit C++ designated fields in declaration order');
		$this->assertContains('Row empty = Row{};', $mainCpp, 'empty struct initializer should emit value initialization');
	}

	private function assertStructValidationRejectsClassLikeFeatures(): void
	{
		if (find_command_path(['ninja']) === null || resolve_compiler(['build' => []]) === null) {
			return;
		}
		$projectRoot = $this->root . '/invalid_struct_project';
		$this->mkdir($projectRoot . '/native_cpp');
		$this->write($projectRoot . '/main.phs', implode("\n", [
			'class Box {',
			'	public uint32 $value = 0;',
			'}',
			'struct BadRow {',
			'	private uint16 $hidden = 0;',
			'	public static uint16 $counter = 0;',
			'	public Box $box;',
			'	public function nope(): void {',
			'		return;',
			'	}',
			'}',
			'echo "ok\n";',
			'',
		]));
		$this->writeProjectConfig($projectRoot, 'invalid_struct_project', 'main.phs');

		$build = scpp_run_build_service($projectRoot, $projectRoot . '/prism.json', ['disable_stan' => true]);
		$this->assertSame(false, $build['ok'], 'invalid struct build should fail during generation');
		$diagnostics = (string) ($build['output'] ?? '') . "\n" . (string) ($build['error'] ?? '');
		$this->assertContains('Struct field BadRow::$hidden must be public', $diagnostics, 'private struct fields should be rejected');
		$this->assertContains('Struct field BadRow::$counter cannot be static', $diagnostics, 'static struct fields should be rejected');
		$this->assertContains('unsupported first-slice field type Box', $diagnostics, 'class object fields should be rejected in structs');
		$this->assertContains('Struct BadRow cannot declare methods', $diagnostics, 'struct methods should be rejected in the first slice');
	}

	private function assertFixedWidthEnumBackingLowersExactly(): void
	{
		if (find_command_path(['ninja']) === null || resolve_compiler(['build' => []]) === null) {
			return;
		}
		$projectRoot = $this->root . '/fixed_enum_project';
		$this->mkdir($projectRoot . '/native_cpp');
		$this->write($projectRoot . '/main.phs', implode("\n", [
			'enum ExpressionKind : uint16 {',
			'	case Error = 0;',
			'	case Variable = 1;',
			'	case IntLiteral = 2;',
			'}',
			'class Row {',
			'	public ExpressionKind $kind = ExpressionKind::Error;',
			'}',
			'$row = new Row();',
			'echo "ok\n";',
			'',
		]));
		$this->writeProjectConfig($projectRoot, 'fixed_enum_project', 'main.phs');

		scpp_run_build_service($projectRoot, $projectRoot . '/prism.json', ['disable_stan' => true]);
		$mainHeader = file_get_contents($projectRoot . '/.prism/generated/main.hpp');
		if (!is_string($mainHeader)) {
			throw new RuntimeException('Expected generated main.hpp for fixed enum project');
		}
		$this->assertContains('enum class ExpressionKind : std::uint16_t {', $mainHeader, 'uint16 enum backing should lower to exact C++ storage');
		$this->assertContains('ExpressionKind kind = ExpressionKind::Error;', $mainHeader, 'fixed-backed enum properties should lower as raw enum storage');
	}

	private function assertLayoutProbesLowerToCppOperators(): void
	{
		if (find_command_path(['ninja']) === null || resolve_compiler(['build' => []]) === null) {
			return;
		}
		$projectRoot = $this->root . '/layout_probe_project';
		$this->mkdir($projectRoot . '/native_cpp');
		$this->write($projectRoot . '/main.phs', implode("\n", [
			'struct CompactChildSpan {',
			'	uint32 $first_child_index = 0;',
			'	uint16 $child_count = 0;',
			'}',
			'$span_size int = layout_sizeof(CompactChildSpan);',
			'$span_align int = layout_alignof(CompactChildSpan);',
			'$count_offset int = layout_offsetof(CompactChildSpan, child_count);',
			'$count_size int = layout_field_sizeof(CompactChildSpan, child_count);',
			'echo $span_size, " ", $span_align, " ", $count_offset, " ", $count_size, "\n";',
			'',
		]));
		$this->writeProjectConfig($projectRoot, 'layout_probe_project', 'main.phs');

		scpp_run_build_service($projectRoot, $projectRoot . '/prism.json', ['disable_stan' => true]);

		$mainCpp = file_get_contents($projectRoot . '/.prism/generated/main.cpp');
		if (!is_string($mainCpp)) {
			throw new RuntimeException('Expected generated main.cpp for layout probe project');
		}
		$this->assertContains('sizeof(CompactChildSpan)', $mainCpp, 'layout_sizeof should lower to C++ sizeof');
		$this->assertContains('alignof(CompactChildSpan)', $mainCpp, 'layout_alignof should lower to C++ alignof');
		$this->assertContains('offsetof(CompactChildSpan, child_count)', $mainCpp, 'layout_offsetof should lower to C++ offsetof');
		$this->assertContains('sizeof(std::declval<CompactChildSpan>().child_count)', $mainCpp, 'layout_field_sizeof should lower to C++ field sizeof');
	}

	private function assertUnionPayloadsLowerAndProbeLayout(): void
	{
		if (find_command_path(['ninja']) === null || resolve_compiler(['build' => []]) === null) {
			return;
		}
		$projectRoot = $this->root . '/union_payload_project';
		$this->mkdir($projectRoot . '/native_cpp');
		$this->write($projectRoot . '/payload.phs', implode("\n", [
			'struct PairPayload {',
			'	uint16 $left = 0;',
			'	uint16 $right = 0;',
			'}',
			'union ExpressionPayload {',
			'	uint32 $int_value;',
			'	PairPayload $pair;',
			'}',
			'',
		]));
		$this->write($projectRoot . '/main.phs', implode("\n", [
			'struct Row {',
			'	ExpressionPayload $payload;',
			'}',
			'$payload_size int = layout_sizeof(ExpressionPayload);',
			'$payload_align int = layout_alignof(ExpressionPayload);',
			'echo $payload_size, " ", $payload_align, "\n";',
			'',
		]));
		$this->writeProjectConfig($projectRoot, 'union_payload_project', 'main.phs');

		scpp_run_build_service($projectRoot, $projectRoot . '/prism.json', ['disable_stan' => true]);

		$payloadHeader = file_get_contents($projectRoot . '/.prism/generated/payload.hpp');
		if (!is_string($payloadHeader)) {
			throw new RuntimeException('Expected generated payload.hpp for union payload project');
		}
		$this->assertContains('union ExpressionPayload {', $payloadHeader, 'union source should lower to a C++ union');
		$this->assertContains('int_t<std::uint32_t> int_value;', $payloadHeader, 'union fixed-width fields should lower as payload fields');
		$this->assertContains('PairPayload pair;', $payloadHeader, 'union struct fields should lower as inline payload fields');

		$mainHeader = file_get_contents($projectRoot . '/.prism/generated/main.hpp');
		if (!is_string($mainHeader)) {
			throw new RuntimeException('Expected generated main.hpp for union payload project');
		}
		$this->assertContains('ExpressionPayload payload;', $mainHeader, 'struct fields may store union payloads inline');

		$mainCpp = file_get_contents($projectRoot . '/.prism/generated/main.cpp');
		if (!is_string($mainCpp)) {
			throw new RuntimeException('Expected generated main.cpp for union payload project');
		}
		$this->assertContains('sizeof(ExpressionPayload)', $mainCpp, 'layout_sizeof should support unions');
		$this->assertContains('alignof(ExpressionPayload)', $mainCpp, 'layout_alignof should support unions');

		$projectUnits = file_get_contents($projectRoot . '/.prism/generated/__project_units.hpp');
		if (!is_string($projectUnits)) {
			throw new RuntimeException('Expected generated __project_units.hpp for union payload project');
		}
		$this->assertContains('#include "payload.hpp"' . "\n" . '#include "main.hpp"', $projectUnits, 'union dependency headers should be included before users');
	}

	private function assertUnionPointerStylePayloadAccessCompiles(): void
	{
		if (find_command_path(['ninja']) === null || resolve_compiler(['build' => []]) === null) {
			return;
		}
		$projectRoot = $this->root . '/union_pointer_access_project';
		$this->mkdir($projectRoot . '/native_cpp');
		$this->write($projectRoot . '/main.phs', implode("\n", [
			'struct AccessPayload {',
			'	public uint32 $subject_id = 0;',
			'	public uint32 $member_id = 0;',
			'}',
			'union ExpressionPayload {',
			'	public uint32 $name_id;',
			'	public AccessPayload $access;',
			'}',
			'struct Row {',
			'	public ExpressionPayload $payload;',
			'}',
			'$row Row = [];',
			'$row->payload->name_id = 7;',
			'$row->payload->access->subject_id = 11;',
			'$row->payload->access->member_id = $row->payload->name_id;',
			'echo layout_sizeof(ExpressionPayload), "\n";',
			'',
		]));
		$this->writeProjectConfig($projectRoot, 'union_pointer_access_project', 'main.phs');

		$build = scpp_run_build_service($projectRoot, $projectRoot . '/prism.json', [
			'compile_runtime' => true,
			'disable_stan' => true,
		]);
		$this->assertSame(true, $build['ok'], "value union pointer-style payload access should compile\n" . (string) ($build['output'] ?? '') . "\n" . (string) ($build['error'] ?? ''));

		$mainHeader = file_get_contents($projectRoot . '/.prism/generated/main.hpp');
		if (!is_string($mainHeader)) {
			throw new RuntimeException('Expected generated main.hpp for union pointer access project');
		}
		$this->assertContains('ExpressionPayload* operator->() { return this; }', $mainHeader, 'value unions should expose pointer-style mutable payload access');
		$this->assertContains('const ExpressionPayload* operator->() const { return this; }', $mainHeader, 'value unions should expose pointer-style const payload access');
	}

	private function assertUnionValidationRejectsClassLikeFeatures(): void
	{
		if (find_command_path(['ninja']) === null || resolve_compiler(['build' => []]) === null) {
			return;
		}
		$projectRoot = $this->root . '/invalid_union_project';
		$this->mkdir($projectRoot . '/native_cpp');
		$this->write($projectRoot . '/main.phs', implode("\n", [
			'class Box {',
			'	public uint32 $value = 0;',
			'}',
			'struct TrivialLeaf {',
			'	public uint16 $value = 0;',
			'}',
			'struct NonTrivialPayload {',
			'	public $items vector_t<TrivialLeaf>;',
			'}',
			'union BadPayload {',
			'	private uint16 $hidden;',
			'	public static uint16 $counter;',
			'	public uint16 $defaulted = 0;',
			'	public Box $box;',
			'	public NonTrivialPayload $non_trivial;',
			'	public function nope(): void {',
			'		return;',
			'	}',
			'}',
			'echo "bad\n";',
			'',
		]));
		$this->writeProjectConfig($projectRoot, 'invalid_union_project', 'main.phs');

		$build = scpp_run_build_service($projectRoot, $projectRoot . '/prism.json', ['disable_stan' => true]);
		$this->assertSame(false, $build['ok'], 'invalid union build should fail during generation');
		$diagnostics = (string) ($build['output'] ?? '') . "\n" . (string) ($build['error'] ?? '');
		$this->assertContains('Union field BadPayload::$hidden must be public', $diagnostics, 'private union fields should be rejected');
		$this->assertContains('Union field BadPayload::$counter cannot be static', $diagnostics, 'static union fields should be rejected');
		$this->assertContains('Union field BadPayload::$defaulted cannot declare a default initializer', $diagnostics, 'union defaults should be rejected');
		$this->assertContains('unsupported first-slice payload type Box', $diagnostics, 'class object fields should be rejected in unions');
		$this->assertContains('unsupported first-slice payload type NonTrivialPayload', $diagnostics, 'structs with non-trivial fields should be rejected in unions');
		$this->assertContains('Union BadPayload cannot declare methods', $diagnostics, 'union methods should be rejected in the first slice');
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
			@unlink($child);
		}
		@rmdir($path);
	}
}

exit((new ScppBuildOptionsTest())->run());
