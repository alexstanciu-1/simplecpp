<?php
namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';

/** Prepare one isolated operator source through shared semantics and C++ lowering. */
function interpolation_test_source(string $text): parsed_file
{
	Compiler_Lifecycle::reset();
	$input = new file();
	$input->path = 'operator.phs';
	$input->content = $text;
	$syntax = (new Parser((new Tokenizer($input))->tokenize()))->parse();
	$module = new module('.', Source_Registry::normalize('.'), '.');
	$modules /** Keyed_Storage<module> */ = Model::$modules;
	$modules->add($module->name, $module);
	$record = Source_Registry::add($module, $input);
	Source_Publication::publish_parsed($record, $syntax);
	$compiler = new Compiler();
	$compiler->prepare();
	$compiler->cpp();
	return $syntax;
}


$syntax = interpolation_test_source('$name = "A"; $n = 2; $a = "Hi $name:{$n}";');
$node = $syntax->root->body->statements[2]->expression->value;
if (!($node instanceof interpolated_string_node) || count($node->parts) !== 4
	|| count(iterator_to_array($node->children())) !== 4 || $node->require_preparation()->addressable) {
	throw new \LogicException('Interpolation lost its ordered parts or value facts');
}
$first = $node->parts[1];
$last = $node->parts[3];
if (($first->byte_offset !== 4) || ($first->byte_length !== 5)
	|| ($last->byte_offset !== 10) || ($last->byte_length !== 4)
	|| ($first->expression->occurrence() === $last->expression->occurrence())
	|| ($first->require_preparation()->conversion->context !== conversion_context::interpolation)
	|| ($last->require_preparation()->conversion->operation !== conversion_operation::explicit_runtime_cast)) {
	throw new \LogicException('Interpolation lost source ranges, collected identity or conversion decisions');
}
Preparation_Cleanup::tree($syntax->root);
if (($node->preparation() !== null) || ($first->preparation() !== null)
	|| ($first->expression->preparation() !== null) || ($node->parts[0]->preparation() !== null)) {
	throw new \LogicException('Interpolation cleanup left attached facts');
}

foreach ([
	['$a = "$missing";', 'established local declaration for missing'],
	['$a = "$a";', 'established local declaration for a'],
	['struct Box { int $v; } $b Box; $a = "$b";', 'interpolation requires a scalar'],
	['$a = "${name}";', 'Unsupported interpolation'],
	['$a = "$$name";', 'Unsupported interpolation'],
	['$x = 1; $a = "{$x + 1}";', 'Unsupported braced interpolation'],
	['$x = 1; $a = "{$x = 2}";', 'Unsupported braced interpolation'],
	['$x = 1; $a = "{$x->field}";', 'Unsupported braced interpolation'],
	['$x = 1; $a = "$x[0]";', 'Unsupported interpolation suffix'],
	['$x = 1; $a = "$x->field";', 'Unsupported interpolation suffix'],
	['$x = 1; $a = "$x()";', 'Unsupported interpolation suffix'],
	['$a = "{$name";', 'Unsupported braced interpolation'],
	['$x = 1; $a = "$x" . 2;', 'concatenation requires string operands'],
	['function f(string &$x): int { return 0; } $s = "x"; return f("$s");', 'reference arguments require stable storage'],
] as [$source, $diagnostic])
{
	$failed = false;
	try {
		interpolation_test_source($source);
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $diagnostic);
	}
	if (!$failed || !Model::$cpp_files->is_empty()) {
		throw new \LogicException('Missing interpolation diagnostic: ' . $source);
	}
}

$directory = sys_get_temp_dir() . '/scpp_interpolation_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
try
{
	$source = 'function value(string $x): string { return "A $x"; } $a = value("ok");';
	file_put_contents($path, $source);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$function = Model::$global_scope->functions_named('value')[0]->syntax();
	$signature = $function->require_preparation();
	$source = str_replace('A $x', 'B {$x}', $source);
	file_put_contents($path, $source);
	$compiler->update_cpp([$path]);
	$node = $function->body->statements[0]->expression;
	$body = $function->body;
	$part = $node->parts[1];
	$occurrence = $part->expression->occurrence();
	if (($function->require_preparation() !== $signature) || ($part->byte_length !== 4)) {
		throw new \LogicException('String edit lost unchanged signature or changed parts');
	}
	$source = 'function before(): int { return 0; } ' . $source;
	file_put_contents($path, $source);
	$compiler->update_cpp([$path]);
	$compiler->cleanup_tokens();
	$token = Model::tokens()[0]->text_at($part->start_token());
	if (($function->body !== $body) || ($part->expression->occurrence() !== $occurrence)
		|| (substr($token, $part->byte_offset, $part->byte_length) !== '{$x}')) {
		throw new \LogicException('Compaction damaged interpolation ranges or collected identity');
	}
	$incremental = Model::$cpp_files[0]->text;
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	if (Model::$cpp_files[0]->text !== $incremental) {
		throw new \LogicException('Incremental interpolation differs from fresh generation');
	}
	foreach (['{$missing}', '{$x + 1}'] as $invalid)
	{
		file_put_contents($path, str_replace('{$x}', $invalid, $source));
		$failed = false;
		try {
			$compiler->update_cpp([$path]);
		}
		catch (\RuntimeException $error) {
			$failed = true;
			if ($invalid === '{$x + 1}') {
				$expected_offset = strpos(str_replace('{$x}', $invalid, $source), $invalid);
				if (!str_contains($error->getMessage(), ': byte ' . $expected_offset)) {
					throw new \LogicException('Interpolation parse diagnostic lost its byte position');
				}
			}
		}
		if (!$failed || !Model::$cpp_files->is_empty()) {
			throw new \LogicException('Failed interpolation edit retained generated output');
		}
		file_put_contents($path, $source);
		$compiler->update_cpp([$path]);
		if (Model::$cpp_files[0]->text !== $incremental) {
			throw new \LogicException('Interpolation recovery did not restore fresh output');
		}
	}
}
finally {
	unlink($path);
	rmdir($directory);
}
echo "Interpolation: parts, ranges, conversions, rejections, cleanup and incremental recovery passed\n";
