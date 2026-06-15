<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppJssProjectBuildRunTest
{
	private string $root;

	public function run(): void
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_jss_project_' . getmypid() . '_' . bin2hex(random_bytes(4)));
		$this->mkdir($this->root);
		try {
			$this->testJssProjectBuildsRunsAndUsesClassifiedDynamicPlus();
			$this->testJssProjectBuildsRunsAndUsesTakeWithFsAndIoHelpers();
			$this->testJssProjectBuildsRunsFsJsonTakeFlow();
			$this->testJssProjectFsJsonErrorPaths();
			echo "PASS: scpp jss project build/run\n";
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function testJssProjectBuildsRunsAndUsesClassifiedDynamicPlus(): void
	{
		$project = $this->root . '/project';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'runtime' => ['languages' => ['php' => ['profile' => 'strict']]],
			'entrypoint' => 'main.jss',
		], JSON_PRETTY_PRINT) . "\n");
		$this->write($project . '/main.jss', implode("\n", [
			'let value: mixed = 4;',
			'print(value + 2, "\n");',
			'',
		]));

		$build = scpp_run_build_service($project, $project . '/prism.json', ['compile_runtime' => true]);
		$this->assertSame(true, $build['ok'], 'JSS project build should succeed: ' . ($build['error'] ?? ''));

		$generatedPhs = $project . '/.prism/jss/main.phs';
		$this->assertSame(true, is_file($generatedPhs), 'JSS project build should emit the classified PHS intermediate');
		$this->assertContains('echo js_plus($value, 2), "\n";', (string) file_get_contents($generatedPhs), 'classified JSS project build should lower dynamic + through js_plus');

		$run = scpp_run_binary_service($project, $project . '/.prism/build/main', [], $build);
		$this->assertSame(0, $run['exit_code'], 'built JSS program should exit successfully');
		$this->assertSame("6\n", $run['stdout'], 'built JSS program should print the dynamic plus result');
		$this->assertSame('', $run['stderr'], 'built JSS program should not write stderr');
	}

	private function testJssProjectBuildsRunsAndUsesTakeWithFsAndIoHelpers(): void
	{
		$project = $this->root . '/project_take_fs_io';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'runtime' => ['languages' => ['php' => ['profile' => 'strict']]],
			'entrypoint' => 'main.jss',
		], JSON_PRETTY_PRINT) . "\n");
		$this->write($project . '/main.jss', implode("\n", [
			'function run(): void {',
			'    let root: string = "jss_take_root";',
			'    let path: string = root + "/data.txt";',
			'    let err: error;',
			'    if (!fs.mkdir(root)) {',
			'        print("mkdir-fail\n");',
			'        return;',
			'    }',
			'    let fh: resource_handle;',
			'    if (!take(fh, io.open(path, "wb+"))) {',
			'        print("open-write-fail\n");',
			'        return;',
			'    }',
			'    let written: int = 0;',
			'    if (!take(written, io.write(fh, "alpha\nbeta"))) {',
			'        print("write-fail\n");',
			'        return;',
			'    }',
			'    io.flush(fh);',
			'    io.rewind(fh);',
			'    let line: string = "";',
			'    if (!take(line, io.read_line(fh))) {',
			'        print("read-fail\n");',
			'        return;',
			'    }',
			'    io.close(fh);',
			'    let content: string = "";',
			'    if (!take(content, err, fs.get(path))) {',
			'        print("get-fail\n");',
			'        return;',
			'    }',
			'    print(line);',
			'    print(content, "\n");',
			'    fs.remove(path);',
			'    fs.rmdir(root);',
			'}',
			'run();',
			'',
		]));

		$build = scpp_run_build_service($project, $project . '/prism.json', ['compile_runtime' => true]);
		$this->assertSame(true, $build['ok'], 'JSS fs/io take project build should succeed: ' . ($build['error'] ?? ''));

		$generatedPhs = $project . '/.prism/jss/main.phs';
		$this->assertSame(true, is_file($generatedPhs), 'JSS fs/io take build should emit the classified PHS intermediate');
		$generated = (string) file_get_contents($generatedPhs);
		$this->assertContains('if (!fs_mkdir($root)) {', $generated, 'classified JSS build should preserve direct bool filesystem helper usage');
		$this->assertContains('take($fh, io_open($path, "wb+"))', $generated, 'classified JSS build should lower io.open through reserved helper-family mapping');
		$this->assertContains('take($content, $err, fs_get($path))', $generated, 'classified JSS build should lower fs.get through reserved helper-family mapping');

		$run = scpp_run_binary_service($project, $project . '/.prism/build/main', [], $build);
		$this->assertSame(0, $run['exit_code'], 'built JSS fs/io take program should exit successfully');
		$this->assertSame("alpha\nalpha\nbeta\n", $run['stdout'], 'built JSS fs/io take program should print the line and full file contents');
		$this->assertSame('', $run['stderr'], 'built JSS fs/io take program should not write stderr');
	}

	private function testJssProjectBuildsRunsFsJsonTakeFlow(): void
	{
		$project = $this->root . '/project_fs_json';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'runtime' => [
				'languages' => ['php' => ['profile' => 'strict']],
				'modules' => ['json', 'filesystem'],
			],
			'entrypoint' => 'main.jss',
		], JSON_PRETTY_PRINT) . "\n");
		$this->write($project . '/main.jss', implode("\n", [
			'// usable prototype lane: fs + json + take',
			'function run(): void {',
			'    /* write JSON text through the strict filesystem helper family */',
			'    let file: string = "sample_strict_fs_json.txt";',
			'    let err: error;',
			'    let written: int = 0;',
			'    if (!take(written, err, fs.put(file, "{\"name\":\"alex\",\"count\":2}\n"))) {',
			'        print("write_error\n");',
			'        return;',
			'    }',
			'    let data: string = "";',
			'    if (!take(data, err, fs.get(file))) {',
			'        print("read_error\n");',
			'        return;',
			'    }',
			'    // decode through the existing dynamic JSON boundary',
			'    let decoded: dynamic = json.decode(data);',
			'    print(written, "\n");',
			'    print(strlen(data), "\n");',
			'    print(decoded["name"], "\n");',
			'    print(decoded["count"], "\n");',
			'    if (!fs.remove(file)) {',
			'        print("remove_error\n");',
			'    }',
			'}',
			'run();',
			'',
		]));

		$build = scpp_run_build_service($project, $project . '/prism.json', ['compile_runtime' => true]);
		$this->assertSame(true, $build['ok'], 'JSS fs/json take project build should succeed: ' . ($build['error'] ?? ''));

		$generatedPhs = $project . '/.prism/jss/main.phs';
		$this->assertSame(true, is_file($generatedPhs), 'JSS fs/json take build should emit the classified PHS intermediate');
		$generated = (string) file_get_contents($generatedPhs);
		$this->assertContains('take($written, $err, fs_put($file, "{\"name\":\"alex\",\"count\":2}\n"))', $generated, 'classified JSS build should lower fs.put through reserved helper-family mapping');
		$this->assertContains('take($data, $err, fs_get($file))', $generated, 'classified JSS build should lower fs.get through reserved helper-family mapping');
		$this->assertContains('$decoded dynamic = json_decode($data);', $generated, 'classified JSS build should preserve json.decode as a dynamic boundary');
		$this->assertContains('echo $decoded["name"], "\n";', $generated, 'classified JSS build should preserve direct decoded dynamic field access');
		$this->assertContains('echo $decoded["count"], "\n";', $generated, 'classified JSS build should preserve direct decoded dynamic numeric field access');

		$run = scpp_run_binary_service($project, $project . '/.prism/build/main', [], $build);
		$this->assertSame(0, $run['exit_code'], 'built JSS fs/json take program should exit successfully');
		$this->assertSame("26\n26\nalex\n2\n", $run['stdout'], 'built JSS fs/json take program should print the expected file/json roundtrip output');
		$this->assertSame('', $run['stderr'], 'built JSS fs/json take program should not write stderr');
	}

	private function testJssProjectFsJsonErrorPaths(): void
	{
		$project = $this->root . '/project_fs_json_errors';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'runtime' => [
				'languages' => ['php' => ['profile' => 'strict']],
				'modules' => ['json', 'filesystem'],
			],
			'entrypoint' => 'main.jss',
		], JSON_PRETTY_PRINT) . "\n");
		$this->write($project . '/main.jss', implode("\n", [
			'function missingFile(): void {',
			'    let err: error;',
			'    let text: string = "";',
			'    if (!take(text, err, fs.get("missing_strict_fs_json.txt"))) {',
			'        print("missing_file\n");',
			'        return;',
			'    }',
			'    print("unexpected_read\n");',
			'}',
			'',
			'function badJson(): void {',
			'    let bad: string = "{\"name\":\"alex\"";',
			'    let decoded: dynamic = json.decode(bad);',
			'    print(decoded["name"], "\n");',
			'}',
			'',
			'missingFile();',
			'badJson();',
			'',
		]));

		$build = scpp_run_build_service($project, $project . '/prism.json', ['compile_runtime' => true]);
		$this->assertSame(true, $build['ok'], 'JSS fs/json error-path project build should succeed: ' . ($build['error'] ?? ''));

		$generatedPhs = $project . '/.prism/jss/main.phs';
		$this->assertSame(true, is_file($generatedPhs), 'JSS fs/json error-path build should emit the classified PHS intermediate');
		$generated = (string) file_get_contents($generatedPhs);
		$this->assertContains('if (!take($text, $err, fs_get("missing_strict_fs_json.txt")))', $generated, 'classified JSS build should preserve the missing-file take flow');
		$this->assertContains('$decoded dynamic = json_decode($bad);', $generated, 'classified JSS build should preserve json.decode as a dynamic error-path boundary');

		$run = scpp_run_binary_service($project, $project . '/.prism/build/main', [], $build);
		$this->assertSame(1, $run['exit_code'], 'built JSS fs/json error-path program should surface the malformed json runtime failure');
		$this->assertSame("missing_file\n", $run['stdout'], 'built JSS fs/json error-path program should report the missing file before the malformed json failure');
		$this->assertContains('Runtime error while running the built program.', $run['stderr'], 'built JSS fs/json error-path program should surface a project-level runtime failure summary even when the runtime does not yet provide a source location');
		$this->assertContains('Runtime message: json error at byte', $run['stderr'], 'built JSS fs/json error-path program should preserve the precise json parse detail as supporting context');
	}

	private function assertSame(mixed $expected, mixed $actual, string $message): void
	{
		if ($expected === $actual) {
			return;
		}
		throw new RuntimeException($message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
	}

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (str_contains($haystack, $needle)) {
			return;
		}
		throw new RuntimeException($message . ' missing `' . $needle . '` in `' . $haystack . '`');
	}

	private function mkdir(string $path): void
	{
		if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
			throw new RuntimeException('Failed to create directory: ' . $path);
		}
	}

	private function write(string $path, string $contents): void
	{
		$this->mkdir(dirname($path));
		if (file_put_contents($path, $contents) === false) {
			throw new RuntimeException('Failed to write file: ' . $path);
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
			@unlink($child);
		}
		@rmdir($path);
	}
}

(new ScppJssProjectBuildRunTest())->run();
