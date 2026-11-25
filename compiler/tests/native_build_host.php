<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/boot.php';
require_once dirname(__DIR__) . '/host/native_build.php';

$project = null;

// Test-only retained host: the production API never owns a wire protocol or spawns this helper.
while (($line = fgets(STDIN)) !== false)
{
	try
	{
		$request = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
		chdir($request['cwd']);
		foreach ($request['environment'] ?? [] as $key => $value) {
			putenv($value === null ? $key : $key . '=' . $value);
		}
		$start = hrtime(true);
		if ($request['action'] === 'lookup') {
			$result = \scpp\compiler\Qualification_Cache::lookup($request['cxx'], $request['flags'],
				$request['link_flags'] ?? [], $request['cache']);
		}
		elseif ($request['action'] === 'incremental') {
			$project ??= new \scpp\compiler\Incremental_Project($request['app'], $request['bundle'],
				\scpp\compiler\Native_Target::load($request['facts']), $request['runtime']);
			$result = $project->run();
			$result['native'] = \scpp\compiler\Native_Build::run($request['bundle'], $request['native'], $request['config']);
		}
		elseif ($request['action'] === 'project') {
			$result = (new \scpp\compiler\Project_Compiler($request['runtime']))->run(
				$request['app'], $request['facts'], $request['bundle']);
			$result['native'] = \scpp\compiler\Native_Build::run($request['bundle'], $request['native'], $request['config']);
		}
		else {
			$result = \scpp\compiler\Native_Build::run($request['bundle'], $request['native'], $request['config'],
				$request['action'] === 'publish_graph');
		}
		echo json_encode(['result' => $result, 'ms' => (hrtime(true) - $start) / 1e6], JSON_THROW_ON_ERROR), "\n";
	}
	catch (Throwable $error) {
		echo json_encode(['error' => $error->getMessage()], JSON_THROW_ON_ERROR), "\n";
	}
}
