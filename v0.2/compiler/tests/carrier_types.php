<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, Type_Resolution, type_registry, carrier_type_definition,
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

/** Require source diagnostics rather than internal exceptions or admission of unpublished types. */
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
$families = ['nullable', 'result', 'result_or_false', 'result_or_bool'];
foreach (flavor::cases() as $flavor) {
	$frontend = new Source_Frontend();
	$parse = static fn(string $type) => $frontend->parse(new source_file('carrier-types', signature($flavor, $type), $flavor));
	$resolve = static fn(string $type) => Type_Resolution::resolve($parse($type)->functions()[0]->parameters[0]->type_reference);
	foreach ($families as $family) {
		verify(type_registry::generic_arity($family) === 1, 'Missing family registration');
		$type = $resolve($family . '<int>');
		verify($type instanceof carrier_type_definition && $type->payload_type === type_registry::integer(), 'Shared carrier payload model missing');
		verify($type === $resolve($family . '<int64>'), 'Payload alias created a second specialization');
		verify($type->cpp_type() === 'scpp::' . $family . '_int64' && $type->cpp_headers() === ['scpp/carriers.hpp'], 'Export binding mismatch');
		verify($resolve($family . '<bool>')->payload_type === type_registry::resolve('bool'), 'Boolean payload missing');
		verify($resolve($family . '<string>')->payload_type === type_registry::string(), 'String payload missing');
		$nested = $resolve($family . '<vector<string>>');
		verify($nested->payload_type === type_registry::specialize('vector', type_registry::string()), 'Nested published payload not canonical');
		verify($nested->name === $family . '<vector<string>>', 'Source name contains runtime spelling');
		$returning = str_replace('void', $family . '<int>', signature($flavor, $family . '<int>'));
		$returning = str_replace('return;', 'return ' . ($flavor === flavor::php ? '$value' : 'value') . ';', $returning);
		$frontend->prepare(new source_file('carrier-values', $returning . 'return 8;', $flavor), new entry_target(31));
		foreach ([$family => 'exactly one', $family . '<int,string>' => 'exactly one',
			$family . '<byte>' => 'Unavailable runtime specialization', $family . '<null>' => 'Unavailable runtime specialization',
			$family . '<vector<int>>' => 'Unavailable runtime specialization',
			$family . '<nullable<int>>' => 'Unavailable runtime specialization', $family . '<Missing>' => 'Unknown or unsupported type'] as $text => $message) {
			rejects(fn() => $resolve($text), $message);
		}
	}
	$directory = sys_get_temp_dir() . '/scpp-carrier-types-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		$main = $directory . '/main.' . $flavor->name;
		$library = $directory . '/library.' . $flavor->name;
		file_put_contents($main, 'echo "carriers"; return 8;');
		$revision = time();
		$write = static function (?string $type) use ($library, $flavor, &$revision): void {
			if ($type === null) {
				unlink($library);
				return;
			}
			file_put_contents($library, signature($flavor, $type));
			touch($library, ++$revision);
		};
		$compiler = new Project_Compiler();
		$session = null;
		$root = null;
		$slot = null;
		$target = new entry_target(31);
		foreach (['nullable<int>', 'result<string>', 'result_or_false<bool>', 'result_or_bool<vector<string>>',
			'result<byte>', 'retry', null, 'nullable<int64>'] as $step => $text) {
			if ($text !== 'retry') {
				$write($text);
				$session = $compiler->frontend($directory, $session);
			}
			$root ??= $session->entry->entry->ast;
			$slot ??= $session->scope->entry('func:inspect');
			verify($root === $session->entry->entry->ast, 'Unchanged AST replaced');
			if ($text === 'result<byte>' || $text === 'retry') {
				rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Unavailable runtime specialization');
				continue;
			}
			$stats = Incremental_Preparation::prepare($session, $target);
			if ($step > 0 && $step < 4) {
				verify($stats['prepared'] === 2, 'Carrier contract change missed observing body');
			}
			verify($session->scope->entry('func:inspect') === $slot, 'Stable entry replaced');
			$files = $session->files();
			$incremental = Cpp_Program::emit(new prepared_program($session->entry_key(), $files,
				new prepared_entry($root, $target), $session))->files;
			$sources = [];
			foreach ($files as $key => $file) {
				$sources[$key] = $file->source();
			}
			$fresh = Cpp_Program::emit((new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target))->files;
			verify($incremental === $fresh, 'Incremental output differs from fresh');
			$fixtures[$flavor->name . '-' . $step] = ['files' => $incremental, 'stdout_hex' => bin2hex('carriers')];
			// Synthetic observer tests signature invalidation without enabling carrier value calls.
			$session->dependency_watches->watch($slot, $root->main()->body());
		}
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 2, 'No-op reparsed signatures');
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
echo "Four carrier families, published payloads, aliases and incremental type recovery passed\n";
