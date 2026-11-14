<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, Type_Resolution, type_registry, hash_type_definition,
	Project_Compiler, Incremental_Preparation, Program_Frontend, Cpp_Program, prepared_program, prepared_entry,
	entry_target, diagnostic, diagnostic_group};

function verify(bool $ok, string $message): void
{
	if (!$ok) {
		throw new RuntimeException($message);
	}
}

function signature(flavor $flavor, string $type): string
{
	return match ($flavor) {
		flavor::cpp => "void inspect($type value){return;}",
		flavor::php => "function inspect($type \$value): void {return;}",
		flavor::js => "function inspect(value: $type): void {return;}",
	};
}

/** Require source diagnostics, never internal errors or implicit activation of unpublished specializations. */
function rejects(Closure $action, string $message): void
{
	try {
		$action();
	}
	catch (diagnostic|diagnostic_group $error) {
		verify(str_contains($error->getMessage(), $message), $error->getMessage());
		return;
	}
	throw new RuntimeException('Expected rejection: ' . $message);
}

$fixtures = [];
foreach (flavor::cases() as $flavor) {
	$frontend = new Source_Frontend();
	$parse = static fn(string $type) => $frontend->parse(new source_file('hash-types', signature($flavor, $type), $flavor));
	$resolve = static fn(string $type) => Type_Resolution::resolve($parse($type)->functions()[0]->parameters[0]->type_reference);
	verify(type_registry::generic_arity('hash') === 2, 'Missing two-argument family registration');
	foreach (['int', 'string'] as $value) {
		foreach (['int', 'string'] as $key) {
			$type = $resolve("hash<$value,$key>");
			verify($type instanceof hash_type_definition, 'Missing canonical hash definition');
			verify($type->value_type === type_registry::resolve($value) && $type->key_type === type_registry::resolve($key), 'Key/value order reversed');
			verify($type === $resolve(str_replace('int', 'int64', "hash<$value,$key>")), 'Alias duplicated identity');
			verify($type->cpp_type() === 'scpp::hash_' . $type->value_type->name . '_' . $type->key_type->name, 'Wrong C++ binding');
			verify($type->cpp_headers() === ['scpp/hashes.hpp'], 'Wrong provider header');
			$syntax = $parse("hash<$value,$key>")->functions()[0]->parameters[0]->type_reference;
			verify($syntax->first_child->parent === $syntax && $syntax->first_child->next_sibling->parent === $syntax, 'Argument ownership mismatch');
			verify($syntax->first_child->next_sibling->next_sibling === null, 'Unexpected argument');
			$returning = str_replace('void', "hash<$value,$key>", signature($flavor, "hash<$value,$key>"));
			$returning = str_replace('return;', 'return ' . ($flavor === flavor::php ? '$value' : 'value') . ';', $returning);
			$frontend->prepare(new source_file('hash-values', $returning . 'return 8;', $flavor), new entry_target(31));
		}
	}
	$forward = str_replace('inspect', 'forward', signature($flavor, 'hash<int,string>'));
	$forward = str_replace('return;', 'inspect(' . ($flavor === flavor::php ? '$value' : 'value') . '); return;', $forward);
	$frontend->prepare(new source_file('hash-calls', signature($flavor, 'hash<int,string>')
		. $forward . 'return 8;', $flavor), new entry_target(31));
	foreach (['hash' => 'two arguments', 'hash<int>' => 'two arguments', 'hash<int,string,bool>' => 'two arguments',
		'hash<byte,string>' => 'Unavailable runtime specialization', 'hash<int,bool>' => 'Unavailable runtime specialization',
		'hash<string,Missing>' => 'Unknown or unsupported type', 'hash<vector<int>,string>' => 'Unavailable runtime specialization',
		'nullable<hash<int,string>>' => 'Unavailable runtime specialization', 'int<string>' => 'does not accept arguments',
		'vector<int,string>' => 'one argument'] as $text => $message) {
		rejects(fn() => $resolve($text), $message);
	}
	$directory = sys_get_temp_dir() . '/scpp-hash-types-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		$main = $directory . '/main.' . $flavor->name;
		$library = $directory . '/library.' . $flavor->name;
		file_put_contents($main, 'echo "hash-types"; return 8;');
		$revision = time();
		$compiler = new Project_Compiler();
		$session = null;
		$root = null;
		$slot = null;
		$target = new entry_target(31);
		foreach (['hash<int,string>', 'hash<string,int>', 'hash<string,string>', 'hash<int,int>',
			'hash<int,bool>', 'retry', null, 'hash<int64,string>'] as $step => $text) {
			if ($text !== 'retry') {
				if ($text === null) {
					unlink($library);
				}
				else {
					file_put_contents($library, signature($flavor, $text));
					touch($library, ++$revision);
				}
				$session = $compiler->frontend($directory, $session);
			}
			$root ??= $session->entry->entry->ast;
			$slot ??= $session->scope->entry('func:inspect');
			verify($root === $session->entry->entry->ast, 'Unchanged AST replaced');
			if ($text === 'hash<int,bool>' || $text === 'retry') {
				rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Unavailable runtime specialization');
				continue;
			}
			$stats = Incremental_Preparation::prepare($session, $target);
			if ($step > 0 && $step < 4) {
				verify($stats['prepared'] === 2, 'Changed hash contract missed observer');
			}
			verify($session->scope->entry('func:inspect') === $slot, 'Stable entry replaced');
			$files = $session->files();
			$incremental = Cpp_Program::emit(new prepared_program($session->entry_key(), $files, new prepared_entry($root, $target), $session))->files;
			$sources = [];
			foreach ($files as $key => $file) {
				$sources[$key] = $file->source();
			}
			$fresh = Cpp_Program::emit((new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target))->files;
			verify($incremental === $fresh, 'Incremental output differs from fresh');
			$fixtures[$flavor->name . '-' . $step] = ['files' => $incremental, 'stdout_hex' => bin2hex('hash-types')];
			// Exercise contract invalidation without enabling source hash value operations.
			$session->dependency_watches->watch($slot, $root->main()->body());
		}
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 2, 'No-op rebuilt signatures');
	}
	finally {
		foreach (glob($directory . '/*') as $path) {
			unlink($path);
		}
		rmdir($directory);
	}
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Hash exports, two-argument identities and incremental type recovery passed\n";
