<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load($argv[1], ['tasks_probe']));
$directory = $argv[2]; mkdir($directory); $path = $directory . '/main.phs';
$valid = '$b=probe_batch();$r=task_join($b);$p=task_progress($b);echo $p->total();';
file_put_contents($path, $valid);
$compiler = new Compiler(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
$initial = CPP_Probe::text(Model::$cpp_files);
$bad = [
 'task_join(1);',
 'task_set_status(probe_batch(),"bad");',
 '$e=probe_error();$e->message="changed";',
 '$e=probe_error();$e->timeout=true;',
 '$e=probe_error();$e->key=3;',
 '$p=task_progress(probe_batch());$p->total(1);',
 '$b=new task_batch();',
];
foreach ($bad as $source) {
 file_put_contents($path,$source); $failed=false;
 try {$compiler->update_cpp([$path]);}catch (\RuntimeException $error){$failed=true;echo $error->getMessage(),"\n";}
 if (!$failed || !Model::$cpp_files->is_empty()) {throw new \LogicException('Invalid task contract accepted or published');}
 file_put_contents($path,$valid);$compiler->update_cpp([$path]);
 if (CPP_Probe::text(Model::$cpp_files)!==$initial){throw new \LogicException('Task recovery differs');}
 $compiler->update_cpp([]);
 if (CPP_Probe::text(Model::$cpp_files)!==$initial){throw new \LogicException('Task no-op differs');}
}
echo "Task rejection/recovery passed\n";
