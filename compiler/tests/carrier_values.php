<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{flavor, Source_Frontend, source_file, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Project_Compiler, Incremental_Preparation, Cpp_Program, Program_Frontend, prepared_program, prepared_entry,
	value_conversion, carrier_type_definition};

function verify(bool $ok, string $message): void
{
	if (!$ok) {
		throw new RuntimeException($message);
	}
}

function declaration(flavor $flavor, string $type, string $name, string $value): string
{
	return match ($flavor) {
		flavor::cpp => "$type $name = $value;", flavor::php => '$' . "$name $type = $value;",
		flavor::js => "let $name: $type = $value;",
	};
}

/** Require a source diagnostic, rather than an internal failure or accidental C++ admission. */
function rejects(Closure $action, string $message): void
{
	try {
		$action();
	}
	catch (diagnostic|diagnostic_group $error) {
		verify(str_contains($error->getMessage(), $message), $error->getMessage());
		return;
	}
	throw new RuntimeException('Expected diagnostic: ' . $message);
}

$fixtures = [];
foreach ([flavor::cpp, flavor::php, flavor::js] as $flavor) {
	$decl = static fn(string $type, string $name, string $value) => declaration($flavor, $type, $name, $value);
	$var = static fn(string $name) => ($flavor === flavor::php ? '$' : '') . $name;
	$prepare = static fn(string $text) => (new Source_Frontend())->prepare(new source_file('carriers', $text, $flavor), new entry_target(31));
	$function = static fn(string $type, string $name, string $body) => ($flavor === flavor::cpp
		? "$type $name()" : "function $name(): $type") . '{' . $body . '}';
	$parameter = match ($flavor) {
		flavor::cpp => 'int consume(nullable<int> value = 4)',
		flavor::php => 'function consume(nullable<int> $value = 4): int',
		flavor::js => 'function consume(value: nullable<int> = 4): int',
	};
	$code = $parameter . '{return ' . $var('value') . ';}'
		. $function('float', 'number', 'return 12.75;')
		. $function('nullable<int>', 'wrapped', 'return number();')
		. $decl('?int', 'a', 'wrapped()') . $decl('result<int>', 'b', $var('a'))
		. $decl('result_or_false<int>', 'c', $var('b')) . $decl('result_or_bool<int>', 'd', $var('c'))
		. 'echo ' . $var('a') . ' + 5, "|", -' . $var('b') . ', "|", ' . $var('c') . ' * 2, "|", '
		. '(int)(' . $var('d') . ' > 10), "|", consume(' . $var('d') . '), "|", consume(), "|";'
		. $decl('byte', 'small', $var('a')) . 'echo (int)' . $var('small') . ', "|";'
		. $decl('nullable<string>', 'text', '"a\\0b"') . $decl('string', 'copy', $var('text'))
		. $var('copy') . ' = "changed";'
		. 'echo (int)(' . $var('text') . ' == "a\\0b"), "|";'
		. $decl('string', 'bytes', $var('text')) . 'echo ' . $var('bytes') . ', "|";'
		. $decl('result_or_false<bool>', 'boolean', 'false') . $decl('bool', 'plain', $var('boolean'))
		. 'echo (int)' . $var('plain') . ', "|";'
		. $decl('nullable<int>', 'empty', 'null') . $var('empty') . ' = 7;'
		. 'echo ' . $var('empty') . ' + 1, "|", 1 + ' . $var('a') . ', "|", ' . $var('a') . ' + ' . $var('b') . '; return 8;';
	$entry = $prepare($code);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify($before === serialize($entry), 'Lowering mutated prepared facts');
	verify(str_contains($cpp, 'scpp::checked_float64_to_int64('), 'Dynamic wrap skipped checked payload conversion');
	verify(str_contains($cpp, '.value()'), 'Missing checked extraction');
	verify(!str_contains($cpp, 'std::optional') && !str_contains($cpp, 'std::variant'), 'Application templates leaked');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex("17|-12|24|1|12|4|12|1|a\0b|0|8|13|24")];
	if ($flavor === flavor::php) {
		$guard = <<<'SCPP'
function missing():result_or_false<bool> { echo "once"; return false_sentinel; }
$flag result_or_false<bool> = false;
$zero result_or_false<int> = 0;
$miss result_or_false<int> = false_sentinel;
echo (int)($flag === false), (int)(false !== $flag), (int)($zero !== false),
    (int)(false === $miss), (int)(missing() === false);
return 8;
SCPP;
		$cpp = (new Cpp_Emitter())->emit($prepare($guard));
		verify(substr_count($cpp, '.is_false()') === 5 && !str_contains($cpp, '.value()'),
			'Strict PHP false guards must inspect the sentinel directly');
		$fixtures['php-false-guards'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('0111once1')];
		$pairs = <<<'SCPP'
function lookup(string $tag, string $needle): result_or_false<int> {
    echo $tag; return strpos("abc", $needle);
}
function replace_hit(result_or_false<int> &$slot): result_or_false<int> {
    $slot = false_sentinel; return $slot;
}
$miss = strpos("abc", "x");
$zero = strpos("abc", "a");
$two = strpos("abc", "c");
echo (int)($miss === $miss), (int)($zero === $miss), (int)($miss !== $zero),
     (int)($zero === $zero), (int)($two !== $zero), "|";
$flag result_or_false<bool> = false;
$absent result_or_false<bool> = false_sentinel;
echo (int)($flag === $absent), (int)($absent !== $flag), (int)($flag === $flag), "|";
$a result_or_false<string> = "";
$b result_or_false<string> = "0";
echo (int)($a === $a), (int)($a === $b), "|";
echo (int)(lookup("L","x") === lookup("R","z")), "|";
echo (int)($zero === replace_hit($zero)), (int)($zero === $miss);
return 8;
SCPP;
		$fixtures['php-false-pairs'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($pairs)),
			'stdout_hex' => bin2hex('10111|011|10|LR1|01')];
		rejects(fn() => $prepare('$a result_or_false<vector<string>> = false_sentinel; return (int)($a === $a);'),
			'Unsupported binary operands');

		$nullable_pairs = <<<'SCPP'
