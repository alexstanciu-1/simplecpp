<?php

declare(strict_types=1);

if ((\PHP_VERSION_ID < 80500) || (\PHP_INT_SIZE !== 8)) {
	throw new RuntimeException('The compiler requires 64-bit PHP 8.5+');
}

require_once __DIR__ . '/compiler/flavor.php';
require_once __DIR__ . '/compiler/names.php';
require_once __DIR__ . '/compiler/dependencies.php';
require_once __DIR__ . '/01_prepare_inputs/structures.php';
require_once __DIR__ . '/01_prepare_inputs/modules.php';
require_once __DIR__ . '/01_prepare_inputs/file.php';
require_once __DIR__ . '/compiler/diagnostics.php';
require_once __DIR__ . '/02_tokenize/structures.php';
require_once __DIR__ . '/02_tokenize/tokens.php';
require_once __DIR__ . '/03_parse/structures/abstract.php';
require_once __DIR__ . '/03_parse/structures/imports.php';
require_once __DIR__ . '/03_parse/structures/source.php';
require_once __DIR__ . '/03_parse/structures/common.php';
require_once __DIR__ . '/03_parse/structures/blocks.php';
require_once __DIR__ . '/03_parse/structures/functions.php';
require_once __DIR__ . '/03_parse/structures/parameters.php';
require_once __DIR__ . '/03_parse/structures/globals.php';
require_once __DIR__ . '/03_parse/structures/output.php';
require_once __DIR__ . '/03_parse/structures/strings.php';
require_once __DIR__ . '/03_parse/collect/structures.php';
require_once __DIR__ . '/03_parse/collect/scope.php';
require_once __DIR__ . '/03_parse/collect/collect.php';
require_once __DIR__ . '/03_parse/collect/diagnostics.php';
require_once __DIR__ . '/compiler/symbols.php';
require_once __DIR__ . '/03_parse/cursor.php';
require_once __DIR__ . '/03_parse/namespaces.php';
require_once __DIR__ . '/03_parse/imports.php';
require_once __DIR__ . '/03_parse/globals.php';
require_once __DIR__ . '/03_parse/parser.php';
require_once __DIR__ . '/03_parse/flavors/cpp.php';
require_once __DIR__ . '/03_parse/flavors/php.php';
require_once __DIR__ . '/03_parse/flavors/js.php';
require_once __DIR__ . '/compiler/frontend.php';

require_once __DIR__ . '/04_analyze/prepare/structures.php';
require_once __DIR__ . '/04_analyze/prepare/strings.php';
require_once __DIR__ . '/types/conversion.php';
require_once __DIR__ . '/types/definition.php';
require_once __DIR__ . '/generated/types/int64.php';
require_once __DIR__ . '/generated/types/null.php';
require_once __DIR__ . '/generated/types/bool.php';
require_once __DIR__ . '/generated/types/byte.php';
require_once __DIR__ . '/generated/types/float64.php';
require_once __DIR__ . '/generated/types/string.php';
require_once __DIR__ . '/generated/types/registry.php';
require_once __DIR__ . '/types/void.php';
require_once __DIR__ . '/04_analyze/prepare/globals.php';
require_once __DIR__ . '/04_analyze/prepare/prepare.php';
require_once __DIR__ . '/04_analyze/prepare/functions.php';
require_once __DIR__ . '/04_analyze/prepare/imports.php';
require_once __DIR__ . '/05_backend/cpp/emit.php';
require_once __DIR__ . '/05_backend/cpp/strings.php';
require_once __DIR__ . '/05_backend/cpp/publication.php';
require_once __DIR__ . '/06_native/target.php';

require_once __DIR__ . '/01_prepare_inputs/program.php';
require_once __DIR__ . '/01_prepare_inputs/manifest.php';
require_once __DIR__ . '/01_prepare_inputs/changes.php';
require_once __DIR__ . '/04_analyze/prepare/program.php';
require_once __DIR__ . '/compiler/program.php';
require_once __DIR__ . '/compiler/incremental.php';
require_once __DIR__ . '/05_backend/cpp/structures.php';
require_once __DIR__ . '/05_backend/cpp/program.php';
require_once __DIR__ . '/05_backend/cpp/bundle.php';
require_once __DIR__ . '/compiler/project.php';

require_once __DIR__ . '/04_analyze/prepare/incremental.php';
