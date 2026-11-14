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

$fixtures = [];
foreach (flavor::cases() as $flavor) {
	$sep = $flavor === flavor::js ? '.' : '::';
	$var = $flavor === flavor::php ? '$n' : 'n';
	$enum = static fn(string $name): string => $flavor === flavor::php
		? 'enum Kind {case First=1;case ' . $name . '=300;}' : 'enum Kind {First=1,' . $name . '=300}';
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('enum-name', $source, $flavor), new entry_target(31));
	$counter = match ($flavor) {
		flavor::php => 'function pick(int &$n):Kind{$n+=1;return Kind::First;}$n int=0;',
		flavor::cpp => 'Kind pick(int &n){n+=1;return Kind::First;}int n=0;',
		flavor::js => 'function pick(&n:int):Kind{n+=1;return Kind.First;}let n:int=0;',
	};
	$start = hrtime(true);
	$cpp = (new Cpp_Emitter())->emit($prepare($enum('Long_Case_Name_Over_Inline_Storage') . $counter
		. 'echo enum_name(pick(' . $var . ')),":",' . $var . ',":",enum_name(Kind' . $sep . 'Long_Case_Name_Over_Inline_Storage);return 8;'));
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('First:1:Long_Case_Name_Over_Inline_Storage'), 'compiler_ms' => (hrtime(true)-$start)/1e6];
	foreach (['1','true','"First"','null'] as $arg) rejects(fn() => $prepare('echo enum_name(' . $arg . ');return 8;'), 'enum_name requires an enum');
	rejects(fn() => $prepare('enum_name();return 8;'), 'enum_name requires one enum argument');
	$shadow = match ($flavor) {
		flavor::php => 'function enum_name(int $v):int{return $v;}',
		flavor::cpp => 'int enum_name(int v){return v;}',
		flavor::js => 'function enum_name(v:int):int{return v;}',
	};
	$fixtures[$flavor->name . '_shadow'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($shadow . 'echo enum_name(9);return 8;')), 'stdout_hex' => bin2hex('9')];
	$bad = (new Cpp_Emitter())->emit($prepare($enum('Last') . 'echo enum_name(Kind' . $sep . 'First);return 8;'));
	$bad = str_replace('scpp_enum_name_Kind(scpp_records::Kind::First)', 'scpp_enum_name_Kind(static_cast<scpp_records::Kind>(2))', $bad);
	verify(str_contains($bad, 'static_cast<scpp_records::Kind>(2)'), 'Invalid-native-value injection failed');
	$fixtures[$flavor->name . '_invalid_native'] = ['cpp' => $bad, 'stdout_hex' => '', 'catch_runtime_error' => 'Invalid value for enum helper'];
	$directory = sys_get_temp_dir() . '/scpp-enum-name-' . bin2hex(random_bytes(8)); mkdir($directory);
	try {
		$main = $directory . '/main.' . $flavor->name; $provider = $directory . '/kind.' . $flavor->name;
		file_put_contents($directory . '/module.json', json_encode(['entry'=>basename($main), 'sources'=>[basename($main),basename($provider)]]));
		$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31)); $stamp=time(); $caller=null;
		foreach (['Last','Renamed',null,'Restored',false,'Again'] as $index=>$name) {
			$source = 'echo ' . ($name === false ? 'enum_value' : 'enum_name') . '(enum_from_value(Kind' . $sep . 'class,300));return 8;';
			foreach ([$main=>$source,$provider=>$name===null?'':$enum($name===false?'Restored':$name)] as $path=>$text) {
				if (!is_file($path)||file_get_contents($path)!==$text) {
					file_put_contents($path,$text);touch($path,++$stamp);
				}
			}
			$fresh=new Incremental_Project($directory,$directory.'/fresh',new entry_target(31));
			if ($name===null) {
				rejects(fn()=>$project->run(),'enum_from_value target must be an enum');
				rejects(fn()=>$project->run(),'enum_from_value target must be an enum');
				rejects(fn()=>$fresh->run(),'enum_from_value target must be an enum');continue;
			}
			$project->run();$fresh->run();
			if ($index===1) verify($caller===$project->session()->entry->entry->ast,'Caller reparsed');
			$caller=$project->session()->entry->entry->ast;
			$files=artifacts($directory.'/out');verify($files===artifacts($directory.'/fresh'),'Fresh differs');
			verify(str_contains(implode('', $files),'scpp_enum_name_')===($name!==false),'Stale helper use');
			$fixtures[$flavor->name.'_edit_'.$index]=['files'=>$files,'stdout_hex'=>bin2hex($name===false?'300':$name)];
			verify($project->run()['preparation']['prepared']===0,'No-op prepares');
		}
	} finally {
		remove_tree($directory);
	}
}
if(isset($argv[1]))file_put_contents($argv[1],json_encode($fixtures,JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT));
echo "Enum name: native names, single evaluation and incremental/fresh recovery passed\n";
