<?php
// Host fixture adapter: explicit opt-in modules, production default lowering.
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
$modules = ['curl', 'filesystem', 'json', 'strings'];
if (isset($argv[2])) { $modules[] = 'curl_probe'; }
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load(
	$argv[2] ?? dirname(__DIR__, 3) . '/runtime/metadata/modules', $modules));
$publication = new Output_Publication($argv[1] . '/published');
try {
	$compiler = new Compiler();
	$compiler->init([$argv[1] . '/source']);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp($publication);
} finally { $publication->close(); }
