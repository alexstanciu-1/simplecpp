<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function payload_check(bool $ok, string $why): void
{
	if (!$ok) {
		throw new \LogicException($why);
	}
}

/** Run through ordinary source registration, shared preparation and artifact emission. */
function payload_compile(string $source): void
{
	Compiler_Lifecycle::reset();
	$module = new module('/payload', '/payload', '/payload');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler(new cpp_lowering_options(false)))->exec_cpp();
}

$schema = ['version'=>1, 'module'=>'payload_probe', 'dependencies'=>['core'], 'functions'=>[], 'cpp'=>[]];
/** Install ordinary metadata contracts to test selection independently of builtin names. */
function payload_row(array &$schema, string $name, array $types, mixed $returns, string $symbol = 'identity', array $extra = []): void
{
	$id = 'payload.' . count($schema['functions']);
	$params = [];
	foreach ($types as $index => $type) {
		$params[] = is_array($type) && isset($type['passing']) ? $type : ['name'=>'p'.$index, 'type'=>$type, 'passing'=>'value'];
	}
	$schema['functions'][] = ['id'=>$id, 'name'=>$name, 'parameters'=>$params, 'returns'=>$returns] + $extra;
	$schema['cpp'][] = ['id'=>$id, 'symbol'=>'payload_probe::'.$symbol, 'header'=>__DIR__.'/runtime_payload_support.hpp'];
}
$falseint = ['name'=>'result_or_false', 'arguments'=>['int']];
foreach (['int','float','bool','string'] as $type) {
	payload_row($schema, 'probe_value', [$type], $type);
}
payload_row($schema, 'probe_priority', ['int'], 'int', 'fallback');
payload_row($schema, 'probe_priority', [$falseint], 'int', 'exact');
payload_row($schema, 'probe_generic', ['int'], 'int', 'fallback');
payload_row($schema, 'probe_generic', [['parameter'=>'T']], 'int', 'exact', ['type_parameters'=>['T']]);
payload_row($schema, 'probe_ambiguous', ['int',$falseint], 'int');
payload_row($schema, 'probe_ambiguous', [$falseint,'int'], 'int');
foreach (['int','string'] as $type)
{
	payload_row($schema, 'probe_ref', [['name'=>'v','type'=>$type,'passing'=>'reference']], 'int');
	payload_row($schema, 'probe_observe', [['name'=>'v','type'=>$type,'passing'=>'observe']], 'int');
	payload_row($schema, 'probe_default', [$type,['name'=>'n','type'=>'int','passing'=>'value','default'=>['int'=>0]]], 'int');
	payload_row($schema, 'probe_pack', [['name'=>'v','type'=>$type,'passing'=>'value','variadic'=>true]], 'int');
}
payload_row($schema, 'probe_numeric', ['float'], 'int');
payload_row($schema, 'probe_numeric', ['string'], 'int');
payload_row($schema, 'probe_wrap', [['name'=>'result','arguments'=>['int']]], 'int');
payload_row($schema, 'probe_wrap', [['name'=>'result','arguments'=>['string']]], 'int');
$reference = ['name'=>'trace','type'=>'int','passing'=>'reference'];
payload_row($schema, 'probe_pair', ['int','string',$reference], 'int', 'pair');
payload_row($schema, 'probe_pair', ['string','int',$reference], 'int', 'pair');
$input = new runtime_catalog_input();
foreach (Model::require_runtime_metadata()->modules as $module) {
	$input->modules->add($module->name, $module);
}
$input->modules->add('payload_probe', Runtime_Catalog_Reader::decode(json_encode($schema), 'payload_probe.json'));
Compiler_Lifecycle::configure_runtime($input);

$out = $argv[1];
$path = 'tests/php/strings/strict_level_01/strict_strings_004_strpos_false_branch_regression.phs';
$text = file_get_contents(dirname(__DIR__, 3) . '/' . $path);
$original = ['path' => $path, 'sha256' => hash('sha256', $text)];
try {
	payload_compile($text);
	$original['compiled'] = true;
	file_put_contents($out . '/original.cpp', CPP_Probe::text(Model::$cpp_files));
}
catch (\RuntimeException $e) {
	$original['compiled'] = false;
	$original['diagnostic'] = $e->getMessage();
}
if (($argv[2] ?? '') === '--baseline') {
	payload_check(str_contains($original['diagnostic'] ?? '', 'exact signature'), 'Missing S09-B1 baseline');
	file_put_contents($out . '/baseline.json', json_encode($original, JSON_PRETTY_PRINT) . "\n");
	exit(0);
}
payload_check($original['compiled'], 'Original false-branch fixture remains blocked');



