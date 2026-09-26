<?php

namespace scpp\compiler;

\define('dbg', false);
require_once dirname(__DIR__) . '/boot.php';

final class Calls_Test
{
	/** Exercise dependency creation from actual call uses, independent of sample names. */
	public static function run(string $directory): void
	{
		$cases = [
			'unused' => [
				['caller.phs' => 'return 1;', 'provider.phs' => 'function unused(): void {}'],
				[], 0,
			],
			'repeated' => [
				['caller.phs' => 'do_work(); do_work(); return 1;', 'provider.phs' => 'function do_work(): void {} function unused(): void {}'],
				['declare void @do__work()'], 2,
			],
			'local_forward' => [
				['caller.phs' => 'later(); return 1; function later(): void {}'],
				[], 1,
			],
			'function_body' => [
				['caller.phs' => 'function wrapper(): void { remote(); } wrapper(); return 1;', 'provider.phs' => 'function remote(): void {}'],
				['declare void @remote()'], 2,
			],
		];
		$cases['value_initializer'] = [
			['caller.phs' => '$a int = make_value(); return $a;', 'provider.phs' => 'function make_value(): int { $a int = 3; return $a; }'],
			['declare i32 @make__value()'], 1, 3,
		];
		$cases['return_chain'] = [
			['caller.phs' => 'function forward(): int { return remote(); } return forward();', 'provider.phs' => 'function remote(): int { return 7; }'],
			['declare i32 @remote()'], 2, 7,
		];
		$cases['multiple_arguments'] = [
			['caller.phs' => '$a int = 7; return pick(1, $a, 9);', 'provider.phs' => 'function pick(int $first, int $second, int $third): int { return $second; }'],
			['declare i32 @pick(i32, i32, i32)'], 1, 7,
		];
		$cases['nested_arguments'] = [
			['caller.phs' => 'return pick(identity(2), identity(8));', 'provider.phs' => 'function identity(int $value): int { return $value; } function pick(int $first, int $second): int { return $second; }'],
			['declare i32 @identity(i32)', 'declare i32 @pick(i32, i32)'], 3, 8,
		];
		$cases['parameter_forwarding'] = [
			['caller.phs' => 'function forward(int $value): int { return remote($value, $value); } $value int = 6; return forward($value);', 'provider.phs' => 'function remote(int $value, int $other): int { return $other; }'],
			['declare i32 @remote(i32, i32)'], 2, 6,
		];
		$cases['void_parameters'] = [
			['caller.phs' => 'consume(1, 2); consume(3, 4); return 5;', 'provider.phs' => 'function consume(int $first, int $second): void { $copy int = $second; }'],
			['declare void @consume(i32, i32)'], 2, 5,
		];
		$cases['reference_mutation'] = [
			['caller.phs' => '$a int = 1; change($a, 7); return $a;', 'provider.phs' => 'function change(int &$value, int $next): void { $value = $next; }'],
			['declare void @change(ptr, i32)'], 1, 7,
		];
		$cases['value_isolation'] = [
			['caller.phs' => '$a int = 1; change($a, 7); return $a;', 'provider.phs' => 'function change(int $value, int $next): void { $value = $next; }'],
			['declare void @change(i32, i32)'], 1, 1,
		];
		$cases['reference_forwarding'] = [
			['caller.phs' => 'function forward(int &$a): void { change($a); } $a int = 1; forward($a); return $a;', 'provider.phs' => 'function change(int &$value): void { $value = 8; }'],
			['declare void @change(ptr)'], 2, 8,
		];
		$cases['reference_aliasing'] = [
			['caller.phs' => '$a int = 1; return change($a, $a);', 'provider.phs' => 'function change(int &$first, int &$second): int { $first = 9; return $second; }'],
			['declare i32 @change(ptr, ptr)'], 1, 9,
		];
		$cases['mixed_evaluation'] = [
			['caller.phs' => '$a int = 1; return select($a, change($a));', 'provider.phs' => 'function change(int &$a): int { $a = 8; return $a; } function select(int $first, int $second): int { return $first; }'],
			['declare i32 @change(ptr)', 'declare i32 @select(i32, i32)'], 2, 1,
		];
		$cases['array_reference'] = [
			['caller.phs' => '$values int[3] = [1, 2, 3]; $values[1] = 7; change($values[0], 9); return $values[0];', 'provider.phs' => 'function change(int &$value, int $next): void { $value = $next; }'],
			['declare void @change(ptr, i32)'], 1, 9,
		];
		$cases['array_value_argument'] = [
			['caller.phs' => '$values int[2] = [4, 8]; return identity($values[1]);', 'provider.phs' => 'function identity(int $value): int { return $value; }'],
			['declare i32 @identity(i32)'], 1, 8,
		];
		$cases['struct_fields'] = [
			['caller.phs' => 'struct point { int $x; public int $y; } $p point; $p->x = 4; $p->y = $p->x; return $p->y;'],
			[], 0, 4,
		];
		$cases['struct_default'] = [
			['caller.phs' => '$p point; return $p->y;', 'provider.phs' => 'struct point { int $x; int $y; }'],
			[], 0, 0,
		];
		$cases['struct_reference'] = [
			['caller.phs' => '$p point; change($p->y, 9); return $p->y;', 'provider.phs' => 'struct point { int $x; int $y; } function change(int &$value, int $next): void { $value = $next; }'],
			['declare void @change(ptr, i32)'], 1, 9,
		];
		$cases['struct_value_argument'] = [
			['caller.phs' => 'struct box { int $value; } $b box; $b->value = 6; return identity($b->value);', 'provider.phs' => 'function identity(int $value): int { return $value; }'],
			['declare i32 @identity(i32)'], 1, 6,
		];
		$cases['struct_local_isolation'] = [
			['caller.phs' => 'struct box { int $value; } function local(): int { $b box; $b->value = 8; return $b->value; } $b box; $b->value = 3; local(); return $b->value;'],
			[], 1, 3,
		];
		$cases['struct_and_array'] = [
			['caller.phs' => 'struct box { int $value; } $b box; $a int[1] = [7]; $b->value = $a[0]; $a[0] = 9; return $b->value;'],
			[], 0, 7,
		];
		$cases['template_reuse'] = [
			['caller.phs' => '$a int = identity<int>(3); return identity<int>($a);', 'provider.phs' => 'template<T> function identity(T $value): T { $copy T = $value; return $copy; }'],
			['declare i32 @identity_x3C_int_x3E_(i32)'], 2, 3,
		];
		$cases['template_forward'] = [
			['caller.phs' => 'return forward<int>(8);', 'provider.phs' => 'template<typename T> function forward(T $value): T { return identity<T>($value); } template<T> function identity(T $value): T { return $value; }'],
			['declare i32 @forward_x3C_int_x3E_(i32)'], 1, 8,
		];
		$cases['template_multi'] = [
			['caller.phs' => 'template<T, U> function second(T $a, U $b): U { return $b; } return second<int, int>(2, 7);'],
			[], 1, 7,
		];
		$cases['template_unused'] = [
			['caller.phs' => 'template<T> function identity(T $a): T { return $a; } return 1;'],
			[], 0, 1,
		];
		$cases['template_recursive_registration'] = [
			['caller.phs' => 'template<T> function again(T $a): T { return again<T>($a); } function unused(): void { again<int>(1); } return 1;'],
			[], 2, 1,
		];
		foreach ($cases as $name => $case)
		{
			[$sources, $expected_external, $expected_calls] = $case;
			$expected_exit = $case[3] ?? 1;
			$folder = $directory . '/' . $name;
			mkdir($folder);
			foreach ($sources as $path => $source) {
				file_put_contents($folder . '/' . $path, $source);
			}
			$c = new Compiler();
			$c->init([$folder]);
			$c->tokenize();
			$c->parse();
			$before = serialize(Model::$collected_files);
			$c->llvm();
			if (serialize(Model::$collected_files) !== $before) {
				throw new \RuntimeException('Preparation changed retained source or collection');
			}
			$caller = Model::$llvm_files[0];
			if (($name === 'template_unused') && (count($caller->functions) !== 1)) {
				throw new \RuntimeException('Unused template emitted executable code');
			}
			if (($name === 'template_reuse') && (count(Model::$llvm_files[1]->functions) !== 1)) {
				throw new \RuntimeException('Repeated template use emitted duplicate implementations');
			}
			if (($caller->external_functions !== $expected_external) || (preg_match_all('/\bcall (?:void|i32) @/', $caller->text) !== $expected_calls)) {
				throw new \RuntimeException('Incorrect dependencies or emitted calls for ' . $name);
			}
			if ($name === 'nested_arguments') {
				if (!str_contains($caller->text, "%_Gv0 = call i32 @identity(i32 2)\n    %_Gv1 = call i32 @identity(i32 8)\n    %_Gv2 = call i32 @pick(i32 %_Gv0, i32 %_Gv1)")) {
					throw new \RuntimeException('Arguments were not evaluated in source order');
				}
			}
			if (isset(Model::$llvm_files[1]) && (Model::$llvm_files[1]->external_functions !== [])) {
				throw new \RuntimeException('Unused dependency added to provider');
			}
			foreach (Model::$llvm_files as $module) {
				file_put_contents($folder . '/' . $module->file_name, $module->text);
			}
			$result = (new Native_Runner())->run(Model::$llvm_files);
			if (($result->build->exit_code !== 0) || ($result->execution?->exit_code !== $expected_exit)) {
				throw new \RuntimeException('Native call test failed: ' . $name . ' ' . $result->build->stderr);
			}
			echo "$name: dependencies verified, native exit $expected_exit\n";
		}
	}
}

Calls_Test::run($argv[1]);
