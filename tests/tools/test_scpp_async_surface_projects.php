<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppAsyncSurfaceProjectsTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_async_surface_projects_' . getmypid() . '_' . bin2hex(random_bytes(4)));
		register_shutdown_function(function (): void {
			$this->removeTree($this->root);
		});
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

		foreach ($this->phsCases() as $index => $case) {
			$this->runCase('phs', $index + 1, $case);
		}
		foreach ($this->jssCases() as $index => $case) {
			$this->runCase('jss', $index + 1, $case);
		}

		echo "PASS: scpp async surface projects\n";
		return 0;
	}

	/** @param array{name:string,source:string,stdout:string,generated:list<string>} $case */
	private function runCase(string $language, int $index, array $case): void
	{
		$project = $this->root . '/' . $language . '_' . str_pad((string) $index, 2, '0', STR_PAD_LEFT) . '_' . $case['name'];
		$entrypoint = $language === 'jss' ? 'main.jss' : 'main.phs';
		$this->writeProject($project, $entrypoint, $case['source']);

		$build = scpp_run_build_service($project, $project . '/prism.json', [
			'compile_runtime' => true,
			'compile_dependencies' => false,
		]);
		$this->assertSame(true, $build['ok'], strtoupper($language) . ' async project `' . $case['name'] . "` should build:\n" . (string) ($build['output'] ?? '') . "\n" . (string) ($build['error'] ?? ''));
		$this->assertNotContains('Static Analysis:', (string) ($build['output'] ?? ''), strtoupper($language) . ' async project `' . $case['name'] . "` should not emit STAN warnings:\n" . (string) ($build['output'] ?? ''));

		$run = scpp_run_binary_service($project, $project . '/.prism/build/main', [], $build);
		$this->assertSame(0, (int) ($run['exit_code'] ?? 1), strtoupper($language) . ' async project `' . $case['name'] . "` should run:\nSTDOUT:\n" . (string) ($run['stdout'] ?? '') . "\nSTDERR:\n" . (string) ($run['stderr'] ?? ''));
		$this->assertSame($case['stdout'], (string) ($run['stdout'] ?? ''), strtoupper($language) . ' async project `' . $case['name'] . '` stdout mismatch');

		$generatedPath = $language === 'jss'
			? $project . '/.prism/jss/main.phs'
			: $project . '/.prism/generated/main.cpp';
		$generated = $this->read($generatedPath);
		foreach ($case['generated'] as $needle) {
			$this->assertContains($needle, $generated, strtoupper($language) . ' async project `' . $case['name'] . '` generated output check');
		}
	}

	private function writeProject(string $project, string $entrypoint, string $source): void
	{
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'name' => basename($project),
			'entrypoint' => $entrypoint,
			'build_dir' => '.prism/build',
			'generated_dir' => '.prism/generated',
			'cache_dir' => '.prism/cache',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/' . $entrypoint, rtrim($source) . "\n");
	}

	/** @return list<array{name:string,source:string,stdout:string,generated:list<string>}> */
	private function phsCases(): array
	{
		return [
			[
				'name' => 'direct_int',
				'source' => <<<'PHS'
async function value_one(): int {
	return 1;
}
echo await value_one(), "\n";
PHS,
				'stdout' => "1\n",
				'generated' => ['scpp::async_core::task<int_t> value_one()', 'co_return', 'sync_wait(value_one())'],
			],
			[
				'name' => 'typed_assignment',
				'source' => <<<'PHS'
async function value_two(): int {
	await async_sleep_ms(0);
	return 2;
}
$value int = await value_two();
echo $value, "\n";
PHS,
				'stdout' => "2\n",
				'generated' => ['co_await scpp::async_core::sleep_ms', 'sync_wait(value_two())'],
			],
			[
				'name' => 'string_result',
				'source' => <<<'PHS'
async function label(): string {
	return "async";
}
echo await label(), "\n";
PHS,
				'stdout' => "async\n",
				'generated' => ['scpp::async_core::task<string_t> label()', 'sync_wait(label())'],
			],
			[
				'name' => 'bool_branch',
				'source' => <<<'PHS'
async function yes(): bool {
	return true;
}
if (await yes()) {
	echo "yes\n";
}
PHS,
				'stdout' => "yes\n",
				'generated' => ['scpp::async_core::task<bool_t> yes()', 'sync_wait(yes())'],
			],
			[
				'name' => 'float_result',
				'source' => <<<'PHS'
async function ratio(): float {
	return 1.5;
}
echo await ratio(), "\n";
PHS,
				'stdout' => "1.5\n",
				'generated' => ['scpp::async_core::task<float_t> ratio()', 'sync_wait(ratio())'],
			],
			[
				'name' => 'parameter_result',
				'source' => <<<'PHS'
async function add_one(int $value): int {
	return $value + 1;
}
echo await add_one(5), "\n";
PHS,
				'stdout' => "6\n",
				'generated' => ['add_one(int_t value)', 'sync_wait(add_one'],
			],
			[
				'name' => 'arithmetic_await',
				'source' => <<<'PHS'
async function base(): int {
	return 7;
}
echo (await base()) + 3, "\n";
PHS,
				'stdout' => "10\n",
				'generated' => ['sync_wait(base())', '+ static_cast<int_t>(3)'],
			],
			[
				'name' => 'two_awaits',
				'source' => <<<'PHS'
async function left(): int {
	return 4;
}
async function right(): int {
	return 5;
}
echo await left(), ",", await right(), "\n";
PHS,
				'stdout' => "4,5\n",
				'generated' => ['sync_wait(left())', 'sync_wait(right())'],
			],
			[
				'name' => 'loop_after_sleep',
				'source' => <<<'PHS'
async function count_to_three(): int {
	await async_sleep_ms(1);
	$total int = 0;
	for ($i int = 1; $i <= 3; $i++) {
		$total = $total + $i;
	}
	return $total;
}
echo await count_to_three(), "\n";
PHS,
				'stdout' => "6\n",
				'generated' => ['co_await scpp::async_core::sleep_ms', 'sync_wait(count_to_three())'],
			],
			[
				'name' => 'void_timer',
				'source' => <<<'PHS'
async function tick(): void {
	await async_sleep_ms(0);
	return;
}
async_wait(tick());
echo "done\n";
PHS,
				'stdout' => "done\n",
				'generated' => ['scpp::async_core::task<void> tick()', 'sync_wait(tick())'],
			],
		];
	}

	/** @return list<array{name:string,source:string,stdout:string,generated:list<string>}> */
	private function jssCases(): array
	{
		return [
			[
				'name' => 'direct_int',
				'source' => <<<'JSS'
async function valueOne(): int {
    return 1;
}
print(await valueOne(), "\n");
JSS,
				'stdout' => "1\n",
				'generated' => ['/** @async */', 'function valueOne(): int', 'async_wait(valueOne())'],
			],
			[
				'name' => 'typed_assignment',
				'source' => <<<'JSS'
async function valueTwo(): int {
    await async_sleep_ms(0);
    return 2;
}
let value: int = await valueTwo();
print(value, "\n");
JSS,
				'stdout' => "2\n",
				'generated' => ['async_sleep_ms(0);', '$value int = async_wait(valueTwo());'],
			],
			[
				'name' => 'string_result',
				'source' => <<<'JSS'
async function label(): string {
    return "async";
}
print(await label(), "\n");
JSS,
				'stdout' => "async\n",
				'generated' => ['function label(): string', 'async_wait(label())'],
			],
			[
				'name' => 'bool_branch',
				'source' => <<<'JSS'
async function yes(): bool {
    return true;
}
if (await yes()) {
    print("yes\n");
}
JSS,
				'stdout' => "yes\n",
				'generated' => ['if (async_wait(yes())) {'],
			],
			[
				'name' => 'float_result',
				'source' => <<<'JSS'
async function ratio(): float {
    return 1.5;
}
print(await ratio(), "\n");
JSS,
				'stdout' => "1.5\n",
				'generated' => ['function ratio(): float', 'async_wait(ratio())'],
			],
			[
				'name' => 'parameter_result',
				'source' => <<<'JSS'
async function addOne(value: int): int {
    return value + 1;
}
print(await addOne(5), "\n");
JSS,
				'stdout' => "6\n",
				'generated' => ['function addOne(int $value): int', 'async_wait(addOne(5))'],
			],
			[
				'name' => 'arithmetic_await',
				'source' => <<<'JSS'
async function base(): int {
    return 7;
}
print((await base()) + 3, "\n");
JSS,
				'stdout' => "10\n",
				'generated' => ['async_wait(base()) + 3'],
			],
			[
				'name' => 'two_awaits',
				'source' => <<<'JSS'
async function left(): int {
    return 4;
}
async function right(): int {
    return 5;
}
print(await left(), ",", await right(), "\n");
JSS,
				'stdout' => "4,5\n",
				'generated' => ['async_wait(left())', 'async_wait(right())'],
			],
			[
				'name' => 'loop_after_sleep',
				'source' => <<<'JSS'
async function countToThree(): int {
    await async_sleep_ms(1);
    let total: int = 0;
    for (let i: int = 1; i <= 3; i++) {
        total = total + i;
    }
    return total;
}
print(await countToThree(), "\n");
JSS,
				'stdout' => "6\n",
				'generated' => ['async_sleep_ms(1);', 'async_wait(countToThree())'],
			],
			[
				'name' => 'void_timer',
				'source' => <<<'JSS'
async function tick(): void {
    await async_sleep_ms(0);
    return;
}
await tick();
print("done\n");
JSS,
				'stdout' => "done\n",
				'generated' => ['function tick(): void', 'async_wait(tick());'],
			],
		];
	}

	private function mkdir(string $path): void
	{
		if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
			throw new RuntimeException('Failed to create directory ' . $path);
		}
	}

	private function write(string $path, string $contents): void
	{
		$this->mkdir(dirname($path));
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

	private function removeTree(string $path): void
	{
		if ($path === '' || !is_dir($path)) {
			return;
		}
		$it = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ($it as $item) {
			$item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
		}
		rmdir($path);
	}

	private function assertSame(mixed $expected, mixed $actual, string $message): void
	{
		if ($expected !== $actual) {
			throw new RuntimeException($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . '.');
		}
	}

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (!str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' Missing `' . $needle . '`.');
		}
	}

	private function assertNotContains(string $needle, string $haystack, string $message): void
	{
		if (str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' Unexpected `' . $needle . '`.');
		}
	}
}

exit((new ScppAsyncSurfaceProjectsTest())->run());
