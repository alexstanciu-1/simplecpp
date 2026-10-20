<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

/** Exercise registered contracts through the normal source/preparation pipeline. */
function copy_requirement_compile(string $source): void
{
	Compiler_Lifecycle::reset();
	$module = new module('/copy_requirement', '/copy_requirement', '/copy_requirement');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
}

function copy_requirement_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

$parameter = ['name'=>'value', 'type'=>['parameter'=>'T'], 'passing'=>'value', 'requires_copy'=>true];
$function = ['id'=>'copy.checked', 'name'=>'checked', 'type_parameters'=>['T'], 'parameters'=>[$parameter], 'returns'=>'void'];
$schema = ['version'=>1, 'module'=>'copy_probe', 'dependencies'=>['core'], 'functions'=>[$function],
	'cpp'=>[['id'=>'copy.checked', 'symbol'=>'copy_probe::consume', 'header'=>__DIR__.'/runtime_copy_support.hpp']]];
$invalid = [];
foreach (['reference', 'observe', 'variadic', 'non_boolean', 'callable'] as $case)
{
	$bad = $schema;
	if (($case === 'reference') || ($case === 'observe')) {
		$bad['functions'][0]['parameters'][0]['passing'] = $case;
	}
	elseif ($case === 'variadic') {
		$bad['functions'][0]['parameters'][0]['variadic'] = true;
	}
	elseif ($case === 'non_boolean') {
		$bad['functions'][0]['parameters'][0]['requires_copy'] = 'yes';
	}
	else {
		$inner = $parameter;
		$inner['type'] = 'int';
		$bad['functions'][0]['parameters'][0]['type'] = ['callable'=>['parameters'=>[$inner], 'returns'=>'void']];
	}
	$error = '';
	try {
		Runtime_Catalog_Reader::decode(json_encode($bad), 'invalid-copy.json');
	}
	catch (\RuntimeException $e) {
		$error = $e->getMessage();
	}
	copy_requirement_check($error !== '', 'Invalid metadata admitted: '.$case);
	$invalid[$case] = $error;
}
$plain = $function;
$plain['id'] = 'copy.consume';
$plain['name'] = 'consume';
$plain['parameters'][0]['requires_copy'] = false;
$fixed = $function;
$fixed['id'] = 'copy.fixed';
$fixed['name'] = 'fixed';
$fixed['parameters'][0]['type'] = ['name'=>'unique', 'arguments'=>['int']];
unset($fixed['type_parameters']);
foreach ([$plain, $fixed] as $row)
{
	$schema['functions'][] = $row;
	$schema['cpp'][] = ['id'=>$row['id'], 'symbol'=>'copy_probe::consume', 'header'=>__DIR__.'/runtime_copy_support.hpp'];
}
$input = new runtime_catalog_input();
foreach (Model::$runtime_metadata->modules as $module) {
	$input->modules->add($module->name, $module);
}
$input->modules->add('copy_probe', Runtime_Catalog_Reader::decode(json_encode($schema), 'copy-probe.json'));
Compiler_Lifecycle::configure_runtime($input);
copy_requirement_compile('function good(unique<int> $v):void{consume(move($v));} checked(7);checked("ok");$v vector<int> = [1];checked($v);');
file_put_contents($argv[1].'/copy_control.cpp', CPP_Probe::text(Model::$cpp_files));
$negative = [];
foreach (['checked', 'fixed'] as $name)
{
	$error = '';
	try {
		copy_requirement_compile('function bad(unique<int> $v):void{'.$name.'(move($v));}');
	}
	catch (\RuntimeException $e) {
		$error = $e->getMessage();
	}
	copy_requirement_check(str_contains($error, 'copyable_value') && Model::$cpp_files->is_empty(), 'Missing provider copy requirement: '.$name);
	$negative[$name] = $error;
}
file_put_contents($argv[1].'/copy_requirements.json', json_encode(['invalid_metadata'=>$invalid, 'rejections'=>$negative], JSON_PRETTY_PRINT)."\n");
