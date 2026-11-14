<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Incremental_Project, type_availability, Program_Preparation, Symbol_Publication, type_registry, enum_type_definition};

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
	$case_ref = static fn(string $type, string $case): string => $type . ($flavor === flavor::js ? '.' : '::') . $case;
	$decl = static fn(string $type, string $name, string $value): string => match ($flavor) {
		flavor::php => '$' . "$name $type=$value;", flavor::cpp => "$type $name=$value;", flavor::js => "let $name:$type=$value;",
	};
	$enum = static function (string $name, ?string $backing, array $cases) use ($flavor): string {
		$text = 'enum ' . $name . ($backing === null ? '' : ':' . $backing) . '{';
		foreach ($cases as $case => $value) $text .= ($flavor === flavor::php ? 'case ' : '') . $case . '=' . $value . ($flavor === flavor::php ? ';' : ',');
		return $text . '}' . ($flavor === flavor::cpp ? ';' : '');
	};
	$fn = static fn(string $name, string $type, string $body): string =>
		($flavor === flavor::cpp ? "$type $name()" : "function $name():$type") . '{' . $body . '}';
	$prepare = static function (string $code, bool $numeric = true) use ($flavor) {
		$publication = new Symbol_Publication();
		$file = (new Source_Frontend())->collect(new source_file('enums', $code, $flavor), $publication,
			new type_availability($numeric ? ['runtime-numeric'] : []));
		return Program_Preparation::prepare('entry', ['entry' => $file], new entry_target(31),
			$publication->publish(['entry' => $file]))->entry;
	};
	$bounds = [
		[null, 1, 255, 'uint8', 1], [null, 1, 256, 'uint16', 2], [null, 1, 65536, 'uint32', 4],
		[null, 1, 4294967295, 'uint32', 4], [null, -1, 2147483648, 'int64', 8],
		[null, 1, 4294967296, 'int64', 8], [null, -128, 127, 'int8', 1], [null, -129, 127, 'int16', 2],
		[null, -1, 200, 'int16', 2], [null, -32769, 0, 'int32', 4], [null, -2147483649, 0, 'int64', 8],
		['int', 1, 300, 'uint16', 2], ['int64', 1, 2, 'int64', 8], ['uint16', 1, 2, 'uint16', 2],
		['byte', 0, 255, 'uint8', 1], ['int8', -128, 127, 'int8', 1],
		['int64', PHP_INT_MIN, PHP_INT_MAX, 'int64', 8],
	];
	$source = '';
	$expected = '';
	$assertions = '';
	foreach ($bounds as $index => [$backing, $low, $high, $storage, $bytes]) {
		$name = 'Kind' . $index;
		$source .= $enum($name, $backing, ['Low' => $low, 'High' => $high])
			. $decl($name, 'v' . $index, $case_ref($name, 'High'))
			. 'echo (int)enum_value(' . $var('v' . $index) . '),"|";';
		$expected .= $high . '|';
		$assertions .= 'static_assert(sizeof(App::scpp_records::' . $name . ') == ' . $bytes . ');' . "\n";
	}
	$source .= $enum('Radix', null, ['Hex' => '0x100', 'Oct' => '0o1000']);
	$source .= $fn('select', 'Radix', 'return ' . $case_ref('Radix', 'Hex') . ';')
		. $decl('Radix', 'first', 'select()') . $decl('Radix', 'copy', $var('first'))
		. $var('copy') . '=' . $case_ref('Radix', 'Oct') . ';'
		. 'echo (int)(' . $var('first') . '==' . $case_ref('Radix', 'Hex') . '),'
		. '(int)(' . $var('first') . '===' . $case_ref('Radix', 'Hex') . '),'
		. '(int)(' . $var('copy') . '!=' . $var('first') . '),'
		. '(int)(' . $var('copy') . '!==' . $var('first') . '),"|";';
	$expected .= '1111|';
	$parameter = match ($flavor) {
		flavor::php => 'Radix $x', flavor::cpp => 'Radix x', flavor::js => 'x:Radix',
	};
	$source .= ($flavor === flavor::cpp ? 'Radix identity(' : 'function identity(') . $parameter . ')'
		. ($flavor === flavor::cpp ? '' : ':Radix') . '{return ' . $var('x') . ';}'
		. 'echo (int)enum_value(identity(' . $var('first') . '));return 8;';
	$expected .= '256';
	$started = hrtime(true);
	$entry = $prepare($source);
	$frontend_prepare_ms = (hrtime(true) - $started) / 1e6;
	foreach ($bounds as $index => [$backing, $low, $high, $storage]) {
		$type = $entry->file->declarations['class:Kind' . $index]->definition;
		verify($type->storage()->name === $storage, 'Storage selected from count rather than extrema');
		verify($type->backing() === type_registry::resolve($backing ?? 'int'), 'Backing identity was compacted');
	}
	$before = serialize($entry);
	$started = hrtime(true);
	$cpp = (new Cpp_Emitter())->emit($entry);
	$emission_ms = (hrtime(true) - $started) / 1e6;
	verify($before === serialize($entry), 'Lowering mutated prepared enum facts');
	$fixtures[$flavor->name] = ['cpp' => $cpp . "\n" . $assertions, 'stdout_hex' => bin2hex($expected),
		'frontend_prepare_ms' => $frontend_prepare_ms, 'emission_ms' => $emission_ms];

	$methods = match ($flavor) {
		flavor::php => 'struct Box { public function __construct(Radix $x) { echo enum_value($x); } '
			. 'public function identity(Radix $x):Radix{return $x;} } $b Box(Radix::Hex); echo enum_value($b->identity(Radix::Oct));',
		flavor::cpp => 'struct Box { Box(Radix x) { echo enum_value(x); } Radix identity(Radix x){return x;} }; '
			. 'Box b(Radix::Hex); echo enum_value(b->identity(Radix::Oct));',
		flavor::js => 'struct Box { constructor(x:Radix) { echo enum_value(x); } identity(x:Radix):Radix{return x;} } '
			. 'let b:Box(Radix.Hex); echo enum_value(b.identity(Radix.Oct));',
	};
	$fixtures[$flavor->name . '_methods'] = ['cpp' => (new Cpp_Emitter())->emit($prepare(
		$enum('Radix', null, ['Hex' => 256, 'Oct' => 512]) . $methods . 'return 8;')), 'stdout_hex' => bin2hex('256512')];

	$base = $enum('Kind', null, ['First' => 1, 'Second' => 300]);
	$first = $case_ref('Kind', 'First');
	foreach ([
		$base . 'echo (int)(' . $first . '==1);' => 'Unsupported',
		$base . 'echo (int)(1===' . $first . ');' => 'Unsupported',
		$base . 'echo (int)(' . $first . '+' . $first . ');' => 'Unsupported',
		$base . 'if (' . $first . ') {}' => 'condition',
		$base . $decl('Kind', 'x', '1') => 'conversion',
		$base . $decl('int', 'x', $first) => 'conversion',
		$base . 'echo enum_value(1);' => 'requires an enum',
		$base . 'echo enum_value();' => 'requires one enum',
		$base . 'echo enum_value(' . $case_ref('Kind', 'Missing') . ');' => 'Unknown enum case',
		$base . 'struct Kind {}' => 'Duplicate',
		$enum('Bad', 'byte', ['Bad' => 256]) => 'outside uint8',
		$enum('Bad', 'uint16', ['Bad' => -1]) => 'outside uint16',
		$enum('Bad', 'int8', ['Bad' => -129]) => 'outside int8',
		$enum('Bad', 'uint64', ['Bad' => 1]) => 'available integer',
		$enum('Bad', 'string', ['Bad' => '"a"']) => 'integer literal',
		$enum('Bad', null, []) => 'at least one case',
		$enum('Bad', null, ['A' => '0x10', 'B' => '16']) => 'Duplicate enum value',
		$enum('Bad', null, ['A' => '-0', 'B' => '+0']) => 'Duplicate enum value',
		$base . $enum('Other', null, ['First' => 1]) . 'echo (int)(' . $first . '==' . $case_ref('Other', 'First') . ');' => 'Unsupported',
	] as $invalid => $message) rejects(fn() => $prepare($invalid . 'return 8;'), $message);
	$duplicate = str_replace('Second', 'First', $base);
	rejects(fn() => $prepare($duplicate . 'return 8;'), 'Duplicate enum case');
	if ($flavor === flavor::php) rejects(fn() => $prepare($base . 'echo enum_value(Kind::$First);return 8;'), 'Unknown static property');
	// Different nominal declaration kinds still share the one owning duplicate chain.
	$publication = new Symbol_Publication();
	$a = (new Source_Frontend())->collect(new source_file('a', $base . 'return 8;', $flavor), $publication);
	$b = (new Source_Frontend())->collect(new source_file('b', 'struct Kind {}', $flavor), $publication);
	rejects(fn() => Program_Preparation::prepare('a', ['a' => $a, 'b' => $b], new entry_target(31),
		$publication->publish(['a' => $a, 'b' => $b])), 'Duplicate');
	$prepare($base . 'return 8;', false);
	rejects(fn() => $prepare($enum('Fixed', 'uint16', ['A' => 1]) . 'return 8;', false), 'requires module');

	// A user function shadows the compiler helper through the normal watched callable lookup.
	$shadow = match ($flavor) {
		flavor::php => 'function enum_value(int $x):int{return $x;}',
		flavor::cpp => 'int enum_value(int x){return x;}',
		flavor::js => 'function enum_value(x:int):int{return x;}',
	};
	$fixtures[$flavor->name . '_shadow'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($shadow . 'echo enum_value(9);return 8;')), 'stdout_hex' => bin2hex('9')];

	$directory = sys_get_temp_dir() . '/scpp-enums-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', json_encode(['sources' => ['main.' . $flavor->name, 'provider.' . $flavor->name]]));
		$sep = match ($flavor) {
			flavor::php => '\\', flavor::cpp => '::', flavor::js => '.',
		};
		$main = 'use Lib' . $sep . 'Kind; use function Lib' . $sep . 'pick;'
			. $decl('Kind', 'x', 'pick()') . 'echo (int)enum_value(' . $var('x') . '),":",(int)(' . $var('x') . '===' . $case_ref('Kind', 'Second') . ');return 8;';
		file_put_contents($directory . '/main.' . $flavor->name, $main);
		$path = $directory . '/provider.' . $flavor->name;
		$stamp = time();
		$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31));
		$caller = null;
		foreach ([300, 70000, -1, 1, null, 'missing_case', 256] as $index => $value) {
			$provider = $value === null ? '' : 'namespace Lib {' . $enum('Kind', null, ['First' => 1, 'Second' => $value])
				. $fn('pick', 'Kind', 'return ' . $case_ref('Kind', 'Second') . ';') . '}';
			if ($value === 'missing_case') $provider = 'namespace Lib {' . $enum('Kind', null, ['First' => 1])
				. $fn('pick', 'Kind', 'return ' . $case_ref('Kind', 'First') . ';') . '}';
			file_put_contents($path, $provider);
			touch($path, ++$stamp);
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			if ($value === 1 || $value === null || $value === 'missing_case') {
				$message = $value === 1 ? 'Duplicate enum value' : 'Kind';
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $fresh->run(), $message);
				continue;
			}
			$project->run();
			$fresh->run();
			$caller ??= $project->session()->entry->entry->ast;
			verify($project->session()->entry->entry->ast === $caller, 'Unchanged caller AST was replaced');
			verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Enum incremental recovery differs from fresh');
			$fixtures[$flavor->name . '_recovery_' . $index] = ['files' => artifacts($directory . '/out'), 'stdout_hex' => bin2hex($value . ':1')];
			verify($project->run()['preparation']['prepared'] === 0, 'No-op prepared bodies');
		}
		// Header publication can change while the declaring unit's AST stays untouched.
		$provider_ast = $project->session()->entry->sources['provider.' . $flavor->name]->ast;
		foreach (['return 8;', $main] as $index => $code) {
			file_put_contents($directory . '/main.' . $flavor->name, $code);
			touch($directory . '/main.' . $flavor->name, ++$stamp);
			$project->run();
			verify($project->session()->entry->sources['provider.' . $flavor->name]->ast === $provider_ast,
				'Header visibility edit reparsed the unchanged provider');
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			$fresh->run();
			verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Enum header visibility differs from fresh');
			$fixtures[$flavor->name . '_visibility_' . $index] = ['files' => artifacts($directory . '/out'),
				'stdout_hex' => bin2hex($index === 0 ? '' : '256:1')];
		}
	} finally {
		remove_tree($directory);
	}
}
if (isset($argv[1])) file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
echo "Integer enums: extrema, nominal identity, typed calls and incremental/fresh recovery passed\n";
