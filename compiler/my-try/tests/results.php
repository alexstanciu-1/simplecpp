<?php

/* Prepared construction decisions, deliberate exclusions and incremental recovery. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

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
		$facts = $root->body->statements[$position]->require_preparation();
		result_check($facts->conversion->operation === $operation, 'Wrong prepared Result state');
		result_check($facts->conversion->result_type->matches($facts->type), 'Result conversion lost destination identity');
	}
	$bool_type = Language_Types::boolean(Model::$language_scope);
	$catalog = Model::$type_catalog;
	foreach ([false, true] as $value)
	{
		$marker = $catalog->boolean_sentinel_type($value);
		result_check(!$marker->matches($bool_type), 'Sentinel type was conflated with boolean payload');
		result_check(Type_Preparation::canonical($marker)->definition()->capabilities() === [],
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
	result_check($root->body->statements[1]->initializer->preparation() === null,
		'Cleanup retained sentinel reference facts');

	$rejections = [
		['$x = null;', 'explicit nullable or legacy Result destination'],
		['$x = nullopt;', 'explicit nullable or legacy Result destination'],
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
		['$a result<int> = 1; $b int = $a;', 'Result extraction is deferred'],
		['$a result<int> = 1; $b = (int) $a;', 'Result extraction is deferred'],
		['$a result<int> = 1; $b ?int = $a;', 'Result extraction is deferred'],
		['$a result<int> = 1; if ($a) {}', 'condition truthiness requires a supported scalar'],
		['$a result<int> = 1; $b = $a ?? 2;', 'coalesce branches require supported scalar'],
		['$a result<int> = 1; $b = isset($a);', 'value probes require a scalar'],
		['$a result<int> = 1; $b = empty($a);', 'value probes require a scalar'],
		['$x = false_sentinel;', 'sentinel requires an explicit Result destination'],
		['$x = true_sentinel;', 'sentinel requires an explicit Result destination'],
		['$x result<int>;', 'Result construction requires an explicit initializer'],
		['$x result<result<int>>;', 'Result construction requires an unmodified scalar payload'],
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
	$original = Model::$cpp_files[0]->text;
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
	result_check(Model::$cpp_files[0]->text === $original, 'Result repair changed output');
	result_source($path, $valid);
	result_check(Model::$cpp_files[0]->text === $original, 'Clean and incremental Result output differ');
	echo "Results: construction facts, sentinel identity, rejections, cleanup and incremental recovery passed\n";
}
finally {
	unlink($path);
	rmdir($directory);
}
