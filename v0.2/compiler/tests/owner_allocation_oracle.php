<?php

declare(strict_types=1);

require_once __DIR__ . '/../boot.php';

use scpp\compiler\{ast_record_declaration, Cpp_Emitter, entry_target, flavor, Program_Frontend, source_file};

$source = stream_get_contents(STDIN);
$program = (new Program_Frontend())->prepare_sources('entry',
	['entry' => new source_file('entry.php', $source, flavor::php)], new entry_target(31));
$emitter = new Cpp_Emitter();
$parameters = new ReflectionMethod($emitter, 'constructor_parameters');
$factory = new ReflectionMethod($emitter, 'allocation_factory');
$rows = [];
foreach ($program->entry->file->declarations as $declaration) {
	if (!($declaration instanceof ast_record_declaration)) continue;
	$type = $declaration->definition->shared;
	$owner = $type->cpp_type();
	$payload = $type->payload->cpp_type();
	$headers = [];
	$text = $emitter->owner_methods($type, $headers);
	$guard_line = strpos($text, "\n");
	$begin = $guard_line === false ? false : strpos($text, "\n", $guard_line + 1);
	if ($begin === false) throw new LogicException('Owner header guard is incomplete');
	$begin++;
	$end = strpos($text, "\ninline $payload* {$owner}::operator->()", $begin);
	if ($end === false) throw new LogicException('Allocation fragment boundary not found');
	$constructor = $type->payload->constructor();
	$rows[] = [
		'abstract' => $declaration->is_abstract,
		'owner' => $owner,
		'payload' => $payload,
		'parameters' => $constructor === null ? null : $parameters->invokeArgs($emitter, [$constructor, &$headers]),
		'factory' => ($constructor === null && !$declaration->is_abstract) ? $factory->invokeArgs($emitter, [$type, &$headers]) : '',
		'fragment' => substr($text, $begin, $end - $begin),
	];
}
echo json_encode($rows, JSON_THROW_ON_ERROR);
