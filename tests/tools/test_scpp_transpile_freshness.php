<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppTranspileFreshnessTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_transpile_freshness_' . getmypid() . '_' . bin2hex(random_bytes(4)));
	}

	public function run(): int
	{
		try {
			$this->assertGeneratedArtifactContentMismatchForcesTranspile();
			echo "PASS: scpp transpile freshness\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function assertGeneratedArtifactContentMismatchForcesTranspile(): void
	{
		$source = $this->root . '/main.phs';
		$header = $this->root . '/.prism/generated/main.hpp';
		$cpp = $this->root . '/.prism/generated/main.cpp';
		$this->write($source, "echo \"ok\\n\";\n");
		$this->write($header, "old header\n");
		$this->write($cpp, "old source\n");

		$sourceHash = hash_file('sha256', $source);
		if (!is_string($sourceHash)) {
			throw new RuntimeException('Failed to hash test source.');
		}
		$meta = [
			'size' => filesize($source),
			'mtime' => filemtime($source),
			'content_hash' => $sourceHash,
		];
		$previous = [
			'size' => $meta['size'],
			'mtime' => $meta['mtime'],
			'content_hash' => $meta['content_hash'],
			'generator_signature' => 'sig',
			'generated_interface_hash' => hash('sha256', "new header\n"),
			'generated_implementation_hash' => hash('sha256', "new source\n"),
			'emit_program_entry' => false,
			'has_export_manifest' => false,
		];

		$reasons = collect_transpile_reasons(
			$previous,
			$meta,
			'sig',
			$header,
			$cpp,
			false,
			$this->root . '/.prism/generated/main.exports.json',
		);

		$this->assertContains('generated header content changed', $reasons, 'stale generated header content should force transpilation');
		$this->assertContains('generated source content changed', $reasons, 'stale generated source content should force transpilation');
	}

	private function write(string $path, string $contents): void
	{
		$dir = dirname($path);
		if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
			throw new RuntimeException('Failed to create directory: ' . $dir);
		}
		if (file_put_contents($path, $contents) === false) {
			throw new RuntimeException('Failed to write file: ' . $path);
		}
	}

	private function assertContains(string $needle, array $haystack, string $message): void
	{
		if (!in_array($needle, $haystack, true)) {
			throw new RuntimeException($message . ' Got: ' . implode(', ', array_map('strval', $haystack)));
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
			} else {
				@unlink($child);
			}
		}
		@rmdir($path);
	}
}

exit((new ScppTranspileFreshnessTest())->run());
