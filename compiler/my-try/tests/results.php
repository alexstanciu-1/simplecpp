<?php

/* Prepared construction decisions, deliberate exclusions and incremental recovery. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function result_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Use the normal retained compiler path for each independent source contract. */
function result_source(string $path, string $source): Compiler
{
	file_put_contents($path, $source);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([dirname($path)]);
	$compiler->exec_cpp();
	return $compiler;
}

$directory = sys_get_temp_dir() . '/scpp_results_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
try
{
	$compiler = result_source($path, '$a result_or_bool<bool> = false; $b result_or_bool<bool> = false_sentinel; '
		. '$c result_or_bool<bool> = true_sentinel; $d result_or_bool<bool> = null; $e result_or_bool<bool> = nullopt; '
		. '$f result_or_bool<bool> = $c;');
	$root = Model::syntax_files()[0]->root;
	$expected = [conversion_operation::result_wrap, conversion_operation::result_false,
		conversion_operation::result_true, conversion_operation::result_false,
		conversion_operation::result_false, conversion_operation::identity];
	foreach ($expected as $position => $operation)
	{
		$facts = $root->single_body()->body->statements[$position]->require_preparation();
		result_check($facts->conversion->operation === $operation, 'Wrong prepared Result state');
		result_check($facts->conversion->result_type->matches($facts->type), 'Result conversion lost destination identity');
	}
	$bool_type = Language_Types::boolean(Model::$language_scope);
	$catalog = Model::$type_catalog;
	foreach ([false, true] as $value)
	{
		$marker = $catalog->boolean_sentinel_type($value);
		result_check(!$marker->matches($bool_type), 'Sentinel type was conflated with boolean payload');
		result_check(Type_Queries::canonical($marker)->definition()->capabilities() === [],
			'Sentinel tag acquired value-storage capabilities');
	}
	result_check(!$catalog->boolean_sentinel_type(false)->matches($catalog->boolean_sentinel_type(true)),
		'False and true sentinel identities were merged');

	// A renamed Result definition retains its role; an ordinary similarly named template does not gain it.
	$registry = $catalog->registry();
	$parameters = [new template_type_parameter('Value', [generic_contract::value_storable])];
	$renamed = $registry->define_result('RenamedResult', type_definition_origin::runtime,
		nominal_type_kind::class_type, $parameters, [generic_contract::value_storable], result_kind::boolean_sentinels);
	$application = $registry->intern_application($renamed, [$bool_type]);
	$target = $registry->use($application->type_id());
	result_check(Condition_Truthiness::decide($target)->operation === truthiness_operation::result_condition,
		'Result condition admission depends on spelling');
	result_check(Conversion_Preparation::decide($bool_type, $target, conversion_context::assignment)->operation
		=== conversion_operation::result_wrap, 'Construction policy depends on Result spelling');
	result_check(Conversion_Preparation::decide($catalog->boolean_sentinel_type(true), $target,
		conversion_context::argument)->operation === conversion_operation::result_true, 'Sentinel policy ignored the role');
	$ordinary = $registry->define_template('result', type_definition_origin::source,
		nominal_type_kind::class_type, $parameters, [generic_contract::value_storable]);
	$ordinary_type = $registry->intern_application($ordinary, [$bool_type]);
	result_check(Result_Conversions::role($registry->use($ordinary_type->type_id())) === null,
		'Ordinary template spelling granted Result semantics');
	Preparation_Cleanup::tree($root);
	result_check($root->single_body()->body->statements[1]->initializer->preparation() === null,
		'Cleanup retained sentinel reference facts');

	result_source($path, '$r result<int> = 7; $v int = $r;');
	$extraction = Model::syntax_files()[0]->root->single_body()->body->statements[1];
	result_check($extraction->require_preparation()->conversion->operation === conversion_operation::result_extract,
		'Required value boundary lost its checked extraction decision');
	Preparation_Cleanup::tree(Model::syntax_files()[0]->root);
	result_check($extraction->preparation() === null && $extraction->initializer->preparation() === null,
		'Cleanup retained extraction facts');

	$rejections = [
		['$x = null;', 'explicit nullable, mixed or legacy Result destination'],
		['$x = nullopt;', 'explicit nullable, mixed or legacy Result destination'],
		['$x result<int> = null;', 'Result construction requires an exact payload'],
		['$x result<int> = false_sentinel;', 'Result construction requires an exact payload'],
		['$x result<int> = true_sentinel;', 'Result construction requires an exact payload'],
		['$x result_or_false<int> = true_sentinel;', 'Result construction requires an exact payload'],
		['$x result_or_false<int> = false;', 'Result construction requires an exact payload'],
		['$x result_or_bool<int> = true;', 'Result construction requires an exact payload'],
		['$x result<float> = 1;', 'Result construction requires an exact payload'],
		['$a result<int> = 1; $b result_or_false<int> = $a;', 'Result construction requires an exact payload'],
		['$a result<int> = 1; $b result<float> = $a;', 'Result construction requires an exact payload'],
		['$a ?int = 1; $b result<int> = $a;', 'Result construction requires an exact payload'],
		['$a result<int> = 1; $b float = $a;', 'Result extraction requires the exact payload type'],
		['$a result<int> = 1; $b int32 = $a;', 'Result extraction requires the exact payload type'],
		['$a result<int> = 1; $b = (int) $a;', 'Result extraction supports only required'],
		['$a result<int> = 1; $b ?int = $a;', 'Result extraction requires the exact payload type'],
		['function ref(int &$v): void {} $a result<int> = 1; ref($a);', 'reference'],
		['$a result<int> = 1; $b = $a ?? 2.0;', 'coalesce branches require matching scalar'],
		['$a result<int> = 1; $b = $a ?? $a;', 'coalesce branches require matching scalar'],
		['$a result<int> = 1; $b = $a ?? null;', 'coalesce branches require matching scalar'],
		['$a result_or_bool<int> = 1; $b = $a ?? 2;', 'coalesce does not permit result_or_bool'],
		['$a result_or_bool<int> = 1; $b = 2 ?? $a;', 'coalesce does not permit result_or_bool'],
		['$a result<int> = 1; $b result_or_bool<int> = 2; $c = $a ?? $b;', 'coalesce does not permit result_or_bool'],
		['$a result<int> = 1; $b = !$a;', 'unary operation requires'],
		['$a result<int> = 1; $b = $a ?: 2.0;', 'conditional Result branches require'],
		['$a result<int> = 1; $b result_or_false<int> = 2; $c = true ? $a : $b;', 'conditional Result branches require'],
		['$a result<int> = 1; $b ?int = 2; $c = true ? $a : $b;', 'conditional Result branches require'],
		['$a result<int> = 1; $b = true ? null : $a;', 'conditional Result branches require'],
		['$a result_or_bool<int> = 1; $b = true ? $a : true_sentinel;', 'conditional Result branches require'],
		['function f(result<vector<int>> $r): bool { return isset($r); }', 'unmodified scalar, mixed, dynamic or explicit callable payload'],
		['function f(result<vector<int>> $r): bool { if ($r) { return true; } return false; }', 'unmodified scalar, mixed, dynamic or explicit callable payload'],
		['$x = false_sentinel;', 'sentinel requires an explicit Result destination'],
		['$x = true_sentinel;', 'sentinel requires an explicit Result destination'],
		['$x result<int>;', 'Result construction requires an explicit initializer'],
		['$x result<result<int>>;', 'Result operations require an unmodified scalar, mixed, dynamic or explicit callable payload'],
		['function ref(result<int> &$v): void {} ref(1);', 'reference'],
	];
	foreach ($rejections as [$source, $diagnostic])
	{
		$failed = false;
		try {
			result_source($path, $source);
		}
		catch (\RuntimeException $error) {
			$failed = str_contains($error->getMessage(), $diagnostic);
		}
		result_check($failed, 'Missing expected Result rejection: ' . $source);
		result_check(Model::$cpp_files->is_empty(), 'Rejected Result source published output');
	}

	$valid = 'function source(): result<int> { return 3; } '
		. 'function pass(): result<int> { return source(); } '
		. 'function stable(): int { return 9; } $a = pass();';
	$compiler = result_source($path, $valid);
	$original = CPP_Probe::text(Model::$cpp_files);
	$stable = Model::$global_scope->functions_named('stable')[0]->syntax()->body->work();
	$version = $stable->version;
	file_put_contents($path, str_replace('source(): result<int>', 'source(): result_or_false<int>', $valid));
	foreach ([[$path], []] as $changed)
	{
		$compiler->sync($changed);
		$failed = false;
		try {
			$compiler->prepare();
		}
		catch (\RuntimeException $error) {
			$failed = str_contains($error->getMessage(), 'Result construction requires an exact payload');
		}
		result_check($failed && Model::$cpp_files->is_empty(), 'Result family change published stale output');
		result_check($stable->version === $version, 'Result family change rebuilt an independent body');
	}
	file_put_contents($path, $valid);
	$compiler->update_cpp([$path]);
	result_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Result repair changed output');
	result_source($path, $valid);
	result_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Clean and incremental Result output differ');

	$valid = 'function source(): result<int> { return 3; } function read(): int { return source(); } '
		. 'function stable(): int { return 9; } $v = read();';
	$compiler = result_source($path, $valid);
	$original = CPP_Probe::text(Model::$cpp_files);
	$stable = Model::$global_scope->functions_named('stable')[0]->syntax()->body->work();
	$version = $stable->version;
	file_put_contents($path, str_replace('result<int> { return 3;', 'result<float> { return 3.5;', $valid));
	foreach ([[$path], []] as $changed)
	{
		$compiler->sync($changed);
		$failed = false;
		try {
			$compiler->prepare();
		}
		catch (\RuntimeException $error) {
			$failed = str_contains($error->getMessage(), 'Result extraction requires the exact payload type');
		}
		result_check($failed && Model::$cpp_files->is_empty(), 'Extraction type change published stale output');
		result_check($stable->version === $version, 'Extraction type change rebuilt an independent body');
	}
	file_put_contents($path, $valid);
	$compiler->update_cpp([$path]);
	result_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Extraction repair changed output');
	result_source($path, $valid);
	result_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Clean and incremental extraction output differ');
	foreach (['if (source()) { return true; } return false;',
		'return isset(source());', 'return !isset(source());'] as $consumer)
	{
		$valid = 'function source(): result<int> { return 0; } function read(): bool { '
			. $consumer . ' } function stable(): int { return 9; } $ok = read();';
		$compiler = result_source($path, $valid);
		$original = CPP_Probe::text(Model::$cpp_files);
		$stable = Model::$global_scope->functions_named('stable')[0]->syntax()->body->work();
		$version = $stable->version;
		file_put_contents($path, str_replace('result<int> { return 0;', 'void { return;', $valid));
		foreach ([[$path], []] as $changed)
		{
			$compiler->sync($changed);
			$failed = false;
			try { $compiler->prepare(); }
			catch (\RuntimeException $error) { $failed = true; }
			result_check($failed && Model::$cpp_files->is_empty(), 'Result probe/condition published stale output');
			result_check($stable->version === $version, 'Result probe/condition rebuilt independent body');
		}
		file_put_contents($path, $valid);
		$compiler->update_cpp([$path]);
		result_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Result probe/condition repair changed output');
		result_source($path, $valid);
		result_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Clean/incremental Result probe/condition differ');
	}
	result_source($path, '$r result<int> = 0; if ($r) {} $p bool = isset($r); $e bool = isset($r);');
	$probe_root = Model::syntax_files()[0]->root;
	result_check($probe_root->single_body()->body->statements[1]->require_preparation()->truthiness->operation
		=== truthiness_operation::result_condition, 'Missing Result condition fact');
	$presence_node = $probe_root->single_body()->body->statements[2]->initializer;
	$empty_node = $probe_root->single_body()->body->statements[3]->initializer;
	Preparation_Cleanup::tree($probe_root);
	result_check($presence_node->preparation() === null && $presence_node->operand->preparation() === null
		&& $empty_node->preparation() === null, 'Result probes retained stale facts');
	result_source($path, '$r result<int> = 1; $a result<int> = true ? $r : 2; '
		. '$b result<int> = false ? 2 : $r; $c result<int> = $r ?: 3;');
	$join_root = Model::syntax_files()[0]->root;
	foreach ([1, 2, 3] as $index) {
		$join_node = $join_root->single_body()->body->statements[$index]->initializer;
		$join_facts = $join_node->require_conditional_preparation();
		result_check(!$join_facts->addressable(), 'Conditional wrapper became an alias');
		result_check($join_facts->true_conversion->operation === ($index === 2
			? conversion_operation::result_wrap : conversion_operation::identity), 'Wrong true branch conversion');
		result_check($join_facts->fallback_conversion->operation === ($index === 2
			? conversion_operation::identity : conversion_operation::result_wrap), 'Wrong fallback conversion');
	}
	Preparation_Cleanup::tree($join_root);
	result_check($join_node->preparation() === null && $join_node->condition->preparation() === null
		&& $join_node->fallback->preparation() === null, 'Conditional wrapper retained facts');
	$valid = 'function source(): result<int> { return 1; } function read(): result<int> { return source() ?: 2; } '
		. 'function stable(): int { return 9; } $r = read();';
	$compiler = result_source($path, $valid);
	$original = CPP_Probe::text(Model::$cpp_files);
	$stable = Model::$global_scope->functions_named('stable')[0]->syntax()->body->work();
	$version = $stable->version;
	file_put_contents($path, str_replace('source(): result<int> { return 1;', 'source(): result<float> { return 1.5;', $valid));
	foreach ([[$path], []] as $changed) {
		$compiler->sync($changed);
		$failed = false;
		try { $compiler->prepare(); }
		catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'conditional Result branches'); }
		result_check($failed && Model::$cpp_files->is_empty(), 'Conditional payload edit published stale output');
		result_check($stable->version === $version, 'Conditional edit rebuilt independent body');
	}
	file_put_contents($path, $valid);
	$compiler->update_cpp([$path]);
	result_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Conditional repair changed output');
	result_source($path, $valid);
	result_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Clean/incremental conditional output differs');
	result_source($path, '$r result<int> = 0; $v int = $r ?? 2;');
	$coalesce_root = Model::syntax_files()[0]->root;
	$coalesce_node = $coalesce_root->single_body()->body->statements[1]->initializer;
	$coalesce_facts = $coalesce_node->require_coalesce_preparation();
	result_check($coalesce_facts->left_extraction->operation === conversion_operation::result_extract
		&& $coalesce_facts->left_extraction->context === conversion_context::coalesce_value
		&& $coalesce_facts->left_conversion->operation === conversion_operation::identity,
		'Coalesce lost its selected extraction context');
	result_check($coalesce_facts->type->matches(Language_Types::integer(Model::$language_scope))
		&& !$coalesce_facts->addressable(), 'Coalesce did not produce an owned payload');
	Preparation_Cleanup::tree($coalesce_root);
	result_check($coalesce_node->preparation() === null && $coalesce_node->left->preparation() === null
		&& $coalesce_node->fallback->preparation() === null, 'Coalesce retained stale facts');
	$valid = 'function source(): result<int> { return 1; } function read(): int { return source() ?? 2; } '
		. 'function stable(): int { return 9; } $r = read();';
	$compiler = result_source($path, $valid);
	$original = CPP_Probe::text(Model::$cpp_files);
	$stable = Model::$global_scope->functions_named('stable')[0]->syntax()->body->work();
	$version = $stable->version;
	file_put_contents($path, str_replace('source(): result<int>', 'source(): result_or_bool<int>', $valid));
	foreach ([[$path], []] as $changed) {
		$compiler->sync($changed);
		$failed = false;
		try { $compiler->prepare(); }
		catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'coalesce does not permit result_or_bool'); }
		result_check($failed && Model::$cpp_files->is_empty(), 'Boolable edit published stale coalesce output');
		result_check($stable->version === $version, 'Coalesce edit rebuilt independent body');
	}
	file_put_contents($path, $valid);
	$compiler->update_cpp([$path]);
	result_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Coalesce repair changed output');
	result_source($path, $valid);
	result_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Clean/incremental coalesce output differs');
	foreach (['mixed', 'dynamic'] as $family) {
		$other = $family === 'mixed' ? 'dynamic' : 'mixed';
		$source = '$v ' . $family . ' = 7; $r result<' . $family . '> = $v; $out ' . $family . ' = $r; '
			. '$err error; take($out, $err) = $r; $join result<' . $family . '> = true ? $v : $r; $selected = $r ?? $v;';
		result_source($path, $source);
		$body = Model::syntax_files()[0]->root->single_body()->body;
		result_check($body->statements[1]->require_preparation()->conversion->operation === conversion_operation::result_wrap
			&& $body->statements[2]->require_preparation()->conversion->operation === conversion_operation::result_extract,
			'Boxed payload bypassed outer Result conversion owner');
		result_check($body->statements[5]->initializer->require_preparation()->type->matches(
			$body->statements[1]->require_preparation()->type), 'Boxed branch swallowed Result identity');
		Preparation_Cleanup::tree(Model::syntax_files()[0]->root);
		result_check($body->statements[5]->initializer->preparation() === null, 'Boxed Result join retained facts');
		foreach ([
			'$r result<' . $family . '> = 7;',
			'$v ' . $other . ' = 7; $r result<' . $family . '> = $v;',
			'function bad(result<' . $family . '> $r): int { return $r; }',
			'function bad(result<' . $family . '> $r): ' . $other . ' { return $r; }',
			'function bad(result<' . $family . '> $r, ' . $other . ' &$v, error &$e): bool { return take($v, $e) = $r; }',
			'function bad(result<' . $family . '> $r, ' . $other . ' $v): ' . $family . ' { return $r ?? $v; }',
			'function bad(result_or_bool<' . $family . '> $r, ' . $family . ' $v): ' . $family . ' { return $r ?? $v; }',
			'function bad(result<' . $family . '> $r, ' . $other . ' $v): result<' . $family . '> { return true ? $r : $v; }',
		] as $invalid) {
			$failed = false;
			try { result_source($path, $invalid); }
			catch (\RuntimeException $error) { $failed = true; }
			result_check($failed && Model::$cpp_files->is_empty(), 'Invalid boxed Result published output: ' . $invalid);
		}
	}
	$valid = 'function source(): result<mixed> { $v mixed = 7; return $v; } '
		. 'function read(): mixed { return source(); } function stable(): int { return 9; }';
	$compiler = result_source($path, $valid);
	$original = CPP_Probe::text(Model::$cpp_files);
	$stable = Model::$global_scope->functions_named('stable')[0]->syntax()->body->work();
	$version = $stable->version;
	file_put_contents($path, str_replace(['result<mixed>', '$v mixed'], ['result<dynamic>', '$v dynamic'], $valid));
	foreach ([[$path], []] as $changed) {
		$compiler->sync($changed);
		$failed = false;
		try { $compiler->prepare(); }
		catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'exact payload'); }
		result_check($failed && Model::$cpp_files->is_empty(), 'Changed boxed Result published stale output');
		result_check($stable->version === $version, 'Boxed Result edit rebuilt independent body');
	}
	file_put_contents($path, $valid);
	$compiler->update_cpp([$path]);
	result_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Boxed Result repair changed output');
	result_source($path, $valid);
	result_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Boxed Result clean/incremental output differs');
	echo "Results: scalar/boxed boundaries, conditions/probes/joins/coalescing, rejections and recovery passed\n";
}
finally {
	unlink($path);
	rmdir($directory);
}
