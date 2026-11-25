<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppMysqliStrictRuntimeLinkTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_mysqli_strict_' . getmypid() . '_' . bin2hex(random_bytes(4)));
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
		$mysqliBuild = resolve_runtime_mysqli_build_spec();
		if (!(bool) ($mysqliBuild['enabled'] ?? false)) {
			echo "SKIP: mysqli runtime module environment not available\n";
			return 0;
		}

		try {
			$project = $this->root . '/strict_mysqli_project';
			$this->writeProject($project);

			$build = scpp_run_build_service($project, $project . '/prism.json', [
				'compile_runtime' => true,
				'compile_dependencies' => false,
			]);
			$this->assertSame(true, $build['ok'], 'strict mysqli project should build and link successfully');
			$this->assertContains('Runtime compilation: enabled', $build['output'], 'strict mysqli project should rebuild the shared runtime when requested');

			$config = load_project_config($project . '/prism.json');
			$repoRoot = resolve_repo_root();
			$compiler = resolve_compiler($config);
			if ($compiler === null) {
				throw new RuntimeException('Compiler not available');
			}
			$runtimeConfig = is_array($config['runtime'] ?? null) ? $config['runtime'] : resolve_runtime_build_config($config);
			$runtimeBuild = build_runtime_artifact_spec($repoRoot, $project, $compiler, resolve_build_mode($config), $runtimeConfig);
			$compositionSource = normalize_path($project . '/' . normalize_config_path($runtimeBuild['source_path']));
			$this->assertFileExists($compositionSource, 'runtime composition source should exist for strict mysqli build');
			$this->assertContains('#include "lang/php/php_mysqli.cpp"', $this->read($compositionSource), 'strict mysqli runtime composition should include the php mysqli wrapper implementation');

			echo "PASS: scpp mysqli strict runtime link\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function writeProject(string $projectRoot): void
	{
		$this->mkdir($projectRoot . '/native_cpp');
		$this->write($projectRoot . '/prism.json', <<<'JSON'
{
  "config_version": 1,
  "project_name": "strict_mysqli_project",
  "entrypoint": "main.phs",
  "build_dir": ".prism/build",
  "generated_dir": ".prism/generated",
  "cache_dir": ".prism/cache",
  "native_cpp_dir": "native_cpp",
  "dependencies": [],
  "libraries": [],
  "build": {
    "backend": "ninja",
    "mode": "debug",
    "cxx": null
  },
  "fastcgi": {
    "enabled": false,
    "workers": 1,
    "max_body_size": 4194304,
    "max_requests": 0
  },
  "runtime": {
    "languages": {
      "php": {
        "profile": "strict"
      }
    },
    "modules": ["mysqli"]
  }
}
JSON);
		$this->write($projectRoot . '/main.phs', <<<'PHS'
class demo {
	public function run(): void {
		$conn = new \scpp\mysqli(
			"127.0.0.1",
			"user",
			"pass",
			"db",
			3306,
			""
		);

		$conn->set_charset("utf8mb4");
		$res = $conn->query("SHOW TABLE STATUS");
		if ($res !== false && $res !== true) {
			$row mixed = null;
			while (true) {
				$row = $res->fetch_assoc();
				if ($row === null) {
					break;
				}
			}
		}
		$conn->close();
	}
}

$worker = new demo();
$worker->run();
PHS);
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

	private function assertFileExists(string $path, string $message): void
	{
		if (!is_file($path)) {
			throw new RuntimeException($message . ': ' . $path);
		}
	}
}

exit((new ScppMysqliStrictRuntimeLinkTest())->run());
