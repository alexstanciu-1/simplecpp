<?php
namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/s2s_proof.php';

/** Compare syntax/scopes independently of retained preparation and dependency bookkeeping. */
function s2s_snapshot(parsed_file $syntax): string
{
	$seen = new \SplObjectStorage();
	$visit = function ($value) use (&$visit, $seen)
	{
		if (is_array($value)) {
			return array_map($visit, $value);
		}
		if (!is_object($value) || $value instanceof \UnitEnum) {
			return $value;
		}
		if (isset($seen[$value])) {
			return ['ref' => $seen[$value]];
		}
		$seen[$value] = spl_object_id($value);
		$result = ['class' => get_class($value), 'id' => spl_object_id($value)];
		foreach ((new \ReflectionClass($value))->getProperties() as $property) {
			// Successful preparation settles the body dirty flag without changing source syntax.
			if (in_array($property->name, ['prepared_facts', 'preparation', 'body_preparation', 'body_work', 'syntax_changed', 'prepared', 'preparation_lookups', 'change_status', 'preparation_changes'], true)) {
				continue;
			}
			$result[$property->name] = $visit($property->getValue($value));
		}
		return $result;
	};
	return serialize($visit($syntax));
}

/** Standalone source setup also verifies private roots publish through global scope. */
function s2s_parse(string $text): parsed_file
{
	$input = new file();
	$input->path = 's2s.phs';
	$input->content = $text;
	$syntax = (new Parser((new Tokenizer($input))->tokenize()))->parse();
	$module = new module('.', Source_Registry::normalize('.'), '.');
	$modules /** Keyed_Storage<module> */ = Model::$modules;
	$modules->add($module->name, $module);
	$record = Source_Registry::add($module, $input);
	Source_Publication::publish_parsed($record, $syntax);
	return $syntax;
}