enum Status { case Ready=0; case Busy=1; }
function replace_absent(?int &$slot, int &$calls): ?int {
    $calls += 1; $slot = 0; return $slot;
}
function observe(string $tag, ?int $slot): ?int { echo $tag; return $slot; }
$a ?int=null; $b ?int=null; $zero ?int=0; $two ?int=2;
echo (int)($a === $b), (int)($a !== $b), (int)($a === $zero), (int)($zero !== $a),
     (int)($zero === $zero), (int)($two !== $zero), "|";
$calls=0;
echo (int)($a === replace_absent($a,$calls)), $calls, (int)($a === $zero), "|";
echo (int)(observe("L",$b) !== observe("R",$a)), "|";
$f ?bool=false; $no_flag ?bool=null;
$s ?string=""; $other ?string="0"; $no_text ?string=null;
echo (int)($f === $f), (int)($f === $no_flag), (int)($s === $s), (int)($s !== $other), (int)($no_text === $s), "|";
$x ?float=0.0; $y ?float=-0.0;
$ready ?Status=Status::Ready; $busy ?Status=Status::Busy; $none ?Status=null;
echo (int)($x === $y), (int)($ready === $ready), (int)($ready !== $busy), (int)($none === $ready);
return 8;
SCPP;
		$fixtures['php-nullable-pairs'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($nullable_pairs)),
			'stdout_hex' => bin2hex('100111|011|LR1|10110|1110')];
		foreach (['==', '!=', '===', '!=='] as $operator) {
			rejects(fn() => $prepare('$a nullable<vector<string>> = null; return (int)($a ' . $operator . ' $a);'),
				'Unsupported binary operands');
		}
		foreach (['false' => $pairs, 'nullable' => $nullable_pairs] as $family => $strict_source) {
			$source = str_replace(['===', '!=='], ['==', '!='], $strict_source);
			$cpp = (new Cpp_Emitter())->emit($prepare($source));
			$fixtures["php-$family-equality"] = ['cpp' => $cpp, 'stdout_hex' => $fixtures["php-$family-pairs"]['stdout_hex']];
		}
		$cpp = $fixtures['php-nullable-equality']['cpp'];
		verify(str_contains($cpp, '(var_a_0).has_value() ? ((var_b_1).has_value()'),
			'Stable nullable locals must compare directly');
		verify(str_contains($cpp, 'const auto binary_left = var_a_0; const auto binary_right = replace_absent_(var_a_0, var_calls_4);'),
			'Snapshot the entire left carrier before the mutating RHS');
		$states = ['p_false' => 'false', 'p_true' => 'true', 's_false' => 'false_sentinel', 's_true' => 'true_sentinel'];
		$table = '';
		foreach ($states as $name => $initial) $table .= '$' . $name . " result_or_bool<bool> = $initial;";
		$expected = '';
		foreach (['==', '!=', '===', '!=='] as $op) {
			foreach (array_keys($states) as $left) {
				foreach (array_keys($states) as $right) {
					$table .= 'echo (int)($' . $left . " $op " . '$' . $right . ');';
					$expected .= (string)(int)(($left === $right) !== str_starts_with($op, '!'));
				}
			}
		}
		$mutation = <<<'SCPP'
