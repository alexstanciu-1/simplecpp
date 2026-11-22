<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/compilation.php';
use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group, Incremental_Project};

function verify(bool $condition, string $message): void
{
	if (!$condition) throw new RuntimeException($message);
}

/** Require a source diagnostic, not a late C++ failure. */
function rejects(Closure $action, string $message): void
{
	try {
		$action();
	}
	catch (diagnostic|diagnostic_group $error) {
		verify(str_contains($error->getMessage(), $message), $error->getMessage());
		return;
	}
	throw new RuntimeException('Missing diagnostic: ' . $message);
}

/** @return string[] Native artifacts only; ignore host metadata paths. */
function artifacts(string $directory): array /** array<string,string> */
{
	$files = [];
	foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $item) {
		$path = $item->getPathname();
		if (in_array(pathinfo($path, PATHINFO_EXTENSION), ['hpp', 'cpp'], true)) $files[substr($path, strlen($directory) + 1)] = file_get_contents($path);
	}
	ksort($files);
	return $files;
}

function remove_tree(string $path): void
{
	foreach (new DirectoryIterator($path) as $item) {
		if (!$item->isDot()) $item->isDir() ? remove_tree($item->getPathname()) : unlink($item->getPathname());
	}
	rmdir($path);
}

$fixtures = [];
foreach (flavor::cases() as $flavor) {
	$signature = static fn(string $name): string => $flavor === flavor::cpp ? "int $name()" : "function $name(): int";
	$failure = $flavor === flavor::cpp ? 'class Failure : Exception {};' : 'class Failure extends Exception {}';
	$local = match ($flavor) {
		flavor::php => '$value = await read_value();return $value;',
		flavor::cpp => 'int value = await read_value();return value;',
		flavor::js => 'let value:int = await read_value();return value;',
	};
	$child = 'async ' . $signature('read_value') . '{await async_sleep_ms(1);return 4294967297;}';
	$parent = 'async ' . $signature('report') . '{return await read_value();}';
	$main = 'echo "before:", await report(), ":after";return 8;';
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('async', $source, $flavor), new entry_target(31));
	rejects(fn() => $prepare($child . 'read_value();return 8;'), 'Async call requires await');
	rejects(fn() => $prepare('await async_sleep_ms(1);return 8;'), 'requires an async function');
	rejects(fn() => $prepare(str_replace('async_sleep_ms(1)', 'async_sleep_ms("bad")', $child) . 'return 8;'), 'int64 milliseconds');
	rejects(fn() => $prepare(str_replace('int', 'string', $child) . 'return 8;'), 'int64 result');

	$directory = sys_get_temp_dir() . '/scpp-async-' . bin2hex(random_bytes(8)); mkdir($directory);
	try {
		$paths = array_map(fn($name) => $name . '.' . $flavor->name, ['main', 'parent', 'child']);
		file_put_contents("$directory/module.json", json_encode(['entry' => $paths[0], 'sources' => $paths]));
		file_put_contents("$directory/$paths[0]", $main);
		file_put_contents("$directory/$paths[1]", $parent);
		$target = new entry_target(31);
		$project = new Incremental_Project($directory, "$directory/out", $target);
		$caller = null;
		$steps = [
			[$child, null],
			[str_replace('4294967297', '4294967299', $child), null],
			[$signature('read_value') . '{return 9;}', 'Await requires an async function'],
			['', 'Unknown'],
			[$child, null],
			[$child, null],
			[$failure . 'async ' . $signature('read_value') . '{throw new Failure();}', null],
		];
		foreach ($steps as $i => [$source, $error]) {
			if ($i === 5) {
				file_put_contents("$directory/$paths[1]", 'async ' . $signature('report') . '{' . $local . '}');
				touch("$directory/$paths[1]", time() + $i);
			}
			file_put_contents("$directory/$paths[2]", $source); touch("$directory/$paths[2]", time() + $i);
			$fresh = new Incremental_Project($directory, "$directory/fresh", $target);
			if ($error !== null) {
				rejects(fn() => $project->run(), $error);
				rejects(fn() => $project->run(), $error);
				rejects(fn() => $fresh->run(), $error);
				continue;
			}
			$start = hrtime(true);
			$project->run();
			$elapsed = (hrtime(true) - $start) / 1e6;
			$fresh->run();
			$caller ??= $project->session()->entry->entry->ast;
			verify($caller === $project->session()->entry->entry->ast, 'Entry reparsed');
			$files = artifacts("$directory/out");
			$fresh_files = artifacts("$directory/fresh");
			verify($files === $fresh_files, 'Fresh build differs');
			foreach ($files as $path => $text) {
				verify(!preg_match('/\btemplate\s*<|async_core\.hpp/', $text), 'Unexpected template or scheduler include');
				if (str_ends_with($path, '.hpp') || str_contains($text, 'wait_for(&')) {
					verify(!str_contains($text, '#include <scpp/async_int64.hpp>'), 'Coroutine header leaked');
				}
			}
			verify($project->run()['preparation']['prepared'] === 0, 'No-op prepared bodies');
			$fixtures[$flavor->name . '_' . $i] = ['files' => $files, 'fresh_files' => $fresh_files, 'compiler_ms' => $elapsed,
				'exit' => $i === 6 ? 1 : 8, 'stderr' => $i === 6 ? "Exception\n" : '',
				'stdout_hex' => bin2hex($i === 6 ? 'before:' : 'before:' . ($i === 1 ? '4294967299' : '4294967297') . ':after')];
		}
	} finally {
		remove_tree($directory);
	}
}
if (isset($argv[1])) file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR));
echo "Async cross-file incremental recovery passed\n";
