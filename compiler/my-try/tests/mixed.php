<?php

/* Prepared mixed boundaries, deferred combinations and incremental failure/recovery. */
namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function mixed_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Compile through the same retained pipeline used by the source fixtures. */
function mixed_source(string $path, string $source): Compiler
{
	file_put_contents($path, $source);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([dirname($path)]);
	$compiler->exec_cpp();
	return $compiler;
}

$directory = sys_get_temp_dir() . '/scpp_mixed_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
try
{
	mixed_source($path, '$a mixed = 7; $b mixed = null; $c mixed = $a; $d int = $a;');
	$root = Model::syntax_files()[0]->root;
	foreach ([conversion_operation::boxed_wrap, conversion_operation::boxed_wrap,
		conversion_operation::identity, conversion_operation::boxed_extract] as $position => $operation)
	{
		$facts = $root->single_body()->body->statements[$position]->require_preparation();
		mixed_check($facts->conversion->operation === $operation, 'Wrong prepared mixed operation');
		mixed_check($facts->conversion->result_type->matches($facts->type), 'Mixed boundary lost target identity');
	}
	mixed_check(str_contains(CPP_Probe::text(Model::$cpp_files), 'scpp::required_cast<scpp::int_t<>>'),
		'Required extraction became an implicit or explicit cast');
	Preparation_Cleanup::tree($root);
	mixed_check($root->single_body()->body->statements[3]->preparation() === null, 'Mixed conversion facts survived cleanup');

	$registry = Model::$type_catalog->registry();
	$renamed = $registry->define_mixed('OtherBox', type_definition_origin::runtime,
		[generic_contract::value_storable, generic_contract::copyable_value]);
	$box = $registry->use($registry->canonical($renamed)->type_id());
	$integer = Language_Types::integer(Model::$language_scope);
	mixed_check(Conversion_Preparation::decide($integer, $box, conversion_context::assignment)->operation
		=== conversion_operation::boxed_wrap, 'Mixed boxing depended on source spelling');
	mixed_check(Conversion_Preparation::decide($box, $integer, conversion_context::return_value)->operation
		=== conversion_operation::boxed_extract, 'Mixed extraction depended on source spelling');
	mixed_check(Conversion_Preparation::decide($box, $integer, conversion_context::explicit_cast)->operation
		=== conversion_operation::explicit_runtime_cast, 'Explicit mixed cast reused required extraction');
	mixed_check(Condition_Truthiness::decide($box)->operation === truthiness_operation::boxed_condition,
		'Mixed truthiness depended on source spelling');
	$renamed_dynamic = $registry->define_dynamic('OtherHandle', type_definition_origin::runtime,
		[generic_contract::value_storable, generic_contract::copyable_value]);
	$handle = $registry->use($registry->canonical($renamed_dynamic)->type_id());
	mixed_check(Conversion_Preparation::decide($handle, $box, conversion_context::explicit_cast)->operation
		=== conversion_operation::explicit_runtime_cast, 'Checked family crossing depended on source spelling');
	mixed_check(Conversion_Preparation::decide($box, $handle, conversion_context::explicit_cast)->operation
		=== conversion_operation::explicit_runtime_cast, 'Dynamic extraction used a required boundary');
	$arithmetic = Boxed_Operators::decide(operator_kind::addition, [$integer, $box]);
	$text = Conversion_Preparation::decide($box, Language_Types::string_type(Model::$language_scope), conversion_context::concatenation);
	mixed_check($text->operation === conversion_operation::text_conversion, 'Text conversion depended on mixed spelling');
	$logical = Boxed_Operators::decide_logical(operator_kind::logical_and, [$integer, $box]);
	mixed_check($logical->operation === operator_operation::condition_and
		&& $logical->result_type->matches(Language_Types::boolean(Model::$language_scope))
		&& $logical->operand_truthiness[0]->operation === truthiness_operation::scalar_condition
		&& $logical->operand_truthiness[1]->operation === truthiness_operation::boxed_condition,
		'Mixed logical normalization lost operand truthiness or depended on spelling');
	$comparison = Boxed_Operators::decide(operator_kind::identical, [$integer, $box]);
	mixed_check($comparison->operation === operator_operation::boxed_identical
		&& $comparison->result_type->matches(Language_Types::boolean(Model::$language_scope))
		&& $comparison->operands[0]->operation === conversion_operation::identity,
		'Mixed comparison depended on spelling or boxed strict identity operands');
	mixed_check($arithmetic->operation === operator_operation::boxed_addition
		&& $arithmetic->result_type->matches($box)
		&& $arithmetic->operands[0]->operation === conversion_operation::boxed_wrap
		&& $arithmetic->operands[1]->operation === conversion_operation::identity,
		'Mixed arithmetic depended on spelling or extracted its operands');
	mixed_source($path, '$v mixed = 7; $sum mixed = $v + 2; $negative mixed = -$v; $typed int = $v * 3;');
	$arithmetic_root = Model::syntax_files()[0]->root;
	$sum_node = $arithmetic_root->single_body()->body->statements[1]->initializer;
	$negative_node = $arithmetic_root->single_body()->body->statements[2]->initializer;
	mixed_check($sum_node->require_binary_preparation()->decision->operation === operator_operation::boxed_addition
		&& Boxed_Conversions::is_mixed($sum_node->require_preparation()->type)
		&& !$sum_node->require_preparation()->addressable(), 'Mixed arithmetic lost its owned result');
	mixed_check($negative_node->require_unary_preparation()->decision->operation === operator_operation::boxed_negative,
		'Mixed unary operation lost its runtime dispatch');
	mixed_check($arithmetic_root->single_body()->body->statements[3]->require_preparation()->conversion->operation
		=== conversion_operation::boxed_extract, 'Typed destination changed arithmetic dispatch');
	Preparation_Cleanup::tree($arithmetic_root);
	mixed_check($sum_node->preparation() === null && $negative_node->preparation() === null,
		'Mixed arithmetic retained stale facts');
	mixed_source($path, '$v mixed = "0"; $answer bool = $v || 2;');
	$logical_root = Model::syntax_files()[0]->root;
	$logical_node = $logical_root->single_body()->body->statements[1]->initializer;
	$logical_facts = $logical_node->require_binary_preparation();
	mixed_check($logical_facts->decision->operation === operator_operation::condition_or
		&& count($logical_facts->decision->operand_truthiness) === 2
		&& count($logical_facts->decision->operands) === 0 && !$logical_facts->addressable(),
		'Logical facts lost truthiness or added boolean conversions');
	Preparation_Cleanup::tree($logical_root);
	mixed_check($logical_node->preparation() === null, 'Logical operand facts survived cleanup');
	mixed_source($path, '$v mixed = 7; $text string = "x" . $v; $boxed mixed = $v . null;');
	$text_root = Model::syntax_files()[0]->root;
	$text_node = $text_root->single_body()->body->statements[1]->initializer;
	$text_facts = $text_node->require_binary_preparation();
	mixed_check($text_facts->decision->operation === operator_operation::string_concatenation
		&& $text_facts->decision->operands[1]->operation === conversion_operation::text_conversion
		&& String_Conversions::is_string($text_facts->type) && !$text_facts->addressable(),
		'Concatenation lost its text conversion or owned string result');
	mixed_check($text_root->single_body()->body->statements[2]->require_preparation()->conversion->operation
		=== conversion_operation::boxed_wrap, 'Mixed destination changed concatenation result type');
	Preparation_Cleanup::tree($text_root);
	mixed_check($text_node->preparation() === null, 'Text conversion survived cleanup');
	mixed_source($path, '$v mixed = 42; $out mixed = ($v .= "!"); $s string = "x"; $copy string = ($s .= $v);');
	$append_root = Model::syntax_files()[0]->root;
	foreach ([1 => conversion_operation::boxed_wrap, 3 => conversion_operation::identity] as $position => $write_back)
	{
		$append_node = $append_root->single_body()->body->statements[$position]->initializer;
		$append_facts = $append_node->require_compound_assignment_preparation();
		mixed_check($append_facts->write_back_mode === compound_write_back::converted_assignment
			&& $append_facts->write_back->operation === $write_back
			&& String_Conversions::is_string($append_facts->decision->result_type)
			&& !$append_facts->addressable(), 'Text update lost replacement semantics or owned snapshot');
	}
	Preparation_Cleanup::tree($append_root);
	mixed_check($append_root->single_body()->body->statements[1]->initializer->preparation() === null,
		'Text update facts survived cleanup');
	mixed_source($path, '$v mixed = 7; $after mixed = ($v += 1); $before mixed = $v++;');
	$update_root = Model::syntax_files()[0]->root;
	$compound_node = $update_root->single_body()->body->statements[1]->initializer;
	$mutation_node = $update_root->single_body()->body->statements[2]->initializer;
	$update_facts = $compound_node->require_compound_assignment_preparation();
	mixed_check($update_facts->write_back_mode === compound_write_back::boxed_stored_kind
		&& $update_facts->decision->operation === operator_operation::boxed_addition
		&& $update_facts->write_back->operation === conversion_operation::identity
		&& !$update_facts->addressable(), 'Mixed update lost its guarded storage write-back or owned snapshot');
	mixed_check($mutation_node->require_mutation_preparation()->decision->operation === operator_operation::boxed_post_increment
		&& !$mutation_node->require_preparation()->addressable(), 'Mixed postfix mutation lost its old-value snapshot');
	mixed_check(str_contains(CPP_Probe::text(Model::$cpp_files), 'scpp::detail::assign_numeric_result('),
		'Mixed compound emission bypassed stored-kind validation');
	Preparation_Cleanup::tree($update_root);
	mixed_check($compound_node->preparation() === null && $mutation_node->preparation() === null,
		'Mixed update retained stale facts');
	mixed_source($path, '$v mixed = null; $a bool = $v === null; $b bool = null !== $v; '
		. '$c bool = $v == null; $d bool = $v < 1.0;');
	$comparison_root = Model::syntax_files()[0]->root;
	foreach ([1, 2, 3, 4] as $position)
	{
		$comparison_node = $comparison_root->single_body()->body->statements[$position]->initializer;
		$decision = $comparison_node->require_binary_preparation()->decision;
		mixed_check($decision->result_type->matches(Language_Types::boolean(Model::$language_scope))
			&& !$comparison_node->require_preparation()->addressable(), 'Comparison lost its owned bool result');
		if ($position < 3) {
			$null_operand = $decision->operands[$position === 1 ? 1 : 0];
			mixed_check($null_operand->operation === conversion_operation::identity
				&& Nullable_Conversions::is_null($null_operand->target_type), 'Strict identity boxed literal null');
		}
		else {
			mixed_check($decision->operands[1]->operation === conversion_operation::boxed_wrap,
				'Ordinary comparison did not normalize its peer');
		}
	}
	Preparation_Cleanup::tree($comparison_root);
	mixed_check($comparison_root->single_body()->body->statements[1]->initializer->preparation() === null,
		'Mixed comparison retained stale facts');
	mixed_source($path, '$d dynamic; $m mixed = (mixed)$d; $again dynamic = (dynamic)$m;');
	$bridge_root = Model::syntax_files()[0]->root;
	mixed_check($bridge_root->single_body()->body->statements[1]->initializer->require_cast_preparation()->conversion->operation
		=== conversion_operation::explicit_runtime_cast, 'Missing checked scalar/null family cast fact');
	Preparation_Cleanup::tree($bridge_root);
	mixed_check($bridge_root->single_body()->body->statements[1]->initializer->preparation() === null,
		'Dynamic cast retained stale facts');

	mixed_source($path, '$v mixed = "false"; if ($v) {} $n int = (int)$v; '
		. '$b bool = !$v; $p bool = isset($v); $e bool = empty($v);');
	$probe_root = Model::syntax_files()[0]->root;
	mixed_check($probe_root->single_body()->body->statements[1]->require_preparation()->truthiness->operation
		=== truthiness_operation::boxed_condition, 'Missing mixed condition fact');
	mixed_check(str_contains(CPP_Probe::text(Model::$cpp_files), 'scpp::cast<scpp::int_t<>>'),
		'Explicit mixed cast did not use its runtime owner');
	$probe_nodes = [];
	foreach ([2, 3, 4, 5] as $position) {
		$probe_nodes[] = $probe_root->single_body()->body->statements[$position]->initializer;
	}
	Preparation_Cleanup::tree($probe_root);
	foreach ($probe_nodes as $probe_node) {
		mixed_check($probe_node->preparation() === null, 'Mixed operation retained stale facts');
	}

	$rejections = [
		['$v int = 7; $x = (dynamic)$v;', 'explicit cast target is not supported'],
		['$v int = 7; $x = (mixed)$v;', 'explicit cast target is not supported'],
		['$n uint8 = 7; $x mixed = $n;', 'boxed wrapping currently supports'],
		['$x mixed = 7; $n uint8 = $x;', 'boxed extraction currently supports'],
		['$v ?int = 7; $x mixed = $v;', 'boxed wrapping currently supports'],
		['$v result<int> = 7; $x mixed = $v;', 'Result extraction requires the exact payload'],
		['$x mixed = 7; $v ?int = $x;', 'boxed extraction currently supports'],
		['$x mixed = 7; $v result<int> = $x;', 'Result construction requires an exact payload'],
		['$d dynamic; $x mixed = $d;', 'family conversion requires an explicit scalar/null cast'],
		['$x mixed = 7; $d dynamic = $x;', 'family conversion requires an explicit scalar/null cast'],
		['$d dynamic; $x mixed = []; $x["child"] = $d;', 'family conversion requires an explicit scalar/null cast'],
		['$d dynamic; $x mixed = [$d];', 'family conversion requires an explicit scalar/null cast'],
		['$x mixed = 7; $y = (uint8)$x;', 'explicit boxed casts require'],
		['$x mixed = 7; $y = $x + true;', 'boxed arithmetic requires'],
		['$x mixed = 7; $y = $x + "1";', 'boxed arithmetic requires'],
		['$x mixed = 7; $n uint8 = 1; $y = $x + $n;', 'boxed arithmetic requires'],
		['$x mixed = 7; $n ?int = 1; $y = $x + $n;', 'boxed arithmetic requires'],
		['$x mixed = 7; $y = $x % 1.5;', 'boxed remainder requires'],
		['$x mixed = 7; $y = 1.5 % $x;', 'boxed remainder requires'],
		['$x mixed = 7; $y = $x ** 2;', 'boxed operator is outside'],
		['$x mixed = 7; $y = $x <=> 2;', 'boxed operator is outside'],
		['$x mixed = 7; $y = $x < "2";', 'boxed ordering requires'],
		['$x mixed = 7; $y = $x > null;', 'boxed ordering requires'],
		['$x mixed = 7; $n uint8 = 2; $y = $x === $n;', 'boxed equality requires'],
		['$x mixed = 7; $n ?int = 2; $y = $x == $n;', 'boxed equality requires'],
		['$x mixed = 7; $d dynamic; $y = $x === $d;', 'boxed equality requires'],
		['$x mixed = 7; $y = ~$x;', 'boxed operator is outside'],
		['$x mixed = 7; $x += true;', 'boxed arithmetic requires'],
		['$x mixed = 7; $n int = 1; $n += $x;', 'boxed compound assignment into native storage is deferred'],
		['$x mixed = 7; $x &= 1;', 'boxed operator is outside'],
		['$x mixed = 7; $n result<int> = 1; $x .= $n;', 'concatenation requires boxed'],
		['$x mixed = 7; $d dynamic; $x .= $d;', 'same boxed family'],
		['$x = false; $x .= "x";', 'compound concatenation requires string or boxed storage'],
		['$x = 1.5; $x .= "x";', 'compound concatenation requires string or boxed storage'],
		['$x mixed = 7; $n result<int> = 1; $y = $x . $n;', 'concatenation requires boxed'],
		['$x mixed = 7; $n uint8 = 1; $y = $x . $n;', 'concatenation requires boxed'],
		['$x mixed = 7; $x %= 1.5;', 'boxed remainder requires'],
		['$x mixed = 7; $d dynamic; $y = $x + $d;', 'family conversion requires'],
		['$x mixed = 7; $y = ($x xor true);', 'supports only AND and OR'],
		['$x mixed = 7; $y = $x && "yes";', 'boxed logical operands require'],
		['$x mixed = 7; $n ?int = 1; $y = $n || $x;', 'boxed logical operands require'],
		['$x mixed = 7; $n result<int> = 1; $y = $x && $n;', 'boxed logical operands require'],
		['$x mixed = 7; $d dynamic; $y = $d || $x;', 'same boxed family'],
		['$x mixed = 7; $y = $x ?: null;', 'boxed selection requires'],
		['$x mixed = 7; $y = null ?? $x;', 'boxed selection requires'],
		['$x mixed = 7; $y = $x ?? null;', 'boxed selection requires'],
		['$x mixed = 7; $y = true ? null : $x;', 'boxed selection requires'],
		['$x mixed = 7; $n uint8 = 1; $y = true ? $x : $n;', 'boxed selection requires'],
		['$x mixed = 7; $small uint8 = 1; $n ?uint8 = $small; $y = $n ?? $x;', 'boxed selection requires'],
		['$x mixed = 7; $n result<int> = 1; $y = $x ?? $n;', 'Result extraction supports only required'],
		['$x mixed = 7; $n result<int> = 1; $y = $n ?: $x;', 'conditional Result branches require'],
		['$x mixed = 7; $n result<int> = 1; $y = true ? $x : $n;', 'conditional Result branches require'],
		['$x mixed = 7; $n result_or_bool<int> = 1; $y = $x ?? $n;', 'does not permit result_or_bool'],
		['$x mixed = 7; $n result_or_bool<int> = 1; $y = $n ?? $x;', 'does not permit result_or_bool'],
		['$x mixed = 7; $y = true ? $x : $missing;', 'S2S'],
		['function consume(mixed &$v): void {} $x mixed = 7; consume($x ?? 1);', 'reference'],
		['$x mixed = 7; $y = empty($x->field);', 'property-path probes'],
		['$y = isset($missing);', 'S2S'],
		['!($new = true);', 'S2S'],
		['function change(int &$x): void {} $x mixed = 7; change($x);', 'reference'],
		['function change(mixed &$x): void {} $x = 7; change($x);', 'reference'],
	];
	foreach ($rejections as [$source, $message])
	{
		$failed = false;
		$actual_message = 'accepted';
		try {
			mixed_source($path, $source);
		}
		catch (\RuntimeException $error) {
			$actual_message = $error->getMessage();
			$failed = str_contains($error->getMessage(), $message);
		}
		mixed_check($failed, 'Missing expected mixed rejection: ' . $source . '; got: ' . $actual_message);
		mixed_check(Model::$cpp_files->is_empty(), 'Rejected mixed boundary published output');
	}

	// Chapter 06 admits keyed probes on mixed; the payload kind is checked at runtime.
	mixed_source($path, '$x mixed = 7; $y = isset($x[0]);');
	mixed_check(!Model::$cpp_files->is_empty(), 'Mixed keyed probe admission regressed');
	mixed_source($path, '$d dynamic; $y = empty($d); $z = isset($d);');
	mixed_check(!Model::$cpp_files->is_empty(), 'Dynamic value probe admission regressed');

	// Payload incompatibility is runtime-dependent, not a preparation rejection.
	mixed_source($path, '$x mixed = "not an integer"; $y int = $x;');
	mixed_check(!Model::$cpp_files->is_empty(), 'Runtime kind failure became a static rejection');
	$valid = 'function source(): mixed { return 3; } function read(): int { return source(); } '
		. 'function stable(): int { return 9; } $v = read();';
	$compiler = mixed_source($path, $valid);
	$original = CPP_Probe::text(Model::$cpp_files);
	$stable = Model::$global_scope->functions_named('stable')[0]->syntax()->body->work();
	$version = $stable->version;
	file_put_contents($path, str_replace('source(): mixed { return 3;', 'source(): vector<int> { return [];', $valid));
	foreach ([[$path], []] as $changed)
	{
		$compiler->sync($changed);
		$failed = false;
		try {
			$compiler->prepare();
		}
		catch (\RuntimeException $error) {
			$failed = true;
		}
		mixed_check($failed && Model::$cpp_files->is_empty(), 'Mixed boundary change published stale output');
		mixed_check($stable->version === $version, 'Mixed boundary change rebuilt an unrelated body');
	}
	file_put_contents($path, $valid);
	$compiler->update_cpp([$path]);
	mixed_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Mixed boundary repair changed output');
	mixed_source($path, $valid);
	mixed_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Clean/incremental mixed output differs');
	foreach (['if (source()) { return 1; } return 0;',
		'return !source() ? 1 : 0;',
		'return (source() && true) ? 1 : 0;', 'return (false || source()) ? 1 : 0;',
		'return source() ?? 1;',
		'return source() ?: 1;', 'return true ? source() : 1;',
		'return (int)(mixed)(dynamic)source();',
		'$v mixed = source(); return $v + 1;', 'return -source();',
		'$v = source(); return $v == 1 ? 1 : 0;', '$v = source(); return $v < 1 ? 1 : 0;',
		'return source() === null ? 1 : 0;', '$v = source(); $v += 1; return (int)$v;',
		'$v = source(); return ++$v;', '$v = source(); return (int)($v . "0");',
		'$v = source(); $v .= "0"; return (int)$v;', '$v = source(); $s = "1"; $s .= $v; return (int)$s;'] as $consumer)
	{
		$valid = 'function source(): mixed { return 3; } function read(): int { '
			. $consumer . ' } function stable(): int { return 9; } $v = read();';
		$compiler = mixed_source($path, $valid);
		$original = CPP_Probe::text(Model::$cpp_files);
		$stable = Model::$global_scope->functions_named('stable')[0]->syntax()->body->work();
		$version = $stable->version;
		file_put_contents($path, str_replace('source(): mixed { return 3;',
			'source(): vector<int> { return [];', $valid));
		foreach ([[$path], []] as $changed)
		{
			$compiler->sync($changed);
			$failed = false;
			try { $compiler->prepare(); }
			catch (\RuntimeException $error) { $failed = true; }
			mixed_check($failed && Model::$cpp_files->is_empty(), 'Mixed operation published stale output');
			mixed_check($stable->version === $version, 'Mixed operation rebuilt unrelated body');
		}
		file_put_contents($path, $valid);
		$compiler->update_cpp([$path]);
		mixed_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Mixed operation repair changed output');
		mixed_source($path, $valid);
		mixed_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Clean/incremental mixed operation differs');
	}
	// Probe consumers now accept both families; signature changes must still refresh output.
	foreach (['isset', 'empty'] as $probe_name) {
		$valid = 'function source(): mixed { return 3; } function read(): bool { return '
			. $probe_name . '(source()); }';
		$compiler = mixed_source($path, $valid);
		$original = CPP_Probe::text(Model::$cpp_files);
		$changed_source = str_replace('source(): mixed { return 3;',
			'source(): dynamic { $d dynamic; return $d;', $valid);
		file_put_contents($path, $changed_source);
		$compiler->update_cpp([$path]);
		$changed_output = CPP_Probe::text(Model::$cpp_files);
		mixed_check($changed_output !== $original, 'Probe signature change retained stale output');
		mixed_source($path, $changed_source);
		mixed_check(CPP_Probe::text(Model::$cpp_files) === $changed_output, 'Probe clean/incremental output differs');
	}
	mixed_source($path, '$v mixed = null; $a mixed = true ? $v : 1; '
		. '$b mixed = $v ?: 2; $c mixed = $v ?? 3; $d mixed = 4 ?? $v;');
	$selection_root = Model::syntax_files()[0]->root;
	foreach ([1, 2, 3, 4] as $position)
	{
		$selection_node = $selection_root->single_body()->body->statements[$position]->initializer;
		$facts = $selection_node->require_preparation();
		mixed_check(Boxed_Conversions::is_mixed($facts->type) && !$facts->addressable(),
			'Selection lost its owned mixed result');
		if ($position < 3) {
			mixed_check($facts->true_conversion->operation === conversion_operation::identity
				&& $facts->fallback_conversion->operation === conversion_operation::boxed_wrap, 'Incorrect conditional normalization');
		}
		else {
			mixed_check($facts->presence === ($position === 3 ? coalesce_presence::boxed_nonnull : coalesce_presence::always),
				'Coalesce presence was inferred from conversion identity');
		}
	}
	Preparation_Cleanup::tree($selection_root);
	foreach ([1, 2, 3, 4] as $position) {
		$selection_node = $selection_root->single_body()->body->statements[$position]->initializer;
		mixed_check($selection_node->preparation() === null, 'Selection retained stale facts');
	}
	foreach (['?int', 'result<int>', 'result_or_false<int>'] as $wrapper)
	{
		mixed_source($path, '$v ' . $wrapper . ' = 0; $fallback mixed = null; $out mixed = $v ?? $fallback;');
		$wrapper_root = Model::syntax_files()[0]->root;
		$coalesce = $wrapper_root->single_body()->body->statements[2]->initializer;
		$facts = $coalesce->require_coalesce_preparation();
		mixed_check($facts->presence === coalesce_presence::wrapper_value
			&& $facts->left_extraction->operation === ($wrapper === '?int' ? conversion_operation::nullable_extract : conversion_operation::result_extract)
			&& $facts->left_conversion->operation === conversion_operation::boxed_wrap
			&& $facts->fallback_conversion->operation === conversion_operation::identity,
			'Wrapper coalescing did not separate extraction from normalization');
		mixed_check($facts->left_extraction->target_type->matches($facts->left_conversion->source_type)
			&& Boxed_Conversions::is_mixed($facts->type) && !$facts->addressable(), 'Invalid selected-value composition');
		Preparation_Cleanup::tree($wrapper_root);
		mixed_check($coalesce->preparation() === null && $coalesce->left->preparation() === null
			&& $coalesce->fallback->preparation() === null, 'Wrapper coalesce facts survived cleanup');

		$valid = 'function source(): ' . $wrapper . ' { return 3; } '
			. 'function read(): mixed { $fallback mixed = null; return source() ?? $fallback; } '
			. 'function stable(): int { return 9; } $v = read();';
		$compiler = mixed_source($path, $valid);
		$original = CPP_Probe::text(Model::$cpp_files);
		$stable = Model::$global_scope->functions_named('stable')[0]->syntax()->body->work();
		$version = $stable->version;
		file_put_contents($path, str_replace('source(): ' . $wrapper, 'source(): result_or_bool<int>', $valid));
		foreach ([[$path], []] as $changed)
		{
			$compiler->sync($changed);
			$failed = false;
			try { $compiler->prepare(); }
			catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'does not permit result_or_bool'); }
			mixed_check($failed && Model::$cpp_files->is_empty(), 'Wrapper join published stale output');
			mixed_check($stable->version === $version, 'Wrapper join rebuilt unrelated body');
		}
		file_put_contents($path, $valid);
		$compiler->update_cpp([$path]);
		mixed_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Wrapper join repair changed output');
		mixed_source($path, $valid);
		mixed_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Clean/incremental wrapper join differs');
	}
	echo "Mixed: boundaries, casts, conditions/probes/joins, deferred cases and incremental recovery passed\n";
}
finally
{
	unlink($path);
	rmdir($directory);
}