S2S_Proof::run();
$directory = $argv[1];
$cases = [
	'nullable_probe_null_isset' => ['return isset(null);', 0],
	'nullable_probe_null_empty' => ['return empty(null);', 1],
	'nullable_probe_absent' => ['$x ?int = null; return empty($x) && !isset($x);', 1],
	'nullable_probe_zero' => ['$x ?int = 0; return empty($x) && isset($x);', 1],
	'nullable_probe_false' => ['$x ?bool = false; return empty($x) && isset($x);', 1],
	'nullable_probe_float' => ['$x ?float = 0.0; return empty($x) && isset($x);', 1],
	'nullable_probe_string_empty' => ['$x ?string = ""; return empty($x) && isset($x);', 1],
	'nullable_probe_string_zero' => ['$x ?string = "0"; return !empty($x) && isset($x);', 1],
	'nullable_probe_present' => ['$x ?int = 7; return !empty($x) && isset($x);', 1],
	'nullable_probe_plain_false' => ['return empty(false) && isset(false);', 1],
	'nullable_probe_plain_zero' => ['return empty(0) && isset(0);', 1],
	'nullable_probe_plain_float' => ['return empty(0.0) && isset(0.0);', 1],
	'nullable_probe_plain_string' => ['return empty("") && !empty("0") && isset("");', 1],
	'nullable_probe_once' => ['function probe(int &$n): ?int { $n++; return null; } $n = 0; $v = empty(probe($n)); return $v ? $n : 9;', 1],
	'nullable_probe_isset_once' => ['function probe(int &$n): int { $n++; return 0; } $n = 0; $v = isset(probe($n)); return $v ? $n : 9;', 1],
	'nullable_probe_nonmutating' => ['$x ?int = null; $a = isset($x); $b = empty($x); return $x === null;', 1],
	'nullable_probe_throw' => ['function fail(): bool { return empty(1 / 0); } return 0;', 0],
	'nullable_elvis_empty' => ['$x ?int = null; $y = $x ?: 7; return $y ?? 9;', 7],
	'nullable_elvis_zero' => ['$x ?int = 0; $y = $x ?: 7; return $y ?? 9;', 7],
	'nullable_elvis_present' => ['$x ?int = 3; $y = $x ?: (1 / 0); return $y ?? 9;', 3],
	'nullable_elvis_both_empty' => ['$x ?int = null; $y ?int = null; $z = $x ?: $y; return $z === null;', 1],
	'nullable_elvis_scalar_left' => ['$x ?int = null; $y = 3 ?: $x; return $y ?? 9;', 3],
	'nullable_elvis_scalar_zero' => ['$x ?int = null; $y = 0 ?: $x; return $y === null;', 1],
	'nullable_elvis_bool' => ['$x ?bool = false; $y = $x ?: true; return $y ?? false;', 1],
	'nullable_elvis_float' => ['$x ?float = 0.0; $y = $x ?: 3.5; return $y ?? 9.5;', 3],
	'nullable_elvis_string' => ['$x ?string = "0"; $y = $x ?: "7"; $z string = $y; return (int) $z;', 7],
	'nullable_elvis_once' => ['function probe(int &$n): ?int { $n++; return 3; } function other(int &$n): int { $n++; return 7; } $n = 0; $x = probe($n) ?: other($n); $v int = $x; return $v + $n;', 4],
	'nullable_elvis_fallback_once' => ['function probe(int &$n): ?int { $n++; return 0; } function other(int &$n): int { $n++; return 7; } $n = 0; $x = probe($n) ?: other($n); $v int = $x; return $v + $n;', 9],
	'nullable_ternary_left' => ['$x ?int = null; $y = true ? $x : 7; return $y === null;', 1],
	'nullable_ternary_right' => ['$x ?int = null; $y = false ? 7 : $x; return $y === null;', 1],
	'nullable_ternary_wrap_left' => ['$x ?int = null; $y = true ? 7 : $x; return $y ?? 9;', 7],
	'nullable_ternary_wrap_right' => ['$x ?int = null; $y = false ? $x : 7; return $y ?? 9;', 7],
	'nullable_ternary_copy' => ['$x ?int = 3; $z ?int = null; $y = true ? $x : $z; $x = 7; return $y ?? 9;', 3],
	'nullable_ternary_lazy' => ['$x ?int = 3; $y = true ? $x : (1 / 0); return $y ?? 9;', 3],
	'nullable_ternary_required_empty' => ['function fail(): int { $x ?int = null; return true ? $x : 7; } return 0;', 0],
	'nullable_condition_empty' => ['$x ?int = null; if ($x) { return 9; } return 2;', 2],
	'nullable_condition_zero' => ['$x ?int = 0; return $x ? 9 : 2;', 2],
	'nullable_condition_false' => ['$x ?bool = false; return $x ? 9 : 2;', 2],
	'nullable_condition_true' => ['$x ?bool = true; return $x ? 3 : 9;', 3],
	'nullable_condition_float' => ['$x ?float = 0.0; if ($x) { return 9; } $x = 1.5; return $x ? 3 : 9;', 3],
	'nullable_condition_string' => ['$x ?string = "yes"; return $x ? 3 : 9;', 3],
	'nullable_condition_string_zero' => ['$x ?string = "0"; return $x ? 9 : 2;', 2],
	'nullable_condition_string_empty' => ['$x ?string = ""; return $x ? 9 : 2;', 2],
	'nullable_condition_elseif' => ['$x ?int = 3; if (false) { return 9; } elseif ($x) { return 3; } return 9;', 3],
	'nullable_condition_while' => ['$x ?int = 3; $n = 0; while ($x) { $n++; $x = null; } return $n;', 1],
	'nullable_condition_do' => ['$x ?int = null; $n = 0; do { $n++; } while ($x); return $n;', 1],
	'nullable_condition_for' => ['$x ?int = 3; $n = 0; for (; $x; $x = null) { $n++; } return $n;', 1],
	'nullable_condition_once' => ['function probe(int &$n): ?int { $n++; return 3; } $n = 0; if (probe($n)) { return $n; } return 9;', 1],
	'nullable_coalesce_empty' => ['$x ?int = null; return $x ?? 7;', 7],
	'nullable_coalesce_zero' => ['$x ?int = 0; return $x ?? 7;', 0],
	'nullable_coalesce_false' => ['$x ?bool = false; return $x ?? true;', 0],
	'nullable_coalesce_string' => ['$x ?string = "0"; $y = $x ?? "9"; return (int) $y;', 0],
	'nullable_coalesce_string_empty' => ['$x ?string = ""; $y = $x ?? "9"; return $y ? 9 : 1;', 1],
	'nullable_coalesce_float' => ['$x ?float = null; return $x ?? 3.5;', 3],
	'nullable_coalesce_scalar' => ['return 0 ?? (1 / 0);', 0],
	'nullable_coalesce_lazy' => ['$x ?int = 3; return $x ?? (1 / 0);', 3],
	'nullable_coalesce_once' => ['function probe(int &$n): ?int { $n++; return null; } function fallback(int &$n): int { $n++; return 7; } $n = 0; $x = probe($n) ?? fallback($n); return $x + $n;', 9],
	'nullable_coalesce_skip' => ['function probe(int &$n): ?int { $n++; return 0; } function fallback(int &$n): int { $n++; return 7; } $n = 0; $x = probe($n) ?? fallback($n); return $x + $n;', 1],
	'nullable_coalesce_chain' => ['$a ?int = null; $b ?int = null; return $a ?? $b ?? 7;', 7],
	'nullable_coalesce_precedence' => ['$x ?int = null; return $x ?? 1 + 2 ? 7 : 9;', 7],
	'nullable_coalesce_logic' => ['$x ?bool = null; return $x ?? false || true;', 1],
	'nullable_coalesce_value' => ['$x ?int = 3; $y = $x ?? 7; $y = 5; $z int = $x; return $z;', 3],
	'nullable_coalesce_throw' => ['function fail(): int { $x ?int = null; return $x ?? (1 / 0); } return 0;', 0],
	'nullable_empty' => ['$x ?int = null; return $x === null;', 1],
	'nullable_default_empty' => ['$x nullable<int>; return null === $x;', 1],
	'nullable_present' => ['$x ?int = 7; $y int = $x; return $y;', 7],
	'nullable_reset' => ['$x ?int = 7; $x = null; return $x === null;', 1],
	'nullable_reassign' => ['$x ?int = null; $x = 8; $y int = $x; return $y;', 8],
	'nullable_copy' => ['$x ?int = 4; $y nullable<int> = $x; $x = null; $z int = $y; return $z;', 4],
	'nullable_copy_empty' => ['$x ?int = null; $y = $x; $x = 3; return $y === null;', 1],
	'nullable_false_present' => ['$x ?bool = false; if ($x === null) { return 9; } $y bool = $x; return $y;', 0],
	'nullable_zero_present' => ['$x ?int = 0; return null !== $x;', 1],
	'nullable_float' => ['$x ?float = 3.5; $y float = $x; return $y;', 3],
	'nullable_string_empty_present' => ['$x ?string = ""; return $x !== null;', 1],
	'nullable_string_copy' => ['$x ?string = "7"; $y ?string = $x; $x = "8"; $z string = $y; return (int) $z;', 7],
	'nullable_alias' => ['$v byte = 258; $x ?byte = $v; $y nullable<uint8> = $x; $z uint8 = $y; return $z;', 2],
	'nullable_calls' => ['function carry(?int $x): nullable<int> { return $x; } function read(?int $x): int { return $x; } return read(carry(6));', 6],
	'nullable_return_null' => ['function none(): ?int { return null; } return none() === null;', 1],
	'nullable_return_value' => ['function some(): ?int { return 5; } $x int = some(); return $x;', 5],
	'nullable_argument_extract' => ['function read(int $x): int { return $x; } $x ?int = 8; return read($x);', 8],
	'nullable_null_argument' => ['function empty_value(?int $x): bool { return $x === null; } return empty_value(null);', 1],
	'nullable_compare_once' => ['function probe(int &$n): ?int { $n++; return null; } $n = 0; $x = null === probe($n); return $n;', 1],
	'nullable_extract_once' => ['function probe(int &$n): ?int { $n++; return 7; } $n = 0; $x int = probe($n); return $x + $n;', 8],
	'nullable_store_error' => ['function fail(int &$target): void { $x ?int = null; $target = $x; } return 0;', 0],
	'nullable_reference' => ['function clear(?int &$x): void { $x = null; } $x ?int = 3; clear($x); return $x === null;', 1],
	'nullable_for' => ['for ($x ?int = null; $x === null; $x = 2) {} return 1;', 1],
	'nullable_ternary_statement' => ['$x = true; $x ? PHP_INT_MAX : 2; return 0;', 0],
	'nullable_empty_extract' => ['function fail(): int { $x ?int = null; return $x; } return 0;', 0],
	'nullable_empty_string_extract' => ['function fail(): string { $x ?string = null; return $x; } return 0;', 0],
	'nullable_empty_argument' => ['function read(int $x): int { return $x; } function fail(): int { $x ?int = null; return read($x); } return 0;', 0],
	'nullable_explicit_string' => ['$x ?string = null; $y = (string) $x; return 0;', 0],
	'value_entry' => ['$a value<int> = 7; return $a;', 7],
	'value_interchange' => ['function carry(value<int> $x): int { return $x; } function plain(int $x): value<int> { return $x; } $a value<int> = 2; $b int = $a; $a = $b + 1; $c = carry($a); $d = plain($b); return $c + $d;', 5],
	'value_reference' => ['function bump(value<int> &$x): void { ++$x; } function plain(int &$x): void { ++$x; } $a int = 1; $b value<int> = 2; bump($a); plain($b); return $a + $b;', 5],
	'value_alias' => ['$a value<byte> = 258; $b uint8 = $a; return $b;', 2],
	'value_bool' => ['$a value<bool> = true; return $a && true;', 1],
	'value_float' => ['$a value<float> = 3.5; $b float = $a; return $b;', 3],
	'value_nested' => ['$a value<value<int>> = 9; return $a;', 9],
	'value_record' => ['struct Point { uint16 $x; } function read(value<Point> $p): int { return $p->x; } $a Point; $a->x = 6; $b value<Point> = $a; return read($b);', 6],
	'interp_simple' => ['$name = "Alex"; $a = "Hello $name";', 0],
	'interp_braced' => ['$count = 3; $a = "Count: {$count}";', 0],
	'interp_adjacent' => ['$x = "a"; $y = "b"; $a = "$x$y{$x}";', 0],
	'interp_suffix' => ['$x = "a"; $a = "{$x}_end[0]()";', 0],
	'interp_long_name' => ['$x = "bad"; $xy = "good"; $a = "$xy";', 0],
	'interp_empty' => ['$x = ""; $a = "<$x>";', 0],
	'interp_self' => ['$a = "old"; $a = "[$a]";', 0],
	'interp_snapshot' => ['$x = "old"; $a = "$x"; $x = "new";', 0],
	'interp_integer' => ['$x = -12; $a = "$x";', 0],
	'interp_narrow' => ['$x uint8 = 255; $a = "$x";', 0],
	'interp_bool' => ['$yes = true; $no = false; $a = "<$yes:$no>";', 0],
	'interp_float' => ['$x = 1.5; $a = "$x";', 0],
	'interp_parameter' => ['function show(string $x): string { return "<$x>"; } $a = show("ok");', 0],
	'interp_concat' => ['$x = "a"; $a = "[$x]" . "!";', 0],
	'interp_compound' => ['$x = "b"; $a = "a"; $a .= "$x";', 0],
	'interp_escapes' => ['$x = "v"; $a = "\\"$x\\"\\n\\t\\$x";', 0],
	'interp_binary' => ['$x = "v\\0z"; $a = "\\0$x\\x00";', 0],
	'interp_utf8' => ['$name = "世界"; $a = "Hi {$name}!";', 0],
	'interp_single_quote' => ['$a = \'hello $name {$name}\';', 0],
	'interp_literal_escape' => ['$x = "v"; $a = "\\x24x=$x";', 0],

	'power_basic' => ['return 2 ** 3;', 8],
	'power_right' => ['return 2 ** 3 ** 2 == 512;', 1],
	'power_grouped' => ['return (2 ** 3) ** 2;', 64],
	'power_unary' => ['return -2 ** 2 == -4;', 1],
	'power_negative_base' => ['return (-2) ** 2;', 4],
	'power_unary_chain' => ['return -2 ** 2 ** 3 == -256;', 1],
	'power_product' => ['return 2 * 3 ** 2 + 1;', 19],
	'power_sum_exponent' => ['return 2 ** (1 + 2);', 8],
	'power_zero' => ['return 0 ** 0;', 1],
	'power_huge_exponent' => ['return (-1) ** PHP_INT_MAX == -1;', 1],
	'power_maximum' => ['return PHP_INT_MAX ** 1 == PHP_INT_MAX;', 1],
	'power_minimum' => ['return (-2) ** 63 == (-PHP_INT_MAX - 1);', 1],
	'power_minimum_identity' => ['return (-PHP_INT_MAX - 1) ** 1 == (-PHP_INT_MAX - 1);', 1],
	'power_square_boundary' => ['return 3037000499 ** 2 == 9223372030926249001;', 1],
	'power_compound_rhs' => ['$a = 1; $a += 2 ** 3; return $a;', 9],
	'power_dynamic' => ['function power(int $a, int $b): int { return $a ** $b; } return power(2, 6);', 64],
	'power_short_circuit' => ['return false && (2 ** -1 == 0);', 0],
	'power_negative_error' => ['function fail(): int { return 2 ** -1; } return 0;', 0],
	'power_signed_exponent_error' => ['function fail(): int { return 2 ** -2 ** 2; } return 0;', 0],
	'power_overflow_error' => ['function fail(): int { return 2 ** 63; } return 0;', 0],
	'power_store_error' => ['function fail(int &$x): int { return $x = 2 ** 63; } return 0;', 0],

	'logic_word_and' => ['$a = false; $b = true; $c = false; $a = $b and $c; return $a;', 1],
	'logic_word_or' => ['$a = false; $b = false; $c = true; $a = $b or $c; return $a;', 0],
	'logic_word_xor' => ['$a = false; $b = true; $c = true; $a = $b xor $c; return $a;', 1],
	'logic_symbolic_and' => ['$a = false; $a = true && false; return $a;', 0],
	'logic_symbolic_or' => ['$a = false; $a = false || true; return $a;', 1],
	'logic_grouped_and' => ['$a = (true and false); return $a;', 0],
	'logic_grouped_or' => ['$a = (false or true); return $a;', 1],
	'logic_grouped_xor' => ['$a = (true xor true); return $a;', 0],
	'logic_precedence' => ['return true or true xor true and false;', 1],
	'logic_symbolic_precedence' => ['return false and true || true;', 0],
	'logic_and_skip' => ['$a = false; false and ($a = true); return $a;', 0],
	'logic_and_write' => ['$a = false; true and ($a = true); return $a;', 1],
	'logic_or_skip' => ['$a = false; true or ($a = true); return $a;', 0],
	'logic_or_write' => ['$a = false; false or ($a = true); return $a;', 1],
	'logic_symbolic_skip' => ['$a = false; false && ($a = true); true || ($a = true); return $a;', 0],
	'logic_symbolic_write' => ['$a = false; true && ($a = true); return $a;', 1],
	'logic_left_snapshot' => ['$a = false; return ($a = true) xor ($a = false);', 1],
	'logic_xor_write_order' => ['$a = false; ($a = true) xor ($a = false); return $a;', 0],
	'logic_xor_reads' => ['$a = true; return $a xor ($a = false);', 1],
	'logic_xor_associativity' => ['return true xor true xor true;', 1],
	'logic_and_throw_skipped' => ['return false and (bool)(1 / 0);', 0],
	'logic_or_throw_skipped' => ['return true or (bool)(1 / 0);', 1],
	'logic_grouped_literal' => ['return (true) and (false) or (true);', 1],
	'logic_assignment_throw' => ['function fail(int &$x): int { return $x = 1 / 0; } return 0;', 0],
	'logic_xor_throw' => ['function fail(): bool { return true xor (bool)(1 / 0); } return 0;', 0],
	'logic_assignment_return' => ['$a = 0; return $a = 7;', 7],
	'logic_assignment_group_target' => ['$a = 0; return (($a)) = 8;', 8],
	'logic_assignment_chain' => ['$a = false; $b = false; return $a = $b = true;', 1],
	'logic_assignment_declaration_chain' => ['$a = ($b = 3); return $a + $b;', 6],
	'logic_assignment_conversion' => ['$a uint8 = 0; $b uint8 = ($a = 258); return $b;', 2],
	'logic_assignment_snapshot' => ['$a = 1; $b = ($a = 3); $a = 8; return $a * 10 + $b;', 83],
	'logic_assignment_parameter' => ['function change(bool &$a): bool { return $a = true; } $x = false; $r = change($x); return $r && $x;', 1],
	'logic_assignment_arguments' => ['function pair(int $a, int $b): int { return $a * 10 + $b; } $x = 0; return pair($x = 2, $x = 3);', 23],
	'logic_assignment_call_once' => ['function next(int &$x): int { return ++$x; } $x = 0; $a = 0; $b = 0; $b = $a = next($x); return $x * 100 + $a * 10 + $b;', 111],
	'logic_nested_assignment_call_once' => ['function next(int &$x): int { return ++$x; } $x = 0; $a = 0; $b int = ($a = next($x)); return $x * 100 + $a * 10 + $b;', 111],
	'logic_cast_assignment' => ['$a = 0; return (bool)($a = 2) and true;', 1],

	'bits_and' => ['return 12 & 10;', 8],
	'bits_or' => ['return 12 | 3;', 15],
	'bits_xor' => ['return 12 ^ 5;', 9],
	'bits_negative' => ['return -1 & 15;', 15],
	'bits_precedence' => ['return 1 | 6 ^ 3 & 1;', 7],
	'bits_shift_precedence' => ['return 1 << 2 + 1;', 8],
	'bits_shift_associativity' => ['return 64 >> 2 >> 1;', 8],
	'bits_shift_grouped' => ['return 1 << (2 + 2);', 16],
	'bits_shift_zero' => ['$a = -7 >> 0; return $a + 7;', 0],
	'bits_left_zero' => ['$a = -7 << 0; return $a + 7;', 0],
	'bits_right_negative' => ['$a = -3 >> 1; return $a + 2;', 0],
	'bits_left_negative' => ['$a = -3 << 1; return $a + 6;', 0],
	'bits_sign_bit' => ['$a = 1 << 63; return 0;', 0],
	'bits_wrap_left' => ['$a = PHP_INT_MAX << 1; return $a + 2;', 0],
	'bits_right_63' => ['$a = (-PHP_INT_MAX - 1) >> 63; return $a + 1;', 0],
	'bits_positive_right_63' => ['return PHP_INT_MAX >> 63;', 0],
	'bits_negative_left_63' => ['$a = -1 << 63; return 0;', 0],
	'bits_compound_and' => ['$a = 7; $a &= 3; return $a;', 3],
	'bits_compound_or' => ['$a = 1; $a |= 4; return $a;', 5],
	'bits_compound_xor' => ['$a = 7; $a ^= 3; return $a;', 4],
	'bits_compound_left' => ['$a = 3; $a <<= 2; return $a;', 12],
	'bits_compound_right' => ['$a = -3; $a >>= 1; return $a + 2;', 0],
	'bits_compound_sign_bit' => ['$a = 1; $a <<= 63; return 0;', 0],
	'bits_compound_right_63' => ['$a = -PHP_INT_MAX - 1; $a >>= 63; return $a + 1;', 0],
	'bits_compound_snapshot' => ['$b = 3; $a = ($b <<= 1); $b >>= 1; return $a * 10 + $b;', 63],
	'bits_reference' => ['function shift(int &$x): int { return $x <<= 2; } $b = 3; $a = shift($b); return $a + $b;', 24],

	'compound_add' => ['$a = 3; $a += 2; return $a;', 5],
	'compound_add_initialized' => ['$a = 1; $a += 1; return $a;', 2],
	'compound_sub' => ['$a = 3; $a -= 2; return $a;', 1],
	'compound_mul' => ['$a = 3; $a *= 2; return $a;', 6],
	'compound_div' => ['$a = 7; $a /= 3; return $a;', 2],
	'compound_mod' => ['$a = 7; $a %= 3; return $a;', 1],
	'compound_concat' => ['$a = "a"; $a .= "b";', 0],
	'compound_concat_cast' => ['$a = "a"; $a .= (string)(1 + 2);', 0],
	'compound_concat_self' => ['$a = "a"; $a .= $a;', 0],
	'compound_concat_snapshot' => ['$b = "a"; $a = ($b .= "b"); $b .= "c";', 0],
	'compound_snapshot' => ['$b = 3; $a = ($b += 2); $b++; return $a * 10 + $b;', 56],
	'compound_precedence' => ['$a = 3; $a += 2 * 4; return $a;', 11],
	'compound_self' => ['$a = 3; $a += $a; return $a;', 6],
	'compound_negative_div' => ['$a = -7; $a /= 3; return $a + 2;', 0],
	'compound_negative_mod' => ['$a = -7; $a %= 3; return $a + 1;', 0],
	'compound_value_parameter' => ['function update(int $x): int { return $x += 2; } $b = 3; $a = update($b); return $a * 10 + $b;', 53],
	'compound_reference_parameter' => ['function update(int &$x): int { return $x += 2; } $b = 3; $a = update($b); return $a * 10 + $b;', 55],
	'compound_argument_order' => ['function pair(int $a, int $b): int { return $a * 10 + $b; } $x = 1; return pair($x += 1, $x += 1);', 23],
	'compound_div_zero' => ['function fail(int &$x): int { return $x /= 0; } return 0;', 0],
	'compound_mod_zero' => ['function fail(int &$x): int { return $x %= 0; } return 0;', 0],

	'mutation_preinc' => ['$b = 3; $a = ++$b; return $a * 10 + $b;', 44],
	'mutation_postinc' => ['$b = 3; $a = $b++; return $a * 10 + $b;', 34],
	'mutation_predec' => ['$b = 3; $a = --$b; return $a * 10 + $b;', 22],
	'mutation_postdec' => ['$b = 3; $a = $b--; return $a * 10 + $b;', 32],
	'mutation_stmt_preinc' => ['$b = 3; ++$b; return $b;', 4],
	'mutation_stmt_postinc' => ['$b = 3; $b++; return $b;', 4],
	'mutation_stmt_predec' => ['$b = 3; --$b; return $b;', 2],
	'mutation_stmt_postdec' => ['$b = 3; $b--; return $b;', 2],
	'mutation_snapshot' => ['$b = 3; $a = ++$b; $b++; return $a * 10 + $b;', 45],
	'mutation_self_post' => ['$b = 3; $b = $b++; return $b;', 3],
	'mutation_self_pre' => ['$b = 3; $b = ++$b; return $b;', 4],
	'mutation_grouped' => ['$b = 3; $a = ($b)++; return $a * 10 + $b;', 34],
	'mutation_value_parameter' => ['function value(int $x): int { return ++$x; } $b = 3; $a = value($b); return $a * 10 + $b;', 43],
	'mutation_reference_parameter' => ['function value(int &$x): int { return $x--; } $b = 3; $a = value($b); return $a * 10 + $b;', 32],
	'mutation_argument_order' => ['function pair(int $a, int $b): int { return $a * 10 + $b; } $x = 3; $a = pair($x++, $x++); return $a + $x;', 39],
	'mutation_prefix_arguments' => ['function pair(int $a, int $b): int { return $a * 10 + $b; } $x = 3; return pair(++$x, ++$x);', 45],
	'mutation_maximum' => ['$x = PHP_INT_MAX - 1; $a = ++$x; return 0;', 0],
	'mutation_minimum' => ['$x = -PHP_INT_MAX; $a = --$x; return 0;', 0],

	'unary_negative' => ['$b = 3; $a = -$b; return $a + 3;', 0],
	'unary_positive' => ['$b = 3; $a = +$b; return $a;', 3],
	'unary_complement' => ['$b = 3; $a = ~$b; return $a + 4;', 0],
	'unary_not' => ['$b = false; $a = !$b; return $a;', 1],
	'unary_not_true' => ['return !true;', 0],
	'unary_precedence' => ['return -2 * 3 + 8;', 2],
	'unary_grouped' => ['return -(2 + 3) + 8;', 3],
	'unary_nested' => ['return - -3 + +2;', 5],
	'unary_complement_nested' => ['return ~~3;', 3],
	'unary_logic' => ['return !false && !!true;', 1],
	'unary_comparison' => ['return !(1 == 2);', 1],
	'unary_cast' => ['return -(int)3.5 + (int)(-2) + 8;', 3],
	'unary_minimum' => ['$a = -PHP_INT_MAX - 1; return 0;', 0],
	'unary_complement_limit' => ['$a = ~PHP_INT_MAX; return 0;', 0],
	'unary_grouped_constant' => ['return (PHP_INT_MAX) - PHP_INT_MAX;', 0],

	'float_explicit' => ['$a float = 10.5; return $a;', 10],
	'float_copy' => ['$a = 10.5; $b float = $a; $a = .5; return $b;', 10],
	'float_reassign' => ['$a = 10.5; $a = 2.5; return $a;', 2],
	'float_direct' => ['return 10.e-1;', 1],
	'bool_true' => ['$a = true; return $a;', 1],
	'bool_false' => ['$a = false; return $a;', 0],
	'bool_explicit' => ['$a bool = true; return $a;', 1],
	'bool_copy' => ['$a = true; $b bool = $a; $a = false; return $b;', 1],
	'bool_reassign' => ['$a = true; $a = false; return $a;', 0],
	'bool_direct' => ['return false;', 0],
	'bool_and_int' => ['$a = true; $b = 7; return $b;', 7],
	'cast_int' => ['$b = 3.5; $a = (int)$b; return $a;', 3],
	'cast_float' => ['$b = 4; $a = (float)$b; return $a;', 4],
	'cast_bool' => ['$b = 1; $a = (bool)$b; return $a;', 1],
	'cast_string' => ['$b = 7; $a = (string)$b;', 0],
	'cast_identity' => ['$b = 7; $a = (int)$b; return $a;', 7],
	'cast_fixed_width' => ['$b = 7; $a = (uint8)$b; return $a;', 7],
	'cast_alias_identity' => ['$b uint8 = 7; $a = (byte)$b; return $a;', 7],
	'cast_field' => ['struct Box { int32 $value; } $box Box; $box->value = 9; return (int)$box->value;', 9],
	'cast_nested' => ['$b = 2; $a = (string)(float)$b;', 0],
	'string_single' => ['$a = \'x\';', 0],
	'string_double' => ['$a = "x";', 0],
	'string_empty_single' => ['$a = \'\';', 0],
	'string_empty_double' => ['$a = "";', 0],
	'string_explicit' => ['$x string = "test"; $x = "next";', 0],
	'constant_int_max' => ['$a = PHP_INT_MAX;', 0],
	'constant_name_call' => ['function PHP_INT_MAX(): int { return 3; } return PHP_INT_MAX();', 3],
	'constant_namespaces' => ['$PHP_INT_MAX = 1; $a = PHP_INT_MAX; return $PHP_INT_MAX;', 1],
	'chain' => ['$a = $b = 1; return $a;', 1],
	'chain_deep' => ['$a = $b = $c = 4; return $a;', 4],
	'chain_existing_outer' => ['$a = 2; $a = $b = 1; return $a;', 1],
	'chain_existing_inner' => ['$b = 2; $a = $b = 1; return $b;', 1],
	'chain_same_target' => ['$a = $a = 1; return $a;', 1],
	'chain_conversion' => ['$b uint8 = 0; $a = $b = 257; return $a;', 1],
	'chain_call_once' => ['function value(): int { return 5; } $a = $b = value(); return $a;', 5],
	'literal' => ['$a = 10;', 0],
	'value' => ['$a = 10; return $a;', 10],
	'explicit' => ['$a int = 10; return $a;', 10],
	'var_chain_002' => ['$a = 1; $b = $a;', 0],
	'var_chain_003' => ['$a = 1; $b = $a; $c = $b;', 0],
	'var_chain_004' => ['$a = 1; $b = $a + 1;', 0],
	'var_reassign_002' => ['$a = 1; $a = $a + 1;', 0],
	'var_reassign_002_value' => ['$a = 1; $a = $a + 1; return $a;', 2],
	'var_reassign_003' => ['$a = 1; $a = $a + $a;', 0],
	'var_reassign_003_value' => ['$a = 1; $a = $a + $a; return $a;', 2],
	'expr_arith_001' => ['$a = 1 + 2; return $a;', 3],
	'sub_catalog' => ['$a = 1 - 2; return $a + 2;', 1],
	'sub_left' => ['return 10 - 3 - 2;', 5],
	'sub_grouped' => ['return 10 - (3 - 2);', 9],
	'sub_mixed' => ['return 10 - 3 + 2;', 9],
	'sub_mixed_reverse' => ['return 10 + 3 - 2;', 11],
	'sub_reassignment' => ['$a = 10; $a = $a - 3; return $a;', 7],
	'sub_cast' => ['$x uint8 = 7; return (int)$x - 2;', 5],
	'sub_minimum' => ['$a = 0 - PHP_INT_MAX - 1; return $a + PHP_INT_MAX + 1;', 0],
	'sub_maximum' => ['$a = (PHP_INT_MAX) - 0; return 0;', 0],
	'div_catalog' => ['$a = 4 / 2; return $a;', 2],
	'div_truncate' => ['return 7 / 3;', 2],
	'div_negative' => ['return (0 - 7) / 3 + 10;', 8],
	'div_negative_rhs' => ['return 7 / (0 - 3) + 10;', 8],
	'div_precedence' => ['return 20 / 2 * 3 + 1;', 31],
	'div_grouped' => ['return 20 / (2 * 2);', 5],
	'div_minimum' => ['$a = (0 - PHP_INT_MAX - 1) / 1; return $a + PHP_INT_MAX + 1;', 0],
	'div_zero_guard' => ['function fail(): int { return 1 / 0; } return 0;', 0],
	'mod_catalog' => ['$a = 5 % 2; return $a;', 1],
	'mod_negative' => ['return (0 - 7) % 3 + 10;', 9],
	'mod_negative_rhs' => ['return 7 % (0 - 3);', 1],
	'mod_precedence' => ['return 20 % 6 * 3 + 1;', 7],
	'mod_grouped' => ['return 20 % (6 * 3);', 2],
	'mod_zero_guard' => ['function fail(): int { return 1 % 0; } return 0;', 0],
	'concat_literals' => ['$a = "a" . "b";', 0],
	'concat_left' => ['$b = "b"; $a = $b . "x";', 0],
	'concat_right' => ['$b = "b"; $a = "x" . $b;', 0],
	'concat_cast' => ['$a = "x" . (string)(2 + 3 * 4);', 0],
	'concat_grouped' => ['$a = ("a" . "b") . "c";', 0],
	'concat_empty' => ['$a = "" . "x" . "";', 0],
	'concat_statement' => ['"a" . "b"; return 0;', 0],
	'expr_statement' => ['$a = 1; $a + 1; return $a;', 1],
	'expr_statement_grouped' => ['$a = 1; ($a + 1) * 2; return $a;', 1],
	'logical_and_table' => ['return (int)(false && false) + (int)(false && true) * 2 + (int)(true && false) * 4 + (int)(true && true) * 8;', 8],
	'logical_or_table' => ['return (int)(false || false) + (int)(false || true) * 2 + (int)(true || false) * 4 + (int)(true || true) * 8;', 14],
	'logical_precedence' => ['return true || false && false;', 1],
	'logical_grouped' => ['return (true || false) && false;', 0],
	'logical_compare' => ['return 1 + 2 < 4 && 7 % 3 == 1;', 1],
	'logical_and_skip' => ['return false && (1 / 0 == 0);', 0],
	'logical_or_skip' => ['return true || (1 % 0 == 0);', 1],
	'logical_and_guard' => ['function fail(): bool { return true && (1 / 0 == 0); } return 0;', 0],
	'logical_or_guard' => ['function fail(): bool { return false || (1 % 0 == 0); } return 0;', 0],
	'nested_catalog' => ['$b = 3; $a = ($b + 1) * 2; return $a;', 8],
	'chain_catalog' => ['$b = 2; $c = 4; $a = $b + 1 + $c; return $a;', 7],
	'compare_precedence' => ['return 2 + 3 * 4 > 13;', 1],
	'compare_constant' => ['return PHP_INT_MAX < 1;', 0],
	'compare_grouped_constant' => ['return (PHP_INT_MAX) >= 1;', 1],
	'compare_limits' => ['return (0 - PHP_INT_MAX - 1) < PHP_INT_MAX;', 1],
	'compare_three_way_less' => ['$a = 1 <=> 2; return $a + 1;', 0],
	'compare_three_way_equal' => ['return 2 <=> 2;', 0],
	'compare_three_way_greater' => ['return 3 <=> 2;', 1],
	'compare_three_way_limits' => ['return PHP_INT_MAX <=> (0 - PHP_INT_MAX - 1);', 1],
	'mul_catalog' => ['$a = 2 * 3; return $a;', 6],
	'mul_precedence_right' => ['return 2 + 3 * 4 - 5;', 9],
	'mul_precedence_left' => ['return 2 * 3 + 4;', 10],
	'mul_grouped' => ['return (2 + 3) * 4;', 20],
	'mul_chain' => ['return 2 * 3 * 4;', 24],
	'mul_cast' => ['$x uint8 = 3; return (int)$x * 2;', 6],
	'mul_reassignment' => ['$a = 3; $a = $a * 2; return $a;', 6],
	'mul_assignment_chain' => ['$a = $b = 2 + 3 * 4; return $a + $b;', 28],
	'mul_zero' => ['return PHP_INT_MAX * 0;', 0],
	'mul_negative' => ['$a = (0 - 3) * 2; return $a + 6;', 0],
	'mul_wide' => ['$a = 3037000499 * 3037000499; return 0;', 0],
	'group_catalog' => ['$b = 2; $a = ($b + 1); return $a;', 3],
	'group_right' => ['return 1 + (2 + 3);', 6],
	'group_left' => ['return ((1 + 2)) + 3;', 6],
	'group_cast_operand' => ['return (int)(1 + 2);', 3],
	'group_cast_result' => ['$x = 3.5; return ((int)$x) + 2;', 5],
	'group_constant' => ['$a = (PHP_INT_MAX); return 0;', 0],
	'group_bool' => ['return ((true));', 1],
	'group_reference' => ['function set(int &$x): void { $x = 13; } $x = 1; set((($x))); return $x;', 13],
	'group_field_reference' => ['struct Box { int32 $value; } function set(int32 &$x): void { $x = 17; } $box Box; set((($box)->value)); return ($box)->value;', 17],
	'group_call_once' => ['function bump(int &$x): int { $x = $x + 1; return $x; } $x = 0; $a = (bump($x)); return $x;', 1],
	'copy' => ['$a = 10; $b = $a; $a = 12; return $b;', 10],
	'wide' => ['$a = 4294967296; return 7;', 7],
	'maximum' => ['$a = 9223372036854775807; return 9;', 9],
	'keyword' => ['$int = 10; return $int;', 10],
	'ident_var_001' => ['function f(int $int): void { $while = $int; }', 0],
	'ident_var_001_prefix_collision' => ['function f(int $int, int $local_int): void { $while = $int; $local_while = $local_int; }', 0],
	'ident_var_escaping' => ['function _f(int $_x, int $U_x, int $a__b, int $aU_U_b): int { $_copy = $_x; return $_copy; } return _f(1, 2, 3, 4);', 1],
	'ident_record_escaping' => ['struct _Box__U { int32 $_field__U; } $value _Box__U; $value->_field__U = 5; return $value->_field__U;', 5],
];
// Ordinary functions and value structs exercise the existing frontend without adding syntax.
$cases += [
	'function_no_arguments' => ['function value(): int { return 13; } return value();', 13],
	'function_variadic_empty' => ['function pack(int ...$items): vector<int> { return $items; } $items = pack(); return 0;', 0],
	'function_variadic_values' => ['function pack(int ...$items): vector<int> { return $items; } $items = pack(2, 5, 9); return 0;', 0],
	'function_variadic_float' => ['function pack(float ...$items): vector<float> { return $items; } $items = pack(1.5, 2.5); return 0;', 0],
	'function_variadic_bool' => ['function pack(bool ...$items): vector<bool> { return $items; } $items = pack(true, false); return 0;', 0],
	'function_variadic_string' => ['function pack(string ...$items): vector<string> { return $items; } $text = "2"; $items = pack($text, "7"); $text .= "3"; return 0;', 0],
	'function_variadic_default_empty' => ['function value(int $first = 10, int ...$items): int { return $first; } return value();', 10],
	'function_variadic_default_override' => ['function value(int $first = 10, int ...$items): int { return $first; } return value(2, 3, 4);', 2],
	'function_variadic_partial_defaults' => ['function value(int $first = 10, int $second = 20, int ...$items): int { return $first + $second; } return value(3);', 23],
	'function_variadic_order' => ['function next(int &$x): int { $x += 1; return $x; } function pack(int $first, int ...$items): vector<int> { return $items; } $x = 0; $items = pack(next($x), next($x), next($x)); return 0;', 0],
	'function_variadic_forward' => ['$items = pack(3); function pack(int ...$items): vector<int> { return $items; } return 0;', 0],
	'function_variadic_vector_forward' => ['function identity(vector<int> $items): vector<int> { return $items; } function pack(int ...$items): vector<int> { return identity($items); } $items = pack(4, 8); return 0;', 0],
	'function_default_int' => ['function add(int $left, int $right = 2): int { return $left + $right; } return add(5);', 7],
	'function_default_override' => ['function add(int $left, int $right = 2): int { return $left + $right; } return add(5, 10);', 15],
	'function_default_all' => ['function add(int $left = 20, int $right = 22): int { return $left + $right; } return add();', 42],
	'function_default_partial' => ['function add(int $left = 20, int $right = 22): int { return $left + $right; } return add(3);', 25],
	'function_default_zero' => ['function value(int $x = 0): int { return $x; } return value();', 0],
	'function_default_bool' => ['function value(bool $x = true): int { if ($x) { return 7; } return 9; } return value();', 7],
	'function_default_false' => ['function value(bool $x = true): int { if ($x) { return 7; } return 9; } return value(false);', 9],
	'function_default_float' => ['function value(float $x = 4.5): float { return $x; } return value();', 4],
	'function_default_negative_float' => ['function value(float $x = -4.5): float { return $x; } $x = value(); return (int)$x + 10;', 6],
	'function_default_negative_int' => ['function value(int $x = -4): int { return $x + 10; } return value();', 6],
	'function_default_positive' => ['function value(int $x = +4): int { return $x; } return value();', 4],
	'function_default_string' => ['function value(string $x = "17"): string { return $x; } $x = value(); return (int)$x;', 17],
	'function_default_empty' => ['function value(string $x = ""): int { if ($x) { return 9; } return 3; } return value();', 3],
	'function_default_fresh_string' => ['function value(string $x = "2"): string { $x .= "3"; return $x; } $first = value(); $second = value(); return (int)$first + (int)$second;', 46],
	'function_default_forward' => ['return value(); function value(int $x = 31): int { return $x; }', 31],
	'function_default_recursive' => ['function sum(int $n, int $step = 2): int { if ($n === 0) { return $step; } $rest = sum($n - 1); return $step + $rest; } return sum(3);', 8],
	'function_default_snapshot' => ['function change(int &$x): int { $x = 9; return 3; } function sum(int $a, int $b, int $c = 4): int { return $a * 10 + $b + $c; } $x = 2; return sum($x, change($x));', 27],
	'function_void' => ['function done(): void { return; } done(); return 0;', 0],
	'function_mixed_parameters' => ['function accept(int $x, float $y, bool $z): void {} accept(1, 2.5, true); return 0;', 0],
	'function_typed_string' => ['function combine(int $amount, string $label): int { return $amount + (int)$label; } return combine(7, "12");', 19],
	'function_typed_positions' => ['function combine(int $first, int $second, string $label, bool $enabled, float $fraction): int { if ($enabled) { return $first * 100 + $second * 10 + (int)$label + (int)$fraction; } return $second; } return combine(1, 2, "3", true, 4.5);', 127],
	'function_typed_false' => ['function combine(int $first, int $second, string $label, bool $enabled, float $fraction): int { if ($enabled) { return $first * 100 + $second * 10 + (int)$label + (int)$fraction; } return $second; } return combine(1, 2, "3", false, 4.5);', 2],
	'function_typed_value_reassignment' => ['function change(int $amount, string $label): int { $amount = 9; $label = "8"; return $amount * 10 + (int)$label; } $amount = 2; $label = "3"; $changed = change($amount, $label); return $changed + $amount * 10 + (int)$label;', 121],
	'function_typed_string_return' => ['function append(string $label): string { return $label . "7"; } $result = append("2"); return (int)$result;', 27],
	'function_typed_string_reference' => ['function change(string &$label): void { $label = "41"; } $label = "3"; change($label); return (int)$label;', 41],
	'function_ownership_value_append' => ['function value(string $text): string { $text .= "3"; return $text; } $original = "2"; $result = value($original); return (int)$original + (int)$result;', 25],
	'function_ownership_reference_append' => ['function value(string &$text): string { $text .= "3"; return $text; } $original = "2"; $result = value($original); return (int)$original + (int)$result;', 46],
	'function_ownership_return_parameter' => ['function value(string $text): string { return $text; } $original = "2"; $result = value($original); $original .= "3"; $result .= "4"; return (int)$original + (int)$result;', 47],
	'function_ownership_return_reference_read' => ['function value(string &$text): string { return $text; } $original = "2"; $result = value($original); $original .= "3"; $result .= "4"; return (int)$original + (int)$result;', 47],
	'function_ownership_return_local' => ['function value(): string { $local = "17"; return $local; } $result = value(); $result .= "3"; return (int)$result;', 173],
	'function_ownership_readonly_alias' => ['function value(string $snapshot, string &$alias): string { $alias .= "3"; return $snapshot; } $original = "2"; $result = value($original, $original); return (int)$original + (int)$result;', 25],
	'function_typed_forward_string' => ['$result = identity("37"); return (int)$result; function identity(string $label): string { return $label; }', 37],
	'function_typed_string_snapshot' => ['function change(string &$label): int { $label = "99"; return 0; } function first(string $label, int $unused): int { return (int)$label; } $label = "23"; return first($label, change($label));', 23],
	'function_local_shadow' => ['$x = 7; function own(): void { $x = 12; } own(); return $x;', 7],
	'struct_argument_order' => ['struct Item { int32 $value; } function change(Item &$x): int { $x->value = 99; return 0; } function first(Item $x, int $unused): int32 { return $x->value; } $x Item; $x->value = 67; return first($x, change($x));', 67],
	'integer_alias_reference' => ['function change(uint8 &$x): void { $x = 71; } $x byte = 0; change($x); return $x;', 71],
	'function_forward' => ['return identity(19); function identity(int $x): int { return $x; }', 19],
	'function_nested' => ['function identity(int $x): int { return $x; } return identity(identity(23));', 23],
	'function_locals' => ['$x = 7; function own(int $x): int { $x = 12; return $x; } own($x); return $x;', 7],
	'function_reference' => ['function set(int &$x): void { $x = 29; return; } $x = 1; set($x); return $x;', 29],
	'function_forward_reference' => ['function forward(int &$x): void { set($x); } function set(int &$x): void { $x = 31; } $x = 1; forward($x); return $x;', 31],
	'function_argument_order' => ['function change(int &$x): int { $x = 33; return $x; } function first(int $a, int $b): int { return $a; } $x = 11; return first($x, change($x));', 11],
	'function_reference_order' => ['function change(int &$x): int { $x = 33; return $x; } function first(int &$a, int $b): int { return $a; } $x = 11; return first($x, change($x));', 33],
	'function_bool' => ['function identity(bool $x): bool { return $x; } return identity(true);', 1],
	'function_float' => ['function identity(float $x): float { return $x; } return identity(10.5);', 10],
	'function_recursive_signature' => ['function first(int $x): int { return second($x); } function second(int $x): int { return first($x); } return 0;', 0],
	'function_recursive_base' => ['function factorial(int $n): int { if ($n <= 1) { return 1; } $previous = factorial($n - 1); return $n * $previous; } return factorial(0);', 1],
	'function_recursive_factorial' => ['function factorial(int $n): int { if ($n <= 1) { return 1; } $previous = factorial($n - 1); return $n * $previous; } return factorial(5);', 120],
	'function_recursive_direct_return' => ['function countdown(int $n): int { if ($n === 0) { return 17; } return countdown($n - 1); } return countdown(4);', 17],
	'function_recursive_locals' => ['function sum(int $n): int { $saved = $n; if ($n === 0) { return 0; } $n = $n - 1; $rest = sum($n); return $saved + $rest; } $n = 4; $total = sum($n); return $total * 10 + $n;', 104],
	'function_recursive_branching' => ['function fib(int $n): int { if ($n <= 1) { return $n; } $left = fib($n - 1); $right = fib($n - 2); return $left + $right; } return fib(7);', 13],
	'function_recursive_mutual' => ['function even(int $n): bool { if ($n === 0) { return true; } return odd($n - 1); } function odd(int $n): bool { if ($n === 0) { return false; } return even($n - 1); } $first = even(6); $second = even(5); return (int)$first * 10 + (int)$second;', 10],
	'function_recursive_reference' => ['function count(int $n, int &$visits): void { $visits = $visits + 1; if ($n === 0) { return; } count($n - 1, $visits); } $visits = 0; count(4, $visits); return $visits;', 5],
	'struct_fields' => ['struct Point { int32 $x; public bool $ok; } $p Point; $p->x = 17; $p->ok = true; return $p->x;', 17],
	'struct_default' => ['struct Point { uint16 $x; } $p Point; return $p->x;', 0],
	'struct_copy' => ['struct Point { int32 $x; } $p Point; $p->x = 19; $q = $p; $p->x = 21; return $q->x;', 19],
	'struct_assignment' => ['struct Point { int32 $x; } $p Point; $q Point; $p->x = 25; $q = $p; $p->x = 27; return $q->x;', 25],
	'struct_nested_forward' => ['struct Outer { Inner $inner; } struct Inner { uint8 $value; } $a Outer; $a->inner->value = 37; return $a->inner->value;', 37],
	'struct_value_parameter' => ['struct Point { int32 $x; } function change(Point $p): void { $p->x = 99; } $p Point; $p->x = 41; change($p); return $p->x;', 41],
	'struct_reference_parameter' => ['struct Point { int32 $x; } function change(Point &$p): void { $p->x = 43; } $p Point; change($p); return $p->x;', 43],
	'struct_return' => ['function make(): Point { $p Point; $p->x = 47; return $p; } struct Point { int32 $x; } $p = make(); return $p->x;', 47],
	'struct_field_reference' => ['struct Point { int32 $x; } function change(int32 &$x): void { $x = 53; } $p Point; change($p->x); return $p->x;', 53],
	'struct_empty' => ['struct Empty {} function copy(Empty $e): Empty { return $e; } $e Empty; $f = copy($e); return 0;', 0],
	'integer_boundary' => ['function identity(uint16 $x): uint8 { return $x; } $x int32 = 59; return identity($x);', 59],
];
// Each fixture independently distinguishes true, false and equality-boundary behavior.
$comparison_cases = [
	'equal' => ['==', 3, 4, 3], 'not_equal' => ['!=', 4, 3, 3],
	'identical' => ['===', 3, 4, 3], 'not_identical' => ['!==', 4, 3, 3],
	'less' => ['<', 4, 3, 3], 'less_equal' => ['<=', 3, 2, 3],
	'greater' => ['>', 2, 3, 3], 'greater_equal' => ['>=', 3, 4, 3],
];
foreach ($comparison_cases as $name => [$symbol, $true_rhs, $false_rhs, $left]) {
	$cases['compare_' . $name] = ['$yes bool = ' . $left . ' ' . $symbol . ' ' . $true_rhs . '; '
		. '$no bool = ' . $left . ' ' . $symbol . ' ' . $false_rhs . '; return (int)$yes + (int)$no * 2;', 1];
}
foreach (['int8', 'int16', 'int32', 'int64', 'uint8', 'byte', 'uint16', 'uint32', 'uint64'] as $type) {
	$cases['field_' . $type] = ['struct Item { ' . $type . ' $value; } $x Item; $x->value = 61; return $x->value;', 61];
}
// Exact spellings cover decimal grammar, precision, normal limits and subnormals.
$float_forms = ['10.5', '.5', '10.', '1e3', '1E+3', '1.25e-3', '.5e2', '10.e-1',
	'1.2345678901234567', '0.0', '0008.5', '08e0', '1.7976931348623157e308',
	'2.2250738585072014e-308', '4.9406564584124654e-324'];
