<?php
// Host-only fixture adapter for explicit opt-in module selection.
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
$modules = json_decode($argv[2], true, 512, JSON_THROW_ON_ERROR);
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load(
	dirname(__DIR__, 3) . '/runtime/metadata/modules', $modules));
$compiler = new Compiler(new cpp_lowering_options(false));
$compiler->init([$argv[1]]);
$compiler->exec_cpp();
echo CPP_Output::encode(Model::$cpp_files);
