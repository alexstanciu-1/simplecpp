<?php

declare(strict_types=1);
namespace scpp\compiler;
require_once __DIR__ . '/../../boot.php';
require_once __DIR__ . '/inspect.php';

/**
 * One measured invocation; diagnostics are scalarized before inspecting retained compiler state.
 * @return mixed[]
 */
function equivalence_run(Incremental_Project $project, Retention_Inspection $probe): array /** array<string,mixed> */
{
	$start = hrtime(true);
	$result = null;
	$errors = [];
	try {
		$result = $project->run();
	}
	catch (diagnostic|diagnostic_group $error) {
		foreach ($error instanceof diagnostic_group ? $error->diagnostics : [$error] as $item) {
			$errors[] = ['path' => $item->source->path, 'offset' => $item->offset, 'message' => $item->getMessage()];
		}
		unset($item, $error);
	}
	$php_ms = (hrtime(true) - $start) / 1e6;
	gc_collect_cycles();
	$memory = memory_get_usage(false);
	$session = $project->session();
	$symbols = [];
	$retention = null;
	if ($session !== null)
	{
		$retention = $probe->snapshot($project);
		if (($retention['session_depth'] !== 1) || ($retention['retired_ast_roots'] !== 0)
			|| ($retention['stale_watchers'] !== 0) || ($retention['pending_notifications'] !== 0)) {
			throw new \LogicException('Unstable retained state');
		}
		$families = [compilation_session::class, ast_file_node::class, ast_function_body::class, token_list::class, source_file::class];
		if ($probe->survivors() !== array_sum(array_intersect_key($retention['class_counts'], array_flip($families)))) {
			throw new \LogicException('Retired objects survived collection');
		}
		foreach ($session->scope->heads() as $key => $head) {
			$chain = [];
			for ($node = $head; $node !== null; $node = $node->duplicate_element()) {
				$chain[] = ['kind' => get_class($node), 'name' => $node->name(), 'path' => $node->source()->path,
					'start' => $node->start, 'end' => $node->end, 'syntax' => $node->source_text()];
			}
			$symbols[$key] = $chain;
		}
		ksort($symbols);
	}
	return ['ok' => $errors === [], 'diagnostics' => $errors, 'symbols' => $symbols,
		'php_ms' => $php_ms, 'php_memory_bytes' => $memory, 'work' => $result, 'retention' => $retention];
}

$project = new Incremental_Project($argv[1], $argv[2], new entry_target(31));
$probe = new Retention_Inspection();
while (fgets(STDIN) !== false) {
	echo json_encode(equivalence_run($project, $probe), \JSON_THROW_ON_ERROR) . "\n";
	flush();
}
unset($project);
gc_collect_cycles();
if ($probe->survivors() !== 0) {
	throw new \LogicException('Project release left observed survivors');
}
