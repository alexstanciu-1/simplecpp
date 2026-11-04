<?php

declare(strict_types=1);
namespace scpp\compiler {
	/**
	 * Observe directory access to prove dependencies are never scanned.
	 *
	 * @return string[]|false
	 */
	function scandir(string $path): array|false /** list<string> */
	{
		$GLOBALS['scanned'][$path] = true;
		return \scandir($path);
	}
}
namespace
{
	require_once __DIR__ . '/../boot.php';
	use scpp\compiler\Project_Compiler;

	function verify(bool $ok, string $message): void
	{
		if (!$ok) {
			throw new RuntimeException($message);
		}
	}

	$root = sys_get_temp_dir() . '/scpp-project-owner-' . bin2hex(random_bytes(8));
	mkdir($root);
	try
	{
		foreach (['app', 'app/lib', 'runtime'] as $directory) {
			mkdir($root . '/' . $directory);
		}
		file_put_contents($root . '/app/module.json', '{"dependencies":{"lib":{"path":"lib"}}}');
		file_put_contents($root . '/app/main.cpp', 'return 8;');
		// Explicit missing sources and entries in dependency manifests must not trigger source validation.
		file_put_contents($root . '/app/lib/module.json',
			'{"sources":["absent.cpp"],"entry":"absent.cpp","artifacts":["missing.a"]}');
		file_put_contents($root . '/app/lib/invalid.cpp', 'not valid source');
		file_put_contents($root . '/runtime/module.json', '{"type":"c++","artifacts":["missing-runtime.a"]}');
		file_put_contents($root . '/runtime/module.sources.json', 'manually preserved');
		$compiler = new Project_Compiler($root . '/runtime');
		$facts = dirname(__DIR__, 2) . '/build/clang/native-layout/facts.json';
		ob_start();
		try {
			$first = $compiler->run($root . '/app', $facts, $root . '/out');
			$stdout = ob_get_contents();
		}
		finally {
			ob_end_clean();
		}
		verify(($stdout === '') && ($first['rebuilt']) && ($first['sources'] === 1),
			'Coordinator output or membership');
		foreach (array_keys($GLOBALS['scanned']) as $path) {
			verify((!str_starts_with($path, $root . '/app/lib')) && (!str_starts_with($path, $root . '/runtime')),
				'Dependency scanned');
		}
		$inventory = $root . '/app/module.sources.json';
		touch($inventory, 1234567890);
		clearstatcache();
		$inode = fileinode($inventory);
		verify(!$compiler->run($root . '/app', $facts, $root . '/out')['rebuilt'], 'Unchanged run rebuilt');
		clearstatcache();
		verify((filemtime($inventory) === 1234567890) && (fileinode($inventory) === $inode),
			'Unchanged inventory replaced');
		verify(file_get_contents($root . '/runtime/module.sources.json') === 'manually preserved',
			'Dependency inventory overwritten');
		verify(!file_exists($root . '/app/lib/module.sources.json'), 'Dependency inventory created');
		file_put_contents($root . '/app/lib/invalid.cpp', 'changed dependency contents');
		file_put_contents($root . '/runtime/missing-runtime.a', 'not a valid archive');
		verify(!$compiler->run($root . '/app', $facts, $root . '/out')['rebuilt'],
			'Dependency edit triggered checking');
		file_put_contents($root . '/app/extra.cpp', '// extra');
		verify($compiler->run($root . '/app', $facts, $root . '/out')['sources'] === 2,
			'Source addition not reflected');
		verify(str_contains(file_get_contents($inventory), 'extra.cpp'), 'Inventory addition missing');
		unlink($root . '/app/extra.cpp');
		$compiler->run($root . '/app', $facts, $root . '/out');
		verify(!str_contains(file_get_contents($inventory), 'extra.cpp'), 'Inventory removal missing');
		$bundle = json_decode(file_get_contents($root . '/out/manifest.json'), true);
		verify((count($bundle['link_inputs']) === 2) && (!isset($bundle['native_sources'])),
			'Prebuilt dependency inputs missing');
	}
	finally
	{
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($iterator as $file)
		{
			if ($file->isDir()) {
				rmdir($file->getPathname());
			}
			else {
				unlink($file->getPathname());
			}
		}
		rmdir($root);
	}
	echo "Project coordinator, app-only compilation, unchecked artifacts and stable inventories passed\n";
}
