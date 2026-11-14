<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Incremental_Project, Program_Preparation, Symbol_Publication};

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
	$sep = match ($flavor) {
		flavor::php => '\\', flavor::cpp => '::', flavor::js => '.',
	};
	$case = $flavor === flavor::js ? '.' : '::';
	$enum = static fn(int $large, bool $missing = false): string => 'namespace Lib {enum Kind {' . ($flavor === flavor::php
		? 'case First=1;' . ($missing ? '' : 'case Large=' . $large . ';')
		: 'First=1' . ($missing ? '' : ',Large=' . $large)) . '}}';
	$record = static function (string $default, int $number = 7) use ($flavor, $sep): string {
		return 'use Lib' . $sep . 'Kind;' . match ($flavor) {
			flavor::php => 'struct Token {public Kind $kind=Kind::First; public int $number; '
				. 'public function __construct(Kind $k=' . $default . ',int $n=' . $number . ') {$this->kind=$k;$this->number=$n;}}'
				. 'struct Wrapper {public Token $token;} struct Outer {public Wrapper $wrapper;}',
			flavor::cpp => 'struct Token {Kind kind=Kind::First; int number; Token(Kind k=' . $default . ',int n=' . $number . ')'
				. '{this->kind=k;this->number=n;}}; struct Wrapper {Token token;}; struct Outer {Wrapper wrapper;};',
			flavor::js => 'struct Token {kind:Kind=Kind.First; number:int; constructor(k:Kind=' . $default . ',n:int=' . $number . ')'
				. '{this.kind=k;this.number=n;}} struct Wrapper {token:Token;} struct Outer {wrapper:Wrapper;}',
		};
	};
	$main = 'use Lib' . $sep . 'Kind;' . match ($flavor) {
		flavor::php => '$a Token; $b Token(Kind::Large,9); $c Token(Kind::Large); '
			. '$h=new Token(); $j=new Token(Kind::Large,9); $k=new Token(Kind::Large); $w Outer; $copy=$b;',
		flavor::cpp => 'Token a; Token b(Kind::Large,9); Token c(Kind::Large); '
			. 'auto h=new Token(); auto j=new Token(Kind::Large,9); auto k=new Token(Kind::Large); Outer w; auto copy=b;',
		flavor::js => 'let a:Token; let b:Token(Kind.Large,9); let c:Token(Kind.Large); '
			. 'let h=new Token(); let j=new Token(Kind.Large,9); let k=new Token(Kind.Large); let w:Outer; let copy=b;',
	};
	$member = $flavor === flavor::js ? '.' : '->';
	$var = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	foreach (['a', 'b', 'c', 'h', 'j', 'k', 'w' . $member . 'wrapper' . $member . 'token', 'copy'] as $name) {
		$main .= 'echo enum_value(' . $var($name) . $member . 'kind),":",' . $var($name) . $member . 'number,"|";';
	}
	$main .= 'return 8;';
	$directory = sys_get_temp_dir() . '/scpp-enum-ctor-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', json_encode(['entry' => 'main.' . $flavor->name, 'sources' => array_map(
			static fn(string $name): string => $name . '.' . $flavor->name, ['main', 'kind', 'token', 'idle'])]));
		$paths = [];
		foreach (['main', 'kind', 'token', 'idle'] as $name) $paths[$name] = $directory . '/' . $name . '.' . $flavor->name;
		file_put_contents($paths['main'], $main);
		file_put_contents($paths['idle'], $flavor === flavor::cpp ? 'int idle(){return 9;}' : 'function idle():int{return 9;}');
		$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31));
		$stamp = time();
		$caller = null;
		$provider = null;
		foreach ([
			[300, 'First', 7, false, null], [300, 'Large', 11, false, null], [70000, 'Large', 11, false, null],
			[70000, 'Missing', 11, false, 'Unknown enum case'], [70000, 'Large', 11, true, 'Unknown enum case'],
			[70000, 'First', 7, false, null], [-1, 'Large', 7, false, null],
		] as $index => [$large, $default, $number, $missing, $error]) {
			foreach (['kind' => $enum($large, $missing), 'token' => $record('Kind' . $case . $default, $number)] as $name => $source) {
				if (!is_file($paths[$name]) || file_get_contents($paths[$name]) !== $source) {
					file_put_contents($paths[$name], $source);
					touch($paths[$name], ++$stamp);
				}
			}
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			if ($error !== null) {
				rejects(fn() => $project->run(), $error);
				rejects(fn() => $project->run(), $error);
				rejects(fn() => $fresh->run(), $error);
				continue;
			}
			$start = hrtime(true);
			$result = $project->run();
			$compiler_ms = (hrtime(true) - $start) / 1e6;
			$fresh->run();
			$caller ??= $project->session()->entry->entry->ast;
			verify($project->session()->entry->entry->ast === $caller, 'Caller was reparsed');
			$current = $project->session()->entry->sources['token.' . $flavor->name]->ast;
			if ($index === 2) verify($current === $provider, 'Enum edit reparsed constructor provider');
			$provider = $current;
			verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Constructor defaults differ from fresh');
			if ($index > 0) verify($result['preparation']['reused'] >= 1, 'Unrelated body was not reused');
			$initial = $default === 'First' ? 1 : $large;
			$expected = '';
			foreach ([[$initial, $number], [$large, 9], [$large, $number], [$initial, $number], [$large, 9],
				[$large, $number], [$initial, $number], [$large, 9]] as [$value, $n]) $expected .= $value . ':' . $n . '|';
			$fixtures[$flavor->name . '_' . $index] = ['files' => artifacts($directory . '/out'),
				'stdout_hex' => bin2hex($expected), 'compiler_ms' => $compiler_ms, 'preparation' => $result['preparation']];
			verify($project->run()['preparation']['prepared'] === 0, 'No-op prepares bodies');
		}
		foreach (['1', 'null', 'Kind' . $case . 'First + 1'] as $default) {
			file_put_contents($paths['token'], $record($default));
			touch($paths['token'], ++$stamp);
			rejects(fn() => $project->run(), 'Enum parameter default requires an enum case');
			rejects(fn() => $project->run(), 'Enum parameter default requires an enum case');
		}
		$other = $flavor === flavor::php ? 'enum Other {case First=1;}' : 'enum Other {First=1}';
		file_put_contents($paths['token'], $record('Other' . $case . 'First') . $other);
		touch($paths['token'], ++$stamp);
		rejects(fn() => $project->run(), 'conversion');
		rejects(fn() => $project->run(), 'conversion');
		$reference = match ($flavor) {
			flavor::php => str_replace('Kind $k=', 'Kind &$k=', $record('Kind' . $case . 'First')),
			flavor::cpp => str_replace('Kind k=', 'Kind &k=', $record('Kind' . $case . 'First')),
			flavor::js => str_replace('constructor(k:', 'constructor(&k:', $record('Kind' . $case . 'First')),
		};
		file_put_contents($paths['token'], $reference);
		touch($paths['token'], ++$stamp);
		rejects(fn() => $project->run(), 'Constructors require by-value parameters');
		rejects(fn() => $project->run(), 'Constructors require by-value parameters');
		file_put_contents($paths['token'], $record('Kind' . $case . 'First'));
		touch($paths['token'], ++$stamp);
		$project->run();
		$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
		$fresh->run();
		verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Invalid-default recovery differs');
	} finally {
		remove_tree($directory);
	}
}
if (isset($argv[1])) file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
echo "Enum constructor defaults: inline/shared/nested construction and incremental recovery passed\n";
