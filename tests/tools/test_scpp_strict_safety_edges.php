<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppStrictSafetyEdgesTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_strict_safety_edges_' . getmypid() . '_' . bin2hex(random_bytes(4)));
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
			$this->runStep('missing return stops build', $this->assertMissingReturnStopsBuild(...));
			$this->runStep('direct self-recursion stops build', $this->assertDirectSelfRecursionStopsBuild(...));
			$this->runStep('missing dynamic JSON fields fail required typed locals', $this->assertMissingDynamicJsonFieldFailsRequiredTypedLocal(...));
			$this->runStep('dynamic JSON numeric shapes fail required int locals', $this->assertDynamicJsonNumericShapesFailRequiredIntLocal(...));
			$this->runStep('nested typed vector literals stabilize recursively', $this->assertNestedTypedVectorLiteralsStabilizeRecursively(...));
			$this->runStep('decoded JSON arrays stabilize into typed vectors', $this->assertDecodedJsonArraysStabilizeIntoTypedVectors(...));
			$this->runStep('json_encode accepts typed collections', $this->assertJsonEncodeAcceptsTypedCollections(...));
			$this->runStep('explicit null string cast still succeeds', $this->assertExplicitNullStringCastStillSucceeds(...));
			$this->runStep('explicit int casts still succeed', $this->assertExplicitIntCastsStillSucceed(...));
			$this->runStep('recursive debug run fails with runtime diagnostic', $this->assertRecursiveDebugRunFailsWithRuntimeDiagnostic(...));
			$this->runStep('recursive release opt-in fails with runtime diagnostic', $this->assertRecursiveReleaseOptInFailsWithRuntimeDiagnostic(...));
			echo "PASS: scpp strict safety edges\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function runStep(string $name, callable $callback): void
	{
		$started = microtime(true);
		echo 'RUN: ' . $name . "\n";
		flush();
		$callback();
		echo 'OK: ' . $name . ' (' . number_format(microtime(true) - $started, 2) . "s)\n";
		flush();
	}

	private function assertMissingReturnStopsBuild(): void
	{
		$project = $this->root . '/missing_return';
		$this->writeProject($project, []);
		$this->write($project . '/main.phs', <<<'PHS'
function choose(bool $flag): int {
	if ($flag) {
		return 1;
	}
}

echo choose(false), "\n";
PHS
 . "\n");

		$build = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'build'], $project, 120);
		$this->assertNotSame(0, $build['exit_code'], 'missing-return build should fail');
		$this->assertContains('STAN pre-build check failed', $build['stderr'], 'missing-return build should stop in STAN');
		$this->assertContains('may exit without returning a value', $build['stderr'], 'missing-return diagnostic should explain the return path');
	}

	private function assertDirectSelfRecursionStopsBuild(): void
	{
		$project = $this->root . '/recursive_stan';
		$this->writeProject($project, []);
		$this->writeRecursiveDiveProgram($project);

		$build = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'build'], $project, 120);
		$this->assertNotSame(0, $build['exit_code'], 'direct self-recursive return should fail STAN preflight');
		$this->assertContains('STAN pre-build check failed', $build['stderr'], 'direct self-recursive return should stop in STAN');
		$this->assertContains('Direct self-recursive return', $build['stderr'], 'direct self-recursive return diagnostic should explain recursion');
	}

	private function assertMissingDynamicJsonFieldFailsRequiredTypedLocal(): void
	{
		$project = $this->root . '/missing_json_field';
		$this->writeProject($project, []);
		$builtRuntime = false;
		foreach ([
			'string' => 'string_t',
			'int' => 'int_t',
			'float' => 'float_t',
			'bool' => 'bool_t',
		] as $localType => $runtimeType) {
			$this->write($project . '/main.phs', <<<'PHS'
$text = "{\"count\":2}";
$row = json_decode($text);
PHS
 . "\n"
 . '$value ' . $localType . ' = $row["name"];' . "\n"
 . 'echo $value, "\n";' . "\n");

			$run = $this->runScpp($project, ['run'], !$builtRuntime, 120);
			$builtRuntime = true;
			$this->assertNotSame(0, $run['exit_code'], 'missing dynamic JSON field should fail a required ' . $localType . ' typed local');
			$this->assertContains('Cannot convert value to required ' . $runtimeType . '.', $run['stderr'], 'missing dynamic JSON field diagnostic should explain the required ' . $localType . ' typed boundary');
			$this->assertContains('Runtime error in main.phs:3', $run['stderr'], 'missing dynamic JSON field diagnostic should remap to the ' . $localType . ' typed local');
			$this->assertContains('Actual runtime kind: null_t', $run['stderr'], 'missing dynamic JSON field diagnostic should preserve the null runtime kind for ' . $localType);
			$this->assertContains('Operation: scpp::required_cast<' . $runtimeType . '>', $run['stderr'], 'missing dynamic JSON field diagnostic should use required_cast for ' . $localType);
		}
	}

	private function assertDynamicJsonNumericShapesFailRequiredIntLocal(): void
	{
		$project = $this->root . '/json_numeric_shapes';
		$this->writeProject($project, []);
		$builtRuntime = false;
		foreach ([
			'json_float_to_int' => '{"count":2.5}',
			'json_string_number_to_int' => '{"count":"42"}',
		] as $case => $json) {
			$this->write($project . '/main.phs', '$row = json_decode(' . var_export($json, true) . ');' . "\n" . <<<'PHS'
$count int = $row["count"];
echo $count, "\n";
PHS
 . "\n");

			$run = $this->runScpp($project, ['run'], !$builtRuntime, 120);
			$builtRuntime = true;
			$this->assertNotSame(0, $run['exit_code'], $case . ' should fail a required int typed local');
			$this->assertContains('Cannot convert value to required int_t.', $run['stderr'], $case . ' diagnostic should explain the required int boundary');
			$this->assertContains('Runtime error in main.phs:2', $run['stderr'], $case . ' diagnostic should remap to the typed local');
			$this->assertContains('Operation: scpp::required_cast<int_t>', $run['stderr'], $case . ' diagnostic should use required_cast');
		}
	}

	private function assertNestedTypedVectorLiteralsStabilizeRecursively(): void
	{
		$project = $this->root . '/nested_typed_vector_literals';
		$this->writeProject($project, []);
		$this->write($project . '/main.phs', <<<'PHS'
$grid vector<vector<int>> = [[1, 2], [3, 4]];
$sum int = 0;

foreach ($grid as $row) {
	foreach ($row as $value) {
		$sum = $sum + $value;
	}
}

echo $sum, "\n";

$words vector<vector<string>> = [["a", "b"], ["c"]];
echo $words[0][1], ":", $words[1][0], "\n";

$mixedGrid vector<vector<mixed>> = [[1, "x"], [null, true]];
echo count($mixedGrid[0]), ":", count($mixedGrid[1]), "\n";

$cube vector<vector<vector<int>>> = [[[1], [2]], [[3], [4]]];
echo $cube[0][1][0], ":", $cube[1][0][0], "\n";
PHS
 . "\n");

		$run = $this->runScpp($project, ['run'], true, 120);
		$this->assertSame(0, $run['exit_code'], 'nested typed vector literals should stabilize recursively');
		$this->assertContains("10\n", $run['stdout'], 'nested vector<int> literal should support foreach summing');
		$this->assertContains("b:c\n", $run['stdout'], 'nested vector<string> literal should stabilize');
		$this->assertContains("2:2\n", $run['stdout'], 'nested vector<mixed> literal should stabilize');
		$this->assertContains("2:3\n", $run['stdout'], 'deep nested vector<int> literal should stabilize recursively');
	}

	private function assertDecodedJsonArraysStabilizeIntoTypedVectors(): void
	{
		$project = $this->root . '/json_arrays_to_vectors';
		$this->writeProject($project, []);
		$this->write($project . '/main.phs', <<<'PHS'
$intsJson = json_decode("[1,2,3]");
$ints vector<int> = $intsJson;
echo $ints[0], ":", $ints[2], "\n";

$floatsJson = json_decode("[1.5,2.25]");
$floats vector<float> = $floatsJson;
echo $floats[0], ":", $floats[1], "\n";

$boolsJson = json_decode("[true,false]");
$bools vector<bool> = $boolsJson;
echo $bools[0], ":", $bools[1], "\n";

$stringsJson = json_decode("[\"a\",\"b\"]");
$strings vector<string> = $stringsJson;
echo $strings[0], ":", $strings[1], "\n";

$mixedJson = json_decode("[1,null,\"x\"]");
$mixed vector<mixed> = $mixedJson;
echo count($mixed), "\n";

$nestedJson = json_decode("[[1,2],[3,4]]");
$nested vector<vector<int>> = $nestedJson;
echo $nested[0][1], ":", $nested[1][0], "\n";
PHS
 . "\n");

		$run = $this->runScpp($project, ['run'], true, 120);
		$this->assertSame(0, $run['exit_code'], 'decoded JSON arrays should stabilize into typed vectors');
		$this->assertContains("1:3\n", $run['stdout'], 'decoded JSON array should stabilize into vector<int>');
		$this->assertContains("1.5:2.25\n", $run['stdout'], 'decoded JSON array should stabilize into vector<float>');
		$this->assertContains("1:\n", $run['stdout'], 'decoded JSON array should stabilize into vector<bool>');
		$this->assertContains("a:b\n", $run['stdout'], 'decoded JSON array should stabilize into vector<string>');
		$this->assertContains("3\n", $run['stdout'], 'decoded JSON array should stabilize into vector<mixed>');
		$this->assertContains("2:3\n", $run['stdout'], 'decoded JSON nested arrays should stabilize into nested typed vectors');

		foreach ([
			'json_object_to_vector' => [
				'json' => '{"items":[1,2]}',
				'expected' => 'vector_t',
			],
			'json_float_element_to_int_vector' => [
				'json' => '[1,2.5]',
				'expected' => 'int_t',
			],
		] as $case => $fixture) {
			$this->write($project . '/main.phs', '$valuesJson = json_decode(' . var_export($fixture['json'], true) . ');' . "\n" . <<<'PHS'
$values vector<int> = $valuesJson;
echo $values[0], "\n";
PHS
 . "\n");

			$failedRun = $this->runScpp($project, ['run'], false, 120);
			$this->assertNotSame(0, $failedRun['exit_code'], $case . ' should fail a required vector<int> typed local');
			$this->assertContains('Cannot convert value to required ' . $fixture['expected'] . '.', $failedRun['stderr'], $case . ' diagnostic should explain the failed required boundary');
			$this->assertContains('Operation: scpp::required_cast<' . $fixture['expected'] . '>', $failedRun['stderr'], $case . ' diagnostic should use required_cast for the failed boundary');
		}
	}

	private function assertJsonEncodeAcceptsTypedCollections(): void
	{
		$project = $this->root . '/json_encode_typed_collections';
		$this->writeProject($project, []);
		$this->write($project . '/main.phs', <<<'PHS'
$items vector<int> = [1, 2, 3];
echo json_encode($items), "\n";

$scores hash<int> = [];
$scores["a"] = 1;
$scores["b"] = 2;
echo json_encode($scores), "\n";

$nested vector<vector<string>> = [["a", "b"], ["c"]];
echo json_encode($nested), "\n";
PHS
 . "\n");

		$run = $this->runScpp($project, ['run'], true, 120);
		$this->assertSame(0, $run['exit_code'], 'json_encode should accept typed collections');
		$this->assertContains("[1,2,3]\n", $run['stdout'], 'json_encode should accept vector<int>');
		$this->assertContains("{\"a\":1,\"b\":2}\n", $run['stdout'], 'json_encode should accept hash<int>');
		$this->assertContains("[[\"a\",\"b\"],[\"c\"]]\n", $run['stdout'], 'json_encode should accept nested vectors');
	}

	private function assertExplicitNullStringCastStillSucceeds(): void
	{
		$project = $this->root . '/explicit_null_string_cast';
		$this->writeProject($project, []);
		$this->write($project . '/main.phs', <<<'PHS'
echo "[", (string) null, "]\n";
PHS
 . "\n");

		$run = $this->runScpp($project, ['run'], true, 120);
		$this->assertSame(0, $run['exit_code'], 'explicit null-to-string cast should still succeed');
		$this->assertContains("[]\n", $run['stdout'], 'explicit null-to-string cast should still stringify to an empty string');
	}

	private function assertExplicitIntCastsStillSucceed(): void
	{
		$project = $this->root . '/explicit_int_casts';
		$this->writeProject($project, []);
		$this->write($project . '/main.phs', <<<'PHS'
echo (int) 2.5, "\n";
echo (int) "42", "\n";
PHS
 . "\n");

		$run = $this->runScpp($project, ['run'], true, 120);
		$this->assertSame(0, $run['exit_code'], 'explicit int casts should still succeed');
		$this->assertContains("2\n42\n", $run['stdout'], 'explicit int casts should preserve configured conversion behavior');
	}

	private function assertRecursiveDebugRunFailsWithRuntimeDiagnostic(): void
	{
		$project = $this->root . '/recursive_guard';
		$this->writeProject($project, [
			'safety' => [
				'max_call_depth' => 32,
			],
		]);
		$this->writeRecursiveDiveProgram($project);

		$run = $this->runScpp($project, ['run', '--no-stan'], true, 120);
		$this->assertNotSame(0, $run['exit_code'], 'recursive debug run should fail');
		$this->assertContains('Maximum call depth exceeded', $run['stderr'], 'recursive debug run should report call-depth guard failure');
		$this->assertContains('main.phs:1', $run['stderr'], 'recursive debug run should remap to the source function');

		$report = json_decode($this->read($project . '/.prism/last_error.json'), true);
		if (!is_array($report)) {
			throw new RuntimeException('last_error.json should decode as an object');
		}
		$diagnostic = $report['diagnostics'][0] ?? null;
		if (!is_array($diagnostic)) {
			throw new RuntimeException('last_error.json should contain at least one runtime diagnostic');
		}
		$this->assertSame('max_call_depth_exceeded', $diagnostic['code'] ?? null, 'recursive runtime diagnostic should preserve error code');
	}

	private function assertRecursiveReleaseOptInFailsWithRuntimeDiagnostic(): void
	{
		$project = $this->root . '/recursive_release_guard';
		$this->writeProject($project, [
			'safety' => [
				'call_depth_guard' => true,
				'max_call_depth' => 24,
			],
		], 'release');
		$this->writeRecursiveDiveProgram($project);

		$run = $this->runScpp($project, ['run', '--no-stan'], true, 120);
		$this->assertNotSame(0, $run['exit_code'], 'recursive release opt-in run should fail');
		$this->assertContains('Maximum call depth exceeded while calling `dive` (limit 24).', $run['stderr'], 'release opt-in should use configured call-depth limit');
	}

	private function writeRecursiveDiveProgram(string $project): void
	{
		$this->write($project . '/main.phs', <<<'PHS'
function dive(int $n): int {
	return dive($n + 1);
}

echo dive(0), "\n";
PHS
 . "\n");
	}

	/** @param array<string,mixed> $runtimeOverrides */
	private function writeProject(string $project, array $runtimeOverrides, string $buildMode = 'debug'): void
	{
		$this->mkdir($project);
		$runtime = array_replace_recursive([
			'languages' => [
				'php' => ['profile' => 'strict'],
			],
			'modules' => ['json', 'filesystem'],
		], $runtimeOverrides);
		$this->write($project . '/prism.json', json_encode([
			'name' => basename($project),
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'generated_dir' => '.prism/generated',
			'cache_dir' => '.prism/cache',
			'build' => [
				'mode' => $buildMode,
			],
			'runtime' => $runtime,
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
	}

	/** @return array{exit_code:int,stdout:string,stderr:string} */
	private function runScpp(string $project, array $args, bool $buildRuntime, int $timeoutSeconds): array
	{
		$command = [PHP_BINARY, resolve_repo_root() . '/bin/scpp.php'];
		foreach ($args as $arg) {
			$command[] = $arg;
			if ($arg === 'run' && $buildRuntime) {
				$command[] = '--build-runtime';
			}
		}
		return $this->runCommand($command, $project, $timeoutSeconds);
	}

	/** @return array{exit_code:int,stdout:string,stderr:string} */
	private function runCommand(array $command, string $cwd, int $timeoutSeconds): array
	{
		$descriptor = [
			0 => ['file', '/dev/null', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$process = proc_open($command, $descriptor, $pipes, $cwd, scpp_build_process_environment([
			'SCPP_CXX_LAUNCHER' => ' ',
			'SCPP_CAPTURE_SUBPROCESS_OUTPUT' => '1',
		]));
		if (!is_resource($process)) {
			throw new RuntimeException('Failed to start command: ' . implode(' ', $command));
		}

		$stdout = '';
		$stderr = '';
		$started = microtime(true);
		$observedExitCode = null;
		foreach ([1, 2] as $index) {
			stream_set_blocking($pipes[$index], false);
		}
		while (true) {
			$status = proc_get_status($process);
			$stdout .= (string) stream_get_contents($pipes[1]);
			$stderr .= (string) stream_get_contents($pipes[2]);
			if (($status['running'] ?? false) !== true) {
				$exitCode = $status['exitcode'] ?? null;
				$observedExitCode = is_int($exitCode) ? $exitCode : null;
				break;
			}
			if ((microtime(true) - $started) > $timeoutSeconds) {
				proc_terminate($process);
				throw new RuntimeException('Timed out after ' . $timeoutSeconds . 's: ' . implode(' ', $command));
			}
			usleep(100000);
		}
		$stdout .= (string) stream_get_contents($pipes[1]);
		$stderr .= (string) stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$exitCode = proc_close($process);
		return [
			'exit_code' => $observedExitCode ?? (is_int($exitCode) ? $exitCode : 1),
			'stdout' => $stdout,
			'stderr' => $stderr,
		];
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

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (!str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' missing `' . $needle . '` in: ' . $haystack);
		}
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
			throw new RuntimeException($message . ' did not expect ' . var_export($actual, true));
		}
	}
}

exit((new ScppStrictSafetyEdgesTest())->run());
