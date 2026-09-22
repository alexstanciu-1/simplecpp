<?php
declare(strict_types=1);

// Standalone tool composition; framework primitives plus the bounded process executor.
require_once __DIR__ . '/../../tools/php_portability/runtime/bootstrap.php';
require_once __DIR__ . '/../tool_process/process.php';
require_once __DIR__ . '/files.php';
require_once __DIR__ . '/definitions.php';
require_once __DIR__ . '/resources.php';
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/records.php';
require_once __DIR__ . '/symbols.php';
require_once __DIR__ . '/bridge.php';
require_once __DIR__ . '/clang.php';
require_once __DIR__ . '/metadata.php';
require_once __DIR__ . '/publication.php';
require_once __DIR__ . '/reservation.php';
require_once __DIR__ . '/candidate.php';
require_once __DIR__ . '/prepare.php';
require_once __DIR__ . '/requests.php';
require_once __DIR__ . '/request_adapter.php';

// Records-only shared contracts; no compiler session or stage execution.
require_once __DIR__ . '/../src/04_analyze/type_model/data/type_references.php';
require_once __DIR__ . '/../src/04_analyze/type_model/data/semantic_calls.php';
require_once __DIR__ . '/../src/04_analyze/type_model/data/generic.php';
require_once __DIR__ . '/../src/04_analyze/type_model/data/lifecycle.php';
require_once __DIR__ . '/../src/04_analyze/type_model/data/families.php';
require_once __DIR__ . '/../src/04_analyze/type_model/data/source_families.php';
require_once __DIR__ . '/../src/04_analyze/type_model/family_contracts.php';
require_once __DIR__ . '/native_types.php';
require_once __DIR__ . '/families/structures.php';
require_once __DIR__ . '/families/catalog.php';
require_once __DIR__ . '/families/arguments.php';
require_once __DIR__ . '/families/requests.php';
require_once __DIR__ . '/../src/compile/join.php';
require_once __DIR__ . '/families/store.php';
require_once __DIR__ . '/families/prepare.php';
require_once __DIR__ . '/families/join.php';

// Project modules have explicit compiler imports, separate from ordinary package closure.
require_once __DIR__ . '/project/structures.php';
require_once __DIR__ . '/project/adapter.php';
require_once __DIR__ . '/project/source_adapter.php';
require_once __DIR__ . '/project/module.php';
