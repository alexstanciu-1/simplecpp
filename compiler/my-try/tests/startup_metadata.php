<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
function startup_check(bool $condition, string $message): void {
	if (!$condition) { throw new \LogicException($message); }
}
$root = $argv[1];
$fresh = ($argv[2] ?? '') === 'fresh';
if (!$fresh) {
	mkdir($root); mkdir($root . '/metadata'); mkdir($root . '/source');
	copy(dirname(__DIR__, 3) . '/runtime/metadata/modules/core.json', $root . '/metadata/core.json');
	file_put_contents($root . '/source/main.phs', '$v vector<vector<int>> = [[1]]; echo count($v), "\\n";');
}
$input = Runtime_Catalog_Loader::load($root . '/metadata', ['core']);
Compiler_Lifecycle::configure_runtime($input);
$compiler = new Compiler();
$compiler->init([$root . '/source']);
$compiler->select_entry('main', 'main.phs');
if ($fresh) {
	$rejected = false;
	try { $compiler->exec_cpp(); }
	catch (\RuntimeException $error) { $rejected = str_contains($error->getMessage(), 'hashable'); }
	startup_check($rejected && Model::$cpp_files->is_empty(), 'New session ignored changed provider constraint');
	echo "fresh constraint rejected\n"; exit(0);
}
$publication = new Output_Publication($root . '/initial');
try { $compiler->exec_cpp($publication); } finally { $publication->close(); }
$target = Model::$runtime_callables->function_definition('core.strlen');
$schema = json_decode(file_get_contents($root . '/metadata/core.json'), true, 512, JSON_THROW_ON_ERROR);
foreach ($schema['types'] as &$type) {
	if ($type['id'] === 'core.vector') { $type['parameters'][0]['requires'][] = 'hashable'; }
}
unset($type);
file_put_contents($root . '/metadata/core.json', json_encode($schema, JSON_THROW_ON_ERROR));
$replacement = Runtime_Catalog_Loader::load($root . '/metadata', ['core']);
$rejected = false;
try { Compiler_Lifecycle::configure_runtime($replacement); }
catch (\LogicException $error) { $rejected = str_contains($error->getMessage(), 'before compilation'); }
startup_check($rejected && Model::$runtime_metadata === $input, 'Live replacement changed startup metadata');
file_put_contents($root . '/source/main.phs', '$v vector<vector<int>> = [[1],[2]]; echo count($v), "\\n";');
// Invalid disk bytes prove source notifications do not reload the provider.
$changed_bytes = file_get_contents($root . '/metadata/core.json');
file_put_contents($root . '/metadata/core.json', 'invalid metadata');
$publication = new Output_Publication($root . '/edited');
try { $compiler->update_cpp([$root . '/source/main.phs'], $publication); } finally { $publication->close(); }
startup_check(Model::$runtime_callables->function_definition('core.strlen') === $target, 'Source edit replaced provider identity');
Compiler_Lifecycle::reset_syntax();
startup_check(Model::$runtime_metadata === $input, 'Syntax reset replaced metadata snapshot');
startup_check(Model::$runtime_callables->function_definition('core.strlen') !== $target, 'Syntax reset reused stale callable identity');
$rejected = false;
try { Model::$cpp_callable_bindings->function_binding($target); } catch (\LogicException $error) { $rejected = true; }
startup_check($rejected, 'Stale callable binding accepted');
file_put_contents($root . '/metadata/core.json', $changed_bytes);
echo "immutable startup metadata: passed\n";
