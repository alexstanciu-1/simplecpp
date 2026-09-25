<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

/** Assert a sync contract without relying on the implementation's comparison helpers. */
function sync_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Read live function candidates; the raw global buckets must still contain tombstones. */
function sync_function(string $name): collected_name
{
	$rows = Scope_Lookup::live(Model::$global_scope->functions_named($name));
	sync_check(count($rows) === 1, 'Expected one live ' . $name);
	return $rows[0];
}

$directory = sys_get_temp_dir() . '/scpp_sync_' . bin2hex(random_bytes(6));
mkdir($directory);
$a = $directory . '/a.phs';
$b = $directory . '/b.phs';
try
{
	file_put_contents($a, 'function value(): int { return 1; } struct Box { int $item; }');
	file_put_contents($b, 'return value();');
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec();
	sync_check(sync_function('value')->changes === SYNC_ADDED, 'Initial build is not all-added');
	$old_node = sync_function('value')->node;
	$old_tokens = Model::$tokens[0];
	$unchanged = Model::$syntax_files[1];
	file_put_contents($a, 'function value(): int { return 2; } struct Box { int $item; }');
	$compiler->update([$a]);
	sync_check(sync_function('value')->changes === SYNC_BODY_CHANGED, 'Body edit changed signature');
	sync_check(sync_function('value')->node !== $old_node && Model::$tokens[0] !== $old_tokens, 'Syntax was patched instead of replaced');
	sync_check(Model::$syntax_files[1] === $unchanged, 'Unchanged file was reparsed');
	$resolved = (new Name_Preparation())->prepare($unchanged->collection);
	$reference = $unchanged->collection->entries[$unchanged->collection->function_references[0]];
	sync_check($resolved->function_references[$reference->token_index] === sync_function('value'), 'Unchanged caller retained old binding');

	file_put_contents($a, "\nfunction value( ): int { return 2; }\nstruct Box { int \$item; }");
	$compiler->sync([$a]);
	sync_check(sync_function('value')->changes === 0, 'Whitespace retained stale change flags');
	file_put_contents($a, 'function value(int $x): int { return 2; } struct Box { int $other; }');
	$compiler->sync([$a]);
	sync_check(sync_function('value')->changes === SYNC_CHANGED, 'Signature-only edit flags wrong');
	$fields = [];
	foreach (Model::$collected_files[0]->entries as $entry) {
		if ($entry->kind === collected_name_kind::field_declaration) {
			$fields[$entry->name] = $entry->changes;
		}
	}
	sync_check($fields['item'] === SYNC_DELETED && $fields['other'] === SYNC_ADDED, 'Field deletion/addition lost');
	file_put_contents($a, 'function replacement(): int { return 3; }');
	$compiler->sync([$a]);
	sync_check(count(Scope_Lookup::live(Model::$global_scope->functions_named('value'))) === 0, 'Deleted function still resolves');
	sync_check(Model::$global_scope->functions_named('value')[0]->changes === SYNC_DELETED, 'Missing global tombstone');
	sync_check(sync_function('replacement')->changes === SYNC_ADDED, 'Added function flag missing');
	try {
		(new Name_Preparation())->prepare($unchanged->collection);
		throw new \LogicException('Removed target still resolved');
	}
	catch (\RuntimeException $expected) {
	}

	// A failed candidate cannot mutate the published bytes, syntax, or global contributions.
	$kept = Model::$syntax_files[0];
	file_put_contents($a, 'function broken(');
	try {
		$compiler->sync([$a]);
		throw new \LogicException('Malformed candidate accepted');
	}
	catch (\RuntimeException $expected) {
	}
	sync_check(Model::$syntax_files[0] === $kept && sync_function('replacement')->file === $kept->collection, 'Failed candidate damaged publication');
	sync_check(Model::$modules[0]->files[0]->content === $kept->tokens->content, 'Failed candidate changed published source');
	sync_check(Model::$llvm_files->is_empty(), 'Failed update retained generated output');

	// Duplicate definitions survive sync; deleting one makes lookup unambiguous again.
	file_put_contents($a, 'function value(): int { return 4; } function value(): int { return 5; }');
	$compiler->sync([$a]);
	sync_check(count(Scope_Lookup::live(Model::$global_scope->functions_named('value'))) === 2, 'Duplicate candidates collapsed');
	file_put_contents($a, 'function value(): int { return 5; } function value(): int { return 4; }');
	$compiler->sync([$a]);
	foreach (Scope_Lookup::live(Model::$global_scope->functions_named('value')) as $entry) {
		sync_check($entry->changes === 0, 'Reordered equivalent duplicate changed');
	}
	file_put_contents($a, 'function value(): int { return 4; }');
	$compiler->update([$a]);
	sync_check(sync_function('value')->changes === 0, 'Surviving duplicate lost exact match');
	unlink($a);
	$compiler->sync([$a]);
	sync_check((Model::$modules[0]->files[0]->changes & SYNC_DELETED) !== 0, 'Deleted file disappeared instead of remaining marked');
	sync_check(count(Scope_Lookup::live(Model::$global_scope->functions_named('value'))) === 0, 'Deleted file still exports functions');
	file_put_contents($a, 'function value(): int { return 6; }');
	$compiler->update([$a]);
	sync_check(sync_function('value')->changes === SYNC_ADDED, 'Historical tombstone participated in matching');
	sync_check(count(Model::$llvm_files) === 2, 'Deleted file was generated');
	$compiler->sync([]);
	sync_check(sync_function('value')->changes === 0, 'No-op update retained added flag');
	// New-file duplicates must not erase another file's contribution on deletion.
	$c = $directory . '/c.phs';
	file_put_contents($c, 'function value(): int { return 99; }');
	$compiler->sync([$c]);
	sync_check(count(Scope_Lookup::live(Model::$global_scope->functions_named('value'))) === 2, 'New file erased another definition');
	unlink($c);
	$compiler->update([$c]);
	sync_check(sync_function('value')->changes === 0, 'Deletion damaged another file definition');
	file_put_contents($a, 'function value(int $x): int { return 10; }');
	$compiler->sync([$a]);
	sync_check(sync_function('value')->changes === (SYNC_CHANGED + SYNC_BODY_CHANGED), 'Combined declaration/body flags wrong');
	file_put_contents($a, 'function value(): int { return 6; }');
	$compiler->init([$directory]);
	$compiler->exec();
	sync_check(count(Model::$modules[0]->files) === 2 && count(Model::$global_scope->functions_named('value')) === 1, 'Full module rebuild retained tombstones');
}
finally {
	foreach (glob($directory . '/*') as $path) {
		unlink($path);
	}
	rmdir($directory);
}
echo "Sync: consecutive edits, private failure, replacements, signatures, fields, duplicate matching, deletion, fresh addition and full rebuild passed\n";
