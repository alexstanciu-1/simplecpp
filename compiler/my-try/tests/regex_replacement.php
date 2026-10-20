<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

/** Compile with normal registration, shared preparation and generated artifact publication. */
function replacement_compile(string $source): void
{
	Compiler_Lifecycle::reset();
	$module = new module('/regex_replacement', '/regex_replacement', '/regex_replacement');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
}
function replacement_check(bool $ok, string $message): void
{
	if (!$ok) {
		throw new \LogicException($message);
	}
}
$out = $argv[1];
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load(dirname(__DIR__, 3).'/runtime/metadata/modules', ['regex']));
$admission = [];
foreach (['regex_replace("/a/","X","aba",1);', 'regex_filter("/a/","X",vector<string>::["aba"]);', 'regex_filter("/a/","X",vector<string>::["aba"],1);'] as $source)
{
	$error = '';
	try {
		replacement_compile($source);
	}
	catch (\RuntimeException $e) {
		$error = $e->getMessage();
	}
	$admission[] = ['source'=>$source, 'diagnostic'=>$error];
}
if (($argv[2] ?? '') === '--baseline') {
	foreach ($admission as $row) {
		replacement_check($row['diagnostic'] !== '', 'Missing baseline source failure');
	}
	file_put_contents($out.'/baseline.json', json_encode($admission, JSON_PRETTY_PRINT)."\n");
	exit(0);
}
foreach ($admission as $row) {
	replacement_check($row['diagnostic'] === '', 'Source binding still missing');
}
$source = <<<'PHS'
function replace4(const string &$p,const string &$r,const string &$s,const int &$n):result_or_false<string>{return regex_replace($p,$r,$s,$n);}
function filter4(const string &$p,const string &$r,const vector<string> &$s,const int &$n):result_or_false<vector<string>>{return regex_filter($p,$r,$s,$n);}
function replace3():result_or_false<string>{return regex_replace("/(a)/",'${1}${1}',"aba");}
function filter3():result_or_false<vector<string>>{return regex_filter("/(a)/",'$1$1',vector<string>::["aba","z"]);}
function pat(string &$p,int &$t):string{$t=$t*10+1;return $p;}
function rep(string &$p,string &$r,int &$t):string{$t=$t*10+2;$p="/z/";return $r;}
function subject(string &$r,string &$s,int &$t):string{$t=$t*10+3;$r="Y";return $s;}
function rows(string &$r,vector<string> &$s,int &$t):vector<string>{$t=$t*10+3;$r="Y";return $s;}
function limit(string &$s,int $n,int &$t):int{$t=$t*10+4;$s="changed";return $n;}
function rowlimit(vector<string> &$s,int $n,int &$t):int{$t=$t*10+4;$s[0]="changed";return $n;}
function ordered(string &$p,string &$r,string &$s,int $n,int &$t):result_or_false<string>{return regex_replace(pat($p,$t),rep($p,$r,$t),subject($r,$s,$t),limit($s,$n,$t));}
function filtered(string &$p,string &$r,vector<string> &$s,int $n,int &$t):result_or_false<vector<string>>{return regex_filter(pat($p,$t),rep($p,$r,$t),rows($r,$s,$t),rowlimit($s,$n,$t));}
PHS;
replacement_compile($source);
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', CPP_Probe::text(Model::$cpp_files));
$cpp .= <<<'CPP'