function change(result_or_bool<bool> &$slot): result_or_bool<bool> {
    echo "R"; $slot=true_sentinel; return $slot;
}
echo (int)($p_true === change($p_true)), (int)($p_true == $s_true);
return 8;
SCPP;
		$cpp = (new Cpp_Emitter())->emit($prepare($table . $mutation));
		verify(str_contains($cpp, '!(var_s_true_3).has_value() && (var_p_true_1).is_true() == (var_s_true_3).is_true()'),
			'Stable operands must compare the stored sentinel only after excluding a payload');
		verify(str_contains($cpp, 'const auto binary_left = var_p_true_1; const auto binary_right = change_(var_p_true_1);'),
			'Save the whole boolean-sentinel carrier before changing its slot');
		$fixtures['php-bool-sentinel-peers'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected . 'R01')];
		rejects(fn() => $prepare('$a result_or_bool<vector<string>> = true_sentinel; return (int)($a == $a);'),
			'Unsupported binary operands');
		$result_source = <<<'SCPP'
function cross_error(result<int> &$a, result<string> &$b): bool { return $a === $b && $b === $a; }
function error_peers(result<int> &$a): bool {
    $empty ?int = null;
    $floating ?float = null;
    return $a == $empty || $empty === $a || $a === 0 || 0 == $a
        || $a == $floating || $floating === $a || 0.0 == $a || $a === 0.0;
}
function ordinary(result<int> &$a, result<int> &$b): bool { return $a == $b; }
function different(result<int> &$a, result<int> &$b): bool { return $a != $b; }
function identical(result<int> &$a, result<int> &$b): bool { return $a === $b; }
function distinct(result<int> &$a, result<int> &$b): bool { return $a !== $b; }
function replace_result(result<int> &$a): result<int> { $a = 9; return $a; }
function before_replace(result<int> &$a): bool { return $a === replace_result($a); }
function fail_result(): result<int> { throw new Exception("left"); }
function right_result(): result<int> { echo "R"; return 1; }
try { echo (int)(fail_result() === right_result()); } catch (Exception $e) { echo $e->get_message(); }
return 8;
SCPP;
		$cpp = (new Cpp_Emitter())->emit($prepare($result_source));
		verify(str_contains($cpp, ': !(var_b_1).has_value()') && str_contains($cpp, ': (var_a_0).same_error_identity(var_b_1)'),
			'Stable result peers must compare directly through the selected error policy');
		verify(str_contains($cpp, 'const auto binary_left = var_a_0; const auto binary_right = replace_result_(var_a_0);'),
			'Snapshot the left result before its slot is replaced');
		// Native callers supply independent structured errors to the source-defined comparators.
		$cpp = "int test_result_equality();\n" . str_replace('::App::scpp_entry_body,', '::test_result_equality,', $cpp)
			. "\n" . '#include "result_equality_driver.hpp"';
		$fixtures['php-result-peers'] = ['files' => [
			'program.cpp' => $cpp,
			'result_equality_driver.hpp' => file_get_contents(__DIR__ . '/result_equality_driver.hpp'),
			'allocation_probe.hpp' => file_get_contents(__DIR__ . '/../../tests/ownership/allocation_probe.hpp'),
		], 'stdout_hex' => bin2hex('left')];
		rejects(fn() => $prepare('function invalid(result<vector<string>> &$a): bool { return $a == $a; } return 8;'),
			'Unsupported binary operands');

		rejects(fn() => $prepare('function invalid(result<vector<string>> &$a, vector<string> &$b): bool { return $b == $a; } return 8;'),
			'Unsupported binary operands');
		$throws = <<<'SCPP'
