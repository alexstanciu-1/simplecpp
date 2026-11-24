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
foreach ([flavor::cpp, flavor::php, flavor::js] as $flavor) {
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
		'for_lists' => [$signature('numbers', 'Iterator<int>') . '{for ('
			. rtrim($local('i', '0'), ';') . ',' . rtrim($local('j', '2'), ';') . ';' . $v('i') . '<2;'
			. $v('i') . '++,' . $v('j') . '++) {yield ' . $v('j') . ';continue;}}'
			. $loop('numbers(1)', $echo) . 'return 8;', '23'],
		'never_started' => [$model . $local('cursor', 'numbers(1)') . 'return 8;', ''],
		'locals' => [$signature('numbers', 'Iterator<int>') . '{' . $local('a', $v('x')) . 'yield ' . $v('a') . ';' . $local('b', '2') . $v('a') . '+=' . $v('b') . ';yield ' . $v('a') . ';}' . $loop('numbers(1)', $echo) . 'return 8;', '13'],
		'local_string' => [$signature('text', 'Iterator<string>', 'string') . '{' . $local('a', $v('x')) . 'yield ' . $v('a') . ';' . $v('a') . '="tail";yield ' . $v('a') . ';}' . $loop('text("abcdefghijklmnopqrstuvwxyz0123456789")', $echo) . 'return 8;', 'abcdefghijklmnopqrstuvwxyz0123456789tail'],
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

	$local_owner = $signature('owned', 'Iterator<weak<Point>>') . '{'
		. $local('p', 'new Point()') . $local('text', '"abcdefghijklmnopqrstuvwxyz0123456789"')
		. 'yield weak(' . $v('p') . ');' . $local('later', 'new Point()')
		. 'yield weak(' . $v('later') . ');}return 8;';
	$entry = $prepare($record . $local_owner);
	$cpp = str_replace('int main()', 'int source_main()', (new Cpp_Emitter())->emit($entry));
	$cpp .= <<<'CPP'
int main() {
    App::weak_Point first, second;
    {
        auto unstarted = App::owned_(1);
    }
    {
        auto original = App::owned_(1);
        if (!original.next(first) || !first.lock()) return 2;
        auto cursor = static_cast<App::owned_cursor&&>(original);
        if (original.next(second) || !first.lock()) return 3;
        if (!cursor.next(second) || !second.lock()) return 4;
        if (cursor.next(second) || first.lock() || second.lock()) return 5;
        cursor.close();
    }
    {
        auto cursor = App::owned_(1);
        if (!cursor.next(first) || !first.lock()) return 6;
    }
    if (first.lock()) return 7;
    return 8;
}
CPP;
	$fixtures[$flavor->name . '_local_owner'] = ['cpp' => $cpp, 'stdout_hex' => ''];
	$bad_local = match ($flavor) {
		flavor::php => '$bad byte=' . $v('x') . ';',
		flavor::cpp => 'byte bad=' . $v('x') . ';',
		flavor::js => 'let bad:byte=' . $v('x') . ';',
	};
	$failed = str_replace($local('later', 'new Point()'), $bad_local . $local('later', 'new Point()'), $local_owner);
	$entry = $prepare($record . $failed);
	$cpp = str_replace('int main()', 'int source_main()', (new Cpp_Emitter())->emit($entry));
	$cpp .= <<<'CPP'
int main() {
    App::weak_Point first;
    auto cursor = App::owned_(300);
    if (!cursor.next(first) || !first.lock()) return 1;
    bool caught = false;
    try { cursor.next(first); } catch (...) { caught = true; }
    if (!caught || first.lock() || cursor.next(first)) return 2;
    return 8;
}
CPP;
	$fixtures[$flavor->name . '_local_failure'] = ['cpp' => $cpp, 'stdout_hex' => ''];

	$elseif = $flavor === flavor::php ? 'elseif' : 'else if';
	$branches = $signature('numbers', 'Iterator<int>') . '{'
		. 'if (' . $v('x') . '>0) {' . $local('n', '10') . $v('x') . '=-1;yield ' . $v('n') . ';'
		. 'if (' . $v('x') . '<0) {' . $v('n') . '+=1;yield ' . $v('n') . ';}yield 12;}'
		. $elseif . ' (' . $v('x') . '==0) {yield 20;return;}else {yield 30;}yield 40;}';
	foreach ([1 => '10111240', 0 => '20', -1 => '3040'] as $input => $expected) {
		$start = hrtime(true);
		$entry = $prepare($branches . $loop('numbers(' . $input . ')', $echo) . 'return 8;');
		$cpp = (new Cpp_Emitter())->emit($entry);
		$fixtures[$flavor->name . '_branch_' . $input] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected),
			'compiler_ms' => (hrtime(true) - $start) / 1e6];
	}
	$probe = $signature('probe', 'bool') . '{echo "c";return ' . $v('x') . '>0;}';
	$conditional_keyed = $signature('keys', 'Iterator<string,int>') . '{if (probe(' . $v('x') . ')) {'
		. $local('s', '"abcdefghijklmnopqrstuvwxyz0123456789"') . $v('x') . '=0;yield 1=>' . $v('s') . ';'
		. $v('s') . '="tail";yield 2=>' . $v('s') . ';}else {yield 3=>"else";}}';
	$entry = $prepare($probe . $conditional_keyed . 'foreach(keys(1) as ' . $v('k') . '=>' . $v('value') . '){echo '
		. $v('k') . ',' . $v('value') . ';}return 8;');
	$fixtures[$flavor->name . '_branch_string'] = ['cpp' => (new Cpp_Emitter())->emit($entry),
		'stdout_hex' => bin2hex('c1abcdefghijklmnopqrstuvwxyz01234567892tail')];

	$conditional_owner = $signature('owned', 'Iterator<weak<Point>>') . '{if (' . $v('x') . '>0) {'
		. $local('p', 'new Point()') . 'yield weak(' . $v('p') . ');' . $v('x') . '=-1;'
		. '{' . $local('q', 'new Point()') . 'if (' . $v('x') . '<0) yield weak(' . $v('q') . ');}'
		. 'yield weak(' . $v('p') . ');}' . $elseif . '(' . $v('x') . '==0){'
		. $local('p', 'new Point()') . 'yield weak(' . $v('p') . ');return;}else {'
		. $local('p', 'new Point()') . 'yield weak(' . $v('p') . ');}yield weak(new Point());}return 8;';
	$entry = $prepare($record . $conditional_owner);
	$cpp = str_replace('int main()', 'int source_main()', (new Cpp_Emitter())->emit($entry));
	$cpp .= <<<'CPP'
