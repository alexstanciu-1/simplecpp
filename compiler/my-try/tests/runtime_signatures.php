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
function signature_pack(mixed $type): array {
	$result = signature_parameter('values', $type);
	$result['variadic'] = true;
	return $result;
}
function signature_reference(mixed $type, bool $readonly = false): array {
	$result = signature_parameter('value', $type);
	$result['passing'] = $readonly ? 'readonly_reference' : 'reference';
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
	['id'=>'sum', 'name'=>'sum', 'parameters'=>[signature_parameter('base','int',['int'=>5]),signature_pack('int')], 'returns'=>'int'],
	['id'=>'gather', 'name'=>'gather', 'type_parameters'=>['T'], 'parameters'=>[signature_pack($variable)],
		'returns'=>['name'=>'vector','arguments'=>[$variable]]],
	['id'=>'measure_int', 'name'=>'measure', 'parameters'=>[signature_pack('int')], 'returns'=>'int'],
	['id'=>'measure_string', 'name'=>'measure', 'parameters'=>[signature_pack('string')], 'returns'=>'int'],
	['id'=>'touch_int', 'name'=>'touch', 'parameters'=>[signature_reference('int')], 'returns'=>'void'],
	['id'=>'touch_string', 'name'=>'touch', 'parameters'=>[signature_reference('string')], 'returns'=>'void'],
	['id'=>'assign', 'name'=>'assign', 'type_parameters'=>['T'],
		'parameters'=>[signature_reference($variable),signature_parameter('replacement',$variable)], 'returns'=>'void'],
	['id'=>'read_ref', 'name'=>'read_ref', 'type_parameters'=>['T'],
		'parameters'=>[signature_reference($variable,true)], 'returns'=>$variable],
	['id'=>'gather_after', 'name'=>'gather_after', 'type_parameters'=>['T'],
		'parameters'=>[signature_parameter('seed',$variable),signature_pack($variable)],
		'returns'=>['name'=>'vector','arguments'=>[$variable]]],
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
$bad=$schema; $bad['functions'][6]['parameters'][]=signature_parameter('after','int'); $invalids[]=[$bad,'variadic parameter must be last'];
$bad=$schema; $bad['functions'][7]['parameters'][0]['passing']='reference'; $invalids[]=[$bad,'variadic parameters require value'];
$bad=$schema; $bad['functions'][7]['parameters'][0]['default']=['int'=>1]; $invalids[]=[$bad,'without an explicit default'];
$bad=$schema; $bad['functions'][6]['parameters'][1]['type']=['name'=>'unique','arguments'=>['int']]; $invalids[]=[$bad,'must be copyable'];
$bad=$schema; $duplicate=$bad['functions'][10]; $duplicate['id']='duplicate_touch'; $duplicate['parameters'][0]['passing']='value';
$bad['functions'][]=$duplicate; $bad['cpp'][]=['id'=>'duplicate_touch','symbol'=>'signature_proof::touch','header'=>'runtime_signature_support.hpp'];
$invalids[]=[$bad,'Duplicate runtime overload'];
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
$empty_sum = sum();
$total = sum(10, 2, 3);
$numbers = gather(2, 4, 6);
$texts = gather("a", "bc");
$n = count($numbers);
$m = count($texts);
$measured = measure(1, 2, 3);
$length = measure("one", "two");
if ($empty_sum !== 5 || $total !== 15 || $n !== 3 || $m !== 2 || $measured !== 6 || $length !== 6) { return 2; }
$changed = 1;
$text = "old";
touch($changed);
touch($text);
$text_length = strlen($text);
if ($changed !== 11 || $text_length !== 7) { return 3; }
assign($changed, 19);
assign($text, "new");
$read = read_ref($changed);
$text_copy = read_ref($text);
$copied_length = strlen($text_copy);
if ($read !== 19 || $copied_length !== 3) { return 4; }
$empty_pack = gather_after(7);
$empty_size = count($empty_pack);
if ($empty_size !== 0) { return 5; }
if ($a !== 25 || $b !== 4 || $c !== 9 || $d !== 3 || $e !== 9 || $f !== 2) { return 1; }
return 0;
PHS;
file_put_contents($argv[1].'/signatures.cpp',signature_compile($source));
foreach (['choose();','choose("wrong");','ambiguous();','defaults(1,"",true,2.0,3);',
	'gather();', 'gather(1,"wrong");', 'measure();', 'sum(1,"wrong");',
	'touch(1);', '$value = 1; assign($value,"wrong");',
	'function rejected(const int &$value): void { touch($value); }',
	'function bad(unique<int> $value): void { $items = gather(move($value)); }'] as $invalid) {
	$failed=false;
	try { signature_compile($invalid); } catch (\RuntimeException $error) { $failed=true; }
	signature_check($failed && Model::$cpp_files->is_empty(),'Invalid default call accepted: '.$invalid);
}
signature_check(signature_compile($source)!=='','Default call recovery failed');
echo "Runtime signature metadata passed\n";
