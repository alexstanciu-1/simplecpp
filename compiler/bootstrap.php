<?php
declare(strict_types=1);

// Host composition only; loading the stage does not read inputs or run a compiler.
require_once __DIR__ . '/../tools/php_portability/runtime/bootstrap.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_manifest/data/project_manifest.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_manifest/utilities/manifest_syntax.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_manifest/manifest_reader.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_sources/utilities/paths.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_sources/data/listing.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_sources/scan.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_sources/main_discover_sources.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_sources/data/buffer.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_sources/read.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_sources/main_read_sources.php';
