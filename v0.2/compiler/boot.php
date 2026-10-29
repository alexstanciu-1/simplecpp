<?php

declare(strict_types=1);

if ((PHP_VERSION_ID < 80500) || (PHP_INT_SIZE !== 8)) {
	throw new RuntimeException('The compiler requires 64-bit PHP 8.5 or newer');
}

require_once __DIR__ . '/compiler/flavor.php';
require_once __DIR__ . '/01_prepare_inputs/structures.php';
require_once __DIR__ . '/01_prepare_inputs/file.php';
require_once __DIR__ . '/compiler/diagnostics.php';
require_once __DIR__ . '/02_tokenize/structures.php';
require_once __DIR__ . '/02_tokenize/tokens.php';
require_once __DIR__ . '/compiler/frontend.php';
