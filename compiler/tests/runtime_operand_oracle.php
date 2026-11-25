<?php

declare(strict_types=1);

require_once __DIR__ . '/../boot.php';

use scpp\compiler\{ast_call_expression, ast_integer_expression, ast_type_marker, runtime_function, type_registry};

foreach (json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR) as [$receiver, $written, $name, $qualified]) {
	$nodes = [];
	foreach (['R', 'A', 'B', 'T'] as $position => $key) {
		$nodes[$key] = $key === 'T' ? new ast_type_marker('Color', $position, $position + 1)
			: new ast_integer_expression($position, $position + 1);
	}
	$arguments = array_map(static fn(string $key) => $nodes[$key], $written);
	$call = new ast_call_expression(0, 8, $name, null, $arguments, $nodes[$receiver] ?? null);
	$binding = new runtime_function($name, [], type_registry::integer(), 'fixture', '', type_qualified: $qualified);
	$operands = $call->runtime_operands($binding);
	$call->settle_runtime($binding);
	if ($call->runtime_operands() !== $operands) throw new LogicException('Settled projection differs');
	echo count($operands), ':';
	foreach ($operands as $operand) {
		$key = array_search($operand, $nodes, true);
		if ($key === false) throw new LogicException('Operand identity lost');
		echo $key;
	}
	echo ';';
}
