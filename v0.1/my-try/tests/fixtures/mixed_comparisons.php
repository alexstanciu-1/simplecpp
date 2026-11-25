<?php

/* Equality dispatch, exact identity and numeric ordering remain distinct semantic paths. */
$fixtures = [];
foreach (['equal' => '==', 'different' => '!=', 'identical' => '===', 'notidentical' => '!=='] as $name => $op)
{
	$strict = ($op === '===') || ($op === '!==');
	$negate = ($op === '!=') || ($op === '!==');
	$source = 'function compare(mixed $a, mixed $b): bool { return $a ' . $op . ' $b; } '
		. 'function nullright(mixed $a): bool { return $a ' . $op . ' null; } '
		. 'function nullleft(mixed $a): bool { return null ' . $op . ' $a; } ';
	$probe = '([&]() { scpp::mixed_t one{scpp::int_t<>{1LL}}; ';
	foreach (['int' => 'scpp::int_t<>{1LL}', 'float' => 'scpp::float_t{1.0}',
		'bool' => 'scpp::bool_t{true}', 'string' => 'scpp::string_t{"1"}'] as $type => $value)
	{
		$source .= 'function right' . $type . '(mixed $a, ' . $type . ' $b): bool { return $a ' . $op . ' $b; } '
			. 'function left' . $type . '(' . $type . ' $a, mixed $b): bool { return $a ' . $op . ' $b; } ';
		$expected = (($strict ? $type === 'int' : true) !== $negate) ? 'true' : 'false';
		$probe .= 'if (function_right' . $type . '(one, ' . $value . ').native_value() != ' . $expected
			. ' || function_left' . $type . '(' . $value . ', one).native_value() != ' . $expected
			. ' || function_compare(one, scpp::mixed_t{' . $value . '}).native_value() != ' . $expected . ') return false; ';
	}
	$yes = $negate ? 'false' : 'true';
	$no = $negate ? 'true' : 'false';
	$zero = (!$strict !== $negate) ? 'true' : 'false';
	$probe .= 'scpp::mixed_t nil{scpp::null}, zero{scpp::int_t<>{0LL}}; '
		. 'return function_compare(nil, nil).native_value() == ' . $yes
		. ' && function_nullright(nil).native_value() == ' . $yes
		. ' && function_nullleft(nil).native_value() == ' . $yes
		. ' && function_nullright(one).native_value() == ' . $no
		. ' && function_nullleft(one).native_value() == ' . $no
		. ' && function_nullright(zero).native_value() == ' . $zero
		. ' && function_compare(scpp::mixed_t{scpp::string_t{"x"}}, scpp::mixed_t{scpp::string_t{"y"}}).native_value() == ' . $no
		. ' && function_compare(scpp::mixed_t{scpp::string_t{"x"}}, scpp::mixed_t{scpp::string_t{"x"}}).native_value() == ' . $yes . '; }())';
	$fixtures['mixed_comparison_' . $name] = [$source, $probe];
}
foreach (['less' => ['<', true, false], 'lessequal' => ['<=', true, true],
	'greater' => ['>', false, false], 'greaterequal' => ['>=', false, true]] as $name => [$op, $ascending, $same])
{
	$expected = $ascending ? 'true' : 'false';
	$equal = $same ? 'true' : 'false';
	$fixtures['mixed_comparison_' . $name] = [
		'function compare(mixed $a, mixed $b): bool { return $a ' . $op . ' $b; } '
			. 'function right(mixed $a, float $b): bool { return $a ' . $op . ' $b; } '
			. 'function left(int $a, mixed $b): bool { return $a ' . $op . ' $b; } '
			. 'function store(mixed $a, mixed $b, bool &$out): void { $out = $a ' . $op . ' $b; }',
		'([&]() { scpp::mixed_t a{scpp::int_t<>{1LL}}, b{scpp::float_t{2.0}}; '
			. 'if (function_compare(a, b).native_value() != ' . $expected
			. ' || function_compare(a, a).native_value() != ' . $equal
			. ' || function_right(a, scpp::float_t{2.0}).native_value() != ' . $expected
			. ' || function_left(scpp::int_t<>{1LL}, b).native_value() != ' . $expected . ') return false; '
			. 'scpp::bool_t out{true}; int caught = 0; auto table = scpp::shared<scpp::hash_t<scpp::mixed_t>>(); '
			. 'for (const auto& bad : {scpp::mixed_t{scpp::null}, scpp::mixed_t{false}, '
			. 'scpp::mixed_t{scpp::string_t{"1"}}, scpp::mixed_t{scpp::unique<scpp::hash_t<scpp::mixed_t>>()}}) { '
			. 'try { function_store(bad, a, out); } catch (const std::runtime_error&) { ++caught; } '
			. 'try { function_store(a, bad, out); } catch (const std::runtime_error&) { ++caught; } } '
			. 'return caught == 8 && out.native_value(); }())',
	];
}
$fixtures['mixed_comparison_tables'] = [
	'function equal(mixed $a, mixed $b): bool { return $a == $b; } '
		. 'function identical(mixed $a, mixed $b): bool { return $a === $b; } '
		. 'function isnull(mixed $a): bool { return $a === null; }',
	'([&]() { auto first = scpp::shared<scpp::hash_t<scpp::mixed_t>>(); auto second = scpp::shared<scpp::hash_t<scpp::mixed_t>>(); '
		. 'scpp::mixed_t a{first}, b{second}, absent{scpp::shared_p<scpp::hash_t<scpp::mixed_t>>{}}, nil{}; '
		. 'if (!function_equal(a, a).native_value() || function_equal(a, b).native_value() '
		. '|| !function_identical(a, b).native_value() || !function_isnull(absent).native_value() '
		. '|| function_identical(absent, nil).native_value() || !function_identical(absent, absent).native_value()) return false; '
		. 'second->set(scpp::string_t{"x"}, scpp::mixed_t{scpp::int_t<>{1LL}}); '
		. 'scpp::mixed_t owned{scpp::unique<scpp::hash_t<scpp::mixed_t>>()}; int caught = 0; '
		. 'try { function_equal(owned, owned); } catch (const std::runtime_error&) { ++caught; } '
		. 'return caught == 1 && !function_identical(a, b).native_value(); }())',
];
$fixtures['mixed_comparison_null_once'] = [
	'function once(mixed $v, int &$n): mixed { $n++; return $v; } '
		. 'function right(mixed $v, int &$n): bool { return once($v, $n) === null; } '
		. 'function left(mixed $v, int &$n): bool { return null !== once($v, $n); }',
	'([&]() { scpp::mixed_t nil{scpp::null}; scpp::int_t<> n{0LL}; '
		. 'return function_right(nil, n).native_value() && !function_left(nil, n).native_value() && n.native_value() == 2; }())',
];
return $fixtures;
