<?php
namespace scpp\compiler;
require_once __DIR__ . '/operators.php';
require_once __DIR__ . '/logical.php';
function numeric_logical_check(bool $ok, string $message): void {
	if (!$ok) { throw new \LogicException($message); }
}
$program = '';
$observers = '';
$types = ['i' => 'int', 'f' => 'float'];
$operations = ['a' => '&&', 'o' => '||', 'wa' => 'and', 'wo' => 'or'];
foreach ($types as $tag => $type) {
	$zero = $tag === 'i' ? '0' : '0.0';
	$syntax = operator_test_source('$r = !' . $zero . ';');
	$value = $syntax->root->single_body()->body->statements[0]->expression->value;
	$d = $value->require_unary_preparation()->decision;
	numeric_logical_check($d->operation === operator_operation::condition_not && $d->result_type->matches(Language_Types::boolean(Model::$language_scope)), 'Wrong numeric negation decision');
	Preparation_Cleanup::tree($syntax->root);
	numeric_logical_check($value->preparation() === null, 'Negation cleanup retained facts');
	$program .= 'function neg' . $tag . '(' . $type . ' $value, int &$calls): bool { $trace int = 0; return !left' . $tag . '($value, $calls, $trace, false); } ';
	foreach (['left' => 1, 'right' => 2] as $side => $digit) {
		$program .= 'function ' . $side . $tag . '(' . $type . ' $value, int &$calls, int &$trace, bool $fail): ' . $type . ' { $calls++; $trace = $trace * 10 + ' . $digit . '; if ($fail) { return ' . ($tag === 'i' ? '1 / 0' : '1.0 / 0.0') . '; } return $value; } ';
	}
	$program .= 'function replacetrue' . $tag . '(' . $type . ' &$x): ' . $type . ' { $x = ' . $zero . '; return ' . ($tag === 'i' ? '7' : '7.0') . '; } ';
	$program .= 'function replacefalse' . $tag . '(' . $type . ' &$x): ' . $type . ' { $x = ' . ($tag === 'i' ? '7' : '7.0') . '; return ' . $zero . '; } ';
	$program .= 'function snapshota' . $tag . '(' . $type . ' &$x): bool { return $x && replacetrue' . $tag . '($x); } ';
	$program .= 'function snapshoto' . $tag . '(' . $type . ' &$x): bool { return $x || replacefalse' . $tag . '($x); } ';
	$program .= 'function changea' . $tag . '(' . $type . ' &$x): bool { return $x && ($x = ' . $zero . '); } ';
	$program .= 'function changeo' . $tag . '(' . $type . ' &$x): bool { return $x || ($x = ' . ($tag === 'i' ? '7' : '7.0') . '); } ';
}
foreach ($types as $lt => $left) foreach ($types as $rt => $right) foreach ($operations as $ot => $operator) {
	$name = $lt . $rt . $ot;
	$a = $lt === 'i' ? '0' : '0.0'; $b = $rt === 'i' ? '7' : '7.0';
	$syntax = operator_test_source('$r = (' . $a . ' ' . $operator . ' ' . $b . ');');
	$value = $syntax->root->single_body()->body->statements[0]->expression->value;
	$d = $value->require_binary_preparation()->decision;
	$is_and = ($ot === 'a' || $ot === 'wa');
	numeric_logical_check($d->operation === ($is_and ? operator_operation::condition_and : operator_operation::condition_or)
		&& $d->result_type->matches(Language_Types::boolean(Model::$language_scope)) && count($d->operand_truthiness) === 2
		&& count($d->operands) === 0, 'Numeric logical decision must preserve lazy truth conversion');
	Preparation_Cleanup::tree($syntax->root);
	numeric_logical_check($value->preparation() === null, 'Binary cleanup retained facts');
	$program .= 'function ' . $name . '(' . $left . ' $a, ' . $right . ' $b, int &$lc, int &$rc, int &$trace, bool $lf, bool $rf): bool { return (left' . $lt . '($a, $lc, $trace, $lf) ' . $operator . ' right' . $rt . '($b, $rc, $trace, $rf)); } ';
	$L = $lt === 'i' ? 'I' : 'F'; $R = $rt === 'i' ? 'I' : 'F';
	$av = $lt === 'i' ? 'ints' : 'floats'; $bv = $rt === 'i' ? 'ints' : 'floats';
	$needed = $is_and ? '(a != 0)' : '(a == 0)';
	$expected = $is_and ? '(a != 0 && b != 0)' : '(a != 0 || b != 0)';
	$call = 'function_' . $name . '(' . $L . '{a}, ' . $R . '{b}, lc, rc, trace, B{lf}, B{rf})';
	$observers .= 'for (auto a : ' . $av . ') for (auto b : ' . $bv . ') for (bool lf : {false,true}) for (bool rf : {false,true}) { I lc{0},rc{0},trace{0}; bool threw=false; bool result=false; try { static_assert(std::is_same_v<decltype(' . $call . '), B>); result=' . $call . '.native_value(); } catch(const scpp::runtime_error& e) { if(e.code()!="division_by_zero") return 1; threw=true; } bool reached=!lf && ' . $needed . '; if(threw != (lf || (reached && rf))) return 2; if(!threw && result != ' . $expected . ') return 3; if(lc.native_value()!=1 || rc.native_value()!=(reached?1:0) || trace.native_value()!=(reached?12:1)) return 4; }' . "\n";
}
foreach ([
	'0 && $missing;', '1 || $missing;', '0 and $missing;', '1 or $missing;',
	'0 && "x";', '1 || true;', 'true && 1;',
	'$x int16 = 1; $x && $x;', '$x uint8 = 1; !$x;',
	'function n(): int { return 1; } 0 && n(1);',
] as $source) {
	$failed = false;
	try { operator_test_source($source); } catch (\RuntimeException $error) { $failed = true; }
	numeric_logical_check($failed && Model::$cpp_files->is_empty(), 'Excluded or skipped invalid operand accepted: ' . $source);
}
$program .= <<<'PHS'
function wordand(int &$x): bool { return ($x = 2 and 0); }
function wordor(int &$x): bool { return ($x = 0 or 7); }
function grouped(bool &$x): bool { $x = (2 and 0); return $x; }
function mark(int $digit, int $value, int &$trace): int { $trace = $trace * 10 + $digit; return $value; }
function nested(int &$trace): bool { return (mark(1, 0, $trace) && mark(2, 7, $trace)) || (mark(3, 7, $trace) && mark(4, 7, $trace)); }
function nestedskip(int &$trace): bool { return (mark(1, 7, $trace) || mark(2, 0, $trace)) || (mark(3, 7, $trace) && mark(4, 7, $trace)); }
PHS;
// Numeric edits must recover through the retained compiler, not a fresh-only path.
foreach (['function f(int $a, float $b): bool { return $a && $b; }', 'function f(int $a, float $b): bool { return !$a; }'] as $valid) {
	$directory = sys_get_temp_dir() . '/scpp_logic_' . bin2hex(random_bytes(6)); mkdir($directory); $path = $directory . '/main.phs';
	try {
		file_put_contents($path, $valid); Compiler_Lifecycle::reset(); $compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
		file_put_contents($path, str_replace('int $a', 'int16 $a', $valid));
		foreach ([[$path], []] as $changes) {
			$failed = false; try { $compiler->update_cpp($changes); } catch (\RuntimeException $error) { $failed = true; }
			numeric_logical_check($failed && Model::$cpp_files->is_empty() && Model::$prepared_files->is_empty(), 'Failed/repeated logical preparation retained artifacts');
		}
		file_put_contents($path, str_replace('&&', 'or', str_replace('!$a', '!$b', $valid))); $compiler->update_cpp([$path]);
		$repaired = CPP_Probe::text(Model::$cpp_files); $compiler->update_cpp([]);
		numeric_logical_check($repaired === CPP_Probe::text(Model::$cpp_files), 'No-op logical output changed');
		Compiler_Lifecycle::reset(); $compiler->init([$directory]); $compiler->exec_cpp();
		numeric_logical_check($repaired === CPP_Probe::text(Model::$cpp_files), 'Logical repair differs from fresh');
	} finally { unlink($path); rmdir($directory); }
}
operator_test_source($program);
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', CPP_Probe::text(Model::$cpp_files));
$cpp .= <<<'CPP'

