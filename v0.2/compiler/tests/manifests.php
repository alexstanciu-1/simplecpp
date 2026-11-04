<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
use scpp\compiler\{Module_Loader, Program_Frontend, Cpp_Program, Cpp_Bundle, entry_target};

function verify(bool $ok, string $message): void
{
	if (!$ok) {
		throw new RuntimeException($message);
	}
}

/** Check that invalid manifest input fails with the expected diagnostic. */
function rejects(Closure $action, string $message): void
{
	try {
		$action();
	}
	catch (RuntimeException $error) {
		verify(str_contains($error->getMessage(), $message), $error->getMessage());
		return;
	}
	throw new LogicException('Expected rejection: ' . $message);
}

/**
 * Fixture manifests remain readable on failure; all generated state lives in the temporary tree.
 */
function manifest(string $root, string $path, string $json): void
{
	file_put_contents($root . '/' . $path . '/module.json', $json);
}

$root = sys_get_temp_dir() . '/scpp-manifests-' . bin2hex(random_bytes(8));
mkdir($root);
try
{
	foreach (['app', 'lib', 'runtime', 'runtime/core', 'runtime/json', 'app/build', 'app/generated', 'app/nested'] as $dir) {
		mkdir($root . '/' . $dir);
	}
	manifest($root, 'app', '{"dependencies":{"lib":{"path":"../lib"},"runtime":{"modules":["json","json"]}}}');
	manifest($root, 'lib', '{"dependencies":{"runtime":{"modules":["json"]}}}');
	manifest($root, 'runtime',
		'{"type":"c++","sources":[],"modules":{"core":"core","json":"json"},"defaults":["core"]}');
	manifest($root, 'runtime/core',
		'{"name":"core","type":"c++","artifacts":["missing.a"],"dependencies":{"runtime":{}}}');
	manifest($root, 'runtime/json', '{"name":"json","type":"c++","dependencies":{"runtime":{}}}');
	manifest($root, 'app/nested', '{}');
	file_put_contents($root . '/app/main.cpp', 'echo "module"; return 8;');
	file_put_contents($root . '/lib/functions.php', 'function helper(): void { echo "module"; }');
	file_put_contents($root . '/runtime/core/native.cpp', '#include <cstdint>');
	file_put_contents($root . '/runtime/json/native.cpp', '#include <string>');
	foreach (['build', 'generated', 'nested'] as $dir) {
		file_put_contents($root . '/app/' . $dir . '/invalid.cpp', '#not our language');
	}
	$loader = new Module_Loader($root . '/runtime');
	$session = $loader->load($root . '/app');
	verify(count($session->modules) === 5, 'Defaults and repeated activation');
	verify(count($session->entry->sources) === 1, 'Discovery exclusions');
	verify($session->entry->entry->path === 'main.cpp', 'Default entry');
	verify($session->entry->dependencies['runtime'] === $session->modules['lib']->dependencies['runtime'],
		'Shared dependency identity');
	verify(isset($session->modules['json']), 'Explicit activation');
	$program = (new Program_Frontend())->prepare($session, new entry_target(31));
	verify(count($program->files) === 1, 'Dependency files entered parser');
	verify(($session->modules['core']->sources === []) && ($session->modules['lib']->sources === []),
		'Dependency source inventory loaded');
	$output = Cpp_Program::emit($program);
	verify(($output->native_sources === []) && (count($output->link_inputs) === 1), 'Prebuilt artifact inventory');
	Cpp_Bundle::write($root . '/bundle', $output, []);
	$bundle = json_decode(file_get_contents($root . '/bundle/manifest.json'), true, flags: \JSON_THROW_ON_ERROR);
	verify($bundle['link_inputs'] === $output->link_inputs, 'Link inputs missing from bundle');
	$inventory = $root . '/app/module.sources.json';
	$data = json_decode(file_get_contents($inventory), true, flags: \JSON_THROW_ON_ERROR);
	verify($data['modules']['app'] === ['main.cpp' => 'cpp'], 'Generated inventory');
	file_put_contents($inventory, 'not a cache');
	verify(count($loader->load($root . '/app')->entry->sources) === 1, 'Inventory was read as input');
	// Multiple defaults may independently depend on their catalog parent, without sibling cycles.
	manifest($root, 'app', '{}');
	verify(!isset($loader->load($root . '/app')->modules['json']), 'Nondefault module activated implicitly');
	manifest($root, 'runtime',
		'{"type":"c++","sources":[],"modules":{"core":"core","json":"json"},"defaults":["core","json"]}');
	verify(count($loader->load($root . '/app')->modules) === 4, 'Sibling defaults created a false dependency cycle');
	// Explicit selection replaces discovery, and arbitrary extensions can carry explicit compiler flavors.
	file_put_contents($root . '/app/entry.scpp', 'return 8;');
	manifest($root, 'app', '{"entry":"entry.scpp","sources":{"entry.scpp":"cpp"}}');
	verify($loader->load($root . '/app')->entry->entry->path === 'entry.scpp', 'Explicit sources/entry');
	manifest($root, 'app', '{}');
	file_put_contents($root . '/app/main.php', 'return 8;');
	rejects(fn() => $loader->load($root . '/app'), 'exactly one');
	unlink($root . '/app/main.php');
	manifest($root, 'app', '{"sources":[]}');
	rejects(fn() => $loader->load($root . '/app'), 'exactly one');
	manifest($root, 'app', '{"dependencies":{"runtime":{"modules":["missing"]}}}');
	rejects(fn() => $loader->load($root . '/app'), 'Unknown submodule');
	manifest($root, 'app', '{"dependencies":{"lib":{"path":"../lib","config":{}}}}');
	rejects(fn() => $loader->load($root . '/app'), 'Unsupported field config');
	manifest($root, 'app', '{"dependencies":{"lib":{"path":"../lib"}}}');
	manifest($root, 'lib', '{"dependencies":{"app":{"path":"../app"}}}');
	rejects(fn() => $loader->load($root . '/app'), 'cycle');
	manifest($root, 'lib', '{"name":"app"}');
	rejects(fn() => $loader->load($root . '/app'), 'Conflicting module name');
	manifest($root, 'lib', '{"name":"wrong"}');
	rejects(fn() => $loader->load($root . '/app'), 'does not match');
	manifest($root, 'lib', '{}');
	manifest($root, 'app', '{"entry":"../lib/functions.php","sources":["../lib/functions.php"]}');
	rejects(fn() => $loader->load($root . '/app'), 'canonical relative');
	manifest($root, 'app', '{"sources":["missing.cpp"]}');
	rejects(fn() => $loader->load($root . '/app'), 'exactly one');
	manifest($root, 'app', '{"entry":"missing.cpp","sources":["missing.cpp"]}');
	rejects(fn() => $loader->load($root . '/app'), 'must exist');
	manifest($root, 'app', '{"dependencies":[]}');
	rejects(fn() => $loader->load($root . '/app'), 'must be objects');
	manifest($root, 'app', '{invalid');
	rejects(fn() => $loader->load($root . '/app'), 'Invalid module JSON');
	manifest($root, 'app', '{}');
	unlink($inventory);
	symlink($root . '/lib/module.json', $inventory);
	rejects(fn() => $loader->load($root . '/app'), 'symlink');
	verify(file_get_contents($root . '/lib/module.json') === '{}', 'Inventory overwrote symlink target');
}
finally
{
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,
			FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
	foreach ($iterator as $file)
	{
		if ($file->isDir() && (!$file->isLink())) {
			rmdir($file->getPathname());
		}
		else {
			unlink($file->getPathname());
		}
	}
	rmdir($root);
}
echo "Manifest defaults, discovery, activation, native separation, publication and rejection passed\n";
