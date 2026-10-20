<?php
namespace scpp\compiler;
require_once __DIR__ . '/operators.php';

function replace_check(bool $ok, string $why): void
{
	if (!$ok) {
		throw new \LogicException($why);
	}
}

$out = $argv[1];
$path = 'tests/php/strings/strict_level_01/strict_strings_001_str_surface.phs';
$text = file_get_contents(dirname(__DIR__, 3) . '/' . $path);
$original = ['path' => $path, 'sha256' => hash('sha256', $text)];
try {
	operator_test_source($text);
	$original['compiled'] = true;
	file_put_contents($out . '/original.cpp', CPP_Probe::text(Model::$cpp_files));
}
catch (\RuntimeException $e) {
	$original['compiled'] = false;
	$original['diagnostic'] = $e->getMessage();
}
if (($argv[2] ?? '') === '--baseline') {
	replace_check(str_contains($original['diagnostic'] ?? '', 'substr_replace'), 'Missing baseline replacement failure');
	file_put_contents($out . '/baseline.json', json_encode($original, JSON_PRETTY_PRINT) . "\n");
	exit(0);
}

$program = <<<'PHS'
function replace3(const string &$s,const string &$r,const int &$o):string{return substr_replace($s,$r,$o);}
function replace4(const string &$s,const string &$r,const int &$o,const int &$l):string{return substr_replace($s,$r,$o,$l);}
function subject(int &$trace,string &$s):string{$trace=$trace*10+1;return $s;}
function replacement(int &$trace,string &$s,string &$r):string{$trace=$trace*10+2;$s="changed";return $r;}
function offset(int &$trace,string &$r,int &$o):int{$trace=$trace*10+3;$r="later";return $o;}
function length(int &$trace,string &$s,int &$o):int{$trace=$trace*10+4;$s="last";$o=5;return 2;}
function nooplength(int &$trace,string &$s):int{$trace=$trace*10+4;$s="last";return -99;}
function order():bool{
 $t=0;$s="abcdef";$r="X";$o=2;
 $v=substr_replace(subject($t,$s),replacement($t,$s,$r),offset($t,$r,$o),length($t,$s,$o));
 if($t!==1234 || $v!=="abXef" || $s!=="last" || $r!=="later" || $o!==5)return false;
 $t=0;$s="abcdef";$r="X";$o=2;
 $v=substr_replace(subject($t,$s),replacement($t,$s,$r),offset($t,$r,$o));
 if($t!==123 || $v!=="abXcdef" || $s!=="changed" || $r!=="later")return false;
 $t=0;$s="abcdef";$r="X";$o=2;
 $v=substr_replace(subject($t,$s),replacement($t,$s,$r),offset($t,$r,$o),nooplength($t,$s));
 return $t===1234 && $v==="abcdef" && $s==="last" && $r==="later";
}
function bad(int &$trace):string{$trace=$trace*10+2;$bad=1/0;return "X";}
function throws(int &$trace):void{$s="abcdef";$r="X";$o=2;$v=substr_replace(subject($trace,$s),bad($trace),offset($trace,$r,$o),length($trace,$s,$o));}
function snapshots():bool{
 $s="abc";$v=substr_replace($s,$s,1,1);$s="changed";
 if($v!=="aabcc")return false;
 $s="abc";$v=substr_replace($s,"X",1,-99);$s="changed";
 return $v==="abc";
}
PHS;
operator_test_source($program);
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', CPP_Probe::text(Model::$cpp_files));
$cpp .= "\n#include \"" . __DIR__ . "/substr_replace_support.hpp\"\n#include <iostream>\n";
$cpp .= <<<'CPP'
int main() {
 auto count=replace_probe::verify(function_replace3,function_replace4);
 if(!function_order().native_value() || !function_snapshots().native_value())return 1;
 scpp::int_t<> trace{0};bool caught=false;
 try{function_throws(trace);}catch(const scpp::runtime_error& e){caught=e.code()=="division_by_zero";}
 if(!caught || trace.native_value()!=12)return 2;
 std::cout<<"replace_checks="<<count<<"\n";
}
CPP;
file_put_contents($out . '/behavior.cpp', $cpp);

$rejections = [];
foreach (['substr_replace();', 'substr_replace("a");', 'substr_replace("a","x");', 'substr_replace("a","x",0,1,2);', 'substr_replace(1,"x",0);', 'substr_replace("a",false,0);', 'substr_replace("a","x",1.0);', 'substr_replace("a","x",0,"1");', '$s mixed="a";substr_replace($s,"x",0);', '$s vector<string>=["a"];substr_replace($s,"x",0);', '$r =& substr_replace("a","x",0);'] as $source)
{
	$error = '';
	try {
		operator_test_source($source);
	}
	catch (\RuntimeException $e) {
		$error = $e->getMessage();
	}
	replace_check(($error !== '') && Model::$cpp_files->is_empty(), 'Invalid call admitted: ' . $source);
	$rejections[] = ['source' => $source, 'diagnostic' => $error];
}

$dir = sys_get_temp_dir() . '/scpp_replace_' . bin2hex(random_bytes(6));
mkdir($dir);
$path = $dir . '/main.phs';
$recoveries = 0;
try
{
	foreach (['', ',2'] as $length)
	{
		$valid = 'echo substr_replace("abcdef","X",2'.$length.');';
		file_put_contents($path, $valid);
		Compiler_Lifecycle::reset();
		$compiler = new Compiler();
		$compiler->init([$dir]);
		$compiler->exec_cpp();
		$fresh = CPP_Probe::text(Model::$cpp_files);
		file_put_contents($path, str_replace('"abcdef"', 'false', $valid));
		foreach ([[$path], []] as $changes)
		{
			$failed = false;
			try {
				$compiler->update_cpp($changes);
			}
			catch (\RuntimeException $e) {
				$failed = true;
			}
			replace_check($failed && Model::$cpp_files->is_empty() && Model::$prepared_files->is_empty(), 'Stale replacement publication');
		}
		file_put_contents($path, $valid);
		$compiler->update_cpp([$path]);
		replace_check(CPP_Probe::text(Model::$cpp_files) === $fresh, 'Repair differs');
		$compiler->update_cpp([]);
		replace_check(CPP_Probe::text(Model::$cpp_files) === $fresh, 'No-op differs');
		++$recoveries;
	}
}
finally {
	unlink($path);
	rmdir($dir);
}
file_put_contents($out . '/checks.json', json_encode(['original' => $original, 'rejections' => $rejections, 'recovery_cycles' => $recoveries], JSON_PRETTY_PRINT) . "\n");
