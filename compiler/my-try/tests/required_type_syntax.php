<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$directory = $argv[1];
mkdir($directory);
$path = $directory . '/main.phs';
$source = 'function proof(): int { $x int = 1; $items vector<int> = [2, 3]; '
	. '$callback function<int(int)> = fn(int $n): int => $n; '
	. 'try { throw Exception::make("test"); } catch (Exception $error) { $x = 4; } '
	. 'return $callback($x); } return proof();';
foreach ([false, true] as $prelower) {
	Compiler_Lifecycle::reset();
	file_put_contents($path, $source);
	$compiler = new Compiler(new cpp_lowering_options($prelower));
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	$owner = Model::$global_scope->functions_named('proof')[0]->syntax();
	$body = $owner->require_body();
	$local = $body->statements[0];
	$local_type = $local->require_type_syntax();
	$container_type = $body->statements[1]->require_type_syntax();
	$callable_type = $body->statements[2]->require_type_syntax();
	$input_type = $callable_type->inputs[0]->require_type_syntax();
	$catch_type = $body->statements[3]->handler->binding->require_type_syntax();
	if (!($container_type instanceof template_application_type_node) || !($input_type instanceof named_type_node)
		|| !($catch_type instanceof named_type_node)) {
		throw new \LogicException('Required type parsing lost its syntax shape');
	}
	$original = CPP_Probe::text(Model::$cpp_files);
	$compiler->update_cpp([$path]);
	if (($owner->require_body() !== $body) || ($body->statements[0] !== $local)
		|| ($local->require_type_syntax() !== $local_type)
		|| ($callable_type->inputs[0]->require_type_syntax() !== $input_type)) {
		throw new \LogicException('No-op parsing replaced retained required type syntax');
	}
	file_put_contents($path, str_replace('$x int = 1;', '$x int[;', $source));
	$failed = false;
	try { $compiler->sync([$path]); $compiler->prepare(); }
	catch (\RuntimeException $error) { $failed = true; }
	if (!$failed) { throw new \LogicException('Malformed declaration type was accepted'); }
	file_put_contents($path, $source);
	$compiler->update_cpp([$path]);
	if ((Model::$global_scope->functions_named('proof')[0]->syntax() !== $owner)
		|| (CPP_Probe::text(Model::$cpp_files) !== $original)) {
		throw new \LogicException('Required-type parse recovery changed owner identity or output');
	}
}
echo "Required type syntax parsing, no-op identity and recovery passed\n";
