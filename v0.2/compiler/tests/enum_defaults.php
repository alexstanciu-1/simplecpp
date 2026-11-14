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
	$root = $flavor === flavor::cpp ? '::' : '\\';
	$case = $flavor === flavor::js ? '.' : '::';
	$var = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	$enum = static fn(int $large, bool $missing = false): string => 'namespace Lib {enum Kind {' . ($flavor === flavor::php
		? 'case First=1;' . ($missing ? '' : 'case Large=' . $large . ';')
		: 'First=1' . ($missing ? '' : ',Large=' . $large)) . '}}';
	$parameter = static fn(string $name, string $default): string => match ($flavor) {
		flavor::php => 'Kind $' . $name . '=' . $default,
		flavor::cpp => 'Kind ' . $name . '=' . $default,
		flavor::js => $name . ':Kind=' . $default,
	};
	$function = static fn(string $name, string $parameters, string $type, string $body): string =>
		($flavor === flavor::cpp ? $type . ' ' . $name : 'function ' . $name) . '(' . $parameters . ')'
		. ($flavor === flavor::cpp ? '' : ':' . $type) . '{' . $body . '}';
	$prepare = static function (string $source) use ($flavor) {
		$publication = new Symbol_Publication();
		$file = (new Source_Frontend())->collect(new source_file('defaults', $source . 'return 8;', $flavor), $publication);
		return Program_Preparation::prepare('entry', ['entry' => $file], new entry_target(31),
			$publication->publish(['entry' => $file]))->entry;
	};
	$base = substr($enum(300), strlen('namespace Lib {'), -1);
	$other = str_replace('Kind', 'Other', $base);
	$p = $parameter('k', 'Kind' . $case . 'First');
	$reference = match ($flavor) {
		flavor::php => str_replace('$k', '&$k', $p), flavor::cpp => str_replace(' k', ' &k', $p), flavor::js => '&' . $p,
	};
	$constructor = match ($flavor) {
		flavor::php => 'public function __construct(' . $p . '){}',
		flavor::cpp => 'Owner(' . $p . '){}', flavor::js => 'constructor(' . $p . '){}',
	};
	rejects(fn() => $prepare($base . 'struct Owner {' . $constructor . '}'), 'Enum constructor defaults are deferred');
	rejects(fn() => $prepare($base . $function('f', $reference, 'Kind', 'return ' . $var('k') . ';')), 'Reference parameter defaults');
	rejects(fn() => $prepare($base . $other . $function('f', $parameter('k', 'Other' . $case . 'First'), 'Kind',
		'return ' . $var('k') . ';')), 'conversion');
	$required = explode('=', $parameter('second', 'unused'))[0];
	rejects(fn() => $prepare($base . $function('f', $p . ',' . $required, 'Kind', 'return ' . $var('k') . ';')),
		'Required parameter follows optional');
	foreach (['First', 'pick()'] as $invalid) {
		rejects(fn() => $prepare($base . $function('f', $parameter('k', $invalid), 'Kind', 'return ' . $var('k') . ';')),
			'Enum parameter default requires an enum case');
	}
	$api = static function (string $default) use ($flavor, $case, $sep, $parameter, $function, $var): string {
		$p = $parameter('k', $default);
		$method = match ($flavor) {
			flavor::php => 'public function choose(' . $p . '):Kind{return $k;} public static function chooseStatic(' . $p . '):Kind{return $k;}',
			flavor::cpp => 'Kind choose(' . $p . '){return k;} static Kind chooseStatic(' . $p . '){return k;}',
			flavor::js => 'choose(' . $p . '):Kind{return k;} static chooseStatic(' . $p . '):Kind{return k;}',
		};
		return 'namespace Api {use Lib' . $sep . 'Kind;'
			. $function('pick', $p, 'Kind', 'return ' . $var('k') . ';')
			. $function('pair', $parameter('a', $default) . ',' . $parameter('b', 'Kind' . $case . 'Large'), 'int',
				'return enum_value(' . $var('a') . ')+enum_value(' . $var('b') . ');')
			. 'struct Selector {' . $method . '}'
			. $function('makeShared', '', 'shared<Selector>', 'return new Selector();')
			. $function('makeInline', '', 'Selector', match ($flavor) {
				flavor::php => '$p Selector;return $p;', flavor::cpp => 'Selector p;return p;', flavor::js => 'let p:Selector;return p;',
			}) . '}';
	};
	$selector = $root . 'Api' . $sep . 'Selector';
	$large_case = $root . 'Lib' . $sep . 'Kind' . $case . 'Large';
	$local_kind = $flavor === flavor::php ? 'enum Kind {case First=9;}' : 'enum Kind {First=9}';
	$main = 'namespace Consumer {use function Api' . $sep . 'pick;use function Api' . $sep . 'pair;use function Api' . $sep . 'makeInline;use function Api' . $sep . 'makeShared;' . $local_kind
		. match ($flavor) {
			flavor::php => '$p=makeInline(); $h=makeShared();',
			flavor::cpp => 'auto p=makeInline(); auto h=makeShared();',
			flavor::js => 'let p=makeInline(); let h=makeShared();',
		};
	$member = $flavor === flavor::js ? '.' : '->';
	$main .= 'echo enum_value(pick()),":",enum_value(pick(' . $large_case . ')),":",'
		. 'enum_value(' . $var('p') . $member . 'choose()),":",enum_value(' . $var('p') . $member . 'choose(' . $large_case . ')),":",'
		. 'enum_value(' . $var('h') . $member . 'choose()),":",enum_value(' . $var('h') . $member . 'choose(' . $large_case . ')),":",'
		. 'enum_value(' . $selector . $case . 'chooseStatic()),":",enum_value(' . $selector . $case . 'chooseStatic(' . $large_case . ')),":",'
		. 'pair(),":",pair(' . $large_case . ');return 8;}';
	$directory = sys_get_temp_dir() . '/scpp-enum-defaults-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', json_encode(['entry' => 'main.' . $flavor->name, 'sources' => array_map(
			static fn(string $name): string => $name . '.' . $flavor->name, ['main', 'kind', 'api', 'idle'])]));
		$paths = [];
		foreach (['main', 'kind', 'api', 'idle'] as $name) $paths[$name] = $directory . '/' . $name . '.' . $flavor->name;
		file_put_contents($paths['main'], $main);
		file_put_contents($paths['idle'], $flavor === flavor::cpp ? 'int idle(){return 9;}' : 'function idle():int{return 9;}');
		$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31));
		$stamp = time();
		$caller = null;
		$provider = null;
		foreach ([
			[300, 'First', false, null], [300, 'Large', false, null], [70000, 'Large', false, null],
			[70000, 'Missing', false, 'Unknown enum case'], [70000, 'Large', true, 'Unknown enum case'],
			[70000, 'First', false, null], [70000, 'qualified', false, null],
		] as $index => [$large, $default, $missing, $error]) {
			$default_source = $default === 'qualified' ? $root . 'Lib' . $sep . 'Kind' . $case . 'First' : 'Kind' . $case . $default;
			foreach (['kind' => $enum($large, $missing), 'api' => $api($default_source)] as $name => $source) {
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
			$current = $project->session()->entry->sources['api.' . $flavor->name]->ast;
			if ($index === 2) verify($current === $provider, 'Enum edit reparsed callable provider');
			$provider = $current;
			verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Enum defaults differ from fresh');
			if ($index > 0) verify($result['preparation']['reused'] >= 1, 'Unrelated body was not reused');
			$initial = $default === 'Large' ? $large : 1;
			$expected = implode(':', [$initial, $large, $initial, $large, $initial, $large, $initial, $large, $initial + $large, 2 * $large]);
			$fixtures[$flavor->name . '_' . $index] = ['files' => artifacts($directory . '/out'),
				'stdout_hex' => bin2hex($expected), 'compiler_ms' => $compiler_ms, 'preparation' => $result['preparation']];
			verify($project->run()['preparation']['prepared'] === 0, 'No-op prepares bodies');
		}
		foreach (['1', 'null', 'Kind' . $case . 'First + 1'] as $default) {
			file_put_contents($paths['api'], $api($default));
			touch($paths['api'], ++$stamp);
			rejects(fn() => $project->run(), 'Enum parameter default requires an enum case');
			rejects(fn() => $project->run(), 'Enum parameter default requires an enum case');
		}
		file_put_contents($paths['api'], $api('Kind' . $case . 'First'));
		touch($paths['api'], ++$stamp);
		$project->run();
		$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
		$fresh->run();
		verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Invalid-default recovery differs');
	} finally {
		remove_tree($directory);
	}
}
if (isset($argv[1])) file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
echo "Enum parameter defaults: functions, methods and incremental recovery passed\n";
