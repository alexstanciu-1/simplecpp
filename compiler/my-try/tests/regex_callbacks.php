<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function callback_compile(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/regex_callbacks', '/regex_callbacks', '/regex_callbacks');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
	return CPP_Probe::text(Model::$cpp_files);
}
function callback_check(bool $ok, string $message): void
{
	if (!$ok) { throw new \LogicException($message); }
}
$out = $argv[1];
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load(dirname(__DIR__, 3).'/runtime/metadata/modules', ['regex']));
$prefix = '$f function<string(vector<string>)> = fn($m) => "X"; $h hash<function<string(vector<string>)>> = {"/a/": $f}; ';
$forms = [
	'regex_replace_callback("/a/",$f,"aba");',
	'regex_replace_callback("/a/",$f,"aba",1);',
	'regex_replace_callback_array($h,"aba");',
	'regex_replace_callback_array($h,"aba",1);',
];
$admission = [];
foreach ($forms as $form) {
	$error = '';
	try { callback_compile($prefix.$form); }
	catch (\RuntimeException $exception) { $error = $exception->getMessage(); }
	$admission[] = ['source'=>$form, 'diagnostic'=>$error];
}
if (($argv[2] ?? '') === '--baseline') {
	foreach ($admission as $row) { callback_check($row['diagnostic'] !== '', 'Expected missing callback binding'); }
	file_put_contents($out.'/baseline.json', json_encode($admission, JSON_PRETTY_PRINT)."\n");
	exit(0);
}
foreach ($admission as $row) { callback_check($row['diagnostic'] === '', $row['diagnostic']); }
$source = <<<'PHS'
function replace4(const string &$p,const function<string(vector<string>)> &$f,const string &$s,const int &$n):result_or_false<string>{return regex_replace_callback($p,$f,$s,$n);}
function table3(const hash<function<string(vector<string>)>> &$h,const string &$s,const int &$n):result_or_false<string>{return regex_replace_callback_array($h,$s,$n);}
function pat(string &$p,int &$t):string{$t=$t*10+1;return $p;}
function cb(string &$p,function<string(vector<string>)> &$f,int &$t):function<string(vector<string>)>{$t=$t*10+2;$p="/z/";return $f;}
function subject(function<string(vector<string>)> &$f,string &$s,int &$t):string{$t=$t*10+3;$f=fn($m)=>"Y";return $s;}
function limit(string &$s,int $n,int &$t):int{$t=$t*10+4;$s="changed";return $n;}
function ordered(string &$p,function<string(vector<string>)> &$f,string &$s,int $n,int &$t):result_or_false<string>{
	return regex_replace_callback(pat($p,$t),cb($p,$f,$t),subject($f,$s,$t),limit($s,$n,$t));
}
function callbacks(hash<function<string(vector<string>)>> &$h,int &$t):hash<function<string(vector<string>)>>{$t=$t*10+1;return $h;}
function tablesubject(hash<function<string(vector<string>)>> &$h,string &$s,int &$t):string{$t=$t*10+2;$h["/a/"]=fn($m)=>"Y";return $s;}
function tablelimit(string &$s,int $n,int &$t):int{$t=$t*10+3;$s="changed";return $n;}
function tableordered(hash<function<string(vector<string>)>> &$h,string &$s,int $n,int &$t):result_or_false<string>{
	return regex_replace_callback_array(callbacks($h,$t),tablesubject($h,$s,$t),tablelimit($s,$n,$t));
}
function defaults():string {
	$prefix="C";
	$f function<string(vector<string>)> = function(vector<string> $matches) use ($prefix):string{return $prefix.$matches[1];};
	$h hash<function<string(vector<string>)>> = {"/a/": fn($m)=>"b", "/b/": fn($m)=>"X"};
	$a string=regex_replace_callback("/(a)/",$f,"aba");
	$b string=regex_replace_callback_array($h,"aa");
	return $a.":".$b;
}
PHS;
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', callback_compile($source));
$cpp .= <<<'CPP'

