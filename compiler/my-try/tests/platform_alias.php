<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
function platform_check(bool $ok,string $why):void { if(!$ok){throw new \LogicException($why);} }
function platform_compile(string $source):string {
 Compiler_Lifecycle::reset();
 $module=new module('/platform','/platform','/platform');
 $file=new file();$file->path='main.phs';$file->content=$source;
 Source_Registry::add($module,$file);Model::$modules->add($module->name,$module);
 (new Compiler())->exec_cpp();return CPP_Probe::text(Model::$cpp_files);
}
$out=$argv[1];$root=dirname(__DIR__,3);
$input=Runtime_Catalog_Loader::load($root.'/runtime/metadata/modules',['filesystem']);
Compiler_Lifecycle::configure_runtime($input);
$original=file_get_contents($root.'/tests/php/strings/strict_level_01/strict_strings_005_php_microtime_static_call.phs');
$admission=[];
foreach ([$original,'microtime();','microtime(true);','php::microtime();','fs_is_windows();'] as $source) {
 $error='';try{platform_compile($source);}catch(\RuntimeException $e){$error=$e->getMessage();}
 $admission[]=['source'=>$source,'diagnostic'=>$error];
}
if(($argv[2]??'')==='--baseline') {
 foreach($admission as $row){platform_check($row['diagnostic']!=='','Baseline unexpectedly admitted source');}
 file_put_contents($out.'/baseline.json',json_encode($admission,JSON_PRETTY_PRINT)."\n");exit(0);
}
foreach($admission as $row){platform_check($row['diagnostic']==='',$row['diagnostic']);}
file_put_contents($out.'/original.cpp',platform_compile($original));
$source= <<<'PHS'
function free_text():string{return microtime();}
function qualified_text():string{return php::microtime();}
function free_number(bool $mode):float{return microtime($mode);}
function qualified_number(bool $mode):float{return php::microtime($mode);}
function nextmode(bool &$mode,int &$calls):bool{$calls++;$old=$mode;$mode=false;return $old;}
function effectmode(bool &$mode,int &$calls):float{return php::microtime(nextmode($mode,$calls));}
function platform():bool{return fs_is_windows();}
function policy(string $path,bool $windows):string{if($windows){return "windows";}return $path;}
function envname(string $name,int &$calls):string{$calls++;return $name;}
function environment(string $name,int &$calls):result_or_false<string>{return getenv(envname($name,$calls));}
PHS;
$cpp=str_replace('int main(int argc, char** argv)','int unused_entry(int argc, char** argv)',platform_compile($source));
$cpp.= <<<'CPP'