$program = '';
$native = '';
$signatures = [];
$index = 0;
foreach (['nullable','result','result_or_false','result_or_bool'] as $family)
{
	foreach (['int'=>['scpp::int_t<>','0'], 'float'=>['scpp::float_t','-0.0'], 'bool'=>['scpp::bool_t','false'], 'string'=>['scpp::string_t','std::string{"a\0b",3}']] as $type => $cpp)
	{
		$wrapper = $family === 'nullable' ? '?'.$type : $family.'<'.$type.'>';
		$program .= 'function f'.$index.'(const '.$wrapper.' &$v):'.$type.'{return probe_value($v);} ';
		$cpp_wrapper = 'scpp::'.$family.'<'.$cpp[0].'>';
		$native .= '{'.$cpp[0].' value{'.$cpp[1].'};'.$cpp_wrapper.' wrapped{value};auto result=function_f'.$index.'(wrapped);';
		$native .= 'if(result.native_value()!=value.native_value() || !wrapped.has_value().native_value() || wrapped.value().native_value()!=value.native_value())return 10;';
		if ($type === 'float') {
			$native .= 'if(!std::signbit(result.native_value()))return 11;';
		}
		$native .= "}\n";
		$states = $family === 'nullable' ? ['scpp::null'] : ($family === 'result' ? ['scpp::error_t{scpp::string_t{"failure"}}'] : ['scpp::false_sentinel']);
		if ($family === 'result_or_bool') {
			$states[] = 'scpp::true_sentinel';
		}
		foreach ($states as $state) {
			$native .= '{'.$cpp_wrapper.' missing{'.$state.'};bool caught=false;try{function_f'.$index.'(missing);}catch(const std::runtime_error&){caught=true;}if(!caught || missing.has_value().native_value())return 12;}' . "\n";
		}
		$signatures[] = [$wrapper, $type];
		++$index;
	}
}
$program .= <<<'PHS'
function priority():int{$v result_or_false<int> = 7;return probe_priority($v)+probe_generic($v);}
function first(result<int> &$v,int &$trace):result<int>{$trace=$trace*10+1;return $v;}
function second(result<int> &$v,int &$trace):string{$trace=$trace*10+2;$v=99;return "x";}
function ordered(result<int> &$v,int &$trace):int{return probe_pair(first($v,$trace),second($v,$trace),$trace);}
PHS;
payload_compile($program);
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', CPP_Probe::text(Model::$cpp_files));
payload_check(str_contains($cpp, 'require_value('), 'Missing shared extraction lowering');
$cpp .= "\n#include <cmath>\n#include <iostream>\nint main(){\n" . $native;
$cpp .= <<<'CPP'
 if(function_priority().native_value()!=4)return 20;
 scpp::result<scpp::int_t<>> good{scpp::int_t<>{7}};scpp::int_t<> trace{0};
 if(function_ordered(good,trace).native_value()!=7 || trace.native_value()!=123 || good.value().native_value()!=99)return 21;
 scpp::result<scpp::int_t<>> bad{scpp::error_t{scpp::string_t{"failure"}}};trace=scpp::int_t<>{0};bool caught=false;
 try{function_ordered(bad,trace);}catch(const std::runtime_error&){caught=true;}
 if(!caught || trace.native_value()!=1 || bad.has_value().native_value())return 22;
 std::cout<<"16 payload signatures; 20 missing states; exact/generic priority; ordered extraction\n";
 return 0;}
CPP;
file_put_contents($out . '/behavior.cpp', $cpp);
$rejections = [];
foreach (['$v result_or_false<int> = 7;probe_ambiguous($v,$v);', '$v result<int> = 7;probe_ref($v);', '$v result<int> = 7;probe_observe($v);', '$v result<int> = 7;probe_default($v);', '$v result<int> = 7;probe_default($v,0);', '$v result<int> = 7;probe_pack($v);', '$v result<int> = 7;probe_numeric($v);', 'probe_numeric(7);', 'probe_wrap(7);', '$v mixed=7;probe_value($v);'] as $source)
{
	$error = '';
	try {
		payload_compile($source);
	}
	catch (\RuntimeException $e) {
		$error = $e->getMessage();
	}
	payload_check(($error !== '') && Model::$cpp_files->is_empty(), 'Fallback widened: '.$source);
	$rejections[] = ['source'=>$source,'diagnostic'=>$error];
}
payload_check(str_contains($rejections[0]['diagnostic'], 'ambiguous checked-payload'), 'Wrong ambiguity boundary');

$dir = sys_get_temp_dir().'/scpp_payload_'.bin2hex(random_bytes(6));mkdir($dir);$file = $dir.'/main.phs';$recoveries = 0;
try
{
	foreach (['?int','result<int>','result_or_false<int>','result_or_bool<int>'] as $type)
	{
		$valid = 'function read('.$type.' $v):int{return probe_value($v);}';
		file_put_contents($file, $valid);Compiler_Lifecycle::reset();$compiler = new Compiler(new cpp_lowering_options(false));$compiler->init([$dir]);$compiler->exec_cpp();$fresh = CPP_Probe::text(Model::$cpp_files);
		file_put_contents($file, str_replace('probe_value','probe_numeric',$valid));
		foreach ([[$file],[]] as $changes)
		{
			$failed = false;
			try { $compiler->update_cpp($changes); }
			catch (\RuntimeException $e) { $failed = true; }
			payload_check($failed && Model::$cpp_files->is_empty() && Model::$prepared_files->is_empty(), 'Stale payload output');
		}
		file_put_contents($file,$valid);$compiler->update_cpp([$file]);payload_check(CPP_Probe::text(Model::$cpp_files)===$fresh,'Repair differs');$compiler->update_cpp([]);payload_check(CPP_Probe::text(Model::$cpp_files)===$fresh,'No-op differs');++$recoveries;
	}
}
finally { unlink($file);rmdir($dir); }
file_put_contents($out.'/checks.json',json_encode(['original'=>$original,'signatures'=>$signatures,'rejections'=>$rejections,'recoveries'=>$recoveries],JSON_PRETTY_PRINT)."\n");