#include "regex_replacement_oracle.hpp"
int main(){
 using S=scpp::string_t;using I=scpp::int_t<>;using V=scpp::vector_t<S>;
 if(regex_replacement_oracle(function_replace4,function_filter4)!=0)return 1;
 if(function_replace3().value().native_value()!="aabaa")return 2;
 auto f=function_filter3();if(!f.has_value().native_value() || f.value().size()!=1 || f.value().at(I{0}).native_value()!="aabaa")return 3;
 I n{1};auto limited=function_replace4(S{"/a/"},S{"X"},S{"aba"},n);
 if(n.native_value()!=1 || limited.value().native_value()!="Xba")return 4;
 for(std::int64_t lim:{-2,0,1}){
  S p{"/a/"},r{"X"},s{"aba"};I trace{0};bool caught=false;
  try{auto result=function_ordered(p,r,s,I{lim},trace);if(lim>=0 && result.value().native_value()!=(lim==0?"aba":"Xba"))return 5;}
  catch(const scpp::ValueError&){caught=true;}
  if(caught!=(lim<0) || trace.native_value()!=1234 || p.native_value()!="/z/" || r.native_value()!="Y" || s.native_value()!="changed")return 6;
  p=S{"/a/"};r=S{"X"};V v;v.append(S{"aba"});trace=I{0};caught=false;
  try{auto result=function_filtered(p,r,v,I{lim},trace);if(lim==0 && result.value().size()!=0)return 7;if(lim==1 && result.value().at(I{0}).native_value()!="Xba")return 8;}
  catch(const scpp::ValueError&){caught=true;}
  if(caught!=(lim<0) || trace.native_value()!=1234 || v.at(I{0}).native_value()!="changed")return 9;
 }
 S p{"/broken"},r{"X"},s{"aba"};I trace{0};
 if(!function_ordered(p,r,s,I{1},trace).is_false().native_value() || trace.native_value()!=1234)return 10;
 std::cout<<"defaults; limit preserved; ordered snapshots; provider failures after arguments\n";
}
CPP;
file_put_contents($out.'/behavior.cpp', $cpp);
$rejections = [];
foreach (['regex_filter("/a/","x",vector<int>::[1]);', 'regex_replace("/a/","x","a",1.0);', 'regex_filter("/a/","x",vector<string>::[],false);', '$n=0;regex_replace("/a/","x","a",1,$n);', '$n=0;regex_filter("/a/","x",vector<string>::[],1,$n);'] as $bad)
{
	$error = '';
	try {
		replacement_compile($bad);
	}
	catch (\RuntimeException $e) {
		$error = $e->getMessage();
	}
	replacement_check($error !== '' && Model::$cpp_files->is_empty(), 'Invalid regex call admitted');
	$rejections[] = ['source'=>$bad, 'diagnostic'=>$error];
}
$directory = sys_get_temp_dir().'/regex_recovery_'.bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory.'/main.phs';
$recoveries = 0;
try
{
	foreach (['regex_replace("/a/","x","a",1);', 'regex_filter("/a/","x",vector<string>::["a"],1);'] as $valid)
	{
		file_put_contents($path, $valid);
		Compiler_Lifecycle::reset();
		$compiler = new Compiler();
		$compiler->init([$directory]);
		$compiler->exec_cpp();
		$fresh = CPP_Probe::text(Model::$cpp_files);
		file_put_contents($path, str_replace(',1)', ',1.0)', $valid));
		foreach ([[$path], []] as $changes)
		{
			$failed = false;
			try {
				$compiler->update_cpp($changes);
			}
			catch (\RuntimeException $e) {
				$failed = true;
			}
			replacement_check($failed && Model::$cpp_files->is_empty() && Model::$prepared_files->is_empty(), 'Stale regex artifacts');
		}
		file_put_contents($path, $valid);
		$compiler->update_cpp([$path]);
		replacement_check(CPP_Probe::text(Model::$cpp_files) === $fresh, 'Regex repair differs');
		$compiler->update_cpp([]);
		replacement_check(CPP_Probe::text(Model::$cpp_files) === $fresh, 'Regex no-op differs');
		++$recoveries;
	}
}
finally {
	unlink($path);
	rmdir($directory);
}
file_put_contents($out.'/checks.json', json_encode(['admission'=>$admission, 'rejections'=>$rejections, 'recoveries'=>$recoveries], JSON_PRETTY_PRINT)."\n");
