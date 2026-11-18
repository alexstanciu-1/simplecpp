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
	$v = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	$signature = static fn(string $name, string $result, string $parameter = 'int'): string => match ($flavor) {
		flavor::php => 'function ' . $name . '(' . $parameter . ' $x):' . $result,
		flavor::cpp => $result . ' ' . $name . '(' . $parameter . ' x)',
		flavor::js => 'function ' . $name . '(x:' . $parameter . '):' . $result,
	};
	$local = static fn(string $name, string $initializer): string => match ($flavor) {
		flavor::php => '$' . $name . '=' . $initializer . ';',
		flavor::cpp => 'auto ' . $name . '=' . $initializer . ';',
		flavor::js => 'let ' . $name . '=' . $initializer . ';',
	};
	$loop = static fn(string $source, string $body): string => 'foreach (' . $source . ' as ' . $v('value') . ') {' . $body . '}';
	$echo = 'echo ' . $v('value') . ';';
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('yield', $source, $flavor), new entry_target(31));
	$model = $signature('numbers', 'Iterator<int>') . '{echo "start";yield ' . $v('x') . ';yield 2;echo "end";return;}';
	$main = $local('cursor', 'numbers(1)') . 'echo "created";'
		. $loop($v('cursor'), $echo . 'break;') . 'echo "pause";'
		. $loop($v('cursor'), $echo) . $loop($v('cursor'), 'echo "bad";') . 'return 8;';
	$keyed = $signature('numbers', 'Iterator<int,string>') . '{yield "a"=>' . $v('x') . ';yield "b"=>2;}';
	$key_loop = 'foreach (numbers(1) as ' . $v('key') . '=>' . $v('value') . ') {echo ' . $v('key') . ',' . $v('value') . ';}return 8;';
	$string = $signature('text', 'Iterator<string>', 'string') . '{yield ' . $v('x') . ';yield "tail";}';
	foreach (['resume' => [$model . $main, 'createdstart1pause2end'],
		'keyed' => [$keyed . $key_loop, 'a1b2'],
		'discard_key' => [$keyed . $loop('numbers(1)', $echo) . 'return 8;', '12'],
		'string' => [$string . $loop('text("abcdefghijklmnopqrstuvwxyz0123456789")', $echo) . 'return 8;', 'abcdefghijklmnopqrstuvwxyz0123456789tail'],
		'never_started' => [$model . $local('cursor', 'numbers(1)') . 'return 8;', ''],
		'empty' => [$signature('numbers', 'Iterator<int>') . '{return;}' . $loop('numbers(1)', $echo) . 'return 8;', ''],
	] as $case => [$source, $expected]) {
		$start = hrtime(true); $entry = $prepare($source); $prepare_ns = hrtime(true) - $start;
		$before = serialize($entry); $start = hrtime(true);
		$cpp = (new Cpp_Emitter())->emit($entry);
		$compiler_ms = ($prepare_ns + hrtime(true) - $start) / 1e6;
		verify(serialize($entry) === $before, 'Emission mutated facts');
		verify(!str_contains($cpp, 'co_yield') && !str_contains($cpp, 'template<'), 'Cursor emission uses coroutines/templates');
		$fixtures[$flavor->name . '_' . $case] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected), 'compiler_ms' => $compiler_ms];
	}
	$access = $flavor === flavor::js ? '.' : '->';
	$property = match ($flavor) {
		flavor::php => 'public int $value=7;', flavor::cpp => 'int value=7;', flavor::js => 'value:int=7;',
	};
	$record = 'struct Point {' . $property . '}' . ($flavor === flavor::cpp ? ';' : '');
	$capture = $signature('capture', 'Iterator<int>', 'shared<Point>') . '{yield ' . $v('x') . $access . 'value;}';
	$captured = $record . $capture . $local('p', 'new Point()') . $local('w', 'weak(' . $v('p') . ')')
		. $local('cursor', 'capture(' . $v('p') . ')') . $v('p') . '=null;'
		. 'if (lock(' . $v('w') . ')==null) {return 1;}' . $loop($v('cursor'), $echo)
		. 'if (lock(' . $v('w') . ')!=null) {return 2;}return 8;';
	$entry = $prepare($captured);
	$fixtures[$flavor->name . '_capture'] = ['cpp' => (new Cpp_Emitter())->emit($entry), 'stdout_hex' => bin2hex('7')];
	$inline_local = match ($flavor) {
		flavor::php => '$point Point;', flavor::cpp => 'Point point;', flavor::js => 'let point:Point;',
	};
	$inline_capture = $signature('capture_value', 'Iterator<int>', 'Point') . '{yield ' . $v('x') . $access . 'value;}';
	$entry = $prepare($record . $inline_capture . $inline_local . $local('cursor', 'capture_value(' . $v('point') . ')')
		. $v('point') . $access . 'value=9;' . $loop($v('cursor'), $echo) . 'return 8;');
	$fixtures[$flavor->name . '_inline_capture'] = ['cpp' => (new Cpp_Emitter())->emit($entry), 'stdout_hex' => bin2hex('7')];

	foreach ([false, true] as $advance) {
		$drop = $signature('drop', 'weak<Point>', 'shared<Point>') . '{'
			. $local('w', 'weak(' . $v('x') . ')') . $local('cursor', 'capture(' . $v('x') . ')') . $v('x') . '=null;'
			. ($advance ? $loop($v('cursor'), $echo . 'break;') : '') . 'return ' . $v('w') . ';}';
		$entry = $prepare($record . $capture . $drop . $local('w', 'drop(new Point())')
			. 'if (lock(' . $v('w') . ')!=null) {return 1;}return 8;');
		$fixtures[$flavor->name . ($advance ? '_abandon_started' : '_abandon_unstarted')] = [
			'cpp' => (new Cpp_Emitter())->emit($entry), 'stdout_hex' => $advance ? bin2hex('7') : ''];
	}

	$checked = $signature('checked', 'Iterator<byte>') . '{yield ' . $v('x') . ';}return 8;';
	$entry = $prepare($checked);
	$cpp = str_replace('int main()', 'int source_main()', (new Cpp_Emitter())->emit($entry));
	$cpp .= <<<'CPP'