foreach ($float_forms as $index => $spelling) {
	$cases['float_form_' . $index] = ['$a = ' . $spelling . ';', 0];
}
$explicit_declarations = [
	'explicit' => ['scpp::int_t<>', 'local_a'],
	'float_explicit' => ['scpp::float_t', 'local_a'],
	'bool_explicit' => ['scpp::bool_t', 'local_a'],
	'string_explicit' => ['scpp::string_t', 'local_x'],
];
$interpolation_values = [
	'interp_simple' => hex2bin('48656c6c6f20416c6578'),
	'interp_braced' => hex2bin('436f756e743a2033'),
	'interp_adjacent' => hex2bin('616261'),
	'interp_suffix' => hex2bin('615f656e645b305d2829'),
	'interp_long_name' => hex2bin('676f6f64'),
	'interp_empty' => hex2bin('3c3e'),
	'interp_self' => hex2bin('5b6f6c645d'),
	'interp_snapshot' => hex2bin('6f6c64'),
	'interp_integer' => hex2bin('2d3132'),
	'interp_narrow' => hex2bin('323535'),
	'interp_bool' => hex2bin('3c313a3e'),
	'interp_float' => hex2bin('312e35'),
	'interp_parameter' => hex2bin('3c6f6b3e'),
	'interp_concat' => hex2bin('5b615d21'),
	'interp_compound' => hex2bin('6162'),
	'interp_escapes' => hex2bin('2276220a092478'),
	'interp_binary' => hex2bin('0076007a00'),
	'interp_utf8' => hex2bin('486920e4b896e7958c21'),
	'interp_single_quote' => hex2bin('68656c6c6f20246e616d65207b246e616d657d'),
	'interp_literal_escape' => hex2bin('24783d76'),
];

