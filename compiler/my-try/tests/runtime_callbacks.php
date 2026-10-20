<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function callback_check(bool $condition, string $message): void {
	if (!$condition) { throw new \LogicException($message); }
}
function callback_parameter(string $name, mixed $type): array { return ['name'=>$name,'type'=>$type,'passing'=>'value']; }
function callback_type(mixed $result, array $parameters): array { return ['callable'=>['returns'=>$result,'parameters'=>$parameters]]; }
$integer_callback = callback_type('int',[callback_parameter('value','int')]);
$string_callback = callback_type('int',[callback_parameter('value','string')]);
$zero_callback = callback_type('int',[]);
$reference_input = ['name'=>'value','type'=>'int','passing'=>'reference'];
$reference_callback = callback_type('void',[$reference_input]);
$generic_callback = callback_type(['parameter'=>'R'],[callback_parameter('value',['parameter'=>'T'])]);
$functions = [
	['id'=>'apply','name'=>'apply','parameters'=>[callback_parameter('fn',$integer_callback),callback_parameter('value','int')],'returns'=>'int'],
	['id'=>'transform','name'=>'transform','type_parameters'=>['T','R'],
		'parameters'=>[callback_parameter('fn',$generic_callback),callback_parameter('value',['parameter'=>'T'])],'returns'=>['parameter'=>'R']],
	['id'=>'choose_int','name'=>'choose','parameters'=>[callback_parameter('fn',$integer_callback),callback_parameter('value','int')],'returns'=>'int'],
	['id'=>'choose_string','name'=>'choose','parameters'=>[callback_parameter('fn',$string_callback),callback_parameter('value','string')],'returns'=>'int'],
	['id'=>'retain','name'=>'retain','parameters'=>[callback_parameter('fn',$zero_callback)],'returns'=>$zero_callback],
	['id'=>'mutate','name'=>'mutate','parameters'=>[callback_parameter('fn',$reference_callback),$reference_input],'returns'=>'void'],
];
$bindings=[];
foreach ($functions as $function) { $bindings[]=['id'=>$function['id'],'symbol'=>'callback_proof::'.$function['name'],'header'=>'runtime_callback_support.hpp']; }
$schema=['version'=>1,'module'=>'callback_proof','dependencies'=>['core'],'functions'=>$functions,'cpp'=>$bindings];
$input=Runtime_Catalog_Loader::load(dirname(__DIR__,3).'/runtime/metadata/modules',['core']);
$input->modules->add('callback_proof',Runtime_Catalog_Reader::decode(json_encode($schema),'callbacks.json'));
Compiler_Lifecycle::configure_runtime($input);
$invalids=[];
$bad=$schema; $bad['functions'][0]['parameters'][0]['type']['name']='int'; $invalids[]=[$bad,'another type form'];
$bad=$schema; $bad['functions'][0]['parameters'][0]['type']['callable']['parameters'][0]['type']='void'; $invalids[]=[$bad,'Void runtime'];
$bad=$schema; $bad['functions'][0]['parameters'][0]['type']['callable']['parameters'][0]['default']=['int'=>0]; $invalids[]=[$bad,'defaults or observation'];
$bad=$schema; $bad['functions'][1]['parameters'][0]['type']['callable']['returns']=['parameter'=>'Unknown']; $invalids[]=[$bad,'Unknown runtime type parameter'];
$bad=$schema; $bad['functions'][1]['parameters'][0]['type']['callable']['parameters'][]=$bad['functions'][1]['parameters'][0]['type']['callable']['parameters'][0]; $invalids[]=[$bad,'Duplicate runtime callable parameter'];
foreach ($invalids as [$bad,$message]) {
	$failed=false;
	try {
		$candidate=new runtime_catalog_input(); $candidate->modules->add('core',$input->modules['core']);
		$candidate->modules->add('callback_proof',Runtime_Catalog_Reader::decode(json_encode($bad),'bad.json'));
		Compiler_Lifecycle::configure_runtime($candidate);
	} catch (\Throwable $error) { $failed=str_contains($error->getMessage(),$message); }
	callback_check($failed && Model::$runtime_metadata===$input,'Bad callback metadata: '.$message);
}
function callback_compile(string $source): string {
	Compiler_Lifecycle::reset();
	$module=new module('/callbacks','/callbacks','/callbacks');
	$file=new file(); $file->path='main.phs'; $file->content=$source;
	Source_Registry::add($module,$file); Model::$modules->add($module->name,$module);
	$compiler=new Compiler(new cpp_lowering_options(false)); $compiler->exec_cpp();
	$output=CPP_Probe::text(Model::$cpp_files); $compiler->exec_cpp();
	callback_check($output===CPP_Probe::text(Model::$cpp_files),'Callback no-op changed output');
	return $output;
}
$source=<<<'PHS'
$a = apply(fn($n) => $n + 1, 4);
$b = transform(function (int $n): string { return "text"; }, 2);
$length = strlen($b);
$c = choose(function (int $n): int { return $n + 2; }, 5);
$d = choose(function (string $text): int { return strlen($text); }, "abc");
function make(): function<int()> { $n = 13; return retain(function () use ($n): int { return $n; }); }
$held = make();
$e = $held();
$value = 4;
mutate(function (&$n) { $n += 3; }, $value);
if ($value !== 7) { return 2; }
if ($a !== 5 || $length !== 4 || $c !== 7 || $d !== 3 || $e !== 13) { return 1; }
return 0;
PHS;
file_put_contents($argv[1].'/callbacks.cpp',callback_compile($source));
foreach ([
	'apply(function (string $s): int { return 1; }, 2);',
	'transform(function (int $n): int { return $n; }, "wrong");',
	'$n = 1; retain(function () use (&$n): int { return $n; });',
] as $invalid) {
	$failed=false;
	try { callback_compile($invalid); } catch (\RuntimeException $error) { $failed=true; }
	callback_check($failed && Model::$cpp_files->is_empty(),'Unsafe callback accepted: '.$invalid);
}
callback_check(callback_compile($source)!=='','Callback recovery failed');
echo "Runtime callback metadata passed\n";
