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
	$enum = static function (int $large, bool $missing = false) use ($flavor): string {
		return 'namespace Lib { enum Kind {' . ($flavor === flavor::php
			? 'case First=1;' . ($missing ? '' : 'case Large=' . $large . ';')
			: 'First=1' . ($missing ? '' : ',Large=' . $large)) . '} }';
	};
	$field = static fn(string $default): string => match ($flavor) {
		flavor::php => 'public Kind $kind' . $default . ';',
		flavor::cpp => 'Kind kind' . $default . ';',
		flavor::js => 'kind:Kind' . $default . ';',
	};
	$record = static fn(string $default): string => 'use Lib' . $sep . 'Kind; struct Token {' . $field($default) . '}'
		. match ($flavor) {
			flavor::php => 'struct Wrapper {public Token $token;}',
			flavor::cpp => '; struct Wrapper {Token token;};',
			flavor::js => 'struct Wrapper {token:Token;}',
		};
	$main = 'use Lib' . $sep . 'Kind;' . match ($flavor) {
		flavor::php => 'function copy(Token $x):Token{return $x;} $p Token; $q=copy($p); $q->kind=Kind::Large; '
			. '$h=new Token(); $alias=$h; $h->kind=Kind::Large; $w Wrapper; '
			. 'echo enum_value($p->kind),":",enum_value($q->kind),":",enum_value($alias->kind),":",enum_value($w->token->kind);return 8;',
		flavor::cpp => 'Token copy(Token x){return x;} Token p; auto q=copy(p); q->kind=Kind::Large; '
			. 'auto h=new Token(); auto alias=h; h->kind=Kind::Large; Wrapper w; '
			. 'echo enum_value(p->kind),":",enum_value(q->kind),":",enum_value(alias->kind),":",enum_value(w->token->kind);return 8;',
		flavor::js => 'function copy(x:Token):Token{return x;} let p:Token; let q=copy(p); q.kind=Kind.Large; '
			. 'let h=new Token(); let alias=h; h.kind=Kind.Large; let w:Wrapper; '
			. 'echo enum_value(p.kind),":",enum_value(q.kind),":",enum_value(alias.kind),":",enum_value(w.token.kind);return 8;',
	};
	$publication = new Symbol_Publication();
	$source = substr($enum(300), strlen('namespace Lib {'), -1)
		. str_replace('use Lib' . $sep . 'Kind;', '', $record('=Kind' . $case . 'First') . $main);
	$file = (new Source_Frontend())->collect(new source_file('single', $source, $flavor), $publication);
	$entry = Program_Preparation::prepare('entry', ['entry' => $file], new entry_target(31),
		$publication->publish(['entry' => $file]))->entry;
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify($before === serialize($entry), 'Emission changed field facts');
	$fixtures[$flavor->name . '_single'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('1:300:300:1')];
	$construct = match ($flavor) {
		flavor::php => 'struct Reset {public Kind $kind=Kind::First; public function __construct(){'
			. 'echo enum_value($this->kind); $this->kind=Kind::Large;}} '
			. '$p Reset; $q=$p; $h=new Reset(); echo enum_value($p->kind),":",enum_value($q->kind),":",enum_value($h->kind);',
		flavor::cpp => 'struct Reset {Kind kind=Kind::First; Reset(){echo enum_value(this->kind); this->kind=Kind::Large;}}; '
			. 'Reset p; auto q=p; auto h=new Reset(); echo enum_value(p->kind),":",enum_value(q->kind),":",enum_value(h->kind);',
		flavor::js => 'struct Reset {kind:Kind=Kind.First; constructor(){echo enum_value(this.kind); this.kind=Kind.Large;}} '
			. 'let p:Reset; let q=p; let h=new Reset(); echo enum_value(p.kind),":",enum_value(q.kind),":",enum_value(h.kind);',
	};
	$publication = new Symbol_Publication();
	$file = (new Source_Frontend())->collect(new source_file('construct', substr($enum(300), strlen('namespace Lib {'), -1)
		. $construct . 'return 8;', $flavor), $publication);
	$entry = Program_Preparation::prepare('entry', ['entry' => $file], new entry_target(31),
		$publication->publish(['entry' => $file]))->entry;
	$fixtures[$flavor->name . '_constructor'] = ['cpp' => (new Cpp_Emitter())->emit($entry), 'stdout_hex' => bin2hex('11300:300:300')];
	$directory = sys_get_temp_dir() . '/scpp-enum-fields-' . bin2hex(random_bytes(8));
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
		$token = null;
		foreach ([
			[300, 'First', false, null], [70000, 'First', false, null],
			[70000, 'Large', false, null], [70000, 'Large', true, 'Unknown enum case'],
			[70000, 'First', false, null], [-1, 'First', false, null],
		] as $index => [$large, $default, $missing, $error]) {
			foreach (['kind' => $enum($large, $missing), 'token' => $record('=Kind' . $case . $default)] as $name => $source) {
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
			if ($index > 0) verify($result['preparation']['reused'] >= 1, 'Unrelated idle body was not reused');
			$fresh->run();
			$caller ??= $project->session()->entry->entry->ast;
			verify($project->session()->entry->entry->ast === $caller, 'Caller was reparsed');
			$current = $project->session()->entry->sources['token.' . $flavor->name]->ast;
			if ($index === 1 || $index === 5) verify($current === $token, 'Enum edit reparsed record');
			$token = $current;
			verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Incremental enum field artifacts differ from fresh');
			$initial = $default === 'First' ? 1 : $large;
			$fixtures[$flavor->name . '_' . $index] = ['files' => artifacts($directory . '/out'),
				'stdout_hex' => bin2hex($initial . ':' . $large . ':' . $large . ':' . $initial), 'compiler_ms' => $compiler_ms,
				'preparation' => $result['preparation']];
			verify($project->run()['preparation']['prepared'] === 0, 'No-op prepares bodies');
		}
		foreach (['' => 'explicit case default', '=1' => 'explicit case default', '=Kind' . $case . 'Missing' => 'Unknown enum case'] as $default => $message) {
			file_put_contents($paths['token'], $record($default));
			touch($paths['token'], ++$stamp);
			rejects(fn() => $project->run(), $message);
			rejects(fn() => $project->run(), $message);
		}
		$other = $flavor === flavor::php ? 'enum Other {case First=1;}' : 'enum Other {First=1}';
		file_put_contents($paths['token'], $record('=Other' . $case . 'First') . $other);
		touch($paths['token'], ++$stamp);
		rejects(fn() => $project->run(), 'conversion');
		rejects(fn() => $project->run(), 'conversion');
		file_put_contents($paths['token'], $record('=Kind' . $case . 'First'));
		touch($paths['token'], ++$stamp);
		$project->run();
		$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
		$fresh->run();
		verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Recovery after invalid field default differs');
		foreach (['1', 'Other' . $case . 'First'] as $bad) {
			$bad_main = str_replace('Kind' . $case . 'Large;', $bad . ';', $main);
			file_put_contents($paths['main'], $other . $bad_main);
			touch($paths['main'], ++$stamp);
			rejects(fn() => $project->run(), 'conversion');
		}
		file_put_contents($paths['main'], $main);
		touch($paths['main'], ++$stamp);
		$project->run();
		$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
		$fresh->run();
		verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Recovery after invalid field write differs');
		file_put_contents($paths['kind'], '');
		touch($paths['kind'], ++$stamp);
		rejects(fn() => $project->run(), 'Unsupported instance field type');
		rejects(fn() => $project->run(), 'Unsupported instance field type');
		$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
		rejects(fn() => $fresh->run(), 'Unsupported instance field type');
		file_put_contents($paths['kind'], $enum(-1));
		touch($paths['kind'], ++$stamp);
		$project->run();
		$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
		$fresh->run();
		verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Enum deletion recovery differs');
	} finally {
		remove_tree($directory);
	}
}
if (isset($argv[1])) file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
echo "Enum fields: copies, shared/nested storage, defaults and incremental recovery passed\n";
