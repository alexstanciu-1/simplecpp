<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Incremental_Project, type_availability, Program_Preparation, Symbol_Publication};

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
	$var = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	$sep = $flavor === flavor::js ? '.' : '::';
	$decl = static fn(string $type, string $name, string $value): string => match ($flavor) {
		flavor::php => '$' . "$name $type=$value;", flavor::cpp => "$type $name=$value;", flavor::js => "let $name:$type=$value;",
	};
	$enum = static function (string $name, array $cases, string $backing = '') use ($flavor): string {
		$text = 'enum ' . $name . ($backing === '' ? '' : ':' . $backing) . '{';
		foreach ($cases as $case => $value) $text .= ($flavor === flavor::php ? 'case ' : '') . $case . '=' . $value . ($flavor === flavor::php ? ';' : ',');
		return $text . '}' . ($flavor === flavor::cpp ? ';' : '');
	};
	$prepare = static function (string $code) use ($flavor) {
		$publication = new Symbol_Publication();
		$file = (new Source_Frontend())->collect(new source_file('construction', $code, $flavor), $publication,
			new type_availability(['runtime-numeric']));
		return Program_Preparation::prepare('entry', ['entry' => $file], new entry_target(31),
			$publication->publish(['entry' => $file]))->entry;
	};
	$definition = $enum('Kind', ['Zero' => 0, 'One' => 1, 'Last' => 255], 'byte');
	$call = static fn(string $value): string => 'enum_from_value(Kind' . $sep . 'class,' . $value . ')';
	$source = $definition;
	foreach (['int', 'byte', 'uint8', 'int8', 'int16', 'int32', 'uint16', 'uint32'] as $index => $type) {
		$source .= $decl($type, 'v' . $index, '1') . $decl('Kind', 'k' . $index, $call($var('v' . $index)))
			. 'echo (int)enum_value(' . $var('k' . $index) . ');';
	}
	$source .= $enum('Edges', ['B' => 255, 'S' => -128, 'M' => -32768, 'L' => -2147483648, 'U' => 65535, 'W' => 4294967295]);
	foreach (['byte' => 255, 'int8' => -128, 'int16' => -32768, 'int32' => -2147483648, 'uint16' => 65535, 'uint32' => 4294967295] as $type => $value) {
		$source .= $decl($type, 'edge_' . $type, (string)$value)
			. 'echo enum_value(enum_from_value(Edges' . $sep . 'class,' . $var('edge_' . $type) . ')),"|";';
	}
	$source .= $enum('Wide', ['Low' => PHP_INT_MIN, 'High' => PHP_INT_MAX]);
	foreach ([PHP_INT_MIN, PHP_INT_MAX] as $value) $source .= 'echo enum_value(enum_from_value(Wide' . $sep . 'class,' . ($value === PHP_INT_MIN ? '(-9223372036854775807 - 1)' : $value) . ')),"|";';
	$source .= 'return 8;';
	$start = hrtime(true);
	$cpp = (new Cpp_Emitter())->emit($prepare($source));
	$fixtures[$flavor->name . '_widths'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('11111111255|-128|-32768|-2147483648|65535|4294967295|' . PHP_INT_MIN . '|' . PHP_INT_MAX . '|'),
		'compiler_ms' => (hrtime(true) - $start) / 1e6];
	foreach ([-1, 2, 256, 257, PHP_INT_MIN, PHP_INT_MAX] as $index => $value) {
		$fixtures[$flavor->name . '_miss_' . $index] = ['cpp' => (new Cpp_Emitter())->emit($prepare($definition . $call($value === PHP_INT_MIN ? '(-9223372036854775807 - 1)' : (string)$value) . ';return 8;')),
			'stdout_hex' => '', 'catch_runtime_error' => 'Invalid value for enum helper'];
	}
	foreach (['true', '1.0', '"1"', 'null', 'Kind' . $sep . 'One'] as $value) {
		rejects(fn() => $prepare($definition . $call($value) . ';return 8;'), 'enum_from_value requires an integer');
	}
	foreach (['enum_from_value(1,1)', 'enum_from_value(Kind' . $sep . 'class)', 'enum_from_value(Kind' . $sep . 'class,1,2)'] as $bad) {
		rejects(fn() => $prepare($definition . $bad . ';return 8;'), 'enum_from_value requires an enum type marker and one integer');
	}
	rejects(fn() => $prepare('enum_from_value(Missing' . $sep . 'class,1);return 8;'), 'enum_from_value target must be an enum');
	rejects(fn() => $prepare($definition . 'echo Kind' . $sep . 'class;return 8;'), 'Type marker is only valid');
	rejects(fn() => $prepare($definition . 'strlen(Kind' . $sep . 'class);return 8;'), 'Type marker is only valid');
	foreach (['mixed', 'dynamic', '?int'] as $type) {
		rejects(fn() => $prepare($definition . $decl($type, 'boxed', '1') . $call($var('boxed')) . ';return 8;'), 'enum_from_value requires an integer');
	}
	$shadow = match ($flavor) {
		flavor::php => 'function enum_from_value(int $x):int{return $x;}',
		flavor::cpp => 'int enum_from_value(int x){return x;}',
		flavor::js => 'function enum_from_value(x:int):int{return x;}',
	};
	$fixtures[$flavor->name . '_shadow'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($shadow . 'echo enum_from_value(9);return 8;')), 'stdout_hex' => bin2hex('9')];
	$counter = match ($flavor) {
		flavor::php => 'function next_value(int &$n):int{$n+=1;return $n;}',
		flavor::cpp => 'int next_value(int &n){n+=1;return n;}',
		flavor::js => 'function next_value(&n:int):int{n+=1;return n;}',
	};
	$namespace = match ($flavor) {
		flavor::php => '\\', flavor::cpp => '::', flavor::js => '.',
	};
	$once = 'namespace Lib {' . $definition . 'use Lib' . $namespace . 'Kind;' . $counter . $decl('int', 'n', '0')
		. 'echo (int)enum_value(' . $call('next_value(' . $var('n') . ')') . '),":",' . $var('n') . ';return 8;}';
	$fixtures[$flavor->name . '_once_import'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($once)), 'stdout_hex' => bin2hex('1:1')];
	// Unused declarations do not get a checked-construction helper.
	verify(!str_contains((new Cpp_Emitter())->emit($prepare($definition . 'return 8;')), 'scpp_enum_from_value_'), 'Unused helper emitted');

	$directory = sys_get_temp_dir() . '/scpp-enum-from-value-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		$paths = [];
		foreach (['main', 'kind', 'idle'] as $name) $paths[$name] = $directory . '/' . $name . '.' . $flavor->name;
		file_put_contents($directory . '/module.json', json_encode(['entry' => basename($paths['main']), 'sources' => array_map('basename', array_values($paths))]));
		file_put_contents($paths['idle'], $flavor === flavor::cpp ? 'int idle(){return 9;}' : 'function idle():int{return 9;}');
		$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31));
		$stamp = time();
		$caller = null;
		foreach ([[1,false,false], [1,true,false], [300,true,false], [300,true,true], [300,true,false], [300,false,false], [1,true,false]] as $index => [$value,$construct,$broken]) {
			$main = 'echo enum_value(' . ($construct ? $call('enum_value(Kind' . $sep . 'One)') : 'Kind' . $sep . 'One') . ');return 8;';
			$kind = $broken ? '' : $enum('Kind', ['One' => $value]);
			foreach (['main' => $main, 'kind' => $kind] as $name => $code) {
				if (!is_file($paths[$name]) || file_get_contents($paths[$name]) !== $code) {
					file_put_contents($paths[$name], $code); touch($paths[$name], ++$stamp);
				}
			}
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			if ($broken) {
				rejects(fn() => $project->run(), 'enum_from_value target must be an enum');
				rejects(fn() => $project->run(), 'enum_from_value target must be an enum');
				rejects(fn() => $fresh->run(), 'enum_from_value target must be an enum');
				continue;
			}
			$start = hrtime(true); $result = $project->run(); $compiler_ms = (hrtime(true) - $start) / 1e6;
			$fresh->run();
			if ($index === 2) verify($project->session()->entry->entry->ast === $caller, 'Enum edit reparsed caller');
			$caller = $project->session()->entry->entry->ast;
			$files = artifacts($directory . '/out');
			verify($files === artifacts($directory . '/fresh'), 'Incremental artifacts differ from fresh');
			verify(str_contains(implode('', $files), 'scpp_enum_from_value_') === $construct, 'Stale/missing helper');
			foreach ($files as $path => $text) if (str_ends_with($path, '.hpp')) verify(!str_contains($text, 'stdexcept'), 'Helper implementation leaked into header');
			$fixtures[$flavor->name . '_incremental_' . $index] = ['files' => $files, 'stdout_hex' => bin2hex((string)$value), 'compiler_ms' => $compiler_ms];
			verify($project->run()['preparation']['prepared'] === 0, 'No-op prepares bodies');
			if ($index === 2) {
				file_put_contents($paths['kind'], $enum('Kind', ['One' => $value, 'Duplicate' => $value]));
				touch($paths['kind'], ++$stamp);
				rejects(fn() => $project->run(), 'Duplicate enum value');
				rejects(fn() => $project->run(), 'Duplicate enum value');
				file_put_contents($paths['kind'], $kind); touch($paths['kind'], ++$stamp);
				$project->run();
				verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Duplicate recovery differs');
			}
		}
	} finally {
		remove_tree($directory);
	}
}
if (isset($argv[1])) file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
echo "Enum checked construction: widths, membership, diagnostics and incremental/fresh recovery passed\n";
