<?php

declare(strict_types=1);

require_once __DIR__ . '/boot.php';
require_once __DIR__ . '/host/syntax.php';
require_once __DIR__ . '/host/cli.php';

exit(\scpp\compiler\Compiler_Cli::run($argv));
