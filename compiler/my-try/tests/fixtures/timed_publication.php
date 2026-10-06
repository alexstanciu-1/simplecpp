<?php
namespace scpp\compiler;

// Host-only phase probe: use existing coordinator boundaries, not a second pipeline.
$started = hrtime(true);
require_once dirname(__DIR__, 2) . '/boot.php';
$times = ['bootstrap' => (hrtime(true) - $started) / 1e9];
$publication = null;
try
{
	$started = hrtime(true);
	$publication = new Output_Publication($argv[2]);
	$compiler = new Compiler();
	$compiler->init([$argv[1]]);
	$compiler->select_entry('main', 'main.phs');
	$times['initialization'] = (hrtime(true) - $started) / 1e9;
	$started = hrtime(true);
	$compiler->sync([]);
	$times['frontend_sync'] = (hrtime(true) - $started) / 1e9;
	$started = hrtime(true);
	$compiler->prepare(true);
	$times['preparation'] = (hrtime(true) - $started) / 1e9;
	$started = hrtime(true);
	$compiler->cpp($publication);
	$times['emission_publication'] = (hrtime(true) - $started) / 1e9;
	echo json_encode($times, JSON_THROW_ON_ERROR), "\n";
}
finally
{
	if ($publication !== null) {
		$publication->close();
	}
}
