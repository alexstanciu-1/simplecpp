<?php
declare(strict_types=1);

// Experimental per-file generation. Timing is deliberately outside native builds.
// The driver validates source metadata before invoking this bounded existing S2S path.
$repo = $argv[1];
$app = $argv[2];
$snapshot = json_decode(file_get_contents(dirname($app) . '/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
if (realpath($app) === realpath($snapshot['source'])) {
	throw new RuntimeException('Refusing to modify the original workload');
}
require $repo . '/bin/bootstrap.php';
$catalog = json_decode(file_get_contents($app . '/.prism/cache/declared_type_kind_catalog.json'), true, flags: JSON_THROW_ON_ERROR);
$generator = new Scpp\S2S\Transpiler(phpProfile: 'strict');
$generator->setDeclaredTypeKinds($catalog['declared_type_kinds']);
foreach (array_slice($argv, 3) as $source) {
	if (str_contains($source, '..') || str_starts_with($source, '/')) {
		throw new RuntimeException('Expected an application-relative source path');
	}
	$output = $generator->transpile($app . '/' . $source, false, $source === 'main.phs');
	if ($output->errors !== []) {
		throw new RuntimeException(implode("\n", $output->errors));
	}
	$base = $app . '/.prism/generated/' . substr($source, 0, -4);
	foreach (['hpp' => $output->headerLines, 'cpp' => $output->sourceLines] as $suffix => $lines) {
		$path = $base . '.' . $suffix;
		$text = implode(PHP_EOL, $lines) . PHP_EOL;
		if (!is_file($path) || file_get_contents($path) !== $text) {
			file_put_contents($path, $text);
		}
	}
}