#include "regex_callbacks_oracle.hpp"
int main() {
	using S=scpp::string_t;using I=scpp::int_t<>;
	using F=std::function<S(scpp::vector_t<S>)>;using H=scpp::hash_t<F,S>;
	if(regex_callbacks_oracle(function_replace4,function_table3)!=0)return 1;
	if(function_defaults().native_value()!="CabCa:XX")return 2;
	for(std::int64_t n:{-2,0,1}) {
		S p{"/a/"},s{"aba"};F f=[](auto){return S{"X"};};I t{0};bool caught=false;
		try {auto r=function_ordered(p,f,s,I{n},t);if(r.value().native_value()!=(n==0?"aba":"Xba"))return 3;}
		catch(const scpp::ValueError&){caught=true;}
		if(caught!=(n<0)||t.native_value()!=1234||p.native_value()!="/z/"||s.native_value()!="changed"||f({}).native_value()!="Y")return 4;
		H h;h.set(S{"/a/"},[](auto){return S{"X"};});s=S{"aba"};t=I{0};caught=false;
		try {auto r=function_tableordered(h,s,I{n},t);if(r.value().native_value()!=(n==0?"aba":"Xba"))return 5;}
		catch(const scpp::ValueError&){caught=true;}
		if(caught!=(n<0)||t.native_value()!=123||s.native_value()!="changed"||h.at(S{"/a/"} )({}).native_value()!="Y")return 6;
	}
	std::cout<<"source defaults; owned captures; ordered callback and table snapshots\n";
}
CPP;
file_put_contents($out.'/behavior.cpp', $cpp);
$rejections = [];
foreach ([
	'regex_replace_callback("/a/",fn(int $n):string=>"x","a");',
	'regex_replace_callback("/a/",fn(vector<string> $m):int=>1,"a");',
	'regex_replace_callback("/a/",$f,"a",1.0);',
	'regex_replace_callback_array(hash<string>::{},"a");',
	'regex_replace_callback("/a/",$f,"a",1,0);',
	'regex_replace_callback_array($h,"a",1,0);',
	'$n="x";$borrow=function(vector<string> $m) use (&$n):string{return $n;};regex_replace_callback("/a/",$borrow,"a");',
] as $bad) {
	$error = '';
	try { callback_compile($prefix.$bad); }
	catch (\RuntimeException $exception) { $error = $exception->getMessage(); }
	callback_check($error !== '' && Model::$cpp_files->is_empty(), 'Invalid callback source admitted');
	$rejections[] = ['source'=>$bad, 'diagnostic'=>$error];
}
$directory = sys_get_temp_dir().'/callback_recovery_'.bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory.'/main.phs';
try {
	foreach ([$forms[1], $forms[3]] as $form) {
		$valid = $prefix.$form;
		file_put_contents($path, $valid);
		Compiler_Lifecycle::reset();
		$compiler = new Compiler();
		$compiler->init([$directory]);
		$compiler->exec_cpp();
		$fresh = CPP_Probe::text(Model::$cpp_files);
		file_put_contents($path, str_replace(',1)', ',1.0)', $valid));
		foreach ([[$path], []] as $changes) {
			$failed = false;
			try { $compiler->update_cpp($changes); }
			catch (\RuntimeException $exception) { $failed = true; }
			callback_check($failed && Model::$cpp_files->is_empty(), 'Failed callback update published stale output');
		}
		file_put_contents($path, $valid);
		$compiler->update_cpp([$path]);
		callback_check(CPP_Probe::text(Model::$cpp_files) === $fresh, 'Callback repair differs');
		$compiler->update_cpp([]);
		callback_check(CPP_Probe::text(Model::$cpp_files) === $fresh, 'Callback no-op differs');
	}
} finally { unlink($path); rmdir($directory); }
file_put_contents($out.'/checks.json', json_encode(['admission'=>$admission,'rejections'=>$rejections,'recovery_cycles'=>2], JSON_PRETTY_PRINT)."\n");
