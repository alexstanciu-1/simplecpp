<?php

declare(strict_types=1);

if ((PHP_VERSION_ID < 80500) || (PHP_INT_SIZE !== 8)) {
	throw new RuntimeException('The compiler requires 64-bit PHP 8.5+');
}

require_once __DIR__ . '/compiler/flavor.php';
require_once __DIR__ . '/01_prepare_inputs/structures.php';
require_once __DIR__ . '/01_prepare_inputs/file.php';
require_once __DIR__ . '/compiler/diagnostics.php';
require_once __DIR__ . '/02_tokenize/structures.php';
require_once __DIR__ . '/02_tokenize/tokens.php';
require_once __DIR__ . '/03_parse/structures/abstract.php';
require_once __DIR__ . '/03_parse/structures/source.php';
require_once __DIR__ . '/03_parse/structures/common.php';
require_once __DIR__ . '/03_parse/structures/functions.php';
require_once __DIR__ . '/03_parse/structures/output.php';
require_once __DIR__ . '/03_parse/collect/structures.php';
require_once __DIR__ . '/03_parse/collect/collect.php';
require_once __DIR__ . '/compiler/symbols.php';
require_once __DIR__ . '/03_parse/cursor.php';
require_once __DIR__ . '/03_parse/parser.php';
require_once __DIR__ . '/03_parse/flavors/cpp.php';
require_once __DIR__ . '/03_parse/flavors/php.php';
require_once __DIR__ . '/03_parse/flavors/js.php';
require_once __DIR__ . '/compiler/frontend.php';

require_once __DIR__ . '/04_analyze/prepare/structures.php';
require_once __DIR__ . '/04_analyze/prepare/output.php';
require_once __DIR__ . '/04_analyze/prepare/prepare.php';
require_once __DIR__ . '/04_analyze/prepare/functions.php';
require_once __DIR__ . '/05_backend/cpp/emit.php';
require_once __DIR__ . '/05_backend/cpp/output.php';
require_once __DIR__ . '/05_backend/cpp/publication.php';
require_once __DIR__ . '/06_native/target.php';

require_once __DIR__ . '/01_prepare_inputs/program.php';
require_once __DIR__ . '/04_analyze/prepare/program.php';
require_once __DIR__ . '/compiler/program.php';
require_once __DIR__ . '/05_backend/cpp/structures.php';
require_once __DIR__ . '/05_backend/cpp/program.php';
require_once __DIR__ . '/05_backend/cpp/bundle.php';
