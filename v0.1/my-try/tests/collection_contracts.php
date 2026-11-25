<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load($argv[1], ['collections_probe']));
$directory = $argv[2]; mkdir($directory); $path = $directory . '/main.phs';
$valid = '$v vector<int> = [1,2];$r=collection_map($v,fn(int $x):int=>$x+1);echo $r[0];';
file_put_contents($path, $valid);
$compiler = new Compiler(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
$initial = CPP_Probe::text(Model::$cpp_files);
$bad = [
 '$v vector<int> = [];collection_map($v,fn(string $x):int=>1);',
 '$v vector<int> = [];collection_map($v,fn():int=>1);',
 '$v vector<int> = [];collection_map($v,fn(int $x,int $y):int=>1);',
 '$v vector<int> = [];collection_filter($v,fn(int $x):int=>1);',
 '$v vector<int> = [];keyed_map($v,fn(int $x):int=>$x);',
 '$v hash<int> = {};sequence_filter($v,fn(int $x):bool=>true);',
 '$v vector<int> = [];$n=1;collection_map($v,function(int $x) use (&$n):int{return $n;});',
 '$v=probe_mixed();collection_map($v,fn(dynamic $x):dynamic=>$x);',
 '$v=probe_dynamic();collection_map($v,fn(dynamic $x):int=>1);',
];
foreach ($bad as $source) {
 file_put_contents($path,$source); $failed=false;
 try {$compiler->update_cpp([$path]);}catch (\RuntimeException $error){$failed=true;echo $error->getMessage(),"\n";}
 if (!$failed || !Model::$cpp_files->is_empty()) {throw new \LogicException('Invalid collection callback accepted or published');}
 file_put_contents($path,$valid);$compiler->update_cpp([$path]);
 if (CPP_Probe::text(Model::$cpp_files)!==$initial){throw new \LogicException('Collection recovery differs');}
 $compiler->update_cpp([]);
 if (CPP_Probe::text(Model::$cpp_files)!==$initial){throw new \LogicException('Collection no-op differs');}
}
echo "Collection rejection/recovery passed\n";