#include "core/string_support.hpp"
#include <chrono>
#include <regex>
#include <filesystem>
int main(){
 using S=scpp::string_t;using B=scpp::bool_t;using I=scpp::int_t<>;
 auto lo=std::chrono::duration<double>(std::chrono::system_clock::now().time_since_epoch()).count()-1;
 auto a=function_freeU_text().native_value(),b=function_qualifiedU_text().native_value();
 auto c=function_freeU_number(B{true}).native_value(),d=function_qualifiedU_number(B{true}).native_value();
 auto hi=std::chrono::duration<double>(std::chrono::system_clock::now().time_since_epoch()).count()+1;
 for(const auto& t:{a,b}){
  if(!std::regex_match(t,std::regex{"0\\.[0-9]{8} [0-9]+"}))return 1;
  auto value=std::stod(t.substr(0,t.find(' ')))+std::stod(t.substr(t.find(' ')+1));
  if(value<lo||value>hi)return 2;
 }
 if(c<lo||c>hi||d<lo||d>hi)return 3;
 for(auto fn:{function_freeU_number,function_qualifiedU_number}){bool caught=false;try{fn(B{false});}catch(const std::logic_error&){caught=true;}if(!caught)return 4;}
 B mode{true};I modes{0};if(function_effectmode(mode,modes).native_value()<lo||mode.native_value()||modes.native_value()!=1)return 11;
 if(function_platform().native_value()!=(std::filesystem::path::preferred_separator=='\\')||function_platform().native_value())return 5;
 if(function_policy(S{"a\\b"},B{false}).native_value()!="a\\b"||function_policy(S{"a\\b"},B{true}).native_value()!="windows")return 6;
 I calls{0};if(!function_environment(S{"SCPP_ALIAS_MISSING"},calls).is_false().native_value())return 7;
 auto empty=function_environment(S{"SCPP_ALIAS_EMPTY"},calls);if(!empty.has_value().native_value()||!empty.value().native_value().empty())return 8;
 auto value=function_environment(S{"SCPP_ALIAS_VALUE"},calls);if(value.value().native_value()!="original")return 9;
 bool caught=false;try{function_environment(S{std::string{"SCPP_ALIAS_VALUE\0suffix",23}},calls);}catch(const scpp::ValueError&){caught=true;}
 if(!caught||calls.native_value()!=4)return 10;
 std::cout<<"four clock forms; false throws; Linux target; two policy branches; environment states and once-only names\n";
}
CPP;
file_put_contents($out.'/behavior.cpp',$cpp);
file_put_contents($out.'/shadow.cpp',platform_compile('class php {public static function microtime(bool $mode):int{return 7;}} echo php::microtime(true);'));
file_put_contents($out.'/free-shadow.cpp',platform_compile('function microtime():int{return 9;} echo microtime();$n float=php::microtime(true);if($n<=0.0){return 1;}'));
$rejections=[];
foreach(['microtime(1);','php::microtime(1);','php::microtime(true,false);','fs_is_windows(1);','getenv();','getenv(1);','getenv("x","y");','class php {} php::microtime(true);','new php();','function bad(php $x):void{}','php::unknown();','other::microtime();','php::strlen("x");'] as $invalid){
 $error='';try{platform_compile($invalid);}catch(\RuntimeException $e){$error=$e->getMessage();}
 platform_check($error!==''&&Model::$cpp_files->is_empty(),'Invalid source emitted: '.$invalid);$rejections[]=['source'=>$invalid,'diagnostic'=>$error];
}
// Unedited caller must observe a source type being added, broken, then removed.
$dir=$out.'/retained';mkdir($dir);$types=$dir.'/types.phs';$caller=$dir.'/main.phs';
file_put_contents($types,'');file_put_contents($caller,'function caller():float{return php::microtime(true);}');
Compiler_Lifecycle::reset();$compiler=new Compiler();$compiler->init([$dir]);$compiler->select_entry('main','main.phs');$compiler->exec_cpp();
file_put_contents($out.'/retained-alias.cpp',str_replace('int main(int argc, char** argv)','int unused_entry(int argc, char** argv)',CPP_Probe::text(Model::$cpp_files))."\nint main(){return function_caller().native_value()>1000.0?0:1;}\n");
file_put_contents($types,'class php {public static function microtime(bool $mode):float{return 7.0;}}');
$compiler->sync([$types]);$compiler->prepare();$compiler->cpp();
$text=CPP_Probe::text(Model::$cpp_files);platform_check(!str_contains($text,'scpp::php::microtime('),'Added type did not shadow alias');
file_put_contents($out.'/retained-shadow.cpp',str_replace('int main(int argc, char** argv)','int unused_entry(int argc, char** argv)',$text)."\nint main(){return function_caller().native_value()==7.0?0:1;}\n");
file_put_contents($types,'class php {}');$compiler->sync([$types]);
$error='';try{$compiler->prepare();}catch(\RuntimeException $e){$error=$e->getMessage();}
platform_check($error!==''&&Model::$cpp_files->is_empty(),'Missing source method fell back');
$compiler->sync([]);$retry='';try{$compiler->prepare();}catch(\RuntimeException $e){$retry=$e->getMessage();}platform_check($retry===$error,'No-edit failure changed');
file_put_contents($types,'');$compiler->sync([$types]);$compiler->prepare();$compiler->cpp();
$text=CPP_Probe::text(Model::$cpp_files);platform_check(str_contains($text,'scpp::php::microtime('),'Type removal did not restore alias');
$compiler->sync([]);$compiler->prepare();$compiler->cpp();platform_check($text===CPP_Probe::text(Model::$cpp_files),'No-op recovery changed output');
Compiler_Lifecycle::reset();$compiler=new Compiler();$compiler->init([$dir]);$compiler->select_entry('main','main.phs');$compiler->exec_cpp();platform_check($text===CPP_Probe::text(Model::$cpp_files),'Fresh and retained outputs differ');
file_put_contents($out.'/checks.json',json_encode(['admission'=>$admission,'rejections'=>$rejections,'retained'=>'alias / source type / missing method / retry / removed type / no-op / fresh'],JSON_PRETTY_PRINT)."\n");
echo "Platform aliases passed\n";
