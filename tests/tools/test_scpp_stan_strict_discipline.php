<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

use Scpp\S2S\Stan\StanWorkspaceSession;

final class ScppStanStrictDisciplineTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_stan_strict_discipline_' . getmypid() . '_' . bin2hex(random_bytes(4)));
	}

	public function run(): int
	{
		try {
			$this->assertDependencyEditsAffectRootStanFingerprint();

			$project = $this->root . '/app';
			$this->writeProject($project, <<<'PHS'
function main(): void
{
	$text string = fs_get("missing-language-case-file.txt");
	echo strlen($text), "\n";
}

main();
PHS
 . "\n");

			$session = new StanWorkspaceSession();
			$diagnostics = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(1, $diagnostics['warning_count'] ?? null, 'unchecked wrapper boundary should produce one STAN finding');
			$first = $diagnostics['diagnostics'][0] ?? null;
			if (!is_array($first)) {
				throw new RuntimeException('unchecked wrapper boundary diagnostic should be present');
			}
			$this->assertSame('stan.unchecked_wrapper_boundary', $first['code'] ?? null, 'diagnostic code should be stable');
			$this->assertSame(3, $first['line'] ?? null, 'diagnostic should point at the typed boundary line');
			$this->assertContains('Unchecked wrapper result assigned to required `string` local `$text`', (string) ($first['message'] ?? ''), 'diagnostic should describe the unchecked wrapper boundary');
			$this->assertContains('Use `take(...)`, `isset(...)`, or an explicit false/null/error-state check', (string) ($first['message'] ?? ''), 'diagnostic should recommend strict wrapper handling');

			$this->writeProject($project, <<<'PHS'
function main(): void
{
	$text string = "";
	if (take($text, fs_get("missing-language-case-file.txt"))) {
		echo strlen($text), "\n";
	}
}

main();
PHS
 . "\n");

			$checked = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(0, $checked['warning_count'] ?? null, 'take(...) wrapper handling should stay clean');

			$this->writeProject($project, <<<'PHS'
function main(): void
{
	$row = json_decode("{\"name\":\"Ada\"}");
	$name string = $row["name"];
	echo $name, "\n";
}

main();
PHS
 . "\n");

			$dynamic = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(1, $dynamic['warning_count'] ?? null, 'dynamic JSON typed boundary should produce one STAN finding');
			$dynamicDiagnostic = $dynamic['diagnostics'][0] ?? null;
			if (!is_array($dynamicDiagnostic)) {
				throw new RuntimeException('dynamic shape boundary diagnostic should be present');
			}
			$this->assertSame('stan.dynamic_shape_boundary', $dynamicDiagnostic['code'] ?? null, 'dynamic diagnostic code should be stable');
			$this->assertSame(4, $dynamicDiagnostic['line'] ?? null, 'dynamic diagnostic should point at the required typed local');
			$this->assertContains('Dynamic value assigned to required `string` local `$name`', (string) ($dynamicDiagnostic['message'] ?? ''), 'dynamic diagnostic should describe the required boundary');
			$this->assertContains('Guard the field with `isset(...)`', (string) ($dynamicDiagnostic['message'] ?? ''), 'dynamic diagnostic should recommend a shape guard');

			$this->writeProject($project, <<<'PHS'
function main(): void
{
	$row = json_decode("{\"name\":\"Ada\"}");
	if (isset($row["name"])) {
		$name string = (string) $row["name"];
		echo $name, "\n";
	}
}

main();
PHS
 . "\n");

			$guardedDynamic = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(0, $guardedDynamic['warning_count'] ?? null, 'guarded dynamic JSON extraction with an explicit cast should stay clean');

			echo "PASS: scpp stan strict discipline\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function assertDependencyEditsAffectRootStanFingerprint(): void
	{
		$dep = $this->root . '/dep';
		$app = $this->root . '/dep_app';
		$this->writeProject($dep, <<<'PHS'
/** @lib-export */
function dep_value(): int
{
	return 1;
}
PHS
 . "\n");
		$this->writeProject($app, <<<'PHS'
function main(): void
{
	echo dep_value(), "\n";
}

main();
PHS
 . "\n", ['../dep']);

		$before = compute_stan_source_fingerprint($app, $app . '/prism.json');
		$this->write($dep . '/main.phs', <<<'PHS'
/** @lib-export */
function dep_value(): int
{
	return 2;
}
PHS
 . "\n");
		$after = compute_stan_source_fingerprint($app, $app . '/prism.json');
		if ($before === $after) {
			throw new RuntimeException('dependency-only source edits should change the root STAN source fingerprint');
		}
	}

	/** @param list<string> $dependencies */
	private function writeProject(string $project, string $mainSource, array $dependencies = []): void
	{
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'config_version' => 1,
			'project_name' => 'stan-strict-discipline',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'generated_dir' => '.prism/generated',
			'cache_dir' => '.prism/cache',
			'dependencies' => $dependencies,
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', $mainSource);
	}

	private function write(string $path, string $contents): void
	{
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
}

exit((new ScppStanStrictDisciplineTest())->run());
