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
mkdir($directory . '/other');
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
	foreach (Model::$modules[$directory]->sources as $record) {
		$source = $record->file;
		$paths[] = $source->path;
		discovery_check($source->content === '', 'Discovery eagerly read a source');
	}
	discovery_check(count(Model::$modules) === 1, 'Nested directories became modules');
	discovery_check($paths === ['a.phs', 'nested/deep/b.phs', 'z.phs'], 'Wrong recursive file order');
	$compiler->sync([]);
	discovery_check(count(Model::syntax_files()) === 3, 'Nested initial files did not parse');

	$new_path = $directory . '/nested/deep/new.phs';
	file_put_contents($new_path, 'return 4;');
	$compiler->sync([$new_path]);
	$stable = Source_Registry::find($new_path);
	$alias_path = $directory . '/nested/cycle/nested/deep/new.phs';
	discovery_check(Source_Registry::normalize($alias_path) === $new_path, 'Parent alias did not select canonical source');
	discovery_check(count(Model::$modules[$directory]->sources) === 4, 'Nested addition was not published in its module');
	file_put_contents($new_path, 'return 5;');
	$compiler->sync([$new_path]);
	discovery_check(Source_Publication::find_source($new_path)->content === 'return 5;', 'Nested edit did not replace its source');
	unlink($new_path);
	$compiler->sync([$new_path]);
	discovery_check(Model::$modules[$directory]->sources['nested/deep/new.phs']->file->changes === SYNC_DELETED, 'Nested deletion lost its tombstone');
	discovery_check(Source_Registry::find($new_path) === $stable, 'Edit or deletion replaced stable source membership');
	discovery_check(Source_Registry::normalize($alias_path) === $new_path, 'Missing source lost parent alias normalization');
	discovery_check(!Module_Loader::contains_path(Model::$modules[$directory], $directory . '-other/a.phs'), 'Sibling prefix treated as a descendant');

	// Canonical roots must have exactly one owner, regardless of requested order or spelling.
	foreach ([[$directory, $directory . '/nested'], [$directory . '/nested', $directory], [$directory, $directory . '/.']] as $roots)
	{
		try {
			$compiler->init($roots);
			throw new \RuntimeException('Overlapping roots accepted');
		}
		catch (\LogicException $expected) {
			discovery_check(str_contains($expected->getMessage(), 'Overlapping'), 'Unexpected overlap error');
		}
	}

	// A trailing root separator must still allow a nested source to reach C++ generation.
	unlink($directory . '/a.phs');
	unlink($directory . '/z.phs');
	$compiler->init([$directory . '/']);
	$compiler->exec_cpp();
	discovery_check(count(Model::$cpp_files) === 1, 'Nested source failed C++ generation');

	// Independent modules retain module order even when notifications publish in reverse order.
	$other_path = $directory . '/other/c.phs';
	$deep_path = $directory . '/nested/deep/b.phs';
	file_put_contents($other_path, 'return 8;');
	$compiler->init([$directory . '/nested/deep', $directory . '/other']);
	$compiler->sync([$other_path, $deep_path]);
	$parsed /** Storage<parsed_file> */ = Model::syntax_files();
	discovery_check((count($parsed) === 2) && ($parsed[0]->source_file()->path === 'b.phs') && ($parsed[1]->source_file()->path === 'c.phs'), 'Completion order replaced module/source order');
	discovery_check((Source_Registry::find($deep_path)->module === Model::$modules[$directory . '/nested/deep']) && (Source_Registry::find($other_path)->module === Model::$modules[$directory . '/other']), 'Disjoint sources lost exact module ownership');
}
finally
{
	unlink($directory . '/nested/cycle');
	foreach (glob($directory . '/other/*') as $path) {
		unlink($path);
	}
	rmdir($directory . '/other');
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
