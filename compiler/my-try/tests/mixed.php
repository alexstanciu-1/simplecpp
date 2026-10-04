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

	$rejections = [
		['$n uint8 = 7; $x mixed = $n;', 'mixed boxing currently supports'],
		['$x mixed = 7; $n uint8 = $x;', 'mixed extraction currently supports'],
		['$v ?int = 7; $x mixed = $v;', 'mixed boxing currently supports'],
		['$v result<int> = 7; $x mixed = $v;', 'mixed boxing currently supports'],
		['$x mixed = 7; $v ?int = $x;', 'mixed extraction currently supports'],
		['$x mixed = 7; $v result<int> = $x;', 'mixed extraction currently supports'],
		['$d dynamic; $x mixed = $d;', 'mixed boxing currently supports'],
		['$x mixed = 7; $d dynamic = $x;', 'mixed extraction currently supports'],
		['$x mixed = 7; $y = (int)$x;', 'mixed conversion currently supports'],
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
	echo "Mixed boundaries: prepared decisions, deferred cases and incremental recovery passed\n";
}
finally
{
	unlink($path);
	rmdir($directory);
}
