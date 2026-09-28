<?php

namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function preparation_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Notifications deliberately bypass mtime granularity in this incremental proof. */
function preparation_edit(Compiler $compiler, source_record $source, string $text): void
{
	file_put_contents(Source_Registry::full_path($source->owning_module(), $source->path), $text);
	$source->changes = change_state::changed;
	$compiler->tokenize();
	$compiler->parse();
}

function preparation_function(string $name): function_structure
{
	$entries = Model::$global_scope->functions_named($name);
	return Syntax_Nodes::function_data($entries[0]->node);
}

$directory = sys_get_temp_dir() . '/scpp_incremental_preparation_' . bin2hex(random_bytes(6));
mkdir($directory);
file_put_contents($directory . '/a.phs', 'function target(int $x): int { return $x; } function untouched(): int { return 8; }');
file_put_contents($directory . '/b.phs', 'function caller(int $x): int { return target($x); }');
file_put_contents($directory . '/c.phs', '$local int = 3; return $local;');
try
{
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$module = Model::$modules[$directory];
	$a = $module->sources['a.phs'];
	$b = $module->sources['b.phs'];
	$c = $module->sources['c.phs'];
	$compiler->tokenize();
	$compiler->parse();
	$compiler->prepare();
	$target = preparation_function('target');
	$caller = preparation_function('caller');
	$untouched = preparation_function('untouched');
	$caller_body = $caller->body;
	$caller_version = $caller->body_preparation->version;
	$target_facts = $target->require_preparation();
	$untouched_body = $untouched->body;
	$untouched_version = $untouched->body_preparation->version;
	$entry_version = $c->parsed->collection->body_preparation->version;
	$entry_node = $c->parsed->root->first_child();
	$entry_facts = Syntax_Nodes::binding_data($entry_node)->require_preparation();
	$compiler->prepare();
	preparation_check($caller->body_preparation->version === $caller_version, 'No-op preparation rebuilt a body');
	preparation_check($target->require_preparation() === $target_facts, 'No-op preparation replaced a signature');

	preparation_edit($compiler, $a, 'function target(int $x): int { $y = 2; return $x; } function untouched(): int { return 8; }');
	$compiler->prepare();
	preparation_check($caller->body_preparation->version === $caller_version, 'Implementation-only change rebuilt a caller');
	preparation_check($target->require_preparation() === $target_facts, 'Body-only change replaced its signature');
	preparation_check(($untouched->body === $untouched_body) && ($untouched->body_preparation->version === $untouched_version), 'Changed file rebuilt an unchanged neighboring body');
	preparation_check($c->parsed->collection->body_preparation->version === $entry_version, 'Unrelated file body was rebuilt');
	preparation_check(Syntax_Nodes::binding_data($entry_node)->require_preparation() === $entry_facts, 'Unrelated attached facts were cleared');

	preparation_edit($compiler, $a, 'function target(uint32 $x): int { return 5; } function untouched(): int { return 8; }');
	$compiler->prepare();
	preparation_check($caller->body === $caller_body, 'Dependency update reparsed the caller');
	preparation_check($caller->body_preparation->version === ($caller_version + 1), 'Signature update did not rebuild the caller once');
	$caller_version = $caller->body_preparation->version;

	// Unchanged top-level executable syntax and facts survive declarations inserted before it.
	preparation_edit($compiler, $c, 'function added(): int { return 1; } $local int = 3; return $local;');
	$compiler->prepare();
	preparation_check($c->parsed->collection->body_preparation->version === $entry_version, 'Declaration insertion rebuilt unchanged entry code');
	preparation_check(Syntax_Nodes::binding_data($entry_node)->require_preparation() === $entry_facts, 'Moved entry syntax lost its facts');

	// Removing a dependency from a body removes both graph directions.
	preparation_edit($compiler, $b, 'function caller(int $x): int { return $x; }');
	$compiler->prepare();
	$caller_version = $caller->body_preparation->version;
	preparation_check(!isset($target->occurrence()->preparation->dependents[$caller->body_preparation]), 'Obsolete dependency edge survived body rebuilding');
	preparation_edit($compiler, $a, 'function untouched(): int { return 8; }');
	$compiler->prepare();
	preparation_check(q_count(Model::$global_scope->functions_named('target')) === 0, 'Deleted function remained in the global index');
	preparation_check($caller->body_preparation->version === $caller_version, 'Former dependency still invalidated the caller');
	foreach ($a->parsed->collection->entries as $entry) {
		preparation_check($entry->change_status !== change_state::deleted, 'Deleted collected record remained owned');
	}

	// A later addition invalidates an absent lookup without requiring source edits in its user.
	preparation_edit($compiler, $b, 'function caller(int $x): int { return missing($x); }');
	$failed = false;
	try {
		$compiler->prepare();
	}
	catch (\RuntimeException $expected) {
		$failed = true;
	}
	preparation_check($failed, 'Missing function did not reject');
	preparation_edit($compiler, $a, 'function missing(int $x): int { return $x; }');
	$compiler->prepare();
	preparation_check($caller->body_preparation->state === preparation_state::ready, 'Missing-name dependency could not recover after addition');

	// A new duplicate candidate must invalidate an already successful global lookup.
	preparation_edit($compiler, $c, 'function missing(int $x): int { return $x; }');
	$failed = false;
	try {
		$compiler->prepare();
	}
	catch (\RuntimeException $expected) {
		$failed = true;
	}
	preparation_check($failed, 'New global ambiguity was not observed');
	preparation_edit($compiler, $c, 'return 0;');
	$compiler->prepare();

	// Declaration cycles differ from legal recursive function calls.
	preparation_edit($compiler, $a, 'struct A { B $b; } struct B { A $a; } function missing(int $x): int { return $x; }');
	$failed = false;
	try {
		$compiler->prepare();
	}
	catch (\RuntimeException $expected) {
		$failed = str_contains($expected->getMessage(), 'Cyclic');
	}
	preparation_check($failed, 'By-value declaration cycle was not detected');
	preparation_edit($compiler, $a, 'function missing(int $x): int { return missing($x); }');
	$compiler->prepare();
	preparation_check(preparation_function('missing')->body_preparation->state === preparation_state::ready, 'Recursive call was mistaken for a declaration cycle');

	// A prior-phase error stops preparation before independent ready facts are touched.
	$stable = $caller->require_preparation();
	$failed = false;
	try {
		preparation_edit($compiler, $c, 'function broken(');
	}
	catch (\RuntimeException $expected) {
		$failed = true;
	}
	preparation_check($failed, 'Expected parse failure');
	try {
		$compiler->prepare();
		throw new \LogicException('Preparation started after a failed parse');
	}
	catch (\RuntimeException $expected) {
		preparation_check($caller->require_preparation() === $stable, 'Earlier failure cleared independent facts');
	}
	preparation_edit($compiler, $c, 'return 0;');
	preparation_edit($compiler, $a, 'function left(int $x): int { return $x; } function right(int $x): int { return $x; }');
	preparation_edit($compiler, $b, 'function caller(int $x): int { left($x); return right($x); }');
	$compiler->prepare();
	$version = preparation_function('caller')->body_preparation->version;
	preparation_edit($compiler, $a, 'function left(uint32 $x): int { return $x; } function right(uint32 $x): int { return $x; }');
	$compiler->prepare();
	preparation_check(preparation_function('caller')->body_preparation->version === ($version + 1), 'Two changed dependencies rebuilt one body more than once');

	preparation_edit($compiler, $a, 'struct Inner { uint8 $x; } struct Outer { Inner $inner; }');
	preparation_edit($compiler, $b, 'function inspect(Outer $x): uint8 { return $x->inner->x; }');
	$compiler->prepare();
	$outer = Model::$global_scope->source_types_named('Outer')[0];
	$outer_version = $outer->preparation->version;
	$inspect = preparation_function('inspect');
	$inspect_version = $inspect->body_preparation->version;
	preparation_edit($compiler, $a, 'struct Inner { uint16 $x; } struct Outer { Inner $inner; }');
	$compiler->prepare();
	preparation_check($outer->preparation->version === ($outer_version + 1), 'Nested record change did not propagate through declarations');
	preparation_check($inspect->body_preparation->version === ($inspect_version + 1), 'Nested field dependency did not rebuild its body');

	// Explicit full invalidation feeds the same worker rather than selecting a second preparation path.
	Compiler_Lifecycle::reset_preparation();
	$compiler->prepare();
	preparation_check($inspect->body_preparation->version === ($inspect_version + 2), 'Explicit reset failed to select existing owners');
	$c->changes = change_state::deleted;
	$compiler->parse();
	$compiler->prepare();
	preparation_check(q_count($c->parsed->collection->entries) === 0, 'Deleted file retained collected rows');
	preparation_edit($compiler, $c, '$again = 4; return $again;');
	$compiler->prepare();
	preparation_check($c->parsed->collection->body_preparation->state === preparation_state::ready, 'Deleted file could not reappear after cleanup');

}
finally
{
	foreach (glob($directory . '/*') as $path) {
		unlink($path);
	}
	rmdir($directory);
}
echo "Incremental preparation: retained facts, selected bodies, dependencies, lookup changes, deletion and cycles passed\n";
