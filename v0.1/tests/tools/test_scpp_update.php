<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppUpdateTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_update_test_' . getmypid() . '_' . bin2hex(random_bytes(4)));
	}

	public function run(): int
	{
		if (find_command_path(['git']) === null) {
			echo "SKIP: git not found\n";
			return 0;
		}

		try {
			$this->mkdir($this->root);
			$remote = $this->root . '/remote.git';
			$seed = $this->root . '/seed';
			$checkout = $this->root . '/checkout';

			$sourceRepo = resolve_repo_root();
			$this->git($this->root, ['clone', '--bare', $sourceRepo, $remote]);
			$this->pointBareRemoteMainAtCurrentHead($sourceRepo, $remote);
			$this->git($this->root, ['clone', '-b', 'main', $remote, $seed]);
			$this->configureUser($seed);
			$this->git($this->root, ['clone', '-b', 'main', $remote, $checkout]);
			$this->configureUser($checkout);

			$this->write($seed . '/CHANGELOG.md', $this->withMarker($seed . '/CHANGELOG.md', 'test-update-change'));
			$this->git($seed, ['add', 'CHANGELOG.md']);
			$this->git($seed, ['commit', '-m', 'second']);
			$this->git($seed, ['push', 'origin', 'main']);

			$this->write($checkout . '/local.txt', "dirty\n");
			$dirty = scpp_run_update_service($checkout);
			$this->assertSame(false, $dirty['ok'], 'dirty checkout update should fail');
			$this->assertContains('local changes', $dirty['error'], 'dirty checkout failure should explain local changes');
			unlink($checkout . '/local.txt');

			$before = $this->gitLine($checkout, ['rev-parse', '--short', 'HEAD']);
			$result = scpp_run_update_service($checkout);
			$this->assertSame(true, $result['ok'], 'clean checkout update should succeed');
			$this->assertContains('Updated scpp:', $result['output'], 'successful update should report revision change');
			$after = $this->gitLine($checkout, ['rev-parse', '--short', 'HEAD']);
			$remoteHead = $this->gitLine($checkout, ['rev-parse', '--short', 'origin/main']);
			$this->assertNotSame($before, $after, 'update should advance HEAD');
			$this->assertSame($remoteHead, $after, 'update should fast-forward to origin/main');
			$this->assertSharedRuntimeMatrixPrepared($checkout);
			$alreadyCurrent = scpp_run_update_service($checkout);
			$this->assertSame(true, $alreadyCurrent['ok'], 'already-current update should still succeed');
			$this->assertContains('Already up to date.', $alreadyCurrent['output'], 'already-current update should report current revision');

			echo "PASS: scpp update\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function configureUser(string $cwd): void
	{
		$this->git($cwd, ['config', 'user.email', 'scpp-update-test@example.invalid']);
		$this->git($cwd, ['config', 'user.name', 'scpp update test']);
	}

	private function pointBareRemoteMainAtCurrentHead(string $sourceRepo, string $remote): void
	{
		$currentHead = $this->gitLine($sourceRepo, ['rev-parse', 'HEAD']);
		$this->git($remote, ['update-ref', 'refs/heads/main', $currentHead]);
	}

	/** @param list<string> $args */
	private function gitLine(string $cwd, array $args): string
	{
		$result = $this->git($cwd, $args);
		return trim($result['stdout']);
	}

	/** @param list<string> $args @return array{exit_code:int,stdout:string,stderr:string} */
	private function git(string $cwd, array $args): array
	{
		$git = find_command_path(['git']);
		if ($git === null) {
			throw new RuntimeException('git not found');
		}
		$command = array_merge([$git], $args);
		$descriptor = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$process = proc_open($command, $descriptor, $pipes, $cwd, scpp_build_process_environment());
		if (!is_resource($process)) {
			throw new RuntimeException('Failed to start git.');
		}
		fclose($pipes[0]);
		$stdout = stream_get_contents($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$status = proc_close($process);
		$result = [
			'exit_code' => is_int($status) ? $status : 1,
			'stdout' => is_string($stdout) ? $stdout : '',
			'stderr' => is_string($stderr) ? $stderr : '',
		];
		if ($result['exit_code'] !== 0) {
			throw new RuntimeException('git ' . implode(' ', $args) . " failed\n" . $result['stderr'] . $result['stdout']);
		}
		return $result;
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

	private function withMarker(string $path, string $marker): string
	{
		$current = file_get_contents($path);
		if (!is_string($current)) {
			throw new RuntimeException('Failed to read ' . $path);
		}
		return $current . "\n<!-- " . $marker . " -->\n";
	}

	private function mkdir(string $path): void
	{
		if (!is_dir($path) && !mkdir($path, 0777, true)) {
			throw new RuntimeException('Failed to create ' . $path);
		}
	}

	private function assertSharedRuntimeMatrixPrepared(string $checkout): void
	{
		$families = ['php-legacy', 'php-strict'];
		$modes = ['debug', 'release'];
		$modules = ['mysqli', 'regex', 'curl', 'tasks'];
		foreach ($families as $family) {
			foreach ($modes as $mode) {
				$dir = $checkout . '/.prism/runtime/release/' . $family . '/' . $mode;
				if (!is_dir($dir)) {
					throw new RuntimeException('Expected shared runtime directory missing after update: ' . $dir);
				}
				foreach ($modules as $moduleName) {
					$moduleDir = $dir . '/modules/' . $moduleName;
					if (!is_dir($moduleDir)) {
						throw new RuntimeException('Expected shared runtime module directory missing after update: ' . $moduleDir);
					}
				}
			}
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

	private function assertNotSame(mixed $unexpected, mixed $actual, string $message): void
	{
		if ($unexpected === $actual) {
			throw new RuntimeException($message . ' unexpected value ' . var_export($actual, true));
		}
	}

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (!str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' missing `' . $needle . '` in `' . $haystack . '`');
		}
	}
}

exit((new ScppUpdateTest())->run());
