<?php

declare(strict_types=1);

require_once __DIR__ . '/../boot.php';

use scpp\compiler\{Cpp_Emitter, Program_Frontend, application_collection_type, callback_type_definition,
	entry_target, flavor, source_file, type_definition};

function verify(bool $ok, string $message): void
{
	if (!$ok) throw new RuntimeException($message);
}

$source = 'class Result { public int $id = 0; } class Item { public int $id = 1; }'
	. ' class Box { public int $id = 2; }'
	. 'function inspect(callback<vector<Item>,vector<Box>> $worker, vector<Box> $items):Result {return new Result();}'
	. 'return 0;';
$program = (new Program_Frontend())->prepare_sources('main.php',
	['main.php' => new source_file('main.php', $source, flavor::php)], new entry_target(31));
$signature = $program->files[$program->entry_path]->functions()[0];
$callback = $signature->parameters[0]->type_def();
verify($callback instanceof callback_type_definition, 'Expected a nested callback signature');
$result = $callback->result;
$input = $callback->inputs[0];
verify($result instanceof application_collection_type && $input instanceof application_collection_type,
	'Expected concrete callback collections');
verify($signature->parameters[1]->type_def() === $input, 'Repeated collection lost its canonical identity');
$expected = [$signature->return_type_def(), $result, ...array_values($result->concrete_types()),
	$input, ...array_values($input->concrete_types()), $input, ...array_values($input->concrete_types())];

$seen = [];
Cpp_Emitter::visit_signature_application_types($signature, static function (type_definition $type) use (&$seen): void {
	$seen[] = $type;
});
verify($seen === $expected, 'Signature dependency order or duplicate visits changed');

$seen = [];
$stop = new RuntimeException('Visitor stopped');
try {
	Cpp_Emitter::visit_signature_application_types($signature, static function (type_definition $type) use (&$seen, $result, $stop): void {
		$seen[] = $type;
		if ($type === $result) throw $stop;
	});
	throw new LogicException('Visitor failure did not propagate');
}
catch (RuntimeException $error) {
	verify($error === $stop && $seen === array_slice($expected, 0, 2), 'Walker continued after the visitor failed');
}

echo "Signature visitation order, canonical identity, and immediate failure passed\n";