$variadic_probes = [
	'function_variadic_empty' => 'local_items.size() == 0',
	'function_variadic_values' => 'local_items.size() == 3 && local_items.at(0).native_value() == 2 && local_items.at(1).native_value() == 5 && local_items.at(2).native_value() == 9',
	'function_variadic_float' => 'local_items.size() == 2 && local_items.at(0).native_value() == 1.5 && local_items.at(1).native_value() == 2.5',
	'function_variadic_bool' => 'local_items.size() == 2 && local_items.at(0).native_value() && !local_items.at(1).native_value()',
	'function_variadic_string' => 'local_items.size() == 2 && local_items.at(0).native_value() == "2" && local_items.at(1).native_value() == "7"',
	'function_variadic_order' => 'local_x.native_value() == 3 && local_items.size() == 2 && local_items.at(0).native_value() == 2 && local_items.at(1).native_value() == 3',
	'function_variadic_forward' => 'local_items.size() == 1 && local_items.at(0).native_value() == 3',
	'function_variadic_vector_forward' => 'local_items.size() == 2 && local_items.at(0).native_value() == 4 && local_items.at(1).native_value() == 8',
];
$ownership_parameters = [
	'function_ownership_value_append' => 'scpp::string_t',
	'function_ownership_reference_append' => 'scpp::string_t&',
	'function_ownership_return_parameter' => 'scpp::string_t',
	'function_ownership_return_reference_read' => 'scpp::string_t&',
	'function_ownership_return_local' => '',
	'function_ownership_readonly_alias' => 'scpp::string_t, scpp::string_t&',
];
$result_fixtures = require __DIR__ . '/fixtures/result_construction.php';
$mixed_fixtures = array_merge(require __DIR__ . '/fixtures/mixed_boundaries.php',
	require __DIR__ . '/fixtures/mixed_operations.php', require __DIR__ . '/fixtures/mixed_selections.php',
	require __DIR__ . '/fixtures/mixed_wrapper_coalesce.php',
	require __DIR__ . '/fixtures/mixed_dynamic_casts.php',
	require __DIR__ . '/fixtures/mixed_arithmetic.php',
	require __DIR__ . '/fixtures/mixed_comparisons.php',
	require __DIR__ . '/fixtures/mixed_updates.php',
	require __DIR__ . '/fixtures/mixed_logical.php',
	require __DIR__ . '/fixtures/mixed_concat.php',
	require __DIR__ . '/fixtures/mixed_concat_updates.php');
