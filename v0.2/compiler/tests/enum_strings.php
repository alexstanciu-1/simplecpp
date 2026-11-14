<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Incremental_Project, type_availability, Program_Preparation, Symbol_Publication};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

/** Require semantic diagnostics before native compilation. */
function rejects(Closure $action, string $message): void
{
	try {
		$action();
	}
	catch (diagnostic|diagnostic_group $error) {
		verify(str_contains($error->getMessage(), $message), $error->getMessage());
		return;
	}
	throw new RuntimeException('Expected diagnostic: ' . $message);
}

/** Read only emitted native artifacts for incremental/fresh equivalence. @return string[] */
function artifacts(string $directory): array /** array<string,string> */
{
	$files = [];
	foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $item) {
		$path = $item->getPathname();
		if (in_array(pathinfo($path, PATHINFO_EXTENSION), ['cpp', 'hpp'], true)) {
			$files[substr($path, strlen($directory) + 1)] = file_get_contents($path);
		}
	}
	ksort($files);
	return $files;
}

/** Remove the isolated proof tree, including emitted bundles. */
function remove_tree(string $path): void
{
	foreach (new DirectoryIterator($path) as $item) {
		if (!$item->isDot()) {
			$item->isDir() ? remove_tree($item->getPathname()) : unlink($item->getPathname());
		}
	}
	rmdir($path);
}

