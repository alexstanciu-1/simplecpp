<?php

declare(strict_types=1);
require_once ($argv[1] ?? __DIR__ . '/../boot.php');
use scpp\compiler\{Cpp_Emitter, Source_Frontend, entry_target, flavor, source_file, diagnostic, diagnostic_group, type_registry};

/** Compare admission and checked reads on the complete source path for this storage context. */
function implicit_body(string $source, string $target, string $context): ?string
{
	$body = match ($context) {
		'init' => "function f($source &\$v): $target { \$x $target = \$v; return \$x; }",
		'assign' => "function f($source &\$v, $target &\$x): void { \$x = \$v; }",
		'arg' => "function sink($target \$x): void {} function f($source &\$v): void { sink(\$v); }",
		'return' => "function f($source &\$v): $target { return \$v; }",
	};
	try {
		$entry = (new Source_Frontend())->prepare(new source_file('implicit', $body . 'return 8;', flavor::php), new entry_target(31));
		return (new Cpp_Emitter())->emit($entry);
	} catch (diagnostic|diagnostic_group $error) {
		return null;
	}
}

// Passing a carrier across numeric domains is a separate choice from converting a plain payload.
if (implicit_body('result<int>', '?float', 'arg') !== null || implicit_body('result<int>', 'result<bool>', 'arg') !== null) {
	throw new RuntimeException('Call accepted a different carrier payload type');
}

foreach (['int', 'bool', 'string', 'float', 'mixed', 'dynamic'] as $payload) {
	foreach (['nullable', 'result', 'result_or_false', 'result_or_bool'] as $family) {
		$carrier = type_registry::specialize($family, type_registry::resolve($payload));
		if ($carrier === null) continue;
		foreach (['init', 'assign', 'arg', 'return'] as $context) {
			$copy = implicit_body("$family<$payload>", "$family<$payload>", $context);
			if ($copy === null || str_contains($copy, '.value()')) throw new RuntimeException("State copy failed in $context");
		}
		if (!$carrier->accepts_empty()) {
			foreach ([$payload, "?$payload"] as $input) {
				if ($input[0] === '?' && type_registry::specialize('nullable', type_registry::resolve($payload)) === null) continue;
				if (implicit_body($input, "$family<$payload>", 'arg') !== null) throw new RuntimeException("Implicit result argument from $input");
			}
			foreach (['nullable', 'result', 'result_or_false', 'result_or_bool'] as $other) {
				if ($other === $family || type_registry::specialize($other, type_registry::resolve($payload)) === null) continue;
				$cpp = implicit_body("$family<$payload>", "$other<$payload>", 'arg');
				$expected = $other === 'nullable' && $carrier->implicit_extraction();
				if (($cpp !== null) !== $expected || ($cpp !== null && substr_count($cpp, '(var_v_0).value()') !== 1)) {
					throw new RuntimeException("Unexpected carrier call from $family<$payload> to $other<$payload>");
				}
			}
		}
		foreach (['bool', 'int', 'byte', 'float', 'string', 'mixed', 'dynamic'] as $target) {
			foreach (['init', 'assign', 'arg', 'return'] as $context) {
				$expected = $carrier->implicit_extraction() && implicit_body($payload, $target, $context) !== null;
				$cpp = implicit_body("$family<$payload>", $target, $context);
				if (($cpp !== null) !== $expected || ($cpp !== null && substr_count($cpp, '(var_v_0).value()') !== 1)) {
					throw new RuntimeException("Unexpected implicit $context from $family<$payload> to $target");
				}
			}
		}
	}
}