int main() {
    App::weak_Point first, nested, again, after;
    {
        auto original = App::owned_(1);
        if (!original.next(first) || !first.lock()) return 1;
        auto cursor = static_cast<App::owned_cursor&&>(original);
        if (original.next(after) || !cursor.next(nested) || !nested.lock()) return 2;
        if (!cursor.next(again) || nested.lock() || !first.lock() || first.ptr != again.ptr) return 3;
        if (!cursor.next(after) || after.lock() || first.lock() || again.lock()) return 4;
        if (cursor.next(after)) return 5;
    }
    for (int input = 0; input >= -1; --input) {
        auto original = App::owned_(input);
        if (!original.next(first) || !first.lock()) return 6;
        auto cursor = static_cast<App::owned_cursor&&>(original);
        bool advanced = cursor.next(after);
        if (advanced != (input == -1) || first.lock()) return 7;
    }
    {
        auto cursor = App::owned_(1);
        if (!cursor.next(first) || !cursor.next(nested)) return 9;
    }
    if (first.lock() || nested.lock()) return 10;
    return 8;
}
CPP;
	$fixtures[$flavor->name . '_branch_owner'] = ['cpp' => $cpp, 'stdout_hex' => ''];
	$failed = str_replace($v('x') . '=-1;', $bad_local, $conditional_owner);
	$entry = $prepare($record . $failed);
	$cpp = str_replace('int main()', 'int source_main()', (new Cpp_Emitter())->emit($entry));
	$cpp .= <<<'CPP'
