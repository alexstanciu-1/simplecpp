<?php

namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function module_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Host fixtures use an ordinary list; the public boundary accepts typed module inputs. */
function module_inputs(array $inputs): Storage /** Storage<module_input> */
{
	$result /** Storage<module_input> */ = new Storage();
	foreach ($inputs as $input) {
		$result->append($input);
	}
	return $result;
}

$directory = sys_get_temp_dir() . '/scpp_module_sync_' . bin2hex(random_bytes(6));
$cwd = getcwd();
mkdir($directory);
foreach (['a', 'b', 'c'] as $name) {
	mkdir($directory . '/' . $name);
	file_put_contents($directory . '/' . $name . '/main.phs', 'return 7;');
}
try
{
	chdir($directory);
	$compiler = new Compiler();
	$inputs = module_inputs([new module_input('a')]);
	module_check($compiler->init_modules($inputs), 'Initial configuration did not rebuild');
	$a = Model::$modules->find('a');
	module_check(($a->declared_path === 'a') && ($a->resolved_path === $directory . '/a'), 'Declared and canonical paths were not retained');
	$compiler->exec_cpp();
	$source = Model::sources()[0];
	$syntax = $source->parsed;
	$scope = Model::$global_scope;
	$output = Model::$cpp_files;
	$text = serialize($output);
	module_check(!$compiler->init_modules($inputs), 'Identical configuration triggered rebuild');
	module_check((Model::sources()[0] === $source) && ($source->parsed === $syntax) && (Model::$global_scope === $scope) && (Model::$cpp_files === $output), 'No-change initialization replaced retained state');
	module_check(($a->changes === 0) && !Model::$full_sync_pending, 'No-change retained transient flags');

	// Invalid complete input must not mutate the retained session, even after a valid first entry.
	foreach ([
		[new module_input('b'), new module_input('missing')],
		[new module_input('a', 'duplicate'), new module_input('b', 'duplicate')],
		[new module_input('a'), new module_input('a/.')],
		[new module_input('.'), new module_input('a')],
		[new module_input('a/main.phs')]
	] as $invalid)
	{
		$failed = false;
		try {
			$compiler->init_modules(module_inputs($invalid));
		}
		catch (\Throwable $expected) {
			$failed = true;
		}
		module_check($failed, 'Invalid module configuration accepted');
		module_check((Model::$cpp_files === $output) && (Model::sources()[0] === $source) && (Model::$modules->find('a') === $a), 'Rejected configuration changed published data');
	}

	// Explicit names retain identity through path edits; changing the key is delete/add.
	$named = module_inputs([new module_input('a', 'app')]);
	module_check($compiler->init_modules($named), 'Renaming module failed to rebuild');
	$app = Model::$modules->find('app');
	module_check(($a->changes === SYNC_DELETED) && ($app !== $a), 'Rename did not retire old key');
	module_check(Model::$cpp_files->is_empty() && Model::syntax_files()->is_empty(), 'Full reset retained compilation results');
	module_check(serialize($output) === $text, 'Reset mutated an externally retained output');
	module_check($compiler->init_modules(module_inputs([new module_input('b', 'app')])), 'Named path change failed to rebuild');
	module_check((Model::$modules->find('app') === $app) && ($app->resolved_path === $directory . '/b') && ($app->changes === SYNC_CHANGED), 'Named path change lost identity or change flag');

	// Reordering alone resets every source; a partial notification still synchronizes all modules.
	$pair = module_inputs([new module_input('a'), new module_input('b')]);
	$compiler->init_modules($pair);
	module_check((Model::$modules->find('a') === $a) && ($a->changes === SYNC_ADDED), 'Reappearance did not reuse deleted identity');
	$compiler->sync(['a/main.phs']);
	module_check(count(Model::syntax_files()) === 2, 'Partial notification skipped required full sync');
	$old_source = Model::sources()[0];
	$old_scope = Model::$global_scope;
	$reordered = module_inputs([new module_input('b'), new module_input('a')]);
	module_check($compiler->init_modules($reordered), 'Order-only change did not rebuild');
	module_check((Model::modules()[0]->name === 'b') && (Model::modules()[1] === $a) && ($a->changes === SYNC_CHANGED), 'Module order or retained identity changed incorrectly');
	module_check((Model::$global_scope !== $old_scope) && (Source_Registry::find($directory . '/a/main.phs') !== $old_source), 'Order-only change retained source/scopes');
	module_check(!$compiler->init_modules($reordered) && Model::$full_sync_pending, 'No-change initialization cancelled pending full sync');
	file_put_contents('a/later.phs', 'return 9;');
	$compiler->sync(['a/later.phs']);
	module_check((count(Model::syntax_files()) === 3) && !Model::$full_sync_pending, 'Full sync lost a new notification');
	unlink('a/later.phs');

	// A failed frontend leaves the full barrier pending across identical initialization and retry.
	$compiler->init_modules($pair);
	file_put_contents('b/main.phs', '$');
	$failed = false;
	try {
		$compiler->sync([]);
	}
	catch (\RuntimeException $expected) {
		$failed = true;
	}
	module_check($failed && Model::$full_sync_pending, 'Frontend failure cleared full-sync obligation');
	file_put_contents('b/main.phs', 'return 7;');
	$compiler->init_modules($pair);
	$compiler->sync([]);
	module_check((count(Model::syntax_files()) === 2) && !Model::$full_sync_pending, 'Retry failed to rebuild all files');

	// Resolved-path changes matter even when key and declared path are unchanged.
	symlink($directory . '/a', 'alias');
	$alias = module_inputs([new module_input('alias', 'linked')]);
	$compiler->init_modules($alias);
	$linked = Model::$modules->find('linked');
	unlink('alias');
	symlink($directory . '/b', 'alias');
	module_check($compiler->init_modules($alias), 'Changed canonical root was missed');
	module_check((Model::$modules->find('linked') === $linked) && ($linked->resolved_path === $directory . '/b'), 'Canonical root update lost identity');

	// A discovery failure retires partial data and permits retry of the same configuration.
	chmod('c', 0000);
	if (!is_readable('c'))
	{
		$failed = false;
		try {
			$compiler->init(['a', 'c']);
		}
		catch (\RuntimeException $expected) {
			$failed = true;
		}
		module_check($failed && !Model::$modules_ready && Model::sources()->is_empty(), 'Discovery failure exposed partial source graph');
		$blocked = false;
		try {
			$compiler->sync([]);
		}
		catch (\LogicException $expected) {
			$blocked = true;
		}
		module_check($blocked, 'Frontend accepted failed discovery');
		chmod('c', 0700);
		$compiler->init(['a', 'c']);
		$compiler->sync([]);
		module_check(Model::$modules_ready && (count(Model::syntax_files()) === 2), 'Identical configuration did not retry failed discovery');
	}
	chmod('c', 0700);
	$compiler->init([]);
	module_check(Model::modules()->is_empty() && Model::sources()->is_empty() && Model::$cpp_files->is_empty(), 'Removing all modules retained live data');
	$compiler->sync([]);
	module_check(!$compiler->init_modules(new Storage()), 'Repeated empty configuration rebuilt');
	Compiler_Lifecycle::reset();
	module_check(Model::$modules->inventory()->is_empty(), 'Fresh session retained module tombstones');
}
finally
{
	chdir($cwd);
	chmod($directory . '/c', 0700);
	if (is_link($directory . '/alias')) {
		unlink($directory . '/alias');
	}
	foreach (['a', 'b', 'c'] as $name) {
		foreach (glob($directory . '/' . $name . '/*') as $path) {
			unlink($path);
		}
		rmdir($directory . '/' . $name);
	}
	rmdir($directory);
}
echo "Module sync: identity, paths, order, atomic rejection, full reset, deletion, failure and retry passed\n";
