<?php
namespace scpp\compiler;
// Benchmark-only provider: prevent loop-invariant reads from disappearing after copy elision.
require_once $argv[1] . '/compiler/my-try/boot.php';
$metadata = new runtime_catalog_input();
foreach (Model::$runtime_metadata->modules as $module) { $metadata->modules->add($module->name, $module); }
$schema = ['version'=>1,'module'=>'bench_fence','dependencies'=>['core'],
	'functions'=>[['id'=>'bench.fence','name'=>'bench_fence','parameters'=>[],'returns'=>'void']],
	'cpp'=>[['id'=>'bench.fence','symbol'=>'scpp_bench::fence','header'=>__DIR__ . '/bench_fence.hpp']]];
$metadata->modules->add('bench_fence', Runtime_Catalog_Reader::decode(json_encode($schema), 'bench-fence.json'));
Compiler_Lifecycle::configure_runtime($metadata);
$compiler = new Compiler(new cpp_lowering_options($argv[4] === 'concrete'));
$compiler->init([$argv[2]]);
$compiler->select_entry('main', 'main.phs');
$publication = new Output_Publication($argv[3]);
try { $compiler->exec_cpp($publication, new cpp_lowering_policy(3)); }
finally { $publication->close(); }
