<?php

/* Source fixtures plus independent native state observations; no source extraction feature implied. */
$fixtures = [];
foreach (['result', 'result_or_false', 'result_or_bool'] as $family)
{
	$fixtures['result_construct_' . $family] = [
		'$i ' . $family . '<int> = 0; $b ' . $family . '<bool> = false; '
			. '$f ' . $family . '<float> = 1.5; $s ' . $family . '<string> = ""; '
			. '$n uint8 = 7; $u ' . $family . '<byte> = $n;',
		'local_i.has_value().native_value() && local_i.value().native_value() == 0'
			. ' && local_b.has_value().native_value() && !local_b.value().native_value()'
			. ' && local_f.has_value().native_value() && local_f.value().native_value() == 1.5'
			. ' && local_s.has_value().native_value() && local_s.value().native_value().empty()'
			. ' && local_u.has_value().native_value() && local_u.value().native_value() == 7',
	];
	$fixtures['result_copy_' . $family] = [
		'function pass(' . $family . '<string> $v): ' . $family . '<string> { return $v; } '
			. 'function change(' . $family . '<string> $v): ' . $family . '<string> { $v = "changed"; return $v; } '
			. '$a ' . $family . '<string> = "old"; $b = pass($a); $c = change($a); '
			. '$d ' . $family . '<string> = "first"; $d = $a; $a = "new";',
		'local_a.value().native_value() == "new" && local_b.value().native_value() == "old"'
			. ' && local_c.value().native_value() == "changed" && local_d.value().native_value() == "old"',
	];
	$fixtures['result_boundaries_' . $family] = [
		'function produce(int &$n): int { $n++; return $n; } '
			. 'function wrap(int $n): ' . $family . '<int> { return $n; } '
			. 'function pass(' . $family . '<int> $v): ' . $family . '<int> { return $v; } '
			. 'function replace(' . $family . '<int> &$v): void { $v = 7; } '
			. '$n = 0; $a = pass(produce($n)); $b = wrap(produce($n)); '
			. '$c ' . $family . '<int> = 0; $c = produce($n); $d = $a; replace($a);',
		'local_n.native_value() == 3 && local_a.value().native_value() == 7'
			. ' && local_b.value().native_value() == 2 && local_c.value().native_value() == 3'
			. ' && local_d.value().native_value() == 1',
	];
}
foreach (['result_or_false', 'result_or_bool'] as $family)
{
	$fixtures['result_false_' . $family] = [
		'function absent(): ' . $family . '<int> { return false_sentinel; } '
			. 'function pass(' . $family . '<int> $v): ' . $family . '<int> { return $v; } '
			. '$a ' . $family . '<int> = null; $b ' . $family . '<int> = nullopt; '
			. '$c = pass(false_sentinel); $d = absent(); $e = $c; $c = 7;',
		'local_a.is_false().native_value() && local_b.is_false().native_value()'
			. ' && local_c.has_value().native_value() && local_c.value().native_value() == 7'
			. ' && local_d.is_false().native_value() && local_e.is_false().native_value()',
	];
	$fixtures['result_bool_' . $family] = [
		'$a ' . $family . '<bool> = false; $b = $a; $a = true; '
			. '$c ' . $family . '<bool> = false_sentinel; $d = $c; $c = false; '
			. '$e ' . $family . '<bool> = true; $e = null; '
			. '$f ' . $family . '<bool> = true; $f = nullopt;',
		'local_a.has_value().native_value() && local_a.value().native_value()'
			. ' && local_b.has_value().native_value() && !local_b.value().native_value()'
			. ' && local_c.has_value().native_value() && !local_c.value().native_value()'
			. ' && local_d.is_false().native_value() && local_e.is_false().native_value()'
			. ' && local_f.is_false().native_value()',
	];
}
$fixtures['result_true_states'] = [
	'function yes(): result_or_bool<bool> { return true_sentinel; } '
		. 'function pass(result_or_bool<int> $v): result_or_bool<int> { return $v; } '
		. '$a = yes(); $b = $a; $a = false; $c = pass(true_sentinel); '
		. '$d result_or_bool<string> = "payload"; $d = true_sentinel; $e = $d; $d = false_sentinel;',
	'local_a.has_value().native_value() && !local_a.value().native_value()'
		. ' && local_b.is_true().native_value() && local_c.is_true().native_value()'
		. ' && local_d.is_false().native_value() && local_e.is_true().native_value()',
];
$fixtures['result_error_copy_bridge'] = [
	'function pass(result<int> $v): result<int> { return $v; }',
	'function_pass(scpp::result<scpp::int_t<>>{scpp::error_t{scpp::string_t("failure")}})'
		. '.error()->get_message().native_value() == "failure"',
];
return $fixtures;