#include <limits>
#include <type_traits>
int main() {
 using I=scpp::int_t<>; using F=scpp::float_t; using B=scpp::bool_t;
 const long ints[]={0,7,-3};
 const double floats[]={0.0,-0.0,7.5,-3.5,std::numeric_limits<double>::infinity(),-std::numeric_limits<double>::infinity(),std::numeric_limits<double>::quiet_NaN()};
 for(auto v:ints) { I calls{0}; static_assert(std::is_same_v<decltype(function_negi(I{v},calls)),B>); if(function_negi(I{v},calls).native_value()!=(v==0) || calls.native_value()!=1) return 5; }
 for(auto v:floats) { I calls{0}; static_assert(std::is_same_v<decltype(function_negf(F{v},calls)),B>); if(function_negf(F{v},calls).native_value()!=(v==0) || calls.native_value()!=1) return 6; }
 for(auto v:ints) { I x{v}; if(function_changeai(x).native_value() || x.native_value()!=0) return 7; x=I{v}; if(!function_changeoi(x).native_value() || x.native_value()!=(v==0?7:v)) return 8; }
 for(auto v:floats) { F x{v}; if(function_changeaf(x).native_value() || x.native_value()!=0) return 9; }
 { I x{7}; if(!function_snapshotai(x).native_value() || x.native_value()!=0) return 15; x=I{0}; if(function_snapshotoi(x).native_value() || x.native_value()!=7) return 16; }
 { F x{7.5}; if(!function_snapshotaf(x).native_value() || x.native_value()!=0) return 17; x=F{0.0}; if(function_snapshotof(x).native_value() || x.native_value()!=7.0) return 18; }
 { I x{0}; if(function_wordand(x).native_value() || x.native_value()!=2) return 10; if(!function_wordor(x).native_value() || x.native_value()!=0) return 11; B b{true}; if(function_grouped(b).native_value() || b.native_value()) return 12; }
 { I trace{0}; if(!function_nested(trace).native_value() || trace.native_value()!=134) return 13; trace=I{0}; if(!function_nestedskip(trace).native_value() || trace.native_value()!=1) return 14; }
CPP;
$cpp .= $observers . "return 0;\n}\n";
if (isset($argv[1])) { file_put_contents($argv[1] . '/numeric_logical.cpp', $cpp); }
echo "Numeric logical: admission, lazy decisions, exclusions and recovery passed\n";
