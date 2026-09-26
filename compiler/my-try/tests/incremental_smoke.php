<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

/** Capture values, not model handles, so later compilation cannot change the baseline. */
function smoke_output(): array /** hash<string> */
{
	$result /** hash<string> */ = [];
	foreach (Model::$llvm_files as $output) {
		$result[$output->file_name] = $output->text;
	}
	return $result;
}

function smoke_require(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

$restore_check = in_array('--restore', $argv, true);
$directory = sys_get_temp_dir() . '/scpp_sync_smoke_' . bin2hex(random_bytes(6));
mkdir($directory);
$definition = $directory . '/a.phs';
$caller = $directory . '/b.phs';
$original = 'function value(): int { return 1; }';
try
{
	file_put_contents($definition, $original);
	file_put_contents($caller, 'return value();');
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_llvm();
	$baseline = smoke_output();
	$old_syntax = Model::$syntax_files[0];
	$kept_caller = Model::$syntax_files[1];

	file_put_contents($definition, 'function value(): int { return 2; }');
	$compiler->update_llvm([$definition]);
	$targets = Scope_Lookup::live(Model::$global_scope->functions_named('value'));
	smoke_require(count($targets) === 1, 'Update lost unique function target');
	smoke_require($targets[0]->changes === SYNC_BODY_CHANGED, 'Wrong body-change flags');
	smoke_require(Model::$syntax_files[0] !== $old_syntax, 'Changed syntax was reused');
	smoke_require(Model::$syntax_files[1] === $kept_caller, 'Unchanged caller was reparsed');
	smoke_require(smoke_output() !== $baseline, 'Edit did not change generated output');
	$names = (new Name_Preparation())->prepare($kept_caller->collection);
	$reference = $kept_caller->collection->entries[$kept_caller->collection->function_references[0]];
	smoke_require($names->function_references[$reference->token_index] === $targets[0], 'Caller resolved the old declaration');

	// The usual smoke stops after one full build and one incremental update.
	file_put_contents($definition, $original);
	if ($restore_check)
	{
		$compiler->update_llvm([$definition]);
		$restored = smoke_output();
		smoke_require($restored === $baseline, 'Restore differs from original full-build output');
		smoke_require(Model::$syntax_files[1] === $kept_caller, 'Restore reparsed unchanged caller');
		$fresh = new Compiler();
		$fresh->init([$directory]);
		$fresh->exec_llvm();
		smoke_require(smoke_output() === $restored, 'Restored incremental output differs from fresh build');
	}
}
finally
{
	// Restore even after an assertion/compiler failure, then discard the isolated fixture.
	if (is_file($definition)) {
		file_put_contents($definition, $original);
		unlink($definition);
	}
	if (is_file($caller)) {
		unlink($caller);
	}
	rmdir($directory);
}
echo $restore_check
? "Incremental smoke: edit, restore and fresh-build output agreement passed\n"
: "Incremental smoke: one full build + one update passed; source restored\n";
