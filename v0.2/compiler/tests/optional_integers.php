<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Incremental_Project, type_availability, Program_Preparation, Symbol_Publication, type_registry};

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
$widths = ['int8' => [-128, 127], 'int16' => [-32768, 32767], 'int32' => [-2147483648, 2147483647],
	'uint16' => [0, 65535], 'uint32' => [0, 4294967295]];
verify(type_registry::resolve('uint64') === null, 'uint64 must remain unavailable');
foreach (flavor::cases() as $flavor) {
	$var = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	$decl = static fn(string $type, string $name, string $value): string => match ($flavor) {
		flavor::php => '$' . "$name $type=$value;", flavor::cpp => "$type $name=$value;",
		flavor::js => "let $name:$type=$value;",
	};
	$fn = static fn(string $name, string $type, string $body): string =>
		($flavor === flavor::cpp ? "$type $name()" : "function $name():$type") . '{' . $body . '}';
	$prepare = static function (string $code, bool $enabled = true) use ($flavor) {
		$publication = new Symbol_Publication();
		$file = (new Source_Frontend())->collect(new source_file('widths', $code, $flavor), $publication,
			new type_availability($enabled ? ['runtime-numeric'] : []));
		return Program_Preparation::prepare('entry', ['entry' => $file], new entry_target(31),
			$publication->publish(['entry' => $file]))->entry;
	};
	$code = '';
	$expected = '';
	foreach ($widths as $type => [$minimum, $maximum]) {
		verify(type_registry::required_module($type) === 'runtime-numeric', 'Wrong module owner');
		rejects(fn() => $prepare($decl($type, 'x', '1') . 'return 8;', false), 'requires module runtime-numeric');
		foreach ([$maximum + 1] as $bad) {
			rejects(fn() => $prepare($decl($type, 'x', (string)$bad) . 'return 8;'), 'outside ' . $type);
		}
		$x = $var('x');
		$body = $decl($type, 'x', (string)$minimum) . 'echo (int)' . $x . ',"|";'
			. $x . '=(' . $type . ')' . $maximum . ';echo (int)' . $x . ',"|";'
			. $x . '=(' . $type . ')8;';
		foreach (['+=2', '-=3', '*=4', '/=3', '%=4', '<<=2', '>>=1', '|=8', '&=10', '^=2'] as $operation) {
			$body .= $x . $operation . ';';
		}
		$body .= 'echo (int)' . $x . ',"|";' . $x . '++;' . $x . '--;'
			. 'echo (int)(-' . $x . '), "|";return;';
		$code .= $fn('test_' . $type, 'void', $body) . 'test_' . $type . '();';
		$negative = $minimum < 0 ? -8 : $maximum - 7;
		$expected .= "$minimum|$maximum|8|$negative|";
	}
	// Every available integer pair: same identity preserves it, other admitted pairs execute in int64.
	$types = array_merge(['int64', 'uint8'], array_keys($widths));
	foreach ($types as $left) foreach ($types as $right) {
		$unsignedPair = str_starts_with($left, 'u') && str_starts_with($right, 'u') && $left !== $right;
		$expr = "(($left)8 + ($right)2)";
		$code .= 'echo (int)((' . $left . ')8 === (' . $right . ')8),"|";';
		$expected .= ($left === $right ? '1' : '0') . '|';
		if ($unsignedPair) {
			foreach (['+', '-', '*', '/', '%', '&', '|', '^', '==', '<', '<=>'] as $op) {
				rejects(fn() => $prepare("echo (int)(($left)8 $op ($right)2);return 8;"), 'Unsupported');
			}
			rejects(fn() => $prepare($decl($left, 'x', '8') . $var('x') . "+=($right)2;return 8;"), 'Unsupported');
			// Explicit conversions remain available even where operator promotion is deferred.
			$name = 'unsigned_' . $left . '_to_' . $right;
			$code .= $fn($name, $left, 'return 8;')
				. $decl($right, $name, $name . '()') . 'echo (int)' . $var($name) . ',"|";';
			$expected .= '8|';
			continue;
		}
		$inferred = match ($flavor) {
			flavor::php => '$result=' . $expr . ';',
			flavor::cpp => 'auto result=' . $expr . ';',
			flavor::js => 'let result=' . $expr . ';',
		};
		$probe = $prepare($inferred . 'return 8;');
		verify($probe->body()->statements[0]->type_def() === type_registry::resolve($left === $right ? $left : 'int64'),
			'Wrong retained promotion identity');
		$code .= 'echo (int)' . $expr . ',"|";';
		$expected .= '10|';
		$code .= 'echo (int)((' . $left . ')8 <=> (' . $right . ')2),"|";';
		$expected .= '1|';
		// Dynamic conversion dispatch must choose the real source representation.
		$name = 'from_' . $left . '_to_' . $right;
		$code .= $fn($name, $left, 'return 8;')
			. $decl($right, $name, $name . '()') . 'echo (int)' . $var($name) . ',"|";';
		$expected .= '8|';
	}
	$code .= $decl('uint16', 'product', '65535') . $var('product') . '*=(' . 'uint16)65535;'
		. 'echo (int)' . $var('product') . ',"|";';
	$expected .= '1|';
	$entry = $prepare($code . 'return 8;');
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering changed retained facts');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected)];
	// Runtime checked narrowing must leave the destination unchanged on failure.
	foreach (['int8' => 128, 'int16' => 32768, 'int32' => 2147483648, 'uint16' => 65536, 'uint32' => 4294967296] as $type => $bad) {
		$source = $fn('bad', 'int', 'return ' . $bad . ';') . $decl($type, 'x', '1')
			. $var('x') . '+=bad();return 8;';
		$fixtures[$flavor->name . '_overflow_' . $type] = [
			'cpp' => (new Cpp_Emitter())->emit($prepare($source)), 'stdout_hex' => '', 'catch_out_of_range' => true];
	}

	foreach ($widths as $type => [$minimum, $maximum]) {
		$source = $fn('below', 'int', 'return ' . ($minimum - 1) . ';')
			. $decl($type, 'x', 'below()') . 'return 8;';
		$fixtures[$flavor->name . '_negative_' . $type] = [
			'cpp' => (new Cpp_Emitter())->emit($prepare($source)), 'stdout_hex' => '', 'catch_out_of_range' => true];
		$source = $fn('number', 'float', 'return 12.75;') . $decl($type, 'x', 'number()')
			. $decl($type, 'b', 'true') . 'echo (int)' . $var('x') . ',(int)' . $var('b') . ';return 8;';
		$fixtures[$flavor->name . '_float_' . $type] = [
			'cpp' => (new Cpp_Emitter())->emit($prepare($source)), 'stdout_hex' => bin2hex('121')];
	}
	// Independent retained projects, signature edits, deletion, failure retries and module changes.
	$directory = sys_get_temp_dir() . '/scpp-widths-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		$stamp = time();
		$manifest = static function (bool $enabled) use ($directory, $flavor, &$stamp): void {
			file_put_contents($directory . '/module.json', json_encode(['sources' => ['main.' . $flavor->name, 'provider.' . $flavor->name],
				'dependencies' => ['runtime' => ['modules' => $enabled ? ['numeric'] : []]]]));
			touch($directory . '/module.json', ++$stamp);
		};
		$manifest(true);
		file_put_contents($directory . '/main.' . $flavor->name,
			$decl('int16', 'x', '8') . $var('x') . '+=value();echo (int)' . $var('x') . ';return 8;');
		$path = $directory . '/provider.' . $flavor->name;
		$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31));
		foreach (['int8', 'uint32', 'bool', '', 'int32'] as $index => $type) {
			file_put_contents($path, $type === '' ? '' : $fn('value', $type, 'return ' . ($type === 'bool' ? 'true' : '2') . ';'));
			touch($path, ++$stamp);
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			if ($type === 'bool' || $type === '') {
				$message = $type === '' ? 'value' : 'Unsupported';
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $fresh->run(), $message);
				continue;
			}
			$project->run();
			$fresh->run();
			verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Recovery differs from fresh');
			$fixtures[$flavor->name . '_recovery_' . $index] = ['files' => artifacts($directory . '/out'), 'stdout_hex' => bin2hex('10')];
			verify($project->run()['preparation']['prepared'] === 0, 'No-op prepared bodies');
		}
		$manifest(false);
		$disabled = new Incremental_Project($directory, $directory . '/disabled', new entry_target(31));
		rejects(fn() => $disabled->run(), 'requires module runtime-numeric');
		rejects(fn() => $project->run(), 'requires module runtime-numeric');
		$manifest(true);
		$project->run();
		$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
		$fresh->run();
		verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Module recovery differs from fresh');
		verify(!$disabled->session()->type_availability->allows('runtime-numeric'), 'Independent session changed');
	} finally {
		remove_tree($directory);
	}
}
if (isset($argv[1])) file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
echo "Optional integer widths: module isolation, numeric families and incremental recovery passed\n";
