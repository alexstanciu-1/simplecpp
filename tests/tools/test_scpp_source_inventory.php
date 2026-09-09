<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppSourceInventoryTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_source_inventory_' . getmypid() . '_' . bin2hex(random_bytes(4)));
	}

	public function run(): int
	{
		try {
			$this->assertProjectStateAndVcsDirectoriesArePruned();
			$this->assertVisibleConflictingSourcesStillFail();
			echo "PASS: scpp source inventory\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function assertProjectStateAndVcsDirectoriesArePruned(): void
	{
		$project = $this->root . '/app';
		$this->write($project . '/main.phs', "echo \"ok\\n\";\n");
		$this->write($project . '/src/helper.php', "function helper(): int { return 1; }\n");
		$this->write($project . '/src/view.jss', "print(\"ok\");\n");
		$this->write($project . '/.prism/generated/main.phs', "echo \"ignored\\n\";\n");
		$this->write($project . '/.git/hooks/generated.php', "echo \"ignored\\n\";\n");
		$this->write($project . '/.hg/cache/generated.php', "echo \"ignored\\n\";\n");
		$this->write($project . '/.svn/tmp/generated.php', "echo \"ignored\\n\";\n");

		$sources = array_map(
			static fn (string $path): string => normalize_config_path(relative_path($project, $path)),
			collect_project_php_files($project)
		);
		$this->assertSame(['main.phs', 'src/helper.php', 'src/view.jss'], $sources, 'source inventory should keep supported source extensions while ignoring project state and VCS metadata directories');
		$this->assertTrue(should_prune_project_source_inventory_directory($project, $project . '/.prism'), 'project .prism should be pruned before recursive descent');
		$this->assertTrue(should_prune_project_source_inventory_directory($project, $project . '/.git'), 'project .git should be pruned before recursive descent');
	}

	private function assertVisibleConflictingSourcesStillFail(): void
	{
		$project = $this->root . '/conflict';
		$this->write($project . '/src/item.phs', "function item_value(): int { return 1; }\n");
		$this->write($project . '/src/item.php', "function item_value_compat(): int { return 2; }\n");

		try {
			collect_project_php_files($project);
		} catch (ScppCliException $exception) {
			$this->assertContains('Conflicting source files detected', $exception->getMessage(), 'visible source conflicts should still be reported');
			return;
		}
		throw new RuntimeException('visible .phs/.php source conflict should fail');
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
}

exit((new ScppSourceInventoryTest())->run());
