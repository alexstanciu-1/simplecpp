<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$families = [
	'int' => ['int', 'scpp::int_t<>'],
	'nullable' => ['?int', 'scpp::nullable<scpp::int_t<>>'],
	'result' => ['result<int>', 'scpp::result<scpp::int_t<>>'],
	'false' => ['result_or_false<int>', 'scpp::result_or_false<scpp::int_t<>>'],
	'bool' => ['result_or_bool<int>', 'scpp::result_or_bool<scpp::int_t<>>'],
	'mixed' => ['mixed', 'scpp::mixed_t'],
	'dynamic' => ['dynamic', 'scpp::dynamic_t<>'],
];
foreach ($families as $name => [$type, $cpp]) {
	$boxed = ($name === 'mixed') || ($name === 'dynamic');
	$return_type = $boxed ? $type : 'int';
	$source = 'function left(' . $type . ' $a,int &$events):' . $type . '{$events=$events*10+1;return $a;} '
		. 'function right(' . $type . ' &$a,' . $type . ' $b,int &$events):' . $type . '{$events=$events*10+2;$a=99;return $b;} ';
	foreach (['shl' => '<<', 'shr' => '>>'] as $operation => $symbol) {
		$source .= 'function ' . $operation . '(' . $type . ' &$a,' . $type . ' $b,int &$events):' . $return_type
			. '{return left($a,$events) ' . $symbol . ' right($a,$b,$events);} ';
		if (!$boxed) {
			$source .= 'function ' . $operation . 'store(' . $type . ' &$a,' . $type . ' $b,int &$events):void'
				. '{$a ' . $symbol . '= right($a,$b,$events);} ';
		}
	}
	Compiler_Lifecycle::reset();
	$module = new module('/checked-shifts', '/checked-shifts', '/checked-shifts');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
	$text = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', CPP_Probe::text(Model::$cpp_files));
	$observer = "\n#include \"checked_shifts_source.hpp\"\nint main(){using W=" . $cpp
		. '; observe_binary<W>(function_shl,6);observe_binary<W>(function_shr,1);';
	if (!$boxed) {
		$observer .= 'observe_store<W>(function_shlstore,6);observe_store<W>(function_shrstore,1);';
	}
	$absent = [
		'nullable' => ['scpp::null'],
		'result' => ['scpp::error_t{scpp::string_t{"left failure"}}'],
		'false' => ['scpp::false_sentinel'],
		'bool' => ['scpp::false_sentinel', 'scpp::true_sentinel'],
	];
	foreach ($absent[$name] ?? [] as $state) {
		$observer .= 'observe_guards<W>(function_shl,function_shlstore,W{' . $state . '});'
			. 'observe_guards<W>(function_shr,function_shrstore,W{' . $state . '});';
	}
	if ($boxed) {
		$observer .= 'observe_kinds<W>(function_shl);observe_kinds<W>(function_shr);';
	}
	$observer .= "}\n";
	file_put_contents($argv[1] . '/' . $name . '.cpp', $text . $observer);
}
