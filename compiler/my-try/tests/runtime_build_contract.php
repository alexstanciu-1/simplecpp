<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

$schema = ['version'=>1,'module'=>'build','dependencies'=>[], 'functions'=>[], 'cpp'=>[]];
foreach ([['sources'=>['../escape.cpp']], ['libraries'=>['-bad']], ['definitions'=>['NAME']],
	['sources'=>['folder//file.cpp']], ['sources'=>['/file.cpp']],
	['definitions'=>['NAME=']], ['definitions'=>['=1']], ['definitions'=>['NAME=1=2']],
	['definitions'=>['NAME=1','NAME=2']], ['definitions'=>['SCPP_LANGUAGE_TARGET_PHP=0']]] as $invalid) {
	$failed = false;
	try { Runtime_Catalog_Reader::decode(json_encode($schema + ['cpp_build'=>$invalid]), 'bad.json'); }
	catch (\RuntimeException $error) { $failed = true; }
	if (!$failed) { throw new \LogicException('Invalid build contract accepted'); }
}
$input = new runtime_catalog_input();
$input->modules->add('dependency', Runtime_Catalog_Reader::decode(json_encode($schema + ['cpp_build'=>[
	'sources'=>['provider.cpp'],'libraries'=>['dependency'],'definitions'=>['MODE=1']]]), 'dependency.json'));
$input->modules->add('consumer', Runtime_Catalog_Reader::decode(json_encode($schema + ['cpp_build'=>[
	'sources'=>['provider.cpp','consumer.cpp'],'libraries'=>['consumer'],'definitions'=>['MODE=1']]]), 'consumer.json'));
$build = CPP_Runtime_Build::requirements($input);
if ($build->sources !== ['provider.cpp','consumer.cpp'] || $build->libraries !== ['consumer','dependency']
	|| $build->definitions !== ['MODE=1']) { throw new \LogicException('Build merge lost dependency order or identity'); }
$input->modules->add('conflict',Runtime_Catalog_Reader::decode(json_encode($schema + ['cpp_build'=>['definitions'=>['MODE=2']]]),'conflict.json'));
$failed=false;
try { CPP_Runtime_Build::requirements($input); } catch (\RuntimeException $error) { $failed=true; }
if (!$failed) { throw new \LogicException('Cross-module definition conflict accepted'); }
echo "Runtime build contracts passed\n";