int main() {
    App::weak_Point first;
    auto cursor = App::owned_(300);
    if (!cursor.next(first) || !first.lock()) return 1;
    bool caught = false;
    try { cursor.next(first); } catch (...) { caught = true; }
    if (!caught || first.lock() || cursor.next(first)) return 2;
    return 8;
}
CPP;
	$fixtures[$flavor->name . '_branch_failure'] = ['cpp' => $cpp, 'stdout_hex' => ''];
	rejects(fn() => $prepare(str_replace('yield 12;', 'yield missing();', $branches) . 'return 8;'), '');
	rejects(fn() => $prepare($signature('bad_scope', 'Iterator<int>') . '{if(true){'
		. $local('n', '1') . '}yield ' . $v('n') . ';}return 8;'), '');

	$for_init = rtrim($local('i', '0'), ';');
	$for_head = 'for (' . $for_init . ';' . $v('i') . '<' . $v('x') . ';' . $v('i') . '++)';
	$while_body = $local('i', '0') . 'while (' . $v('i') . '<' . $v('x') . ') {'
		. $v('i') . '++;' . $local('n', $v('i')) . 'if (' . $v('i') . '==2){continue;}'
		. 'yield ' . $v('n') . ';if (' . $v('i') . '==3){break;}}';
	$do_body = $local('i', '0') . 'do {' . $local('n', $v('i')) . 'yield ' . $v('n') . ';'
		. $v('i') . '++;if (' . $v('i') . '<2){continue;}break;}while (' . $v('i') . '<' . $v('x') . ');';
	$for_body = $for_head . '{' . $local('s', '"abcdefghijklmnopqrstuvwxyz0123456789"')
		. 'yield ' . $v('i') . ';if (' . $v('i') . '==1){continue;}if (' . $v('i') . '==3){break;}yield 9;}';
	$nested_body = $for_head . '{for (' . rtrim($local('j', '0'), ';') . ';' . $v('j') . '<3;' . $v('j') . '++) {'
		. 'if (' . $v('j') . '==0){yield ' . $v('i') . '*10+' . $v('j') . ';continue;}yield 99;break;}'
		. 'if (' . $v('i') . '==0){continue;}break;}';
	foreach (['while' => [$while_body, 5, '13'], 'do' => [$do_body, 5, '01'],
		'for' => [$for_body, 5, '091293'], 'nested' => [$nested_body, 5, '0991099'],
		'while_empty' => [$while_body, 0, ''], 'for_empty' => [$for_body, 0, ''], 'do_once' => [$do_body, 0, '0']] as $case => [$code, $input, $expected]) {
		$start = hrtime(true);
		$entry = $prepare($signature('numbers', 'Iterator<int>') . '{' . $code . '}' . $loop('numbers(' . $input . ')', $echo) . 'return 8;');
		$cpp = (new Cpp_Emitter())->emit($entry);
		$fixtures[$flavor->name . '_loop_' . $case] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected), 'compiler_ms' => (hrtime(true) - $start) / 1e6];
	}
	$step = $signature('step', 'int') . '{echo "u";return ' . $v('x') . ';}';
	$effects = $signature('numbers', 'Iterator<int>') . '{for (' . rtrim($local('i', 'step(0)'), ';')
		. ';' . $v('i') . '<2;' . $v('i') . '+=step(1)){yield ' . $v('i') . ';continue;}}';
	$entry = $prepare($step . $effects . $loop('numbers(0)', $echo) . 'return 8;');
	$fixtures[$flavor->name . '_loop_effects'] = ['cpp' => (new Cpp_Emitter())->emit($entry), 'stdout_hex' => bin2hex('u0u1u')];

	$owned_loop = $signature('owned', 'Iterator<weak<Point>>') . '{' . $for_head . '{'
		. $local('p', 'new Point()') . 'yield weak(' . $v('p') . ');'
		. 'if (' . $v('i') . '==0){continue;}if (' . $v('i') . '==1){break;}}yield weak(new Point());}return 8;';
	$entry = $prepare($record . $owned_loop);
	$cpp = str_replace('int main()', 'int source_main()', (new Cpp_Emitter())->emit($entry));
	$cpp .= <<<'CPP'
int main() {
    App::weak_Point first, second, end;
    {
        auto original = App::owned_(3);
        if (!original.next(first) || !first.lock()) return 1;
        auto cursor = static_cast<App::owned_cursor&&>(original);
        if (!cursor.next(second) || first.lock() || !second.lock()) return 2;
        if (!cursor.next(end) || second.lock() || end.lock() || cursor.next(end)) return 3;
    }
    {
        auto cursor = App::owned_(3);
        if (!cursor.next(first)) return 4;
    }
    if (first.lock()) return 5;
    return 8;
}
CPP;
	$fixtures[$flavor->name . '_loop_owner'] = ['cpp' => $cpp, 'stdout_hex' => ''];
	$entry = $prepare($record . str_replace('if (' . $v('i') . '==0){continue;}', $bad_local, $owned_loop));
	$cpp = str_replace('int main()', 'int source_main()', (new Cpp_Emitter())->emit($entry));
	$cpp .= <<<'CPP'
