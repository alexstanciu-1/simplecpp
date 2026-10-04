<?php

/* Prepared mixed boundaries, deferred combinations and incremental failure/recovery. */
namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';

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
	foreach ([conversion_operation::mixed_box, conversion_operation::mixed_box,
		conversion_operation::identity, conversion_operation::mixed_extract] as $position => $operation)
	{
		$facts = $root->body->statements[$position]->require_preparation();
		mixed_check($facts->conversion->operation === $operation, 'Wrong prepared mixed operation');
		mixed_check($facts->conversion->result_type->matches($facts->type), 'Mixed boundary lost target identity');
	}
	mixed_check(str_contains(Model::$cpp_files[0]->text, 'scpp::required_cast<scpp::int_t<>>'),
		'Required extraction became an implicit or explicit cast');
	Preparation_Cleanup::tree($root);
	mixed_check($root->body->statements[3]->preparation() === null, 'Mixed conversion facts survived cleanup');

	$registry = Model::$type_catalog->registry();
	$renamed = $registry->define_mixed('OtherBox', type_definition_origin::runtime,
		[generic_contract::value_storable, generic_contract::copyable_value]);
	$box = $registry->use($registry->canonical($renamed)->type_id());
	$integer = Language_Types::integer(Model::$language_scope);
	mixed_check(Conversion_Preparation::decide($integer, $box, conversion_context::assignment)->operation
		=== conversion_operation::mixed_box, 'Mixed boxing depended on source spelling');
	mixed_check(Conversion_Preparation::decide($box, $integer, conversion_context::return_value)->operation
		=== conversion_operation::mixed_extract, 'Mixed extraction depended on source spelling');
	mixed_check(Conversion_Preparation::decide($box, $integer, conversion_context::explicit_cast)->operation
		=== conversion_operation::explicit_runtime_cast, 'Explicit mixed cast reused required extraction');
	mixed_check(Condition_Truthiness::decide($box)->operation === truthiness_operation::mixed_condition,
		'Mixed truthiness depended on source spelling');

	mixed_source($path, '$v mixed = "false"; if ($v) {} $n int = (int)$v; '
		. '$b bool = !$v; $p bool = isset($v); $e bool = empty($v);');
	$probe_root = Model::syntax_files()[0]->root;
	mixed_check($probe_root->body->statements[1]->require_preparation()->truthiness->operation
		=== truthiness_operation::mixed_condition, 'Missing mixed condition fact');
	mixed_check(str_contains(Model::$cpp_files[0]->text, 'scpp::cast<scpp::int_t<>>'),
		'Explicit mixed cast did not use its runtime owner');
	$probe_nodes = [];
	foreach ([2, 3, 4, 5] as $position) {
		$probe_nodes[] = $probe_root->body->statements[$position]->initializer;
	}
	Preparation_Cleanup::tree($probe_root);
	foreach ($probe_nodes as $probe_node) {
		mixed_check($probe_node->preparation() === null, 'Mixed operation retained stale facts');
	}

	$rejections = [
		['$n uint8 = 7; $x mixed = $n;', 'mixed boxing currently supports'],
		['$x mixed = 7; $n uint8 = $x;', 'mixed extraction currently supports'],
		['$v ?int = 7; $x mixed = $v;', 'mixed boxing currently supports'],
		['$v result<int> = 7; $x mixed = $v;', 'mixed boxing currently supports'],
		['$x mixed = 7; $v ?int = $x;', 'mixed extraction currently supports'],
		['$x mixed = 7; $v result<int> = $x;', 'mixed extraction currently supports'],
		['$d dynamic; $x mixed = $d;', 'mixed boxing currently supports'],
		['$x mixed = 7; $d dynamic = $x;', 'mixed extraction currently supports'],
		['$x mixed = 7; $y = (uint8)$x;', 'explicit mixed casts require'],
		['$x mixed = 7; $y = $x + 1;', 'S2S'],
		['$x mixed = 7; $y = $x && true;', 'S2S'],
		['$x mixed = 7; $y = $x ?: null;', 'mixed selection requires'],
		['$x mixed = 7; $y = null ?? $x;', 'mixed selection requires'],
		['$x mixed = 7; $y = $x ?? null;', 'mixed selection requires'],
		['$x mixed = 7; $y = true ? null : $x;', 'mixed selection requires'],
		['$x mixed = 7; $n uint8 = 1; $y = true ? $x : $n;', 'mixed selection requires'],
		['$x mixed = 7; $small uint8 = 1; $n ?uint8 = $small; $y = $n ?? $x;', 'mixed selection requires'],
		['$x mixed = 7; $n result<int> = 1; $y = $x ?? $n;', 'mixed selection requires'],
		['$x mixed = 7; $n result<int> = 1; $y = $n ?: $x;', 'mixed selection requires'],
		['$x mixed = 7; $n result<int> = 1; $y = true ? $x : $n;', 'mixed selection requires'],
		['$x mixed = 7; $n result_or_bool<int> = 1; $y = $x ?? $n;', 'does not permit result_or_bool'],
		['$x mixed = 7; $n result_or_bool<int> = 1; $y = $n ?? $x;', 'does not permit result_or_bool'],
		['$x mixed = 7; $y = true ? $x : $missing;', 'S2S'],
		['function consume(mixed &$v): void {} $x mixed = 7; consume($x ?? 1);', 'reference'],
		['$x mixed = 7; $y = isset($x[0]);', 'keyed/property-path probes'],
		['$x mixed = 7; $y = empty($x->field);', 'keyed/property-path probes'],
		['$y = isset($missing);', 'S2S'],
		['$d dynamic; if ($d) {}', 'condition truthiness'],
		['$d dynamic; $y = empty($d);', 'value probes require'],
		['function source(): mixed { return 1; } $y = !source() === false;', 'order-independent operands'],
		['!($new = true);', 'S2S'],
		['function change(int &$x): void {} $x mixed = 7; change($x);', 'reference'],
		['function change(mixed &$x): void {} $x = 7; change($x);', 'reference'],
	];
	foreach ($rejections as [$source, $message])
	{
		$failed = false;
		try {
			mixed_source($path, $source);
		}
		catch (\RuntimeException $error) {
			$failed = str_contains($error->getMessage(), $message);
		}
		mixed_check($failed, 'Missing expected mixed rejection: ' . $source);
		mixed_check(Model::$cpp_files->is_empty(), 'Rejected mixed boundary published output');
	}

	// Payload incompatibility is runtime-dependent, not a preparation rejection.
	mixed_source($path, '$x mixed = "not an integer"; $y int = $x;');
	mixed_check(!Model::$cpp_files->is_empty(), 'Runtime kind failure became a static rejection');
	$valid = 'function source(): mixed { return 3; } function read(): int { return source(); } '
		. 'function stable(): int { return 9; } $v = read();';
	$compiler = mixed_source($path, $valid);
	$original = Model::$cpp_files[0]->text;
	$stable = Model::$global_scope->functions_named('stable')[0]->syntax()->body->work();
	$version = $stable->version;
	file_put_contents($path, str_replace('source(): mixed { return 3;', 'source(): dynamic { $d dynamic; return $d;', $valid));
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
	mixed_check(Model::$cpp_files[0]->text === $original, 'Mixed boundary repair changed output');
	mixed_source($path, $valid);
	mixed_check(Model::$cpp_files[0]->text === $original, 'Clean/incremental mixed output differs');
	foreach (['return (int)source();', 'if (source()) { return 1; } return 0;',
		'return !source() ? 1 : 0;', 'return isset(source()) ? 1 : 0;',
		'return empty(source()) ? 1 : 0;', 'return source() ?? 1;',
		'return source() ?: 1;', 'return true ? source() : 1;'] as $consumer)
	{
		$valid = 'function source(): mixed { return 3; } function read(): int { '
			. $consumer . ' } function stable(): int { return 9; } $v = read();';
		$compiler = mixed_source($path, $valid);
		$original = Model::$cpp_files[0]->text;
		$stable = Model::$global_scope->functions_named('stable')[0]->syntax()->body->work();
		$version = $stable->version;
		file_put_contents($path, str_replace('source(): mixed { return 3;',
			'source(): dynamic { $d dynamic; return $d;', $valid));
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
		mixed_check(Model::$cpp_files[0]->text === $original, 'Mixed operation repair changed output');
		mixed_source($path, $valid);
		mixed_check(Model::$cpp_files[0]->text === $original, 'Clean/incremental mixed operation differs');
	}
	mixed_source($path, '$v mixed = null; $a mixed = true ? $v : 1; '
		. '$b mixed = $v ?: 2; $c mixed = $v ?? 3; $d mixed = 4 ?? $v;');
	$selection_root = Model::syntax_files()[0]->root;
	foreach ([1, 2, 3, 4] as $position)
	{
		$selection_node = $selection_root->body->statements[$position]->initializer;
		$facts = $selection_node->require_preparation();
		mixed_check(Mixed_Conversions::is_mixed($facts->type) && !$facts->addressable,
			'Selection lost its owned mixed result');
		if ($position < 3) {
			mixed_check($facts->true_conversion->operation === conversion_operation::identity
				&& $facts->fallback_conversion->operation === conversion_operation::mixed_box, 'Incorrect conditional normalization');
		}
		else {
			mixed_check($facts->presence === ($position === 3 ? coalesce_presence::mixed_nonnull : coalesce_presence::always),
				'Coalesce presence was inferred from conversion identity');
		}
	}
	Preparation_Cleanup::tree($selection_root);
	foreach ([1, 2, 3, 4] as $position) {
		$selection_node = $selection_root->body->statements[$position]->initializer;
		mixed_check($selection_node->preparation() === null, 'Selection retained stale facts');
	}
	foreach (['?int', 'result<int>', 'result_or_false<int>'] as $wrapper)
	{
		mixed_source($path, '$v ' . $wrapper . ' = 0; $fallback mixed = null; $out mixed = $v ?? $fallback;');
		$wrapper_root = Model::syntax_files()[0]->root;
		$coalesce = $wrapper_root->body->statements[2]->initializer;
		$facts = $coalesce->require_coalesce_preparation();
		mixed_check($facts->presence === coalesce_presence::wrapper_value
			&& $facts->left_extraction->operation === ($wrapper === '?int' ? conversion_operation::nullable_extract : conversion_operation::result_extract)
			&& $facts->left_conversion->operation === conversion_operation::mixed_box
			&& $facts->fallback_conversion->operation === conversion_operation::identity,
			'Wrapper coalescing did not separate extraction from normalization');
		mixed_check($facts->left_extraction->target_type->matches($facts->left_conversion->source_type)
			&& Mixed_Conversions::is_mixed($facts->type) && !$facts->addressable, 'Invalid selected-value composition');
		Preparation_Cleanup::tree($wrapper_root);
		mixed_check($coalesce->preparation() === null && $coalesce->left->preparation() === null
			&& $coalesce->fallback->preparation() === null, 'Wrapper coalesce facts survived cleanup');

		$valid = 'function source(): ' . $wrapper . ' { return 3; } '
			. 'function read(): mixed { $fallback mixed = null; return source() ?? $fallback; } '
			. 'function stable(): int { return 9; } $v = read();';
		$compiler = mixed_source($path, $valid);
		$original = Model::$cpp_files[0]->text;
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
		mixed_check(Model::$cpp_files[0]->text === $original, 'Wrapper join repair changed output');
		mixed_source($path, $valid);
		mixed_check(Model::$cpp_files[0]->text === $original, 'Clean/incremental wrapper join differs');
	}
	echo "Mixed: boundaries, casts, conditions/probes/joins, deferred cases and incremental recovery passed\n";
}
finally
{
	unlink($path);
	rmdir($directory);
}
