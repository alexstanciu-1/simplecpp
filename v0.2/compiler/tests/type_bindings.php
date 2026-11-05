<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{flavor, entry_target, diagnostic, diagnostic_group, Incremental_Project};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

/** Require semantic diagnostics before native compilation. */
function rejects(Closure $action, string $message): void
{
	try {
		$action();
	}
	catch (diagnostic|diagnostic_group $error) {
		verify(str_contains($error->getMessage(), $message), $error->getMessage());
		return;
	}
	throw new RuntimeException('Expected diagnostic: ' . $message);
}

/** Read only emitted native artifacts for incremental/fresh equivalence. @return string[] */
function artifacts(string $directory): array /** array<string,string> */
{
	$files = [];
	foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $item) {
		$path = $item->getPathname();
		if (in_array(pathinfo($path, PATHINFO_EXTENSION), ['cpp', 'hpp'], true)) {
			$files[substr($path, strlen($directory) + 1)] = file_get_contents($path);
		}
	}
	ksort($files);
	return $files;
}

/** Remove the isolated proof tree, including emitted bundles. */
function remove_tree(string $path): void
{
	foreach (new DirectoryIterator($path) as $item) {
		if (!$item->isDot()) {
			$item->isDir() ? remove_tree($item->getPathname()) : unlink($item->getPathname());
		}
	}
	rmdir($path);
}

$fixtures = [];
foreach (flavor::cases() as $flavor) {
	foreach (['byte', 'int', 'string'] as $destination) {
		$directory = sys_get_temp_dir() . '/scpp-type-bindings-' . bin2hex(random_bytes(8));
		mkdir($directory);
		try {
			file_put_contents($directory . '/module.json', json_encode(['sources' => ['main.' . $flavor->name, 'provider.' . $flavor->name]], JSON_THROW_ON_ERROR));
			$expression = $destination === 'string' ? 'value()' : '(' . $destination . ') value()';
			$output = $destination === 'byte' ? '(int) (' . $expression . ')' : $expression;
			file_put_contents($directory . '/main.' . $flavor->name, 'echo ' . $output . '; return 8;');
			$path = $directory . '/provider.' . $flavor->name;
			$stamp = time();
			$write = static function (string $type, string $expression) use ($path, $flavor, &$stamp): void {
				$head = $flavor === flavor::cpp ? $type . ' value()' : 'function value(): ' . $type;
				file_put_contents($path, $head . '{ return ' . $expression . '; }');
				touch($path, ++$stamp);
			};
			$type = match ($destination) {
				'byte' => 'int', 'int' => 'float', default => 'string',
			};
			$initial = match ($destination) {
				'byte' => '8', 'int' => '8.75', default => '"A"',
			};
			$changed = match ($destination) {
				'byte' => '9', 'int' => '9.75', default => '"B"',
			};
			$write($type, $initial);
			$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31));
			$project->run();
			$caller = $project->session()->entry->entry->ast;
			foreach (['edit', 'remove', 'recover'] as $step) {
				if ($step === 'remove') {
					file_put_contents($path, '');
					touch($path, ++$stamp);
				}
				else $write($type, $changed);
				$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
				if ($step === 'remove') {
					rejects(fn() => $project->run(), '');
					rejects(fn() => $project->run(), '');
					rejects(fn() => $fresh->run(), '');
				} else {
					$project->run();
					$fresh->run();
					verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Binding recovery differs from fresh output');
					$fixtures[$flavor->name . '_' . $destination] = ['files' => artifacts($directory . '/out'), 'stdout_hex' => bin2hex($destination === 'string' ? 'B' : '9')];
				}
				verify($project->session()->entry->entry->ast === $caller, 'Unchanged caller AST replaced');
			}
			verify($project->run()['preparation']['prepared'] === 0, 'No-op prepared bodies');
		} finally {
			remove_tree($directory);
		}
	}
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Type bindings: edits, deletion, repeated failure and recovery match fresh compilation in all flavors\n";
