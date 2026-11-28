<?php

declare(strict_types=1);

use scpp\compiler\{entry_target, Incremental_Project, Native_Build};

require_once __DIR__ . '/../../compiler/boot.php';
require_once __DIR__ . '/../../compiler/host/native_build.php';

[$script, $application, $work, $configuration] = $argv;
$bundle = $work . '/bundle';
$project = new Incremental_Project($application, $bundle, new entry_target(31));
while (($line = fgets(STDIN)) !== false) {
	try {
		if (trim($line) !== 'build') throw new RuntimeException('Expected build request');
		clearstatcache();
		$config = $configuration !== '' ? Native_Build::configuration($configuration) : [
			'cxx' => 'clang++', 'flags' => ['-std=c++23', '-stdlib=libc++', '-I', dirname(__DIR__, 2) . '/runtime/generated/public'],
		];
		// The resident frontend uses whole-second mtimes. Editors can replace bytes
		// without changing either stamp. Reopen this session only on an actual collision.
		foreach ($project->session()?->entry->sources ?? [] as $unit) {
			$path = $unit->module->root . '/' . $unit->path;
			$stat = @stat($path);
			if ($stat !== false && $stat['mtime'] === $unit->mtime && $stat['size'] === $unit->size
				&& $unit->ast !== null && @file_get_contents($path) !== $unit->ast->source()->content) {
				$project = new Incremental_Project($application, $bundle, new entry_target(31));
				break;
			}
		}
		$project->run();
		$result = Native_Build::run($bundle, $work . '/native', $config);
		if ($result['exit_code'] !== 0) throw new RuntimeException($result['stdout'] . $result['stderr']);
		$id = hash_file('sha256', $result['executable']);
		if ($id === false) throw new RuntimeException('Cannot identify linked executable');
		$response = ['ready' => $id];
	}
	catch (Throwable $error) {
		$response = ['error' => substr($error->getMessage(), 0, 8192)];
	}
	echo json_encode($response, JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR) . "\n";
	flush();
}
