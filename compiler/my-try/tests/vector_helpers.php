<?php
namespace scpp\compiler;
require_once __DIR__ . '/operators.php';

function vector_check(bool $ok, string $why): void
{
	if (!$ok) {
		throw new \LogicException($why);
	}
}
$out = $argv[1];
$cases = [
	'forms' => <<<'PHS'
$v vector<int> = [1,2];vector_reserve($v,16);$c=vector_capacity($v);
if($c<16 || count($v)!==2)return 1;
vector_resize($v,4,7);if($v[2]!==7 || $v[3]!==7 || $v[0]!==1)return 2;
vector_resize($v,1,9);if(count($v)!==1 || $v[0]!==1)return 3;
vector_compact($v,0);if(count($v)!==1 || $v[0]!==1 || vector_capacity($v)<1)return 4;
vector_compact($v);if(vector_capacity($v)<count($v))return 5;
vector_reserve($v,12);$c=vector_capacity($v);vector_clear($v);
if(count($v)!==0 || vector_capacity($v)!==$c)return 6;
vector_resize($v,2,8);vector_clear_keep_capacity($v);
if(count($v)!==0 || vector_capacity($v)!==$c)return 7;
$filled=vector_filled(3,9);if(count($filled)!==3 || $filled[2]!==9)return 8;
return 0;
PHS,
	'scalars' => '$small int16=7;$a=vector_filled(2,$small);vector_resize($a,3,$small);$b=vector_filled(2,false);$c=vector_filled(2,1.5);if(count($a)!==3 || $b[0] || $c[1]!==1.5)return 1;return 0;',
	'copies' => <<<'PHS'
$seed vector<int> = [7];$v=vector_filled(2,$seed);$v[0][0]=9;
if($seed[0]!==7 || $v[1][0]!==7)return 1;
vector_resize($v,3,$v[0]);$v[0][0]=4;
if($v[2][0]!==9)return 2;
$s=vector_filled(2,"old");vector_resize($s,10,$s[0]);$s[0]="new";
if($s[1]!=="old" || $s[9]!=="old")return 3;
return 0;
PHS,
	'observation' => <<<'PHS'
function inspect(const vector<int> &$v):int{return vector_capacity($v);}
function make(int &$n):vector<int>{$n++;return vector<int>::[1,2];}
class Bag{public vector<int> $items=[1,2];}
function bag(int &$n):Bag{$n++;return new Bag();}
$v vector<int> = [1];$n=0;
if(inspect($v)<1 || vector_capacity(make($n))<2 || vector_capacity(bag($n)->items)<2 || $n!==2)return 1;
return 0;
PHS,
	'noncopyable' => <<<'PHS'
$v vector<unique<int>>;vector_reserve($v,4);vector_compact($v,2);vector_compact($v);
if(vector_capacity($v)<count($v))return 1;
vector_clear($v);vector_clear_keep_capacity($v);return count($v);
PHS,
	'order' => <<<'PHS'
function size(int &$trace):int{$trace=$trace*10+1;return 4;}
function fill(vector<int> &$v,int &$trace):int{$trace=$trace*10+2;$v[0]=9;return 7;}
$v vector<int> = [1];$trace=0;vector_resize($v,size($trace),fill($v,$trace));
if($trace!==12 || $v[0]!==9 || $v[3]!==7)return 1;
return 0;
PHS,
];
foreach ($cases as $name => $source) {
	operator_test_source($source);
	file_put_contents($out.'/'.$name.'.cpp',CPP_Probe::text(Model::$cpp_files));
}
$program = <<<'PHS'
function reserve(vector<int> &$v,int $n):void{vector_reserve($v,$n);}
function resize(vector<int> &$v,int $n):void{vector_resize($v,$n,7);}
function compact(vector<int> &$v,int $n):void{vector_compact($v,$n);}
function filled(int $n):void{$v=vector_filled($n,7);}
function touch(vector<int> &$v,int &$trace):int{$trace++;$v[0]=9;return 7;}
function negative(vector<int> &$v,int &$trace):void{vector_resize($v,-1,touch($v,$trace));}
PHS;
operator_test_source($program);
$cpp = str_replace('int main(int argc, char** argv)','int unused_entry(int argc, char** argv)',CPP_Probe::text(Model::$cpp_files));
$cpp .= <<<'CPP'

