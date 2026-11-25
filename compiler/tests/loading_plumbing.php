<?php

declare(strict_types=1);

namespace scpp\compiler
{
	/**
	 * Count directory reads at the PHP filesystem boundary, without production instrumentation.
	 *
	 * @return string[]|false
	 */
	function scandir(string $directory): array|false /** list<string> */
	{
		$GLOBALS['directory_reads'][$directory] = ($GLOBALS['directory_reads'][$directory] ?? 0) + 1;
		return \scandir($directory);
	}

	/** Exercise an unresolved directory path independently of the test runner's filesystem permissions. */
	function realpath(string $path): string|false
	{
		return $path === ($GLOBALS['unresolved_path'] ?? null) ? false : \realpath($path);
	}

	/** Count reads of the bundle manifest across baseline, reuse and publication. @param resource $handle */
	function stream_get_contents($handle): string|false
	{
		$path = stream_get_meta_data($handle)['uri'];
		$GLOBALS['file_reads'][$path] = ($GLOBALS['file_reads'][$path] ?? 0) + 1;
		return \stream_get_contents($handle);
	}
}

namespace
{
	require_once __DIR__ . '/../boot.php';
	use scpp\compiler\{Program_Loader, Project_Changes, Cpp_Bundle, cpp_program_output, flavor};

	function verify(bool $ok, string $message): void
	{
		if (!$ok) {
			throw new RuntimeException($message);
		}
	}

	$root = sys_get_temp_dir() . '/scpp-plumbing-' . bin2hex(random_bytes(8));
	mkdir($root);
	try
	{
		file_put_contents($root . '/main.cpp', 'return 8;');
		file_put_contents($root . '/helper.cpp', '// helper');
		file_put_contents($root . '/shared.hpp', '// header');
		file_put_contents($root . '/facts.json', '{}');
		$session = Program_Loader::modules([
				'app' => ['root' => $root, 'entry' => 'main.cpp', 'sources' => ['main.cpp' => flavor::cpp,
						'helper.cpp' => flavor::cpp], 'dependencies' => ['lib']],
				'lib' => ['root' => $root, 'sources' => [], 'dependencies' => []],
			], 'app');
		mkdir($root . '/nested');
		file_put_contents($root . '/nested/input.hpp', '// tracked input');
		$GLOBALS['unresolved_path'] = $root . '/nested';
		verify(realpath($root . '/out') === false, 'Expected absent output on first capture');
		$state = Project_Changes::capture($session, $root . '/facts.json', $root . '/out');
		verify(isset($state['other_inputs'][$root . '/nested/input.hpp']), 'Unresolved input mistaken for absent output');
		unset($GLOBALS['unresolved_path']);
		verify($GLOBALS['directory_reads'][$root] === 1, 'Shared root scanned more than once');
		verify((!isset($state['other_inputs'][$root . '/main.cpp']))
			&& (!isset($state['other_inputs'][$root . '/helper.cpp'])),
			'Source metadata duplicated in other inputs');
		verify(isset($state['other_inputs'][$root . '/shared.hpp']), 'Shared header tracking lost');
		verify($state['modules']['app']['sources']['main.cpp']['size'] === 9, 'Source record incomplete');
		verify(Project_Changes::valid(json_decode(json_encode($state), true)),
			'Saved state does not round trip');
		verify(!Project_Changes::valid(['version' => 2]), 'Old schema accepted');
		$output = new cpp_program_output('app/main.cpp', ['app/main.cpp' => 'entry.cpp'], ['entry.cpp' => '// generated']);
		Cpp_Bundle::write($root . '/out', $output, [], $state);
		$manifest = $root . '/out/manifest.json';
		$previous = Cpp_Bundle::with_reader($root . '/out',
			static fn(bool $present) => Cpp_Bundle::snapshot($root . '/out'));
		verify(Cpp_Bundle::baseline($previous->manifest) === $state, 'Baseline changed');
		verify(Cpp_Bundle::reuse($root . '/out', $state, $previous->manifest) !== null, 'Unchanged bundle not reusable');
		Cpp_Bundle::write($root . '/out', $output, [], $state, $previous);
		verify($GLOBALS['file_reads'][$manifest] === 1, 'Manifest reread during reuse or publication');
		try {
			Cpp_Bundle::write($root . '/out', $output, [], $state, $previous);
			throw new \LogicException('Stale snapshot accepted after manifest replacement');
		}
		catch (\RuntimeException $error) {
			verify(str_contains($error->getMessage(), 'Program bundle changed'), $error->getMessage());
		}
		verify($GLOBALS['file_reads'][$manifest] === 1, 'Stale snapshot check reread the manifest');
		verify(Project_Changes::capture($session, $root . '/facts.json', $root . '/out') === $state,
			'Output affected inputs');
		verify($GLOBALS['directory_reads'][$root] === 2, 'Final capture reused stale scan state');
		file_put_contents($root . '/shared.hpp', '// changed header');
		verify(Project_Changes::capture($session, $root . '/facts.json', $root . '/out') !== $state,
			'Shared header change missed');
	}
	finally
	{
		if (is_dir($root . '/out')) {
			foreach (new \FilesystemIterator($root . '/out', \FilesystemIterator::SKIP_DOTS) as $path) {
				unlink($path->getPathname());
			}
			rmdir($root . '/out');
		}
		unlink($root . '/nested/input.hpp');
		rmdir($root . '/nested');
		foreach (glob($root . '/*') as $path) {
			unlink($path);
		}
		rmdir($root);
	}
	echo "Single manifest read, normalized source state and per-check root scans passed\n";
}
