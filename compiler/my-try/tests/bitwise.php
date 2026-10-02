<?php
namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';

/** Prepare one isolated operator source through shared semantics and C++ lowering. */
function bitwise_test_source(string $text): parsed_file
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


// Invalid counts remain representable source under the native runtime contract.
// Prepare and emit them only: executing either expression would have undefined behavior.
foreach (['return 1 << 64;', 'return 1 >> -1;'] as $source) {
	bitwise_test_source($source);
}

foreach ([['return 8 << 1;', 'return 8 >> 1;', 0],
	['$x = 8; return $x <<= 1;', '$x = 8; return $x >>= 1;', 1]] as [$before, $after, $slot])
{
	$directory = sys_get_temp_dir() . '/scpp_bits_' . bin2hex(random_bytes(6));
	mkdir($directory);
	$path = $directory . '/main.phs';
	try
	{
		file_put_contents($path, 'function value(): int { ' . $before . ' } return value();');
		Compiler_Lifecycle::reset();
		$compiler = new Compiler();
		$compiler->init([$directory]);
		$compiler->exec_cpp();
		$function = Model::$global_scope->functions_named('value')[0]->syntax();
		$signature = $function->require_preparation();
		$changed = 'function value(): int { ' . $after . ' } return value();';
		file_put_contents($path, $changed);
		$compiler->update_cpp([$path]);
		$body = $function->body;
		$expression = $body->statements[$slot]->expression;
		if (($expression->preparation()->decision->operation !== operator_operation::integer_shift_right)
			|| ($function->require_preparation() !== $signature)) {
			throw new \LogicException('Shift edit lost its operation or unchanged signature');
		}
		$changed = 'function before(): int { return 0; } ' . $changed;
		file_put_contents($path, $changed);
		$compiler->update_cpp([$path]);
		$compiler->cleanup_tokens();
		$expected = $slot === 0 ? '>>' : '>>=';
		if (($function->body !== $body)
			|| (Model::tokens()[0]->operator_text_at($expression->operator_token_index) !== $expected)) {
			throw new \LogicException('Compaction lost a retained shift spelling');
		}
		$incremental = Model::$cpp_files[0]->text;
		Compiler_Lifecycle::reset();
		$compiler->init([$directory]);
		$compiler->exec_cpp();
		if (Model::$cpp_files[0]->text !== $incremental) {
			throw new \LogicException('Incremental shift output differs from fresh output');
		}
		if ($slot === 0)
		{
			file_put_contents($path, str_replace('>>', '> >', $changed));
			$failed = false;
			try {
				$compiler->update_cpp([$path]);
			}
			catch (\RuntimeException $error) {
				$failed = true;
			}
			if (!$failed) {
				throw new \LogicException('Whitespace separating shift characters reused valid syntax');
			}
			file_put_contents($path, $changed);
			$compiler->update_cpp([$path]);
			if (Model::$cpp_files[0]->text !== $incremental) {
				throw new \LogicException('Shift recovery did not restore fresh output');
			}
		}
	}
	finally {
		unlink($path);
		rmdir($directory);
	}
}
echo "Bitwise: native-count acceptance without execution, incremental shifts, compaction and recovery passed\n";
