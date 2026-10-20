<?php
namespace scpp\compiler;
require_once __DIR__ . '/operators.php';

function search_check(bool $ok, string $why): void
{
	if (!$ok) {
		throw new \LogicException($why);
	}
}

$out = $argv[1];
$baseline = ($argv[2] ?? '') === '--baseline';
$originals = [];
foreach (['002_str_edges', '004_strpos_false_branch_regression', '001_str_surface'] as $suffix)
{
	$path = 'tests/php/strings/strict_level_01/strict_strings_' . $suffix . '.phs';
	$text = file_get_contents(dirname(__DIR__, 3) . '/' . $path);
	$row = ['path' => $path, 'sha256' => hash('sha256', $text)];
	try {
		operator_test_source($text);
		$row['compiled'] = true;
		file_put_contents($out . '/original_' . $suffix . '.cpp', CPP_Probe::text(Model::$cpp_files));
	}
	catch (\RuntimeException $e) {
		$row['compiled'] = false;
		$row['diagnostic'] = $e->getMessage();
	}
	$originals[$suffix] = $row;
}
if ($baseline) {
	search_check(str_contains($originals['002_str_edges']['diagnostic'] ?? '', 'strpos'), 'Missing baseline search failure');
	file_put_contents($out . '/baseline.json', json_encode($originals, JSON_PRETTY_PRINT) . "\n");
	exit(0);
}

$program = <<<'PHS'
function f2(const string &$h, const string &$n):result_or_false<int>{return strpos($h,$n);}
function f3(const string &$h, const string &$n, const int &$o):result_or_false<int>{return strpos($h,$n,$o);}
function r2(const string &$h, const string &$n):result_or_false<int>{return strrpos($h,$n);}
function r3(const string &$h, const string &$n, const int &$o):result_or_false<int>{return strrpos($h,$n,$o);}
function hay(int &$trace,string &$h):string{$trace=$trace*10+1;return $h;}
function needle(int &$trace,string &$h,string &$n):string{$trace=$trace*10+2;$h="changed";return $n;}
function offset(int &$trace,string &$n):int{$trace=$trace*10+3;$n="zz";return 0;}
function order():bool{
 $t=0;$h="banana";$n="na";$v=strpos(hay($t,$h),needle($t,$h,$n),offset($t,$n));
 if($t!==123 || $v!==2 || $h!=="changed" || $n!=="zz")return false;
 $t=0;$h="banana";$n="na";$v=strrpos(hay($t,$h),needle($t,$h,$n),offset($t,$n));
 if($t!==123 || $v!==4 || $h!=="changed" || $n!=="zz")return false;
 $t=0;$h="banana";$n="na";$v=strpos(hay($t,$h),needle($t,$h,$n));
 if($t!==12 || $v!==2)return false;
 $t=0;$h="banana";$n="na";$v=strrpos(hay($t,$h),needle($t,$h,$n));
 return $t===12 && $v===4;
}
function badneedle(int &$trace):string{$trace=$trace*10+2;$bad=1/0;return "a";}
function throws(int &$trace):void{$h="banana";$n="na";$v=strpos(hay($trace,$h),badneedle($trace),offset($trace,$n));}
function extracted():string{
 $p=strpos("varchar(32)","(");$position int=0;
 if(take($position)=$p)return substr("varchar(32)",0,$position);
 return "missing";
}
function states():bool{
 $h="abc";$v=strpos($h,"a");$h="changed";
 if($v===false || ($v??9)!==0)return false;
 $missing=strrpos($h,"zz");
 return $missing===false && ($missing??9)===9 && $v===0;
}
PHS;
operator_test_source($program);
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', CPP_Probe::text(Model::$cpp_files));
$cpp .= "\n#include \"" . __DIR__ . "/string_search_support.hpp\"\n#include <iostream>\n";
$cpp .= <<<'CPP'
int main() {
 auto count=search_probe::verify(function_f2,function_f3,function_r2,function_r3);
 if(!function_order().native_value() || !function_states().native_value() || function_extracted().native_value()!="varchar")return 1;
 scpp::int_t<> trace{0};bool caught=false;
 try{function_throws(trace);}catch(const scpp::runtime_error& e){caught=e.code()=="division_by_zero";}
 if(!caught || trace.native_value()!=12)return 2;
 search_probe::text_controls();
 std::cout<<"search_checks="<<count<<"\n";
}
CPP;
file_put_contents($out . '/behavior.cpp', $cpp);

$rejections = [];
foreach (['strpos','strrpos'] as $name)
{
	foreach ([$name.'();', $name.'("a");', $name.'("a","a",0,1);', $name.'(1,"a");', $name.'("a",false);', $name.'("a","a",1.0);', '$v mixed="a";'.$name.'($v,"a");', '$v vector<string>=["a"];'.$name.'($v,"a");', '$r =& '.$name.'("a","a");'] as $source)
	{
		$error = '';
		try {
			operator_test_source($source);
		}
		catch (\RuntimeException $e) {
			$error = $e->getMessage();
		}
		search_check(($error !== '') && Model::$cpp_files->is_empty(), 'Invalid call admitted: ' . $source);
		$rejections[] = ['source' => $source, 'diagnostic' => $error];
	}
}

// Preserve the downstream call boundary now closed by checked-payload selection.
$boundary = '$p=strpos("varchar(32)","(");if($p!==false)echo substr("varchar(32)",0,$p);';
$boundary_error = '';
try {
	operator_test_source($boundary);
}
catch (\RuntimeException $e) {
	$boundary_error = $e->getMessage();
}
search_check($boundary_error === '', 'Checked-payload downstream call failed');
search_check($originals['004_strpos_false_branch_regression']['compiled'], 'Original false-branch fixture failed');

$dir = sys_get_temp_dir() . '/scpp_search_' . bin2hex(random_bytes(6));
mkdir($dir);
$path = $dir . '/main.phs';
$recoveries = 0;
try
{
	foreach (['strpos','strrpos'] as $name)
	{
		foreach (['', ',0'] as $offset)
		{
			$valid = 'echo ('.$name.'("banana","na"'.$offset.')??-1);';
			file_put_contents($path, $valid);
			Compiler_Lifecycle::reset();
			$compiler = new Compiler();
			$compiler->init([$dir]);
			$compiler->exec_cpp();
			$fresh = CPP_Probe::text(Model::$cpp_files);
			file_put_contents($path, str_replace('"banana"', 'false', $valid));
			foreach ([[$path], []] as $changes)
			{
				$failed = false;
				try {
					$compiler->update_cpp($changes);
				}
				catch (\RuntimeException $e) {
					$failed = true;
				}
				search_check($failed && Model::$cpp_files->is_empty() && Model::$prepared_files->is_empty(), 'Stale search publication');
			}
			file_put_contents($path, $valid);
			$compiler->update_cpp([$path]);
			search_check(CPP_Probe::text(Model::$cpp_files) === $fresh, 'Repair differs');
			$compiler->update_cpp([]);
			search_check(CPP_Probe::text(Model::$cpp_files) === $fresh, 'No-op differs');
			++$recoveries;
		}
	}
}
finally {
	unlink($path);
	rmdir($dir);
}
file_put_contents($out . '/checks.json', json_encode(['originals' => $originals, 'rejections' => $rejections, 'recovery_cycles' => $recoveries, 'downstream_boundary' => ['source' => $boundary, 'diagnostic' => $boundary_error]], JSON_PRETTY_PRINT) . "\n");
