<?php

namespace scpp\compiler;

\define('dbg', false);
require_once dirname(__DIR__) . '/boot.php';

final class LLVM_Test
{
	/** Exercise real source files through the same coordinator used by the browser. */
	public static function run(string $directory): void
	{
		$cases = [
			'array_read' => ['$values int[3] = [1, 2, 3]; return $values[2];', 3],
			'array_write' => ['$values int[3] = [1, 2, 3]; $values[1] = 7; return $values[1];', 7],
			'array_copy_element' => ['$first int[2] = [4, 8]; $second int[1] = [0]; $second[0] = $first[1]; return $second[0];', 8],
			'array_scope' => ['function value(): int { $values int[1] = [6]; return $values[0]; } $values int[2] = [1, 2]; return value();', 6],
			'empty_array' => ['$values int[0] = []; return 0;', 0],
			'sample' => ['$a int = 1; return $a;', 1],
			'copy' => ['$first int = 7; $second int = $first; return $second;', 7],
			'literal' => ['return 9;', 9],
			'zero' => ['$zero int = 000; return $zero;', 0],
			'maximum' => ['return 2147483647;', 255],
			'function_scope' => ['function example(): void { $a int = 2; $b int = $a; } $a int = 1; return $a;', 1],
			'empty_function' => ['function empty_body(): void {} return 3;', 3],
			'bare_return' => ['function done(): void { return; } return 4;', 4],
			'after_entry_return' => ['return 5; function later(): void {}', 5],
			'escaped_names' => ['function under_score(): void {} $entry int = 1; $_Gv0 int = $entry; return $_Gv0;', 1],
			'duplicate_names' => ['function repeat(): void {} function repeat(): void {} $a int = 2; $a int = 3; return 7;', 7],
			'value_initializer' => ['function value(): int { return 3; } $a int = value(); return $a;', 3],
			'return_call' => ['function value(): int { $a int = 7; return $a; } return value();', 7],
			'discard_result' => ['function value(): int { return 3; } value(); return 9;', 9],
		];
		$executions = [];
		foreach ($cases as $name => [$source, $exit_code])
		{
			$compiler = self::compile($directory . '/' . $name, $source);
			$module = Model::$llvm_files[0];
			if ((count(Model::$llvm_files) !== 1) || !$module->functions[0]->is_entry || ($module->globals !== []) || ($module->external_functions !== []) || ($module->types !== []) || ($module->metadata !== [])) {
				throw new \RuntimeException('Incorrect output module structure');
			}
			$file = Model::$collected_files[0];
			if ($file->source->content !== $source) {
				throw new \RuntimeException('Source content changed');
			}
			foreach ($file->defined_elements as $index) {
				$entry = $file->entries[$index];
				$pool = $entry->kind === collected_name_kind::function_declaration ? $entry->scope->functions_named($entry->name) : $entry->scope->variables_named($entry->name);
				if (!in_array($entry, $pool, true)) {
					throw new \RuntimeException('Declaration collection changed');
				}
			}
			if ($name === 'function_scope') {
				self::check_function_scope($file, $module);
			}
			$path = $directory . '/' . $name . '.ll';
			file_put_contents($path, $module->text);
			$executions[] = ['path' => $path, 'exit_code' => $exit_code];
		}

		// Unsupported cases must fail before publishing LLVM output.
		$unsupported = [
			'template<T> function f(T $x): T {} return 0;',
			'template<T> function f(T $x): T { return $x; return $x; } return 0;',
			'template<T> function f(T $x): T { return 1; } return 0;',
			'template<T> function f(T $x): int { return $x; } return 0;',
			'template<T> function f(T &$x): void {} return 0;',
			'template<T> function f(T $x): T { $y T; return $x; } return 0;',
			'template<T> function f(T $x): int { return $x->value; } return 0;',
			'template<T> function f(T $x): T { return $x; } return f(1);',
			'template<T> function f(T $x): T { return $x; } return f<void>(1);',
			'template<T> function f(T $x): T { return $x; } return f<int, int>(1);',
			'template<T, T> function f(): void {} return 0;',
			'template<T> function f(T $T): void {} return 0;',
			'template<T> function f(T $x): T { return missing<T>($x); } return 0;',
			'function concrete(int $a): int { return $a; } template<T> function f(T $a): T { return concrete($a); } return 0;',
			'template<T, U> function f(T $a, U $b): T { return $b; } return f<int, int>(1, 2);',

			'struct empty {} return 0;',
			'struct point { int $x; int $x; } return 0;',
			'struct point { int $x; } struct point { int $y; } return 0;',
			'struct int { int $x; } return 0;',
			'struct point { void $x; } return 0;',
			'struct point { private int $x; } return 0;',
			'struct point { int $x = 7; } return 0;',
			'struct point { int $x; } $p point; return $p->missing;',
			'$a int = 1; return $a->x;',
			'struct point { int $x; } $p point; return $p->x->y;',
			'struct point { int $x; } $p point; $q point = $p; return 0;',
			'struct point { int $x; } $p point; $q point; $q = $p; return 0;',
			'struct point { int $x; } $p point; return $p;',
			'struct point { int $x; } function f(point $p): void {} return 0;',
			'struct point { int $x; } function f(point &$p): void {} return 0;',
			'struct point { int $x; } $p point[1] = []; return 0;',
			'struct point { int $x; } return $p->x; $p point;',
			'struct point { int $x; } $p point; $p->x = nothing(); function nothing(): void {} return 0;',

			'$a int[2] = [1]; return 0;',
			'$a int[1] = [1, 2]; return 0;',
			'$a int[1] = [1]; return $a[1];',
			'$a int[1] = [1]; $a[1] = 7; return 0;',
			'function f(int &$v): void {} $a int[1] = [1]; f($a[1]); return 0;',
			'$a int[1] = [1]; $i int = 0; return $a[$i];',
			'$a int[1] = [1]; return $a[-1];',
			'$a int[1] = [1]; return $a[999999999999999999999999999];',
			'$a int[0] = []; return $a[0];',
			'$a int[999999999999999999999999999] = []; return 0;',
			'$a int[1]; return $a[0];',
			'$a int[1] = [1]; $b int[1] = $a; return 0;',
			'$a int[1] = [1]; $a = [2]; return 0;',
			'$a int[1] = [1]; return $a;',
			'function f(int &$v): void {} $a int[1] = [1]; f($a); return 0;',
			'function f(int $v): void {} $a int[1] = [1]; f($a); return 0;',
			'$v int = 1; $a int[1] = [$v]; return 0;',
			'$a int[1][1] = [[1]]; return 0;',
			'$a int = 1; return $a[0];',
			'$a int[1] = [1]; return $a[0][0];',
			'$a int[1] = [1]; $i int = 0; $a[$i] = 7; return 0;',
			'function f(int &$v): void {} $a int[1] = [1]; $i int = 0; f($a[$i]); return 0;',

			'$a = 1; return $a;',
			'$a Other = 1; return $a;',
			'return $missing;',
			'$a int = 1; $a int = 2; return $a;',
			'return $a; $a int = 1;',
			'$a int = $a; return $a;',
			'$a int; return $a;',
			'return;',
			'return 2147483648;',
			'$a int = 1;',
			'return 1; return 2;',
			'function bad(): void { return 1; } return 0;',
			'function bad(void $a): void {} return 0;',
			'function bad(): int {} return 0;',
			'function bad(): int { return; } return 0;',
			'function bad(): Other { return 1; } return 0;',
			'function nothing(): void {} function bad(): int { return nothing(); } return 0;',
			'function outer(): void { function inner(): void {} } return 0;',
			'function bad(): void { $b int = $a; } $a int = 1; return $a;',
			'function inner(): void { $a int = 2; } return $a;',
			'function missing(): void { $a int = 1;',
			'function main(): void {} return 0;',
			'missing(); return 0;',
			'function same(): void {} function same(): void {} same(); return 0;',
			'function f(): void {} f(1); return 0;',
			'function f(int &$a): void { $a = 7; } f(1); return 0;',
			'function f(int &$a): void {} function value(): int { return 1; } f(value()); return 0;',
			'function f(int &$a): void {} f($a); $a int = 1; return $a;',
			'function f(int &$a): void {} $a int; f($a); return 0;',
			'function f(int &$a): void {} $a int = 1; f(&$a); return $a;',
			'$missing = 7; return 0;',
			'function f(int $a, int $b): int { return $a; } return f(1);',
			'function f(int $a): int { return $a; } return f(1, 2);',
			'function f(int $a): int { return $a; } function nothing(): void {} return f(nothing());',
			'function f(int $a,): void {} return 0;',
			'function f(int $a): void {} f(1,); return 0;',
			'function f(int $a): void {} return $a;',
			'function f(int $a, int $a): int { return $a; } return f(1, 2);',
			'function f(): void {} $a int = f(); return $a;',
		];
		foreach ($unsupported as $index => $source)
		{
			try {
				self::compile($directory . '/unsupported_' . $index, $source);
			}
			catch (\RuntimeException $error) {
				continue;
			}
			throw new \RuntimeException('Expected unsupported input failure: ' . $source);
		}
		file_put_contents($directory . '/executions.json', json_encode($executions, JSON_THROW_ON_ERROR));
		echo "OK: source pipeline, module records, literal/copy cases and unsupported-input failures\n";
	}

