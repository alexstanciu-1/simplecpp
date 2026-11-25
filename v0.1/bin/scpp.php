#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/project_services.php';

try {
	main($argv);
} catch (ScppCliException $exception) {
	scpp_write($exception->getMessage(), $exception->stream);
	exit($exception->exitCode);
}
