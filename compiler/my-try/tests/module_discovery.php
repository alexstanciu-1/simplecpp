<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function discovery_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

$directory = sys_get_temp_dir() . '/scpp_discovery_' . bin2hex(random_bytes(6));
mkdir($directory);
mkdir($directory . '/nested');
mkdir($directory . '/nested/deep');
try
{
	// Creation order differs from the required sorted depth-first discovery order.
	file_put_contents($directory . '/z.phs', 'return 3;');
	file_put_contents($directory . '/nested/deep/b.phs', 'return 2;');
	file_put_contents($directory . '/a.phs', 'return 1;');
	file_put_contents($directory . '/nested/ignored.txt', '$');
	symlink($directory, $directory . '/nested/cycle');
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$paths = [];
	foreach (Model::$modules[0]->files as $source) {
		$paths[] = $source->path;
		discovery_check($source->content === '', 'Discovery eagerly read a source');
	}
	discovery_check(count(Model::$modules) === 1, 'Nested directories became modules');
	discovery_check($paths === [$directory . '/a.phs', $directory . '/nested/deep/b.phs', $directory . '/z.phs'], 'Wrong recursive file order');
	$compiler->sync($paths);
	discovery_check(count(Model::$syntax_files) === 3, 'Nested initial files did not parse');

	$new_path = $directory . '/nested/deep/new.phs';
	file_put_contents($new_path, 'return 4;');
	$compiler->sync([$new_path]);
	discovery_check(count(Model::$modules[0]->files) === 4, 'Nested addition was not published in its module');
	file_put_contents($new_path, 'return 5;');
	$compiler->sync([$new_path]);
	discovery_check(Source_Publication::find_source($new_path)->content === 'return 5;', 'Nested edit did not replace its source');
	unlink($new_path);
	$compiler->sync([$new_path]);
	discovery_check(Model::$modules[0]->files[3]->changes === SYNC_DELETED, 'Nested deletion lost its tombstone');
	discovery_check(!Module_Loader::contains_path(Model::$modules[0], $directory . '-other/a.phs'), 'Sibling prefix treated as a descendant');

	// A trailing root separator must still allow a nested source to reach C++ generation.
	unlink($directory . '/a.phs');
	unlink($directory . '/z.phs');
	$compiler->init([$directory . '/']);
	$compiler->exec_cpp();
	discovery_check(count(Model::$cpp_files) === 1, 'Nested source failed C++ generation');
}
finally
{
	unlink($directory . '/nested/cycle');
	foreach (glob($directory . '/nested/deep/*') as $path) {
		unlink($path);
	}
	foreach (glob($directory . '/*.phs') as $path) {
		unlink($path);
	}
	unlink($directory . '/nested/ignored.txt');
	rmdir($directory . '/nested/deep');
	rmdir($directory . '/nested');
	rmdir($directory);
}
echo "Module discovery: recursive order, symlink cycle, nested updates and C++ generation passed\n";
