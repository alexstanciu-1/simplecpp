<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function combined_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

$directory = sys_get_temp_dir() . '/scpp_combined_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
$initial = 'function target(int $x): int { return $x; } function caller(): int { return target(7); } return caller();';
try
{
	file_put_contents($path, $initial);
	$compiler = new Compiler(new cpp_lowering_options(false));
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$record = Model::$modules[$directory]->sources['main.phs'];
	$parsed = $record->parsed;
	$target = object_cast(Model::$global_scope->functions_named('target')[0]->syntax(), function_node::class);
	$caller = object_cast(Model::$global_scope->functions_named('caller')[0]->syntax(), function_node::class);
	$signature = $target->require_preparation();
	$caller_body = $caller->body;
	$version = $caller->body->work()->version;
	$tokens = $record->tokens;
	$output = CPP_Probe::text(Model::$cpp_files);
	$compiler->exec_cpp();
	combined_check($record->tokens === $tokens && $caller->body->work()->version === $version && CPP_Probe::text(Model::$cpp_files) === $output, 'No-op combined compilation rebuilt facts or changed output');

	file_put_contents($path, str_replace('return $x;', 'return 11;', $initial));
	$compiler->update_cpp([$path, $path]);
	combined_check($record->parsed === $parsed && $record->tokens !== $tokens, 'Combined sync lost retained syntax or token replacement');
	combined_check($target->require_preparation() === $signature && $caller->body === $caller_body && $caller->body->work()->version === $version, 'Implementation-only edit rebuilt the caller');
	combined_check(CPP_Probe::text(Model::$cpp_files) !== $output, 'Implementation edit did not reach emitted output');

	file_put_contents($path, str_replace('target(int $x): int', 'target(int $x): uint8', $initial));
	$compiler->update_cpp([$path]);
	combined_check($caller->body === $caller_body && $caller->body->work()->version === $version + 1, 'Signature change did not rebuild the retained caller exactly once');
	$incremental = CPP_Probe::text(Model::$cpp_files);
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	combined_check(CPP_Probe::text(Model::$cpp_files) === $incremental, 'Incremental and fresh C++ differ');

	file_put_contents($path, 'function broken(');
	try {
		$compiler->update_cpp([$path]);
		throw new \LogicException('Malformed update accepted');
	}
	catch (\RuntimeException $expected) {
	}
	combined_check(Model::$prepared_files->is_empty() && Model::$cpp_files->is_empty() && !Model::syntax_files()[0]->complete, 'Failed combined update published results');
	file_put_contents($path, $initial);
	$compiler->update_cpp([$path]);
	combined_check(Model::syntax_files()[0]->complete && !Model::$cpp_files->is_empty(), 'Combined retry did not recover');
}
finally {
	unlink($path);
	rmdir($directory);
}
echo "Combined sync: no-op reuse, notifications, retained bodies, signature invalidation, fresh equivalence and retry passed\n";