	/** Prove that the independent body block owns locals and declarations emit no call. */
	private static function check_function_scope(collected_file $file, llvm_module $module): void
	{
		$global = $file->root->structure->scope;
		$function = $global->functions_named('example')[0]->node->structure;
		$local = $function->body->structure->scope;
		if (($function->body->kind !== node_kind::block) || ($local === $global) || ($local->parent_scope() !== $global) || !$local->is_function() || (count($global->variables_named('a')) !== 1) || (count($local->variables_named('a')) !== 1)) {
			throw new \RuntimeException('Incorrect block or scope ownership');
		}
		if ((count($module->functions) !== 2) || ($module->functions[1]->return_type !== 'void') || str_contains($module->text, 'call ')) {
			throw new \RuntimeException('Function definition was not independently lowered');
		}
		if ((count($module->functions[0]->blocks[0]->instructions) !== 4) || (count($module->functions[1]->blocks[0]->instructions) !== 6)) {
			throw new \RuntimeException('Local storage or instructions leaked between functions');
		}
	}

	/** Create a fixture and verify that non-debug compilation is silent and retains no failed output. */
	private static function compile(string $directory, string $source): Compiler
	{
		mkdir($directory);
		file_put_contents($directory . '/main.phs', $source);
		$compiler = new Compiler();
		$compiler->init([$directory]);
		ob_start();
		try {
			$compiler->exec();
		}
		catch (\Throwable $error) {
			if (!Model::$llvm_files->is_empty()) {
				throw new \LogicException('Failed generation published output', 0, $error);
			}
			throw $error;
		}
		finally {
			$output = ob_get_clean();
		}
		if ($output !== '') {
			throw new \RuntimeException('Unexpected debug output');
		}
		return $compiler;
	}
}

LLVM_Test::run($argv[1]);
