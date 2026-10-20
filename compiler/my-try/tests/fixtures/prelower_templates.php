<?php
namespace scpp\compiler;
require_once dirname(__DIR__, 2) . '/boot.php';

function check_prelower(bool $value, string $message): void
{
	if (!$value) {
		throw new \LogicException($message);
	}
}

/** Compare stable artifact membership and bytes across incremental invocations. */
function prelower_artifacts(): array
{
	$result = [];
	foreach (Model::$cpp_files as $file) {
		$result[$file->file_name] = $file->text;
	}
	ksort($result);
	return $result;
}

$directory = $argv[1];
mkdir($directory);
$path = $directory . '/main.phs';
$source = 'function make(): vector<int> { $v vector<int> = [1, 2]; return $v; } function use_value(): int { $u vector<uint16> = [9]; $v = make(); return $v[0]; } return use_value();';
file_put_contents($path, $source);
$compiler = new Compiler(new cpp_lowering_options(false));
$compiler->init([$directory]);
$compiler->exec_cpp();
$original = prelower_artifacts();
$owner = Model::$global_scope->functions_named('make')[0]->syntax()->body->work();
$version = $owner->version;
$compiler->cpp_lowering->prelower_templates = true;
$compiler->cpp();
$concrete = prelower_artifacts();
check_prelower(Model::$cpp_output_program->prelower_templates, 'Public option was not retained');
check_prelower(get_object_vars($compiler->cpp_lowering) === ['prelower_templates' => true], 'Temporary lowering selectors remain');
check_prelower(str_contains($concrete['entry.cpp'], 'scpp::internal::run_application'), 'Entry reporter bypassed support');
check_prelower(!str_contains($concrete['entry.cpp'], 'scpp/runtime_error.hpp'), 'Runtime diagnostics leaked into entry');
check_prelower(!str_contains($concrete['specializations/base.hpp'], '#include <exception>'), 'Exception templates leaked into concrete base');
check_prelower($owner->version === $version, 'Lowering configuration changed semantic work');
$fragment = Model::$cpp_output_program->fragments[$owner];
$compiler->cpp();
check_prelower($concrete === prelower_artifacts(), 'Cached fragment lost support artifacts');
check_prelower($fragment === Model::$cpp_output_program->fragments[$owner], 'No-op rerendered fragment');
$compiler->cpp_lowering->prelower_templates = false;
$compiler->cpp();
check_prelower($original === prelower_artifacts(), 'Default mode changed after toggling');
$compiler->cpp_lowering->prelower_templates = true;
$compiler->cpp();
check_prelower($concrete === prelower_artifacts(), 'Concrete mode changed after toggling');
file_put_contents($path, str_replace('[1, 2]', '[3, 4]', $source));
$compiler->update_cpp([$path]);
$changed = prelower_artifacts();
foreach ($concrete as $name => $text) {
	if (str_starts_with($name, 'specializations/')) {
		check_prelower(($changed[$name] ?? null) === $text, 'Body edit changed support: ' . $name);
	}
}
file_put_contents($path, 'return 0;');
$compiler->update_cpp([$path]);
foreach (prelower_artifacts() as $name => $text) {
	check_prelower(!str_contains($name, 'concrete_n_vector'), 'Deleted request retained vector');
}
file_put_contents($path, '$f function<int()> = fn(): int => 1; return $f();');
$failed = false;
try {
	$compiler->update_cpp([$path]);
}
catch (\RuntimeException $error) {
	$failed = str_contains($error->getMessage(), 'callable storage requires a concrete callback transport');
}
check_prelower($failed && Model::$cpp_files->is_empty(), 'Unsupported family exposed ready output');
file_put_contents($path, $source);
$compiler->update_cpp([$path]);
check_prelower(prelower_artifacts() === $concrete, 'Failed generation did not recover');
$left = new CPP_Specializations();
$right = new CPP_Specializations();
$int = Model::$type_catalog->registry()->use(Model::$type_catalog->canonical('int')->type_id());
$byte = Model::$type_catalog->registry()->use(Model::$type_catalog->canonical('uint8')->type_id());
$left->request($int);
$left->request($byte);
$right->request($byte);
$right->request($int);
$left_files = new Storage();
$right_files = new Storage();
$left->append_artifacts($left_files);
$right->append_artifacts($right_files);
foreach ($left_files as $id => $artifact) {
	check_prelower($artifact->file_name === $right_files[$id]->file_name && $artifact->text === $right_files[$id]->text,
		'Discovery order changed specialization artifacts');
}
echo "prelower lifecycle passed\n";
