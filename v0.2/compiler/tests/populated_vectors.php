<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter};

foreach (flavor::cases() as $flavor) {
	$source = match ($flavor) {
		flavor::php => '$values vector<int> = [true, 9.75, 8]; echo $values[0], $values[1]; return 8;',
		flavor::cpp => 'vector<int> values = [true, 9.75, 8]; echo values[0], values[1]; return 8;',
		flavor::js => 'let values: vector<int> = [true, 9.75, 8]; echo values[0], values[1]; return 8;',
	};
	$entry = (new Source_Frontend())->prepare(new source_file('literal', $source, $flavor), new entry_target(31));
	if ($entry->file->declaration_nodes[0]->body()->expression_visits !== 11) {
		throw new RuntimeException('Initializer or element was visited more than once');
	}
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	if (serialize($entry) !== $before) throw new RuntimeException('Lowering modified prepared facts');
	if (!str_contains($cpp, '.append(') || str_contains($cpp, 'std::vector<')) {
		throw new RuntimeException('Literal did not reuse concrete append binding');
	}
}
echo "Populated vector conversion and pure lowering checks passed\n";
