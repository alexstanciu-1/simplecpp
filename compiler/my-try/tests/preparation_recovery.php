<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function recovery_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

function recovery_function(string $name): function_node
{
	return object_cast(Model::$global_scope->functions_named($name)[0]->syntax(), function_node::class);
}

/** Assert that a failed preparation withholds completed results and preserves its diagnostic. */
function recovery_failure(Compiler $compiler): string
{
	try {
		$compiler->prepare();
	}
	catch (\RuntimeException $error) {
		recovery_check(!Model::$rebuild_required, 'Expected diagnostic requested full rebuild');
		recovery_check(Model::$prepared_files->is_empty() && Model::$cpp_files->is_empty(), 'Failure published completed output');
		return $error->getMessage();
	}
	throw new \LogicException('Expected preparation failure');
}

$directory = sys_get_temp_dir() . '/scpp_recovery_' . bin2hex(random_bytes(6));
mkdir($directory);
$a = $directory . '/a.phs';
$b = $directory . '/b.phs';
$c = $directory . '/c.phs';
file_put_contents($a, 'function target(int $x): int { return $x; }');
file_put_contents($b, 'function caller(): int { return target(7); } return caller();');
file_put_contents($c, 'function independent(): int { return 3; }');
try
{
	$compiler = new Compiler();
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->sync([]);
	$compiler->prepare();
	$target = recovery_function('target');
	$caller = recovery_function('caller');
	$signature = $target->require_preparation();
	$caller_body = $caller->body;
	$caller_version = $caller->body->work()->version;
	$target_version = $target->occurrence()->preparation->version;
	$independent = recovery_function('independent');
	$independent_version = $independent->body->work()->version;

	// A failed signature invalidates consumers while independent changed work can complete.
	file_put_contents($a, 'function target(int $x): Missing { return $x; }');
	file_put_contents($c, 'function independent(): int { return 4; }');
	$compiler->sync([$a, $c]);
	$error = recovery_failure($compiler);
	recovery_check(str_contains($error, 'Missing'), 'Lost originating signature error');
	recovery_check($target->occurrence()->preparation->failed && $caller->body->work()->failed, 'Failure did not reach the consumer');
	recovery_check($target->occurrence()->change_status === change_state::changed && $caller->body->work()->change_status === change_state::changed, 'Failed work was settled');
	recovery_check($caller->body->work()->version === $caller_version, 'Failed consumer published body facts');
	recovery_check($independent->body->work()->version === $independent_version + 1 && !$independent->body->work()->failed, 'Independent work did not complete after failure');

	// No-edit increments must retry pending work without losing errors or looping in one pass.
	$compiler->sync([]);
	recovery_check(recovery_failure($compiler) === $error, 'No-edit retry changed the diagnostic');
	recovery_check($caller->body->work()->version === $caller_version, 'No-edit retry consumed unavailable signature');
	$compiler->sync([$a]); // Even re-parsing identical failing text must not settle it.
	recovery_check($target->occurrence()->change_status === change_state::changed, 'Collector cleared unresolved change');
	recovery_failure($compiler);

	// Recovering to the previous signature still wakes consumers; no edit to caller is needed.
	file_put_contents($a, 'function target(int $x): int { return $x; }');
	$compiler->sync([$a]);
	$compiler->prepare();
	recovery_check($target->require_preparation() === $signature, 'Equivalent recovered signature lost identity');
	recovery_check($target->occurrence()->preparation->version === $target_version + 1, 'Recovery was not observable to dependencies');
	recovery_check($caller->body === $caller_body && $caller->body->work()->version === $caller_version + 1, 'Recovery failed to rebuild unchanged caller once');
	recovery_check(!$caller->body->work()->failed && $caller->body->work()->change_status === change_state::unchanged, 'Successful retry did not settle error state');
	recovery_check($target->occurrence()->change_status === change_state::unchanged, 'Successful declaration did not settle symbol state');

	// A body error has no effect on the independently valid signature or its callers.
	$caller_version = $caller->body->work()->version;
	file_put_contents($a, 'function target(int $x): int { return $missing; }');
	$compiler->sync([$a]);
	recovery_failure($compiler);
	recovery_check($target->body->work()->failed && !$target->occurrence()->preparation->failed, 'Body failure damaged signature state');
	recovery_check(!$caller->body->work()->failed && $caller->body->work()->version === $caller_version, 'Body failure invalidated caller');
	file_put_contents($a, 'function target(int $x): int { return $x; }');
	$compiler->sync([$a]);
	$compiler->prepare();

	// Exact body bytes: internal whitespace replaces the body; external movement retains it.
	$body = $target->body;
	$version = $target->body->work()->version;
	file_put_contents($a, 'function target(int $x): int {  return $x; }');
	$compiler->sync([$a]);
	recovery_check($target->body !== $body, 'Body text comparison ignored internal whitespace');
	$compiler->prepare();
	recovery_check($target->body->work()->version === $version + 1, 'Whitespace edit did not prepare its body');
	$body = $target->body;
	file_put_contents($a, "\n\nfunction target(int \$x): int {  return \$x; }");
	$compiler->sync([$a]);
	$compiler->prepare();
	recovery_check($target->body === $body && $target->body->work()->version === $version + 1, 'Moving identical body text rebuilt it');

	// A new consumer discovers a failed declaration through guarded lookup, not stale facts.
	file_put_contents($a, 'function target(int $x): Missing { return $x; }');
	file_put_contents($b, 'function fresh(): int { return target(8); } return fresh();');
	$compiler->sync([$a, $b]);
	recovery_failure($compiler);
	recovery_check(recovery_function('fresh')->body->work()->failed, 'New consumer used failed declaration facts');
	// Editing a blocked consumer to remove its use must not be held by its old dependency.
	file_put_contents($b, 'function fresh(): int { return 8; } return fresh();');
	$compiler->sync([$b]);
	recovery_failure($compiler);
	recovery_check(!recovery_function('fresh')->body->work()->failed, 'Removed dependency kept consumer blocked');
	file_put_contents($a, 'function target(int $x): int { return $x; }');
	$compiler->sync([$a]);
	$compiler->prepare();

	// Declaration cycles keep changed state and can recover when only one participant is edited.
	file_put_contents($a, 'struct A { B $b; } struct B { A $a; }');
	file_put_contents($b, 'function use_record(A $a): void {}');
	$compiler->sync([$a, $b]);
	recovery_check(str_contains(recovery_failure($compiler), 'Cyclic by-value'), 'Missing cycle diagnostic');
	$compiler->sync([]);
	recovery_failure($compiler);
	file_put_contents($a, 'struct A { B $b; } struct B { uint8 $x; }');
	$compiler->sync([$a]);
	$compiler->prepare();
	recovery_check(!recovery_function('use_record')->body->work()->failed, 'Cycle repair failed to recover consumer');
	// Notifications during deletion must not resurrect another deleted member of a failed cycle.
	file_put_contents($a, 'struct A { B $b; } struct B { A $a; }');
	$compiler->sync([$a]);
	recovery_failure($compiler);
	file_put_contents($a, 'function replacement(): int { return 1; }');
	file_put_contents($b, 'return replacement();');
	$compiler->sync([$a, $b]);
	$compiler->prepare();
	recovery_check(count(Model::$global_scope->source_types_named('A')) === 0 && count(Model::$global_scope->source_types_named('B')) === 0, 'Deletion notification revived a failed declaration');

	// An internal error must discard all compiled state on the next entry, even without edits.
	foreach (['prepare', 'sync', 'tokenize', 'parse'] as $retry)
	{
		$broken = recovery_function('replacement');
		$before = Model::sources()[0]->tokens;
		$old_global = Model::$global_scope;
		$broken->occurrence()->preparation->change_status = change_state::changed;
		unset($broken->return_type);
		try {
			$compiler->prepare();
			throw new \LogicException('Expected internal preparation error');
		}
		catch (\Error $error) {
			recovery_check(Model::$rebuild_required, 'Internal error did not request rebuild');
		}
		recovery_check(Model::$prepared_files->is_empty() && Model::$cpp_files->is_empty(), 'Internal error published results');
		if ($retry === 'sync') {
			$compiler->sync([]);
		}
		elseif ($retry === 'tokenize') {
			$compiler->tokenize();
		}
		elseif ($retry === 'parse') {
			$compiler->parse();
		}
		$compiler->prepare();
		recovery_check(!Model::$rebuild_required && Model::$global_scope !== $old_global, 'Retry retained unsafe semantic state');
		recovery_check(Model::sources()[0]->tokens !== $before && recovery_function('replacement') !== $broken, 'Retry did not rebuild tokens and syntax');
	}
}
finally {
	foreach (glob($directory . '/*') as $path) {
		unlink($path);
	}
	rmdir($directory);
}
echo "Preparation recovery: persistent changes, failure propagation, no-edit retries, independent progress, recovery and body text comparison passed\n";
