<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
function platform_check(bool $ok,string $why):void { if(!$ok){throw new \LogicException($why);} }
function platform_compile(string $source):string {
 Compiler_Lifecycle::reset();
 $module=new module('/platform','/platform','/platform');
 $file=new file();$file->path='main.phs';$file->content=$source;
 Source_Registry::add($module,$file);Model::$modules->add($module->name,$module);
 (new Compiler(new cpp_lowering_options(false)))->exec_cpp();return CPP_Probe::text(Model::$cpp_files);
}
$out=$argv[1];$root=dirname(__DIR__,3);
// Metadata exposures are identity indexes and are not tied to the php spelling.
$core=file_get_contents($root.'/runtime/metadata/modules/core.json');
$custom=new runtime_catalog_input();$custom->modules->add('core',Runtime_Catalog_Reader::decode(str_replace('"qualified_aliases":["php"]','"qualified_aliases":["clock_probe"]',$core),'custom.json'));
Compiler_Lifecycle::configure_runtime($custom);
$metadata=[];
foreach(['["php","php"]','["self"]','["parent"]','["static"]','["9bad"]','["a::b"]','["a\\\\b"]','[""]'] as $aliases){
 $bad=new runtime_catalog_input();$error='';
 try{$bad->modules->add('core',Runtime_Catalog_Reader::decode(str_replace('["php"]',$aliases,$core),'invalid.json'));Compiler_Lifecycle::configure_runtime($bad);}catch(\RuntimeException $e){$error=$e->getMessage();}
 platform_check($error!==''&&Model::$runtime_metadata===$custom,'Invalid alias configuration published');$metadata[]=$error;
}
$bad=new runtime_catalog_input();$error='';
try{
 $bad->modules->add('core',Runtime_Catalog_Reader::decode(str_replace('"member":{"owner":"Exception","dispatch":"static"}', '"qualified_aliases":["php"],"member":{"owner":"Exception","dispatch":"static"}',$core),'member.json'));
 Compiler_Lifecycle::configure_runtime($bad);
}catch(\RuntimeException $e){$error=$e->getMessage();}
platform_check(str_contains($error,'members cannot expose')&&Model::$runtime_metadata===$custom,'Member alias admitted');$metadata[]=$error;
file_put_contents($out.'/custom.cpp',platform_compile('$n float=clock_probe::microtime(true);if($n<=0.0){return 1;}'));
$targets=Model::$runtime_callables->qualified_named('clock_probe','microtime');
platform_check(count($targets)===2&&$targets[0]===Model::$runtime_callables->function_definition('core.microtime.0'),'Alias copied identity');
platform_check(Scope_Lookup::types(Model::$language_scope,'clock_probe')===[],'Alias invented a type');
file_put_contents($out.'/metadata.json',json_encode($metadata,JSON_PRETTY_PRINT)."\n");
echo "Alias metadata passed\n";
