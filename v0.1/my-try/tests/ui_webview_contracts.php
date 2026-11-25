<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

$schema = ['version'=>1,'module'=>'probe','dependencies'=>[], 'functions'=>[], 'cpp'=>[]];
foreach ([['providers'=>['shell']], ['compile_options'=>['-include','/tmp/inject.hpp']],
	['link_options'=>['-Wl,--script=/tmp/inject']], ['diagnostics'=>['fake']]] as $invalid) {
	$rejected = false;
	try { Runtime_Catalog_Reader::decode(json_encode($schema + ['cpp_build'=>$invalid]), 'probe.json'); }
	catch (\RuntimeException $error) { $rejected = true; }
	if (!$rejected) { throw new \LogicException('Module JSON admitted unowned SDK policy'); }
}

$input = Runtime_Catalog_Loader::load(dirname(__DIR__, 3) . '/runtime/metadata/modules', ['webview']);
$rejected = false;
try { CPP_Runtime_Build::requirements($input); }
catch (\RuntimeException $error) { $rejected = true; }
if (!$rejected) { throw new \LogicException('Unresolved SDK silently published disabled providers'); }

$build = new runtime_cpp_build_spec();
$build->compile_options = ['-I', '/opt/sdk/include', '-pthread'];
$build->link_options = ['-L/opt/sdk/lib', '-lgtk-3', '-Wl,--export-dynamic'];
$build->diagnostics = ['fixture SDK'];
$copy = Native_CPP_Publication::runtime_inputs(json_read(Runtime_Build_Contract::encode($build)));
if ($copy->compile_options !== $build->compile_options || $copy->link_options !== $build->link_options
	|| $copy->diagnostics !== $build->diagnostics) { throw new \LogicException('SDK publication lost resolved arguments'); }
foreach ([['-include', '/tmp/inject.hpp'], ['-I'], ['-I', 'relative'], ['-DFAKE=1'], ["-I/tmp/\n"]] as $invalid) {
	$rejected = false;
	try { Runtime_Build_Contract::options($invalid, false); }
	catch (\RuntimeException $error) { $rejected = true; }
	if (!$rejected) { throw new \LogicException('SDK compilation admitted unsupported flags'); }
}
$old = Native_CPP_Publication::runtime_inputs(json_read('{"sources":[],"libraries":[],"definitions":[]}'));
if ($old->compile_options !== [] || $old->link_options !== []) { throw new \LogicException('Old publication acquired SDK flags'); }
echo "UI/WebView SDK contracts passed\n";