int main() {
    App::weak_Point first;
    auto cursor = App::owned_(300);
    if (!cursor.next(first) || !first.lock()) return 1;
    bool caught = false;
    try { cursor.next(first); } catch (...) { caught = true; }
    if (!caught || first.lock() || cursor.next(first)) return 2;
    return 8;
}
CPP;
	$fixtures[$flavor->name . '_loop_failure'] = ['cpp' => $cpp, 'stdout_hex' => ''];

	$header_owner = $signature('owned', 'Iterator<weak<Point>>') . '{for (' . rtrim($local('p', 'new Point()'), ';')
		. ';' . $v('x') . '>0;' . $v('x') . '--){yield weak(' . $v('p') . ');'
		. 'if (' . $v('x') . '==2){continue;}break;}yield weak(new Point());}return 8;';
	$entry = $prepare($record . $header_owner);
	$cpp = str_replace('int main()', 'int source_main()', (new Cpp_Emitter())->emit($entry));
	$cpp .= <<<'CPP'
int main() {
    App::weak_Point first, second, end;
    auto cursor = App::owned_(2);
    if (!cursor.next(first) || !first.lock()) return 1;
    if (!cursor.next(second) || !second.lock() || first.ptr != second.ptr) return 2;
    if (!cursor.next(end) || first.lock() || second.lock() || end.lock()) return 3;
    if (cursor.next(end)) return 4;
    return 8;
}
CPP;
	$fixtures[$flavor->name . '_loop_header_owner'] = ['cpp' => $cpp, 'stdout_hex' => ''];

	rejects(fn() => $prepare($model . $local('cursor', 'numbers(1)') . $local('copy', $v('cursor')) . 'return 8;'), 'move-only');
	rejects(fn() => $prepare($signature('numbers', 'int') . '{yield 1;return 8;}return 8;'), 'Yield requires');
	rejects(fn() => $prepare(str_replace('yield 2;', 'yield "a"=>2;', $model) . $main), 'Yield key');
	rejects(fn() => $prepare(str_replace('yield 2;', 'foreach (numbers(1) as ' . $v('other') . ') {yield 2;}', $model) . $main), 'Nested cursor');
	rejects(fn() => $prepare(str_replace('yield 2;', $local('temporary', 'numbers(2)') . 'yield 2;', $model) . $main), 'Nested cursor');
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
		foreach ([$model, str_replace('yield 2;', 'yield 3;', $model), str_replace('yield 2;', 'yield "wrong";', $model), '', $model, str_replace(['Iterator<int>', 'yield '], ['Iterator<int,string>', 'yield "k"=>'], $model), $model,
			str_replace('yield 2;', $local('a', '2') . 'yield ' . $v('a') . ';', $model),
			str_replace('yield 2;', $local('a', '"unused heap string abcdefghijklmnopqrstuvwxyz"') . 'yield 2;', $model),
			str_replace('yield 2;', $local('a', 'new Point()') . 'yield 2;', $model) . $record,
			str_replace('yield 2;', $local('a', 'true') . 'yield 2;', $model), $model,
			str_replace('yield 2;', 'if (' . $v('x') . '>0) {' . $local('n', '2') . 'yield ' . $v('n') . ';}else {yield 4;}', $model),
			str_replace('yield 2;', 'if (false) {yield "bad";}else {yield 2;}', $model),
			str_replace('yield 2;', 'if (false) {yield 4;}else {' . $local('n', '"heap abcdefghijklmnopqrstuvwxyz"') . 'yield 2;}', $model),
			'', $model,
			str_replace('yield 2;', $for_head . '{yield 2;}', $model),
			str_replace('yield 2;', 'do {yield 2;}while(false);', $model),
			str_replace('yield 2;', 'while(true){yield missing();break;}', $model),
			str_replace('yield 2;', 'while(true){yield 2;break;}', $model), $model] as $step => $source) {
			file_put_contents($provider, $source); touch($provider, ++$stamp);
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			if (in_array($step, [2, 3, 13, 15, 19], true)) {
				rejects(fn() => $project->run(), ''); rejects(fn() => $project->run(), ''); rejects(fn() => $fresh->run(), ''); continue;
			}
			$project->run(); $fresh->run(); $caller ??= $project->session()->entry->entry->ast;
			verify($caller === $project->session()->entry->entry->ast, 'Unchanged caller reparsed');
			$files = artifacts($directory . '/out'); verify($files === artifacts($directory . '/fresh'), 'Incremental differs from fresh: ' . $flavor->name . ' step ' . $step);
			$fixtures[$flavor->name . '_edit_' . $step] = ['files' => $files, 'stdout_hex' => bin2hex($step === 1 ? 'createdstart1pause3end' : 'createdstart1pause2end')];
			verify($project->run()['preparation']['prepared'] === 0, 'No-op prepared bodies');
		}
	} finally {
		remove_tree($directory);
	}
}
if (isset($argv[1])) file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
echo "Yield contracts, lazy execution, move-only admission and incremental recovery passed\n";
