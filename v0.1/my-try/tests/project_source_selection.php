<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function selection_check(bool $condition, string $message): void {
	if (!$condition) { throw new \LogicException($message); }
}
function selected_inputs(string $root, array $app_paths = ['main.phs']): Storage {
	$inputs = new Storage();
	foreach ([['app', $root, $app_paths], ['dependency', $root . '/dep', ['library.phs']]] as [$name, $path, $sources]) {
		$input = new module_input($path, $name);
		$input->selected_sources = true;
		$input->source_paths = $sources;
		$inputs->append($input);
	}
	return $inputs;
}
$root = sys_get_temp_dir() . '/scpp_selected_' . bin2hex(random_bytes(6));
mkdir($root); mkdir($root . '/dep'); mkdir($root . '/.prism');
try {
	file_put_contents($root . '/main.phs', 'echo answer(), "\\n";');
	file_put_contents($root . '/dep/library.phs', 'function answer():int {return 42;}');
	file_put_contents($root . '/.prism/stale.phs', 'invalid syntax');
	file_put_contents($root . '/ignored.phs', 'invalid syntax');
	$compiler = new Compiler();
	$inputs = selected_inputs($root);
	$compiler->init_modules($inputs);
	$compiler->select_entry('app', 'main.phs');
	$compiler->exec_cpp();
	selection_check(count(Model::sources()) === 2, 'Unselected inputs discovered');
	$source = Source_Registry::find($root . '/dep/library.phs');
	selection_check($source->owning_module()->name === 'dependency', 'Nested source shadowed by parent root');
	selection_check(!$compiler->init_modules($inputs), 'Identical selection discarded preparation');
	$previous = Model::$cpp_files;
	foreach ([['../escape.phs'], ['dep/library.phs'], ['./main.phs'], ['main.phs', 'main.phs']] as $invalid) {
		$rejected = false;
		try { $compiler->init_modules(selected_inputs($root, $invalid)); }
		catch (\LogicException $error) { $rejected = true; }
		selection_check($rejected && Model::$cpp_files === $previous, 'Invalid selection changed published session');
	}
	$rejected = false;
	try { $compiler->update_cpp([$root . '/ignored.phs']); }
	catch (\LogicException $error) { $rejected = true; }
	selection_check($rejected, 'Notification admitted an unselected source');
	file_put_contents($root . '/dep/library.phs', 'function answer():int {return 123;}');
	$compiler->update_cpp([$root . '/dep/library.phs']);
	selection_check(Source_Registry::find($root . '/dep/library.phs') === $source, 'Edit replaced stable membership');
	unlink($root . '/dep/library.phs');
	$rejected = false;
	try { $compiler->update_cpp([$root . '/dep/library.phs']); }
	catch (\RuntimeException $error) { $rejected = true; }
	selection_check($rejected && Model::$cpp_files->is_empty(), 'Deleted provider retained usable output');
	file_put_contents($root . '/dep/library.phs', 'function answer():int {return 42;}');
	$compiler->update_cpp([$root . '/dep/library.phs']);
	file_put_contents($root . '/extra.phs', 'function extra():int {return 5;}');
	selection_check($compiler->init_modules(selected_inputs($root, ['main.phs', 'extra.phs'])), 'Selection change did not rebuild');
	$compiler->select_entry('app', 'main.phs');
	$compiler->exec_cpp();
	selection_check(count(Model::sources()) === 3, 'Added selection absent');
	echo "project source selection: passed\n";
}
finally {
	foreach (['main.phs', 'ignored.phs', 'extra.phs', 'dep/library.phs', '.prism/stale.phs'] as $path) {
		if (is_file($root . '/' . $path)) { unlink($root . '/' . $path); }
	}
	rmdir($root . '/dep'); rmdir($root . '/.prism'); rmdir($root);
}