$value_fixtures = array_merge($result_fixtures, $mixed_fixtures);
foreach ($value_fixtures as $name => [$source, $predicate]) {
	$cases[$name] = [$source, 0];
}
$executions = [];
foreach ($cases as $name => [$source, $exit])
{
	Compiler_Lifecycle::reset();
	$syntax = s2s_parse($source);
	$before = s2s_snapshot($syntax);
	$compiler = new Compiler();
	$compiler->prepare();
	$compiler->cpp();
	$path = $directory . '/' . $name . '.cpp';
	file_put_contents($path, Model::$cpp_files[0]->text);
	$executions[] = ['path' => $path, 'exit_code' => $exit];
	if (isset($value_fixtures[$name]))
	{
		$probe = "\tif (!(" . $value_fixtures[$name][1] . ")) { return 91; }\n";
		$probe_path = $directory . '/' . $name . '_state.cpp';
		$probe_text = str_replace("\treturn 0;", $probe . "\treturn 0;", Model::$cpp_files[0]->text);
		if (isset($mixed_fixtures[$name])) {
			// Native bridge inputs need their own headers, independently of generated declarations.
			$probe_text = "#include \"scpp/memory.hpp\"\n#include \"scpp/hash_t.hpp\"\n" . $probe_text;
		}
		file_put_contents($probe_path, $probe_text);
		$executions[] = ['path' => $probe_path, 'exit_code' => 0];
	}
	if ($name === 'nullable_explicit_string') {
		$probe_path = $directory . '/' . $name . '_value.cpp';
		file_put_contents($probe_path, str_replace("\treturn static_cast<int>",
			"\tif (!local_y.native_value().empty()) { return 91; }\n\treturn static_cast<int>", Model::$cpp_files[0]->text));
		$executions[] = ['path' => $probe_path, 'exit_code' => 0];
	}
	if (isset($variadic_probes[$name])) {
		$probe = "\tif (!(" . $variadic_probes[$name] . ")) { return 201; }\n";
		$probe_path = $directory . '/' . $name . '_value.cpp';
		$probe_text = str_replace("\treturn static_cast<int>", $probe . "\treturn static_cast<int>", Model::$cpp_files[0]->text);
		if ($probe_text === Model::$cpp_files[0]->text) { throw new \LogicException('Variadic value probe missed entry return'); }
		file_put_contents($probe_path, $probe_text);
		$executions[] = ['path' => $probe_path, 'exit_code' => $exit];
	}
	if (isset($ownership_parameters[$name])) {
		$probe = 'static_assert(std::is_same_v<decltype(&function_value), scpp::string_t (*)(' . $ownership_parameters[$name] . ')>);';
		$probe_path = $directory . '/' . $name . '_type.cpp';
		file_put_contents($probe_path, "#include <type_traits>\n" . Model::$cpp_files[0]->text . "\n" . $probe . "\n");
		$executions[] = ['path' => $probe_path, 'exit_code' => $exit];
	}
	// Verify actual member representation, not merely that small integer values survive.
	if (str_starts_with($name, 'field_')) {
		$alias = substr($name, strlen('field_'));
		$native = $alias === 'byte' ? 'uint8' : $alias;
		$probe = 'static_assert(std::is_same_v<decltype(record_Item{}.field_value), scpp::int_t<std::' . $native . '_t>>);';
		file_put_contents($path, Model::$cpp_files[0]->text . "\n" . $probe . "\n");
	}

	if (($name === 'bool_true') || ($name === 'bool_false')) {
		$probe = "\tstatic_assert(std::is_same_v<decltype(local_a), scpp::bool_t>);\n";
		$probe_path = $directory . '/' . $name . '_type.cpp';
		$probe_text = str_replace("\treturn static_cast<int>", $probe . "\treturn static_cast<int>", Model::$cpp_files[0]->text);
		file_put_contents($probe_path, $probe_text);
		$executions[] = ['path' => $probe_path, 'exit_code' => $exit];
	}

	if (in_array($name, ['string_single', 'string_double', 'string_empty_single', 'string_empty_double'], true)) {
		$text = Model::$cpp_files[0]->text;
		$expected_value = str_contains($name, 'empty') ? '' : 'x';
		if (!str_contains($text, 'auto local_a = scpp::string_t("' . $expected_value . '");')) {
			throw new \LogicException('String literal did not use its canonical C++ representation');
		}
		$probe = "\tstatic_assert(std::is_same_v<decltype(local_a), scpp::string_t>);\n";
		$probe .= "\tif (local_a.native_value() != std::string(\"" . $expected_value . '", '
			. string_byte_len($expected_value) . ")) { return 91; }\n";
		file_put_contents($path, str_replace("\treturn 0;", $probe . "\treturn 0;", $text));
	}

	if (isset($interpolation_values[$name]))
	{
		$expected = $interpolation_values[$name];
		$escaped = '';
		foreach (str_split($expected) as $byte) {
			$escaped .= '\x' . bin2hex($byte);
		}
		$probe = '	if (local_a.native_value() != std::string("' . $escaped . '", '
			. strlen($expected) . ')) { return 91; }' . "\n";
		file_put_contents($path, str_replace("\treturn 0;", $probe . "\treturn 0;", Model::$cpp_files[0]->text));
	}

	if ($name === 'constant_int_max')
	{
		$text = Model::$cpp_files[0]->text;
		$assignment = object_cast($syntax->root->body->statements[0]->expression, assignment_expression_node::class);
		$constant = object_cast($assignment->value, constant_reference_node::class);
		$facts = $constant->require_constant_reference_preparation();
		$definition = object_cast($facts->definition, integer_constant_definition::class);
		if (($definition !== Model::$language_scope->constants_named('PHP_INT_MAX')[0]) ||
			(!$facts->type->matches(Language_Types::integer(Model::$language_scope))) ||
			($definition->decimal !== '9223372036854775807') || $facts->addressable ||
			!str_contains($text, 'auto local_a = static_cast<scpp::int_t<>>(9223372036854775807LL);') ||
			!str_contains($text, '#include "scpp/int_t.hpp"') || str_contains($text, 'string_support.hpp')) {
			throw new \LogicException('Constant reference lost its definition, type, immutability or canonical lowering');
		}
	}

	if ($name === 'chain')
	{
		$text = Model::$cpp_files[0]->text;
		$outer = object_cast($syntax->root->body->statements[0]->expression, assignment_expression_node::class);
		$inner = object_cast($outer->value, assignment_expression_node::class);
		$outer_binding = $outer->require_assignment_preparation()->binding;
		$inner_binding = $inner->require_assignment_preparation()->binding;
		$expected = "\tauto local_b = static_cast<scpp::int_t<>>(1LL);\n"
			. "\tauto local_a = local_b;\n";
		if (($inner_binding->resolved_kind !== binding_kind::declaration) ||
			($outer_binding->resolved_kind !== binding_kind::declaration) ||
			(!$inner_binding->type->matches($outer_binding->type)) || !str_contains($text, $expected)) {
			throw new \LogicException('Assignment chain lost right-associative facts or inner-first lowering');
		}
	}

	if ($name === 'chain_deep') {
		$expected = "\tauto local_c = static_cast<scpp::int_t<>>(4LL);\n"
			. "\tauto local_b = local_c;\n"
			. "\tauto local_a = local_b;\n";
		if (!str_contains(Model::$cpp_files[0]->text, $expected)) {
			throw new \LogicException('Deep assignment chain was not flattened from the innermost write');
		}
	}

	if ($name === 'chain_same_target')
	{
		$outer = object_cast($syntax->root->body->statements[0]->expression, assignment_expression_node::class);
		$inner = object_cast($outer->value, assignment_expression_node::class);
		$outer_binding = $outer->require_assignment_preparation()->binding;
		$inner_binding = $inner->require_assignment_preparation()->binding;
		$text = Model::$cpp_files[0]->text;
		if (($inner_binding->resolved_kind !== binding_kind::declaration) ||
			($outer_binding->resolved_kind !== binding_kind::assignment) ||
			(weakref_get($outer_binding->declaration) !== weakref_get($inner_binding->declaration)) ||
			(substr_count($text, 'auto local_a =') !== 1) ||
			!str_contains($text, "\tlocal_a = local_a;\n")) {
			throw new \LogicException('Repeated-name chain created two declarations or lost storage identity');
		}
	}

	if (($name === 'chain_conversion') &&
		(!str_contains(Model::$cpp_files[0]->text, 'local_b = static_cast<scpp::int_t<std::uint8_t>>((static_cast<scpp::int_t<>>(257LL)).native_value());') ||
		 !str_contains(Model::$cpp_files[0]->text, 'auto local_a = local_b;'))) {
		throw new \LogicException('Assignment chain copied the unconverted RHS instead of the stored inner value');
	}

	if (($name === 'chain_call_once') && (substr_count(Model::$cpp_files[0]->text, 'return function_value();') !== 1)) {
		throw new \LogicException('Assignment chain evaluated its call RHS more than once');
	}

	if ($name === 'var_chain_002')
	{
		$expected = "\tauto local_a = static_cast<scpp::int_t<>>(1LL);\n"
			. "\tauto local_b = local_a;\n";
		$text = Model::$cpp_files[0]->text;
		if (!str_contains($text, $expected) || str_contains($text, 'auto local_b = static_cast')) {
			throw new \LogicException('VAR-CHAIN-002 lost sequential declaration order or direct copy lowering');
		}
	}

	if ($name === 'var_chain_003')
	{
		$first = object_cast($syntax->root->body->statements[0]->expression, assignment_expression_node::class);
		$second = object_cast($syntax->root->body->statements[1]->expression, assignment_expression_node::class);
		$third = object_cast($syntax->root->body->statements[2]->expression, assignment_expression_node::class);
		$second_source = object_cast($second->value, variable_reference_node::class);
		$third_source = object_cast($third->value, variable_reference_node::class);
		$first_facts = $first->require_assignment_preparation();
		$second_facts = $second->require_assignment_preparation();
		$third_facts = $third->require_assignment_preparation();
		$first_identity = weakref_get($first_facts->binding->declaration);
		$second_identity = weakref_get($second_facts->binding->declaration);
		$third_identity = weakref_get($third_facts->binding->declaration);
		$expected = "\tauto local_a = static_cast<scpp::int_t<>>(1LL);\n"
			. "\tauto local_b = local_a;\n"
			. "\tauto local_c = local_b;\n";
		$text = Model::$cpp_files[0]->text;
		if (($first_facts->binding->resolved_kind !== binding_kind::declaration) ||
			($second_facts->binding->resolved_kind !== binding_kind::declaration) ||
			($third_facts->binding->resolved_kind !== binding_kind::declaration) ||
			($first_identity === $second_identity) || ($second_identity === $third_identity) ||
			($first_identity === $third_identity) ||
			(weakref_get($second_source->require_variable_reference_preparation()->declaration) !== $first_identity) ||
			(weakref_get($third_source->require_variable_reference_preparation()->declaration) !== $second_identity) ||
			(!$first_facts->type->matches($second_facts->type)) || (!$second_facts->type->matches($third_facts->type)) ||
			!str_contains($text, $expected) || str_contains($text, 'auto local_b = static_cast') ||
			str_contains($text, 'auto local_c = static_cast')) {
			throw new \LogicException('VAR-CHAIN-003 lost source-order identities, canonical type or direct copy lowering');
		}
	}

	if ($name === 'var_chain_004')
	{
		$first = object_cast($syntax->root->body->statements[0]->expression, assignment_expression_node::class);
		$second = object_cast($syntax->root->body->statements[1]->expression, assignment_expression_node::class);
		$binary = object_cast($second->value, binary_expression_node::class);
		$left = object_cast($binary->left, variable_reference_node::class);
		$right = object_cast($binary->right, integer_literal_node::class);
		$first_facts = $first->require_assignment_preparation();
		$second_facts = $second->require_assignment_preparation();
		$binary_facts = $binary->require_binary_preparation();
		$integer_type = Language_Types::integer(Model::$language_scope);
		$expected = "\tauto local_a = static_cast<scpp::int_t<>>(1LL);\n"
			. "\tauto local_b = (local_a + static_cast<scpp::int_t<>>(1LL));\n";
		$text = Model::$cpp_files[0]->text;
		if (($binary_facts->decision->operation !== operator_operation::integer_addition)
			|| (!$binary_facts->type->matches($integer_type)) ||
			$binary_facts->addressable || (!$left->require_preparation()->type->matches($integer_type)) ||
			(!$right->require_preparation()->type->matches($integer_type)) || (!$second_facts->type->matches($integer_type)) ||
			(weakref_get($left->require_variable_reference_preparation()->declaration) !== weakref_get($first_facts->binding->declaration)) ||
			!str_contains($text, $expected) || !str_contains($text, '#include "scpp/generated/operators.hpp"')) {
			throw new \LogicException('VAR-CHAIN-004 lost integer addition facts, source identity or normalized lowering');
		}
	}

	if ($name === 'var_reassign_002')
	{
		$first = object_cast($syntax->root->body->statements[0]->expression, assignment_expression_node::class);
		$second = object_cast($syntax->root->body->statements[1]->expression, assignment_expression_node::class);
		$binary = object_cast($second->value, binary_expression_node::class);
		$self_reference = object_cast($binary->left, variable_reference_node::class);
		$first_facts = $first->require_assignment_preparation();
		$second_facts = $second->require_assignment_preparation();
		$binary_facts = $binary->require_binary_preparation();
		$declaration = weakref_get($first_facts->binding->declaration);
		$expected = "\tauto local_a = static_cast<scpp::int_t<>>(1LL);\n"
			. "\tlocal_a = (local_a + static_cast<scpp::int_t<>>(1LL));\n";
		$text = Model::$cpp_files[0]->text;
		if (($first_facts->binding->resolved_kind !== binding_kind::declaration) ||
			($second_facts->binding->resolved_kind !== binding_kind::assignment) ||
			(weakref_get($second_facts->binding->declaration) !== $declaration) ||
			(weakref_get($self_reference->require_variable_reference_preparation()->declaration) !== $declaration) ||
			($binary_facts->decision->operation !== operator_operation::integer_addition) ||
			(!$first_facts->type->matches($binary_facts->type)) || (!$second_facts->type->matches($binary_facts->type)) ||
			!str_contains($text, $expected) || (substr_count($text, 'auto local_a =') !== 1)) {
			throw new \LogicException('VAR-REASSIGN-002 redeclared its target or lost self-read/addition facts');
		}
	}

	if ($name === 'var_reassign_003')
	{
		$first = object_cast($syntax->root->body->statements[0]->expression, assignment_expression_node::class);
		$second = object_cast($syntax->root->body->statements[1]->expression, assignment_expression_node::class);
		$binary = object_cast($second->value, binary_expression_node::class);
		$left = object_cast($binary->left, variable_reference_node::class);
		$right = object_cast($binary->right, variable_reference_node::class);
		$first_facts = $first->require_assignment_preparation();
		$second_facts = $second->require_assignment_preparation();
		$binary_facts = $binary->require_binary_preparation();
		$left_facts = $left->require_variable_reference_preparation();
		$right_facts = $right->require_variable_reference_preparation();
		$declaration = weakref_get($first_facts->binding->declaration);
		$expected = "\tauto local_a = static_cast<scpp::int_t<>>(1LL);\n"
			. "\tlocal_a = (local_a + local_a);\n";
		$text = Model::$cpp_files[0]->text;
		if (($first_facts->binding->resolved_kind !== binding_kind::declaration) ||
			($second_facts->binding->resolved_kind !== binding_kind::assignment) ||
			(weakref_get($second_facts->binding->declaration) !== $declaration) ||
			($left === $right) || ($left_facts === $right_facts) ||
			(weakref_get($left_facts->declaration) !== $declaration) ||
			(weakref_get($right_facts->declaration) !== $declaration) ||
			($binary_facts->decision->operation !== operator_operation::integer_addition) ||
			(!$left_facts->type->matches($binary_facts->type)) || (!$right_facts->type->matches($binary_facts->type)) ||
			(!$second_facts->type->matches($binary_facts->type)) || !str_contains($text, $expected) ||
			(substr_count($text, 'auto local_a =') !== 1)) {
			throw new \LogicException('VAR-REASSIGN-003 lost distinct reads, shared identity or reassignment lowering');
		}
	}

	if ($name === 'ident_var_001')
	{
		$function = object_cast($syntax->root->declarations[0], function_node::class);
		$parameter = $function->parameters[0];
		$assignment = object_cast($function->body->statements[0]->expression, assignment_expression_node::class);
		$target = object_cast($assignment->target, variable_reference_node::class);
		$source = object_cast($assignment->value, variable_reference_node::class);
		$parameter_facts = $parameter->require_preparation();
		$assignment_facts = $assignment->require_assignment_preparation();
		$source_facts = $source->require_variable_reference_preparation();
		$parameter_identity = weakref_get($parameter_facts->declaration);
		$local_identity = weakref_get($assignment_facts->binding->declaration);
		$signature = 'void function_f(scpp::int_t<> local_int)';
		$definition = $signature . "\n{\n\tauto local_while = local_int;\n}";
		$text = Model::$cpp_files[0]->text;
		if (($function->name !== 'f') || ($function->occurrence()->name !== 'f') ||
			($parameter->name !== 'int') || ($parameter->occurrence()->name !== 'int') ||
			($target->name !== 'while') || ($target->occurrence()->name !== 'while') ||
			($source->name !== 'int') || ($source->occurrence()->name !== 'int') ||
			($parameter_identity === $local_identity) ||
			(weakref_get($source_facts->declaration) !== $parameter_identity) ||
			(!$parameter_facts->storage_type()->matches($source_facts->type)) || (!$source_facts->type->matches($assignment_facts->type)) ||
			(substr_count($text, $signature) !== 2) || !str_contains($text, $definition) ||
			str_contains($text, 'int__') || str_contains($text, 'while__')) {
			throw new \LogicException('IDENT-VAR-001 lost role-prefixed names, identity or signature consistency');
		}
	}

	if ($name === 'ident_var_001_prefix_collision')
	{
		$text = Model::$cpp_files[0]->text;
		$signature = 'void function_f(scpp::int_t<> local_int, scpp::int_t<> local_localU_int)';
		$body = "\tauto local_while = local_int;\n\tauto local_localU_while = local_localU_int;\n";
		if ((substr_count($text, $signature) !== 2) || !str_contains($text, $body)) {
			throw new \LogicException('Role prefixes collided with source names containing the same prefix');
		}
	}

	if ($name === 'ident_var_escaping')
	{
		$function = object_cast($syntax->root->declarations[0], function_node::class);
		$assignment = object_cast($function->body->statements[0]->expression, assignment_expression_node::class);
		$target = object_cast($assignment->target, variable_reference_node::class);
		$source = object_cast($assignment->value, variable_reference_node::class);
		$call = object_cast(object_cast($syntax->root->body->statements[0], return_node::class)->expression, call_node::class);
		$text = Model::$cpp_files[0]->text;
		$signature = 'scpp::int_t<> function_U_f(scpp::int_t<> local_U_x, scpp::int_t<> local_UUU_x, '
			. 'scpp::int_t<> local_aU_U_b, scpp::int_t<> local_aUUU_UUU_b)';
		if (($function->name !== '_f') || ($call->name !== '_f') ||
			($function->parameters[0]->name !== '_x') || ($function->parameters[1]->name !== 'U_x') ||
			($function->parameters[2]->name !== 'a__b') || ($function->parameters[3]->name !== 'aU_U_b') ||
			($target->name !== '_copy') || ($source->name !== '_x') ||
			(substr_count($text, $signature) !== 2) ||
			!str_contains($text, 'auto local_U_copy = local_U_x;') ||
			!str_contains($text, 'return function_U_f(argument_0, argument_1, argument_2, argument_3);') ||
			str_contains($text, '__')) {
			throw new \LogicException('NOTE-021 lost raw names, reversible escaping or consistent function spelling');
		}
	}

	if ($name === 'ident_record_escaping')
	{
		$text = Model::$cpp_files[0]->text;
		if (!str_contains($text, "struct record_U_BoxU_U_UU\n") ||
			(substr_count($text, 'record_U_BoxU_U_UU local_value') !== 1) ||
			!str_contains($text, 'scpp::int_t<std::int32_t> field_U_fieldU_U_UU;') ||
			(substr_count($text, '.field_U_fieldU_U_UU') !== 2) || str_contains($text, '__')) {
			throw new \LogicException('NOTE-021 did not share identifier escaping across record and field roles');
		}
	}

	if ($name === 'function_locals')
	{
		$function = object_cast($syntax->root->declarations[0], function_node::class);
		$parameter = $function->parameters[0]->require_preparation();
		$function_assignment = object_cast($function->body->statements[0]->expression, assignment_expression_node::class);
		$function_binding = $function_assignment->require_assignment_preparation()->binding;
		$entry_assignment = object_cast($syntax->root->body->statements[0]->expression, assignment_expression_node::class);
		$entry_binding = $entry_assignment->require_assignment_preparation()->binding;
		$parameter_identity = weakref_get($parameter->declaration);
		$entry_identity = weakref_get($entry_binding->declaration);
		$text = Model::$cpp_files[0]->text;
		$signature = 'scpp::int_t<> function_own(scpp::int_t<> local_x)';
		if (($entry_binding->resolved_kind !== binding_kind::declaration) ||
			($function_binding->resolved_kind !== binding_kind::assignment) ||
			(weakref_get($function_binding->declaration) !== $parameter_identity) ||
			($entry_identity === $parameter_identity) ||
			(substr_count($text, $signature) !== 2) ||
			(substr_count($text, 'auto local_x = static_cast<scpp::int_t<>>(7LL);') !== 1) ||
			(substr_count($text, 'local_x = static_cast<scpp::int_t<>>(12LL);') !== 1) ||
			!str_contains($text, 'scpp::int_t<> argument_0 = local_x;') ||
			str_contains($text, 'argument_0 = static_cast<scpp::int_t<>>') ||
			!str_contains($text, 'return function_own(argument_0);') ||
			!str_contains($text, 'return static_cast<int>((local_x).native_value());')) {
			throw new \LogicException('NOTE-033.a lost executable-unit isolation or parameter-seeded reassignment');
		}
	}

	if (isset($explicit_declarations[$name]))
	{
		[$cpp_type, $cpp_name] = $explicit_declarations[$name];
		$text = Model::$cpp_files[0]->text;
		if (!str_contains($text, $cpp_type . ' ' . $cpp_name . ' = ') || str_contains($text, 'auto ' . $cpp_name . ' = ')) {
			throw new \LogicException('Explicit local declaration lost its canonical C++ type');
		}
	}

	if (str_starts_with($name, 'float_form_'))
	{
		$spelling = $float_forms[(int) substr($name, strlen('float_form_'))];
		$text = Model::$cpp_files[0]->text;
		if (!str_contains($text, 'static_cast<scpp::float_t>(' . $spelling . ')')) {
			throw new \LogicException('Float literal spelling was rounded or changed');
		}
		$probe = "\tstatic_assert(std::is_same_v<decltype(local_a), scpp::float_t>);\n";
		$probe .= "\tif (local_a.native_value() != " . $spelling . ") { return 91; }\n";
		file_put_contents($path, str_replace("\treturn 0;", $probe . "\treturn 0;", $text));
	}

	// Independent native probes observe the value and type of large emitted literals.
	if (($name === 'wide') || ($name === 'maximum'))
	{
		$magnitude = $name === 'wide' ? '4294967296' : '9223372036854775807';
		$probe = "\tstatic_assert(std::is_same_v<decltype(local_a), scpp::int_t<>>);\n";
		$probe .= "\tif (local_a.native_value() != " . $magnitude . "LL) { return 91; }\n";
		$probe_path = $directory . '/' . $name . '_value.cpp';
		$probe_text = str_replace("\treturn static_cast<int>", $probe . "\treturn static_cast<int>", Model::$cpp_files[0]->text);
		file_put_contents($probe_path, $probe_text);
		$executions[] = ['path' => $probe_path, 'exit_code' => $exit];
	}

	// Verify signed values directly; process exit codes alone lose sign and high bits.
	$arithmetic_values = [ 'bits_shift_zero' => '-7LL', 'bits_left_zero' => '-7LL',
		'bits_right_negative' => '-2LL', 'bits_left_negative' => '-6LL', 'bits_wrap_left' => '-2LL',
		'bits_right_63' => '-1LL', 'bits_compound_right' => '-2LL', 'bits_compound_right_63' => '-1LL',
		'bits_sign_bit' => '(-9223372036854775807LL - 1LL)',
		'bits_negative_left_63' => '(-9223372036854775807LL - 1LL)',
		'bits_compound_sign_bit' => '(-9223372036854775807LL - 1LL)',
'mutation_maximum' => '9223372036854775807LL',
		'mutation_minimum' => '(-9223372036854775807LL - 1LL)', 'unary_negative' => '-3LL', 'unary_complement' => '-4LL',
		'unary_minimum' => '(-9223372036854775807LL - 1LL)',
		'unary_complement_limit' => '(-9223372036854775807LL - 1LL)', 'sub_catalog' => '-1LL',
		'sub_minimum' => '(-9223372036854775807LL - 1LL)', 'sub_maximum' => '9223372036854775807LL',
		'div_minimum' => '(-9223372036854775807LL - 1LL)', 'compare_three_way_less' => '-1LL',
		'mul_negative' => '-6LL', 'mul_wide' => '9223372030926249001LL'];
	if (isset($arithmetic_values[$name]))
	{
		$probe = "\tstatic_assert(std::is_same_v<decltype(local_a), scpp::int_t<>>);\n";
		$probe .= "\tif (local_a.native_value() != " . $arithmetic_values[$name] . ") { return 91; }\n";
		$text = Model::$cpp_files[0]->text;
		$site = (($name === 'sub_maximum') || ($name === 'mul_wide') || ($name === 'unary_minimum') || ($name === 'unary_complement_limit') || ($name === 'mutation_maximum') || ($name === 'mutation_minimum') || ($name === 'bits_sign_bit')
			|| ($name === 'bits_negative_left_63') || ($name === 'bits_compound_sign_bit')) ? "\treturn 0;" : "\treturn static_cast<int>";
		$probe_path = $directory . '/' . $name . '_value.cpp';
		file_put_contents($probe_path, str_replace($site, $probe . $site, $text));
		$executions[] = ['path' => $probe_path, 'exit_code' => $exit];
	}

	$concatenation_values = ['compound_concat' => 'ab', 'compound_concat_cast' => 'a3',
		'compound_concat_self' => 'aa', 'compound_concat_snapshot' => 'ab', 'concat_literals' => 'ab', 'concat_left' => 'bx',
		'concat_right' => 'xb', 'concat_cast' => 'x14', 'concat_grouped' => 'abc', 'concat_empty' => 'x'];
	if (isset($concatenation_values[$name]))
	{
		$probe = "\tstatic_assert(std::is_same_v<decltype(local_a), scpp::string_t>);\n";
		$probe .= '	if (local_a.native_value() != "' . $concatenation_values[$name] . '") { return 91; }' . "\n";
		$probe_path = $directory . '/' . $name . '_value.cpp';
		file_put_contents($probe_path, str_replace("\treturn 0;", $probe . "\treturn 0;", Model::$cpp_files[0]->text));
		$executions[] = ['path' => $probe_path, 'exit_code' => 0];
	}

	// A failed assignment RHS or compound computation preserves the caller-owned target.
	if (($name === 'compound_div_zero') || ($name === 'compound_mod_zero') || ($name === 'logic_assignment_throw')
		|| ($name === 'power_store_error') || ($name === 'nullable_store_error'))
	{
		$error_code = $name === 'compound_mod_zero' ? 'modulo_by_zero' : 'division_by_zero';
		if ($name === 'power_store_error') {
			$error_code = 'power_overflow';
		}
		if ($name === 'nullable_store_error') {
			$error_code = 'invalid_nullable_unwrap_empty';
		}
		$probe = "\nint main() { scpp::int_t<> target(7); try { (void)function_fail(target); }"
			. ' catch (const scpp::runtime_error& error) { return error.code() == "' . $error_code
			. '" && target.native_value() == 7 ? 0 : 91; } return 92; }';
		$probe_path = $directory . '/' . $name . '_throw.cpp';
		file_put_contents($probe_path, str_replace('int main()', 'int unused_entry()', Model::$cpp_files[0]->text) . $probe);
		$executions[] = ['path' => $probe_path, 'exit_code' => 0];
	}

	// Observe runtime exceptions in-process without introducing source try/catch support.
	$runtime_error_codes = ['div_zero_guard' => 'division_by_zero', 'mod_zero_guard' => 'modulo_by_zero',
		'nullable_probe_throw' => 'division_by_zero',
		'nullable_ternary_required_empty' => 'invalid_nullable_unwrap_empty',
		'nullable_coalesce_throw' => 'division_by_zero',
		'nullable_empty_extract' => 'invalid_nullable_unwrap_empty',
		'nullable_empty_string_extract' => 'invalid_nullable_unwrap_empty',
		'nullable_empty_argument' => 'invalid_nullable_unwrap_empty',
		'logical_and_guard' => 'division_by_zero', 'logical_or_guard' => 'modulo_by_zero',
		'logic_xor_throw' => 'division_by_zero', 'power_negative_error' => 'power_negative_exponent',
		'power_signed_exponent_error' => 'power_negative_exponent', 'power_overflow_error' => 'power_overflow'];
	if (isset($runtime_error_codes[$name]))
	{
		$error_code = $runtime_error_codes[$name];
		$probe = '
int main() { try { (void)function_fail(); } catch (const scpp::runtime_error& error) {'
			. ' return error.code() == "' . $error_code . '" ? 0 : 91; } return 92; }
';
		$probe_path = $directory . '/' . $name . '_throw.cpp';
		file_put_contents($probe_path, str_replace('int main()', 'int unused_entry()', Model::$cpp_files[0]->text) . $probe);
		$executions[] = ['path' => $probe_path, 'exit_code' => 0];
	}

	Preparation_Cleanup::tree($syntax->root);
	if (s2s_snapshot($syntax) !== $before) {
		throw new \LogicException('Preparation/emission changed source syntax or its scopes');
	}
}
$scope_isolation_rejections = [
	'$x = 1; function f(): int { return $x; }' => 'established local declaration for x',
];
foreach ($scope_isolation_rejections as $source => $diagnostic)
{
	Compiler_Lifecycle::reset();
	$syntax = s2s_parse($source);
	$before = s2s_snapshot($syntax);
	$failed = false;
	try {
		(new Compiler())->prepare();
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $diagnostic);
	}
	if (!$failed || !Model::$cpp_files->is_empty() || !Model::$prepared_files->is_empty() || (s2s_snapshot($syntax) !== $before)) {
		throw new \LogicException('NOTE-033.a allowed a function to capture an entry-body local implicitly');
	}
}

$rejections = ['function f(int &$x): void {} f(1);',
	'return isset($missing);',
	'return empty($missing);',
	'return empty([1, 2]);',
	'return isset([1, 2]);',
	'$x = [1, 2]; return isset($x[0]);',
	'function f(): void {} return empty(f());',
	'isset($new = 1);',
	'$x = null;',
	'$x int = null;',
	'$x ?int = 1; $y ?float = $x;',
	'$x ?int = 1; $y float = $x;',
	'$x ?int = true;',
	'$x ?int = 1; $y ?int = 2; return $x ?? $y;',
	'$x ?int = 1; return $x ?? 2.0;',
	'return 1 ?? "wrong";',
	'return null ?? 1;',
	'$x ?int = null; return $x ?: 1.5;',
	'$x ?int = null; return true ? $x : null;',
	'$x ?int = null; return true ? null : $x;',
	'$x ?int = null; $y ?float = null; return true ? $x : $y;',
	'$x ?int = null; $y = match (1) { 1 => $x, default => $x };',
	'$x ?int = 1; return $x == null;',
	'$x ?int = 1; return $x < null;',
	'$x ?int = 1; return $x + 1;',
	'function f(?void $x): void {}',
	'function f(int &$x): void {} $x ?int = 1; f($x);',
	'function f(?int &$x): void {} f(null);',
	'$x ?vector<int> = null;',
	'function f(int ...$items, int $last): void {}',
	'function f(int ...$items, int ...$more): void {}',
	'function f(int &...$items): void {}',
	'function f(int ...$items = 1): void {}',
	'function f(void ...$items): void {}',
	'function f(int $first, int ...$items): void {} f();',
	'function f(int ...$items): void {} f(1, "2");',
	'function f(): int { return 1; } function f(): int { return 2; }',
	'function f(int $x): int { return $x; } function f(string $x): int { return 2; }',
	'function f(): int { return 1; } function f(): int { return 2; } return f();',
	'function f(int $x, int $x): void {}',
	'function f(int $x, string $x): void {} f(1, "2");',
	'$outside = 3; function f(): int { return $outside; }',
	'$outside = 3; function f(): void { $outside += 1; }',
	'function f(int $x, int $y = 1): int { return $x; } f();',
	'function f(int $x = 1): int { return $x; } f(1, 2);',
	'function f(int $x = 1, int $y): int { return $x; }',
	'function f(int &$x = 1): void {}',
	'function f(int $x = "1"): int { return $x; } f(2);',
	'function f(string $x = 1): string { return $x; }',
	'function f(bool $x = 0): bool { return $x; }',
	'function f(float $x = 1): float { return $x; }',
	'function f(int $x = 1 + 2): int { return $x; }',
	'function g(): int { return 1; } function f(int $x = g()): int { return $x; }',
	'function f(int $x, int $y = $x): int { return $y; }',
	'function f(int $x = PHP_INT_MAX): int { return $x; }',
	'function f(string $x = "$missing"): string { return $x; }',
	'function f(bool $x = !true): bool { return $x; }',
	'function f(int $x = -true): int { return $x; }',
	'struct Box {} function f(Box $x = 1): void {}',
	'function f(int $amount, string $label): int { return $amount; } f(1, 2);',
	'function f(int $amount, string $label): int { return $amount; } f("2", 1);',
	'function f(int $amount, string $label): int { return $amount; } f(1, "2", true);',
	'function f(string $label): int { return $label; }',
	'function f(string $label): string { return 1; }',
	'function f(int &$x): void {} f(((1)));',
	'function f(int $x): int { return $x; } f();',
	'function f(): int { return; }',
	'function f(): void { return 1; }',
	'function f(): void {} $x = f();',
	'struct S { uint8 $x; } $s S; $s->missing = 1;',
	'struct A {} struct B {} $a A; $b B = $a;',
	'struct S { uint8 $x; } $s S = [1];',
	'struct Box {} $box Box; $value = (int)$box;',
	'struct Box {} $box Box; $value = (Box)$box;',
	'$value = (uint8)true;',
	'function f(int &$x): void {} $x uint8 = 1; f($x);',
	'$a float = 1;', '$a int = 1.5;', '$a = 1.5; $a = false;', '$a int = true;', '$a bool = 1;', '$a = true; $a = 1;', '$a = 1; $a = false;', '$a = 9223372036854775808;', '$a = 010;', '$a = $a;', '$a = $b = $a;', '$b = true; $a = $b = 1;', '$a = unknown();', '$a = UNKNOWN_CONSTANT;', '$a = php_int_max;', '$a void;', "return 'x';", 'template<T> function f(): int { return 1; }'];
foreach ($rejections as $source)
{
	Compiler_Lifecycle::reset();
	$syntax = s2s_parse($source);
	$before = s2s_snapshot($syntax);
	$failed = false;
	try {
		(new Compiler())->prepare();
		(new Compiler())->cpp();
	}
	catch (\RuntimeException $expected) {
		$failed = true;
	}
	if (!$failed || !Model::$cpp_files->is_empty() || !Model::$prepared_files->is_empty() || (s2s_snapshot($syntax) !== $before)) {
		throw new \LogicException('Unsupported generation published output');
	}
}

// A fresh local copies one established value; source and target keep distinct identities.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('$b int = 11; $a = $b; $b = 19; return $a;');
$children = $syntax->root->body->statements;
$source_declaration = object_cast($children[0], variable_declaration_node::class);
$copy_assignment = object_cast($children[1]->expression, assignment_expression_node::class);
$source_reference = object_cast($copy_assignment->value, variable_reference_node::class);
$source_mutation = object_cast($children[2]->expression, assignment_expression_node::class);
$copy_reference = object_cast($children[3]->expression, variable_reference_node::class);
$before = s2s_snapshot($syntax);
$compiler = new Compiler();
$compiler->prepare();
$source_binding = $source_declaration->require_preparation();
$copy_facts = $copy_assignment->require_assignment_preparation();
$copy_binding = $copy_facts->binding;
$source_reference_facts = $source_reference->require_variable_reference_preparation();
$mutation_binding = $source_mutation->require_assignment_preparation()->binding;
$copy_reference_facts = $copy_reference->require_variable_reference_preparation();
$integer_type = Language_Types::integer(Model::$language_scope);
$source_identity = weakref_get($source_binding->declaration);
$copy_identity = weakref_get($copy_binding->declaration);
if (($source_binding->resolved_kind !== binding_kind::declaration) ||
	($copy_binding->resolved_kind !== binding_kind::declaration) ||
	($mutation_binding->resolved_kind !== binding_kind::assignment) ||
	($source_identity === $copy_identity) ||
	(weakref_get($source_reference_facts->declaration) !== $source_identity) ||
	(weakref_get($mutation_binding->declaration) !== $source_identity) ||
	(weakref_get($copy_reference_facts->declaration) !== $copy_identity) ||
	(!$source_binding->type->matches($integer_type)) || (!$copy_facts->type->matches($integer_type)) ||
	(!$copy_binding->type->matches($integer_type)) || (!$source_reference_facts->type->matches($integer_type)) ||
	(!$mutation_binding->type->matches($integer_type)) || (!$copy_reference_facts->type->matches($integer_type))) {
	throw new \LogicException('Variable copy lost source order, declaration identity or canonical type');
}
$compiler->cpp();
$copy_output = Model::$cpp_files[0]->text;
if ((substr_count($copy_output, 'auto local_a = local_b;') !== 1) || str_contains($copy_output, 'auto local_a = static_cast')) {
	throw new \LogicException('Variable copy added a conversion or lost direct inferred declaration lowering');
}
Compiler_Lifecycle::reset_preparation();
if (($source_declaration->preparation() !== null) || ($copy_assignment->preparation() !== null) ||
	($source_reference->preparation() !== null) || ($source_mutation->preparation() !== null) ||
	($copy_reference->preparation() !== null) || (s2s_snapshot($syntax) !== $before)) {
	throw new \LogicException('Variable copy cleanup changed syntax or retained prepared facts');
}

$variable_copy_rejections = [
	'VAR-ORDER-001' => ['$a = $b; $b = 1;', 'established local declaration for b'],
	'self-initialization' => ['$a = $a;', 'established local declaration for a'],
];
foreach ($variable_copy_rejections as $name => [$source, $diagnostic])
{
	Compiler_Lifecycle::reset();
	$syntax = s2s_parse($source);
	$before = s2s_snapshot($syntax);
	$failed = false;
	try {
		(new Compiler())->prepare();
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $diagnostic);
	}
	if (!$failed || !Model::$cpp_files->is_empty() || !Model::$prepared_files->is_empty() || (s2s_snapshot($syntax) !== $before)) {
		throw new \LogicException($name . ' missed its source-order diagnostic or published partial results');
	}
}

