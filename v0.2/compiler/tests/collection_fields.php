<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/compilation.php';

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
	$prepare = static fn(string $text) => (new Source_Frontend())->prepare(new source_file('collection-fields', $text, $flavor), new entry_target(31));
	$member = $flavor === flavor::js ? '.' : '->';
	$field = static fn(string $type, string $name): string => match ($flavor) {
		flavor::php => 'public ' . $type . ' $' . $name . ';',
		flavor::cpp => $type . ' ' . $name . ';',
		flavor::js => $name . ':' . $type . ';',
	};
	$record = static fn(string $name, string $fields): string => 'struct ' . $name . '{' . $fields . '}' . ($flavor === flavor::cpp ? ';' : '');
	$empty_local = static fn(string $name): string => match ($flavor) {
		flavor::php => '$' . $name . ' Outer;', flavor::cpp => 'Outer ' . $name . ';', flavor::js => 'let ' . $name . ':Outer;',
	};
	$ref = static fn(string $type, string $name): string => $flavor === flavor::js ? '&' . $name . ':' . $type : $type . ' &' . $v($name);
	foreach (['vector<int>', 'vector<string>', 'hash<int,int>', 'hash<int,string>', 'hash<string,int>', 'hash<string,string>'] as $type) {
		$is_vector = str_starts_with($type, 'vector');
		$string = str_starts_with($type, 'vector<string') || str_starts_with($type, 'hash<string');
		$key = str_ends_with($type, ',string>') ? '"key"' : '0';
		$element = $string ? 'string' : 'int';
		$first = $string ? '"long heap-backed text with\\0NUL"' : '7';
		$second = $string ? '"changed"' : '9';
		$bytes = $string ? "long heap-backed text with\0NUL" : '7'; $changed = $string ? 'changed' : '9';
		$path = static fn(string $name): string => $v($name) . $member . 'inner' . $member . 'items';
		$insert = static fn(string $place, string $value): string => $place . '[' . ($is_vector ? '' : $key) . ']=' . $value . ';';
		$body = $insert($v('this') . $member . 'items', $first);
		$method = match ($flavor) {
			flavor::php => 'public ' . $function('void', 'fill', '', $body),
			flavor::cpp => $function('void', 'fill', '', $body),
			flavor::js => 'fill():void {' . $body . '}',
		};
		$model = $record('Inner', $field($type, 'items') . $method) . $record('Outer', $field('Inner', 'inner'));
		$helpers = $function($element, 'replace_owner', $ref('shared<Outer>', 'owner'), $v('owner') . '=new Outer();return ' . $second . ';')
			. $function($type, 'copy', $param($type, 'items'), 'return ' . $v('items') . ';');
		$main = $empty_local('a') . 'echo count(' . $path('a') . '),"|";'
			. $v('a') . $member . 'inner' . $member . 'fill();' . $decl('Outer', 'b', $v('a'))
			. $path('b') . '[' . $key . ']=' . $second . ';'
			. $decl($type, 'local', 'copy(' . $path('a') . ')') . $v('local') . '[' . $key . ']=' . $second . ';'
			. 'echo ' . $path('a') . '[' . $key . '],"|",' . $path('b') . '[' . $key . '],"|";'
			. $path('b') . '=' . $path('a') . ';' . $path('b') . '=' . $path('b') . ';'
			. 'echo ' . $path('b') . '[' . $key . '],"|";'
			. $decl('shared<Outer>', 'p', 'new Outer()') . $insert($path('p'), $first)
			. $decl('shared<Outer>', 'q', $v('p')) . $path('p') . '[' . $key . ']=' . $second . ';'
			. 'echo ' . $path('q') . '[' . $key . '],"|";'
			. $path('p') . '[' . $key . ']=replace_owner(' . $v('p') . ');'
			. 'echo ' . $path('q') . '[' . $key . '],"|",count(' . $path('p') . '),"|";'
			. $insert($path('p'), $first)
			. ($is_vector ? $insert($path('p'), 'replace_owner(' . $v('p') . ')') : $path('p') . '[' . $key . ']=replace_owner(' . $v('p') . ');')
			. 'echo count(' . $path('p') . ');return 8;';
		$expected = '0|' . $bytes . '|' . $changed . '|' . $bytes . '|' . $changed . '|' . $changed . '|0|0';
		$start = hrtime(true); $entry = $prepare($model . $helpers . $main); $before=serialize($entry);
		$cpp = (new Cpp_Emitter())->emit($entry);
		verify($before === serialize($entry), 'Lowering mutated facts');
		$name=$flavor->name . '_' . preg_replace('/[^a-z]+/', '_', $type);
		$fixtures[$name] = ['cpp'=>$cpp,'stdout_hex'=>bin2hex($expected),'compiler_ms'=>(hrtime(true)-$start)/1e6];
		$bad = $prepare($model . $empty_local('a') . 'echo ' . $path('a') . '[' . $key . '];return 8;');
		$fixtures[$name.'_missing']=['cpp'=>(new Cpp_Emitter())->emit($bad),'stdout_hex'=>'','catch_out_of_range'=>true];
		$bad = $prepare($model . $decl('shared<Outer>','p','null') . $insert($path('p'),$first) . 'return 8;');
		$fixtures[$name.'_empty_owner']=['cpp'=>(new Cpp_Emitter())->emit($bad),'stdout_hex'=>'','catch_runtime_error'=>true];
		$take = $function('void','consume_items',$ref($type,'items'),'return;');
		rejects(fn()=>$prepare($model . $take . $empty_local('a') . 'consume_items('.$path('a').');return 8;'),'named local or parameter');
		$private = match ($flavor) {
			flavor::php => str_replace('public '.$type, 'private '.$type, $model),
			flavor::cpp => str_replace($field($type,'items'),'private: '.$field($type,'items').' public:', $model),
			flavor::js => str_replace($field($type,'items'),'private '.$field($type,'items'),$model),
		};
		rejects(fn()=>$prepare($private . $empty_local('a') . $insert($path('a'),$first).'return 8;'),'private');
		$directory=sys_get_temp_dir().'/scpp-collection-fields-'.bin2hex(random_bytes(8));mkdir($directory);
		try {
			$path_file=$directory.'/model.'.$flavor->name;
			file_put_contents($directory.'/main.'.$flavor->name,$helpers.$main);
			file_put_contents($directory.'/module.json',json_encode(['entry'=>'main.'.$flavor->name,'sources'=>['main.'.$flavor->name,basename($path_file)]]));
			$project=new Incremental_Project($directory,$directory.'/out',new entry_target(31));$stamp=time();$caller=null;
			$edits=[$model,$record('Inner',$field($type,'items').$field('string','extra').$method).$record('Outer',$field('Inner','inner')),
				str_replace($field($type,'items'),$field('int','items'),$model),str_replace($field($type,'items'),'',$model),$model];
			foreach ($edits as $index=>$text) {
				file_put_contents($path_file,$text);touch($path_file,++$stamp);
				$fresh=new Incremental_Project($directory,$directory.'/fresh',new entry_target(31));
				if ($index===2 || $index===3) {
					$error=$index===3?'Unknown field':($is_vector?'appendable collection':'Keyed write requires');
					rejects(fn()=>$project->run(),$error);rejects(fn()=>$project->run(),$error);rejects(fn()=>$fresh->run(),$error);continue;
				}
				$project->run();$fresh->run();$caller??=$project->session()->entry->entry->ast;
				verify($caller===$project->session()->entry->entry->ast,'Caller reparsed');
				$files=artifacts($directory.'/out');verify($files===artifacts($directory.'/fresh'),'Fresh output differs');
				$fixtures[$name.'_edit_'.$index]=['files'=>$files,'stdout_hex'=>bin2hex($expected)];
				verify($project->run()['preparation']['prepared']===0,'No-op prepared bodies');
			}
		} finally {
			remove_tree($directory);
		}
	}
}
if (isset($argv[1])) file_put_contents($argv[1],json_encode($fixtures,JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT));
echo "Collection fields: defaults, copies, mutations and incremental recovery passed\n";
