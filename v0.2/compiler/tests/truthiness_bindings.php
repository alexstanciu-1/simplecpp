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
	$directory = sys_get_temp_dir() . '/scpp-truthiness-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', json_encode(['sources' => ['main.' . $flavor->name, 'provider.' . $flavor->name]], JSON_THROW_ON_ERROR));
		file_put_contents($directory . '/main.' . $flavor->name, 'if (predicate()) { echo "T"; } else { echo "F"; } return 8;');
		$path = $directory . '/provider.' . $flavor->name;
		$stamp = time();
		$write = static function (string $type, string $value) use ($path, $flavor, &$stamp): void {
			$head = $flavor === flavor::cpp ? "$type predicate()" : "function predicate(): $type";
			file_put_contents($path, $type === '' ? '' : $head . '{ echo "P"; return ' . $value . '; }');
			touch($path, ++$stamp);
		};
		$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31));
		$write('bool', 'true');
		$project->run();
		$caller = $project->session()->entry->entry->ast;
		foreach ([['string', '""', 'F'], ['string', '"0"', 'F'], ['string', '"00"', 'T'],
			['string', '"\0"', 'T'], ['int', '0', 'F'], ['int', '8', 'T'], ['float', '-0.0', 'F'],
			['float', '1.5', 'T'], ['null', 'null', null], ['bool', 'false', 'F'],
			['byte', '8', 'T'], ['string', '"recovered"', 'T'], ['', '', null], ['bool', 'true', 'T']]
			as $index => [$type, $value, $output]) {
			$write($type, $value);
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			if ($output === null) {
				$message = $type === '' ? 'Unknown function' : 'Unsupported condition type';
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $fresh->run(), $message);
			} else {
				$project->run();
				$fresh->run();
				verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Condition recovery differs from fresh output');
				$fixtures[$flavor->name . '_' . $index] = ['files' => artifacts($directory . '/out'), 'stdout_hex' => bin2hex('P' . $output)];
			}
			verify($project->session()->entry->entry->ast === $caller, 'Unchanged condition caller reparsed');
		}
		verify($project->run()['preparation']['prepared'] === 0, 'No-op prepared bodies');
	} finally {
		remove_tree($directory);
	}
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Truthiness admission, single evaluation and incremental/fresh recovery passed in all flavors\n";