$integer_addition_rejections = [
	'undeclared operand' => ['$b = $missing + 1;', 'established local declaration for missing'],
	'boolean operand' => ['$a = 1; $b = $a + true;', 'integer binary operation requires canonical int operands'],
	'narrow integer operand' => ['$a uint8 = 1; $b = $a + 1;', 'integer binary operation requires canonical int operands'],
	'effectful operand' => ['function value(): int { return 1; } $a = value() + 1;', 'binary operation requires order-independent operands'],
	'grouped effectful operand' => ['function value(): int { return 1; } $a = (value()) + 1;', 'binary operation requires order-independent operands'],
];
foreach ($integer_addition_rejections as $name => [$source, $diagnostic])
{
	Compiler_Lifecycle::reset();
	$syntax = s2s_parse($source);
	$before = s2s_snapshot($syntax);
	$failed = false;
	try {
		(new Compiler())->prepare();
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $diagnostic);
	}
	if (!$failed || !Model::$cpp_files->is_empty() || !Model::$prepared_files->is_empty() || (s2s_snapshot($syntax) !== $before)) {
		throw new \LogicException('Invalid integer addition ' . $name . ' missed its diagnostic or published partial results');
	}
}

// Reassignment preserves one local identity and type; explicit syntax cannot redeclare it.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('$a = 1; $a = 2; return $a;');
$children = $syntax->root->body->statements;
$declaration_assignment = object_cast($children[0]->expression, assignment_expression_node::class);
$reassignment = object_cast($children[1]->expression, assignment_expression_node::class);
$result_reference = object_cast($children[2]->expression, variable_reference_node::class);
$before = s2s_snapshot($syntax);
$compiler = new Compiler();
$compiler->prepare();
$declaration_facts = $declaration_assignment->require_assignment_preparation();
$reassignment_facts = $reassignment->require_assignment_preparation();
$result_facts = $result_reference->require_variable_reference_preparation();
$integer_type = Language_Types::integer(Model::$language_scope);
$declaration_identity = weakref_get($declaration_facts->binding->declaration);
if (($declaration_facts->binding->resolved_kind !== binding_kind::declaration) ||
	($reassignment_facts->binding->resolved_kind !== binding_kind::assignment) ||
	(weakref_get($reassignment_facts->binding->declaration) !== $declaration_identity) ||
	(weakref_get($result_facts->declaration) !== $declaration_identity) ||
	(!$declaration_facts->type->matches($integer_type)) || (!$declaration_facts->binding->type->matches($integer_type)) ||
	(!$reassignment_facts->type->matches($integer_type)) || (!$reassignment_facts->binding->type->matches($integer_type)) ||
	(!$result_facts->type->matches($integer_type))) {
	throw new \LogicException('Reassignment lost declaration identity, outcome or canonical type');
}
$compiler->cpp();
$reassignment_output = Model::$cpp_files[0]->text;
$reassignment_spelling = "\tauto local_a = static_cast<scpp::int_t<>>(1LL);\n"
	. "\tlocal_a = static_cast<scpp::int_t<>>(2LL);\n";
