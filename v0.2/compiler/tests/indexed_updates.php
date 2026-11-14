<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/compilation.php';
require_once __DIR__ . '/../host/syntax.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Incremental_Project};

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

$fixtures = [];
foreach (flavor::cases() as $flavor) {
	$v = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	$decl = static fn(string $type, string $name, string $value): string => match ($flavor) {
		flavor::php => '$' . $name . ' ' . $type . ' = ' . $value . ';',
		flavor::cpp => $type . ' ' . $name . '=' . $value . ';',
		flavor::js => 'let ' . $name . ':' . $type . ' = ' . $value . ';',
	};
	$param = static fn(string $type, string $name): string => $flavor === flavor::js ? $name . ':' . $type : $type . ' ' . $v($name);
	$function = static fn(string $type, string $name, string $params, string $body): string =>
		($flavor === flavor::cpp ? $type . ' ' . $name . '(' . $params . ')' : 'function ' . $name . '(' . $params . '):' . $type) . '{' . $body . '}';
	$prepare = static fn(string $text) => (new Source_Frontend())->prepare(new source_file('indexed-updates', $text, $flavor), new entry_target(31));
	$member = $flavor === flavor::js ? '.' : '->';
	$field = static fn(string $type, string $name): string => match ($flavor) {
		flavor::php => 'public ' . $type . ' $' . $name . ';',
		flavor::cpp => $type . ' ' . $name . ';',
		flavor::js => $name . ':' . $type . ';',
	};
	$record = static fn(string $name, string $fields): string => 'struct ' . $name . '{' . $fields . '}' . ($flavor === flavor::cpp ? ';' : '');
	$ref = static fn(string $type, string $name): string => $flavor === flavor::js ? '&' . $name . ':' . $type : $type . ' &' . $v($name);
	foreach (['vector<int>', 'hash<int,int>', 'hash<int,string>'] as $type) {
		$is_vector=str_starts_with($type,'vector');$key=str_ends_with($type,',string>')?'"key"':'0';$key_type=$key==='0'?'int':'string';
		$place=static fn(string $name): string=>$v($name).$member.'items';
		$insert=static fn(string $place): string=>$place.'['.($is_vector?'':$key).']=10;';
		$body='++'.$place('this').'['.$key.'];';
		$method=match($flavor) {
			flavor::php=>'public '.$function('void','bump','',$body),
			flavor::cpp=>$function('void','bump','',$body),
			flavor::js=>'bump():void {'.$body.'}',
		};
		$model=$record('Box',$field($type,'items').$method);
		$key_fn=$function($key_type,'key_once','','echo "K";return '.$key.';');
		$helpers=$function('shared<Box>','make','',$decl('shared<Box>','p','new Box()').$insert($place('p')).'return '.$v('p').';')
			.$function('shared<Box>','receiver',$param('shared<Box>','p'),'echo "R";return '.$v('p').';')
			. $function($key_type,'key_replace',$ref('shared<Box>','p'),$v('p').'=new Box();return '.$key.';')
			.$key_fn.$function('void','bump',$ref($type,'items'),'++'.$v('items').'['.$key.'];');
		$local=match($flavor) {
			flavor::php=>'$a Box;',flavor::cpp=>'Box a;',flavor::js=>'let a:Box;',
		};
		$main=$decl($type,'items','[]').$insert($v('items'))
			.$v('items').'['.$key.']++;--'.$v('items').'['.$key.'];++'.$v('items').'['.$key.'];'.$v('items').'['.$key.']--;'
			.'echo '.$v('items').'['.$key.'],"|";'
			.$decl('shared<Box>','p','make()').$decl('shared<Box>','q',$v('p'))
			.'receiver('.$v('p').')'.$member.'items[key_once()]++;--receiver('.$v('p').')'.$member.'items[key_once()];'
			.'echo '.$place('q').'['.$key.'],"|";make()'.$member.'items['.$key.']++;'
			.$v('p').'=make();'.$v('q').'='.$v('p').';receiver('.$v('p').')'.$member.'items[key_replace('.$v('p').')]++;'
			.'echo '.$place('q').'['.$key.'],"|",count('.$place('p').'),"|";'
			.$v('p').'=make();++receiver('.$v('p').')'.$member.'items[key_replace('.$v('p').')];echo count('.$place('p').'),"|";'
			.$local.$insert($place('a')).'++'.$place('a').'['.$key.'];'.$v('a').$member.'bump();'
			.'echo '.$place('a').'['.$key.'],"|";'
			.$decl($type,'copy',$v('items')).'bump('.$v('items').');echo '.$v('items').'['.$key.'],"|",'.$v('copy').'['.$key.'],"|";'
			.$v('items').'['.$key.']=9223372036854775806;++'.$v('items').'['.$key.'];echo '.$v('items').'['.$key.'],"|";'
			.$v('items').'['.$key.']=-9223372036854775807;'.$v('items').'['.$key.']--;echo '.$v('items').'['.$key.'];return 8;';
		$expected='10|RKRK10|R11|0|R0|12|11|10|9223372036854775807|-9223372036854775808';
		$start=hrtime(true);$entry=$prepare($model.$helpers.$main);$before=serialize($entry);
		scpp\compiler\Syntax_Inspection::file($entry->file);scpp\compiler\Compilation_Inspection::program($entry);
		$cpp=(new Cpp_Emitter())->emit($entry);verify($before===serialize($entry),'Lowering mutated facts');
		$name=$flavor->name.'_'.preg_replace('/[^a-z]+/','_',$type);
		$fixtures[$name]=['cpp'=>$cpp,'stdout_hex'=>bin2hex($expected),'compiler_ms'=>(hrtime(true)-$start)/1e6];
		foreach (['missing','empty_owner'] as $failure) {
			$bad=$prepare($model.$decl('shared<Box>','p',$failure==='missing'?'new Box()':'null').'++'.$place('p').'['.$key.'];return 8;');
			$fixtures[$name.'_'.$failure]=['cpp'=>(new Cpp_Emitter())->emit($bad),'stdout_hex'=>'','catch_'.($failure==='missing'?'out_of_range':'runtime_error')=>true];
		}
		if ($is_vector) {
			$bad=$prepare($decl($type,'items','[]').$insert($v('items')).$v('items').'[-1]++;return 8;');
			$fixtures[$name.'_negative']=['cpp'=>(new Cpp_Emitter())->emit($bad),'stdout_hex'=>'','catch_out_of_range'=>true];
		}
		$private=match($flavor) {
			flavor::php=>str_replace('public '.$type,'private '.$type,$model),
			flavor::cpp=>$record('Box','private: '.$field($type,'items')),
			flavor::js=>$record('Box','private '.$field($type,'items')),
		};
		rejects(fn()=>$prepare($private.$decl('shared<Box>','p','new Box()').'++'.$place('p').'['.$key.'];return 8;'),'private');
		$string_type=str_replace(['vector<int>','hash<int,'],['vector<string>','hash<string,'],$type);
		rejects(fn()=>$prepare($decl($string_type,'items','[]').'++'.$v('items').'['.$key.'];return 8;'),'Unsupported update type');
		rejects(fn()=>$prepare($decl('string','text','"a"').$v('text').'[0]++;return 8;'),'Indexed updates require');
		rejects(fn()=>$prepare($decl($type,'items','[]').$v('items').'[]++;return 8;'),'Indexed update requires a key');
		rejects(fn()=>$prepare($decl($type,'items','[]').$decl('int','result','++'.$v('items').'['.$key.']').'return 8;'),'Expected');
		rejects(fn()=>$prepare($decl($type,'items','[]').'for ('.$decl('int','i','0').$v('i').'<1;++'.$v('items').'['.$key.']) {}return 8;'),'for headers');
		$directory=sys_get_temp_dir().'/scpp-index-update-'.bin2hex(random_bytes(8));mkdir($directory);
		try {
			$provider=$directory.'/library.'.$flavor->name;file_put_contents($directory.'/main.'.$flavor->name,$main);
			file_put_contents($directory.'/module.json',json_encode(['entry'=>'main.'.$flavor->name,'sources'=>['main.'.$flavor->name,basename($provider)]]));
			$project=new Incremental_Project($directory,$directory.'/out',new entry_target(31));$stamp=time();$caller=null;
			$initial=$model.$helpers;
			$wrong=$key_type==='int'?$function('string','key_once','','return "bad";'):$function('int','key_once','','return 0;');
			$edits=[$initial,str_replace('echo "K"','echo "J"',$initial),str_replace($key_fn,$wrong,$initial),str_replace($key_fn,'',$initial),$initial];
			foreach ($edits as $index=>$text) {
				file_put_contents($provider,$text);touch($provider,++$stamp);$fresh=new Incremental_Project($directory,$directory.'/fresh',new entry_target(31));
				if ($index===2 || $index===3) {
					$error=$index===3?'Unknown':($is_vector?'Vector index requires':'Hash key requires');
					rejects(fn()=>$project->run(),$error);rejects(fn()=>$project->run(),$error);rejects(fn()=>$fresh->run(),$error);continue;
				}
				$project->run();$fresh->run();$caller??=$project->session()->entry->entry->ast;verify($caller===$project->session()->entry->entry->ast,'Caller reparsed');
				$files=artifacts($directory.'/out');verify($files===artifacts($directory.'/fresh'),'Fresh differs');
				$fixtures[$name.'_edit_'.$index]=['files'=>$files,'stdout_hex'=>bin2hex($index===1?str_replace('K','J',$expected):$expected)];
				verify($project->run()['preparation']['prepared']===0,'No-op prepared bodies');
			}
		} finally {
			remove_tree($directory);
		}
	}
}
if (isset($argv[1])) file_put_contents($argv[1],json_encode($fixtures,JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT));
echo "Indexed updates: expression receivers, prefix/postfix, boundaries and incremental recovery passed\n";
