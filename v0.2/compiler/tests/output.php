<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{Cpp_Emitter, Source_Frontend, entry_target, flavor, prepared_echo,
	prepared_output_string, prepared_reference, source_file};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

$outputs = [];
foreach ([
	[flavor::php, '$n int=8; echo $n;'],
	[flavor::cpp, 'int n=8; echo n;'],
	[flavor::js, 'let n: int=8; echo n;'],
] as [$flavor, $prefix])
{
	$entry = (new Source_Frontend())->prepare(new source_file('output',
		$prefix . ' echo "text\n\0"; return 8;', $flavor), new entry_target(31));
	verify($entry->statements[1] instanceof prepared_echo, 'Missing echo facts');
	$value = $entry->statements[1]->value;
	verify($value instanceof prepared_reference && $value->binding === $entry->statements[0]->binding,
		'Echo did not resolve its own local binding');
	$string = $entry->statements[2]->value;
	verify($string instanceof prepared_output_string && $string->bytes === "text\n\0", 'Incorrect literal facts');
	verify($entry->expression_visits === 4, 'Unexpected expression traversal count');
	$before = serialize($entry);
	$outputs[] = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering mutated prepared data');
}
verify(count(array_unique($outputs)) === 1, 'Flavor output differs');
echo "Echo facts, binding, traversal count and lowering purity passed\n";