$fixtures=[];
foreach(flavor::cases() as $flavor) {
	$sep=$flavor===flavor::js?'.':'::';
	$enum=static function(string $value='"a\\0b"',string $name='Binary')use($flavor):string {
		return $flavor===flavor::php?'enum Kind:string {case Empty="";case '.$name.'='.$value.';case Zero="0";case Zeros="00";case Long="a long string that needs heap storage";}'
			:'enum Kind:string {Empty="",'.$name.'='.$value.',Zero="0",Zeros="00",Long="a long string that needs heap storage"}';
	};
	$prepare=static fn(string $source)=>(new Source_Frontend())->prepare(new source_file('string-enum',$source,$flavor),new entry_target(31));
	$body='';$expected='';
	foreach(['Empty'=>'','Binary'=>"a\0b",'Zero'=>'0','Zeros'=>'00','Long'=>'a long string that needs heap storage'] as $name=>$value) {
		$body.='echo enum_name(enum_from_value(Kind'.$sep.'class,enum_value(Kind'.$sep.$name.'))),":",enum_value(Kind'.$sep.$name.'),"|";';
		$expected.=$name.':'.$value.'|';
	}
	$start=hrtime(true);$cpp=(new Cpp_Emitter())->emit($prepare($enum().$body.'return 8;'));
	$fixtures[$flavor->name]=['cpp'=>$cpp,'stdout_hex'=>bin2hex($expected),'compiler_ms'=>(hrtime(true)-$start)/1e6];
	$many = 'enum Kind:string {';
	for ($index = 0; $index < 257; $index++) {
		$many .= ($flavor === flavor::php ? 'case ' : '') . 'Case' . $index . '= "value' . $index . '"' . ($flavor === flavor::php ? ';' : ',');
	}
	$many .= '}echo enum_name(Kind' . $sep . 'Case256);return 8;';
	$wide = (new Cpp_Emitter())->emit($prepare($many));
	$wide .= "\nstatic_assert(sizeof(App::scpp_records::Kind) == 2);\n";
	$fixtures[$flavor->name . '_wide_tags'] = ['cpp' => $wide, 'stdout_hex' => bin2hex('Case256')];
	foreach(['"a"','"A\\0b"','"missing"'] as $index=>$input) {
		$fixtures[$flavor->name.'_miss_'.$index]=['cpp'=>(new Cpp_Emitter())->emit($prepare($enum().'enum_from_value(Kind'.$sep.'class,'.$input.');return 8;')),
			'stdout_hex'=>'','catch_runtime_error'=>'Invalid value for enum helper'];
	}
	rejects(fn()=>$prepare($enum('"0"').'return 8;'),'Duplicate enum value');
	rejects(fn()=>$prepare($enum('1').'return 8;'),'String-backed enum requires string literals');
	rejects(fn()=>$prepare(str_replace(':string',':int',$enum()).'return 8;'),'Integer-backed enum requires integer literals');
	foreach(['1','true','null'] as $input) rejects(fn()=>$prepare($enum().'enum_from_value(Kind'.$sep.'class,'.$input.');return 8;'),'enum_from_value requires a string');
	// Ordinary field/default/signature paths retain nominal enum identity, not backing strings.
	$record=match($flavor) {
		flavor::php=>'struct Box {public Kind $kind=Kind::Binary; public function __construct(Kind $v=Kind::Binary){$this->kind=$v;} public function get(Kind $fallback=Kind::Empty):Kind{return $this->kind;}} $b Box;echo enum_value($b->get());',
		flavor::cpp=>'struct Box {Kind kind=Kind::Binary; Box(Kind v=Kind::Binary){this->kind=v;} Kind get(Kind fallback=Kind::Empty){return this->kind;}}; Box b;echo enum_value(b->get());',
		flavor::js=>'struct Box {kind:Kind=Kind.Binary; constructor(v:Kind=Kind.Binary){this.kind=v;} get(fallback:Kind=Kind.Empty):Kind{return this.kind;}} let b:Box;echo enum_value(b.get());',
	};
	$fixtures[$flavor->name.'_fields']=['cpp'=>(new Cpp_Emitter())->emit($prepare($enum().$record.'return 8;')),'stdout_hex'=>bin2hex("a\0b")];
	$directory=sys_get_temp_dir().'/scpp-string-enum-'.bin2hex(random_bytes(8));mkdir($directory);
	try {
		$main=$directory.'/main.'.$flavor->name;$provider=$directory.'/kind.'.$flavor->name;
		file_put_contents($directory.'/module.json',json_encode(['entry'=>basename($main),'sources'=>[basename($main),basename($provider)]]));
		file_put_contents($main,'echo enum_name(enum_from_value(Kind'.$sep.'class,enum_value(Kind'.$sep.'Binary))),":",enum_value(Kind'.$sep.'Binary);return 8;');
		$project=new Incremental_Project($directory,$directory.'/out',new entry_target(31));$stamp=time();$caller=null;
		foreach(['"a\\0b"','"é"',null,'"recovered"','"0"','"final"'] as $index=>$literal) {
			file_put_contents($provider,$literal===null?'':$enum($literal));touch($provider,++$stamp);
			$fresh=new Incremental_Project($directory,$directory.'/fresh',new entry_target(31));
			$error=$literal===null?'enum_from_value target must be an enum':($literal==='"0"'?'Duplicate enum value':null);
			if($error!==null) {
				rejects(fn()=>$project->run(),$error);rejects(fn()=>$project->run(),$error);rejects(fn()=>$fresh->run(),$error);continue;
			}
			$project->run();$fresh->run();$caller??=$project->session()->entry->entry->ast;
			verify($caller===$project->session()->entry->entry->ast,'Caller reparsed');
			$files=artifacts($directory.'/out');verify($files===artifacts($directory.'/fresh'),'Fresh differs');
			$payload=$index===0?"a\0b":substr($literal,1,-1);
			$fixtures[$flavor->name.'_edit_'.$index]=['files'=>$files,'stdout_hex'=>bin2hex('Binary:'.$payload)];
			verify($project->run()['preparation']['prepared']===0,'No-op prepares');
		}
	} finally {
		remove_tree($directory);
	}
}
if(isset($argv[1]))file_put_contents($argv[1],json_encode($fixtures,JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT));
echo "String enums: exact bytes, fields/defaults and incremental/fresh recovery passed\n";
