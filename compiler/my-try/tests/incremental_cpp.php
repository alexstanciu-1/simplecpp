<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function cpp_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

function cpp_function(string $name): function_node
{
	return object_cast(Model::$global_scope->functions_named($name)[0]->node, function_node::class);
}

$directory = sys_get_temp_dir() . '/scpp_cpp_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
$initial = 'struct Box { int32 $value; } function target(): int { return 1; } function caller(): int { return target(); } return caller();';
try
{
	file_put_contents($path, $initial);
	$compiler = new Compiler();
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$target = cpp_function('target');
	$caller = cpp_function('caller');
	$target_owner = $target->occurrence()->preparation;
	$target_body = $target->body->work();
	$caller_body = $caller->body->work();
	$source = Model::collected_files()[0];
	$signature = Model::$cpp_program->fragments[$target_owner];
	$body = Model::$cpp_program->fragments[$target_body];
	$caller_fragment = Model::$cpp_program->fragments[$caller_body];
	$entry = Model::$cpp_program->fragments[$source->root->body->work()];
	$text = Model::$cpp_files[0]->text;
	$compiler->exec_cpp();
	cpp_check(Model::$cpp_files[0]->text === $text && Model::$cpp_program->fragments[$target_body] === $body, 'No-op generation rerendered body');

	// Changed body, unchanged signature and consumers; preparation hands off work without settling it.
	file_put_contents($path, str_replace('return 1;', 'return 22;', $initial));
	$compiler->sync([$path]);
	$compiler->prepare();
	cpp_check(isset($source->preparation_changes[$target_body]), 'Preparation lost pending generation work');
	$compiler->cpp();
	cpp_check(count($source->preparation_changes) === 0, 'Successful generation did not consume handoff');
	cpp_check(Model::$cpp_program->fragments[$target_body] !== $body, 'Changed body reused stale text');
	cpp_check(Model::$cpp_program->fragments[$target_owner] === $signature && Model::$cpp_program->fragments[$caller_body] === $caller_fragment && Model::$cpp_program->fragments[$source->root->body->work()] === $entry, 'Body edit rerendered unchanged fragments');

	// Insertion moves tokens but cannot rename existing declarations or perturb cached temporaries.
	$changed = 'function before(): int { return target(); } ' . str_replace('return 1;', 'return 22;', $initial);
	file_put_contents($path, $changed);
	$compiler->update_cpp([$path]);
	cpp_check(Model::$cpp_program->fragments[$caller_body] === $caller_fragment && Model::$cpp_program->fragments[$target_owner] === $signature, 'Token movement invalidated stable fragments');
	$incremental = Model::$cpp_files[0]->text;
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	cpp_check(Model::$cpp_files[0]->text === $incremental, 'Fresh and incremental assembly differ');

	// Dirty rendering failure retains pending work and withholds completed output until retry.
	file_put_contents($path, str_replace('return 22;', 'return 33;', $changed));
	$compiler->sync([$path]);
	$compiler->prepare();
	$source = Model::collected_files()[0];
	$body_owner = cpp_function('target')->body->work();
	Language_Types::integer(Model::$language_scope)->value_bits = 32;
	$failed = false;
	try {
		$compiler->cpp();
	}
	catch (\RuntimeException $error) {
		$failed = true;
	}
	cpp_check($failed && Model::$cpp_files->is_empty() && isset($source->preparation_changes[$body_owner]), 'Failed generation lost dirty work or published output');
	cpp_check(Model::$cpp_program->fragments[$body_owner]->change_status === change_state::changed, 'Failed fragment was settled');
	Language_Types::integer(Model::$language_scope)->value_bits = 64;
	$compiler->cpp();
	cpp_check(Model::$cpp_program->fragments[$body_owner]->change_status === change_state::unchanged && count($source->preparation_changes) === 0, 'Retry did not settle generation');

	// Deleted declarations and their bodies leave both assembly and retained fragment storage.
	$deleted = cpp_function('before');
	$deleted_signature = $deleted->occurrence()->preparation;
	$deleted_body = $deleted->body->work();
	file_put_contents($path, str_replace('return 1;', 'return 33;', $initial));
	$compiler->update_cpp([$path]);
	cpp_check(!isset(Model::$cpp_program->fragments[$deleted_signature]) && !isset(Model::$cpp_program->fragments[$deleted_body]) && !str_contains(Model::$cpp_files[0]->text, 'function_before'), 'Deleted declaration retained C++ fragments');

	// Signature changes invalidate affected bodies without replacing unrelated record fragments.
	$caller_owner = cpp_function('caller')->body->work();
	$caller_before = Model::$cpp_program->fragments[$caller_owner];
	$box_owner = Model::$global_scope->source_types_named('Box')[0]->preparation;
	$box_before = Model::$cpp_program->fragments[$box_owner];
	file_put_contents($path, str_replace('target(): int', 'target(): uint8', str_replace('return 1;', 'return 33;', $initial)));
	$compiler->update_cpp([$path]);
	cpp_check(Model::$cpp_program->fragments[$caller_owner] !== $caller_before, 'Signature change left stale consumer fragment');
	cpp_check(Model::$cpp_program->fragments[$box_owner] === $box_before, 'Signature change rerendered unrelated record');

	// An included runtime header disappears once its last emitting fragment is removed.
	file_put_contents($path, 'function floating(): float { return 1.5; } return 0;');
	$compiler->update_cpp([$path]);
	cpp_check(str_contains(Model::$cpp_files[0]->text, 'scpp/float_t.hpp'), 'Missing fragment include');
	file_put_contents($path, 'return 0;');
	$compiler->update_cpp([$path]);
	cpp_check(!str_contains(Model::$cpp_files[0]->text, 'scpp/float_t.hpp'), 'Deleted fragment retained stale include');
}
finally {
	unlink($path);
	rmdir($directory);
}
echo "Incremental C++: cached fragments, body/signature independence, stable names, fresh equivalence, failure retry and deletion passed\n";
