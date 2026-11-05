<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
use scpp\compiler\{Incremental_Project, entry_target, prepared_program, prepared_entry};

// Test-only line protocol keeps the real compiler objects resident while Python edits files and invokes Ninja.
$project = new Incremental_Project($argv[1], $argv[2], new entry_target(31));
$handle = null;
while (($line = fgets(STDIN)) !== false) {
	try {
		$request = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
		if ($request === 'check_stale') {
			try {
				$handle->require_prepared();
				throw new RuntimeException('Old emission handle accepted');
			}
			catch (LogicException) {
			}
			$response = ['stale_rejected' => true];
		}
		else {
			$start = hrtime(true);
			$response = $project->run();
			$response['php_ms'] = (hrtime(true) - $start) / 1e6;
			$session = $project->session();
			if ($handle === null) {
				$files = $session->files();
				$entry = $session->entry_key();
				$handle = new prepared_program($entry, $files, new prepared_entry($files[$entry], new entry_target(31)), $session);
			}
			$response['source_count'] = count($session->files());
		}
	}
	catch (Throwable $error) {
		$response = ['error' => $error->getMessage()];
	}
	echo json_encode($response, JSON_THROW_ON_ERROR) . "\n";
	flush();
}
