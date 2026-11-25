<?php

declare(strict_types=1);

require_once __DIR__ . '/../boot.php';

use scpp\compiler\{Cpp_Bundle, cpp_program_output, flavor, Program_Loader, Project_Changes};

[$script, $mode, $root, $bundle] = $argv;
$output = new cpp_program_output('app/main.cpp', ['app/main.cpp' => 'entry.cpp'], ['entry.cpp' => '// proof']);
$manifest = $bundle . '/manifest.json';
if ($mode === 'capture') {
	$files = ['main.cpp' => flavor::cpp];
	if (is_file($root . '/sub/helper.php')) $files['sub/helper.php'] = flavor::php;
	$session = Program_Loader::modules(['app' => ['root' => $root, 'entry' => 'main.cpp',
		'sources' => $files, 'dependencies' => []]], 'app');
	$state = Project_Changes::capture($session, $root . '/facts.json', $bundle);
	Cpp_Bundle::write($bundle, $output, [], $state);
	// Inspect the actual temporary projection without widening the scanner API.
	$index = new ReflectionMethod(Project_Changes::class, 'sources');
	echo json_encode($index->invoke(null, $state), JSON_THROW_ON_ERROR);
} elseif ($mode === 'compare') {
	$expected = file_get_contents($manifest);
	$state = json_decode($expected, true, flags: JSON_THROW_ON_ERROR)['build']['inputs'];
	$state['modules']['app']['sources'] = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
	Cpp_Bundle::write($bundle, $output, [], $state);
	if (file_get_contents($manifest) !== $expected) throw new RuntimeException('Persisted input bytes differ');
} else {
	throw new InvalidArgumentException($mode);
}
