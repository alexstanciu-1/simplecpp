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

	rejects(fn() => $prepare($child . 'if (false && await read_value()) {return 1;}return 0;'), 'Await requires');
	rejects(fn() => $prepare($child . 'async ' . $signature('invalid') . '{return 1 + (true && await read_value());}return 0;'), 'Await requires');

	rejects(fn() => $prepare($child . 'await read_value();return 0;'), 'Await requires');
	rejects(fn() => $prepare($child . 'if (await read_value()) {return 1;}return 0;'), 'Await requires');

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
		$throwing = $steps[6][0];
		$loud = str_replace('return 4294967297;', 'echo "R";return 4294967297;', $child);
		$conditions = 'if (false && await read_value()) {return 90;}if (true || await read_value()) {echo "S";}';
		$taken = 'if (true && await read_value()) {echo "A";}if (false || await read_value()) {echo "B";}return 7;';
		$initial = match ($flavor) {
			flavor::php => '$n int = 9;', flavor::cpp => 'int n = 9;', flavor::js => 'let n:int = 9;',
		};
		$name = $flavor === flavor::php ? '$n' : 'n';
		$ordinary = $initial . 'await read_value();echo "D";if (await read_value()) {'
			. $name . ' = await read_value();}return ' . $name . ';';
		$short = [
			// Parent body, child, diagnostic, stdout after the caller's prefix, exception, rebuilt objects.
			[$conditions . 'return 7;', $throwing, null, 'S7:after', false, 1],
			[$conditions . $taken, $loud, null, 'SRARB7:after', false, 2],
			[$conditions . $taken, $signature('read_value') . '{return 9;}', 'Await requires an async function', '', false, 0],
			[$conditions . $taken, $child, null, 'SAB7:after', false, 1],
			[$conditions . $taken, $throwing, null, 'S', true, 1],
			['if (false || await read_value()) {return 99;}return 0;', $throwing, null, '', true, 1],
			[$conditions . $taken, str_replace('4294967297', '0', $loud), null, 'SRR7:after', false, 2],
			[$ordinary, $child, null, 'D4294967297:after', false, 2],
			[$ordinary, str_replace('4294967297', '0', $loud), null, 'RDR9:after', false, 1],
			[$ordinary, $throwing, null, '', true, 1],
			[$ordinary, $signature('read_value') . '{return 1;}', 'Await requires an async function', '', false, 0],
			[$ordinary, $loud, null, 'RDRR4294967297:after', false, 1],
		];
		foreach ($short as $case) $steps[] = [$case[1], $case[2]];
		foreach ($steps as $i => [$source, $error]) {
			$case = $short[$i - 7] ?? null;
			if ($case !== null) {
				$body = 'async ' . $signature('report') . '{' . $case[0] . '}';
				if (file_get_contents("$directory/$paths[1]") !== $body) {
					file_put_contents("$directory/$paths[1]", $body);
					touch("$directory/$paths[1]", time() + $i);
				}
			}
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
				'objects' => $case[5] ?? ($i === 0 ? 4 : 1),
				'exit' => ($case[4] ?? ($i === 6)) ? 1 : 8, 'stderr' => ($case[4] ?? ($i === 6)) ? "Exception\n" : '',
				'stdout_hex' => bin2hex('before:' . ($case[3] ?? ($i === 6 ? '' : ($i === 1 ? '4294967299' : '4294967297') . ':after')))];
		}
		// Independent parameter project. Keep each successful generation for native/fresh execution.
		$parameter = static fn(string $n): string => match ($flavor) {
			flavor::php => 'int $' . $n, flavor::js => $n . ':int', flavor::cpp => 'int ' . $n,
		};
		$v = static fn(string $n): string => ($flavor === flavor::php ? '$' : '') . $n;
		$fn = static fn(string $n, array $params): string => str_replace('()', '(' . implode(',', array_map($parameter, $params)) . ')', $signature($n));
		$sum = 'async ' . $fn('sum', ['x','y']) . '{await async_sleep_ms(1);return ' . $v('x') . '+' . $v('y') . ';}';
		$mark = $fn('mark', ['x']) . '{echo "M";return ' . $v('x') . ';}';
		$args = 'mark(' . $v('v') . '),mark(true)';
		$report = $mark . 'async ' . $fn('report', ['v']) . '{if (false && await sum(' . $args . ')) {return 0;}return await sum(' . $args . ');}';
		$entry = 'echo await report(4294967297);return 8;';
		$extra = str_replace($parameter('y') . ')', $parameter('y') . ',' . $parameter('z') . ')', $sum);
		$fixed = str_replace($args, $args . ',0', $report);
		foreach (['string', 'default', 'reference'] as $bad) {
			$param = match ($bad) {
				'string' => str_replace('int', 'string', $parameter('x')),
				'default' => $parameter('x') . '=1',
				'reference' => match ($flavor) {
					flavor::php => 'int &$x', flavor::cpp => 'int &x', flavor::js => '&x:int',
				},
			};
			rejects(fn() => $prepare('async ' . str_replace('()', '(' . $param . ')', $signature('bad')) . '{return 1;}return 0;'), 'Async parameters require');
		}
		$project = new Incremental_Project($directory, "$directory/params", $target);
		$steps = [
			[$report, $sum, $entry, null, 'MM4294967298', 4],
			[$report, $extra, $entry, 'Argument count mismatch', '', 0],
			[$fixed, $extra, $entry, null, 'MM4294967298', 2],
			[$fixed, '', $entry, 'Unknown', '', 0],
			[$report, $sum, $entry, null, 'MM4294967298', 2],
			[$report, $sum, 'echo await sum(10,2);' . $entry, null, '12MM4294967298', 3],
			[$report, $sum, $entry, null, 'MM4294967298', 3],
		];
		foreach ($steps as $i => [$body,$leaf,$caller,$error,$stdout,$objects]) {
			foreach ([$caller,$body,$leaf] as $j => $text) {
				$path = "$directory/$paths[$j]";
				if (file_get_contents($path) === $text) continue;
				file_put_contents($path, $text); touch($path, time() + 60 + $i);
			}
			$fresh = new Incremental_Project($directory, "$directory/fresh-params", $target);
			if ($error !== null) {
				rejects(fn() => $project->run(), $error);
				rejects(fn() => $project->run(), $error);
				rejects(fn() => $fresh->run(), $error);
				continue;
			}
			$start = hrtime(true); $project->run(); $ms = (hrtime(true) - $start) / 1e6;
			$fresh->run();
			$files = artifacts("$directory/params");
			$fresh_files = artifacts("$directory/fresh-params");
			verify($files === $fresh_files, 'Async parameter artifacts differ from fresh compilation');
			foreach ($files as $path => $text) {
				if (str_ends_with($path, '.hpp') && str_contains($text, 'report_wait(')) {
					verify(!str_contains($text, 'async_'), 'Blocking-only export leaks async declarations');
				}
			}
			$all = implode("\n", $files);
			verify(str_contains($all, 'sum_wait(') === ($i === 5), 'Blocking bridge demand differs');
			verify(!preg_match('/\btemplate\s*</', $all), 'App emits a template');
			$fixtures[$flavor->name . 'params_' . $i] = ['files' => $files, 'fresh_files' => $fresh_files, 'compiler_ms' => $ms,
				'objects' => $objects, 'exit' => 8, 'stderr' => '', 'stdout_hex' => bin2hex($stdout)];
		}
	} finally {
		remove_tree($directory);
	}
}
if (isset($argv[1])) file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR));
echo "Async cross-file incremental recovery passed\n";
