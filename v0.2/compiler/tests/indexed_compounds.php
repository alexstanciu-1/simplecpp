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
	$prepare = static fn(string $text) => (new Source_Frontend())->prepare(new source_file('indexed-compounds', $text, $flavor), new entry_target(31));
	$member = $flavor === flavor::js ? '.' : '->';
	$field = static fn(string $type, string $name): string => match ($flavor) {
		flavor::php => 'public ' . $type . ' $' . $name . ';',
		flavor::cpp => $type . ' ' . $name . ';',
		flavor::js => $name . ':' . $type . ';',
	};
	$record = static fn(string $name, string $fields): string => 'struct ' . $name . '{' . $fields . '}' . ($flavor === flavor::cpp ? ';' : '');
	$ref = static fn(string $type, string $name): string => $flavor === flavor::js ? '&' . $name . ':' . $type : $type . ' &' . $v($name);
	foreach (['vector<int>', 'vector<string>', 'hash<int,int>', 'hash<int,string>', 'hash<string,int>', 'hash<string,string>'] as $type) {
		$is_vector = str_starts_with($type,'vector');
		$string = str_starts_with($type,'vector<string') || str_starts_with($type,'hash<string');
		$key = str_ends_with($type,',string>') ? '"key"' : '0';
		$key_type = $key === '0' ? 'int' : 'string'; $element = $string ? 'string' : 'int';
		$first = $string ? '"a\\0b"' : '8'; $rhs = $string ? '"!"' : '2';
		$op = $string && $flavor === flavor::php ? '.=' : '+=';
		$place = static fn(string $name): string => $v($name).$member.'items';
		$insert = static fn(string $place,string $value): string => $place.'['.($is_vector?'':$key).']='.$value.';';
		$model = $record('Box',$field($type,'items'));
		$factory = $function('shared<Box>','make','',$decl('shared<Box>','p','new Box()').$insert($place('p'),$first).'return '.$v('p').';');
		$helpers = $factory
			. $function('shared<Box>','receiver',$param('shared<Box>','p'),'echo "R";return '.$v('p').';')
			. $function($key_type,'key_once','','echo "K";return '.$key.';')
			. $function($element,'replace_owner',$ref('shared<Box>','p'),$v('p').'=new Box();echo "V";return '.$rhs.';')
			. $function('void','touch',$ref($type,'items'),$v('items').'['.$key.']'.$op.$rhs.';');
		// RHS growth/replacement invalidates element addresses, but not the selected named collection slot.
		$grow_body = $is_vector ? 'for ('.$decl('int','i','0').$v('i').'<128;'.$v('i').'++){'.$v('items').'[]='.$first.';}'
			: $decl($type,'fresh','[]').$v('fresh').'['.$key.']='.$first.';'.$v('items').'='.$v('fresh').';';
		$helpers .= $function($element,'grow',$ref($type,'items'),$grow_body.'return '.$rhs.';');
		$ops = $string ? [$op=>'"!"'] : ['+='=>'2','-='=>'1','*='=>'3','/='=>'3','%='=>'7','&='=>'3','|='=>'8','^='=>'1','<<='=>'1','>>='=>'1','**='=>'2'];
		$main=$decl($type,'items','[]').$insert($v('items'),$first);
		foreach ($ops as $operator=>$literal) $main.=$v('items').'['.$key.']'.$operator.$literal.';';
		$main.='echo '.$v('items').'['.$key.'],"|";touch('.$v('items').');'
			. $v('items').'['.$key.']'.$op.'grow('.$v('items').');echo '.$v('items').'['.$key.'],"|";'
			. $decl('shared<Box>','p','make()').$decl('shared<Box>','q',$v('p'))
			. 'receiver('.$v('p').')'.$member.'items[key_once()]'.$op.'replace_owner('.$v('p').');'
			. 'echo "|",'.$place('q').'['.$key.'],"|",count('.$place('p').'),"|";'
			. $v('p').'=make();receiver('.$v('p').')'.$member.'items[key_once()]'.$op.'replace_owner('.$v('p').');'
			. 'echo "|",count('.$place('p').');'
			. 'make()'.$member.'items['.$key.']'.$op.$rhs.';return 8;';
		$expected = ($string ? "a\0b!|a\0b!!!" : '121|125').'|RKV|'.($string?"a\0b!":'10').'|0|RKV|0';
		$start=hrtime(true);$entry=$prepare($model.$helpers.$main);$before=serialize($entry);
		scpp\compiler\Syntax_Inspection::file($entry->file);
		scpp\compiler\Compilation_Inspection::program($entry);
		$cpp=(new Cpp_Emitter())->emit($entry);verify($before===serialize($entry),'Lowering mutated facts');
		$name=$flavor->name.'_'.preg_replace('/[^a-z]+/','_',$type);
		$fixtures[$name]=['cpp'=>$cpp,'stdout_hex'=>bin2hex($expected),'compiler_ms'=>(hrtime(true)-$start)/1e6];
		foreach (['missing','empty_owner'] as $failure) {
			$code=$model.$helpers.$decl('shared<Box>','p',$failure==='missing'?'new Box()':'null')
				.$place('p').'['.$key.']'.$op.$rhs.';return 8;';
			$fixtures[$name.'_'.$failure]=['cpp'=>(new Cpp_Emitter())->emit($prepare($code)),'stdout_hex'=>'',
				'catch_'.($failure==='missing'?'out_of_range':'runtime_error')=>true];
		}
		if (!$string) {
			foreach (['/='=>'0','<<='=>'64'] as $operator=>$literal) {
				$bad=$prepare($model.$helpers.$decl('shared<Box>','p','make()').$place('p').'['.$key.']'.$operator.$literal.';return 8;');
				$fixtures[$name.'_failure_'.($literal==='0'?'divide':'shift')]=['cpp'=>(new Cpp_Emitter())->emit($bad),'stdout_hex'=>'','catch_domain_error'=>true];
			}
		}
		$empty_rhs = $function($element, 'erase', $ref($type, 'items'), $decl($type, 'fresh', '[]') . $v('items') . '=' . $v('fresh') . ';return ' . $rhs . ';');
		$bad = $prepare($decl($type, 'items', '[]') . $insert($v('items'), $first) . $empty_rhs
			. $v('items') . '[' . $key . ']' . $op . 'erase(' . $v('items') . ');return 8;');
		$fixtures[$name . '_removed_by_rhs'] = ['cpp' => (new Cpp_Emitter())->emit($bad), 'stdout_hex' => '', 'catch_out_of_range' => true];
		$private=match($flavor) {
			flavor::php=>str_replace('public '.$type,'private '.$type,$model),
			flavor::cpp=>$record('Box','private: '.$field($type,'items')),
			flavor::js=>$record('Box','private '.$field($type,'items')),
		};
		rejects(fn()=>$prepare($private.$decl('shared<Box>','p','new Box()').$place('p').'['.$key.']'.$op.$rhs.';return 8;'),'private');
		rejects(fn()=>$prepare($decl('string','text','"x"').$v('text').'[0]+=1;return 8;'),'Indexed compounds require');
		rejects(fn()=>$prepare($decl($type,'items','[]').$v('items').'['.$key.']'.($string?'-=1':'+="bad"').';return 8;'),'compound');
		$directory=sys_get_temp_dir().'/scpp-index-compound-'.bin2hex(random_bytes(8));mkdir($directory);
		try {
			$provider=$directory.'/library.'.$flavor->name;
			file_put_contents($directory.'/main.'.$flavor->name,$main);
			file_put_contents($directory.'/module.json',json_encode(['entry'=>'main.'.$flavor->name,'sources'=>['main.'.$flavor->name,basename($provider)]]));
			$project=new Incremental_Project($directory,$directory.'/out',new entry_target(31));$stamp=time();$caller=null;
			$initial=$model.$helpers;
			$edits=[$initial,$record('Box',$field($type,'items').$field('string','extra')).$helpers,
				str_replace($field($type,'items'),$field('int','items'),$initial),str_replace($field($type,'items'),'',$initial),$initial];
			foreach ($edits as $index=>$text) {
				file_put_contents($provider,$text);touch($provider,++$stamp);$fresh=new Incremental_Project($directory,$directory.'/fresh',new entry_target(31));
				if ($index===2 || $index===3) {
					$error=$index===3?'Unknown field':($is_vector?'appendable collection':'Keyed write requires');
					rejects(fn()=>$project->run(),$error);rejects(fn()=>$project->run(),$error);rejects(fn()=>$fresh->run(),$error);continue;
				}
				$project->run();$fresh->run();$caller??=$project->session()->entry->entry->ast;
				verify($caller===$project->session()->entry->entry->ast,'Caller reparsed');$files=artifacts($directory.'/out');verify($files===artifacts($directory.'/fresh'),'Fresh differs');
				$fixtures[$name.'_edit_'.$index]=['files'=>$files,'stdout_hex'=>bin2hex($expected)];
				verify($project->run()['preparation']['prepared']===0,'No-op prepared bodies');
			}
		} finally {
			remove_tree($directory);
		}
	}
}
if (isset($argv[1])) file_put_contents($argv[1],json_encode($fixtures,JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT));
echo "Indexed compounds: operators, expression receivers, lifetime and incremental recovery passed\n";