#include <limits>
int main(){using I=scpp::int_t<>;scpp::vector_t<I> v;v.append(I{7});v.reserve(10);auto capacity=v.capacity();int caught=0;
 for(auto n:{std::int64_t{-1},std::numeric_limits<std::int64_t>::min()}){
  for(int mode=0;mode<4;++mode){try{if(mode==0)function_reserve(v,I{n});else if(mode==1)function_resize(v,I{n});else if(mode==2)function_compact(v,I{n});else function_filled(I{n});}
   catch(const scpp::runtime_error& e){if(e.code()!="value_error")return 1;++caught;}
   if(v.size()!=1 || v.at(I{0}).native_value()!=7 || v.capacity()!=capacity)return 2;
  }
 }
 I trace{0};try{function_negative(v,trace);}catch(const scpp::runtime_error& e){if(e.code()!="value_error")return 3;++caught;}
 return caught==9 && trace.native_value()==1 && v.at(I{0}).native_value()==9 && v.size()==1 ? 0:4;}
CPP;
file_put_contents($out.'/errors.cpp',$cpp);
$rejections = [];
foreach (['vector_reserve();','vector_capacity(1);','$v vector<int>;vector_reserve($v,1.0);','vector_resize(vector<int>::[],1,7);','function bad(const vector<int> &$v):void{vector_clear($v);}', '$v vector<int>;vector_resize($v,2,"x");','$v vector<unique<int>>;$x unique<int>;vector_resize($v,0,$x);','vector_compact(vector<int>::[]);','$v vector<int>;vector_compact($v,1,2);'] as $source)
{
	$error = '';
	try { operator_test_source($source); }
	catch (\RuntimeException $e) { $error = $e->getMessage(); }
	vector_check(($error !== '') && Model::$cpp_files->is_empty(),'Invalid vector contract admitted: '.$source);
	$rejections[] = ['source'=>$source,'diagnostic'=>$error];
}
// Expose provider copy prerequisites explicitly; do not infer them from by-value transport.
$move_probes = [];
foreach (['filled'=>'function probe(unique<int> $x):vector<unique<int>>{return vector_filled(0,move($x));}',
	'resize'=>'function probe(vector<unique<int>> &$v,unique<int> $x):void{vector_resize($v,0,move($x));}'] as $name => $source)
{
	$record = ['source'=>$source];
	try {
		operator_test_source($source);
		$record['compiled'] = true;
		file_put_contents($out.'/move_'.$name.'.cpp',CPP_Probe::text(Model::$cpp_files));
	}
	catch (\RuntimeException $e) {
		$record['compiled'] = false;
		$record['diagnostic'] = $e->getMessage();
	}
	$move_probes[$name] = $record;
}
// A failed provider requirement must not leave published artifacts in a retained session.
$directory = sys_get_temp_dir().'/vector_recovery_'.bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory.'/main.phs';
$valid = '$v vector<int> = [1];vector_resize($v,2,7);';
$recoveries = 0;
try
{
	foreach (['function bad(vector<unique<int>> &$v,unique<int> $x):void{vector_resize($v,0,move($x));}',
		'$v vector<int>;vector_resize($v,2,"bad");'] as $invalid)
	{
		file_put_contents($path, $valid);
		Compiler_Lifecycle::reset();
		$compiler = new Compiler();
		$compiler->init([$directory]);
		$compiler->exec_cpp();
		$fresh = CPP_Probe::text(Model::$cpp_files);
		file_put_contents($path, $invalid);
		foreach ([[$path], []] as $changes)
		{
			$failed = false;
			try {
				$compiler->update_cpp($changes);
			}
			catch (\RuntimeException $e) {
				$failed = true;
			}
			vector_check($failed && Model::$cpp_files->is_empty() && Model::$prepared_files->is_empty(), 'Stale vector output');
		}
		file_put_contents($path, $valid);
		$compiler->update_cpp([$path]);
		vector_check(CPP_Probe::text(Model::$cpp_files) === $fresh, 'Vector repair differs');
		$compiler->update_cpp([]);
		vector_check(CPP_Probe::text(Model::$cpp_files) === $fresh, 'Vector no-op differs');
		++$recoveries;
	}
}
finally {
	unlink($path);
	rmdir($directory);
}
file_put_contents($out.'/checks.json',json_encode(['programs'=>array_merge(array_keys($cases),['errors']),'rejections'=>$rejections,'move_probes'=>$move_probes,'recovery_cycles'=>$recoveries],JSON_PRETTY_PRINT)."\n");
