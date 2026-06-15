<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppJssProjectBuildRunTest
{
	private string $root;

	public function run(): void
	{
		$this->root = normalize_path(__DIR__ . '/../../.tmp/scpp_jss_project_' . getmypid() . '_' . bin2hex(random_bytes(4)));
		$this->mkdir($this->root);
		try {
			$this->testJssProjectBuildsRunsAndUsesClassifiedDynamicPlus();
			$this->testJssProjectBuildsRunsAndUsesTakeWithFsAndIoHelpers();
			$this->testJssProjectBuildsRunsFsJsonTakeFlow();
			$this->testJssProjectBuildsRunsFsJsonDatetimeFlow();
			$this->testJssProjectBuildsRunsStrictStringIoFlow();
			$this->testJssProjectBuildsRunsReferencesAndArrowFunctions();
			$this->testJssProjectFsJsonErrorPaths();
			$this->testJssProjectBuildsRunsStrictWrapperErrorPaths();
			$this->testJssProjectBuildsRunsStrictRegexFlow();
			$this->testJssProjectBuildsRunsStrictCurlFlow();
			$this->testJssProjectReportsInactiveHelperModule();
			$this->testJssProjectReportsInactiveCurlModule();
			$this->testJssProjectReportsMissingSymbolCompileError();
			$this->testJssProjectReportsTypeMismatchCompileError();
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

		$build = $this->buildProject($project);
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

		$build = $this->buildProject($project);
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

		$build = $this->buildProject($project);
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

	private function testJssProjectBuildsRunsFsJsonDatetimeFlow(): void
	{
		$project = $this->root . '/project_fs_json_datetime';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'runtime' => [
				'languages' => ['php' => ['profile' => 'strict']],
				'modules' => ['json', 'filesystem', 'datetime'],
			],
			'entrypoint' => 'main.jss',
		], JSON_PRETTY_PRINT) . "\n");
		$this->write($project . '/main.jss', implode("\n", [
			'function run(): void {',
			'    let file: string = "sample_strict_fs_json_datetime.txt";',
			'    let err: error;',
			'    let written: int = 0;',
			'    if (!take(written, err, fs.put(file, "{\"label\":\"epoch\"}\n"))) {',
			'        print("write_error\n");',
			'        return;',
			'    }',
			'    let data: string = "";',
			'    if (!take(data, err, fs.get(file))) {',
			'        print("read_error\n");',
			'        return;',
			'    }',
			'    let decoded: dynamic = json.decode(data);',
			'    let stamp: int = 0;',
			'    if (!take(stamp, err, dt.parse_iso_utc("1970-01-01T00:00:00Z"))) {',
			'        print("date_error\n");',
			'        return;',
			'    }',
			'    print(decoded["label"], "\n");',
			'    print(dt.format_iso_utc(stamp), "\n");',
			'    fs.remove(file);',
			'}',
			'run();',
			'',
		]));

		$build = $this->buildProject($project);
		$this->assertSame(true, $build['ok'], 'JSS fs/json/datetime project build should succeed: ' . ($build['error'] ?? ''));

		$generatedPhs = $project . '/.prism/jss/main.phs';
		$this->assertSame(true, is_file($generatedPhs), 'JSS fs/json/datetime build should emit the classified PHS intermediate');
		$generated = (string) file_get_contents($generatedPhs);
		$this->assertContains('take($written, $err, fs_put($file, "{\"label\":\"epoch\"}\n"))', $generated, 'classified JSS build should lower fs.put in the combined helper flow');
		$this->assertContains('$decoded dynamic = json_decode($data);', $generated, 'classified JSS build should preserve json.decode in the combined helper flow');
		$this->assertContains('take($stamp, $err, dt_parse_iso_utc("1970-01-01T00:00:00Z"))', $generated, 'classified JSS build should lower dt.parse_iso_utc in the combined helper flow');
		$this->assertContains('echo dt_format_iso_utc($stamp), "\n";', $generated, 'classified JSS build should lower dt.format_iso_utc in the combined helper flow');

		$run = scpp_run_binary_service($project, $project . '/.prism/build/main', [], $build);
		$this->assertSame(0, $run['exit_code'], 'built JSS fs/json/datetime program should exit successfully');
		$this->assertSame("epoch\n1970-01-01T00:00:00Z\n", $run['stdout'], 'built JSS fs/json/datetime program should print decoded JSON and formatted datetime output');
		$this->assertSame('', $run['stderr'], 'built JSS fs/json/datetime program should not write stderr');
	}

	private function testJssProjectBuildsRunsStrictStringIoFlow(): void
	{
		$project = $this->root . '/project_strict_string_io';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'runtime' => [
				'languages' => ['php' => ['profile' => 'strict']],
				'modules' => ['filesystem'],
			],
			'entrypoint' => 'main.jss',
		], JSON_PRETTY_PRINT) . "\n");
		$this->write($project . '/main.jss', implode("\n", [
			'function run(): void {',
			'    let root: string = "strict_str_io_root";',
			'    let path: string = root + "/data.txt";',
			'    if (!fs.mkdir(root)) {',
			'        print("MKDIR_FAIL\n");',
			'    } else {',
			'        let fh: resource_handle;',
			'        print(take(fh, io.open(path, "wb+")) ? "T\n" : "F\n");',
			'        let bytes: int = 0;',
			'        if (!take(bytes, io.write(fh, implode("|", explode(",", "a,b,c"))))) {',
			'            print("WRITE_FAIL\n");',
			'        } else {',
			'            print(bytes, "\n");',
			'            print(io.rewind(fh) ? "R\n" : "r\n");',
			'            let line: string = "";',
			'            if (!take(line, io.read(fh, 64))) {',
			'                print("READ_FAIL\n");',
			'            } else {',
			'                print(strtoupper(line), "\n");',
			'            }',
			'        }',
			'        print(io.close(fh) ? "C\n" : "c\n");',
			'        print(fs.remove(path) ? "U\n" : "u\n");',
			'        print(fs.rmdir(root) ? "D\n" : "d\n");',
			'    }',
			'}',
			'run();',
			'',
		]));

		$build = $this->buildProject($project);
		$this->assertSame(true, $build['ok'], 'JSS strict string/IO project build should succeed: ' . ($build['error'] ?? ''));

		$generatedPhs = $project . '/.prism/jss/main.phs';
		$this->assertSame(true, is_file($generatedPhs), 'JSS strict string/IO build should emit the classified PHS intermediate');
		$generated = (string) file_get_contents($generatedPhs);
		$this->assertContains('take($fh, io_open($path, "wb+"))', $generated, 'classified JSS build should lower io.open in the string/IO flow');
		$this->assertContains('take($bytes, io_write($fh, implode("|", explode(",", "a,b,c"))))', $generated, 'classified JSS build should preserve nested strict string helpers inside io.write');
		$this->assertContains('echo strtoupper($line), "\n";', $generated, 'classified JSS build should preserve strict string helper calls');
		$this->assertContains('echo (fs_remove($path) ? "U\n" : "u\n");', $generated, 'classified JSS build should lower cleanup filesystem helpers');

		$run = scpp_run_binary_service($project, $project . '/.prism/build/main', [], $build);
		$this->assertSame(0, $run['exit_code'], 'built JSS strict string/IO program should exit successfully');
		$this->assertSame("T\n5\nR\nA|B|C\nC\nU\nD\n", $run['stdout'], 'built JSS strict string/IO program should print the expected file/string IO output');
		$this->assertSame('', $run['stderr'], 'built JSS strict string/IO program should not write stderr');
	}

	private function testJssProjectBuildsRunsReferencesAndArrowFunctions(): void
	{
		$project = $this->root . '/project_refs_arrows';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'runtime' => ['languages' => ['php' => ['profile' => 'strict']]],
			'entrypoint' => 'main.jss',
		], JSON_PRETTY_PRINT) . "\n");
		$this->write($project . '/main.jss', implode("\n", [
			'let value: int = 5;',
			'let alias = &value;',
			'alias = 9;',
			'let add_one = (x: int): int => x + 1;',
			'print(value, ":", add_one(value), "\n");',
			'',
		]));

		$build = $this->buildProject($project);
		$this->assertSame(true, $build['ok'], 'JSS references/arrows project build should succeed: ' . ($build['error'] ?? ''));

		$generatedPhs = $project . '/.prism/jss/main.phs';
		$this->assertSame(true, is_file($generatedPhs), 'JSS references/arrows build should emit the classified PHS intermediate');
		$generated = (string) file_get_contents($generatedPhs);
		$this->assertContains('$alias =& $value;', $generated, 'classified JSS build should lower explicit reference aliases through PHS reference assignment');
		$this->assertContains('$add_one = fn($x int): int => $x + 1;', $generated, 'classified JSS build should lower explicit typed arrows through PHS fn syntax');

		$run = scpp_run_binary_service($project, $project . '/.prism/build/main', [], $build);
		$this->assertSame(0, $run['exit_code'], 'built JSS references/arrows program should exit successfully');
		$this->assertSame("9:10\n", $run['stdout'], 'built JSS references/arrows program should print reference mutation and arrow result');
		$this->assertSame('', $run['stderr'], 'built JSS references/arrows program should not write stderr');
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

		$build = $this->buildProject($project);
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

	private function testJssProjectBuildsRunsStrictWrapperErrorPaths(): void
	{
		$project = $this->root . '/project_strict_wrapper_error_paths';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'runtime' => [
				'languages' => ['php' => ['profile' => 'strict']],
				'modules' => ['filesystem'],
			],
			'entrypoint' => 'main.jss',
		], JSON_PRETTY_PRINT) . "\n");
		$this->write($project . '/main.jss', implode("\n", [
			'let err: error;',
			'let text: string = "seed";',
			'print(take(text, err, fs.get("missing_strict_sample.txt")) ? "T\n" : "F\n");',
			'print(text, "\n");',
			'',
			'print(take(text, hex2bin("4142")) ? "T\n" : "F\n");',
			'print(text, "\n");',
			'',
			'print(take(text, hex2bin("4")) ? "T\n" : "F\n");',
			'print(text, "\n");',
			'',
		]));

		$build = $this->buildProject($project);
		$this->assertSame(true, $build['ok'], 'JSS strict wrapper error-path project build should succeed: ' . ($build['error'] ?? ''));

		$generatedPhs = $project . '/.prism/jss/main.phs';
		$this->assertSame(true, is_file($generatedPhs), 'JSS strict wrapper error-path build should emit the classified PHS intermediate');
		$generated = (string) file_get_contents($generatedPhs);
		$this->assertContains('echo (take($text, $err, fs_get("missing_strict_sample.txt")) ? "T\n" : "F\n");', $generated, 'classified JSS build should lower fs.get wrapper failure checks');
		$this->assertContains('echo (take($text, hex2bin("4142")) ? "T\n" : "F\n");', $generated, 'classified JSS build should preserve two-argument take on successful wrapper extraction');
		$this->assertContains('echo (take($text, hex2bin("4")) ? "T\n" : "F\n");', $generated, 'classified JSS build should preserve two-argument take on failed wrapper extraction');

		$run = scpp_run_binary_service($project, $project . '/.prism/build/main', [], $build);
		$this->assertSame(0, $run['exit_code'], 'built JSS strict wrapper error-path program should exit successfully');
		$this->assertSame("F\nseed\nT\nAB\nF\nAB\n", $run['stdout'], 'built JSS strict wrapper error-path program should preserve wrapper success/failure value behavior');
		$this->assertSame('', $run['stderr'], 'built JSS strict wrapper error-path program should not write stderr');
	}

	private function testJssProjectBuildsRunsStrictRegexFlow(): void
	{
		$project = $this->root . '/project_strict_regex';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'runtime' => [
				'languages' => ['php' => ['profile' => 'strict']],
				'modules' => ['regex'],
			],
			'entrypoint' => 'main.jss',
		], JSON_PRETTY_PRINT) . "\n");
		$this->write($project . '/main.jss', implode("\n", [
			'let err: error;',
			'',
			'let caps: vector<string> = [];',
			'if (!take(caps, regex_match("/(ab+)-(cd+)/i", "xxAbb-cDDyy"))) {',
			'    print("MATCH_ERROR\n");',
			'} else {',
			'    print(caps[0], "\n");',
			'    print(caps[1], "\n");',
			'    print(caps[2], "\n");',
			'}',
			'',
			'let parts: vector<string> = [];',
			'if (!take(parts, regex_split("/,/", "a,b,c"))) {',
			'    print("SPLIT_ERROR\n");',
			'} else {',
			'    print(implode("|", parts), "\n");',
			'}',
			'',
			'let replaced: string = "";',
			'if (!take(replaced, regex_replace("/ab+/i", "X", "ab xx ABB yy"))) {',
			'    print("REPLACE_ERROR\n");',
			'} else {',
			'    print(replaced, "\n");',
			'}',
			'',
		]));

		$build = $this->buildProject($project);
		$this->assertSame(true, $build['ok'], 'JSS strict regex project build should succeed: ' . ($build['error'] ?? ''));

		$generatedPhs = $project . '/.prism/jss/main.phs';
		$this->assertSame(true, is_file($generatedPhs), 'JSS strict regex build should emit the classified PHS intermediate');
		$generated = (string) file_get_contents($generatedPhs);
		$this->assertContains('if (!take($caps, regex_match("/(ab+)-(cd+)/i", "xxAbb-cDDyy")))', $generated, 'classified JSS build should preserve regex_match wrapper flow');
		$this->assertContains('if (!take($parts, regex_split("/,/", "a,b,c")))', $generated, 'classified JSS build should preserve regex_split wrapper flow');
		$this->assertContains('if (!take($replaced, regex_replace("/ab+/i", "X", "ab xx ABB yy")))', $generated, 'classified JSS build should preserve regex_replace wrapper flow');

		$run = scpp_run_binary_service($project, $project . '/.prism/build/main', [], $build);
		$this->assertSame(0, $run['exit_code'], 'built JSS strict regex program should exit successfully');
		$this->assertSame("Abb-cDD\nAbb\ncDD\na|b|c\nX xx X yy\n", $run['stdout'], 'built JSS strict regex program should print the expected match/split/replace output');
		$this->assertSame('', $run['stderr'], 'built JSS strict regex program should not write stderr');
	}

	private function testJssProjectBuildsRunsStrictCurlFlow(): void
	{
		$project = $this->root . '/project_strict_curl';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'runtime' => [
				'languages' => ['php' => ['profile' => 'strict']],
				'modules' => ['filesystem', 'curl'],
			],
			'entrypoint' => 'main.jss',
		], JSON_PRETTY_PRINT) . "\n");
		$this->write($project . '/main.jss', implode("\n", [
			'let root: string = "strict_curl_root";',
			'let path: string = root + "/payload.bin";',
			'let payload: string = "";',
			'let written: int = 0;',
			'let real: string = "";',
			'let err: error;',
			'let ok: bool = false;',
			'let ch: curl_handle;',
			'let resp: curl_response;',
			'',
			'if (!fs.mkdir(root)) {',
			'    print("MKDIR_FAIL\n");',
			'} else {',
			'    if (!take(payload, hex2bin("4100420A"))) {',
			'        print("HEX_FAIL\n");',
			'    } else if (!take(written, err, fs.put(path, payload))) {',
			'        print("WRITE_FAIL\n");',
			'    } else if (!take(real, err, fs.realpath(path))) {',
			'        print("REAL_FAIL\n");',
			'    } else {',
			'        print(written, "\n");',
			'',
			'        let url: string = "file://" + real;',
			'        if (!take(ch, err, curl_init(url))) {',
			'            print("INIT_FAIL\n");',
			'        } else {',
			'            let headers: vector<string> = [];',
			'            headers.push("X-Test: strict-curl");',
			'',
			'            print(take(ok, err, curl_setopt(ch, CURLOPT_TIMEOUT, 5)) ? "T\n" : "F\n");',
			'            print(take(ok, err, curl_setopt(ch, CURLOPT_FOLLOWLOCATION, true)) ? "T\n" : "F\n");',
			'            print(take(ok, err, curl_setopt(ch, CURLOPT_USERAGENT, "simplecpp-strict-curl/1.0")) ? "T\n" : "F\n");',
			'            print(take(ok, err, curl_setopt(ch, CURLOPT_HTTPHEADER, headers)) ? "T\n" : "F\n");',
			'',
			'            if (!take(resp, err, curl_exec(ch))) {',
			'                print("EXEC_FAIL\n");',
			'                print(curl_errno(ch), "\n");',
			'                print(curl_error(ch), "\n");',
			'            } else {',
			'                print(resp.status_code, "\n");',
			'                print(fs.basename(resp.effective_url), "\n");',
			'                print(bin2hex(resp.body), "\n");',
			'                print(strlen(resp.body), "\n");',
			'                print(curl_errno(ch), "\n");',
			'                print(curl_error(ch), "\n");',
			'            }',
			'',
			'            print(take(ok, err, curl_close(ch)) ? "T\n" : "F\n");',
			'        }',
			'    }',
			'',
			'    print(fs.remove(path) ? "U\n" : "u\n");',
			'    print(fs.rmdir(root) ? "D\n" : "d\n");',
			'}',
			'',
		]));

		$build = $this->buildProject($project);
		$this->assertSame(true, $build['ok'], 'JSS strict curl project build should succeed: ' . ($build['error'] ?? ''));

		$generatedPhs = $project . '/.prism/jss/main.phs';
		$this->assertSame(true, is_file($generatedPhs), 'JSS strict curl build should emit the classified PHS intermediate');
		$generated = (string) file_get_contents($generatedPhs);
		$this->assertContains('take($ch, $err, curl_init($url))', $generated, 'classified JSS build should preserve strict curl_init flow');
		$this->assertContains('take($ok, $err, curl_setopt($ch, CURLOPT_TIMEOUT, 5))', $generated, 'classified JSS build should preserve strict curl_setopt flow');
		$this->assertContains('take($resp, $err, curl_exec($ch))', $generated, 'classified JSS build should preserve strict curl_exec flow');
		$this->assertContains('echo $resp->status_code, "\n";', $generated, 'classified JSS build should lower curl response field access');

		$run = scpp_run_binary_service($project, $project . '/.prism/build/main', [], $build);
		$this->assertSame(0, $run['exit_code'], 'built JSS strict curl program should exit successfully');
		$this->assertSame("4\nT\nT\nT\nT\n0\npayload.bin\n4100420a\n4\n0\n\nT\nU\nD\n", $run['stdout'], 'built JSS strict curl program should print the expected file-url curl output');
		$this->assertSame('', $run['stderr'], 'built JSS strict curl program should not write stderr');
	}

	private function testJssProjectReportsInactiveHelperModule(): void
	{
		$project = $this->root . '/project_missing_filesystem_module';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'runtime' => [
				'languages' => ['php' => ['profile' => 'strict']],
				'modules' => ['json'],
			],
			'entrypoint' => 'main.jss',
		], JSON_PRETTY_PRINT) . "\n");
		$this->write($project . '/main.jss', implode("\n", [
			'function run(): void {',
			'    let text: string = "";',
			'    let err: error;',
			'    if (!take(text, err, fs.get("missing.txt"))) {',
			'        return;',
			'    }',
			'}',
			'run();',
			'',
		]));

		$build = scpp_run_build_service($project, $project . '/prism.json', ['compile_runtime' => true]);
		$this->assertSame(false, $build['ok'], 'JSS project build should fail when a helper runtime module is inactive');
		$error = (string) ($build['error'] ?? '');
		$this->assertContains('Runtime helper `fs_get()` requires module `filesystem` in the active project runtime config.', $error, 'JSS project build should surface the STAN-owned helper module diagnostic');
		$this->assertContains('main.jss', $error, 'JSS project helper module diagnostic should mention the source file');
	}

	private function testJssProjectReportsInactiveCurlModule(): void
	{
		$project = $this->root . '/project_missing_curl_module';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'runtime' => [
				'languages' => ['php' => ['profile' => 'strict']],
				'modules' => ['filesystem'],
			],
			'entrypoint' => 'main.jss',
		], JSON_PRETTY_PRINT) . "\n");
		$this->write($project . '/main.jss', implode("\n", [
			'function run(): void {',
			'    let err: error;',
			'    let ch: curl_handle;',
			'    if (!take(ch, err, curl_init("file:///missing"))) {',
			'        return;',
			'    }',
			'}',
			'run();',
			'',
		]));

		$build = scpp_run_build_service($project, $project . '/prism.json', ['compile_runtime' => true]);
		$this->assertSame(false, $build['ok'], 'JSS project build should fail when the curl runtime module is inactive');
		$error = (string) ($build['error'] ?? '');
		$this->assertContains('Runtime helper `curl_init()` requires module `curl` in the active project runtime config.', $error, 'JSS project build should surface the STAN-owned curl module diagnostic');
		$this->assertContains('main.jss', $error, 'JSS project curl module diagnostic should mention the source file');
	}

	private function testJssProjectReportsMissingSymbolCompileError(): void
	{
		$project = $this->root . '/project_missing_symbol';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'runtime' => [
				'languages' => ['php' => ['profile' => 'strict']],
				'modules' => ['json', 'filesystem'],
			],
			'entrypoint' => 'main.jss',
		], JSON_PRETTY_PRINT) . "\n");
		$this->write($project . '/main.jss', implode("\n", [
			'unknown_compile_probe();',
			'',
		]));

		$build = scpp_run_build_service($project, $project . '/prism.json', ['compile_runtime' => true]);
		$this->assertSame(false, $build['ok'], 'JSS project build should fail on a missing function symbol');
		$error = (string) ($build['error'] ?? '');
		$this->assertContains('unknown_compile_probe', $error, 'JSS missing-symbol build failure should mention the unresolved callee');
		$this->assertContains('main.jss', $error, 'JSS missing-symbol build failure should mention the source file');
	}

	private function testJssProjectReportsTypeMismatchCompileError(): void
	{
		$project = $this->root . '/project_type_mismatch';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'runtime' => [
				'languages' => ['php' => ['profile' => 'strict']],
				'modules' => ['json', 'filesystem'],
			],
			'entrypoint' => 'main.jss',
		], JSON_PRETTY_PRINT) . "\n");
		$this->write($project . '/main.jss', implode("\n", [
			'let value: int = "abc";',
			'print(value, "\n");',
			'',
		]));

		$build = scpp_run_build_service($project, $project . '/prism.json', ['compile_runtime' => true]);
		$this->assertSame(false, $build['ok'], 'JSS project build should fail on a strict assignment type mismatch');
		$error = (string) ($build['error'] ?? '');
		$this->assertContains('conversion from', $error, 'JSS type-mismatch build failure should explain the failed conversion');
		$this->assertContains('main.jss', $error, 'JSS type-mismatch build failure should mention the source file');
	}

	private function assertSame(mixed $expected, mixed $actual, string $message): void
	{
		if ($expected === $actual) {
			return;
		}
		throw new RuntimeException($message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
	}

	/** @return array<string,mixed> */
	private function buildProject(string $project): array
	{
		$build = scpp_run_build_service($project, $project . '/prism.json', ['compile_runtime' => true]);
		if (($build['ok'] ?? false) === true || !$this->isTransientNativeBuildFailure((string) ($build['error'] ?? ''))) {
			return $build;
		}
		return scpp_run_build_service($project, $project . '/prism.json', ['compile_runtime' => true, 'force_runtime_rebuild' => true]);
	}

	private function isTransientNativeBuildFailure(string $error): bool
	{
		return str_contains($error, 'Ninja build failed.')
			|| str_contains($error, 'Failed to publish runtime object')
			|| str_contains($error, 'Required runtime artifact is missing.');
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
