<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/compilation.php';
use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, ast_function_declaration, Cpp_Headers, Cpp_Emitter, diagnostic, diagnostic_group, Incremental_Project};

function verify(bool $condition, string $message): void
{
	if (!$condition) throw new RuntimeException($message);
}

/** Require the expected frontend diagnostic or manifest validation error before native compilation. */
function rejects(Closure $action, string $message, bool $manifest = false): void
{
	try {
		$action();
	}
	catch (RuntimeException $error) {
		if (!$manifest && !($error instanceof diagnostic || $error instanceof diagnostic_group)) throw $error;
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

/** Additional artifacts for the outlined proof, built from the successful session's resolved result types. */
function publish_task_payloads(Incremental_Project $project): array
{
	$session = $project->session();
	$publisher = new Cpp_Headers();
	$emitter = new Cpp_Emitter($publisher, $session->cpp_namespace);
	$headers = [];
	foreach ($session->files() as $file) {
		foreach ($file->declaration_nodes as $node) {
			if (!($node instanceof ast_function_declaration) || !$node->is_async) continue;
			$type = $node->return_type_def();
			$key = $type->concrete_identity();
			$headers[$key] ??= 'units/' . $publisher->publish_value_header($type, $emitter, $session->cpp_namespace);
		}
	}
	ksort($headers);
	return ['headers' => array_values($headers), 'files' => (object) $publisher->files()];
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
	$prepare(str_replace('int', 'float', $child) . 'return 8;');

	rejects(fn() => $prepare($child . 'if (false && await read_value()) {return 1;}return 0;'), 'Await requires');
	rejects(fn() => $prepare($child . 'async ' . $signature('invalid') . '{return 1 + (true && await read_value());}return 0;'), 'Await requires');

	$prepare($child . 'await read_value();return 0;');
	rejects(fn() => $prepare($child . 'if (await read_value()) {return 1;}return 0;'), 'Await requires');

	$catch = static fn(string $type): string => 'catch (' . match ($flavor) {
		flavor::php => $type . ' $e', flavor::cpp => $type . ' e', flavor::js => 'e:' . $type,
	} . ')';
	$protected = 'try {return await read_value();}';
	$fallback = $protected . $catch('Failure') . '{return 7;}';
	foreach (['return await read_value();', 'await async_sleep_ms(1);return 0;'] as $handler) {
		rejects(fn() => $prepare($failure . $child . 'async ' . $signature('report') . '{' . $protected
			. $catch('Failure') . '{' . $handler . '}}return 0;'), 'Cannot suspend inside an async catch handler');
	}
	rejects(fn() => $prepare($child . 'async ' . $signature('report') . '{' . $protected . 'finally {echo 1;}}return 0;'),
		'Finally in async functions is deferred');

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
		$types = $failure . str_replace('Failure', 'OtherFailure', $failure);
		$delayed = $types . str_replace('return 4294967297;', 'echo "T";throw new Failure();', $child);
		$resume = 'async ' . $signature('recover') . '{await async_sleep_ms(1);return 11;}';
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
			['return await read_value();', $types . $child, null, '4294967297:after', false, 2],
			[$fallback, $types . $child, null, '4294967297:after', false, 2],
			[$fallback, $delayed, null, 'T7:after', false, 1],
			[str_replace($catch('Failure'), $catch('OtherFailure'), $fallback), $delayed, null, 'T', true, 2],
			[$protected . $catch('Failure') . '{echo "R";throw;}', $delayed, null, 'TR', true, 2],
			[$protected . $catch('Failure') . '{echo "C";}return await recover();', $delayed . $resume, null, 'TC11:after', false, 2],
			[str_replace($catch('Failure'), $catch('MissingFailure'), $fallback), $delayed, 'Throw and catch require', '', false, 0],
			[$fallback, $delayed, null, 'T7:after', false, 2],
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
				verify(!str_contains($text, '::scpp::async::wait_for('), 'Blocking call used a retired runtime bridge');
			}
			verify($project->run()['preparation']['prepared'] === 0, 'No-op prepared bodies');
			$fixtures[$flavor->name . '_' . $i] = ['files' => $files, 'fresh_files' => $fresh_files, 'compiler_ms' => $elapsed,
				'objects' => $case[5] ?? ($i === 0 ? 4 : 1),
				'exit' => ($case[4] ?? ($i === 6)) ? 1 : 8, 'stderr' => ($case[4] ?? ($i === 6)) ? "Exception\n" : '',
				'stdout_hex' => bin2hex('before:' . ($case[3] ?? ($i === 6 ? '' : ($i === 1 ? '4294967299' : '4294967297') . ':after')))];
		}
		// Independent parameter project. Keep each successful generation for native/fresh execution.
		$parameter = static fn(string $n, string $type = 'int'): string => match ($flavor) {
			flavor::php => $type . ' $' . $n, flavor::js => $n . ':' . $type, flavor::cpp => $type . ' ' . $n,
		};
		$v = static fn(string $n): string => ($flavor === flavor::php ? '$' : '') . $n;
		$fn = static fn(string $n, array $params): string => str_replace('()', '(' . implode(',', array_map($parameter, $params)) . ')', $signature($n));
		$sum = 'async ' . $fn('sum', ['x','y']) . '{await async_sleep_ms(1);return ' . $v('x') . '+' . $v('y') . ';}';
		$mark = $fn('mark', ['x']) . '{echo "M";return ' . $v('x') . ';}';
		$args = 'mark(' . $v('v') . '),mark(true)';
		$report = $mark . 'async ' . $fn('report', ['v']) . '{if (false && await sum(' . $args . ')) {return 0;}return await sum(' . $args . ');}';
		$entry = 'echo await report(4294967297);return 8;';
		$n = $v('n');
		$loop_report = $mark . 'async ' . $fn('report', ['v']) . '{' . str_replace('9;', '0;', $initial)
			. 'while (' . $n . '<2 && await sum(1,0)) {mark(' . $n . ');' . $n . '++;}'
			. 'do {await sum(1,0);' . $n . '+=1;} while (await sum(0,0));'
			. 'for (; ' . $n . '<6 && await sum(1,0);' . $n . '++) {'
			. 'if (' . $n . '==3) {continue;}if (' . $n . '==5) {break;}await sum(1,0);mark(' . $n . ');}'
			. 'return ' . $v('v') . '+' . $n . ';}';
		$extra = str_replace($parameter('y') . ')', $parameter('y') . ',' . $parameter('z') . ')', $sum);
		$fixed = str_replace($args, $args . ',0', $report);
		$never = 'async ' . $signature('forever') . '{for (;;) {}}';
		$native = (new Cpp_Emitter())->emit($prepare($never . 'return 0;'));
		verify(str_contains($native, 'co_return'), 'Infinite async body must still construct a coroutine');
		foreach (['vector', 'reference'] as $bad) {
			$param = match ($bad) {
				'vector' => $parameter('x', 'vector<int>'),
				'reference' => match ($flavor) {
					flavor::php => 'int &$x', flavor::cpp => 'int &x', flavor::js => '&x:int',
				},
			};
			rejects(fn() => $prepare('async ' . str_replace('()', '(' . $param . ')', $signature('bad')) . '{return 1;}return 0;'), 'Async parameters require');
		}
		$default_sig = $fn('read_value', ['x','y']);
		$defaults = static fn(string $x, string $y): string => str_replace(
			[$parameter('x'), $parameter('y')], [$parameter('x') . $x, $parameter('y') . $y], $default_sig);
		$body = '{await async_sleep_ms(1);return ' . $v('x') . '+' . $v('y') . ';}';
		$reader = static fn(string $x): string => 'async ' . $defaults($x, '=false') . $body;
		$default_report = 'async ' . $signature('report') . '{echo await read_value(42), ",";return await read_value();}';
		$default_entry = 'echo await read_value(), ",", await read_value(42), ",", await report();return 8;';
		rejects(fn() => $prepare('async ' . $defaults('=7', '') . $body . 'return 0;'), 'Required parameter follows optional parameter');
		$bytes = "abcdefghijklmnopqrstuvwxyz\0XYZ";
		$literal = '"abcdefghijklmnopqrstuvwxyz\0XYZ"';
		$text = $v('text');
		$declare_text = match ($flavor) {
			flavor::php => "$text string", flavor::cpp => "string $text", flavor::js => "let $text:string",
		};
		$owned_params = implode(',', [$parameter('text', 'string') . '=' . $literal, $parameter('enabled', 'bool') . '=true',
			$parameter('marker', 'uint8') . '=65', $parameter('scale', 'float64') . '=2.5']);
		$owned_child = $failure . 'async ' . str_replace('()', '(' . $owned_params . ')', $signature('read_value'))
			. '{await async_sleep_ms(1);echo ' . $text . ';if (' . $v('enabled') . ' && '
			. $v('scale') . '>2.0) {' . $text . '="modified";return ' . $v('marker') . ';}throw new Failure();}';
		$owned_parent = 'async ' . str_replace('()', '(' . $parameter('text', 'string') . ')', $signature('report'))
			. '{try {return await read_value(' . $text . ',false,66,3.0);}' . $catch('Failure') . '{echo ' . $text . ';return 7;}}';
		$owned_entry = $declare_text . '=' . $literal . ';echo await read_value(' . $text . '),await report(' . $text . '),' . $text . ';'
			. 'try {echo await read_value(' . $text . ',false);}' . $catch('Failure') . '{echo "caught";}echo await read_value();return 8;';
		$owned_output = $bytes . '65' . $bytes . $bytes . '7' . $bytes . $bytes . 'caught' . $bytes . '65';
		$void_sig = static fn(string $sig): string => $flavor === flavor::cpp ? preg_replace('/^int /', 'void ', $sig) : str_replace('): int', '): void', $sig);
		$notify_sig = $fn('notify', ['x']);
		$notify_body = '{await async_sleep_ms(1);echo ' . $v('x') . ';';
		$notify = 'async ' . $void_sig($notify_sig) . $notify_body . 'if (' . $v('x') . ') {return;}}';
		$int_notify = 'async ' . $notify_sig . $notify_body . 'return 3;}';
		$relay = 'async ' . $void_sig($signature('relay')) . '{await notify(1);}';
		rejects(fn() => $prepare('async ' . $void_sig($signature('invalid')) . '{return 1;}return 0;'), 'Void function cannot return a value');
		$discard = 'await relay();await notify(0);return 8;';
		$consume = 'await relay();echo await notify(0);return 8;';
		$bool_sig = static fn(string $sig): string => str_replace('void', 'bool', $void_sig($sig));
		$bool_child = 'async ' . $bool_sig($notify_sig) . '{await async_sleep_ms(1);return ' . $v('x') . '>0;}';
		$bool_parent = 'async ' . $bool_sig($signature('relay')) . '{return await notify(1);}';
		$bool_entry = match ($flavor) {
			flavor::php => '$ok bool', flavor::cpp => 'bool ok', flavor::js => 'let ok:bool',
		} . '=await relay();if (' . $v('ok') . ') {echo "T";}await notify(0);return 8;';
		$decl = static fn(string $type, string $name, string $init): string => match ($flavor) {
			flavor::php => '$' . "$name $type = $init;", flavor::cpp => "$type $name=$init;", flavor::js => "let $name:$type = $init;",
		};
		$results_child = $results_parent = $results_entry = '';
		$samples = [
			['string', $literal, ''], ['vector<int>', '[31]', '[0]'], ['hash<int,int>', '[4 => 32]', '[4]'],
			['nullable<int>', '33', ''], ['mixed', '34', '[0]'], ['dynamic', '35', '[0]'],
		];
		foreach ($samples as $k => [$type, $init, $index]) {
			$sig = static fn(string $name): string => $flavor === flavor::cpp ? "$type $name()" : "function $name(): $type";
			$boxed = in_array($type, ['mixed', 'dynamic'], true);
			if ($boxed) {
				$results_child .= $sig("make$k") . '{' . $decl($type, 'item', '[]') . $v('item') . "[0]=$init;return " . $v('item') . ';}';
				$init = "make$k()";
			}
			$results_child .= 'async ' . $sig("leaf$k") . '{' . $decl($type, 'item', $init)
				. 'await async_sleep_ms(1);return ' . $v('item') . ';}';
			$results_parent .= 'async ' . $sig("relay$k") . "{return await leaf$k();}";
			$results_entry .= $decl($type, "value$k", "await relay$k()") . 'echo '
				. ($boxed ? '(int)' : '') . ($k === 3 ? '(' : '') . $v("value$k") . $index . ($k === 3 ? ' ?? 0)' : '') . ';';
			if ($boxed) {
				$results_entry .= $decl($type, "copy$k", $v("value$k")) . $v("copy$k") . '[0]=9;echo (int)' . $v("value$k") . '[0];';
			}
		}
		$results_entry .= 'return 8;';
		$results_output = $bytes . '3132333434359';
		$enum_decl = $flavor === flavor::php ? 'enum TokenKind {case Word=7;}' : 'enum TokenKind {Word=7,}';
		$enum_case = $flavor === flavor::js ? 'TokenKind.Word' : 'TokenKind::Word';
		$enum_sig = static fn(string $name): string => $flavor === flavor::cpp
			? "async TokenKind $name()" : "async function $name():TokenKind";
		$enum_child = $enum_decl . $enum_sig('read_kind') . "{await async_sleep_ms(1);return $enum_case;}";
		$enum_parent = $enum_sig('relay') . '{return await read_kind();}';
		$enum_entry = $decl('TokenKind', 'kind', 'await relay()') . 'echo enum_value(' . $v('kind') . ');return 8;';
		$second_child = str_replace(['TokenKind', 'read_kind'], ['OtherKind', 'read_other'], $enum_child);
		$second_parent = str_replace(['TokenKind', 'relay', 'read_kind'], ['OtherKind', 'relay_other', 'read_other'], $enum_parent);
		$two_entries = str_replace('return 8;', '', $enum_entry)
			. str_replace(['TokenKind', 'relay()', $v('kind')], ['OtherKind', 'relay_other()', $v('other')], $enum_entry);

		[$point, $guard, $make, $relay_point, $access, $empty_point, $empty_guard] = match ($flavor) {
			flavor::php => ['struct Point {public int $x;public string $label;}', 'struct Guard {public function __destruct(){echo "D";}}',
				'async function makePoint(bool $fail):Point', 'async function relayPoint(bool $fail):Point', '->', '$p Point;', '$g Guard;'],
			flavor::cpp => ['struct Point {int x;string label;};', 'struct Guard {public: ~Guard(){echo "D";}};',
				'async Point makePoint(bool fail)', 'async Point relayPoint(bool fail)', '->', 'Point p;', 'Guard g;'],
			flavor::js => ['struct Point {x:int;label:string;}', 'struct Guard {public destructor(){echo "D";}}',
				'async function makePoint(fail:bool):Point', 'async function relayPoint(fail:bool):Point', '.', 'let p:Point;', 'let g:Guard;'],
		};
		$z = match ($flavor) {
			flavor::php => 'public float $z;', flavor::cpp => 'float z;', flavor::js => 'z:float;',
		};
		$ctor = match ($flavor) {
			flavor::php => 'public function __construct(){}', flavor::cpp => 'Point(){}', flavor::js => 'constructor(){}',
		};
		$label = 'heap-backed Point label across await';
		$p = $v('p') . $access;
		$q = $v('q') . $access;
		$point_factory = $flavor === flavor::cpp ? 'Point buildPoint()' : 'function buildPoint():Point';
		$point_child = $failure . $point . $guard . $point_factory . '{' . $empty_point . $p . 'x=7;' . $p . 'label="' . $label . '";return ' . $v('p') . ';}'
			. $make . '{' . $empty_guard . $decl('Point', 'p', 'buildPoint()') . 'await async_sleep_ms(1);if (' . $v('fail') . ') {throw new Failure();}return ' . $v('p') . ';}';
		$point_parent = $relay_point . '{return await makePoint(' . $v('fail') . ');}';
		$point_entry = $decl('Point', 'p', 'await relayPoint(false)') . $decl('Point', 'q', $v('p'))
			. $q . 'x=9;' . $q . 'label[0]=88;echo ' . $p . 'x,' . $q . 'x,(int)' . $p . 'label[0],(int)' . $q . 'label[0];'
			. 'try {await relayPoint(true);}' . $catch('Failure') . '{echo "caught";}return 8;';
		$typed_fn = static fn(string $name, string $type, bool $flag = false): string => match ($flavor) {
			flavor::php => 'function ' . $name . '(' . ($flag ? 'bool $fail' : '') . '):' . $type,
			flavor::cpp => $type . ' ' . $name . '(' . ($flag ? 'bool fail' : '') . ')',
			flavor::js => 'function ' . $name . '(' . ($flag ? 'fail:bool' : '') . '):' . $type,
		};
		$families = [];
		foreach (['points' => 'vector<Point>', 'owner' => 'shared<Point>'] as $name => $type) {
			$factory = $name === 'points'
				? $decl($type, 'item', '[buildPoint()]')
				: $decl($type, 'item', 'new Point()') . $v('item') . $access . 'x=7;';
			$leaf = $typed_fn('build_' . $name, $type) . '{' . $factory . 'return ' . $v('item') . ';}'
				. 'async ' . $typed_fn('leaf_' . $name, $type, true) . '{' . $decl($type, 'item', 'build_' . $name . '()')
				. 'await async_sleep_ms(1);if (' . $v('fail') . ') {throw new Failure();}return ' . $v('item') . ';}';
			$relay_source = 'async ' . $typed_fn('get_' . $name, $type, true) . '{return await leaf_' . $name . '(' . $v('fail') . ');}';
			$families[$name] = [$leaf, $relay_source];
		}
		[$vector_leaf, $vector_relay] = $families['points'];
		[$owner_leaf, $owner_relay] = $families['owner'];
		$owner_leaf .= 'async ' . $typed_fn('emptyOwner', 'shared<Point>') . '{return null;}';
		$vec_checks = $decl('vector<Point>', 'items', 'await get_points(false)') . $decl('vector<Point>', 'dup', $v('items'))
			. $decl('Point', 'replacement', 'buildPoint()') . $v('replacement') . $access . 'x=42;'
			. $v('dup') . '[0]=' . $v('replacement') . ';' . $decl('Point', 'a', $v('items') . '[0]')
			. $decl('Point', 'b', $v('dup') . '[0]') . $v('dup') . $access . 'clear();'
			. 'echo ' . $v('a') . $access . 'x,":",' . $v('b') . $access . 'x,":",count(' . $v('items') . ');'
			. 'try {await get_points(true);}' . $catch('Failure') . '{echo "V";}';
		$owner_checks = $decl('shared<Point>', 'first', 'await get_owner(false)') . $decl('shared<Point>', 'alias', $v('first'))
			. $decl('weak<Point>', 'watch', 'weak(' . $v('first') . ')') . $v('alias') . $access . 'x=19;'
			. 'if (' . $v('alias') . ' === ' . $v('first') . ') {echo ' . $v('first') . $access . 'x;}'
			. $v('first') . '=null;if (lock(' . $v('watch') . ') !== null) {echo "L";}'
			. $v('alias') . '=null;if (lock(' . $v('watch') . ') === null) {echo "X";}'
			. $decl('shared<Point>', 'empty_result', 'await emptyOwner()') . 'if (' . $v('empty_result') . ' === null) {echo "E";}'
			. 'try {await get_owner(true);}' . $catch('Failure') . '{echo "S";}';
		$common = $failure . $point . $point_factory . '{' . $empty_point . $p . 'x=7;return ' . $v('p') . ';}';
		$both = $vector_relay . $owner_relay;
		$all_leaf = $common . $vector_leaf . $owner_leaf;
		$checks = $vec_checks . $owner_checks . 'return 8;';
		$three_parent = $both . 'async ' . $typed_fn('get_value', 'Point') . '{return await leaf_value();}';
		$three_leaf = $all_leaf . 'async ' . $typed_fn('leaf_value', 'Point') . '{return buildPoint();}';
		$three_entry = $decl('Point', 'value_result', 'await get_value()') . 'echo ' . $v('value_result') . $access . 'x;' . $checks;
		// A self link supplies the external strong owner until the live-result caller releases it.
		$link = match ($flavor) {
			flavor::php => 'public shared<Guard> $keep;', flavor::cpp => 'public: shared<Guard> keep;', flavor::js => 'keep:shared<Guard>;',
		};
		$tracked = preg_replace('/\{/', '{' . $link, $guard, 1);
		$observer = 'weak<Guard>';
		$strong = 'shared<Guard>';
		$keep = $p . 'keep';
		$watch = $v('watch');
		$pin = match ($flavor) {
			flavor::php => 'function pin(shared<Guard> $p):void', flavor::cpp => 'void pin(shared<Guard> p)', flavor::js => 'function pin(p:shared<Guard>):void',
		};
		$weak_leaf = $tracked . $pin . '{' . $keep . '=' . $v('p') . ';}' . 'async ' . $typed_fn('observe', $observer, true) . '{'
			. $decl($strong, 'p', 'new Guard()') . $decl($observer, 'watch', 'weak(' . $v('p') . ')')
			. 'if (lock(' . $watch . ') === ' . $v('p') . ') {echo "L";}await async_sleep_ms(1);'
			. 'if (' . $v('fail') . ') {' . $v('p') . '=null;echo "X";}else {pin(' . $v('p') . ');}return ' . $watch . ';}';
		$weak_empty = 'async ' . $typed_fn('emptyWatch', $observer) . '{' . $decl($strong, 'p', 'null') . 'return weak(' . $v('p') . ');}';
		$weak_parent = 'async ' . $typed_fn('relayWatch', $observer, true) . '{return await observe(' . $v('fail') . ');}';
		$weak_entry = $decl($observer, 'watch', 'await relayWatch(false)') . $decl($strong, 'p', 'lock(' . $watch . ')')
			. 'if (' . $v('p') . ' === ' . $keep . ') {echo "I";}' . $keep . '=null;' . $v('p') . '=null;'
			. $decl($observer, 'expired', 'await relayWatch(true)') . $decl($observer, 'emptyWatchResult', 'await emptyWatch()');
		foreach (['expired','emptyWatchResult'] as $name) {
			$weak_entry .= 'if (lock(' . $v($name) . ') === null) {echo "N";}if (' . $v($name) . $access . 'is_empty()) {echo "E";}';
		}
		$weak_entry .= 'return 8;';
		$hash = 'hash<Point,string>';
		$hash_leaf = $common . 'async ' . $typed_fn('buildTable', $hash) . '{'
			. $decl($hash, 'table', '["key" => buildPoint()]') . 'await async_sleep_ms(1);return ' . $v('table') . ';}';
		$hash_parent = 'async ' . $typed_fn('getTable', $hash) . '{return await buildTable();}';
		$hash_entry = $decl($hash, 'original', 'await getTable()') . $decl($hash, 'copy', $v('original'))
			. $decl('Point', 'p', $v('copy') . '["key"]') . $p . 'x=42;' . $v('copy') . '["key"]=' . $v('p') . ';'
			. $decl('Point', 'a', $v('original') . '["key"]') . $decl('Point', 'b', $v('copy') . '["key"]')
			. 'echo ' . $v('a') . $access . 'x,":",' . $v('b') . $access . 'x;return 8;';
		$edited_hash = str_replace([$point, 'x=7;'], [str_replace('{', '{' . $z, $point), 'x=8;'], $hash_leaf);
		$point_leaf_only = 'async ' . $typed_fn('leafPoint', 'Point') . '{await async_sleep_ms(1);return buildPoint();}';
		$point_relay_only = 'async ' . $typed_fn('readPoint', 'Point') . '{return await leafPoint();}';
		$point_read = $decl('Point', 'valueResult', 'await readPoint()') . 'echo ' . $v('valueResult') . $access . 'x;';
		$combined_relay = $point_relay_only . $hash_parent;
		$group_child = $group_parent = $group_entry = $group_output = '';
		// Each public result crosses both a suspending call and a blocking call.
		$cases = [
			['null', 'null', '1'], ['byte', '255', '255'], ['float', '2.5', '2'],
			['int8', '-8', '-8'], ['int16', '-16', '-16'], ['int32', '-32', '-32'],
			['uint16', '65535', '65535'], ['uint32', '4294967295', '4294967295'],
		];
		foreach (['nullable', 'result', 'result_or_false', 'result_or_bool'] as $family) {
			foreach (['bool' => 'false', 'int' => '21', 'string' => '"text"', 'vector<string>' => $v('items')] as $payload => $init) {
				if ($family === 'nullable' && $payload === 'int') continue; // Already published.
				$cases[] = ["$family<$payload>", $init, '1'];
			}
		}
		$cases[] = ['result_or_false<bool>', 'false_sentinel', '0'];
		foreach ($cases as $k => [$type, $init, $expected]) {
			$is_carrier = str_contains($type, '<');
			$is_vector = str_contains($type, 'vector<');
			$leaf = "produce$k";
			$relay_name = "forward$k";
			$item = $v("received$k");
			$group_child .= 'async ' . $typed_fn($leaf, $type) . '{' . ($is_vector ? $decl('vector<string>', 'items', '["first"]') : '')
				. $decl($type, 'item', $init) . 'await async_sleep_ms(1);return ' . $v('item') . ';}';
			$group_parent .= 'async ' . $typed_fn($relay_name, $type) . "{return await $leaf();}";
			$group_entry .= $decl($type, "received$k", "await $relay_name()") . 'echo '
				. ($is_carrier ? "(int)isset($item)" : ($type === 'null' ? "(int)($item === null)" : "(int)$item")) . ',"|";';
			$group_output .= $expected . '|';
			if ($is_vector && str_starts_with($type, 'nullable')) {
				$group_entry .= $decl('vector<string>', 'copy', $item) . $v('copy') . '[0]="edited";'
					. $decl('vector<string>', 'original', $item) . 'echo ' . $v('original') . '[0];';
				$group_output .= 'first';
			}
		}
		$group_entry .= 'return 8;';
		$byte_child = 'async ' . $typed_fn('sample', 'byte') . '{await async_sleep_ms(1);return 17;}';
		$byte_parent = 'async ' . $typed_fn('relaySample', 'uint8') . '{return await sample();}';
		$byte_entry = $decl('int', 'answer', 'await relaySample()') . 'echo ' . $v('answer') . ';return 8;';
		$float_child = 'async ' . $typed_fn('sample', 'float') . '{await async_sleep_ms(1);return 18.5;}';
		$float_parent = 'async ' . $typed_fn('relaySample', 'float64') . '{return await sample();}';

		$project = new Incremental_Project($directory, "$directory/params", $target);
		$steps = [
			[$report, $sum, $entry, null, 'MM4294967298', 4],
			[$report, $extra, $entry, 'Argument count mismatch', '', 0],
			[$fixed, $extra, $entry, null, 'MM4294967298', 2],
			[$fixed, '', $entry, 'Unknown', '', 0],
			[$report, $sum, $entry, null, 'MM4294967298', 2],
			[$report, $sum, 'echo await sum(10,2);' . $entry, null, '12MM4294967298', 3],
			[$report, $sum, $entry, null, 'MM4294967298', 3],
			[$loop_report, $sum, $entry, null, 'MMM4294967302', 1],
			[$loop_report, $extra, $entry, 'Argument count mismatch', '', 0],
			[$loop_report, str_replace('1);return', '0);return', $sum), $entry, null, 'MMM4294967302', 1],
			[$default_report, $reader('=7'), $default_entry, null, '7,42,42,7', 3],
			[$default_report, $reader('=4294967297'), $default_entry, null, '4294967297,42,42,4294967297', 2],
			[$default_report, $reader(''), $default_entry, 'Argument count mismatch', '', 0],
			[$default_report, $reader('=7'), $default_entry, null, '7,42,42,7', 2],
			// Writing the same arguments explicitly should keep every native artifact reusable.
			[str_replace(['read_value()', 'read_value(42)'], ['read_value(7,false)', 'read_value(42,false)'], $default_report),
				$reader('=7'), str_replace(['read_value()', 'read_value(42)'], ['read_value(7,false)', 'read_value(42,false)'], $default_entry),
				null, '7,42,42,7', 0],
			[$owned_parent, $owned_child, $owned_entry, null, $owned_output, 3],
			[$owned_parent, str_replace('=65', '=256', $owned_child), $owned_entry, 'range', '', 0],
			[$owned_parent, str_replace('=65', '=64', $owned_child), $owned_entry, null, str_replace('65', '64', $owned_output), 1],
			[$relay, $int_notify, $consume, null, '103', 3],
			[$relay, $notify, $consume, 'Void call cannot be used as a value', '', 0],
			[$relay, $notify, $discard, null, '10', 3],
			[$relay, $int_notify, $consume, null, '103', 3],
			[$relay, $failure . 'async ' . $void_sig($notify_sig) . '{throw new Failure();}',
				'try {await relay();}' . $catch('Failure') . '{echo "caught";}return 8;', null, 'caught', 3],
			[$bool_parent, $bool_child, $bool_entry, null, 'T', 3],
			[$bool_parent, $notify, $bool_entry, 'Void call cannot be used as a value', '', 0],
			[$bool_parent, str_replace('>0;', '<0;', $bool_child), $bool_entry, null, '', 1],
			[$results_parent, $results_child, $results_entry, null, $results_output, 3],
			[$results_parent, '', $results_entry, 'Unknown', '', 0],
			[$results_parent, str_replace('[31]', '[41]', $results_child), $results_entry, null, str_replace('31', '41', $results_output), 1],
			[$enum_parent, $enum_child, $enum_entry, null, '7', 3],
			[$enum_parent, str_replace('=7', '=512', $enum_child), $enum_entry, null, '512', 3],
			[$enum_parent, str_replace($enum_decl, '', $enum_child), $enum_entry, 'Unknown', '', 0],
			[$enum_parent, $enum_child, $enum_entry, null, '7', 3],
			[$bool_parent, $bool_child, $bool_entry, null, 'T', 3],
			[$enum_parent . $second_parent, $enum_child . $second_child, $two_entries, null, '77', 3],
			[$enum_parent, $enum_child, $enum_entry, null, '7', 3],
			[$point_parent, $point_child, $point_entry, null, 'D7910488Dcaught', 3],
			[$point_parent, str_replace('x=7;', 'x=8;', $point_child), $point_entry, null, 'D8910488Dcaught', 1],
			[$point_parent, str_replace($point, '', $point_child), $point_entry, 'Unknown', '', 0],
			[$point_parent, $point_child, $point_entry, null, 'D7910488Dcaught', 1],
			[$point_parent, str_replace($point, str_replace('{', '{' . $z, $point), $point_child), $point_entry, null, 'D7910488Dcaught', 3],
			[$point_parent, str_replace($point, str_replace('{', '{' . $ctor, $point), $point_child), $point_entry, 'Unpublished async result: Point', '', 0],
			[$point_parent, $point_child, $point_entry, null, 'D7910488Dcaught', 3],
			[$enum_parent, $enum_child, $enum_entry, null, '7', 3],
			[$both, $all_leaf, $checks, null, '7:42:1V19LXES', 3],
			[$both, str_replace('x=7;', 'x=8;', $all_leaf), $checks, null, '8:42:1V19LXES', 1],
			[$both, $common . $vector_leaf, $checks, 'Unknown', '', 0],
			[$both, $all_leaf, $checks, null, '7:42:1V19LXES', 1],
			[$vector_relay, $common . $vector_leaf, $vec_checks . 'return 8;', null, '7:42:1V', 3],
			[$both, $all_leaf, $checks, null, '7:42:1V19LXES', 3],
			[$three_parent, $three_leaf, $three_entry, null, '77:42:1V19LXES', 3],
			[$both, str_replace($point, str_replace('{', '{' . $ctor, $point), $all_leaf), $checks, null, '7:42:1V19LXES', 3],
			[$weak_parent, $weak_leaf . $weak_empty, $weak_entry, null, 'LIDLDXNNE', 3],
			[$weak_parent, str_replace('"X"', '"Y"', $weak_leaf) . $weak_empty, $weak_entry, null, 'LIDLDYNNE', 1],
			[$weak_parent, $tracked . $weak_empty, $weak_entry, 'Unknown', '', 0],
			[$weak_parent, $weak_leaf . $weak_empty, $weak_entry, null, 'LIDLDXNNE', 1],
			[$hash_parent, $hash_leaf, $hash_entry, null, '7:42', 3],
			[$hash_parent, $edited_hash, $hash_entry, null, '8:42', 3],
			[$hash_parent, str_replace($point, '', $hash_leaf), $hash_entry, 'Unknown', '', 0],
			[$hash_parent, $hash_leaf, $hash_entry, null, '7:42', 3],
			[$group_parent, $group_child, $group_entry, 'requires module runtime-numeric', '', 0],
			[$group_parent, $group_child, $group_entry, null, $group_output, 3, true],
			[$group_parent, str_replace('produce3()', 'missingWidth()', $group_child), $group_entry, 'Unknown', '', 0, true],
			[$group_parent, str_replace('-8;', '-7;', $group_child), $group_entry, null, str_replace('-8|', '-7|', $group_output), 1, true],
			[$group_parent, $group_child, $group_entry, 'requires module runtime-numeric', '', 0, false],
			[$group_parent, $group_child, $group_entry, null, $group_output, 1, true],
			[$byte_parent, $byte_child, $byte_entry, null, '17', 3],
			[$byte_parent, '', $byte_entry, 'Unknown', '', 0],
			[$float_parent, $float_child, $byte_entry, null, '18', 3],
			['', '', 'return 8;', null, '', 3],
			[$byte_parent, $byte_child, $byte_entry, null, '17', 3],
			['', $byte_child, str_replace('relaySample()', 'sample()', $byte_entry), null, '17', 3],
			['', '', 'return 8;', null, '', 2],
			[$byte_parent, $byte_child, $byte_entry, null, '17', 3],
			[$combined_relay, $hash_leaf . $point_leaf_only, $point_read . $hash_entry, null, '77:42', 3],
			[$combined_relay, $edited_hash . $point_leaf_only, $point_read . $hash_entry, null, '88:42', 3],
			[$combined_relay, str_replace($point, '', $hash_leaf) . $point_leaf_only, $point_read . $hash_entry, 'Unknown', '', 0],
			[$combined_relay, $hash_leaf . $point_leaf_only, $point_read . $hash_entry, null, '77:42', 3],
			[$hash_parent, $hash_leaf, $hash_entry, null, '7:42', 3],
			[$point_relay_only, $common . $point_leaf_only, $point_read . 'return 8;', null, '7', 3],
			['', '', 'return 8;', null, '', 3],
			[$combined_relay, $hash_leaf . $point_leaf_only, $point_read . $hash_entry, null, '77:42', 3],
			[$combined_relay, $hash_leaf . $point_leaf_only, $point_read . $hash_entry, null, '77:42', 3, false, 'grouped'],
			[$combined_relay, str_replace('x=7;', 'x=8;', $hash_leaf) . $point_leaf_only, $point_read . $hash_entry, null, '88:42', 1],
			[$combined_relay, $edited_hash . $point_leaf_only, $point_read . $hash_entry, null, '88:42', 4],
			[$combined_relay, str_replace($point, '', $hash_leaf) . $point_leaf_only, $point_read . $hash_entry, 'Unknown', '', 0],
			[$combined_relay, $hash_leaf . $point_leaf_only, $point_read . $hash_entry, null, '77:42', 4],
			[$combined_relay, $hash_leaf . $point_leaf_only, $point_read . $hash_entry, null, '77:42', 2, false, 'inline'],
			[$byte_parent, $byte_child, $byte_entry, null, '17', 4, false, 'grouped'],
			['', $byte_child, str_replace('relaySample()', 'sample()', $byte_entry), null, '17', 3],
			['', '', 'return 8;', null, '', 2],
			[$byte_parent, $byte_child, $byte_entry, null, '17', 4],
			[$enum_parent, $enum_child, $enum_entry, null, '7', 4],
			[$three_parent, $three_leaf, $three_entry, null, '77:42:1V19LXES', 4],
			[$weak_parent, $weak_leaf . $weak_empty, $weak_entry, null, 'LIDLDXNNE', 4],
			[$group_parent, $group_child, $group_entry, null, $group_output, 4, true, 'grouped'],
			[$relay, $notify, $discard, null, '10', 4],
			[$relay, $notify, $discard, 'async_task_methods must be inline or grouped', '', 0, false, 'invalid'],
			[$relay, $notify, $discard, null, '10', 2, false, 'inline'],
		];
		foreach ($steps as $i => [$body,$leaf,$caller,$error,$stdout,$objects]) {
			if (isset($steps[$i][6])) {
				$options = isset($steps[$i][7]) ? ['async_task_methods' => $steps[$i][7]] : [];
				$config = json_encode($options + ['entry' => $paths[0], 'sources' => $paths,
					'dependencies' => ['runtime' => ['modules' => $steps[$i][6] ? ['numeric'] : []]]]);
				if (file_get_contents("$directory/module.json") !== $config) {
					file_put_contents("$directory/module.json", $config);
					touch("$directory/module.json", time() + 60 + $i);
				}
			}
			foreach ([$caller,$body,$leaf] as $j => $text) {
				$path = "$directory/$paths[$j]";
				if (file_get_contents($path) === $text) continue;
				file_put_contents($path, $text); touch($path, time() + 60 + $i);
			}
			$fresh = new Incremental_Project($directory, "$directory/fresh-params", $target);
			if ($error !== null) {
				$invalid_mode = ($steps[$i][7] ?? null) === 'invalid';
				rejects(fn() => $project->run(), $error, $invalid_mode);
				rejects(fn() => $project->run(), $error, $invalid_mode);
				rejects(fn() => $fresh->run(), $error, $invalid_mode);
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
			$tasks = array_filter($files, static fn(string $text): bool => str_contains($text, 'struct promise_type final'));
			$group = 'units/' . hash('sha256', 'async:grouped-methods') . '.cpp';
			$grouped = $project->session()->group_async_methods;
			verify(isset($files[$group]) === ($grouped && $tasks !== []), 'Grouped unit demand differs');
			foreach ($tasks as $task) verify(str_contains($task, '#include <scpp/async/frame.hpp>') !== $grouped, 'Task linkage differs');
			$manifest = json_decode(file_get_contents("$directory/params/manifest.json"), true);
			verify(in_array($group, $manifest['translation_units'], true) === isset($files[$group]), 'Grouped native input differs');
			$expected = match ($body) {
				$group_parent => 23, $three_parent => 3, $combined_relay => 2, $point_relay_only => 1, $enum_parent . $second_parent, $both => 2,
				$bool_parent, $weak_parent, $hash_parent, $byte_parent, $float_parent, $enum_parent, $point_parent, $vector_relay => 1,
				'' => $leaf === '' ? 0 : 1, default => null,
			};
			if ($expected !== null) verify(count($tasks) === $expected, "Task count $i: " . count($tasks));
			$all = implode("\n", $files);
			verify(str_contains($all, 'sum_wait(') === ($i === 5), 'Blocking bridge demand differs');
			if ($i === 15 || $i === 17) verify(str_contains($all, 'read_value_(static_cast<::scpp::string&&>('), 'Blocking bridge must transfer its owned string');
			verify(!preg_match('/\btemplate\s*</', $all), 'App emits a template');
			$fixtures[$flavor->name . 'params_' . $i] = ['files' => $files, 'fresh_files' => $fresh_files, 'compiler_ms' => $ms,
				'objects' => $objects, 'exit' => 8, 'stderr' => '', 'stdout_hex' => bin2hex($stdout)];
			if ($i >= 74 && $i <= 81 && isset($argv[1])) {
				$publication = publish_task_payloads($project);
				verify($publication == publish_task_payloads($fresh), 'Payload publication differs from fresh compilation');
				$fixtures[$flavor->name . 'params_' . $i]['payloads'] = $publication;
			}
		}
	} finally {
		remove_tree($directory);
	}
}
if (isset($argv[1])) file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR));
echo "Async cross-file incremental recovery passed\n";
