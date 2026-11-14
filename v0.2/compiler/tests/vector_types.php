<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, Type_Resolution, type_registry, vector_type_definition,
	Project_Compiler, Incremental_Preparation, Program_Frontend, Cpp_Program, prepared_program, prepared_entry,
	entry_target, diagnostic, diagnostic_group};

function verify(bool $ok, string $message): void
{
	if (!$ok) {
		throw new RuntimeException($message);
	}
}

function signature(flavor $flavor, string $type, string $result = 'void', string $body = 'return;'): string
{
	$parameter = match ($flavor) {
		flavor::cpp => "$type value", flavor::php => "$type \$value", flavor::js => "value: $type",
	};
	return ($flavor === flavor::cpp ? "$result inspect($parameter)" : "function inspect($parameter): $result") . '{' . $body . '}';
}

/** Require language diagnostics instead of internal exceptions or silently admitted unavailable types. */
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

$frontend = new Source_Frontend();
$fixtures = [];
foreach (flavor::cases() as $flavor) {
	$parse = static fn(string $type) => $frontend->parse(new source_file('vector-types', signature($flavor, $type), $flavor));
	$function = $parse('vector<int>')->functions()[0];
	$written = $function->parameters[0]->type_reference;
	verify(($written->name === 'vector') && ($written->source_text() === 'vector<int>'), 'Generic type bounds');
	verify(($written->first_child->name === 'int') && ($written->first_child->parent === $written)
		&& ($written->first_child->next_sibling === null), 'Argument ownership');
	$type = Type_Resolution::resolve($written);
	verify($type instanceof vector_type_definition && $type->element_type === type_registry::integer(), 'Element identity');
	verify($type === Type_Resolution::resolve($parse('vector<int64>')->functions()[0]->parameters[0]->type_reference), 'Alias specialization duplicated');
	verify(($type->cpp_type() === 'scpp::vector_int64') && ($type->cpp_headers() === ['scpp/vectors.hpp']), 'Runtime export binding');
	verify(Type_Resolution::resolve($parse('vector<string>')->functions()[0]->parameters[0]->type_reference)->cpp_type() === 'scpp::vector_string', 'String vector binding');
	$nested = $parse('hash<string,vector<int>>')->functions()[0]->parameters[0]->type_reference;
	verify(($nested->spelling() === 'hash<string,vector<int>>')
		&& ($nested->first_child->next_sibling->parent === $nested)
		&& ($nested->first_child->next_sibling->first_child->name === 'int'), 'Nested ordered arguments');
	$return = $frontend->parse(new source_file('return-type', signature($flavor, 'int', 'vector<int>'), $flavor))->functions()[0];
	verify($return->return_type_reference->spelling() === 'vector<int>', 'Generic return syntax');
	verify($return->root()->tokens->text_at($return->name_position()) === 'inspect', 'Generic return moved name diagnostics');
	foreach (['vector' => 'exactly one', 'vector<int,string>' => 'exactly one',
		'vector<byte>' => 'Unavailable runtime specialization', 'vector<vector<int>>' => 'Unavailable runtime specialization',
		'vector<Missing>' => 'Unknown or unsupported type', 'int<string>' => 'does not accept arguments'] as $text => $message) {
		rejects(fn() => Type_Resolution::resolve($parse($text)->functions()[0]->parameters[0]->type_reference), $message);
	}
	foreach (['vector<>', 'vector<int,>', 'vector<int', 'vector<int,,string>'] as $text) {
		rejects(fn() => $parse($text), 'Expected');
	}
	$prepare = static fn(string $text) => $frontend->prepare(new source_file('vector-operations', $text, $flavor), new entry_target(31));
	$variable = $flavor === flavor::php ? '$value' : 'value';
	$prepare(signature($flavor, 'vector<int>', 'vector<int>', 'return ' . $variable . ';') . 'return 8;');

	$directory = sys_get_temp_dir() . '/scpp-vector-types-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		$main = $directory . '/main.' . $flavor->name;
		$library = $directory . '/lib.' . $flavor->name;
		file_put_contents($main, 'echo "ok"; return 8;');
		$revision = time();
		$write = static function (string $type) use ($library, $flavor, &$revision): void {
			file_put_contents($library, signature($flavor, $type));
			touch($library, ++$revision);
		};
		$write('vector<int>');
		$compiler = new Project_Compiler();
		$session = $compiler->frontend($directory);
		$target = new entry_target(31);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 2, 'Initial signatures');
		$root = $session->entry->entry->ast;
		$tokens = $root->tokens;
		$slot = $session->scope->entry('func:inspect');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 2, 'No-op reparsed types');
		// Synthetic observer isolates signature invalidation without enabling vector value passing.
		$session->dependency_watches->watch($slot, $root->main()->body());
		$write('vector<string>');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 2, 'Element-only change missed observer');
		verify($slot->declaration()->parameters[0]->type_def()->name === 'vector<string>', 'Stale element type');
		$write('vector<byte>');
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Unavailable runtime specialization');
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Unavailable runtime specialization');
		unlink($library);
		$session = $compiler->frontend($directory, $session);
		Incremental_Preparation::prepare($session, $target);
		$write('vector<int64>');
		$session = $compiler->frontend($directory, $session);
		Incremental_Preparation::prepare($session, $target);
		verify(($session->scope->entry('func:inspect') === $slot)
			&& ($slot->declaration()->parameters[0]->type_def() === $type), 'Re-added canonical identity');
		verify(($session->entry->entry->ast === $root) && ($root->tokens === $tokens), 'Unchanged AST/tokens replaced');
		$files = $session->files();
		$incremental = Cpp_Program::emit(new prepared_program($session->entry_key(), $files,
			new prepared_entry($root, $target), $session))->files;
		$sources = [];
		foreach ($files as $key => $file) {
			$sources[$key] = $file->source();
		}
		$fresh = Cpp_Program::emit((new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target))->files;
		verify($incremental === $fresh, 'Recovery differs from fresh compilation');
		$fixtures[$flavor->name] = ['files' => $incremental, 'stdout_hex' => bin2hex('ok')];
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
echo "Vector type syntax, runtime export resolution, arity, deferred operations and incremental recovery passed\n";