if (!str_contains($reassignment_output, $reassignment_spelling) ||
	(substr_count($reassignment_output, 'auto local_a =') !== 1)) {
	throw new \LogicException('Reassignment redeclared its target or lost canonical literal lowering');
}
Compiler_Lifecycle::reset_preparation();
if (($declaration_assignment->preparation() !== null) || ($reassignment->preparation() !== null) ||
	($result_reference->preparation() !== null) || (s2s_snapshot($syntax) !== $before)) {
	throw new \LogicException('Reassignment cleanup changed syntax or retained prepared facts');
}

Compiler_Lifecycle::reset();
$syntax = s2s_parse('$a uint8 = 1; $b = 2; $a = $b; return $a;');
$compiler = new Compiler();
$compiler->prepare();
$compiler->cpp();
if (!str_contains(Model::$cpp_files[0]->text,
	'local_a = static_cast<scpp::int_t<std::uint8_t>>((local_b).native_value());')) {
	throw new \LogicException('Reassignment omitted a required compatible-integer conversion');
}

Compiler_Lifecycle::reset();
$syntax = s2s_parse('$a = 1; $a = $a; return $a;');
$children = $syntax->root->body->statements;
$self_reassignment = object_cast($children[1]->expression, assignment_expression_node::class);
$self_reference = object_cast($self_reassignment->value, variable_reference_node::class);
$compiler = new Compiler();
$compiler->prepare();
$self_binding = $self_reassignment->require_assignment_preparation()->binding;
$self_reference_facts = $self_reference->require_variable_reference_preparation();
if (($self_binding->resolved_kind !== binding_kind::assignment) ||
	(weakref_get($self_binding->declaration) !== weakref_get($self_reference_facts->declaration))) {
	throw new \LogicException('Reassignment RHS could not read the established target binding');
}

$reassignment_rejections = [
	'$a = 1; $a = false;' => 'value boundary requires matching types or an integer conversion',
	'$a int = 1; $a int = 2;' => 'local a is already declared in this scope',
];
foreach ($reassignment_rejections as $source => $diagnostic)
{
	Compiler_Lifecycle::reset();
	$syntax = s2s_parse($source);
	$before = s2s_snapshot($syntax);
	$failed = false;
	try {
		(new Compiler())->prepare();
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $diagnostic);
	}
	if (!$failed || !Model::$cpp_files->is_empty() || !Model::$prepared_files->is_empty() || (s2s_snapshot($syntax) !== $before)) {
		throw new \LogicException('Invalid reassignment or duplicate declaration missed its semantic diagnostic');
	}
}

// Explicit local types are authoritative semantic boundaries, not deferred C++ failures.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('$x string = "test"; $x = "next";');
$children = $syntax->root->body->statements;
$declaration = object_cast($children[0], variable_declaration_node::class);
$assignment = object_cast($children[1]->expression, assignment_expression_node::class);
$before = s2s_snapshot($syntax);
$compiler = new Compiler();
$compiler->prepare();
$declaration_facts = $declaration->require_preparation();
$assignment_facts = $assignment->require_assignment_preparation()->binding;
$string_type = Language_Types::string_type(Model::$language_scope);
if (($declaration_facts->resolved_kind !== binding_kind::declaration) ||
	($assignment_facts->resolved_kind !== binding_kind::assignment) ||
	(!$declaration_facts->type->matches($string_type)) || (!$assignment_facts->type->matches($string_type)) ||
	(weakref_get($declaration_facts->declaration) !== weakref_get($assignment_facts->declaration))) {
	throw new \LogicException('Explicit string local lost its prepared type or declaration identity');
}
$compiler->cpp();
$typed_output = Model::$cpp_files[0]->text;
$typed_spelling = "\tscpp::string_t local_x = scpp::string_t(\"test\");\n\tlocal_x = scpp::string_t(\"next\");\n";
if (!str_contains($typed_output, $typed_spelling)) {
	throw new \LogicException('Explicit string local did not retain typed declaration and ordinary reassignment lowering');
}
Compiler_Lifecycle::reset_preparation();
if (($declaration->preparation() !== null) || ($assignment->preparation() !== null) || (s2s_snapshot($syntax) !== $before)) {
	throw new \LogicException('Explicit local cleanup changed syntax or retained prepared facts');
}

$typed_local_rejections = [
	'$x string = 1;' => 'value boundary requires matching types or an integer conversion',
];
foreach ($typed_local_rejections as $source => $diagnostic)
{
	Compiler_Lifecycle::reset();
	$syntax = s2s_parse($source);
	$before = s2s_snapshot($syntax);
	$failed = false;
	try {
		(new Compiler())->prepare();
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $diagnostic);
	}
	if (!$failed || !Model::$cpp_files->is_empty() || !Model::$prepared_files->is_empty() || (s2s_snapshot($syntax) !== $before)) {
		throw new \LogicException('Explicit local mismatch missed its semantic diagnostic');
	}
}