function fail_left(): ?int { throw new Exception("left"); }
function right(): ?int { echo "R"; return null; }
try { echo (int)(fail_left() == right()); } catch (Exception $e) { echo $e->get_message(); }
return 8;
SCPP;
		$fixtures['php-peer-failure'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($throws)),
			'stdout_hex' => bin2hex('left')];

	}
	$presence = $decl('nullable<int>', 'none', 'null')
		. $decl('result_or_false<bool>', 'successful', 'false')
		. $decl('result_or_false<bool>', 'absent', 'false_sentinel')
		. $decl('result_or_bool<int>', 'truth', 'true_sentinel')
		. 'echo (int)isset(' . $var('none') . '), (int)isset(' . $var('successful') . '), (int)isset('
		. $var('absent') . '), (int)isset(' . $var('truth') . ');'
		. 'if (' . $var('none') . ') {echo "bad";} if (' . $var('successful') . ') {echo "bad";}'
		. 'if (' . $var('truth') . ') {echo "yes";}'
		. 'echo (int)!' . $var('absent') . ';'
		. $function('nullable<int>', 'once', 'echo "once"; return 1;')
		. 'echo (int)isset(once()); if (once()) {echo "ok";}'
		. 'if (' . $var('none') . ' && once()) {echo "bad";} echo (int)true; return 8;';
	$cpp = (new Cpp_Emitter())->emit($prepare($presence));
	verify(str_contains($cpp, '(once_()).has_value()') && str_contains($cpp, '(var_absent_2).has_value()'),
		'Expected direct value-presence checks on both a call and a local');
	$fixtures[$flavor->name . '-presence'] = ['cpp' => $cpp,
		'stdout_hex' => bin2hex('0101yes1once1onceok1')];
	$fill = match ($flavor) {
		flavor::cpp => 'null fill(nullable<string> &item)',
		flavor::php => 'function fill(nullable<string> &$item): null',
		flavor::js => 'function fill(&item: nullable<string>): null',
	};
	$strict_null = $function('nullable<string>', 'text_once', 'echo "P"; return "";')
		. $function('nullable<string>', 'absent_once', 'echo "A"; return null;')
		. $function('null', 'null_once', 'echo "N"; return null;')
		. $fill . '{echo "M"; ' . $var('item') . ' = "filled"; return null;}'
		. $decl('?string', 'missing', 'null') . $decl('?string', 'blank', '""');
	foreach ([$var('missing'), $var('blank')] as $candidate) {
		$strict_null .= 'echo (int)(' . $candidate . ' === null), (int)(' . $candidate . ' !== null), '
			. '(int)(null === ' . $candidate . '), (int)(null !== ' . $candidate . ');';
	}
	$strict_null .= 'echo (int)(text_once() !== null), (int)(null_once() === absent_once()), '
		. '(int)(text_once() !== null_once());'
		. 'echo (int)(' . $var('missing') . ' === fill(' . $var('missing') . ')), (int)(' . $var('missing') . ' !== null);'
		. $var('missing') . ' = null; echo (int)(fill(' . $var('missing') . ') !== ' . $var('missing') . '); return 8;';
	$entry = $prepare($strict_null);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify($before === serialize($entry), 'Strict null comparison lowering mutated prepared facts');
	verify(str_contains($cpp, '.has_value()') && !str_contains($cpp, '.value()'), 'Strict null comparison extracted its payload');
	$fixtures[$flavor->name . '-strict-null'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('10100101P1NA1PN1M11M1')];
	$vector = $decl('vector<string>', 'items', '[]') . $var('items') . '[] = "first";'
		. $decl('nullable<vector<string>>', 'packed', $var('items'))
		. $decl('vector<string>', 'copy', $var('packed')) . $var('copy') . '[] = "second";'
		. 'echo ' . $var('items') . '[0], ' . $var('copy') . '[1]; return 8;';
	$fixtures[$flavor->name . '-vector'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($vector)),
		'stdout_hex' => bin2hex('firstsecond')];
	$reference = match ($flavor) {
		flavor::cpp => 'void change(int &value)',
		flavor::php => 'function change(int &$value): void',
		flavor::js => 'function change(&value: int): void',
	};
	// A payload extraction is a value copy, never an exact-storage reference binding.
	rejects(fn() => $prepare($reference . '{return;}' . $decl('nullable<int>', 'item', '8')
		. 'change(' . $var('item') . '); return 8;'), 'exact storage type');
	$single = $prepare($decl('nullable<int>', 'item', '12.75') . 'return 8;');
	$local = $single->file->main()->body()->statements[0];
	verify($local->initialization() === (value_conversion::floating_integer_proven | value_conversion::WRAP), 'Packed wrap code mismatch');
	verify(!$local->constant_known(), 'Carrier state leaked into scalar constants');
	foreach ([['nullable<int>', 'null'], ['result_or_false<int>', 'false_sentinel'], ['result_or_bool<int>', 'true_sentinel']] as [$type, $state]) {
		$failed = $prepare($decl($type, 'item', $state) . 'echo ' . $var('item') . ' + 1; return 8;');
		$fixtures[$flavor->name . '-' . $state] = ['cpp' => (new Cpp_Emitter())->emit($failed),
			'stdout_hex' => '', 'catch_runtime_error' => 'carrier_no_value'];
	}
	$overflow = $function('float', 'huge', 'return 1e100;')
		. $decl('nullable<int>', 'item', 'huge()') . 'return 8;';
	$fixtures[$flavor->name . '-overflow'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($overflow)),
		'stdout_hex' => '', 'catch_out_of_range' => true];
	foreach ([
		[$decl('result_or_false<int>', 'bad', 'null'), 'Null cannot construct'],
		[$decl('nullable<int>', 'bad', 'false_sentinel'), 'Sentinel is not supported'],
		[$decl('result_or_false<int>', 'bad', 'true_sentinel'), 'Sentinel is not supported'],
		[$decl('nullable<int>', 'bad', '"text"'), 'conversion'],
		[$decl('nullable<int>', 'bad', '8') . $var('bad') . ' = 12.75;', 'conversion'],
		[$decl('nullable<int>', 'bad', '1e100'), 'outside int64 range'],
	] as [$text, $message]) {
		rejects(fn() => $prepare($text . 'return 8;'), $message);
	}
	$directory = sys_get_temp_dir() . '/scpp-carrier-values-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		$main = $directory . '/main.' . $flavor->name;
		$library = $directory . '/library.' . $flavor->name;
		$pair_check = $flavor === flavor::php ? '$sink ?float = 1.0; $sink += provide(); echo (int)(provide() === provide()), (int)(provide() == provide()), (int)(provide() === 0);' : '';
		$sum = $flavor === flavor::php ? '(int)(provide() + 5)' : 'provide() + 5';
		file_put_contents($main, $pair_check . 'echo (int)isset(provide()), ' . $sum . '; return 8;');
		$compiler = new Project_Compiler();
		$session = null;
		$root = null;
		$slot = null;
		$target = new entry_target(31);
		$revision = time();
		$steps = [['nullable<int>', '12'], ['result<int>', '13.75'], ['nullable<string>', '"wrong"'],
			['retry', ''], ['result_or_false<int>', '14'], ['delete', ''], ['nullable<int64>', '15']];
		if ($flavor === flavor::php) {
			$steps[] = ['nullable<float>', '16.0'];
			$steps[] = ['result_or_bool<int>', '16'];
		}
		foreach ($steps as $step => [$type, $value]) {
			if ($type !== 'retry') {
				if ($type === 'delete') {
					unlink($library);
				}
				else {
					file_put_contents($library, $function($type, 'provide', 'return ' . $value . ';'));
					touch($library, ++$revision);
				}
				$session = $compiler->frontend($directory, $session);
			}
			$root ??= $session->entry->entry->ast;
			$slot ??= $session->scope->entry('func:provide');
			verify($root === $session->entry->entry->ast, 'Unchanged consumer AST replaced');
			verify($slot === $session->scope->entry('func:provide'), 'Stable symbol replaced');
			if (in_array($type, ['nullable<string>', 'retry', 'delete'], true)) {
				rejects(fn() => Incremental_Preparation::prepare($session, $target), $type === 'delete' ? 'Unknown function' : 'Unsupported');
				continue;
			}
			$stats = Incremental_Preparation::prepare($session, $target);
			verify($stats['prepared'] === 2, 'Changed contract did not prepare observing body');
			$files = $session->files();
			$incremental = Cpp_Program::emit(new prepared_program($session->entry_key(), $files, new prepared_entry($root, $target), $session))->files;
			$sources = [];
			foreach ($files as $key => $file) {
				$sources[$key] = $file->source();
			}
			$fresh = Cpp_Program::emit((new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target))->files;
			verify($incremental === $fresh, 'Incremental differs from fresh');
			$fixtures[$flavor->name . '-incremental-' . $step] = ['files' => $incremental, 'stdout_hex' => bin2hex(($pair_check === '' ? '1' : '1101') . (string)((int)(float)$value + 5))];
		}
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 2, 'No-op failed to reuse bodies');
	}
	finally {
		foreach (glob($directory . '/*') as $path) {
			unlink($path);
		}
		rmdir($directory);
	}
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Packed carrier conversions, payload operators and incremental recovery passed\n";
