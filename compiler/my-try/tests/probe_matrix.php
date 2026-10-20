<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

// Host-only compiler adapter: preserve failed preparation and empty-artifact proof.
Compiler_Lifecycle::reset();
$module = new module('/probe-matrix', '/probe-matrix', '/probe-matrix');
$file = new file();
$file->path = 'main.phs';
$file->content = file_get_contents($argv[1]);
Source_Registry::add($module, $file);
Model::$modules->add($module->name, $module);
$compiler = new Compiler(new cpp_lowering_options(false));
$compiler->sync(['/probe-matrix/main.phs']);
try {
	$compiler->prepare(true);
}
catch (\RuntimeException $error) {
	echo json_encode(['accepted' => false, 'stage' => 'prepare',
		'empty_artifacts' => Model::$cpp_files->is_empty(), 'diagnostic' => $error->getMessage()]);
	return;
}
$compiler->cpp();
echo json_encode(['accepted' => true, 'cpp' => CPP_Probe::text(Model::$cpp_files)]);
