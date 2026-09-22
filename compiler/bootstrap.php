<?php
declare(strict_types=1);

// Host composition only; loading the stage does not read inputs or run a compiler.
require_once __DIR__ . '/../tools/php_portability/runtime/bootstrap.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_manifest/data/project_manifest.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_manifest/utilities/manifest_syntax.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_manifest/manifest_reader.php';