int main() {
    static_assert(!__is_constructible(App::checked_cursor, const App::checked_cursor&));
    auto original = App::checked_(300);
    auto cursor = static_cast<App::checked_cursor&&>(original);
    scpp::uint8 output = 77;
    if (original.next(output)) return 1;
    bool caught = false;
    try { cursor.next(output); } catch (...) { caught = true; }
    if (!caught || output != 77 || cursor.next(output)) return 2;
    auto moved_closed = static_cast<App::checked_cursor&&>(cursor);
    if (moved_closed.next(output)) return 3;
    return 8;
}
CPP;
	$fixtures[$flavor->name . '_checked_cleanup'] = ['cpp' => $cpp, 'stdout_hex' => ''];

	rejects(fn() => $prepare($model . $local('cursor', 'numbers(1)') . $local('copy', $v('cursor')) . 'return 8;'), 'move-only');
	rejects(fn() => $prepare($signature('numbers', 'int') . '{yield 1;return 8;}return 8;'), 'Yield requires');
	rejects(fn() => $prepare(str_replace('yield 2;', 'yield "a"=>2;', $model) . $main), 'Yield key');
	rejects(fn() => $prepare(str_replace('yield 2;', 'while (true) {yield 2;}', $model) . $main), 'straight-line');
	rejects(fn() => $prepare(str_replace('yield 2;', $local('temporary', '2') . 'yield 2;', $model) . $main), 'straight-line');
	rejects(fn() => $prepare(str_replace('return;', 'return 8;', $model) . $main), 'cannot return a value');
	rejects(fn() => $prepare($record . $signature('record_values', 'Iterator<Point>', 'Point') . '{yield ' . $v('x') . ';}return 8;'), 'Inline struct yield outputs');

	$reference = match ($flavor) {
		flavor::php => str_replace('int $x', 'int &$x', $model),
		flavor::cpp => str_replace('int x', 'int &x', $model),
		flavor::js => str_replace('x:int', '&x:int', $model),
	};
	rejects(fn() => $prepare($reference . $main), 'by-value parameters');

	$directory = sys_get_temp_dir() . '/scpp-yield-' . bin2hex(random_bytes(8)); mkdir($directory);
	try {
		$provider = $directory . '/numbers.' . $flavor->name;
		file_put_contents($directory . '/main.' . $flavor->name, $main);
		file_put_contents($directory . '/module.json', json_encode(['entry' => 'main.' . $flavor->name, 'sources' => ['main.' . $flavor->name, basename($provider)]]));
		$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31)); $stamp = time(); $caller = null;
		foreach ([$model, str_replace('yield 2;', 'yield 3;', $model), str_replace('yield 2;', 'yield "wrong";', $model), '', $model, str_replace(['Iterator<int>', 'yield '], ['Iterator<int,string>', 'yield "k"=>'], $model), $model] as $step => $source) {
			file_put_contents($provider, $source); touch($provider, ++$stamp);
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			if ($step === 2 || $step === 3) {
				rejects(fn() => $project->run(), ''); rejects(fn() => $project->run(), ''); rejects(fn() => $fresh->run(), ''); continue;
			}
			$project->run(); $fresh->run(); $caller ??= $project->session()->entry->entry->ast;
			verify($caller === $project->session()->entry->entry->ast, 'Unchanged caller reparsed');
			$files = artifacts($directory . '/out'); verify($files === artifacts($directory . '/fresh'), 'Incremental differs from fresh');
			$fixtures[$flavor->name . '_edit_' . $step] = ['files' => $files, 'stdout_hex' => bin2hex($step === 1 ? 'createdstart1pause3end' : 'createdstart1pause2end')];
			verify($project->run()['preparation']['prepared'] === 0, 'No-op prepared bodies');
		}
	} finally {
		remove_tree($directory);
	}
}
if (isset($argv[1])) file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
echo "Yield contracts, lazy execution, move-only admission and incremental recovery passed\n";
