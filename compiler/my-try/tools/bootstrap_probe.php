<?php
// Host-only measured bootstrap: the compiler and project adapter own all processing.
require $argv[1] . '/boot.php';
require $argv[1] . '/host/project.php';
/** Publish the settled C++ bundle so successful bootstrap output can be built. */
function publish_bootstrap_cpp(\scpp\compiler\Compiler $compiler, string $directory): void
{
	$publication = new \scpp\compiler\Output_Publication($directory);
	try { $compiler->cpp($publication); }
	finally { $publication->close(); }
}
$started = hrtime(true);
$stage = 'initialize';
$events = [];
$failure = null;
register_shutdown_function(function () use ($started, &$stage, &$events, &$failure, $argv): void {
	file_put_contents($argv[3], json_encode([
		'elapsed_seconds' => (hrtime(true) - $started) / 1e9,
		'peak_php_bytes' => memory_get_peak_usage(true),
		'last_stage' => $stage, 'events' => $events, 'failure' => $failure,
		'fatal' => error_get_last(),
	], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
});
try {
	$compiler = new \scpp\compiler\Compiler(new \scpp\compiler\cpp_lowering_options(true));
	$project = new \scpp\compiler\Project_Compilation($argv[2]);
	foreach (['initialize', 'sync', 'prepare', 'validate', 'emit'] as $stage) {
		match ($stage) {
			'initialize' => $project->initialize($compiler),
			'sync' => $compiler->sync([]),
			'prepare' => $compiler->prepare(true),
			'validate' => $project->validate(),
			'emit' => publish_bootstrap_cpp($compiler, dirname($argv[3]) . '/published'),
		};
		$events[] = ['stage' => $stage, 'seconds' => (hrtime(true) - $started) / 1e9];
		echo json_encode(end($events)) . "\n";
	}
} catch (Throwable $error) {
	$failure = ['class' => get_class($error), 'message' => $error->getMessage()];
	fwrite(STDERR, $stage . ': ' . $error->getMessage() . "\n");
	exit(1);
}