// Unsupported double-quoted forms fail during semantic preparation with their agreed diagnostic.
$string_rejections = [
	'$a = "hello $name";' => 'established local declaration for name',
	'$a = "\u{41}";' => 'Unicode escape syntax is not supported',
];
foreach ($string_rejections as $source => $diagnostic)
{
	Compiler_Lifecycle::reset();
	$syntax = s2s_parse($source);
	$before = s2s_snapshot($syntax);
	$failed = false;
	try {
		(new Compiler())->prepare();
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $diagnostic);
	}
	if (!$failed || !Model::$cpp_files->is_empty() || !Model::$prepared_files->is_empty() || (s2s_snapshot($syntax) !== $before)) {
		throw new \LogicException('Unsupported string form missed its semantic diagnostic');
	}
}

// Required by-value completion rejects cycles before body preparation or emission.
foreach (['struct Loop { Loop $next; }', 'struct A { B $b; } struct B { A $a; }'] as $source)
{
	Compiler_Lifecycle::reset();
	$syntax = s2s_parse($source);
	$compiler = new Compiler();
	$before = s2s_snapshot($syntax);
	$failed = false;
	try {
		$compiler->prepare();
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), 'Cyclic by-value');
	}
	if ((!$failed) || (!Model::$cpp_files->is_empty()) || (!Model::$prepared_files->is_empty()) || (s2s_snapshot($syntax) !== $before)) {
		throw new \LogicException('Recursive layout failure changed syntax or published output');
	}
}

// Malformed numeric tokens must not split into accidentally valid expressions.
foreach (['.', '.e2', '1e', '1e+', '1e-', '1.2.3', '1e2e3', '1.0f', '1_0.5', '0x1.2'] as $spelling)
{
	Compiler_Lifecycle::reset();
	$failed = false;
	try {
		s2s_parse('$a = ' . $spelling . ';');
	}
	catch (\RuntimeException $expected) {
		$failed = true;
	}
	if (!$failed) {
		throw new \LogicException('Unsupported numeric syntax accepted: ' . $spelling);
	}
}

// Floating facts retain exact text and canonical identity; cleanup belongs to specialization.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('$a = 1.2345678901234567; $b = $a;');
$children = $syntax->root->body->statements;
$float_node = $children[0]->expression->value;
$float_data = object_cast($float_node, float_literal_node::class);
$before = s2s_snapshot($syntax);
$compiler = new Compiler();
$compiler->prepare();
$float_facts = $float_data->require_float_literal_preparation();
$floating = Language_Types::floating(Model::$language_scope);
$resolved = Scope_Lookup::types(Model::$global_scope, 'float');
$floating_definition = object_cast($resolved[0], floating_type_definition::class);
if (($floating_definition->storage_bits() !== 64) || ($floating_definition->precision_bits() !== 53)
	|| ($float_facts->decimal !== '1.2345678901234567') || (!$float_facts->type->matches($floating))
	|| (!$children[1]->expression->require_preparation()->type->matches($floating))) {
	throw new \LogicException('Floating literal lost precision or canonical type identity');
}
$compiler->cpp();
Compiler_Lifecycle::reset_cpp();
if ($float_data->require_float_literal_preparation() !== $float_facts) {
	throw new \LogicException('Output reset changed floating facts');
}
Compiler_Lifecycle::reset_preparation();
if (($float_data->preparation() !== null) || (s2s_snapshot($syntax) !== $before)) {
	throw new \LogicException('Floating cleanup changed syntax or retained facts');
}

// Both quote spellings retain decoded bytes and one canonical string identity.
Compiler_Lifecycle::reset();
$string_source = <<<'PHS'
$a = 'x';
$b = 'can\'t';
$c = 'slash\\path';
$d = '\n';
$f = "line\nnext";
$g = "quote\"slash\\dollar\$";
$h = "unknown\q";
$i = "hex\x41";
$j = "octal\101";
$k = "controls\r\t\v\f\e";
$l = '';
$m = "";
PHS;
$string_source .= "\n" . '$e = \'' . string_byte_from_int(0) . '\';';
$syntax = s2s_parse($string_source);
$children = $syntax->root->body->statements;
$expected_values = ['x', "can't", 'slash' . "\\" . 'path', "\\n", "line\nnext",
	'quote"slash' . "\\" . 'dollar$', 'unknown' . "\\" . 'q', 'hexA', 'octalA',
	"controls\r\t\v\f" . string_byte_from_int(27), '', '', string_byte_from_int(0)];
$string_nodes /** vector<string_literal_node> */ = [];
foreach ($children as $index => $statement) {
	$assignment = object_cast($statement->expression, assignment_expression_node::class);
	$string_nodes[] = object_cast($assignment->value, string_literal_node::class);
}
$before = s2s_snapshot($syntax);
$compiler = new Compiler();
$compiler->prepare();
$string_type = Language_Types::string_type(Model::$language_scope);
$resolved = Scope_Lookup::types(Model::$global_scope, 'string');
if (Type_Preparation::canonical($string_type)->definition() !== $resolved[0]) {
	throw new \LogicException('Canonical string identity changed');
}
foreach ($string_nodes as $index => $string_node)
{
	$facts = $string_node->require_string_literal_preparation();
	if (($facts->value !== $expected_values[$index]) || (!$facts->type->matches($string_type))) {
		throw new \LogicException('String literal lost decoded bytes or canonical type');
	}
	foreach ($syntax->collection->entries as $entry) {
		if ($entry->syntax() === $string_node) {
			throw new \LogicException('String literal was collected as a name');
		}
	}
}
$compiler->cpp();
$string_output = Model::$cpp_files[0]->text;
foreach (['scpp::string_t("x")', 'scpp::string_t("can\'t")', 'scpp::string_t("slash\\\\path")',
	'scpp::string_t("\\\\n")', 'scpp::string_t("")', 'scpp::string_t(std::string("\\x00" "", 1))'] as $expected_spelling) {
	if (!str_contains($string_output, $expected_spelling)) {
		throw new \LogicException('C++ string escaping lost exact bytes: ' . $expected_spelling);
	}
}
Compiler_Lifecycle::reset_preparation();
foreach ($string_nodes as $string_node) {
	if ($string_node->preparation() !== null) {
		throw new \LogicException('String cleanup retained prepared facts');
	}
}
if (s2s_snapshot($syntax) !== $before) {
	throw new \LogicException('String preparation changed source syntax');
}

// Boolean literals keep canonical identity without entering reference/name collection.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('$a = false; $b = $a; return $b;');
$children = $syntax->root->body->statements;
$binding_data = $children[0]->expression;
$literal_node = $binding_data->value;
$literal_data = object_cast($literal_node, boolean_literal_node::class);
$reference_data = object_cast($children[1]->expression->value, variable_reference_node::class);
$before = s2s_snapshot($syntax);
$compiler = new Compiler();
$compiler->prepare();
$boolean_type = Language_Types::boolean(Model::$language_scope);
$literal_facts = $literal_data->require_boolean_literal_preparation();
if (($literal_node->kind() !== node_kind::boolean_literal) || (Syntax_Nodes::category($literal_node) !== node_category::expression) || $literal_data->value || $literal_facts->value || (!$literal_facts->type->matches($boolean_type)) || (!$reference_data->require_preparation()->type->matches($boolean_type))) {
	throw new \LogicException('Boolean syntax, false value or inferred type changed');
}
foreach ($syntax->collection->entries as $entry) {
	if ($entry->syntax() === $literal_node) {
		throw new \LogicException('Boolean literal was collected as a name');
	}
}
$compiler->cpp();
$expected = "#include \"scpp/bool_t.hpp\"\n\nint main()\n{\n\tauto local_a = static_cast<scpp::bool_t>(false);\n\tauto local_b = local_a;\n\treturn static_cast<int>((local_b).native_value());\n\treturn 0;\n}\n";
if (Model::$cpp_files[0]->text !== $expected) {
	throw new \LogicException('Unexpected boolean C++ representation or includes');
}
Compiler_Lifecycle::reset_cpp();
if ($literal_data->require_boolean_literal_preparation() !== $literal_facts) {
	throw new \LogicException('Boolean facts lost across output reset');
}
Compiler_Lifecycle::reset_preparation();
if (($literal_data->preparation() !== null) || ($reference_data->preparation() !== null) || (s2s_snapshot($syntax) !== $before)) {
	throw new \LogicException('Boolean cleanup missed facts or changed syntax');
}

// A standalone failure must preserve syntax; partial-fact recovery remains deferred.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('$a = 10; $b = $missing;');
$children = $syntax->root->body->statements;
$first_data = $children[0]->expression;
$first_literal_data = object_cast($first_data->value, integer_literal_node::class);
$before = s2s_snapshot($syntax);
$failed = false;
try {
	(new File_Preparation($syntax->collection, Model::$language_scope))->prepare();
}
catch (\RuntimeException $expected) {
	$failed = true;
}
if ((!$failed) || (s2s_snapshot($syntax) !== $before)) {
	throw new \LogicException('Standalone failure left prepared facts or changed syntax');
}

// Preparation and emission are independent; an output failure retains valid facts.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('$a = 10; return $a;');
$compiler = new Compiler();
$failed = false;
try {
	$compiler->cpp();
}
catch (\RuntimeException $expected) {
	$failed = true;
}
if (!$failed || !Model::$prepared_files->is_empty() || !Model::$cpp_files->is_empty()) {
	throw new \LogicException('Emission implicitly prepared source');
}
$compiler->prepare();
$children = $syntax->root->body->statements;
$first_data = $children[0]->expression;
$first_literal_data = object_cast($first_data->value, integer_literal_node::class);
$binding_facts = $first_data->require_assignment_preparation();
$literal_facts = $first_literal_data->require_integer_literal_preparation();
$completion = Model::$prepared_files[0];
if (!Model::$cpp_files->is_empty()) {
	throw new \LogicException('Preparation emitted C++ output');
}
$compiler->cpp();
$output = Model::$cpp_files[0];
Compiler_Lifecycle::reset_cpp();
if (($first_data->require_assignment_preparation() !== $binding_facts) || (Model::$prepared_files[0] !== $completion) || !Model::$cpp_files->is_empty()) {
	throw new \LogicException('Output reset changed shared preparation');
}
$compiler->cpp();
$valid_integer_type = $first_literal_data->require_integer_literal_preparation()->type;
$first_literal_data->require_integer_literal_preparation()->type = new canonical_type_use(Type_Identity::maximum());
// Fault injection must invalidate the fragment whose prepared representation was altered.
Model::$cpp_output_program->fragments[$syntax->collection->root->body->work()]->change_status = change_state::changed;
$failed = false;
try {
	$compiler->cpp();
}
catch (\RuntimeException $expected) {
	$failed = true;
}
if ((!$failed) || ($first_data->preparation() !== $binding_facts) || ($first_literal_data->preparation() !== $literal_facts) || (Model::$prepared_files[0] !== $completion) || (!Model::$cpp_files->is_empty())) {
	throw new \LogicException('Emission failure damaged shared preparation or retained stale output');
}
$first_literal_data->require_integer_literal_preparation()->type = $valid_integer_type;
$compiler->cpp();
if (Model::$cpp_files[0]->text !== $output->text) {
	throw new \LogicException('Emission retry changed output');
}
$compiler->prepare();
if (($first_data->require_assignment_preparation() !== $binding_facts) || !Model::$cpp_files->is_empty()) {
	throw new \LogicException('No-op preparation replaced facts or left stale output');
}
$compiler->cpp();
Compiler_Lifecycle::reset_preparation();
if (($first_data->preparation() !== null) || ($first_literal_data->preparation() !== null) || !Model::$prepared_files->is_empty() || !Model::$cpp_files->is_empty()) {
	throw new \LogicException('Preparation reset retained facts or dependent output');
}

// The cleanup traversal reaches nested expression specializations through syntax-only parents.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('function nested(): int { return 7; }');
$children = $syntax->root->declarations;
$body = object_cast($children[0], function_node::class)->body;
$statements = $body->statements;
$nested_literal_data = object_cast(object_cast($statements[0], return_node::class)->expression, integer_literal_node::class);
$before = s2s_snapshot($syntax);
$facts = new prepared_integer_literal();
$facts->type = Language_Types::integer(Model::$language_scope);
$facts->decimal = '7';
$nested_literal_data->set_preparation($facts);
Compiler_Lifecycle::reset_preparation();
if (($nested_literal_data->preparation() !== null) || (s2s_snapshot($syntax) !== $before)) {
	throw new \LogicException('Nested node cleanup changed syntax or missed attached facts');
}

// Reset must clear the old graph before dropping it, including externally retained nodes.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('$a = 10; return $a;');
(new Compiler())->prepare();
(new Compiler())->cpp();
$children = $syntax->root->body->statements;
$first_data = $children[0]->expression;
$first_literal_data = object_cast($first_data->value, integer_literal_node::class);
Compiler_Lifecycle::reset_syntax();
if (($first_data->preparation() !== null) || ($first_literal_data->preparation() !== null)) {
	throw new \LogicException('Syntax reset dropped roots before cleaning their nodes');
}

// Ordinary parent traversal permits source shadowing; reserved-name enforcement is deferred.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('struct int { int $field; }');
$root = $syntax->root->file_scope();
$local = new scope();
$local->set_parent($root);
$found = Scope_Lookup::types($local, 'int');
if (count($found) !== 1 || $found[0]->origin() !== type_definition_origin::source || $found[0] !== $root->types_named('int')[0]) {
	throw new \LogicException('Source type did not shadow parent or publication copied its identity');
}
$entry = Model::$type_catalog->source_declarations()->declaration($found[0]->definition_id());
$entry->change_status = change_state::deleted;
(new Preparation_Worker(Model::$language_scope))->remove_deleted_sources(Model::collected_files());
$found = Scope_Lookup::types($local, 'int');
$language_integer = Type_Preparation::canonical(Language_Types::integer(Model::$language_scope))->definition();
if (count($found) !== 1 || $found[0] !== $language_integer) {
	throw new \LogicException('Deleted source type blocked parent lookup');
}
$replacement = Model::$type_catalog->define_source_structure('int', $entry)->nominal_definition();
$entry->changes = 0;
$local->register_type($replacement);
if (Scope_Lookup::types($local, 'int')[0] !== $replacement) {
	throw new \LogicException('Nearest scope lost precedence');
}
file_put_contents($directory . '/programs.json', json_encode(['valid' => $cases, 'rejected' => $rejections], JSON_PRETTY_PRINT));
file_put_contents($directory . '/executions.json', json_encode($executions, JSON_PRETTY_PRINT));
echo "S2S: canonical types, first assignment, reuse, node cleanup, syntax purity, scope lookup and bounded output passed\n";
