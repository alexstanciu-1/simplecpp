#!/usr/bin/env php
<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/php/input.php';
require_once __DIR__ . '/php/publication.php';
require_once __DIR__ . '/php/target.php';
require_once __DIR__ . '/php/scalars.php';
require_once __DIR__ . '/php/families.php';
require_once __DIR__ . '/php/render.php';
require_once __DIR__ . '/php/render_sequences.php';
require_once __DIR__ . '/php/render_modules.php';

function run(array $args): array {
    requireThat(PHP_VERSION_ID >= 80500 && PHP_INT_SIZE === 8, 'The API generator requires 64-bit PHP 8.5 or later');
    $command = $args[0] ?? '';
    if($command === 'validate-target' && count($args) === 3) {
        $facts = targetFacts(decode(readFile($args[1]), 'target facts'), decode(readFile($args[2]), 'target context'));
        return ['context_id' => $facts['id'], 'target' => $facts['target'], 'standard_library' => $facts['standard_library'], 'status' => 'valid'];
    }
    $emit = in_array($command, ['emit-runtime', 'emit-scalars'], true);
    requireThat(($emit && in_array(count($args), [6, 7], true)) || (!$emit && count($args) === 5 && in_array($command, ['prepare-runtime', 'prepare-scalars', 'prepare-integer-construction'], true)), 'Usage: generate.php emit-runtime|emit-scalars METADATA PUBLICATION FACTS CONTEXT OUTPUT [--check] | validate-target FACTS CONTEXT | prepare-runtime|prepare-scalars|prepare-integer-construction METADATA PUBLICATION FACTS CONTEXT');
    $check = count($args) === 7;
    requireThat(!$check || $args[6] === '--check', 'Unknown generator option');
    $facts = targetFacts(decode(readFile($args[3]), 'target facts'), decode(readFile($args[4]), 'target context'));
    $metadata = decode(readFile($args[1]), 'runtime metadata');
    $publication = decode(readFile($args[2]), 'publication');
    $runtime = in_array($command, ['emit-runtime', 'prepare-runtime'], true);
    $plan = $runtime ? prepareRuntime($metadata, $publication, $facts):prepareScalars($metadata, $publication, $facts, $command === 'prepare-integer-construction');
    if(!$emit) {
        return $plan;
    }
    $files = render($plan);
    publish($files, $args[5], $check);
    return ['status' => $check ? 'verified':'generated', 'artifacts' => count($files)];
}
try {
    echo encode(run(array_slice($argv, 1))), "\n";
}
catch(\Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
