<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

use Scpp\S2S\Jss\JssTranspiler;

final class ScppJssSamplesTest
{
	public function run(): void
	{
		$root = normalize_path(__DIR__ . '/../../samples/jss');
		if (!is_dir($root)) {
			throw new RuntimeException('Missing JSS samples directory: ' . $root);
		}
		$cases = $this->findCases($root);
		if ($cases === []) {
			throw new RuntimeException('No JSS sample cases found.');
		}
		$this->assertNoOrphanExpectedFiles($root);
		$transpiler = new JssTranspiler();
		foreach ($cases as $case) {
			$source = file_get_contents($case['jss']);
			$expected = file_get_contents($case['expected']);
			if (!is_string($source) || !is_string($expected)) {
				throw new RuntimeException('Failed to read JSS sample case: ' . $case['jss']);
			}
			$actual = $transpiler->transpileToPhs($source);
			if ($actual !== $expected) {
				throw new RuntimeException(
					'JSS sample mismatch: '
					. normalize_config_path(relative_path($root, $case['jss']))
					. "\nExpected:\n"
					. $expected
					. "\nActual:\n"
					. $actual
				);
			}
			$classifiedActual = $transpiler->transpileToPhsWithStanClassifications($source, $case['jss']);
			if ($classifiedActual !== $expected) {
				throw new RuntimeException(
					'JSS classified sample mismatch: '
					. normalize_config_path(relative_path($root, $case['jss']))
					. "\nExpected:\n"
					. $expected
					. "\nActual:\n"
					. $classifiedActual
				);
			}
		}
		echo 'PASS: scpp jss samples (' . count($cases) . " cases)\n";
	}

	/** @return list<array{jss:string,expected:string}> */
	private function findCases(string $root): array
	{
		$cases = [];
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
		);
		foreach ($iterator as $fileInfo) {
			if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
				continue;
			}
			if (strtolower($fileInfo->getExtension()) !== 'jss') {
				continue;
			}
			$jss = normalize_path($fileInfo->getPathname());
			$expected = substr($jss, 0, -4) . '.expected.phs';
			if (!is_file($expected)) {
				throw new RuntimeException('Missing expected PHS for JSS sample: ' . $jss);
			}
			$cases[] = ['jss' => $jss, 'expected' => $expected];
		}
		usort($cases, static fn (array $a, array $b): int => strcmp($a['jss'], $b['jss']));
		return $cases;
	}

	private function assertNoOrphanExpectedFiles(string $root): void
	{
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
		);
		foreach ($iterator as $fileInfo) {
			if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
				continue;
			}
			$path = normalize_path($fileInfo->getPathname());
			if (!str_ends_with($path, '.expected.phs')) {
				continue;
			}
			$jss = substr($path, 0, -strlen('.expected.phs')) . '.jss';
			if (!is_file($jss)) {
				throw new RuntimeException('Missing JSS source for expected PHS sample: ' . $path);
			}
		}
	}
}

(new ScppJssSamplesTest())->run();
