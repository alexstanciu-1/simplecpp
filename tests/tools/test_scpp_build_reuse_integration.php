<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppBuildReuseIntegrationTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_build_reuse_' . getmypid() . '_' . bin2hex(random_bytes(4)));
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
			$app = $this->root . '/app';
			$lib = $this->root . '/lib';
			$this->writeProject($lib, [], "<?php\nfunction helper_value(): int { return 7; }\n");
			$this->writeProject($app, ['../lib'], "<?php\necho \"app\\n\";\n");

			$full = scpp_run_build_service($app, $app . '/prism.json', [
				'compile_runtime' => true,
				'compile_dependencies' => true,
			]);
			$this->assertSame(true, $full['ok'], 'initial full build should succeed');
			$this->assertContains('Runtime compilation: enabled', $full['output'], 'full build should report runtime compilation');
			$this->assertContains('Dependency compilation: enabled', $full['output'], 'full build should report dependency compilation');

			$depObject = $this->findDependencyObject($lib);
			$runtimeArtifact = $this->resolveRuntimeArtifactPath($app);
			$this->assertFileExists($depObject, 'dependency object should exist after full build');
			$this->assertFileExists($runtimeArtifact, 'runtime artifact should exist after full build');
			$depBeforeReuse = $this->mtime($depObject);
			$runtimeBeforeReuse = $this->mtime($runtimeArtifact);

			$this->sleepForTimestamp();
			$this->write($app . '/main.phs', "<?php\necho \"app reuse\\n\";\n");
			$reuse = scpp_run_build_service($app, $app . '/prism.json');
			$this->assertSame(true, $reuse['ok'], 'warm service build should succeed');
			$this->assertContains('Runtime compilation: reuse existing artifact only', $reuse['output'], 'service build should reuse runtime by default');
			$this->assertContains('Dependency compilation: reuse existing artifacts only', $reuse['output'], 'service build should reuse dependencies by default');
			$this->assertSame($depBeforeReuse, $this->mtime($depObject), 'dependency object should not rebuild in default reuse mode');
			$this->assertSame($runtimeBeforeReuse, $this->mtime($runtimeArtifact), 'runtime artifact should not rebuild in default reuse mode');

			$this->sleepForTimestamp();
			$this->write($lib . '/main.phs', "<?php\nfunction helper_value(): int { return 8; }\n");
			$depReuse = scpp_run_build_service($app, $app . '/prism.json');
			$this->assertSame(true, $depReuse['ok'], 'service build with changed dependency source should still succeed in reuse mode');
			$this->assertSame($depBeforeReuse, $this->mtime($depObject), 'dependency object should remain untouched until dependency compilation is requested');

			$this->sleepForTimestamp();
			$depFull = scpp_run_build_service($app, $app . '/prism.json', parse_build_command_arguments(['--build-dependencies']));
			$this->assertSame(true, $depFull['ok'], 'full build should rebuild dependency artifacts');
			$this->assertContains('Dependency compilation: enabled', $depFull['output'], 'full build should re-enable dependency compilation');
			$depAfterFull = $this->mtime($depObject);
			$this->assertTrue($depAfterFull > $depBeforeReuse, 'dependency object should rebuild when full dependency compilation is requested');

			$this->sleepForTimestamp();
			unlink($runtimeArtifact);
			$runtimeFull = scpp_run_build_service($app, $app . '/prism.json', [
				'compile_runtime' => true,
				'compile_dependencies' => false,
			]);
			$this->assertSame(true, $runtimeFull['ok'], 'runtime-only rebuild should succeed');
			$this->assertFileExists($runtimeArtifact, 'runtime artifact should be recreated when runtime compilation is requested');
			$this->assertTrue($this->mtime($runtimeArtifact) > $runtimeBeforeReuse, 'runtime artifact should rebuild when explicitly requested');

			echo "PASS: scpp build reuse integration\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function resolveRuntimeArtifactPath(string $projectRoot): string
	{
		$config = load_project_config($projectRoot . '/prism.json');
		$repoRoot = resolve_repo_root();
		$compiler = resolve_compiler($config);
		if ($compiler === null) {
			throw new RuntimeException('Compiler not available');
		}
		$runtimeConfig = is_array($config['runtime'] ?? null) ? $config['runtime'] : resolve_runtime_build_config($config);
		$runtimeBuild = build_runtime_artifact_spec($repoRoot, $projectRoot, $compiler, resolve_build_mode($config), $runtimeConfig);
		return normalize_path($projectRoot . '/' . normalize_config_path($runtimeBuild['artifact_path']));
	}

	private function findDependencyObject(string $projectRoot): string
	{
		$buildDir = $projectRoot . '/.prism/build';
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($buildDir, FilesystemIterator::SKIP_DOTS)
		);
		foreach ($iterator as $item) {
			if (!$item->isFile()) {
				continue;
			}
			$path = normalize_path($item->getPathname());
			if (str_ends_with($path, '.o') && !str_contains($path, 'pch')) {
				return $path;
			}
		}
		throw new RuntimeException('Failed to locate dependency object in ' . $buildDir);
	}

	private function sleepForTimestamp(): void
	{
		usleep(1200000);
		clearstatcache();
	}

	/** @param list<string> $dependencies */
	private function writeProject(string $path, array $dependencies, string $source): void
	{
		$this->mkdir($path);
		$this->mkdir($path . '/native_cpp');
		$this->write($path . '/main.phs', $source);
		$config = [
			'config_version' => 1,
			'project_name' => basename($path),
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'generated_dir' => '.prism/generated',
			'cache_dir' => '.prism/cache',
			'native_cpp_dir' => 'native_cpp',
			'dependencies' => $dependencies,
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
			throw new RuntimeException('Failed to encode prism.json');
		}
		$this->write($path . '/prism.json', $json . PHP_EOL);
	}

	private function mtime(string $path): int
	{
		clearstatcache(true, $path);
		$mtime = filemtime($path);
		if (!is_int($mtime)) {
			throw new RuntimeException('Failed to read mtime for ' . $path);
		}
		return $mtime;
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

	private function assertTrue(bool $condition, string $message): void
	{
		if (!$condition) {
			throw new RuntimeException($message);
		}
	}

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (!str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' missing `' . $needle . '`');
		}
	}

	private function assertFileExists(string $path, string $message): void
	{
		if (!is_file($path)) {
			throw new RuntimeException($message . ': ' . $path);
		}
	}
}

exit((new ScppBuildReuseIntegrationTest())->run());
