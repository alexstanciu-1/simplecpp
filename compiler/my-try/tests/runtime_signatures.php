<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function signature_check(bool $value, string $message): void {
	if (!$value) { throw new \LogicException($message); }
}
// Host fixture authoring only; production JSON decoding uses typed records directly.
function signature_parameter(string $name, mixed $type, ?array $default = null): array {
	$result = ['name'=>$name, 'type'=>$type, 'passing'=>'value'];
	if ($default !== null) { $result['default'] = $default; }
	return $result;
}
$variable = ['parameter'=>'T'];
$functions = [
	['id'=>'defaults', 'name'=>'defaults', 'parameters'=>[
		signature_parameter('number', 'int', ['int'=>7]),
		signature_parameter('text', 'string', ['string'=>"a\0b"]),
		signature_parameter('enabled', 'bool', ['bool'=>true]),
		signature_parameter('scale', 'float', ['float'=>2.5])], 'returns'=>'int'],
	['id'=>'choose', 'name'=>'choose', 'type_parameters'=>['T'], 'parameters'=>[
		signature_parameter('value', $variable), signature_parameter('fallback', $variable, ['int'=>9])], 'returns'=>$variable],
	['id'=>'pick_int', 'name'=>'pick', 'parameters'=>[
		signature_parameter('number','int'), signature_parameter('suffix','int',['int'=>4])], 'returns'=>'int'],
	['id'=>'pick_string', 'name'=>'pick', 'parameters'=>[signature_parameter('text','string')], 'returns'=>'int'],
	['id'=>'amb_int', 'name'=>'ambiguous', 'parameters'=>[signature_parameter('n','int',['int'=>0])], 'returns'=>'int'],
	['id'=>'amb_string', 'name'=>'ambiguous', 'parameters'=>[signature_parameter('s','string',['string'=>''])], 'returns'=>'int'],
];
$bindings = [];
foreach ($functions as $function) {
	$symbol = $function['name'] === 'ambiguous' ? 'pick' : $function['name'];
	$bindings[] = ['id'=>$function['id'], 'symbol'=>'signature_proof::'.$symbol, 'header'=>'runtime_signature_support.hpp'];
}
$schema = ['version'=>1, 'module'=>'signature_proof', 'dependencies'=>['core'], 'functions'=>$functions, 'cpp'=>$bindings];
$input = Runtime_Catalog_Loader::load(dirname(__DIR__,3).'/runtime/metadata/modules',['core']);
$input->modules->add('signature_proof', Runtime_Catalog_Reader::decode(json_encode($schema), 'signatures.json'));
Compiler_Lifecycle::configure_runtime($input);
$invalids = [];
$bad=$schema; $bad['functions'][0]['parameters'][0]['default']=['string'=>'wrong']; $invalids[]=[$bad,'exact parameter type'];
$bad=$schema; unset($bad['functions'][0]['parameters'][1]['default']); $invalids[]=[$bad,'required parameter cannot follow'];
$bad=$schema; $bad['functions'][0]['parameters'][0]['passing']='reference'; $invalids[]=[$bad,'reference parameter defaults'];
$bad=$schema; $bad['functions'][0]['parameters'][0]['default']=['int'=>1,'bool'=>true]; $invalids[]=[$bad,'one scalar tag'];
$bad=$schema; $bad['functions'][0]['parameters'][0]['default']=['null'=>null]; $invalids[]=[$bad,'Unsupported runtime default'];
$bad=$schema; $bad['functions'][1]['parameters'][1]['default']=['int'=>1.5]; $invalids[]=[$bad,'integer spelling'];
foreach ($invalids as [$bad,$message]) {
	$failed=false;
	try {
		$candidate=new runtime_catalog_input(); $candidate->modules->add('core',$input->modules['core']);
		$candidate->modules->add('signature_proof',Runtime_Catalog_Reader::decode(json_encode($bad),'bad.json'));
		Compiler_Lifecycle::configure_runtime($candidate);
	} catch (\Throwable $error) { $failed=str_contains($error->getMessage(),$message); }
	signature_check($failed && Model::$runtime_metadata===$input,'Bad default metadata: '.$message);
}
function signature_compile(string $text): string {
	Compiler_Lifecycle::reset();
	$module=new module('/signatures','/signatures','/signatures');
	$file=new file(); $file->path='main.phs'; $file->content=$text;
	Source_Registry::add($module,$file); Model::$modules->add($module->name,$module);
	$compiler=new Compiler(); $compiler->exec_cpp();
	$result=CPP_Probe::text(Model::$cpp_files); $compiler->exec_cpp();
	signature_check($result===CPP_Probe::text(Model::$cpp_files),'No-op changed default output');
	return $result;
}
$source=<<<'PHS'
$a = defaults();
$b = defaults(1, "", false, 1.5);
$c = choose(2);
$d = choose(2, 3);
$e = pick(5);
$f = pick("xx");
if ($a !== 25 || $b !== 4 || $c !== 9 || $d !== 3 || $e !== 9 || $f !== 2) { return 1; }
return 0;
PHS;
file_put_contents($argv[1].'/signatures.cpp',signature_compile($source));
foreach (['choose();','choose("wrong");','ambiguous();','defaults(1,"",true,2.0,3);'] as $invalid) {
	$failed=false;
	try { signature_compile($invalid); } catch (\RuntimeException $error) { $failed=true; }
	signature_check($failed && Model::$cpp_files->is_empty(),'Invalid default call accepted: '.$invalid);
}
signature_check(signature_compile($source)!=='','Default call recovery failed');
echo "Runtime signature metadata passed\n";
