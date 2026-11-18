<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/compilation.php';
require_once __DIR__ . '/../host/syntax.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Incremental_Project};

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
	$v = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	$member = $flavor === flavor::js ? '.' : '->';
	$field = $v('this') . $member . 'position';
	$decl = static fn(string $name): string => match ($flavor) {
		flavor::php => '$' . $name . ' Counter;',
		flavor::cpp => 'Counter ' . $name . ';',
		flavor::js => 'let ' . $name . ':Counter;',
	};
	$property = match ($flavor) {
		flavor::php => 'public int $position=0;', flavor::cpp => 'int position=0;', flavor::js => 'position:int=0;'
	};
	$signature = match ($flavor) {
		flavor::php => 'public function next(int &$value):bool', flavor::cpp => 'bool next(int &value)', flavor::js => 'next(&value:int):bool'
	};
	$body = 'if (' . $field . '>=3) {return false;}' . $v('value') . '=' . $field . ';' . $field . '++;return true;';
	$model = 'struct Counter implements Iterator<int> {' . $property . $signature . '{' . $body . '}}' . ($flavor === flavor::cpp ? ';' : '');
	$loop = static fn(string $name, string $inside): string => 'foreach (' . $v($name) . ' as ' . $v('value') . ') {' . $inside . '}';
	$echo = 'echo ' . $v('value') . ';';
	$main = $decl('cursor') . $decl('copy') . $v('copy') . '=' . $v('cursor') . ';'
		. $loop('cursor', $echo . 'break;') . $loop('cursor', $echo) . $loop('cursor', $echo)
		. 'echo ' . $v('cursor') . $member . 'position;'
		. $loop('copy', $echo) . 'return 8;';
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('iterator', $source, $flavor), new entry_target(31));
	$start = hrtime(true); $entry = $prepare($model . $main); $before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering changed preparation');
	verify(!str_contains($cpp, 'iteration_limit') && !str_contains($cpp, '[&]'), 'Custom traversal acquired collection bounds or a lambda');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('0123012'), 'compiler_ms' => (hrtime(true) - $start) / 1e6];
	$string_model = str_replace(['Iterator<int>', $signature, $v('value') . '=' . $field . ';'],
		['Iterator<string>', str_replace('int', 'string', $signature), $v('value') . '="payload";'], $model);
	$string_entry = $prepare($string_model . $decl('cursor') . $loop('cursor', $echo) . 'return 8;');
	$fixtures[$flavor->name . '_string'] = ['cpp' => (new Cpp_Emitter())->emit($string_entry), 'stdout_hex' => bin2hex('payloadpayloadpayload')];
	// No captured end: the body feeds the next item into the same borrowed producer state.
	$ready = $v('this') . $member . 'ready';
	$ready_field = match ($flavor) {
		flavor::php => 'public bool $ready=true;', flavor::cpp => 'bool ready=true;', flavor::js => 'ready:bool=true;',
	};
	$feed_body = 'if (!' . $ready . ') {return false;}' . $ready . '=false;' . $v('value') . '=' . $field . ';return true;';
	$feed = str_replace([$property, $body], [$property . $ready_field, $feed_body], $model);
	$feed_entry = $prepare($feed . $decl('cursor') . $loop('cursor', $echo . 'if (' . $v('value') . '<2) {'
		. $v('cursor') . $member . 'position++;' . $v('cursor') . $member . 'ready=true;}') . 'return 8;');
	$fixtures[$flavor->name . '_feed'] = ['cpp' => (new Cpp_Emitter())->emit($feed_entry), 'stdout_hex' => bin2hex('012')];
	$existing = match ($flavor) {
		flavor::php => '$value int=77;', flavor::cpp => 'int value=77;', flavor::js => 'let value:int=77;',
	};
	$false_model = str_replace($body, $v('value') . '=99;return false;', $model);
	$false_entry = $prepare($false_model . $decl('cursor') . $existing . $loop('cursor', $echo) . $echo . 'return 8;');
	$fixtures[$flavor->name . '_false_output'] = ['cpp' => (new Cpp_Emitter())->emit($false_entry), 'stdout_hex' => bin2hex('77')];
	// A shared receiver is retained even when the caller releases its original owner.
	$shared = match ($flavor) {
		flavor::php => '$cursor=new Counter();', flavor::cpp => 'shared<Counter> cursor=new Counter();', flavor::js => 'let cursor:shared<Counter> = new Counter();'
	};
	$entry = $prepare($model . $shared . $loop('cursor', $v('cursor') . '=null;' . $echo) . 'return 8;');
	$fixtures[$flavor->name . '_shared'] = ['cpp' => (new Cpp_Emitter())->emit($entry), 'stdout_hex' => bin2hex('012')];
	$entry = $prepare($model . $decl('cursor') . $loop('cursor', $echo . 'if (' . $v('value') . '<2) {continue;}') . 'return 8;');
	$fixtures[$flavor->name . '_continue'] = ['cpp' => (new Cpp_Emitter())->emit($entry), 'stdout_hex' => bin2hex('012')];
	rejects(fn() => $prepare(str_replace(' implements Iterator<int>', '', $model) . $main), 'iterable');
	rejects(fn() => $prepare(str_replace('Iterator<int>', 'Iterator<int,string>', $model) . $main), 'Iterator<T>');
	rejects(fn() => $prepare(str_replace('Iterator<int>', 'Iterator<string>', $model) . $main), 'Iterator requires');
	rejects(fn() => $prepare(str_replace('&', '', $model) . $main), 'Iterator requires');
	rejects(fn() => $prepare(str_replace('next(', 'wrong(', $model) . $main), 'Iterator requires');
	rejects(fn() => $prepare($model . $decl('cursor') . 'foreach (' . $v('cursor') . ' as ' . $v('key') . '=>' . $v('value') . ') {}return 8;'), 'key binding');
	$static_signature = match ($flavor) {
		flavor::php => str_replace('public function', 'public static function', $signature),
		flavor::cpp, flavor::js => 'static ' . $signature,
	};
	rejects(fn() => $prepare(str_replace($signature, $static_signature, $model) . $main), 'Iterator requires');
	$bad_result = $flavor === flavor::cpp ? str_replace('bool next', 'int next', $signature) : str_replace(':bool', ':int', $signature);
	rejects(fn() => $prepare(str_replace($signature, $bad_result, $model) . $main), 'Iterator requires');
	$bad = match ($flavor) {
		flavor::php => str_replace('public function next', 'private function next', $model),
		flavor::cpp => str_replace('bool next', 'private: bool next', $model),
		flavor::js => str_replace('next(', 'private next(', $model),
	};
	rejects(fn() => $prepare($bad . $main), 'Iterator requires');
	$directory = sys_get_temp_dir() . '/scpp-iterator-' . bin2hex(random_bytes(8)); mkdir($directory);
	try {
		$provider = $directory . '/counter.' . $flavor->name;
		file_put_contents($directory . '/main.' . $flavor->name, $main);
		file_put_contents($directory . '/module.json', json_encode(['entry' => 'main.' . $flavor->name, 'sources' => ['main.' . $flavor->name, basename($provider)]]));
		$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31)); $stamp = time(); $caller = null;
		foreach ([$model, str_replace('>=3', '>=2', $model), $bad, str_replace(' implements Iterator<int>', '', $model), '', $model] as $step => $text) {
			file_put_contents($provider, $text); touch($provider, ++$stamp);
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			if ($step >= 2 && $step <= 4) {
				rejects(fn() => $project->run(), ''); rejects(fn() => $project->run(), ''); rejects(fn() => $fresh->run(), ''); continue;
			}
			$project->run(); $fresh->run(); $caller ??= $project->session()->entry->entry->ast;
			verify($caller === $project->session()->entry->entry->ast, 'Caller reparsed');
			$files = artifacts($directory . '/out'); verify($files === artifacts($directory . '/fresh'), 'Fresh build differs');
			$fixtures[$flavor->name . '_edit_' . $step] = ['files' => $files, 'stdout_hex' => bin2hex($step === 1 ? '01201' : '0123012')];
			verify($project->run()['preparation']['prepared'] === 0, 'No-op prepared bodies');
		}
	} finally {
		remove_tree($directory);
	}
}
if (isset($argv[1])) file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
echo "Iterator contract, borrowed progress, copying and incremental recovery passed\n";
