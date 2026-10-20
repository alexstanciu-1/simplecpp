<?php
namespace scpp\compiler;
require_once __DIR__ . '/operators.php';

function string_comparison_check(bool $ok, string $message): void
{
	if (!$ok) {
		throw new \LogicException($message);
	}
}

$operations = ['equal' => '==', 'not_equal' => '!=', 'less' => '<', 'less_equal' => '<=',
	'greater' => '>', 'greater_equal' => '>=', 'three_way' => '<=>'];
$program = '';
$observers = '';
foreach ($operations as $name => $symbol)
{
	$tag = str_replace('_', '', $name);
	$return = $name === 'three_way' ? 'int' : 'bool';
	$native = $name === 'three_way' ? 'I' : 'B';
	$expected = $name === 'three_way' ? 'order' : '(order ' . $symbol . ' 0)';
	$syntax = operator_test_source('$r = "a" ' . $symbol . ' "b";');
	$value = $syntax->root->single_body()->body->statements[0]->expression->value;
	$decision = $value->require_binary_preparation()->decision;
	$type = $name === 'three_way' ? Language_Types::integer(Model::$language_scope) : Language_Types::boolean(Model::$language_scope);
	string_comparison_check($decision->result_type->matches($type)
		&& $decision->operation->name === 'string_' . $name, 'Wrong string comparison decision');
	foreach ($decision->operands as $operand) {
		string_comparison_check($operand->operation === conversion_operation::identity
			&& $operand->target_type->matches(Language_Types::string_type(Model::$language_scope)), 'String comparison coerced operands');
	}
	Preparation_Cleanup::tree($syntax->root);
	string_comparison_check($value->preparation() === null, 'String comparison cleanup retained facts');
	$program .= 'function op' . $tag . '(string $a, string $b, int &$lc, int &$rc, int &$trace, bool $lf, bool $rf): ' . $return
		. ' { return left($a, $lc, $trace, $lf) ' . $symbol . ' right($b, $rc, $trace, $rf); } ';
	$program .= 'function snap' . $tag . '(string &$a, string $b, int &$calls): ' . $return
		. ' { return $a ' . $symbol . ' change($a, $b, $calls); } ';
	$program .= 'function prop' . $tag . '(string $a, string $b, int &$calls): ' . $return
		. ' { $box = new Box(); $box->value = $a; return $box->value ' . $symbol . ' changebox($box, $b, $calls); } ';
	$call = 'function_op' . $tag . '(S{a},S{b},lc,rc,trace,B{lf},B{rf})';
	$observers .= 'for(const auto& a:values) for(const auto& b:values) { int order=byte_order(a,b); '
		. 'for(bool lf:{false,true}) for(bool rf:{false,true}) { I lc{0},rc{0},trace{0}; bool threw=false; '
		. 'try { static_assert(std::is_same_v<decltype(' . $call . '),' . $native . '>); auto result=' . $call . '; '
		. 'if(result.native_value()!=' . $expected . ') return 1; } '
		. 'catch(const scpp::runtime_error& e) { if(e.code()!="division_by_zero") return 2; threw=true; } '
		. 'if(threw!=(lf||rf) || lc.native_value()!=1 || rc.native_value()!=(lf?0:1) || trace.native_value()!=(lf?1:12)) return 3; } '
		. 'I calls{0}; S stored{a}; if(function_snap' . $tag . '(stored,S{b},calls).native_value()!=' . $expected
		. ' || stored.native_value()!="changed" || calls.native_value()!=1) return 4; '
		. 'calls=I{0}; if(function_prop' . $tag . '(S{a},S{b},calls).native_value()!=' . $expected . ' || calls.native_value()!=1) return 5; }' . "\n";

	foreach (['int', 'float', 'bool', '?string', 'result<string>', 'result_or_false<string>'] as $other)
	{
		if (in_array($other, ['?string','result<string>'], true)) { continue; }
		if (in_array($symbol, ['<','<=','>','>=','<=>'], true) && in_array($other, ['?string','result<string>','result_or_false<string>'], true)) { continue; }
		foreach ([['string', $other], [$other, 'string']] as [$left, $right])
		{
			$failed = false;
			try {
				operator_test_source('function bad(' . $left . ' $a, ' . $right . ' $b): ' . $return . ' { return $a ' . $symbol . ' $b; }');
			}
			catch (\RuntimeException $error) {
				$failed = true;
			}
			string_comparison_check($failed && Model::$cpp_files->is_empty(), 'Excluded comparison pair admitted');
		}
	}

	// Every form must survive failure, repeat, repair, no-op and fresh reconstruction.
	$directory = sys_get_temp_dir() . '/scpp_string_' . bin2hex(random_bytes(6));
	mkdir($directory);
	$path = $directory . '/main.phs';
	$valid = 'function f(string $a, string $b): ' . $return . ' { return $a ' . $symbol . ' $b; }';
	try
	{
		file_put_contents($path, $valid);
		Compiler_Lifecycle::reset();
		$compiler = new Compiler(new cpp_lowering_options(false));
		$compiler->init([$directory]);
		$compiler->exec_cpp();
		file_put_contents($path, str_replace('string $b', 'int $b', $valid));
		foreach ([[$path], []] as $changes)
		{
			$failed = false;
			try {
				$compiler->update_cpp($changes);
			}
			catch (\RuntimeException $error) {
				$failed = true;
			}
			string_comparison_check($failed && Model::$cpp_files->is_empty()
				&& Model::$prepared_files->is_empty(), 'Repeated failure retained artifacts');
		}
		file_put_contents($path, str_replace('$a ' . $symbol . ' $b', '$b ' . $symbol . ' $a', $valid));
		$compiler->update_cpp([$path]);
		$repaired = CPP_Probe::text(Model::$cpp_files);
		$compiler->update_cpp([]);
		string_comparison_check($repaired === CPP_Probe::text(Model::$cpp_files), 'No-op changed output');
		Compiler_Lifecycle::reset();
		$compiler->init([$directory]);
		$compiler->exec_cpp();
		string_comparison_check($repaired === CPP_Probe::text(Model::$cpp_files), 'Repair differs from fresh');
	}
	finally {
		unlink($path);
		rmdir($directory);
	}
}
$program .= <<<'PHS'
class Box { public string $value = ""; }
function left(string $value, int &$calls, int &$trace, bool $fail): string {
 $calls++; $trace = $trace * 10 + 1;
 if ($fail) { $bad = 1 / 0; }
 return $value;
}
function right(string $value, int &$calls, int &$trace, bool $fail): string {
 $calls++; $trace = $trace * 10 + 2;
 if ($fail) { $bad = 1 / 0; }
 return $value;
}
function change(string &$a, string $b, int &$calls): string { $calls++; $a = "changed"; return $b; }
function changebox(Box $box, string $b, int &$calls): string { $calls++; $box->value = "changed"; return $b; }
function literals(): bool { return ("10" < "2") && ("01" != "1") && ("A" < "a") && ("é" != "é"); }
PHS;
operator_test_source($program);
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', CPP_Probe::text(Model::$cpp_files));
$cpp .= <<<'CPP'

#include <type_traits>
#include <vector>
// Independent unsigned-byte oracle; do not derive expected values using string operators.
int byte_order(const std::string& a, const std::string& b) {
 for(size_t i=0;i<a.size() && i<b.size();++i) {
  auto x=static_cast<unsigned char>(a[i]); auto y=static_cast<unsigned char>(b[i]);
  if(x<y) return -1; if(x>y) return 1;
 }
 return a.size()<b.size()?-1:a.size()>b.size()?1:0;
}
int main() {
 using S=scpp::string_t; using I=scpp::int_t<>; using B=scpp::bool_t;
 const std::vector<std::string> values={"", "a", "ab", "b", "A", "0", "01", "1", "10", "2",
  std::string("a\0b",3), std::string("a\0c",3), std::string("\0",1),
  std::string("\x7f",1), std::string("\x80",1), std::string("\xff",1), "é", "é"};
 if(!function_literals().native_value()) return 6;
CPP;
$cpp .= $observers . "return 0;\n}\n";
file_put_contents($argv[1] . '/string_comparisons.cpp', $cpp);
echo "String comparisons: seven forms, byte domain, ordered snapshots and recovery passed\n";
