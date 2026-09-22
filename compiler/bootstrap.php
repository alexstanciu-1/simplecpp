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
require_once __DIR__ . '/src/02_tokenize/structures.php';
require_once __DIR__ . '/src/02_tokenize/store.php';
require_once __DIR__ . '/src/02_tokenize/tokenize.php';
require_once __DIR__ . '/src/02_tokenize/main_tokenize.php';
require_once __DIR__ . '/src/03_parse/data/nodes.php';
require_once __DIR__ . '/src/03_parse/data/tree.php';
require_once __DIR__ . '/src/03_parse/utilities/binary_syntax.php';
require_once __DIR__ . '/src/03_parse/data/expression_state.php';
require_once __DIR__ . '/src/03_parse/handlers/expressions.php';
require_once __DIR__ . '/src/03_parse/data/result.php';
require_once __DIR__ . '/src/03_parse/handlers/statements.php';
require_once __DIR__ . '/src/03_parse/handlers/control_statements.php';
require_once __DIR__ . '/src/03_parse/handlers/declarations.php';
require_once __DIR__ . '/src/03_parse/handlers/metaprogramming.php';
require_once __DIR__ . '/src/03_parse/parse_file.php';
require_once __DIR__ . '/src/03_parse/data/role_views.php';
require_once __DIR__ . '/src/03_parse/utilities/metaprogramming_syntax.php';
require_once __DIR__ . '/src/03_parse/utilities/struct_member_cursor.php';
require_once __DIR__ . '/src/03_parse/utilities/syntax_access.php';
require_once __DIR__ . '/src/03_parse/utilities/syntax_comparer.php';
require_once __DIR__ . '/src/03_parse/data/store.php';
require_once __DIR__ . '/src/03_parse/select_tasks.php';
require_once __DIR__ . '/src/03_parse/join.php';
require_once __DIR__ . '/src/03_parse/main_parse.php';
