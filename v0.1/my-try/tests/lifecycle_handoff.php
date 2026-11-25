<?php

/* Source changes accumulate independently of analysis scheduling and retirement. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function handoff_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

$directory = sys_get_temp_dir() . '/scpp_handoff_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/a.phs';
$other = $directory . '/b.phs';
file_put_contents($path, 'function value(): int { return 1; } return value();');
file_put_contents($other, 'function neighbor(): int { return 8; }');
try {
	Compiler_Lifecycle::reset();
	$compiler = new Compiler(new cpp_lowering_options(false));
	$compiler->init([$directory]);
	$compiler->sync([]);
	$compiler->prepare();
	$entry = Model::$global_scope->functions_named('value')[0];
	$function = $entry->syntax();
	$signature = $entry->preparation;
	$body = $function->body->work();
	$collection = $entry->collection;
	$file_body = $collection->root->single_body()->body->work();
	$prepared = $collection->prepared;
	$version = $body->version;
	$file_version = $file_body->version;
	$signature_version = $signature->version;
	$old_function_body = $function->body;
	$old_file_body = $collection->root->single_body()->body;

	// Reparse twice before analysis: identities move, but semantic state does not.
	file_put_contents($path, 'function value(): int { return 2; } return 3;');
	$compiler->sync([$path]);
	handoff_check($function->body->work() === $body && $collection->root->single_body()->body->work() === $file_body, 'Parsing replaced work identities');
	handoff_check($body->executable_body() === $function->body && $file_body->executable_body() === $collection->root->single_body()->body, 'Replacement did not rebind work to current syntax');
	handoff_check($old_function_body !== $body->executable_body() && $old_file_body !== $file_body->executable_body(), 'Changed bodies retained obsolete syntax');
	handoff_check($old_function_body->work() === null && $old_file_body->work() === null, 'Old syntax retained transferred work');
	foreach ([$body, $file_body, $signature] as $work) {
		handoff_check($work->state === preparation_state::ready && $work->change_status === change_state::unchanged, 'Parser changed analysis scheduling state');
	}
	$compiler->sync([$path]);
	handoff_check($function->body->syntax_changed && $collection->root->single_body()->body->syntax_changed, 'Repeated parsing erased unconsumed body changes');
	$failed = false;
	try { $stale = (new CPP_Generator(null, new cpp_lowering_options(false)))->generate($prepared); }
	catch (\RuntimeException $expected) { $failed = true; }
	handoff_check($failed, 'Saved preparation permitted emission after unconsumed edits');
	$compiler->prepare();
	handoff_check($body->version === $version + 1 && $file_body->version === $file_version + 1, 'Accumulated changes did not rebuild each body exactly once');
	handoff_check($signature->version === $signature_version, 'Body edit rebuilt the signature');
	handoff_check(!$function->body->syntax_changed && !$collection->root->single_body()->body->syntax_changed, 'Successful preparation did not consume source changes');

	// A signature change stays on the collected identity until analysis consumes it.
	file_put_contents($path, 'function value(): uint32 { return 2; } return 3;');
	$compiler->sync([$path]);
	$compiler->sync([$path]);
	handoff_check($entry->change_status === change_state::changed, 'Repeated parse erased declaration change');
	handoff_check($signature->change_status === change_state::unchanged && $signature->state === preparation_state::ready, 'Parser scheduled declaration preparation');
	$compiler->prepare();
	handoff_check($signature->version === $signature_version + 1, 'Analysis missed the changed signature');

	// Partial parsing may replace a body before a later syntax failure; retry must retain it.
	$version = $body->version;
	file_put_contents($path, 'function value(): uint32 { return 4; } function broken(');
	$failed = false;
	try { $compiler->sync([$path]); }
	catch (\RuntimeException $expected) { $failed = true; }
	handoff_check($failed && !$collection->parse_complete, 'Expected incomplete parse');
	handoff_check($body->version === $version && $body->state === preparation_state::ready, 'Failed parser changed semantic state');
	handoff_check($body->executable_body() === $function->body, 'Partial parse left work observing obsolete syntax');
	file_put_contents($path, 'function value(): uint32 { return 4; } return 3;');
	$compiler->sync([$path]);
	$compiler->sync([$path]);
	$compiler->prepare();
	handoff_check($function->body->work() === $body && $body->version === $version + 1, 'Failed-parse repair lost pending body work');
	handoff_check($body->executable_body() === $function->body && $file_body->executable_body() === $collection->root->single_body()->body, 'Repair lost executable-body links');

	// A failed semantic attempt keeps its source change and diagnostic through reparse.
	file_put_contents($path, 'function value(): uint32 { return $missing; } return 3;');
	$compiler->sync([$path]);
	$failed = false;
	try { $compiler->prepare(); }
	catch (\RuntimeException $expected) { $failed = true; }
	handoff_check($failed && $body->failed && $function->body->syntax_changed, 'Failed preparation consumed source change');
	$message = $body->failure_message;
	$compiler->sync([$path]);
	handoff_check($body->failed && $body->failure_message === $message && $function->body->syntax_changed, 'Reparse erased failed work');
	file_put_contents($path, 'function value(): uint32 { return 5; } return 3;');
	$compiler->sync([$path]);
	$compiler->prepare();

	// A deletion in one file must not dispose membership before the whole parse join succeeds.
	file_put_contents($path, 'return 3;');
	file_put_contents($other, 'function unfinished(');
	$failed = false;
	try { $compiler->sync([$path, $other]); }
	catch (\RuntimeException $expected) { $failed = true; }
	handoff_check($failed && $entry->change_status === change_state::deleted, 'Expected deletion during a failed join');
	handoff_check($entry->preparation === $signature && $signature->change_status !== change_state::deleted, 'Failed join retired semantic identity');
	handoff_check(isset($collection->entries[$entry->local_index]), 'Failed join disposed collected membership');
	$failed = false;
	try { Compiler_Lifecycle::retire_deleted_sources(Model::collected_files(), Model::$language_scope); }
	catch (\RuntimeException $expected) { $failed = true; }
	handoff_check($failed && $entry->preparation === $signature, 'Retirement did not guard the whole join');
	file_put_contents($other, 'function neighbor(): int { return 8; }');
	$compiler->sync([$other]);
	handoff_check($signature->change_status === change_state::deleted && $body->change_status === change_state::deleted, 'Successful join did not retire semantic work');
	handoff_check($entry->preparation === null && $function->body->work() === null, 'Retirement retained semantic attachments');
	handoff_check(!isset($collection->entries[$entry->local_index]) && count(Model::$global_scope->functions_named('value')) === 0, 'Frontend disposal retained deleted declaration');
	$compiler->prepare();
}
finally {
	unlink($path);
	unlink($other);
	rmdir($directory);
}
echo "Lifecycle handoff: passive parsing, accumulated changes, retries and join-ordered retirement passed\n";
