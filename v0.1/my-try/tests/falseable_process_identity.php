<?php
namespace scpp\compiler;
require_once __DIR__.'/operators.php';
$source=dirname(__DIR__,3).'/tests/php/process/strict_level_01/strict_process_001_shell_exec_contract.phs';
// The retained compiler accepts a directory; isolate the unchanged fixture bytes.
$dir=sys_get_temp_dir().'/scpp_s04_'.bin2hex(random_bytes(6));mkdir($dir);$path=$dir.'/main.phs';
try {
 file_put_contents($path,file_get_contents($source));Compiler_Lifecycle::reset();$compiler=new Compiler(new cpp_lowering_options(false));$compiler->init([$dir]);$compiler->exec_cpp();
 file_put_contents($argv[1].'/original.cpp',CPP_Probe::text(Model::$cpp_files));
} finally {unlink($path);rmdir($dir);}
operator_test_source('function branch(result_or_false<string> $v):bool{return $v === false;} function reverse(result_or_false<string> $v):bool{return false === $v;} function different(result_or_false<string> $v):bool{return $v !== false;} function differentreverse(result_or_false<string> $v):bool{return false !== $v;}');
$cpp=str_replace('int main(int argc, char** argv)','int unused_entry(int argc, char** argv)',CPP_Probe::text(Model::$cpp_files));
$cpp.="\n#include \"".__DIR__."/cross_identity_support.hpp\"\nusing namespace identity_probe;\n";
$cpp.='int main(){long cases=0;for(auto state:states<2,std::string>()){auto value=make<2,scpp::string_t>(state);bool expected=state.kind==state_kind::false_value;if(function_branch(value).native_value()!=expected||function_reverse(value).native_value()!=expected||function_different(value).native_value()==expected||function_differentreverse(value).native_value()==expected||!same(value,state))return 1;++cases;}std::cout<<"branch_cases="<<cases<<"\\n";}';
file_put_contents($argv[1].'/controls.cpp',$cpp);echo "unchanged S04 source and deterministic state controls prepared\n";
