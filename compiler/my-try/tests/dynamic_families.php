<?php
namespace scpp\compiler;

// Reuse the retained boundary/recovery proofs; export only this migration slice.
require __DIR__ . '/mixed.php';

$output = $argv[1];
$fixtures = require __DIR__ . '/fixtures/mixed_dynamic_casts.php';
$executions = [];
foreach ($fixtures as $name => [$source, $predicate]) {
	Compiler_Lifecycle::reset();
	$input_module = new module('/family-proof', '/family-proof', '/family-proof');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($input_module, $file);
	Model::$modules->add($input_module->name, $input_module);
	(new Compiler())->exec_cpp();
	$text = Model::$cpp_files[0]->text;
	if (str_contains($text, 'dynamic_box')) {
		throw new \LogicException('Removed handle bridge returned to C++ output');
	}
	$headers = "#include \"scpp/memory.hpp\"\n#include \"scpp/hash_t.hpp\"\n";
	$text = str_replace('int main()', 'int unused_entry()', $text);
	$text = $headers . $text . "\nint main() { return (" . $predicate . ") ? 0 : 91; }\n";
	$path = $output . '/' . $name . '.cpp';
	file_put_contents($path, $text);
	$executions[] = ['path' => $path, 'exit_code' => 0];
}
file_put_contents($output . '/executions.json', json_encode($executions, JSON_PRETTY_PRINT));
echo "Family casts: checked scalar/null conversions exported\n";
