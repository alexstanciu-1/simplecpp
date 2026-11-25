<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppObjectActionIdentityTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_object_action_identity_' . getmypid() . '_' . bin2hex(random_bytes(4)));
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
			$this->assertBuildRecordsObjectActionIdentities();
			echo "PASS: scpp object action identity\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function assertBuildRecordsObjectActionIdentities(): void
	{
		$project = $this->root . '/app';
		$this->mkdir($project . '/native_cpp');
		$this->write($project . '/main.phs', "echo \"action identity\\n\";\n");
		$this->write($project . '/native_cpp/probe.cpp', "void scpp_action_identity_probe() {}\n");
		$this->writeJson($project . '/prism.json', [
			'config_version' => 1,
			'project_name' => 'object_action_identity',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'generated_dir' => '.prism/generated',
			'cache_dir' => '.prism/cache',
			'native_cpp_dir' => 'native_cpp',
			'build' => [
				'backend' => 'ninja',
				'cxx' => null,
				'mode' => 'debug',
				'object_action_identity' => true,
			],
			'runtime' => [
				'languages' => ['php'],
				'modules' => ['json', 'filesystem'],
				'language_profiles' => [
					'php' => ['profile' => 'strict'],
				],
			],
		]);

		$build = scpp_run_build_service($project, $project . '/prism.json', [
			'compile_runtime' => true,
			'compile_dependencies' => false,
		]);
		$this->assertSame(true, $build['ok'] ?? null, 'build should succeed');

		$lastRun = json_decode($this->read($project . '/.prism/last_run.json'), true);
		if (!is_array($lastRun)) {
			throw new RuntimeException('last_run.json should decode');
		}
		$details = is_array($lastRun['details'] ?? null) ? $lastRun['details'] : [];
		$topLevel = normalize_object_action_identity_report(is_array($details['object_action_identity'] ?? null) ? $details['object_action_identity'] : []);
		$explanation = is_array($details['build_explanation'] ?? null) ? $details['build_explanation'] : [];
		$explanationReport = normalize_object_action_identity_report(is_array($explanation['object_action_identity'] ?? null) ? $explanation['object_action_identity'] : []);
		$this->assertSame($topLevel, $explanationReport, 'top-level and explanation action identity reports should match');
		$this->assertSame(2, $topLevel['total_action_count'] ?? null, 'build should record generated and native object actions');
		$this->assertSame(1, $topLevel['generated_action_count'] ?? null, 'build should record one generated object action');
		$this->assertSame(1, $topLevel['native_action_count'] ?? null, 'build should record one native object action');
		$this->assertSame(0, $topLevel['skipped_dependency_object_count'] ?? null, 'root-only project should skip no dependency objects');
		$this->assertSame('full', $topLevel['capture_mode'] ?? null, 'opt-in build should record full object action identities');
		$this->assertSame('build.object_action_identity', $topLevel['capture_reason'] ?? null, 'full object action capture should identify the config source');

		$actionsByKind = [];
		foreach (is_array($topLevel['actions'] ?? null) ? $topLevel['actions'] : [] as $action) {
			if (is_array($action) && is_string($action['kind'] ?? null)) {
				$actionsByKind[(string) $action['kind']] = $action;
			}
		}
		$generated = $actionsByKind['generated'] ?? null;
		$native = $actionsByKind['native'] ?? null;
		if (!is_array($generated) || !is_array($native)) {
			throw new RuntimeException('generated and native action rows should be present');
		}
		foreach ([$generated, $native] as $action) {
			$this->assertHash((string) ($action['action_key'] ?? ''), 'action key should be a sha256 hash');
			$this->assertHash((string) ($action['command_hash'] ?? ''), 'command hash should be a sha256 hash');
			$this->assertHash((string) ($action['input_hash'] ?? ''), 'input hash should be a sha256 hash');
			$this->assertHash((string) ($action['output_hash'] ?? ''), 'output hash should be available after a successful build');
			$fingerprints = is_array($action['input_fingerprints'] ?? null) ? $action['input_fingerprints'] : [];
			$this->assertTrue($fingerprints !== [], 'action row should record input fingerprints');
			$this->assertTrue(is_array($action['command'] ?? null), 'action row should include normalized command payload');
			$this->assertTrue(is_array($action['environment'] ?? null), 'action row should include environment payload');
		}
		$this->assertSame('.prism/generated/main.cpp', $generated['primary_input'] ?? null, 'generated action should identify generated C++ as primary input');
		$this->assertContains('main.phs', implode("\n", is_array($generated['member_sources'] ?? null) ? $generated['member_sources'] : []), 'generated action should list member source');
		$this->assertSame('native_cpp/probe.cpp', $native['primary_input'] ?? null, 'native action should identify native source as primary input');

		$sources = is_array($explanation['sources'] ?? null) ? $explanation['sources'] : [];
		$mainSource = null;
		foreach ($sources as $source) {
			if (is_array($source) && ($source['path'] ?? null) === 'main.phs') {
				$mainSource = $source;
				break;
			}
		}
		if (!is_array($mainSource)) {
			throw new RuntimeException('build explanation should contain main.phs source row');
		}
		$this->assertSame($generated['action_key'] ?? null, $mainSource['object_action_key'] ?? null, 'source row should carry generated object action key');

		$lines = implode("\n", render_object_action_identity_lines($topLevel));
		$this->assertContains('Object action identity: 2 action(s), generated 1, native 1', $lines, 'action identity view should summarize actions');
		$this->assertContains('.prism/build/main.o', $lines, 'action identity view should show generated object path');
		$this->assertContains('.prism/build/native/native_cpp/probe.o', $lines, 'action identity view should show native object path');
	}

	private function writeJson(string $path, array $data): void
	{
		$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		if (!is_string($json)) {
			throw new RuntimeException('Failed to encode JSON');
		}
		$this->write($path, $json . PHP_EOL);
	}

	private function mkdir(string $path): void
	{
		if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
			throw new RuntimeException('Failed to create directory: ' . $path);
		}
	}

	private function write(string $path, string $contents): void
	{
		$dir = dirname($path);
		if (!is_dir($dir)) {
			$this->mkdir($dir);
		}
		if (file_put_contents($path, $contents) === false) {
			throw new RuntimeException('Failed to write file: ' . $path);
		}
	}

	private function read(string $path): string
	{
		$contents = file_get_contents($path);
		if (!is_string($contents)) {
			throw new RuntimeException('Failed to read file: ' . $path);
		}
		return $contents;
	}

	private function removeTree(string $path): void
	{
		if (!is_dir($path)) {
			return;
		}
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ($iterator as $entry) {
			if (!$entry instanceof SplFileInfo) {
				continue;
			}
			if ($entry->isDir() && !$entry->isLink()) {
				@rmdir($entry->getPathname());
			} else {
				@unlink($entry->getPathname());
			}
		}
		@rmdir($path);
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
			throw new RuntimeException($message . ' missing `' . $needle . '` in `' . $haystack . '`');
		}
	}

	private function assertHash(string $value, string $message): void
	{
		if (preg_match('/^[0-9a-f]{64}$/', $value) !== 1) {
			throw new RuntimeException($message . ', got `' . $value . '`');
		}
	}
}

exit((new ScppObjectActionIdentityTest())->run());
